# Form Entity builder

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
	public function getId() : int
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getName() : string
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 * @return TestExampleEntityRel
	 */
	public function setName($name) : TestExampleEntityRel
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
	 * @var TestExampleEntityRel
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
	public function getId() : int
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getName() : string
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 * @return ExampleEntity
	 */
	public function setName($name) : ExampleEntity
	{
		$this->name = $name;
		return $this;
	}
	
	/**
	 * @param TestExampleEntityRel $related
	 * @return ExampleEntity
	 */
	public function setRelated(TestExampleEntityRel $related)  : ExampleEntity
	{
		$this->related = $related;
		return $this;
	}
	
	/**
	 * @return TestExampleEntityRel
	 */
	public function getRelated() : TestExampleEntityRel
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


	public function getForm() : Form
	{
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
