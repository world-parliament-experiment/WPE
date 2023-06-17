<?php


namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use AppBundle\Enum\FavouriteEnum;
use Symfony\Component\Validator\Constraints as Assert;


/**
 *
 * @ORM\Entity(repositoryClass="AppBundle\Repository\FavouriteRepository")
 * @ORM\Table(name="favourite")
 *
 */

class Favourite
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User", inversedBy="favourites")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $user;

    /**
     *
     * @Assert\NotBlank(message="Type should be selected")
     * @ORM\Column(type="smallint", nullable=false)
     */
    protected $type;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User", inversedBy="friends")
     * @ORM\JoinColumn(nullable=true)
     */
    protected $friend;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Initiative", inversedBy="favourites")
     * @ORM\JoinColumn(nullable=true)
     */
    protected $initiative;

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
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
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

    /**
     * @return mixed|string
     */
    public function getTypeName()
    {
        return FavouriteEnum::getTypeName($this->type);
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getFriend()
    {
        return $this->friend;
    }

    /**
     * @param mixed $friend
     */
    public function setFriend($friend)
    {
        $this->friend = $friend;
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

}