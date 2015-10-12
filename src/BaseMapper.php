<?php
namespace DoctrineMapper;

use Doctrine\Common\Util\ClassUtils;
use Kdyby\Doctrine\EntityManager;
use DoctrineMapper\Parsers\Date\DateParser;
use Nette\Utils\Callback;

/**
 * Base entity mapper
 *
 * @author Pecina Ondřej <pecina.ondrej@gmail.com>
 * @package DoctrineMapper
 */
abstract class BaseMapper
{
	/**
	 * @var EntityManager
	 */
	protected $entityManager;

	/**
	 * @var DateParser
	 */
	protected $dateParser;

	/**
	 * BaseMapper constructor.
	 * @param EntityManager $entityManager
	 * @param DateParser $dateParser
	 */
	public function __construct(EntityManager $entityManager, DateParser $dateParser)
	{
		$this->entityManager = $entityManager;
		$this->dateParser = $dateParser;
	}

	/**
	 * Primary key name in entity
	 *
	 * @param object|string $entity
	 * @return string
	 * @throws \Doctrine\ORM\Mapping\MappingException
	 */
	protected function getEntityPrimaryKeyName($entity)
	{
		if(is_object($entity)) {
			$entity = ClassUtils::getClass($entity);
		}

		$meta = $this->entityManager->getClassMetadata($entity);
		return $meta->getSingleIdentifierFieldName();
	}

	/**
	 * Check object status is enity
	 *
	 * @param object|string $entity
	 * @return bool
	 */
	protected function isEntity($entity)
	{
		if(is_object($entity)) {
			$entity = ClassUtils::getClass($entity);
		}

		return ! $this->entityManager->getMetadataFactory()->isTransient($entity);
	}

	/**
	 * Get throw getter
	 *
	 * @param string $propertyName
	 * @param object $entity
	 * @return null
	 */
	protected function invokeGetter($propertyName, $entity)
	{
		$getterName = ['get' . ucfirst($propertyName), 'is' . ucfirst($propertyName)];

		$value = NULL;
		foreach ($getterName as $getter) {
			if(method_exists($entity, $getter) && $value === NULL) {
				$value = Callback::invoke([$entity, $getter]);
			}
		}

		return $value;
	}
}