<?php
namespace DoctrineMapper;

use ArrayAccess;
use Doctrine\Common\Util\ClassUtils;
use Kdyby\Doctrine\EntityManager;
use DoctrineMapper\Parsers\Date\DateParser;
use Nette\Utils\Callback;
use Traversable;

/**
 * Base entity mapper
 *
 * @author Pecina Ondřej <pecina.ondrej@gmail.com>
 * @package DoctrineMapper
 */
abstract class BaseMapper
{
	/** @var EntityManager */
	protected $entityManager;

	/** @var DateParser */
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
	 *
	 * @throws \Doctrine\ORM\Mapping\MappingException
	 */
	protected function getEntityPrimaryKeyName($entity) : ?string
	{
		if(is_object($entity)) {
			$entity = ClassUtils::getClass($entity);
		}

		$meta = $this->entityManager->getClassMetadata($entity);
		return $meta->getSingleIdentifierFieldName();
	}

	/**
	 * Check object status is entity
	 *
	 * @param object|string $entity
	 * @return bool
	 */
	protected function isEntity($entity) : bool
	{
		if(is_object($entity)) {
			$entity = ClassUtils::getClass($entity);
		}

		return ! $this->entityManager->getMetadataFactory()->isTransient($entity);
	}

	/**
	 * Check is value iterable
	 *
	 * @param $values
	 * @return bool
	 */
	protected function isIterable($values) : bool
	{
		return ($values instanceof ArrayAccess || $values instanceof Traversable || is_array($values));
	}

	/**
	 * Get throw getter
	 *
	 * @param string $propertyName
	 * @param object $entity
	 * @return NULL|mixed
	 */
	protected function invokeGetter(string $propertyName, $entity) : ?mixed
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