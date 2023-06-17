<?php
/**
 * Created by PhpStorm.
 * User: borchert
 * Date: 12.12.2016
 * Time: 12:42
 */

namespace AppBundle\Twig;

use Twig\Extension\AbstractExtension;


class WidgetExtension extends AbstractExtension
{

    public function getTests()
    {
        return array(
            new \Twig\TwigTest('instanceOf', [$this, 'isInstanceOf']),
            new \Twig\TwigTest('UnknownDate', [$this, 'isUnknownDate']),
        );
    }

    public function getFunctions()
    {
        return [
            new \Twig\TwigFunction(
                'panel_start',
                [$this, 'panel_start'],
                ['needs_environment' => true, 'is_safe' => ['html']]
            ),
            new \Twig\TwigFunction(
                'panel_end',
                [$this, 'panel_end'],
                ['needs_environment' => true, 'is_safe' => ['html']]
            ),
            new \Twig\TwigFunction(
                'remote_file_exists',
                [$this, 'remoteFileExists']),

        ];
    }


    public function getFilters()
    {
        return array(
            new \Twig\TwigFilter('format_bytes', array($this, 'formatBytes')),
            new \Twig\TwigFilter('json_decode', array($this, 'jsonDecode')),
        );
    }

    public function jsonDecode($string)
    {
        return json_decode($string);
    }

    function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        // Uncomment one of the following alternatives
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public function panel_start(\Twig\Environment $environment, $title='', $options=array())
    {
        return $environment->render('Widget/panel_start.html.twig', array("title" => $title, "options" => $options));
    }

    public function panel_end(\Twig\Environment $environment)
    {
        return $environment->render('Widget/panel_end.html.twig');
    }


    public function remoteFileExists($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $status === 200 ? true : false;
    }

    public function isInstanceOf($object, $class)
    {
        $reflectionClass = new \ReflectionClass($class);
        return $reflectionClass->isInstance($object);
    }

    public function isUnknownDate(\DateTime $object)
    {
        if (!$object instanceof \DateTime) {
            throw new  \InvalidArgumentException('Type has to be DateTime');
        }

        if ($object == new \DateTime('3000-01-01')) {
            return true;
        }
        return false;
    }

    public function getName()
    {
        return 'app.twig.widget_extension';
    }
}