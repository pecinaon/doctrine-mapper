<?php
namespace DoctrineMapper\Builder;

use Doctrine\ORM\Mapping\Column;
use DoctrineMapper\BaseMapper;
use DoctrineMapper\EntityFormMapper;
use DoctrineMapper\Exception\InvalidStateException;
use DoctrineMapper\Parsers\Date\DateParser;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\Mapping\ClassMetadata;
use Nette\Application\UI\Form;
use Nette\ComponentModel\IComponent;
use Nette\Forms\Container;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\ChoiceControl;
use Nette\Reflection\ClassType;
use Nette\Reflection\Property;
use Nette\Utils\Callback;


/**
 * Form builder
 *
 * Ondřej Pecina <pecina.ondrej@gmail.com>
 * @package DoctrineMapper
 */
class FormBuilder extends BaseMapper
{
	/** @var EntityFormMapper */
	private $entityFormMapper;

	/** @var object */
	private $entity;

	/** @var ClassType */
	private $entityReflection;

	/** @var \Doctrine\ORM\Mapping\ClassMetadata */
	private $entityMetadata;

	/** @var array  */
	private $hidden = [];

	/** @var Container */
	private $form;


	/**
	 * @param EntityFormMapper $entityFormMapper
	 * @param object $entity
	 * @param EntityManager $entityManager
	 * @param DateParser $dateParser
	 * @param bool|FALSE $autoBuild
	 * @param Container $container
	 * @throws InvalidStateException
	 */
	public function __construct(EntityFormMapper $entityFormMapper, $entity, EntityManager $entityManager, DateParser $dateParser, $autoBuild = FALSE, Container $container)
	{
		parent::__construct($entityManager, $dateParser);

		$this->entityFormMapper = $entityFormMapper;
		$this->entity = $entity;
		$this->form = $container;

		$this->entityReflection = new ClassType($entity);
		$this->entityMetadata = $this->entityManager->getClassMetadata(get_class($entity));

		if (!($this->form instanceof Container)) {
			throw new InvalidStateException(sprintf('Form class excepted the class witch is instance of Nette\Forms\Container %s given', gettype($this->form)));
		}

		// generate form
		if ($autoBuild) {
			$this->createForm();
		}
	}

	/**
	 * Add definitions for builder
	 *
	 * @param array $definitions
	 * @return FormBuilder
	 *
	 * @throws InvalidStateException
	 */
	public function addAll(array $definitions) : FormBuilder
	{
		foreach ($definitions as $definition) {
			$this->add($definition);
		}

		return $this;
	}

	/**
	 * Add definition for builder
	 *
	 * @param array $definition
	 * @return FormBuilder
	 * @throws InvalidStateException
	 */
	public function add(array $definition = []) : FormBuilder
	{
		if (!isset ($definition['propertyName'])) {
			throw new InvalidStateException("Missing property name!");
		}
		$propertyName = $definition['propertyName'];

		// load default rules
		$builderDefinition = $this->getPropertyRule($propertyName, !(isset ($definition['componentType']) && $definition['componentType'] === BuilderDefinition::COMPONENT_TYPE_CONTAINER));

		if (isset ($definition['componentType'])) {
			if ($definition['componentType'] === BuilderDefinition::COMPONENT_TYPE_CONTAINER) {
				$container = $this->form->addContainer($propertyName);
				$targetEntity = $this->invokeGetter($propertyName, $this->entity);

				if ($targetEntity === NULL) {
					$className = $builderDefinition->getTargetEntity();
					$targetEntity = new $className();
				}
				$isSpecified = isset ($definition['settings']) && is_array($definition['settings']) && is_array($definition['settings'][0]);
				/** @var FormBuilder $builder */
				$builder = new self($this->entityFormMapper, $targetEntity, $this->entityManager, $this->dateParser, !$isSpecified, $container);

				if ($isSpecified) {
					$builder->addAll($definition['settings']);
				}

				// try to map value
				$this->entityFormMapper->mapValueToComponent($targetEntity, $container);
				// end scope
				return $this;
			}
			else {
				$builderDefinition->setComponentType($definition['componentType']);
			}
		}

		if (isset($definition['settings'])) {
			$settings = $definition['settings'];

			if (isset ($settings['required'])) {
				$builderDefinition->setRequired($settings['required']);
			}

			if (isset ($settings['placeholder'])) {
				$builderDefinition->setPlaceholder($settings['placeholder']);
			}

			if (isset ($settings['validationRules'])) {
				$builderDefinition->setValidationRules($settings['validationRules']);
			}

			if (isset ($settings['label'])) {
				$builderDefinition->setLabel($settings['label']);
			}

			if (isset ($settings['values']) && is_array($settings['values'])) {
				$builderDefinition->setValues($settings['values']);
			}

			if (isset ($settings['value'])) {
				$builderDefinition->setValue($settings['value']);
			}

			if (isset ($settings['appendValidationRules']) && is_array($settings['appendValidationRules'])) {
				foreach ($settings['appendValidationRules'] as $row) {
					$builderDefinition->addValidationRuleRow($row);
				}
			}
		}


		// replace old
		$this->replaceFormControl($builderDefinition);

		return $this;
	}

