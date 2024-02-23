<?php
/**
 * Created by PhpStorm.
 * User: Konstantin Borchert
 * Date: 08.05.2019
 * Time: 00:56
 */

namespace AppBundle\Twig;

use AppBundle\Annotation\Page;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Provides an extension for Twig to output breadcrumbs
 */
class PageExtension extends \Twig_Extension
{

    private $page;
    private $translator;

    /**
     * PageExtension constructor.
     */
    public function __construct(TranslatorInterface $translator, Page $page)
    {
        $this->translator = $translator;
        $this->page = $page;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction("page_title", array($this, "getPageTitle")),
        );
    }

    /**
     * @return string
     */
    public function getPageTitle()
    {
        return ($this->page->getTranslate() ? $this->translator->trans($this->page->getTitle()) : $this->page->getTitle());
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return "page";
    }
}