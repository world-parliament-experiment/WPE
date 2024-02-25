<?php


namespace AppBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TextExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('truncate', [$this, 'truncate']),
            new TwigFilter('wordwrap', [$this, 'wordwrap']),
        ];
    }

    public function truncate(string $string, int $length = 30, string $ellipsis = '...'): string
    {
        if (strlen($string) > $length) {
            $string = substr($string, 0, $length - strlen($ellipsis)) . $ellipsis;
        }

        return $string;
    }

    public function wordwrap(string $string, int $width = 75, string $break = "\n", bool $cut = false): string
    {
        return wordwrap($string, $width, $break, $cut);
    }

}