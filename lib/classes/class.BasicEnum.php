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

    public static function getAll()
    {
        return self::getConstants();
    }

	public static function isValidName($name, $strict = false)
    {
        $constants = self::getConstants();
        if ($strict) {
            return isset($constants[$name]);
        }
        $keys = array_map('strtolower', array_keys($constants));
        return in_array(strtolower($name), $keys);
    }

    public static function getName($value, $strict = false)
    {
        $values = array_values(self::getConstants());
		$key = array_search($value, $values, $strict);
        if ($key !== false) {
            return $key;
		}
	}

    public static function isValidValue($value, $strict = false)
    {
        $values = array_values(self::getConstants());
        return in_array($value, $values, $strict);
    }

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
