<?php


namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @ORM\Entity(repositoryClass="App\Repository\VoterRepository")
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

}