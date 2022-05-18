<?php
/*
Enumerator class
This is an extension of contributions by Brian Cline and others.
See https://stackoverflow.com/questions/254514/php-and-enumerations
Stackoverflow contributions are CC BY-SA 3.0 licensed
*/
namespace CMSMS;

use ReflectionClass;

abstract class BasicEnum
{
    // static properties here >> Lone property|ies ?
    private static $constCacheArray = [];

    /**
     * Get all names in the enum
     * @private
     * @return array
     */
    private static function getConstants()
    {
        $calledClass = static::class;
        if (!isset(self::$constCacheArray[$calledClass])) {
            $reflect = new ReflectionClass($calledClass);
            self::$constCacheArray[$calledClass] = $reflect->getConstants();
        }
        return self::$constCacheArray[$calledClass];
    }

    /**
     * Get names and corresponding values of all members of the enum
     * @return array
     */
    public static function getAll()
    {
        return self::getConstants();
    }

    /**
     * Get names of all members of the enum
     * @return array
     */
    public static function getNames()
    {
        return array_keys(self::getConstants());
    }

    /**
     * Check whether $name is a valid enum-member
     * @param mixed string|const $name
     * @param bool $strict optional flag whether to require exact case-match, default false
     * @return bool
     */
    public static function isValidName($name, $strict = false) : bool
    {
        $constants = self::getConstants();
        if ($strict) {
            return isset($constants[$name]);
        }
        $keys = array_map('strtolower', array_keys($constants));
        return in_array(strtolower($name), $keys);
    }

    /**
     * Get the enum-name for the provided $value
     * @param mixed const|int|string $value
     * @param bool $strict optional parameter for array_search(), default false
     * @return mixed enum-name | null
     */
    public static function getName($value, $strict = false)
    {
        $key = array_search($value, self::getConstants(), $strict);
        if ($key !== false) {
            return $key;
        }
    }

    /**
     * Check whether $value is a valid enum-member
     * @param mixed const|int|string $value
     * @param bool $strict optional parameter for in_array(), default false
     * @return bool
     */
    public static function isValidValue($value, $strict = false) : bool
    {
        $values = array_values(self::getConstants());
        return in_array($value, $values, $strict);
    }

    /**
     * Get the enum-value for the provided $name
     * @param mixed string|const $name
     * @param bool $strict optional flag whether to require exact case-match, default false
     * @return mixed enum-value | null
     */
    public static function getValue($name, $strict = false)
    {
        $constants = self::getConstants();
        if ($strict) {
            return $constants[$name] ?? null;
        }
        $keys = array_keys($constants);
        $idx = array_search($name, $keys);
        if ($idx !== false) {
            return $constants[$keys[$idx]];
        }
    }
} // class
