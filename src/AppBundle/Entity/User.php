<?php

namespace AppBundle\Entity;

use AppBundle\Enum\FavouriteEnum;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Rollerworks\Component\PasswordStrength\Validator\Constraints as RollerworksPassword;
use JMS\Serializer\Annotation as JMSSerializer;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserRepository")
 * @ORM\Table(name="fos_user")
 */
class User extends BaseUser
{

    const USER_GENDER_MALE = "MALE";
    const USER_GENDER_FEMALE = "FEMALE";
    const USER_GENDER_DIVERSE = "DIVERSE";
    const USER_GENDER_NOTSTATED = "NOT STATED";

    /**
     * @Assert\Callback
     * @param ExecutionContextInterface $context
     * @param $payload
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {

        foreach($this->roles as $role){
            if(!preg_match("/^ROLE_[A-Z0-9_]+$/",$role)){
                $context->buildViolation('group.roles.format')
                        ->atPath('userroles')
                        ->addViolation();
                break;
            }
        }
    }

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @JMSSerializer\Type("integer")
     * @JMSSerializer\Groups({"default", "simple"})
     */
    protected $id;

    
    /**
     * @Assert\NotBlank(message="Please enter your first name.", groups={"Registration", "Profile"})
     * @ORM\Column(type="string")
     * @JMSSerializer\Type("string")
     * @JMSSerializer\Groups({"default"})
     *
     */
    protected $firstname;

    /**
     * @Assert\NotBlank(message="Please enter your last name.", groups={"Registration", "Profile"})
     * @ORM\Column(type="string")
     * @JMSSerializer\Type("string")
     * @JMSSerializer\Groups({"default"})
     */
    protected $lastname;


    /**
     * @ORM\Column(type="string", nullable=true)
     * @JMSSerializer\Type("string")
     * @JMSSerializer\Groups({"default", "simple"})
     */
    protected $gender;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    protected $birthday;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @JMSSerializer\Type("string")
     * @JMSSerializer\Groups({"default", "simple"})
     */
    protected $country;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @JMSSerializer\Type("string")
     * @JMSSerializer\Groups({"default", "simple"})
     */
    protected $city;

    /**
     * @ORM\Column(type="smallint", options={"default": 0})
     */

    protected $consents;

    /**
     * @Assert\Count(min=1, minMessage = "You must specify at least one group", groups={"NewUser"})
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Group")
     * @ORM\JoinTable(name="fos_user_group",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id")}
     * )
     */
    protected $groups;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Delegation", mappedBy="user")
     */
    protected $delegations;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\UserImage", mappedBy="user")
     */
    protected $images;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Delegation", mappedBy="truster")
     */
    protected $trustees;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Favourite", mappedBy="user")
     */
    protected $favourites;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Favourite", mappedBy="friend",cascade={"persist"})
     */
    protected $friends;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Vote", mappedBy="user")
     */
    protected $votes;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Voter", mappedBy="user")
     */
    protected $voters;

    /**
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="create")
     * @JMSSerializer\Type("DateTime<'Y-m-d H:i'>")
     * @JMSSerializer\SerializedName("registeredAt")*
     * @JMSSerializer\Groups({"default", "simple"})
     */
    protected $registeredAt;


    /**
     * @RollerworksPassword\PasswordStrength(minLength=7, minStrength=3)
     */
    protected $plainPassword;

    /**
     * @JMSSerializer\Type("string")
     * @JMSSerializer\Groups({"default"})
     */
    protected $email;

    /**
     * @JMSSerializer\Type("string")
     * @JMSSerializer\Groups({"default", "simple"})
     */
    protected $username;

    /**
     * @JMSSerializer\Type("DateTime<'Y-m-d H:i'>")
     * @JMSSerializer\SerializedName("lastLogin")*
     * @JMSSerializer\Groups({"default", "simple"})
     */
    protected $lastLogin;


    public function __construct()
    {
        parent::__construct();

        $this->delegations = new ArrayCollection();
        $this->trustees = new ArrayCollection();
        $this->votes = new ArrayCollection();

    }

    /**
     * @return DateTime
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * @param DateTime $birthday
     */
    public function setBirthday($birthday)
    {
        $this->birthday = $birthday;
    }

    /**
     * @return string
     */

    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $description;

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
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param string $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }
    /**
     *
     * @JMSSerializer\VirtualProperty
     * @JMSSerializer\SerializedName("fullname")
     * @JMSSerializer\Type("string")
     * @JMSSerializer\Groups({"default"})
     *
     */

    public function getFullname()
    {
        return trim($this->firstname . " " . $this->lastname);
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * @param string $gender
     */
    public function setGender($gender)
    {
        $this->gender = $gender;
    }

    /**
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }


    /**
     * @param string $firstname
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
    }

    /**
     * @param string $lastname
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
    }

    /**
     * @return array
     */
    public function getUserRoles()
    {
        return $this->roles;
    }
    /**
     * @param array
     */
    public function setUserRoles($roles)
    {
        $this->roles = $roles;
    }

    /**
     * @return DateTime
     */
    public function getRegisteredAt()
    {
        return $this->registeredAt;
    }

    /**
     * @param DateTime $registeredAt

     */
    public function setRegisteredAt($registeredAt)
    {
        $this->registeredAt = $registeredAt;
    }

    /**
     * @param $role
     * @return $this|BaseUser
     */
    public function addRole($role)
    {
        $role = strtoupper($role);

        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }

        return $this;
    }
    /**
     * @param User $user
     * @return Collection
     */
    protected function friendsByUser(User $user){
        return $this->friends->filter(function(Favourite $fav) use($user){
            return $fav->getUser()->getId() == $user->getId();
        });
    }
    /**
     * @param User $user
     * @return bool
     */
    public function  isFriend(User $user){
        return $this->friendsByUser($user)->count() > 0;
    }
    /**
     * @param User $user
     * @return Favorite|null
     */
    public function  getFriend(User $user){
        return $this->friendsByUser($user)->first();
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * @return mixed
     */
    public function getConsents()
    {
        return $this->consents;
    }

    /**
     * @param mixed $consents
     */
    public function setConsents($consents)
    {
        $this->consents = $consents;
    }


}