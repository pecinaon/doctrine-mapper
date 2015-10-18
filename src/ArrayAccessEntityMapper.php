<?php
	namespace DoctrineMapper;

	use ArrayAccess;
	use Doctrine\Common\Collections\ArrayCollection;
	use Doctrine\ORM\Mapping\ClassMetadata;
	use Kdyby\Doctrine\MissingClassException;
	use Nette\Reflection\ClassType;
	use Nette\Utils\Callback;
	use DoctrineMapper\Exception\MapperException;

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
		 * @param ArrayAccess $values
		 * @param object $entity
		 * @param array $columns
		 * @return Object $entity
		 * @throws MapperException
		 */
		public function setToEntity(ArrayAccess $values, $entity, array $columns = array())
		{
			if (!is_object($entity)) {
				throw new MapperException(sprintf("Entity have to be object, %s given", gettype($entity)));
			}

			if (empty ($columns)) {
				$columns = array_keys((array) $values);
			}

			/** @var ClassMetadata $metaData */
			$metaData = $this->entityManager->getClassMetadata(get_class($entity));

			foreach($columns as $column) {
				$setterName = 'set' . ucfirst($column);
				if(method_exists($entity, $setterName) && isset($values->$column)) {
					// load value
					$value = $values->offsetGet($column);

					// Base PHP types
					if ($metaData->hasField($column)) {
						$type = $metaData->getTypeOfField($column);
						if ($value !== NULL && $value !== '') {
							if (strrpos($type, 'date') !== FALSE) {
								$value = $this->dateParser->parseDateTime($value);
							} else if ($type === 'integer') {
								$value = (int) $value;
							} else if ($type === 'boolean') {
								$value = (bool) $value;
							} else if (strrpos($type, 'date') !== FALSE) {
								$value = (array) $value;
							}
						}
					}
					// Entities
					else if (!empty ($value)) {
						$association = $metaData->getAssociationMapping($column);

						// not reference
						if ($association === NULL) {
							continue;
						}
						// try to find repository
						$targetEntity = $association['targetEntity'];
						$repository = $this->findRepository($targetEntity, $entity);
						$pk = $this->getEntityPrimaryKeyName($this->findEntityWholeName($targetEntity, $entity));
						// maybe many to many - one to many - collection association
						if ($metaData->isCollectionValuedAssociation($column)) {
							if ($value instanceof ArrayAccess && isset($value[0]) && $value[0] instanceof ArrayAccess) {
								$newValues = new ArrayCollection();
								foreach ($value as $val) {
									$entity = new $targetEntity;

									// exists key load
									if (isset($val->$pk) && !empty($val->$pk)) {
										$pkValue = $val->$pk;
										$entity = $repository->find($pkValue);
									}

									$entity = $this->setToEntity($val, $entity);
									$newValues->add($entity);
								}
								/** @var ArrayCollection $oldValues */
								$oldValues = $this->invokeGetter($column, $entity);

								// remove old relations
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
								$value = new ArrayCollection($repository->findBy(array(
									$pk => (array) $value
								)));
							}
						}
						else if ($metaData->isSingleValuedAssociation($column)) {
							// many to one
							if ($value instanceof ArrayAccess) {
								$entity = new $targetEntity;

								// exists key = load
								if (isset($value->$pk) && !empty($value->$pk)) {
									$pkValue = $value->$pk;
									$entity = $repository->find($pkValue);
								}

								$value = $this->setToEntity($value, $entity);
							}
							else {
								$value = $repository->find($value);
							}
						}
					}

					// if empty ? set NULL
					if (empty ($value) && $value !== FALSE) {
						$value = NULL;
					}

					// set value
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
	}