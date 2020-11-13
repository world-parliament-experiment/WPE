<?php

namespace AppBundle\Entity;

use AppBundle\Enum\DelegationEnum;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 *
 * @ORM\Entity(repositoryClass="AppBundle\Repository\DelegationRepository")
 * @ORM\Table(name="delegation")
 *
 */
class Delegation
{

    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var User $user
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User", inversedBy="delegations")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * @var User $truster
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User", inversedBy="trustees")
     * @ORM\JoinColumn(name="truster_id", referencedColumnName="id")
     */
    protected $truster;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Category", inversedBy="delegations")
     * @ORM\JoinColumn(nullable=true)
     */
    protected $category;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Initiative", inversedBy="delegations")
     * @ORM\JoinColumn(nullable=true)
     */
    protected $initiative;

    /**
     *
     * @Assert\NotBlank(message="Scope should be selected")
     * @ORM\Column(type="smallint", nullable=false)
     */
    protected $scope;

    /**
    *
    * @var DateTime $validUntil
    * @ORM\Column(type="datetime")
    */
    protected $validUntil;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
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
     * @return User
     */
    public function getTruster()
    {
        return $this->truster;
    }

    /**
     * @param User $truster
     */
    public function setTruster($truster)
    {
        $this->truster = $truster;
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
    public function getScope()
    {

        return $this->scope;

    }

    /**
     * @param mixed $scope
     * @return Delegation
     */
    public function setScope($scope)
    {
        if (!in_array($scope, DelegationEnum::getAvailableScopes())) {
            throw new \InvalidArgumentException("Invalid scope");
        }

        $this->scope = $scope;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getValidUntil()
    {
        return $this->validUntil;
    }

    /**
     * @param DateTime $validUntil
     */
    public function setValidUntil($validUntil)
    {
        $this->validUntil = $validUntil;
    }

}

