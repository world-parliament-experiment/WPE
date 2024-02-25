<?php


namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @ORM\Entity(repositoryClass="AppBundle\Repository\VoterRepository")
 * @ORM\Table(name="direct_voter")
 *
 */

class DirectVoter extends Voter
{

    /**
     *
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $weight;

    /**
     *
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $value;

    /**
     * @return mixed
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param mixed $weight
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
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