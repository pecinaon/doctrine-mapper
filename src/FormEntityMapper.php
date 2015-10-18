<?php
namespace DoctrineMapper;

use Nette\Utils\ArrayHash;
use DoctrineMapper\Exception\MapperException;

/**
 * Simple service to mapping ArrayHash values (Form result) to entity
 *
 * @deprecated - use ArrayHashEntityMapper
 * @author Pecina Ondřej <pecina.ondrej@gmail.com>
 */
class FormEntityMapper
{
	/**
	 * @var ArrayAccessEntityMapper
	 */
	private $arrayAccessEntityMapper;

	/**
	 * FormEntityMapper constructor.
	 * @param ArrayAccessEntityMapper $arrayAccessEntityMapper
	 */
	public function __construct(ArrayAccessEntityMapper $arrayAccessEntityMapper)
	{
		$this->arrayAccessEntityMapper = $arrayAccessEntityMapper;
	}

	/**
	 * Dynamically map values to entity
	 *
	 * @param ArrayHash $values
	 * @param object $entity
	 * @param array $columns
	 * @return Object $entity
	 *
	 *
	 * @deprecated use ArrayHashEntityMapper->setToEntity
	 *
	 * @throws MapperException
	 */
	public function setValuesToEntity(ArrayHash $values, $entity, array $columns = array())
	{
		return $this->arrayAccessEntityMapper->setToEntity($values, $entity, $columns);
	}
}