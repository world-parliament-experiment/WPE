<?php
/**
 * Created by PhpStorm.
 * User: Konstantin Borchert
 * Date: 14.05.2019
 * Time: 21:01
 */

namespace AppBundle\Entity;

use AppBundle\Enum\CommentEnum;
use AppBundle\Enum\DelegationEnum;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Loggable\Entity\Repository\LogEntryRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as JMSSerializer;

/**
 *
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CommentRepository")
 * @ORM\Table(name="comment")
 * @Gedmo\Loggable()
 *
 */

class Comment
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
     * @ORM\OneToMany(targetEntity="Comment", mappedBy="parent")
     * @ORM\OrderBy({"parent" = "DESC", "createdAt" = "ASC"})
     */
    protected $children;

    /**
     * @ORM\ManyToOne(targetEntity="Comment", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     * @JMSSerializer\Expose
     * @JMSSerializer\Type("AppBundle\Entity\Comment")
     * @JMSSerializer\Groups({"default", "simple"})
     */
    protected $parent;

    /**
     * @ORM\ManyToOne(targetEntity="Initiative", inversedBy="comments")
     * @ORM\JoinColumn(nullable=false)
     * @JMSSerializer\Expose
     * @JMSSerializer\Type("AppBundle\Entity\Initiative")
     * @JMSSerializer\Groups({"default", "simple"})
     */
    protected $initiative;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Gedmo\Versioned()
     * @JMSSerializer\Expose
     * @JMSSerializer\Type("string")
     * @JMSSerializer\Groups({"default", "simple"})
     * @Assert\NotBlank(message="Message should be set")
     */
    protected $message;

    /**
     *
     * @Assert\NotBlank(message="State should be selected")
     * @ORM\Column(type="smallint", nullable=false)
     * @Gedmo\Versioned()
     */
    protected $state;

    /**
     *
     * @ORM\Column(type="integer", options={"default": 0})
     * @JMSSerializer\Expose
     * @JMSSerializer\Groups({"default", "simple"})
     */
    protected $liked;

    /**
     *
     * @ORM\Column(type="integer", options={"default": 0})
     * @JMSSerializer\Expose
     * @JMSSerializer\Groups({"default", "simple"})
     */
    protected $disliked;

    /**
     *
     * @ORM\Column(type="integer", options={"default": 0})
     * @JMSSerializer\Expose
     * @JMSSerializer\Groups({"default", "simple"})
     */
    protected $reported;

    /**
     *
     * @ORM\Column(type="text", nullable=true)
     * @Gedmo\Versioned()
     * @JMSSerializer\Expose
     * @JMSSerializer\Type("string")
     * @JMSSerializer\Groups({"default", "simple"})
     */
    protected $note;

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
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    protected $updatedAt;

    public function __construct() {
        $this->children = new ArrayCollection();
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
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @return mixed
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param mixed $parent
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * @return mixed
     */
    public function getInitiative()
    {
        return $this->initiative;
    }

    /**
     * @param mixed $initiative
     */
    public function setInitiative($initiative)
    {
        $this->initiative = $initiative;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param mixed $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
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
     * @return Comment
     */
    public function setScope($state)
    {
        if (!in_array($state, CommentEnum::getAvailableStates())) {
            throw new \InvalidArgumentException("Invalid state");
        }

        $this->state = $state;

        return $this;
    }

    /**
     * @param mixed $state
     */
    public function setState($state)
    {
        $this->state = $state;
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
    public function getLiked()
    {
        return $this->liked;
    }

    /**
     * @param mixed $liked
     */
    public function setLiked($liked)
    {
        $this->liked = $liked;
    }

    /**
     * @return mixed
     */
    public function getDisliked()
    {
        return $this->disliked;
    }

    /**
     * @param mixed $disliked
     */
    public function setDisliked($disliked)
    {
        $this->disliked = $disliked;
    }

    /**
     * @return mixed
     */
    public function getReported()
    {
        return $this->reported;
    }

    /**
     * @param mixed $reported
     */
    public function setReported($reported)
    {
        $this->reported = $reported;
    }

    /**
     * @return mixed
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * @param mixed $note
     */
    public function setNote($note)
    {
        $this->note = $note;
    }

    /**
     * @param Comment $comment
     * @return $this
     */
    public function addReply(Comment $comment)
    {
        $comment->setParent($this);
        $comment->setInitiative($this->initiative);
        $this->children[] = $comment;
        return $this;
    }
    /**
     * @param LogEntryRepository $repo
     * @return mixed
     */public function getLastMessageModifierUsername(LogEntryRepository $repo )
    {
        $logs = $repo->getLogEntries($this);
        foreach ($logs as $log){
            $data = $log->getData();
            if(isset($data['message']) && $data['message'] == $this->message){
                return $log->getUsername();
            }
        }
        return null;
    }
    /**
     * @param LogEntryRepository $repo
     * @return mixed
     */
    public function getLastStateModifierUsername(LogEntryRepository $repo )
    {
        $logs = $repo->getLogEntries($this);
        foreach ($logs as $log){
            $data = $log->getData();
            if(isset($data['state']) && $data['state'] == $this->state){
                return $log->getUsername();
            }
        }
        return null;
    }
    public function getChanges(LogEntryRepository $repo )
    {
        return $repo->getLogEntries($this);
    }
}