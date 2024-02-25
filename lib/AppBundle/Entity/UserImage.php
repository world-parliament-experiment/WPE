<?php
/**
 * Created by PhpStorm.
 * User: Konstantin Borchert
 * Date: 14.05.2019
 * Time: 16:39
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 *
 * @ORM\Entity()
 * @ORM\Table(name="user_image")
 *
 */

class UserImage
{


    const USER_IMAGE_TYPE_PORTRAIT = 0;
    const USER_IMAGE_TYPE_AVATAR = 1;

    /**
     * @var User $user
     *
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User", cascade={"persist"}, inversedBy="images")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;


    /**
     * @var
     *
     * @ORM\Id()
     * @ORM\Column(type="smallint")
     */
    protected $imageType;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    protected $contentType;

    /**
     * @var mixed
     *
     * @ORM\Column(type="string")
     */
    protected $path;

    private $avatarPath;

    /**
     * UserImage constructor.
     */
    public function __construct($avatarPath="")
    {
        $this->avatarPath = $avatarPath;
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
    public function getImageType()
    {
        return $this->imageType;
    }

    /**
     * @param mixed $imageType
     */
    public function setImageType($imageType)
    {
        $this->imageType = $imageType;
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * @param string $contentType
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param mixed $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

}