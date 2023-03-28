<?php


namespace AppBundle\Enum;


/**
 * Class DelegationEnum
 * @package AppBundle\Enum
 */
abstract class DelegationEnum
{

    const SCOPE_PLATFORM = 0;
    const SCOPE_CATEGORY = 1;
    const SCOPE_INITIATIVE = 2;

    /**
     * @var array
     */
    protected static $scopeName = [
        self::SCOPE_PLATFORM    => "platform",
        self::SCOPE_CATEGORY    => "category",
        self::SCOPE_INITIATIVE  => "initiative",
    ];


    /**
     * @param $scope
     * @return mixed|string
     */
    public static function getScopeName($scope) {
        if (!isset(static::$scopeName[$scope])) {
            return "Unknown scope ($scope)";
        }

        return static::$scopeName[$scope];
    }

    /**
     * @return array<integer>
     */
    public static function getAvailableScopes() {
        return [
            self::SCOPE_PLATFORM,
            self::SCOPE_CATEGORY,
            self::SCOPE_INITIATIVE,
        ];
    }

}