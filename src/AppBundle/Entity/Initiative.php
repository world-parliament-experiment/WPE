<?php
/**
 * Created by PhpStorm.
 * User: Konstantin Borchert
 * Date: 14.05.2019
 * Time: 21:01
 */

namespace AppBundle\Entity;

use AppBundle\Enum\FavouriteEnum;
use AppBundle\Enum\InitiativeEnum;
use AppBundle\Enum\VotingEnum;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as JMSSerializer;

/**
 *
 * @ORM\Entity(repositoryClass="AppBundle\Repository\InitiativeRepository")
 * @ORM\Table(name="initiative")
 * @JMSSerializer\ExclusionPolicy("all")
 * @Gedmo\Loggable()
 *
 */

class Initiative
{

    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @JMSSerializer\Expose
     * @JMSSerializer\Type("integer")
     * @JMSSerializer\Groups({"default", "simple"})
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="initiatives")
     * @ORM\JoinColumn(nullable=false)
     * @JMSSerializer\Expose
     * @JMSSerializer\Type("AppBundle\Entity\Category")
     * @JMSSerializer\Groups({"default", "simple"})
     */
    protected $category;

    /**
     * @ORM\OneToMany(targetEntity="Comment", mappedBy="initiative", cascade={"remove"})
     * @ORM\OrderBy({"createdAt" = "ASC"})
     */
    protected $comments;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Voting", mappedBy="initiative")
     */
    protected $votings;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Delegation", mappedBy="initiative")
     */
    protected $delegations;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Favourite", mappedBy="initiative")
     */
    protected $favourites;

    /**
     * @Assert\NotBlank(message="Title for the initiative is mandatory")
     * @ORM\Column(type="string")
     * @Gedmo\Versioned()
     * @JMSSerializer\Expose
     * @JMSSerializer\Type("string")
     * @JMSSerializer\Groups({"default", "simple"})
     */
    protected $title;

    /**
     * @Assert\NotBlank(message="Description for the initiative is mandatory")
     * @ORM\Column(type="text", nullable=true, unique=true)
     * @Gedmo\Versioned()
     * @JMSSerializer\Expose
     * @JMSSerializer\Type("string")
     * @JMSSerializer\Groups({"default", "simple"})
     */
    protected $description;

    /**
     *
     * @Assert\NotBlank(message="Type should be selected")
     * @ORM\Column(type="smallint", nullable=false)
     * @JMSSerializer\Expose
     * @JMSSerializer\Type("integer")
     * @JMSSerializer\Groups({"default", "simple"})
     */
    protected $type;

    /**
     *
     * @Assert\NotBlank(message="State should be selected")
     * @ORM\Column(type="smallint", nullable=false)
     * @JMSSerializer\Expose
     * @JMSSerializer\Type("integer")
     * @JMSSerializer\Groups({"default", "simple"})
     */
    protected $state;

    /**
     *
     * @ORM\Column(type="integer", options={"default": 0})
     * @JMSSerializer\Expose
     * @JMSSerializer\Type("integer")
     * @JMSSerializer\Groups({"default", "simple"})
     */
    protected $views;

    /**
     *
     * @ORM\Column(type="smallint", options={"default": 1})
     * @JMSSerializer\Expose
     * @JMSSerializer\Type("integer")
     * @JMSSerializer\Groups({"default", "simple"})
     */
    protected $duration;

    /**
     * @var User $createdBy
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id")
     * @Gedmo\Blameable(on="create")
     * @JMSSerializer\Expose
     * @JMSSerializer\SerializedName("createdBy")
     * @JMSSerializer\Type("AppBundle\Entity\User")
     * @JMSSerializer\Groups({"default", "simple"})
     */
    protected $createdBy;

    /**
     * @var User $updatedBy
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User")
     * @ORM\JoinColumn(name="updated_by", referencedColumnName="id")
     * @Gedmo\Blameable(on="update")
     */
    protected $updatedBy;

    /**
     * @var DateTime $createdAt
     *
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="create")
     * @JMSSerializer\Expose
     * @JMSSerializer\SerializedName("createdAt")
     * @JMSSerializer\Type("DateTime<'Y-m-d H:i'>")
     * @JMSSerializer\Groups({"default", "simple"})
     */
    protected $createdAt;

    /**
     *
     * @var DateTime $updatedAt
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="update")
     */
    protected $updatedAt;

    /**
     *
     * @var DateTime $publishedAt
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $publishedAt;

    /**
     * @ORM\Column(type="string", unique=true)
     * @Gedmo\Slug(fields={"title"})
     * @JMSSerializer\Expose
     * @JMSSerializer\Type("string")
     * @JMSSerializer\Groups({"default", "simple"})
     */
    private $slug;

    /**
     *
     * @JMSSerializer\VirtualProperty
     * @JMSSerializer\SerializedName("shorttitle")
     * @JMSSerializer\Type("string")
     * @JMSSerializer\Groups({"default", "simple"})
     *
     */

    public function getShorttitle()
    {
        return $this->_truncString($this->getTitle(), 50, " ");
    }

    /**
     *
     * @JMSSerializer\VirtualProperty
     * @JMSSerializer\SerializedName("shortdescription")
     * @JMSSerializer\Type("string")
     * @JMSSerializer\Groups({"default", "simple"})
     *
     */

    public function getShortdescription()
    {
        return $this->_truncString($this->getDescription(), 300);
    }

    public function __construct()
    {
        $this->views = 0;
        $this->comments = new ArrayCollection();
        $this->votings = new ArrayCollection();
        $this->delegations = new ArrayCollection();
        $this->state = InitiativeEnum::STATE_DRAFT;
        $this->type = InitiativeEnum::TYPE_FUTURE;
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
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param mixed $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * @return ArrayCollection
     */
    public function getComments($showParentsOnly = true)
    {
        // on default, show only the root comments with parent = null
        $criteria = Criteria::create();
        if ($showParentsOnly === true) {
            $criteria->where(Criteria::expr()->eq('parent', null));
        }
        return $this->comments->matching($criteria);
    }

    /**
     * @param Comment $comment
     * @return $this
     */
    public function addComment(Comment $comment)
    {
        $comment->setInitiative($this);
        $this->comments[] = $comment;
        return $this;
    }

    /**
     * @param Comment $comment
     * @return $this
     */
    public function removeComment(Comment $comment)
    {
        $this->comments->removeElement($comment);
        return $this;
    }

    public function countComments()
    {
        return $this->comments->count();
    }

    /**
     * @return mixed
     */
    public function getVotings()
    {
        return $this->votings;
    }

    /**
     * @param mixed $votings
     */
    public function setVotings($votings)
    {
        $this->votings = $votings;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
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
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     * @return Initiative
     */

    public function setType($type)
    {
        if (!in_array($type, InitiativeEnum::getAvailableTypes())) {
            throw new \InvalidArgumentException("Invalid type");
        }

        $this->type = $type;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param mixed $state
     * @return Initiative
     */
    public function setState($state)
    {
        if (!in_array($state, InitiativeEnum::getAvailableStates())) {
            throw new \InvalidArgumentException("Invalid state");
        }

        $this->state = $state;

        return $this;
    }

    /**
     * @return User
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * @param User $createdBy
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;
    }

    /**
     * @return User
     */
    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }

    /**
     * @param User $updatedBy
     */
    public function setUpdatedBy($updatedBy)
    {
        $this->updatedBy = $updatedBy;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param DateTime $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return mixed
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @return mixed
     */
    public function getDelegations()
    {
        return $this->delegations;
    }

    /**
     * @param mixed $delegations
     */
    public function setDelegations($delegations)
    {
        $this->delegations = $delegations;
    }

    /**
     * @return mixed
     */
    public function getViews()
    {
        return $this->views;
    }

    /**
     * @param mixed $views
     */
    public function setViews($views)
    {
        $this->views = $views;
    }

    /**
     * @return DateTime
     */
    public function getPublishedAt()
    {
        return $this->publishedAt;
    }

    /**
     * @param DateTime $publishedAt
     */
    public function setPublishedAt($publishedAt)
    {
        $this->publishedAt = $publishedAt;
    }

    /**
     * @JMSSerializer\VirtualProperty
     * @JMSSerializer\SerializedName("typeName")
     * @JMSSerializer\Type("string")
     * @JMSSerializer\Groups({"default", "simple"})
     */
    public function getTypeName()
    {
        return InitiativeEnum::getTypeName($this->type);
    }

    /**
     * @JMSSerializer\VirtualProperty
     * @JMSSerializer\SerializedName("stateName")
     * @JMSSerializer\Type("string")
     * @JMSSerializer\Groups({"default", "simple"})
     */
    public function getStateName()
    {
        return InitiativeEnum::getStateName($this->state);
    }

    /**
     * @return mixed
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param mixed $duration
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
    }


    /**
     * @return Voting|false
     */
    public function getFutureVoting()
    {
        /** @var Voting $voting */
        foreach ($this->votings as $voting) {
            if ($voting->getType() == VotingEnum::TYPE_FUTURE) {
                return $voting;
            }
        }
        return false;
    }

    /**
     * @return Voting|false
     */
    public function getCurrentVoting()
    {
        /** @var Voting $voting */
        foreach ($this->votings as $voting) {
            if ($voting->getType() == VotingEnum::TYPE_CURRENT) {
                return $voting;
            }
        }
        return false;
    }

    /**
     * @param User $user
     * @return Collection
     */
    protected function favouritesByUser(User $user)
    {
        return $this->favourites->filter(function (Favourite $fav) use ($user) {
            return $fav->getUser()->getId() == $user->getId();
        });
    }

    /**
     * @param User $user
     * @return bool
     */
    public function isFavored(User $user)
    {
        return $this->favouritesByUser($user)->count() > 0;
    }

    /**
     * @param User $user
     * @return mixed
     */
    public function getFavourite(User $user)
    {
        return $this->favouritesByUser($user)->first();
    }

    /**
     * can you vote on this initiative now
     * @JMSSerializer\VirtualProperty
     * @JMSSerializer\SerializedName("voteStatus")
     * @JMSSerializer\Type("string")
     * @JMSSerializer\Groups({"default", "simple"})
     * @return bool
     */
    public function getVoteStatus()
    {
        if ($this->getType() === InitiativeEnum::TYPE_FUTURE && $this->getState() === InitiativeEnum::STATE_ACTIVE) {
            if ($voting = $this->getFutureVoting()) {
                if ($voting->getState() === VotingEnum::STATE_WAITING) {
                    return InitiativeEnum::VOTE_SOON;
                } elseif ($voting->getState() === VotingEnum::STATE_OPEN) {
                    return InitiativeEnum::VOTE_NOW;
                }
            }
        } elseif ($this->getType() === InitiativeEnum::TYPE_CURRENT && $this->getState() === InitiativeEnum::STATE_ACTIVE) {
            if ($voting = $this->getCurrentVoting()) {
                if ($voting->getState() === VotingEnum::STATE_WAITING) {
                    return InitiativeEnum::VOTE_SOON;
                } elseif ($voting->getState() === VotingEnum::STATE_OPEN) {
                    return InitiativeEnum::VOTE_NOW;
                }
            }
        }
        return InitiativeEnum::VOTE_NONE;
    }

    /**
     * increments views
     */
    public function incrementViews()
    {
        $this->views = $this->views + 1;
    }

    private function _truncString($string, $limit, $break=".", $pad="...")
    {
        // return with no change if string is shorter than $limit
        if(strlen($string) <= $limit) return $string;

        // is $break present between $limit and the end of the string?
        if(false !== ($breakpoint = strpos($string, $break, $limit))) {
            if($breakpoint < strlen($string) - 1) {
                $string = substr($string, 0, $breakpoint) . $pad;
            }
        }

        return $string;
    }
}