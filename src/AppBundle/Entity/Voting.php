<?php
/**
 * Created by PhpStorm.
 * User: Konstantin Borchert
 * Date: 14.05.2019
 * Time: 21:25
 */

namespace AppBundle\Entity;

use AppBundle\Enum\VotingEnum;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 *
 * @ORM\Entity(repositoryClass="AppBundle\Repository\VotingRepository")
 * @ORM\Table(name="voting")
 *
 */

class Voting
{

    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Initiative", inversedBy="votings")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $initiative;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Vote", mappedBy="voting")
     */
    protected $votes;

    /**
     *
     * @Assert\NotBlank(message="Type should be selected")
     * @ORM\Column(type="smallint", nullable=false)
     */
    protected $type;

    /**
     *
     * @Assert\NotBlank(message="State should be selected")
     * @ORM\Column(type="smallint", nullable=false)
     */
    protected $state;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $startdate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $enddate;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    protected $quorum;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    protected $consensus;

    /**
     * @ORM\Column(type="integer", nullable=true, options={"default": 0})
     */
    protected $eligibleVoters;

    /**
     * @ORM\Column(type="integer", nullable=true, options={"default": 0})
     */
    protected $votesTotal;

    /**
     * @ORM\Column(type="integer", nullable=true, options={"default": 0})
     */
    protected $votesTotalDelegated;

    /**
     * @ORM\Column(type="integer", nullable=true, options={"default": 0})
     */
    protected $votesRejection;

    /**
     * @ORM\Column(type="integer", nullable=true, options={"default": 0})
     */
    protected $votesRejectionDelegated;

    /**
     * @ORM\Column(type="integer", nullable=true, options={"default": 0})
     */
    protected $votesAcception;

    /**
     * @ORM\Column(type="integer", nullable=true, options={"default": 0})
     */
    protected $votesAcceptionDelegated;

    /**
     * @ORM\Column(type="integer", nullable=true, options={"default": 0})
     */
    protected $votesAbstention;

    /**
     * @ORM\Column(type="integer", nullable=true, options={"default": 0})
     */
    protected $votesAbstentionDelegated;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $accepted;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $rejected;

    /**
     * Voting constructor.
     */
    public function __construct()
    {
        $this->votes = new ArrayCollection();
        $this->quorum = 0.05;
        $this->consensus = 0.1;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return Initiative
     */
    public function getInitiative()
    {
        return $this->initiative;
    }

    /**
     * @param Initiative $initiative
     */
    public function setInitiative($initiative)
    {
        $this->initiative = $initiative;
    }

    /**
     * @return ArrayCollection
     */
    public function getVotes()
    {
        return $this->votes;
    }

    /**
     * @param ArrayCollection $votes
     */
    public function setVotes($votes)
    {
        $this->votes = $votes;
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
     * @return Voting
     */
    public function setType($type)
    {
        if (!in_array($type, VotingEnum::getAvailableStates())) {
            throw new InvalidArgumentException("Invalid type");
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
     * @return Voting
     */
    public function setState($state)
    {
        if (!in_array($state, VotingEnum::getAvailableStates())) {
            throw new InvalidArgumentException("Invalid state");
        }

        $this->state = $state;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getStartdate()
    {
        return $this->startdate;
    }

    /**
     * @param mixed $startdate
     */
    public function setStartdate($startdate)
    {
        $this->startdate = $startdate;
    }

    /**
     * @return mixed
     */
    public function getEnddate()
    {
        return $this->enddate;
    }

    /**
     * @param mixed $enddate
     */
    public function setEnddate($enddate)
    {
        $this->enddate = $enddate;
    }


    public function getQuorumAxisBreakMin()
    {
        return round (2 * ($this->getEligibleVoters() * $this->getQuorum()));
    }

    public function getQuorumAxisBreakMax()
    {
        return round ($this->getEligibleVoters() - ($this->getEligibleVoters() * $this->getQuorum()));
    }

    /**
     * @return float
     */
    public function getQuorum()
    {
        return $this->quorum;
    }

    /**
     * @param float $quorum
     */
    public function setQuorum($quorum)
    {
        $this->quorum = $quorum;
    }

    /**
     * @return float
     */
    public function getConsensus()
    {
        return $this->consensus;
    }

    /**
     * @param float $consensus
     */
    public function setConsensus($consensus)
    {
        $this->consensus = $consensus;
    }

    /**
     * @return mixed
     */
    public function getVotesTotal()
    {
        return $this->votesTotal;
    }

    /**
     * @param mixed $votesTotal
     */
    public function setVotesTotal($votesTotal)
    {
        $this->votesTotal = $votesTotal;
    }

    /**
     * @return mixed
     */
    public function getVotesTotalDelegated()
    {
        return $this->votesTotalDelegated;
    }

    /**
     * @param mixed $votesTotalDelegated
     */
    public function setVotesTotalDelegated($votesTotalDelegated)
    {
        $this->votesTotalDelegated = $votesTotalDelegated;
    }

    /**
     * @return mixed
     */
    public function getVotesRejection()
    {
        return $this->votesRejection;
    }

    /**
     * @param mixed $votesRejection
     */
    public function setVotesRejection($votesRejection)
    {
        $this->votesRejection = $votesRejection;
    }

    /**
     * @return mixed
     */
    public function getVotesRejectionDelegated()
    {
        return $this->votesRejectionDelegated;
    }

    /**
     * @param mixed $votesRejectionDelegated
     */
    public function setVotesRejectionDelegated($votesRejectionDelegated)
    {
        $this->votesRejectionDelegated = $votesRejectionDelegated;
    }

    /**
     * @return mixed
     */
    public function getVotesAcception()
    {
        return $this->votesAcception;
    }

    /**
     * @param mixed $votesAcception
     */
    public function setVotesAcception($votesAcception)
    {
        $this->votesAcception = $votesAcception;
    }

    /**
     * @return mixed
     */
    public function getVotesAcceptionDelegated()
    {
        return $this->votesAcceptionDelegated;
    }

    /**
     * @param mixed $votesAcceptionDelegated
     */
    public function setVotesAcceptionDelegated($votesAcceptionDelegated)
    {
        $this->votesAcceptionDelegated = $votesAcceptionDelegated;
    }

    /**
     * @return mixed
     */
    public function getVotesAbstention()
    {
        return $this->votesAbstention;
    }

    /**
     * @param mixed $votesAbstention
     */
    public function setVotesAbstention($votesAbstention)
    {
        $this->votesAbstention = $votesAbstention;
    }

    /**
     * @return mixed
     */
    public function getVotesAbstentionDelegated()
    {
        return $this->votesAbstentionDelegated;
    }

    /**
     * @param mixed $votesAbstentionDelegated
     */
    public function setVotesAbstentionDelegated($votesAbstentionDelegated)
    {
        $this->votesAbstentionDelegated = $votesAbstentionDelegated;
    }

    /**
     * @return mixed
     */
    public function getAccepted()
    {
        return $this->accepted;
    }

    /**
     * @param mixed $accepted
     */
    public function setAccepted($accepted)
    {
        $this->accepted = $accepted;
    }

    /**
     * @return mixed
     */
    public function getRejected()
    {
        return $this->rejected;
    }

    /**
     * @param mixed $rejected
     */
    public function setRejected($rejected)
    {
        $this->rejected = $rejected;
    }

    /**
     * @return mixed
     */
    public function getEligibleVoters()
    {
        return $this->eligibleVoters;
    }

    /**
     * @param mixed $eligibleVoters
     */
    public function setEligibleVoters($eligibleVoters)
    {
        $this->eligibleVoters = $eligibleVoters;
    }

}