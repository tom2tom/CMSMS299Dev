<?php
/*
Class for managing variables used instead of conventional static variables.
Copyright (C) 2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS;

use CMSMS\DeprecationNotice;
use const CMS_DEPREC;

/**
 * Singleton class for managing intra-request variables used instead of standard
 * PHP static variables/properties, which can be a problem in some contexts
 * e.g. where 'binary safe' operation is needed
 * @since 2.99
 * @final
 * @package CMS
 * @license GPL
 */
final class StaticProperties
{
	//TODO consider other storages e.g. shmop if available
	/* *
	 * @ignore
	 */
//	private static $_instance = null;
	/**
	 * @ignore
	 */
	public function __construct()
	{
		register_shutdown_function([$this, 'cleanup']);
	}
	/* *
	 * @ignore
	 */
//	private function __clone() {}
	/**
	 * @ignore
	 */
	public function __get(string $key)
	{
		return $this->get_simple($key);
	}
	/**
	 * @ignore
	 */
	public function __set(string $key, $val)
	{
		$this->set_simple($key, $val);
	}
	/**
	 * Retrieve the single instance of this class
	 * @deprecated since 2.99 instead use CMSMS\AppSingle::StaticProperties()
	 */
	public static function get_instance() : self
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method','CMSMS\AppSingle::StaticProperties()'));
		return AppSingle::StaticProperties();
	}
	/**
	 * End-of-request cache cleaner
	 * @ignore
	 */
	public static function cleanup()
	{
		$grp = self::$_instance->cache_group();
		SystemCache::get_instance()->clear($grp);
	}
	/**
	 * Generate cache key
	 * @param string $longprop
	 * @return string
	 */
	private function cache_key(string $longprop) : string
	{
		return hash('adler32', $longprop);
	}
	/**
	 * Generate cache group
	 * @return string
	 */
	private function cache_group() : string
	{
		return hash('adler32', session_id().$_SERVER['QUERY_STRING']);
	}
	/**
	 * Get cached value
	 * @param string $longprop
	 * @return mixed
	 */
	public function get_simple(string $longprop)
	{
		$sp = trim($longprop, ' \\');
		$key = $this->cache_key($sp);
		$grp = $this->cache_group();
		return SystemCache::get_instance()->get($key, $grp);
	}
	/**
	 * Get cached value
	 * @param string $prop
	 * @param string $space
	 * @return mixed
	 */
	public function get(string $prop, string $space = '')
	{
		$s = ($space !== '') ? trim($space, ' \\').'\\' : '';
		$sp = $s.trim($prop);
		return $this->get_simple($sp);
	}
	/**
	 * Store value in cache
	 * @param string $longprop
	 * @param mixed $val
	 * @return string
	 */
	public function set_simple(string $longprop, $val) : string
	{
		$sp = trim($longprop, ' \\');
		$key = $this->cache_key($sp);
		$grp = $this->cache_group();
		SystemCache::get_instance()->set($key, $val, $grp);
		return $key;
	}
	/**
	 * Store value in cache
	 * @param string $prop
	 * @param mixed $val
	 * @param string $space
	 * @return string
	 */
	public function set(string $prop, $val, string $space = '') : string
	{
		$s = ($space !== '') ? trim($space, ' \\').'\\' : '';
		$sp = $s.trim($prop);
		return $this->set_simple($sp, $val);
	}
}
