<?php


namespace AppBundle\Enum;


/**
 * Class VotingEnum
 * @package AppBundle\Enum
 */
abstract class VotingEnum
{

    const STATE_WAITING = 0;
    const STATE_OPEN = 1;
    const STATE_FINISHED = 2;
    const STATE_CLOSED = 3;
    const STATE_DELETED = 4;
    const ENDDATEONEWEEK= 1;
    const ENDDATETWOOWEEKS = 2;
    const TYPE_FUTURE = 0;
    const TYPE_CURRENT = 1;
    const CONSENSUS = 0.01;
    const QUORUM = 0;

    /**
     * @var array
     */
    protected static $typeName = [
        self::TYPE_FUTURE   => "future",
        self::TYPE_CURRENT  => "current",
    ];

    /**
     * @var array
     */
    protected static $stateName = [
        self::STATE_WAITING     => "waiting",
        self::STATE_OPEN        => "open",
        self::STATE_FINISHED    => "finished",
        self::STATE_CLOSED      => "closed",
        self::STATE_DELETED     => "deleted",
    ];

    /**
     * @param $type
     * @return mixed|string
     */
    public static function getTypeName($type) {
        if (!isset(static::$typeName[$type])) {
            return "Unknown type ($type)";
        }

        return static::$typeName[$type];
    }

    /**
     * @return array<integer>
     */
    public static function getAvailableTypes() {
        return [
            self::TYPE_FUTURE,
            self::TYPE_CURRENT,
        ];
    }

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
            self::STATE_WAITING,
            self::STATE_OPEN,
            self::STATE_FINISHED,
            self::STATE_CLOSED,
            self::STATE_DELETED,
        ];
    }
}