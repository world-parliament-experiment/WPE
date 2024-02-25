<?php
/**
 * Created by PhpStorm.
 * User: Konstantin Borchert
 * Date: 08.05.2019
 * Time: 00:43
 */

namespace AppBundle\Annotation;

class Page
{

    /**
     * @var string Title of the page
     */
    private $title = null;

    /**
     * @var mixed An array of additional attributes for the page
     */
    private $attributes = array();

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTranslate() {
        if (isset($this->attributes['translate'])) {
            return $this->attributes['translate'];
        } else {
            return false;
        }
    }

    /**
     * @return mixed
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param mixed $attributes
     */
    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;
    }

}