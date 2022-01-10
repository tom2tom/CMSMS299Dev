<?php
/*
Singleton class for dealing with one-instance-per-request classes and properties.
Copyright (C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS;

use RuntimeException;
//use SplObjectStorage;
use Throwable;

/**
 * Singleton class that records, for later supply on-demand, classes
 * and properties that should not, or cannot properly, be 'static' for
 * the duration of the current request.
 *
 * Notably the main 'system' singleton objects. Those will be autoloaded
 * on demand from namespaces: CMSMS | global | CMSMS\internal, or else
 * must be manually insert()'d by some external process before retrieval
 * from here. Those objects can be retrieved by CMSMS\SingleItem::Classname(),
 * or CMSMS\SingleItem::get(Classname).
 *
 * Store and retrieve other properties using CMSMS\SingleItem::{set|get}propkey
 *
 * @final
 * @since 2.99
 * @package CMS
 * @license GPL
 * @throws RuntimeException if the supplied classname or property name
 *  is not recognised
 */
final class SingleItem //extends SplObjectStorage worth doing this subclass? (iteration, ArrayAccess etc)
{
	/**
	 * @var object Preserves this object through the request
	 * @ignore
	 */
	private static $instance = null;

	/**
	 * @var array Cached singleton-instances of other classes
	 * Each member like 'identifier' => object
	 * The identifier may be a classname (without namespace), or an alias.
	 * @ignore
	 */
	public $singles = [];

	/**
	 * @var array Cached properties other than system-singletons
	 * Each member like 'identifier' => value
	 * The identifier includes suitable namespace differentiation
	 * @ignore
	 */
	public $properties = [];

	private function __construct() {}
	private function __clone() {}

	/**
	 * Retrieve a class singleton-object, after construction if necessary.
	 * Typically this requires existence of cms_autoloader().
	 * Some singletons e.g. the database connection (::Db()) and the
	 * admin-theme (::Theme()) can't be simply autoloaded, so must be
	 * insert()'d by some external process before retrieval from here.
	 *
	 * @param string $name Class name, without namespace. No aliases.
	 * @param mixed $args scalar | array | null Optional class-constructor
	 *  argument(s) (rare for CMSMS singletons)
	 * @return object | not at all
	 * @throws RuntimeException if the class is not found or its constructor bombs
	 */
	public static function __callStatic(string $name, array $args)
	{
		if (!self::$instance) {
			self::$instance = new self();
		}
		$inst = self::$instance;
		if (isset($inst->singles[$name])) {
			return $inst->singles[$name];
		} else {
			$obj = null;
			foreach ([
				'CMSMS\\'.$name, //most likely namespace
				$name,
				'CMSMS\internal\\'.$name, //least likely (mainly Smarty)
			] as $i => $classname) {
				if (class_exists($classname, false)) {
					try {
						$obj = new $classname(...$args);
						break;
					} catch (Throwable $t) {
						throw new RuntimeException($t->GetMessage().': '.__CLASS__.'::'.$name.'()');
					}
				} elseif ($i != 1) { // global- and CMSMS-namespace class files are in the same place
					// there's no trap for class-not-found errors i.e. must workaround namespace here
					// this could autoload a class-file even though the loaded class has wrong namespace
					cms_autoloader($classname);
					if (class_exists($classname, false)) {
						try {
							$obj = new $classname(...$args);
							break;
						} catch (Throwable $t) {
							throw new RuntimeException($t->GetMessage().': '.__CLASS__.'::'.$name.'()');
						}
					}
				}
			}
			if ($obj) {
				$inst->singles[$name] = $obj;
				return $obj;
			} else {
				throw new RuntimeException('Unrecognized class requested '.__CLASS__.'::'.$name.'()');
			}
		}
	}

	/**
	 * Cache a property
	 * Not for singletons. PHP (7 at least) doesn't support syntax like SingleItem->classname
	 *
	 * @param string $name Property name, with suitable namespace-differentiation
	 * @param mixed $value The data to be cached
	 */
	public function __set(string $name, $value)
	{
		if (!self::$instance) {
			self::$instance = new self();
		}
		self::$instance->properties[$name] = $value;
	}

	/**
	 * Retrieve a cached property
	 * Not for singletons. PHP (7 at least) doesn't support syntax like SingleItem->classname
	 *
	 * @param string $name Property name, with suitable namespace-differentiation
	 * @return mixed Cached property value | null if not found
	 */
	public function __get(string $name)
	{
		if (!self::$instance || !isset(self::$instance->properties[$name])) {
			return null;
		}
		return self::$instance->properties[$name];
	}

	/**
	 * Static analog of magic method __set()
	 */
	public static function set(string $name, $val)
	{
		if (!self::$instance) {
			self::$instance = new self();
		}
		self::$instance->__set($name, $val);
	}

	/**
	 * Static analog of magic method __get()
	 */
	public static function get(string $name)
	{
		if (!self::$instance) {
			return null;
		}
		return self::$instance->__get($name);
	}

	/**
	 * Append or prepend a member of an array-property.
	 * If the named property does not exist, an array is created, or
	 * if it exists but is scalar, it is converted to an array.
	 *
	 * @param string $name Property name, with suitable namespace-differentiation
	 * @param mixed $val The data to be cached
	 * @param bool $append Whether to append or prepend $val. Default true.
	 */
	public static function add(string $name, $val, bool $append = true)
	{
		if (!self::$instance) {
			self::$instance = new self();
		}
		if (!isset(self::$instance->properties[$name])) {
			self::$instance->properties[$name] = [];
		} elseif (!is_array(self::$instance->properties[$name])) {
			self::$instance->properties[$name] = [self::$instance->properties[$name]];
		}
		if ($append) {
			self::$instance->properties[$name][] = $val;
		} else {
			array_unshift(self::$instance->properties[$name], $val);
		}
	}

	/**
	 * Directly cache a system-singleton object.
	 * For use (normally during request-initialization) when the object
	 * cannot be auto-loaded (perhaps due to circular dependencies), or
	 * to record a non-discoverable class-alias.
	 * Does nothing if $name is already cached.
	 *
	 * @param string $name Class name, without namespace
	 * @param object $obj  The instance to be cached
	 */
	public static function insert(string $name, object $obj)
	{
		if (!self::$instance) {
			self::$instance = new self();
		}
		$inst = self::$instance;
		if (!isset($inst->singles[$name])) {
			$inst->singles[$name] = $obj;
		}
	}
}
