<?php

namespace AppBundle\Enum;

/**
 * Class CategoryEnum
 * @package AppBundle\Enum
 */
abstract class CategoryEnum
{

    const TYPE_GLOBAL = 0;
    const TYPE_SUPRANATIONAL = 1;
    const TYPE_NATIONAL = 2;
    const TYPE_REGIONAL = 3;
    const TYPE_LOCAL = 4;

    /**
     * @var array
     */
    protected static $typeName = [
        self::TYPE_GLOBAL    => "global",
        self::TYPE_SUPRANATIONAL    => "supranational",
        self::TYPE_NATIONAL => "national",
        self::TYPE_REGIONAL => "regional",
        self::TYPE_LOCAL => "local"
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
            self::TYPE_GLOBAL,
            self::TYPE_SUPRANATIONAL,
            self::TYPE_NATIONAL,
            self::TYPE_REGIONAL,
            self::TYPE_LOCAL
        ];
    }
    public static function checkTypeName($typeName) {

        $type = -1;

        if ($typeName === self::getTypeName(self::TYPE_GLOBAL)) {
            $type = self::TYPE_GLOBAL;
        } elseif ($typeName === self::getTypeName(self::TYPE_SUPRANATIONAL)) {
            $type = self::TYPE_SUPRANATIONAL;
        } elseif ($typeName === self::getTypeName(self::TYPE_NATIONAL)) {
            $type = self::TYPE_NATIONAL;
        } elseif ($typeName === self::getTypeName(self::TYPE_REGIONAL)) {
            $type = self::TYPE_REGIONAL;
        } elseif ($typeName === self::getTypeName(self::TYPE_LOCAL)) {
                $type = self::TYPE_LOCAL;
        }

        if (in_array($type, self::getAvailableTypes())) {
            return $type;
        }

        return false;
    }

}