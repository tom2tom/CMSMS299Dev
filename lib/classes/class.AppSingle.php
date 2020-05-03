<?php
/*
Singleton class for accumulating singleton instances of other classes.
Copyright (C) 2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
BUT WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

namespace CMSMS;

use RuntimeException;

/**
 * Singleton class that caches and supplies on-demand other 'system' singletons,
 * whose namespace may be one of: global | CMSMS | CMSMS\internal.
 * Those objects can be retrieved by CMSMS\AppSingle::Classname()
 * @since 2.9
 *
 * @final
 * @package CMS
 * @license GPL
 * @throws RuntimeException if the supplied classname is not recognised
 */
final class AppSingle
{
	/**
	 * @var object Singleton instance of this class
	 * @ignore
	 */
	protected static $instance = null;

	/**
	 * @var array Cached singletons of other classes
	 * Each member like classname => object
	 * No namespace presence in the classname
	 * @ignore
	 */
	private $singles = [];

	private function __construct() {}
	private function __clone() {}

	/**
	 * Retrieve a class singleton-object, after construction if necessary.
	 * Typically this requires existence of cms_autoloader().
	 * Some singletons e.g. the database connection (::Db() and the
	 * admin theme ::Theme()) can't be simply autoloaded, so must be
	 * set() by some external process before retrieval from here.
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
	 * Directly cache a singleton object.
	 * For use (normally during request-initialization) when a singleton
	 * cannot be autoloaded (perhaps circular dependencies, or recording a
	 * non-discoverable class-alias).
	 * Does nothing if $name is already cached.
	 *
	 * @param string $name Class name, without namespace
	 * @param object $obj  The instance to be cached
	 */
	public static function set(string $name, object $obj)
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
