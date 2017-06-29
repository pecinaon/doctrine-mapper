<?php
namespace DoctrineMapper;

use Doctrine\ORM\PersistentCollection;
use DoctrineMapper\Exception\InvalidStateException;
use Nette\ComponentModel\Component;
use Nette\Forms\Container;
use Nette\Forms\Controls\BaseControl;

/**
 * Mapper entity to Nette form
 *
 * @author Pecina Ondřej <pecina.ondrej@gmail.com>
 * @package DoctrineMapper
 */
class EntityFormMapper extends BaseMapper
{
	/**
	 * Map Entity to form
	 *
	 * @param object $entity
	 * @param Container $container
	 */
	public function setEntityToContainer($entity, Container $container)
	{
		// go throw all components and try find values for it
		/** @var BaseControl $component */
		foreach($container->getComponents() as $component) {
			$this->mapValueToComponent($entity, $component);
		}
	}

	/**
	 * Set value to form
	 *
	 * @param object $entity
	 * @param Component $component
	 *
	 * @throws InvalidStateException
	 */
	public function mapValueToComponent($entity, Component $component) : void
	{
		/** @noinspection PhpParamsInspection */
		$value = $this->getEntityValue($entity, $component);

		if($value === NULL) {
			return;
		}

		$defaultValue = $value;

		if(is_object($value)) {
			// try is value is entity
			if($this->isEntity($value)) {
				if ($component instanceof Container) {
					$this->setEntityToContainer($value, $component);
				}
				else if ($component instanceof BaseControl) {
					// try to find PK
					$pkName = $this->getEntityPrimaryKeyName($value);
					// invoke method and set value
					$component->setDefaultValue($this->invokeGetter($pkName, $value));
				} else {
					throw new InvalidStateException(sprintf('Invalid type for map values!', get_class($component)));
				}

				// no default function scope
				return;
			}

			// if is DateTime object
			else if(get_class($value) === 'DateTime')
			{
				$defaultValue = $this->dateParser->parseString($value);
			}
			// is doctrine collection
			else if(get_class($value) === 'Doctrine\ORM\PersistentCollection')
			{
				$defaultValue = array();
				// list entities from collection
				/** @var PersistentCollection $value */
				foreach($value->getValues() as $entity) {
					// try to find Primary key
					$pkName = $this->getEntityPrimaryKeyName($entity);
					// invoke getter name and set value
					$defaultValue[] = $this->invokeGetter($pkName, $entity);
				}
			}
		}

		// if default value is not NULL set default value
		if($defaultValue !== NULL) {
			// this hack if cause fu Nette when setting default value false - convert it to ''
			if ($defaultValue === FALSE) {
				$defaultValue = 0;
			}

			/** @noinspection PhpUndefinedMethodInspection */
			$component->setDefaultValue($defaultValue);
		}
	}

	/**
	 * Get value for control
	 *
	 * @param object $entity
	 * @param Component $control
	 * @return NULL|mixed
	 */
	private function getEntityValue($entity, Component $control) : ?mixed
	{
		return $this->invokeGetter($control->getName(), $entity);
	}
}