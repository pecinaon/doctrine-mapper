<?php
namespace DoctrineMapper;

use DoctrineMapper\Builder\FormBuilder;
use DoctrineMapper\Exception\InvalidStateException;
use DoctrineMapper\Parsers\Date\DateParser;
use Kdyby\Doctrine\EntityManager;
use Nette\Application\UI\Form;


/**
 * Build form from entity
 *
 * Ondřej Pecina <pecina.ondrej@gmail.com>
 * @package DoctrineMapper
 */
class FormEntityBuilder
{

	/**
	 * @var EntityFormMapper
	 */
	private $entityFormMapper;

	/**
	 * @var DateParser
	 */
	private $dateParser;

	/**
	 * @var EntityManager
	 */
	private $entityManager;

	/**
	 * FormEntityBuilder constructor.
	 * @param EntityFormMapper $entityFormMapper
	 * @param DateParser $dateParser
	 * @param EntityManager $entityManager
	 */
	public function __construct(EntityFormMapper $entityFormMapper, DateParser $dateParser, EntityManager $entityManager)
	{
		$this->entityFormMapper = $entityFormMapper;
		$this->dateParser = $dateParser;
		$this->entityManager = $entityManager;
	}

	/**
	 * Create form builder
	 *
	 * @param object $entity
	 * @param bool|FALSE $autoBuild
	 * @param string $formClass
	 * @return FormBuilder
	 * @throws InvalidStateException
	 */
	public function getBuilder($entity, $autoBuild = FALSE, $formClass = Form::class)
	{
		if (!is_object($entity)) {
			throw new InvalidStateException(sprintf("Required doctrine entity, %s given", gettype($entity)));
		}

		return new FormBuilder($this->entityFormMapper, $entity,  $this->entityManager,  $this->dateParser, $autoBuild, new $formClass);
	}
}