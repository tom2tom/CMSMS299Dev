<?php
/*
Singleton class for dealing with one-instance-per-request properties.
Copyright (C) 2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

/**
 * Singleton class that records, for later supply on-demand, properties
 * that should not, or cannot properly, be 'static' for the duration of
 * the current request.
 *
 * Notably the main 'system' singleton objects. Those will be autoloaded
 * from namespaces: global | CMSMS | CMSMS\internal, or else must be
 * manually insert()'d by some external process before retrieval from here.
 * Those objects can be retrieved by CMSMS\AppSingle::Classname(), or
 * CMSMS\AppSingle::get(Classname).
 *
 * Store and retrieve other properties using CMSMS\AppSingle::{set|get}propkey
 *
 * @final
 * @since 2.99
 * @package CMS
 * @license GPL
 * @throws RuntimeException if the supplied classname is not recognised
 */
final class AppSingle
{
	/**
	 * @var object Preserves this object through the request
	 * TODO something else that's persistent e.g. a reference recorded somewhere
	 * @ignore
	 */
	protected static $instance = null;

	/**
	 * @var array Cached singleton-instances of other 'system' classes
	 * Each member like classname => object
	 * No namespace presence in the classname. It may be an alias.
	 * @ignore
	 */
	private $singles = [];

	/**
	 * @var array Cached properties other than system-singletons
	 * Each member like key => data
	 * Suitable namespace differentiation in the key
	 * @ignore
	 */
	private $properties = [];

	private function __construct() {}
	private function __clone() {}

	/**
	 * Retrieve a class singleton-object, after construction if necessary.
	 * Typically this requires existence of cms_autoloader().
	 * Some singletons e.g. the database connection (::Db() and the
	 * admin theme ::Theme()) can't be simply autoloaded, so must be
	 * insert()'d by some external process before retrieval from here.
	 *
	 * @param string $name Class name, without namespace. No aliases.
	 * @param array $args  Object constructor-argument(s) (rare for CMSMS singletons)
	 * @return object | not at all
	 * @throws RuntimeException if the class is not found
	 */
	public static function __callStatic($name, $args)
	{
		if (!self::$instance) {
			self::$instance = new self();
		}
		//TODO also support shortform static analogs for __get(K) and __set(K,Y)
		$cache =& self::$instance->singles;
		if (!isset($cache[$name])) {
			$obj = null;
			foreach ([
				'CMSMS\\'.$name, //most likely namespace
				$name,
				'CMSMS\\internal\\'.$name, //least likely (mainly Smarty)
			] as $i => $one) {
				if (class_exists($one, false)) {
					try {
						$obj = new $one(...$args);
						break;
					} catch (Throwable $t) {
						throw new RuntimeException($t->GetMessage().': '.self::class.'::'.$name.'()');
					}
				} else {
					// there's no trap for class-not-found errors i.e. must workaround namespace here
					if ($i != 1) { // global- and CMSMS-namespace class files are in the same place
						// this could autoload a class-file even though the loaded class has wrong namespace
						cms_autoloader($one);
					}
					if (class_exists($one, false)) {
						try {
							$obj = new $one(...$args);
							break;
						} catch (Throwable $t) {
							throw new RuntimeException($t->GetMessage().': '.self::class.'::'.$name.'()');
						}
					}
				}
			}
			if ($obj !== null) {
				$cache[$name] = $obj;
			} else {
				throw new RuntimeException('Unrecognized class requested '.self::class.'::'.$name.'()');
			}
		}
		$obj = $cache[$name];
		unset($cache);
		return $obj;
	}

	/**
	 * Cache a property
	 *
	 * @param string $name Property name, with suitable namespace-differentiation
	 * @param object $obj  The data to be cached
	 */
	public function __set($name, $value)
	{
		if (!self::$instance) {
			self::$instance = new self();
		}
		$cache = self::$instance->properties;
		$cache[$name] = $value;
	}

	/**
	 * Retrieve a cached property
	 *
	 * @param string $name Property name, with suitable namespace-differentiation
	 * @return mixed Cached property value | null if not found
	 */
	public function __get($name)
	{
		if (!self::$instance) {
			return null;
		}
		$cache = self::$instance->properties;
		if (isset($cache[$name])) { return $cache[$name]; }
		$cache = self::$instance->singles;
		if (isset($cache[$name])) { return $cache[$name]; }
		return null;
	}

	/**
	 * Static analog of magic method __set()
	 */
	public static function set(string $key, $val)
	{
		if (!self::$instance) {
			self::$instance = new self();
		}
		self::$instance->$key = $val;
	}

	/**
	 * Static analog of magic method __get()
	 */
	public static function get(string $key)
	{
		if (!self::$instance) {
			self::$instance = new self();
		}
		return self::$instance->$key;
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
		$cache =& self::$instance->singles;
		if (!isset($cache[$name])) {
			$cache[$name] = $obj;
		}
	}
}