	/**
	 * Remove Component from form
	 *
	 * @param string $propertyName
	 * @return FormBuilder
	 */
	public function hide($propertyName) : FormBuilder
	{
		$this->hidden[$propertyName] = TRUE;
		return $this;
	}

	/**
	 * Hide all Components
	 *
	 * @return FormBuilder
	 */
	public function hideAll() : FormBuilder
	{
		$properties = $this->entityReflection->getProperties();

		/** @var Property $property */
		foreach ($properties as $property) {
			$this->hide($property->getName());
		}

		return $this;
	}


	/**
	 * Get generated form
	 * Form is filled you can erase it
	 *
	 * @param bool $erase
	 *
	 * @return Container
	 */
	public function getForm($erase = FALSE) : Container
	{
		foreach ($this->hidden as $name => $hidden) {
			if (isset ($this->form[$name])) {
				unset($this->form[$name]);
			}
		}
		$form = $this->form;

		if ($erase) {
			$form->setDefaults([], TRUE);
		}

		return $form;
	}

	/**
	 * Get component from form
	 *
	 * @param string $propertyName
	 * @return \Nette\ComponentModel\IComponent
	 *
	 * @throws InvalidStateException
	 */
	public function getFormComponent($propertyName) : IComponent
	{
		if (!isset ($this->form[$propertyName])) {
			throw new InvalidStateException(sprintf("Component with name %s does not exists.", $propertyName));
		}

		return $this->form[$propertyName];
	}

	/**
	 * Generate form from entity
	 *
	 * @return FormBuilder
	 */
	private function createForm() : FormBuilder
	{
		$properties = $this->entityReflection->getProperties();

		/** @var Property $property */
		foreach ($properties as $property) {
			$rule = $this->getPropertyRule($property->getName());
			if ($rule !== NULL) {
				$this->replaceFormControl($rule);
			}
		}

		return $this;
	}

	/**
	 * Replace form control with new rules
	 *
	 * @param BuilderDefinition $definition
	 */
	private function replaceFormControl(BuilderDefinition $definition)
	{
		$form = $this->form;

		if (isset ($form[$definition->getName()])) {
			unset($form[$definition->getName()]);
		}

		$methodName = 'add' . ucfirst($definition->getComponentType());
		/** @var BaseControl $control */
		$control = Callback::invokeArgs(array(
			$form, $methodName
		), [$definition->getName(), $definition->getLabel()]);


		// is required?
		if ($definition->isRequired()) {
			$control->addRule(Form::FILLED, sprintf('%s is required!', ($definition->getLabel() === NULL ? $definition->getName() : $definition->getLabel())));
		}

		// rules conditions - validation rules
		foreach ($definition->getValidationRules() as $validationRule) {
			if ($validationRule['conditional']) {
				$control->addCondition(Form::FILLED)
					->addRule($validationRule['validator'], $validationRule['text'], $validationRule['arg']);
			}
			else {
				$control->addRule($validationRule['validator'], $validationRule['text'], $validationRule['arg']);
			}
		}

		// placeholder
		if ($definition->getPlaceholder() !== NULL) {
			$control->setAttribute('placeholder', $definition->getPlaceholder());
		}

		if (in_array($definition->getComponentType(), [BuilderDefinition::COMPONENT_TYPE_CHECKBOX_LIST, BuilderDefinition::COMPONENT_TYPE_SELECT, BuilderDefinition::COMPONENT_TYPE_RADIO_LIST, BuilderDefinition::COMPONENT_TYPE_MULTI_SELECT])) {
			/** @var ChoiceControl $control */
			if (count($definition->getValues()) > 0) {
				$control->setItems($definition->getValues());
			}
		}

		// set value - or load it from entity
		if ($definition->getValue() !== NULL) {
			$control->setValue($definition->getValue());
		} else {
			$this->entityFormMapper->mapValueToComponent($this->entity, $control);
		}
	}


