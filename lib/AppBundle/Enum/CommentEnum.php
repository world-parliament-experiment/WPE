<?php

namespace AppBundle\Enum;

/**
 * Class CommentEnum
 * @package AppBundle\Enum
 */
abstract class CommentEnum
{

    const STATE_OPEN = 0;
    const STATE_CLOSED = 1;
    const STATE_DELETED = 2;

    /**
     * @var array
     */
    protected static $stateName = [
        self::STATE_OPEN    => "open",
        self::STATE_CLOSED    => "closed",
        self::STATE_DELETED  => "deleted",
    ];


    /**
     * @param $state
     * @return mixed|string
     */
    public static function getStateName($state) {
        if (!isset(static::$stateName[$state])) {
            return "Unknown state ($state)";
        }

        return static::$stateName[$state];
    }

    /**
     * @return array<integer>
     */

    public static function getAvailableStates() {
        return [
            self::STATE_OPEN,
            self::STATE_CLOSED,
            self::STATE_DELETED,
        ];
    }

}