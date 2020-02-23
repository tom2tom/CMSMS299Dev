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
    private static $constCacheArray = null;

	/**
	 * Get all names in the enum
	 * @private
	 * @return array
	 */
    private static function getConstants()
    {
        if (self::$constCacheArray === null) {
            self::$constCacheArray = [];
        }
        $calledClass = static::class;
        if (!isset(self::$constCacheArray[$calledClass])) {
            $reflect = new ReflectionClass($calledClass);
            self::$constCacheArray[$calledClass] = $reflect->getConstants();
        }
        return self::$constCacheArray[$calledClass];
    }

	/**
	 * Get names of all members of the enum
	 * @return array
	 */
    public static function getAll()
    {
        return self::getConstants();
    }

	/**
	 * Check whether $name is a valid enum-member
	 * @param type $name
	 * @param type $strict
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
	 * @param type $value
	 * @param type $strict
	 * @return mixed enum-name | null
	 */
    public static function getName($value, $strict = false)
    {
        $values = array_values(self::getConstants());
		$key = array_search($value, $values, $strict);
        if ($key !== false) {
            return $key;
		}
	}

	/**
	 * Check whether $value is a valid enum-member
	 * @param type $value
	 * @param type $strict
	 * @return bool
	 */
    public static function isValidValue($value, $strict = false) : bool
    {
        $values = array_values(self::getConstants());
        return in_array($value, $values, $strict);
    }

	/**
	 * Get the enum-value for the provided $name
	 * @param type $name
	 * @param type $strict
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
