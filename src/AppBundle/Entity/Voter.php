<?php


namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as JMSSerializer;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\VoterRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", columnDefinition="SMALLINT NOT NULL DEFAULT 0")
 * @ORM\DiscriminatorMap({0 = "Voter", 1 = "DirectVoter", 2 = "DelegatingVoter", 3 = "NonVoter" })
 *
 */
class Voter
{
    const TYPE_VOTER = 0;
    const TYPE_DIRECT_VOTER = 1;
    const TYPE_DELEGATING_VOTER = 2;
    const TYPE_NON_VOTER = 3;

    /**
     * @var Voting $voting
     *
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Voting")
     */
    protected $voting;

    /**
     * @var User $user
     *
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User", inversedBy="voters")
     */
    protected $user;

    protected $type;

    /**
     * @return Voting
     */
    public function getVoting()
    {
        return $this->voting;
    }

    /**
     * @param Voting $voting
     */
    public function setVoting($voting)
    {
        $this->voting = $voting;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

}