<?php
/**
 * Created by PhpStorm.
 * User: kborc
 * Date: 04.03.2019
 * Time: 16:45
 */

namespace AppBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 *
 * @ORM\Entity(repositoryClass="AppBundle\Repository\VoteRepository")
 * @ORM\Table(name="vote")
 *
 */
class Vote
{

    /**
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Voting", inversedBy="votes")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $voting;
    
     /**
     * @ORM\Column(type="integer")
     */
    protected $value;

    /**
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User", inversedBy="votes")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $user;   

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
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

}
