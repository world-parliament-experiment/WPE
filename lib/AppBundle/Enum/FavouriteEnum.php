<?php

namespace AppBundle\Enum;

/**
 * Class FavouriteEnum
 * @package AppBundle\Enum
 */
abstract class FavouriteEnum
{

    const TYPE_USER = 0;
    const TYPE_INITIATIVE = 1;

    /**
     * @var array
     */
    protected static $typeName = [
        self::TYPE_USER    => "user",
        self::TYPE_INITIATIVE    => "initiative",
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
            self::TYPE_USER,
            self::TYPE_INITIATIVE,
        ];
    }
    /**
     * @return integer
     */
    public static function getInitiativeType() {
        return self::TYPE_INITIATIVE;
    }
    /**
     * @return integer
     */
    public static function getUserType() {
        return self::TYPE_USER;
    }

}