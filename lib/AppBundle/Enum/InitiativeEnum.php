<?php


namespace AppBundle\Enum;


/**
 * Class InitiativeEnum
 * @package AppBundle\Enum
 */
abstract class InitiativeEnum
{
    const TYPE_FUTURE = 0;
    const TYPE_CURRENT= 1;
    const TYPE_PAST = 2;
    const TYPE_PROGRAM = 3;

    const STATE_DRAFT = 0;
    const STATE_ACTIVE = 1;
    const STATE_FINISHED = 2;
    const STATE_CLOSED = 3;
    const STATE_DELETED = 4;

    const VOTE_NONE = "";
    const VOTE_SOON = "soon";
    const VOTE_NOW = "now";

    /**
     * @var array
     */
    protected static $typeName = [
        self::TYPE_FUTURE   => "future",
        self::TYPE_CURRENT  => "current",
        self::TYPE_PAST     => "past",
        self::TYPE_PROGRAM  => "program",
    ];

    /**
     * @var array
     */
    protected static $stateName = [
        self::STATE_DRAFT       => "draft",
        self::STATE_ACTIVE      => "active",
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
            self::TYPE_PAST,
            self::TYPE_PROGRAM
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
            self::STATE_DRAFT,
            self::STATE_ACTIVE,
            self::STATE_FINISHED,
            self::STATE_CLOSED,
            self::STATE_DELETED,
        ];
    }

    public static function checkTypeName($typeName) {

        $type = -1;

        if ($typeName === self::getTypeName(self::TYPE_PAST)) {
            $type = self::TYPE_PAST;
        } elseif ($typeName === self::getTypeName(self::TYPE_PROGRAM)) {
            $type = self::TYPE_PROGRAM;
        } elseif ($typeName === self::getTypeName(self::TYPE_CURRENT)) {
            $type = self::TYPE_CURRENT;
        } elseif ($typeName === self::getTypeName(self::TYPE_FUTURE)) {
            $type = self::TYPE_FUTURE;
        }

        if (in_array($type, self::getAvailableTypes())) {
            return $type;
        }

        return false;

    }
}