	/**
	 * @param string $propertyName
	 * @param bool|true $fillValues
	 *
	 * @return BuilderDefinition|NULL
	 *
	 * @throws InvalidStateException
	 * @throws \Doctrine\ORM\Mapping\MappingException
	 */
	private function getPropertyRule(string $propertyName, bool $fillValues = TRUE) : ?BuilderDefinition
	{
		$rule = new BuilderDefinition($propertyName);
		if($this->entityMetadata->hasField($propertyName))
		{
			// Column
			if($this->getEntityPrimaryKeyName($this->entity) === $propertyName)
			{
				$rule->setComponentType(BuilderDefinition::COMPONENT_TYPE_HIDDEN);
				$rule->setRequired(FALSE);
			} else
			{
				$fieldMapping = $this->entityMetadata->getFieldMapping($propertyName);

				$type = BuilderDefinition::COMPONENT_TYPE_TEXT;
				$rule->setRequired(!$fieldMapping['nullable']);

				/** @var Column $annotation */
				if ($fieldMapping['type'] === 'boolean') {
					$type = BuilderDefinition::COMPONENT_TYPE_CHECKBOX;
				}

				// is numeric?
				if ($fieldMapping['type'] === 'integer'
					|| $fieldMapping['type'] === 'float'
					|| $fieldMapping['type'] === 'bigint'
					|| $fieldMapping['type'] === 'decimal'
					|| $fieldMapping['type'] === 'smallint'
				) {
					$rule->addValidationRule(Form::NUMERIC, 'This is required in numeric format', TRUE);
				}

				$rule->setComponentType($type);
			}
		} else if($this->entityMetadata->hasAssociation($propertyName))
		{
			$associationMapping = $this->entityMetadata->getAssociationMapping($propertyName);
			switch($associationMapping['type'])
			{
				case ClassMetadata::MANY_TO_ONE:
					$rule->setComponentType(BuilderDefinition::COMPONENT_TYPE_SELECT);
					if ($fillValues) {
						$rule->setValues($this->getPossibleValues($associationMapping['targetEntity']));
					}
					$rule->setTargetEntity($associationMapping['targetEntity']);
					$rule->setRequired(TRUE);
					break;
				case ClassMetadata::MANY_TO_MANY:
					$rule->setComponentType(BuilderDefinition::COMPONENT_TYPE_MULTI_SELECT);
					if ($fillValues) {
						$rule->setValues($this->getPossibleValues($associationMapping['targetEntity']));
					}
					$rule->setRequired(TRUE);
					$rule->setTargetEntity($associationMapping['targetEntity']);
					break;
			}
		}

		return ($rule->getComponentType() === NULL ? NULL : $rule);
	}

	/**
	 * Generate values
	 *
	 * @param $entity
	 * @return array
	 *
	 * @throws InvalidStateException
	 */
	private function getPossibleValues($entity) : array
	{
		if (!class_exists($entity)) {
			throw new InvalidStateException(sprintf('Object %s is not accessible please use better specification in targetEntity annotation!', $entity));
		}

		$repository = $this->entityManager->getRepository($entity);
		$entities = $repository->findAll();
		$pkName = $this->getEntityPrimaryKeyName($entity);
		$values = array();

		foreach ($entities as $oneEntity) {
			$pk = Callback::invoke(array(
				$oneEntity, 'get' . ucfirst($pkName)
			));

			if (!method_exists($oneEntity, '__toString')) {
				throw new InvalidStateException(sprintf('Please specify __toString function for entity %s', get_class($oneEntity)));
			}

			$values[$pk] = (string) $oneEntity;
		}

		return $values;
	}
}