# Entity to Nette Form/Container


Map Entity to Nette form
---------------------

```php
<?php
namespace App;

use DoctrineMapper\EntityFormMapper;
use Nette\Application\UI\Form;
use Doctrine\ORM\Mapping as ORM;
use Nette\Object;
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
		$this->examples = $examples;
		return $this;
	}
}


class ExampleFormMapper
{
	/** @var EntityFormMapper */
	private $entityFormMapper;
	
	/** @var ExampleRepository */
	private $repository;

	/**
	 * ExampleFormMapper constructor.
	 * @param EntityFormMapper $entityFormMapper
	 */
	public function __construct(EntityFormMapper $entityFormMapper, ExampleRepository $repository)
	{
		$this->entityFormMapper = $entityFormMapper;
	}


	public function getForm() : Form
	{
		$form = new Form();
		$form->addText('name', 'Name')
			->setRequired();
		
		
		$allowedValues = $this->repository->findAll();
		$vals = [];
		foreach ($allowedValues  as $val) {
			$vals[$val->getId()] = $val->getName();
		}
		
		$form->addSelect('singleEntity', 'Single relation', $vals);
		$form->addMultiSelect('examples', 'Many relation', $vals);

		$form->addSubmit('Sub', 'Save');

		$form->onSuccess[] = function(Form $form) {
			// do stuff
		};
		
		/** @var ExampleEntity $entity */
		$entity = $this->repository->find(11);

		// this line map all values in entity to form in this case set to component with name name' value "Example name"
		// Relations will be mapped automatically for primary keys from related entities
		// Form can throws exception with non existing value - you can disable it with: $form['examples']->checkAllowedValues = FALSE;
		$this->entityFormMapper->setEntityToContainer($entity, $form);
		
		return $form;
	}
}
```
