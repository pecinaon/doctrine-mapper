<?php
namespace DoctrineMapper\DI;

use DoctrineMapper\Exception\UnexpectedValueException;
use Kdyby;
use Nette;
use Nette\PhpGenerator as Code;
use Nette\DI\Config;

/**
 * Extension for mapping entities to Nette Form and Nette Form to Doctrine Entity
 *
 * Ondřej Pecina <pecina.ondrej@gmail.com>
 * @package SmartValidator\DI
 */
class DoctrineMapperExtension  extends Nette\DI\CompilerExtension
{
	public $defaults = array(
		'dateFormat'    => 'DoctrineMapper\Parsers\Date\SimpleDateFormat'
	);

	public function loadConfiguration()
	{
		$config = $this->validateConfig($this->defaults);
		$builder = $this->getContainerBuilder();

		if (!class_exists($config['dateFormat'])) {
			throw new UnexpectedValueException(sprintf('Class %s not exists.', $config['dateFormat']));
		}

		$builder->addDefinition($this->prefix('dateFormat'))
			->setClass($config['dateFormat']);

		$builder->addDefinition($this->prefix('dateParser'))
			->setClass('DoctrineMapper\Parsers\Date\DateParser');

		$builder->addDefinition($this->prefix('entityFormMapper'))
			->setClass('DoctrineMapper\EntityFormMapper');

		$builder->addDefinition($this->prefix('arrayAccessEntityMapper'))
			->setClass('DoctrineMapper\ArrayAccessEntityMapper');

		$builder->addDefinition($this->prefix('formEntityBuilder'))
			->setClass('DoctrineMapper\FormEntityBuilder');

		$builder->addDefinition($this->prefix('formEntityMapper'))
			->setClass('DoctrineMapper\FormEntityMapper');

	}
}