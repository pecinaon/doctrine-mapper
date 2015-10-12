<?php
namespace DoctrineMapper;

use Doctrine\Common\Collections\ArrayCollection;
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
					// Base PHP Objects
					if (!$propertyType['relation']) {
						if ($value !== NULL && $value !== '') {
							if ($propertyType['type'] === \DateTime::class) {
								// try with time
								$value = $this->dateParser->parseDateTime($value);
							} else if ($propertyType['type'] === 'int') {
								$value = (int) $value;
							} else if ($propertyType['type'] === 'bool') {
								$value = (bool) $value;
							}
						}
					}
					// Entities
					else {
						if (empty ($value)) {
							continue;
						}
						// try to find repository
						$repository = $this->findRepository($propertyType['type'], $entity);

						// maybe many to many - one to many
						if ($propertyType['collection']) {
							$value = new ArrayCollection($repository->findBy(array(
								$this->getEntityPrimaryKeyName($this->findEntityWholeName($propertyType['type'], $entity))    => $value
							)));
						}
						else {
							// many to one
							$value = $repository->find($value);
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
	 * @param object $baseEntity
	 * @param string $name
	 * @return array|NULL
	 */
	private function readPropertyDataType($baseEntity, $name)
	{
		// read property information
		$reflectionClass = new ClassType($baseEntity);
		$property = $reflectionClass->getProperty($name);

		// if property exists
		if ($property !== NULL) {
			// read annotations
			$annotations =  $property->getAnnotations();
			foreach ($annotations as $name => $val) {
				// if contains ORM annotations
				if ($name === 'ORM\ManyToOne'
					|| $name === 'ORM\ManyToMany'
					|| $name === 'ORM\OneToMany') {
					return  (isset($val[0]) ? [
						'type'          => $val[0]['targetEntity'],
						'relation'      => TRUE,
						'collection'    => ($name === '$name' || $name === 'ORM\ManyToMany')
					] : NULL);
				} else if ($name === 'ORM\Column') {
					// default type
					$type = 'string';
					$collection = FALSE;
					if (count($val) > 0) {
						if (isset ($val[0]['type'])) {
							$type = $val[0]['type'];
							if ($type === 'dateinterval') {
								$type = \DateInterval::class;
							} else if (strrpos($type, 'array') !== FALSE) {
								$type = 'array';
								$collection = TRUE;
							} else if (strrpos($type, 'date') !== FALSE || $type === 'time') {
								$type = \DateTime::class;
							} else if ($type === 'integer') {
								$type = 'int';
							} else if ($type === 'boolean') {
								$type = 'bool';
							}
						}
					}

					return [
						'type'          => $type,
						'collection'    => $collection,
						'relation'      => FALSE
					];
				}
			}
		}

		return NULL;
	}
}