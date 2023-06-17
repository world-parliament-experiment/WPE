<?php
/**
 * Created by PhpStorm.
 * User: Konstantin Borchert
 * Date: 07.05.2019
 * Time: 23:41
 */

namespace AppBundle\Annotation;

/**
 *
 * @Annotation
 *
 */
class PageAnnotation
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
     * Constructor.
     *
     * @param array $data An array of annotation values
     */
    public function __construct(array $data)
    {
        if (isset($data['value'])) {
            $data['title'] = $data['value'];
            unset($data['value']);
        }

        foreach ($data as $key => $value) {
            $method = 'set'.$key;
            if (!method_exists($this, $method)) {
                throw new \BadMethodCallException(sprintf("Unknown property '%s' on annotation '%s'.", $key, get_class($this)));
            }
            $this->$method($value);
        }
    }

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