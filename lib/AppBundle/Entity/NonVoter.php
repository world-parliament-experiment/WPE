<?php
/**
 * Created by PhpStorm.
 * User: Konstantin Borchert
 * Date: 14.05.2019
 * Time: 21:24
 */

namespace AppBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 *
 * @ORM\Entity(repositoryClass="AppBundle\Repository\VoterRepository")
 * @ORM\Table(name="non_voter")
 *
 */

class NonVoter extends Voter
{


    /**
     *
     * @ORM\Column(type="array")
     */
    protected $delegateUserIds;

    /**
     *
     * @ORM\Column(type="string")
     */
    protected $reason;

    /**
     * @return mixed
     */
    public function getDelegateUserIds()
    {
        return $this->delegateUserIds;
    }

    /**
     * @param mixed $delegateUserIds
     */
    public function setDelegateUserIds($delegateUserIds)
    {
        $this->delegateUserIds = $delegateUserIds;
    }

    /**
     * @return mixed
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * @param mixed $reason
     */
    public function setReason($reason)
    {
        $this->reason = $reason;
    }

}