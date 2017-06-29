# Array to Entity mapper


DateFormat
---------------------


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
	public function getDateTimeFormat() :string
	{
		return "H:i:s";
	}

	/**
	 * Return date format
	 *
	 * @return string
	 */
	public function getDateFormat() :string
	{
		return "d/m.y";
	}
}
```



Map array to Entity
---------------------

This is simple way to map array or Travesable object to entity.

```php
<?php
namespace App;

use DoctrineMapper\ArrayAccessEntityMapper;
use Nette\Application\UI\Form;
use Doctrine\ORM\Mapping as ORM;
use Nette\Object;
use Nette\Utils\ArrayHash;
use Doctrine\Common\Collections\Collection;

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
	 * @ORM\ManyToOne(targetEntity="ExampleEntity", cascade={"persist"}, fetch="EXTRA_LAZY")
	 * @ORM\JoinColumn(name="single_id", referencedColumnName="id", nullable=true)
	 * @var ExampleEntity
	 */
	protected $singleEntity;
	
	/**
	 * @ORM\ManyToMany(targetEntity="ExampleEntity", fetch="EXTRA_LAZY", cascade={"merge", "persist"})
	 * @ORM\JoinTable(name="tp_example_example",
	 *      joinColumns={@ORM\JoinColumn(name="example_a_id", referencedColumnName="id")},
	 *      inverseJoinColumns={@ORM\JoinColumn(name="example_b_id", referencedColumnName="id")}
	 *      )
	 * @var ExampleEntity[]|Collection|array
	 */
	protected $examples;

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
	public function getId() :int
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getName() :string
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 * @return ExampleEntity
	 */
	public function setName(string $name) :ExampleEntity
	{
		$this->name = $name;
		return $this;
	}
	
	/**
	 * @return ExampleEntity
	 */
	public function getSingleEntity() :ExampleEntity
	{
		return $this->singleEntity;
	}

	/**
	 * @param ExampleEntity $singleEntity
	 * @return ExampleEntity
	 */
	public function setSingleEntity(ExampleEntity $singleEntity) :ExampleEntity
	{
		$this->singleEntity = $singleEntity;
		return $this;
	}
	
	/**
	 * @return Collection
	 */
	public function getExamples() :Collection
	{
		return $this->examples;
	}

	/**
	 * @param Collection $examples
	 * @return ExampleEntity
	 */
	public function setExamples(Collection $examples) :ExampleEntity
	{
		$this->singleEntity = $examples;
		return $this;
	}
}

class ExampleFormMapper
{
	/** @var ArrayAccessEntityMapper */
	private $arrayAccessEntityMapper;

	/**
	 * ExampleFormMapper constructor.
	 * @param ArrayAccessEntityMapper $arrayAccessEntityMapper
	 */
	public function __construct(ArrayAccessEntityMapper $arrayAccessEntityMapper)
	{
		$this->arrayAccessEntityMapper = $arrayAccessEntityMapper;
	}

	public function getForm() : Form
	{
		$form = new Form();
		$form->addText('name', 'Name')
			->setRequired();

		$form->addSubmit('Sub', 'Save');

		$that = $this;
		$form->onSuccess[] = function(Form $form) use ($that) {
			$entity  = new ExampleEntity();

			// array with name is not reuired - if is not set, set all values with existing properties in entity
			$that->arrayAccessEntityMapper->setToEntity($form->getValues(), $entity, ['name']);
			
			// works same like line above
			$that->arrayAccessEntityMapper->setToEntity($form->getValues(), $entity);
			
			// you can map also in this way 
			$that->arrayAccessEntityMapper->setToEntity(ArrayHash::from([
				'name'  => 'Testing name'
			]), $entity);
			// do stuff with entity
		};

		return $form;
	}
	
	public function createEntity()
	{
		$entity  = new ExampleEntity();
		
		$this->arrayAccessEntityMapper->setToEntity(ArrayHash::from([
		    'name'  => 'Testing name',
		    'singleEntity'   => 1,
		    'examples'   => [1,2,4]
		]), $entity);
		
		// or you can create entity automatically
		$this->arrayAccessEntityMapper->setToEntity(ArrayHash::from([
			'name'  => 'Testing name',
			'singleEntity'   => [
				'name'  => 'Sub name'
			],
			'examples'   => [
				[
					'name'  => 'Sub magic name'
				],
				[
					'name'  => 'Sub magic name 2'
				], 1, 2, 3
			]
		]), $entity);
		
		// in this case mapper create sub entities with names and append it to object
		// you can combine arrays and primary key values
	}
}
```
The third argument in setToEntity is optional, because when is empty, 
the mapper fill all values from ArrayHash to entity when the property with same name exist in doctrine entity.
The mapper can map all relations @ManyToMany, @ManyToOne - mapper find related entity repository throws
Kdyby EntityManager with primary key a set entity to mapped entity.

