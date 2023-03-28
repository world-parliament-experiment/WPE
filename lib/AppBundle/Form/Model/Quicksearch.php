<?php

namespace AppBundle\Form\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;


class Quicksearch
{
    /**
     * @var string
     * @Assert\NotBlank(message="at least one character required")
     *
     */
    public $query;

    /**
     * Search constructor.
     */
    public function __construct()
    {
    }

    /**
     * Get query
     *
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     *
     * setQuery
     *
     * @param $query
     * @return mixed|string
     */
    public function setQuery($query)
    {
        // fix for jquery.download handling search requests with quotes
        $query = str_replace("%22", '"', $query);
        $this->query = $query;

        return $this->query;
    }

    function __toString()
    {
        return $this->query;
    }

}

