<?php
/**
 * Created by PhpStorm.
 * User: kborc
 * Date: 04.03.2019
 * Time: 15:53
 */

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMSSerializer;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 *
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CategoryRepository")
 * @ORM\Table(name="category")
 *
 */
class Category
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Initiative", mappedBy="category")
     */
    private $initiatives;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Delegation", mappedBy="category")
     */
    private $delegations;

    /**
     * @Assert\NotBlank(message="Name for the category is mandatory")
     * @ORM\Column(type="string")
     * @JMSSerializer\Expose
     * @JMSSerializer\Type("string")
     * @JMSSerializer\Groups({"default", "simple"})
     */
    protected $name;

    /**
     * @ORM\Column(type="text", nullable=true)     *
     */
    protected $description;

    /**
     * @ORM\Column(type="string", unique=true)
     * @Gedmo\Slug(fields={"name"})
     */
    private $slug;


    /**
     * Category constructor.
     */
    public function __construct()
    {
        $this->initiatives = new ArrayCollection();
        $this->delegations = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return ArrayCollection
     */
    public function getInitiatives()
    {
        return $this->initiatives;
    }

    /**
     * @param ArrayCollection $initiatives
     */
    public function setInitiatives($initiatives)
    {
        $this->initiatives = $initiatives;
    }

    /**
     * @return mixed
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @return string
     */
    public function getImage(){
        return $this->getImageLarge();
    }

    /**
     * @return string
     */
    public function getImageLarge(){
        return 'assets/img/category/K_' . $this->getId() . '_large.jpg';
    }

    /**
     * @return string
     */
    public function getImageSmall(){
        return 'assets/img/category/K_' . $this->getId() . '_small.jpg';
    }

}
