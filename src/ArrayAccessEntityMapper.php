<?php
namespace DoctrineMapper;

use ArrayAccess;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use DoctrineMapper\Exception\MethodNotExistsException;
use Kdyby\Doctrine\MissingClassException;
use Nette\Reflection\ClassType;
use Nette\Utils\Callback;
use DoctrineMapper\Exception\MapperException;
use Traversable;

/**
 * Simple service to mapping ArrayHash values (Form result) to entity
 *
 * @author Pecina Ondřej <pecina.ondrej@gmail.com>
 */
class ArrayAccessEntityMapper extends BaseMapper
{
	/**
	 * Dynamically map values to entity
	 * Map all values
	 *
	 * @param array|ArrayAccess $values
	 * @param object $entity
	 * @param array $columns
	 * @param array $columnsMapping
	 *
	 * @return object entity
	 *
	 * @throws Exception\CantParseException
	 * @throws MapperException
	 * @throws \Doctrine\ORM\Mapping\MappingException
	 */
	public function setToEntity($values, $entity, array $columns = array(), array $columnsMapping = array())
	{
		if (!is_object($entity)) {
			throw new MapperException(sprintf("Entity have to be object, %s given", gettype($entity)));
		}

		if ($values instanceof ArrayAccess || $values instanceof Traversable || is_array($values)) {
			$values = $this->convertToArray($values);
		}

		if(!is_array($values)) {
			throw new MapperException(sprintf("Values can not be mapped expected format is array|ArrayAccess, %s given", gettype($values)));
		}

		if (empty ($columns)) {
			$columns = array_keys($values);
		}

		/** @var ClassMetadata $metaData */
		$metaData = $this->entityManager->getClassMetadata(get_class($entity));

		foreach($columns as $column) {
			$targetProperty = $column;
			if (isset ($columnsMapping[$column])) {
				$targetProperty = $columnsMapping[$column];
			}

			$setterName = 'set' . ucfirst($targetProperty);
			if(method_exists($entity, $setterName) && isset($values[$column])) {
				// load value
				$value = $values[$column];

				// Base PHP types
				if ($metaData->hasField($targetProperty)) {
					$type = $metaData->getTypeOfField($targetProperty);
					if ($value !== NULL && $value !== '') {
						if (strrpos($type, 'date') !== FALSE) {
							$value = $this->dateParser->parseDateTime($value);
						} else if ($type === 'integer') {
							$value = (int) $value;
						} else if ($type === 'boolean') {
							$value = (bool) $value;
						} else if (strrpos($type, 'array') !== FALSE) {
							$value = (array) $value;
						}
					}
				}
				// Entities
				else if (!empty ($value)) {
					$association = $metaData->getAssociationMapping($targetProperty);

					// not reference
					if ($association === NULL) {
						continue;
					}
					// try to find repository
					$targetEntity = $association['targetEntity'];
					// find repository for entity
					$repository = $this->findRepository($targetEntity, $entity);

					// get primary key for entity
					$pk = $this->getEntityPrimaryKeyName($this->findEntityWholeName($targetEntity, $entity));
					// maybe many to many - one to many - collection association
					if ($metaData->isCollectionValuedAssociation($targetProperty)) {
						if (is_array($value)) {
							$newValues = new ArrayCollection();
							/** @var ArrayCollection $oldValues */
							$oldValues = $this->invokeGetter($targetProperty, $entity);
							foreach ($value as $item) {
								if (is_array($item)) {
									$newValues->add($this->getMappedEntity($item, $targetEntity, $pk, $repository));
								}
								else {
									$newValues->add($repository->find($item));
								}
							}
							if ($oldValues !== NULL) {
								foreach ($oldValues as $val) {
									if ($newValues->contains($val)) {
										// remove relation
										$oldValues->removeElement($val);
									}
								}
							}

							$value = $newValues;
						} else {
							throw new MapperException(sprintf("Values for property %s expected array or array of array , %s given", $targetProperty, gettype($value)));
						}
					}
					else if ($metaData->isSingleValuedAssociation($targetProperty)) {
						// many to one
						$keyValue = $value;
						// create or update entity
						if (is_array($value)) {
							$value = $this->getMappedEntity($value, $targetEntity, $pk, $repository);
						}
						// only key - try to find during repository
						else {
							$value = $repository->find($value);
						}

						// NULL returned - bad situation
						if ($value === NULL) {
							throw new MapperException(sprintf("Can not find or create Entity (%s) for column with primary key %s.", $targetEntity, $targetProperty, $keyValue));
						}
					}
				}

				// if empty ? set NULL
				if (empty ($value) && $value !== FALSE && !is_numeric($value)) {
					$value = NULL;
				}

				// set value
				if (!method_exists($entity, $setterName)) {
					throw new MethodNotExistsException(sprintf('Method %s not found in object %s', $setterName, get_class($entity)));
				}
				
				Callback::invokeArgs(array($entity, $setterName), [$value]);
			}
		}

		return $entity;
	}

	/**
	 * Convert to array recursive
	 *
	 * @param ArrayAccess $arrayAccess
	 * @return array
	 */
	private function convertToArray($arrayAccess)
	{
		$values = (array) $arrayAccess;

		foreach ($values as $key => $value) {
			if ($value instanceof ArrayAccess || $value instanceof Traversable || is_array($value)) {
				$values[$key] = $this->convertToArray($value);
			}
		}

		return $values;
	}

	/**
	 * Try to find repository for Doctrine relation
	 *
	 * @param string $className
	 * @param object $entity
	 * @return EntityRepository|NULL
	 * @throws MapperException
	 */
	private function findRepository($className, $entity)
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
	 * @param class $className
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
	 * @param mixed $value
	 * @param class $targetEntity
	 * @param string $pk
	 * @param EntityRepository $repository
	 * @return Object
	 *
	 * @throws MapperException
	 */
	private function getMappedEntity($value, $targetEntity, $pk, EntityRepository $repository) {
		$entity = new $targetEntity;

		// exists key load
		if (isset($value[$pk]) && !empty($value[$pk])) {
			$pkValue = $value[$pk];
			$entity = $repository->find($pkValue);
		}

		return $this->setToEntity($value, $entity);
	}
}
