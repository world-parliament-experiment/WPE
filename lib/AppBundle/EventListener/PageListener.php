<?php
/**
 * Created by PhpStorm.
 * User: Konstantin Borchert
 * Date: 07.05.2019
 * Time: 23:46
 */

namespace AppBundle\EventListener;


use AppBundle\Annotation\Page;
use AppBundle\Annotation\PageAnnotation;
use Doctrine\Common\Annotations\Reader;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class PageListener
{
    /**
     * @var Reader An Reader instance
     */
    protected $reader;

    /**
     * @var Page
     */
    private $page;

    /**
     * Constructor.
     *
     * @param Reader $reader An Reader instance
     */
    public function __construct(Reader $reader, Page $page)
    {
        $this->reader = $reader;
        $this->page = $page;
    }

    /**
     * @param ControllerEvent $event A ControllerEvent instance
     * @throws \ReflectionException
     */
    public function onKernelController(ControllerEvent $event)
    {

        if (!is_array($controller = $event->getController())) {
            return;
        }

        // Annotations from class
        $class = new \ReflectionClass($controller[0]);

        // Manage JMSSecurityExtraBundle proxy class
        if (false !== $className = $this->getRealClass($class->getName())) {
            $class = new \ReflectionClass($className);
        }

        if ($class->isAbstract()) {
            throw new \InvalidArgumentException(sprintf('Annotations from class "%s" cannot be read as it is abstract.', $class));
        }

        if ($event->getRequestType() == HttpKernelInterface::MASTER_REQUEST) {

            // Annotations from method
            $method = $class->getMethod($controller[1]);

            foreach ($this->reader->getMethodAnnotations($method) as $annotation) {
                if ($annotation instanceof PageAnnotation) {

                    $this->page->setTitle($annotation->getTitle());
                    $this->page->setAttributes($annotation->getAttributes());

                }
            }

        }
    }

    private function getRealClass($className)
    {
        if (false === $pos = strrpos($className, '\\__CG__\\')) {
            return false;
        }

        return substr($className, $pos + 8);
    }
}