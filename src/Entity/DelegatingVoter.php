<?php


namespace App\Entity;

use App\Enum\DelegationEnum;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @ORM\Entity(repositoryClass="App\Repository\VoterRepository")
 * @ORM\Table(name="delegating_voter")
 *
 */

class DelegatingVoter extends DirectVoter
{

    /**
     *
     * @ORM\Column(type="array")
     */
    protected $delegateUserIds;

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

}