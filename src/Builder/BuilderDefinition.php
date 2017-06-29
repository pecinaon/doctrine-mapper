<?php
namespace DoctrineMapper\Builder;
use DoctrineMapper\Exception\InvalidStateException;


/**
 * Builder Definition
 *
 * Ondřej Pecina <pecina.ondrej@gmail.com>
 * @package DoctrineMapper\Builder
 */
class BuilderDefinition
{
	const COMPONENT_TYPE_HIDDEN = 'hidden';
	const COMPONENT_TYPE_CONTAINER = 'container';
	const COMPONENT_TYPE_TEXT = 'text';
	const COMPONENT_TYPE_SELECT = 'select';
	const COMPONENT_TYPE_MULTI_SELECT = 'multiSelect';
	const COMPONENT_TYPE_TEXT_AREA = 'textArea';
	const COMPONENT_TYPE_CHECKBOX = 'checkbox';
	const COMPONENT_TYPE_CHECKBOX_LIST = 'checkboxList';
	const COMPONENT_TYPE_RADIO_LIST = 'radioList';

	/** @var string */
	private $name;

	/** @var bool */
	private $required;

	/** @var string */
	private $componentType;

	/** @var array */
	private $validationRules = array();

	/** @var string */
	private $label;

	/** @var string */
	private $placeholder;

	/** @var array */
	private $values = array();

	/** @var class */
	private $targetEntity;

	/** @var mixed */
	private $value;

	/**
	 * BuilderDefinition constructor.
	 * @param string $name
	 */
	public function __construct($name)
	{
		$this->name = $name;
	}

	/**
	 * @return boolean
	 */
	public function isRequired() : bool
	{
		return $this->required;
	}

	/**
	 * @param boolean $required
	 * @return $this
	 */
	public function setRequired(bool $required)
	{
		$this->required = $required;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getValidationRules() : array
	{
		return $this->validationRules;
	}

	/**
	 * @param array $validationRules
	 * @return BuilderDefinition
	 */
	public function setValidationRules(array $validationRules) : BuilderDefinition
	{
		foreach ($validationRules as $row) {
			$this->validationRules[] = $this->processValidationRulesRow($row);
		}

		return $this;
	}

	/**
	 * Add validation rule
	 *
	 * @param Form::RULE $validator
	 * @param string $text
	 * @param boolean $conditional
	 * @param array $arg
	 * @return $this
	 */
	public function addValidationRule(string $validator, string $text = NULL, bool $conditional = FALSE, array $arg = [])
	{
		$this->validationRules[] = [
			'validator'     => $validator,
			'text'          => $text,
			'conditional'   => (bool) $conditional,
			'arg'           => $arg
		];

		return $this;
	}

	public function addValidationRuleRow(array $row)
	{
		$this->validationRules[] = $this->processValidationRulesRow($row);
	}

	/**
	 * @return string
	 */
	public function getName() : string
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getComponentType() : string
	{
		return $this->componentType;
	}

	/**
	 * @param string $componentType
	 * @return BuilderDefinition
	 * @throws InvalidStateException
	 */
	public function setComponentType(string $componentType) : BuilderDefinition
	{
		if (!in_array($componentType, [
			self::COMPONENT_TYPE_CONTAINER,
			self::COMPONENT_TYPE_HIDDEN,
			self::COMPONENT_TYPE_CHECKBOX,
			self::COMPONENT_TYPE_CHECKBOX_LIST,
			self::COMPONENT_TYPE_MULTI_SELECT,
			self::COMPONENT_TYPE_RADIO_LIST,
			self::COMPONENT_TYPE_SELECT,
			self::COMPONENT_TYPE_TEXT,
			self::COMPONENT_TYPE_TEXT_AREA
		])) {
			throw new InvalidStateException(sprintf("Component type %s not exist.", $componentType));
		}

		$this->componentType = $componentType;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getPlaceholder() : string
	{
		return $this->placeholder;
	}

	/**
	 * @param string $placeholder
	 * @return BuilderDefinition
	 */
	public function setPlaceholder(string $placeholder) : BuilderDefinition
	{
		$this->placeholder = $placeholder;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getLabel() : string
	{
		return $this->label;
	}

	/**
	 * @param string $label
	 * @return BuilderDefinition
	 */
	public function setLabel(string $label) : BuilderDefinition
	{
		$this->label = $label;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getValues() : array
	{
		return $this->values;
	}

	/**
	 * @param array $values
	 * @return BuilderDefinition
	 */
	public function setValues(array $values) : BuilderDefinition
	{
		$this->values = $values;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTargetEntity() : string
	{
		return $this->targetEntity;
	}

	/**
	 * @param string $targetEntity
	 * @return BuilderDefinition
	 */
	public function setTargetEntity(string $targetEntity) : BuilderDefinition
	{
		$this->targetEntity = $targetEntity;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getValue() : ?mixed
	{
		return $this->value;
	}

	/**
	 * @param mixed $value
	 * @return BuilderDefinition
	 */
	public function setValue($value) : BuilderDefinition
	{
		$this->value = $value;
		return $this;
	}

	/**
	 * Process rule row
	 *
	 * @param array $row
	 * @return array
	 */
	private function processValidationRulesRow(array $row) : array
	{
		return  [
			'validator'     => $row['validator'],
			'text'          => (isset ($row['text']) ? $row['text'] : NULL),
			'conditional'   => (bool) (isset ($row['conditional']) ? $row['conditional'] : NULL),
			'arg'           => (isset ($row['arg']) ? $row['arg'] : NULL),
		];
	}
}