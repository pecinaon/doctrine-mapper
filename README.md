# Doctrine Mapper
======


Requirements
------------

DoctrineMapper requires PHP 5.5 or higher.

- [Nette Framework](https://github.com/nette/nette)
- [Kdyby Doctrine](https://github.com/Kdyby/Doctrine)


Installation
------------

The best way to install pecinaon/doctrine-mapper is using  [Composer](http://getcomposer.org/):

```sh
$ composer require pecinaon/doctrine-mapper
```

Configuration
---------------------

This extension creates new configuration section `doctrineMapper`, the available configuration looks

```yml
doctrineMapper:
	dateFormat: App\ExampleDateFormat
```
The `dateFormat` which parse DateTime from your format, you can change it. This class have to implements DoctrineMapper\Parsers\Date\IDateFormat.

```php
<?php
namespace App;

class ExampleDateFormat implements \DoctrineMapper\Parsers\Date\IDateFormat
{

	/**
	 * Return date and time format
	 *
	 * @return string
	 */
	public function getDateTimeFormat()
	{
		return "H:i:s";
	}

	/**
	 * Return date format
	 *
	 * @return string
	 */
	public function getDateFormat()
	{
		return "d/m.y";
	}
}
```


Map ArrayHash (Form result) to Entity
---------------------

```php
<?php
namespace App;

use DoctrineMapper\FormEntityMapper;
use Nette\Application\UI\Form;
use Doctrine\ORM\Mapping as ORM;
use Nette\Object;
use Nette\Utils\ArrayHash;

/**
 * Example Entity
 *
 * @ORM\Entity(repositoryClass="ExampleRepository")
 * @ORM\Table(name="tp_example", indexes={
 * })
 * @author Pecina Ondřej <pecina.ondrej@gmail.com>
 */
class ExampleEntity extends Object
{

	/**
	 * @ORM\Column(length=128)
	 * @var string
	 */
	protected $name;

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer", options={"unsigned"=true})
	 * @ORM\GeneratedValue
	 * @var int
	 */
	protected  $id;

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 * @return ExampleEntity
	 */
	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}
}

class ExampleFormMapper
{
	/** @var FormEntityMapper */
	private $formEntityMapper;

	/**
	 * ExampleFormMapper constructor.
	 * @param FormEntityMapper $formEntityMapper
	 */
	public function __construct(FormEntityMapper $formEntityMapper)
	{
		$this->formEntityMapper = $formEntityMapper;
	}

	public function getForm() {
		$form = new Form();
		$form->addText('name', 'Name')
			->setRequired();

		$form->addSubmit('Sub', 'Save');

		$form->onSuccess[] = function(Form $form) {
			$entity  = new ExampleEntity();

			// array with name is not reuired - if is not set, set all values with existing properties in entity
			$this->formEntityMapper->setValuesToEntity($form->getValues(), $entity, ['name']);
			
			// works same like line above
			$this->formEntityMapper->setValuesToEntity($form->getValues(), $entity);
			
			// you can map also in this way 
			$this->formEntityMapper->setValuesToEntity(ArrayHash::from([
				'name'  => 'Testing name'
			]), $entity);
			// do stuff with entity
		};

		return $form;
	}
}
```
The third argument in setValuesToEntity is optional, because when is empty, 
the mapper fill all values from ArrayHash to entity when the property with same name exist in doctrine entity.
The mapper can map all relations @ManyToMany, @ManyToOne - mapper find related entity repository throws
Kdyby EntityManager with primary key a set entity to mapped entity.

Map Entity to Nette form
---------------------

```php
<?php
namespace App;

use DoctrineMapper\EntityFormMapper;
use Nette\Application\UI\Form;
use Doctrine\ORM\Mapping as ORM;
use Nette\Object;

/**
 * Excample entity
 *
 * @ORM\Entity(repositoryClass="ExampleRepository")
 * @ORM\Table(name="tp_example", indexes={
 * })
 * @author Pecina Ondřej <pecina.ondrej@gmail.com>
 */
class ExampleEntity extends Object
{

	/**
	 * @ORM\Column(length=128)
	 * @var string
	 */
	protected $name;

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer", options={"unsigned"=true})
	 * @ORM\GeneratedValue
	 * @var int
	 */
	protected  $id;

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 * @return ExampleEntity
	 */
	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}
}

class ExampleFormMapper
{
	/** @var EntityFormMapper */
	private $entityFormMapper;

	/**
	 * ExampleFormMapper constructor.
	 * @param EntityFormMapper $entityFormMapper
	 */
	public function __construct(EntityFormMapper $entityFormMapper)
	{
		$this->entityFormMapper = $entityFormMapper;
	}


	public function getForm() {
		$form = new Form();
		$form->addText('name', 'Name')
			->setRequired();

		$form->addSubmit('Sub', 'Save');

		$form->onSuccess[] = function(Form $form) {
			// do stuff
		};
		
		$entity = new ExampleEntity();
		$entity->setName('Example name');

		// this line map all values in entity to form in this case set to component with name name' value "Example name"
		$this->entityFormMapper->setEntityToContainer($entity, $form);
		
		return $form;
	}
}
```

Form builder from entity
---------------------

```php
<?php
namespace App;

use DoctrineMapper\Builder\BuilderDefinition;
use DoctrineMapper\FormEntityBuilder;
use Nette\Application\UI\Form;
use Doctrine\ORM\Mapping as ORM;
use Nette\Object;

/**
 * Test related entity
 *
 * @ORM\Entity(repositoryClass="ExampleRepositoryRel")
 * @ORM\Table(name="tp_example_re", indexes={
 * })
 * @author Pecina Ondřej <pecina.ondrej@gmail.com>
 */
class TestExampleEntityRel extends Object
{

	/**
	 * @ORM\Column(length=128)
	 * @var string
	 */
	protected $name;

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer", options={"unsigned"=true})
	 * @ORM\GeneratedValue
	 * @var int
	 */
	protected  $id;

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 * @return TestExampleEntityRel
	 */
	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}
}

/**
 * Example entity
 *
 * @ORM\Entity(repositoryClass="ExampleRepository")
 * @ORM\Table(name="tp_example", indexes={
 * })
 * @author Pecina Ondřej <pecina.ondrej@gmail.com>
 */
class ExampleEntity extends Object
{

	/**
	 * @ORM\Column(length=128)
	 * @var string
	 */
	protected $name;

	/**
	 * @ORM\ManyToOne(targetEntity="App\TestExampleEntityRel")
	 * @ORM\JoinColumn(name="rel_id", referencedColumnName="id")
	 * @var OwnerEntity
	 */
	protected $related;

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer", options={"unsigned"=true})
	 * @ORM\GeneratedValue
	 * @var int
	 */
	protected  $id;

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 * @return ExampleEntity
	 */
	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}
	
	/**
	 * @param TestExampleEntityRel $related
	 * @return ExampleEntity
	 */
	public function setRelated(TestExampleEntityRel $related) 
	{
		$this->related = $related;
		return $this;
	}
	
	/**
	 * @return TestExampleEntityRel
	 */
	public function getRelated()
	{
		return $this->related;
	}
}

class ExampleFormMapper
{
	/** @var FormEntityBuilder */
	private $formEntityBuilder;

	/**
	 * ExampleFormMapper constructor.
	 * @param FormEntityBuilder $formEntityBuilder
	 */
	public function __construct(FormEntityBuilder $formEntityBuilder)
	{
		$this->formEntityBuilder = $formEntityBuilder;
	}


public function getForm() {
		$entity = new ExampleEntity();
		$entity->setName('Example name');

		// TRUE param build form automatically
		$builder = $this->formEntityBuilder->getBuilder($entity, TRUE);
		$form = $builder->getForm();

		$form->addSubmit('Sub', 'Save');

		$form->onSuccess[] = function(Form $form) {
			// do stuff
		};

		// FALSE param is manual render form
		$builder = $this->formEntityBuilder->getBuilder($entity, FALSE);

		$builder->add([
			'propertyName'  => 'name',
			// allowed types in BuilderDefinition::COMPONENT_TYPE_*
			'componentType' => BuilderDefinition::COMPONENT_TYPE_TEXT_AREA, // override component type (component type is generated automatically from entity)
			'settings' => [
				'label' => 'Name',
				'placeholder'   => 'example@example.com',
				'required'      => TRUE,
				'value'         => 'New name', // override value
				'values'        => ['key' => 'Value', 'key2' => 'Value 2'], // set possible values for list (CheckboxList, RadioList, SelectBox,...)
				'appendValidationRules' => [ // append validation rules
					[
						'validator' => Form::EMAIL,
						'text'      => 'Please fill valid email'
					]
				],
				'validationRules' => [ // replace validation rules
					[
						'validator' => Form::NUMERIC,
						'text'      => 'Please fill number'
					]
				],
			]
		]);

		// this create container with name related and edit box for name and hidden id value
		$builder->add([
			'propertyName'  => 'related',
			'componentType' => BuilderDefinition::COMPONENT_TYPE_CONTAINER,
			'settings' => [
				[
					'propertyName'  => 'name',
					'componentType' => BuilderDefinition::COMPONENT_TYPE_TEXT,
					'settings' => [
						'label' => 'Name'
					]
				],
				[
					'propertyName'  => 'id',
					'componentType' => BuilderDefinition::COMPONENT_TYPE_HIDDEN,
				]
			]
		]);

		return $form;
	}
}
```

Form builder automatically suggest type from property type. This builder automatically suggest required and numeric types.
Automatically create component type from property type and find relations. When you have relation mapped by any annotation,
the builder find values and keys from EntityManager from Kdyby. Target entity have to specified __toString method, 
cause it is for label in select, radio, ...


