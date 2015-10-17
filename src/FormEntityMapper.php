<?php
namespace DoctrineMapper;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Kdyby\Doctrine\MissingClassException;
use Nette\Object;
use Nette\Reflection\ClassType;
use Nette\Utils\ArrayHash;
use Nette\Utils\Callback;
use DoctrineMapper\Exception\MapperException;

/**
 * Simple service to mapping ArrayHash values (Form result) to entity
 *
 * @author Pecina Ondřej <pecina.ondrej@gmail.com>
 */
class FormEntityMapper extends BaseMapper
{
	/**
	 * Dynamically map values to entity
	 * Can map simple values as int, boolean, string
	 * Now can map simple relation ship when you are using id/ids
	 *
	 * @param ArrayHash $values
	 * @param object $entity
	 * @param array $columns
	 * @return Object $entity
	 * @throws MapperException
	 */
	public function setValuesToEntity(ArrayHash $values, $entity, array $columns = array())
	{
		if (!is_object($entity)) {
			throw new MapperException(sprintf("Entity have to be object, %s given", gettype($entity)));
		}

		if (empty ($columns)) {
			$columns = array_keys((array) $values);
		}

		foreach($columns as $column) {
			$setterName = 'set' . ucfirst($column);
			if(method_exists($entity, $setterName) && isset($values->$column)) {
				// try to find simple connection to entity
				$propertyType = $this->readPropertyDataType($entity, $column);
				$value = $values->$column;

				// if is connected entity try find value into storage
				if ($propertyType !== NULL) {
					// Base PHP Objets
					if (!$propertyType['relation']) {
						if ($value !== NULL && $value !== '') {
							if ($propertyType['type'] === \DateTime::class) {
								// try with time
								$value = $this->dateParser->parseDateTime($value);
							} else if ($propertyType['type'] === 'integer') {
								$value = (int) $value;
							} else if ($propertyType['type'] === 'boolean') {
								$value = (bool) $value;
							}
						}
					}
					// Entities
					else if (!empty ($value)) {
						// try to find repository
						$repository = $this->findRepository($propertyType['type'], $entity);

						// maybe many to many - one to many
						if ($propertyType['collection']) {
							$pk = $this->getEntityPrimaryKeyName($this->findEntityWholeName($propertyType['type'], $entity));
							if ($value instanceof ArrayHash && isset($value[0]) && $value[0] instanceof ArrayHash) {
								$newValues = new ArrayCollection();
								$updatedValues = array();
								foreach ($value as $val) {
									$entity = new $propertyType['type'];

									// exists key load
									if (isset($val->$pk) && !empty($val->$pk)) {
										$pkValue = $val->$pk;
										$updatedValues[$pkValue] = 1;
										$entity = $repository->find($pkValue);
									}

									$entity = $this->setValuesToEntity($val, $entity);
									$newValues->add($entity);
								}
								/** @var ArrayCollection $oldValues */
								$oldValues = $this->invokeGetter($column, $entity);
								if ($oldValues !== NULL) {
									foreach ($oldValues as $val) {
										$pkValue = $this->invokeGetter($pk, $val);
										if (array_key_exists($pkValue, $updatedValues)) {
											// remove relation
											$this->invokeGetter($column, $entity)->removeElement($val);
										}
									}
								}
								$value = $newValues;
							} else {

								$value = new ArrayCollection($repository->findBy(array(
									$pk => (array) $value
								)));
							}
						}
						else {
							// many to one
							if ($value instanceof ArrayHash) {
								$pk = $this->getEntityPrimaryKeyName($this->findEntityWholeName($propertyType['type'], $entity));
								$entity = new $propertyType['type'];

								// exists key = load
								if (isset($value->$pk) && !empty($value->$pk)) {
									$pkValue = $value->$pk;
									$entity = $repository->find($pkValue);
								}

								$value = $this->setValuesToEntity($value, $entity);
							}
							else {
								$value = $repository->find($value);
							}
						}
					}
				}

				// if empty ? set NULL
				if (empty ($value) && $value !== FALSE) {
					$value = NULL;
				}

				Callback::invokeArgs(array($entity, $setterName), [$value]);
			}
		}

		return $entity;
	}

	/**
	 * Try to find repository for Doctrine relation
	 *
	 * @param string $className
	 * @param object $entity
	 * @return \Kdyby\Doctrine\EntityRepository|null
	 * @throws MapperException
	 */
	private function findRepository($className, Object $entity)
	{
		$repository = NULL;
		$existingClass = $this->findEntityWholeName($className, $entity);

		// if found existing class - try to find repository
		if ($existingClass !== NULL) {
			try {
				$repository = $this->entityManager->getRepository($existingClass);
			}
			catch (MissingClassException $e) {}
		}

		// if not found repository
		if ($repository === NULL) {
			throw new MapperException(sprintf("Please, specify better targetEntity reference in ORM annotation, can't find class %s", $className));
		}

		return $repository;
	}

	/**
	 * Try to find entity class in possible NS
	 *
	 * @param string $className
	 * @param object $entity
	 * @return null|string
	 */
	private function findEntityWholeName($className, $entity)
	{
		$existingClass = NULL;
		// try to locate class in this namespace
		if (!class_exists($className)) {
			// try to find in namespace of entity
			$reflection = new ClassType($entity);
			$existingClass = $reflection->getNamespaceName() . "\\" . $className;

			// try to locate in parents namespace (recursive)
			if (!class_exists($existingClass)) {
				$parentClass = $reflection->getParentClass();
				while ($parentClass !== NULL) {
					$existingClass = $reflection->getParentClass()->getNamespaceName() . "\\" . $className;
					// not found try to find in parent namespace
					if (!class_exists($existingClass)) {
						$rc = new ClassType($parentClass);
						$parentClass = $rc->getParentClass();
						$existingClass = NULL;
					} else {
						$parentClass = NULL;
					}
				}
			}
		}
		else {
			$existingClass = $className;
		}

		return $existingClass;
	}

	/**
	 * Read property information
	 * Read relations and base DB types for convert values to correct format
	 *
	 * @param $baseEntity
	 * @param $name
	 * @return array|null
	 * @throws \Doctrine\ORM\Mapping\MappingException
	 */
	private function readPropertyDataType($baseEntity, $name)
	{
		$metaData = $this->entityManager->getClassMetadata(get_class($baseEntity));

		$type = NULL;
		$collection = FALSE;
		$relation = FALSE;

		if($metaData->hasField($name))
		{
			$type = $metaData->getTypeOfField($name);
			if (strrpos($type, 'array') !== FALSE) {
				$type = \DateTime::class;
			} else if ($type === 'dateinterval') {
				$type = \DateInterval::class;
			} else if (strrpos($type, 'array') !== FALSE) {
				$type = 'array';
				$collection = TRUE;
			}
		} else if($metaData->hasAssociation($name))
		{
			$association = $metaData->getAssociationMapping($name);

			switch($association['type'])
			{
				case ClassMetadata::TO_ONE:
					$type = $association['targetEntity'];
					$relation = TRUE;
				case ClassMetadata::TO_MANY:
					$relation = TRUE;
					$collection = TRUE;
					$type = $association['targetEntity'];
			}

			return [
				'type'          => $type,
				'collection'    => $collection,
				'relation'      => $relation
			];
		}

		return NULL;
	}
}