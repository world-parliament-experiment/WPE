<?php


namespace AppBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TimeDiffExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('time_diff', [$this, 'getTimeDiff']),
        ];
    }

    public function getTimeDiff($date1, $date2): string
    {
        // Calculate the time difference between $date1 and $date2
        $interval = $date1->diff($date2);

        // Build a string representation of the time difference
        $parts = [];
  
        if ($interval->d) {
            $parts[] = $interval->format('%d days ago');
        }

        // Return the string representation of the time difference
        return implode(', ', $parts);
    }
}
