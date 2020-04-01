<?php
#Generic cache-driver wrapper class
#Copyright (C) 2010-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace CMSMS;

use cms_siteprefs;
use CmsException;
use CMSMS\AppSingle;
use CMSMS\AppState;
use CMSMS\CacheDriver;
use DeprecationNotice;
use Exception;
use Throwable;
use const CMS_DEPREC;
use function debug_buffer;

/**
 * Wrapper class providing caching capability.
 * NOTE After instantiation and before use, connect() or set_driver() must be called
 *
 * Site-preferences which affect cache operation (unless overridden by supplied params):
 * 'cache_driver' string 'predis','memcached','apcu','yac','file' or 'auto'
 * 'cache_autocleaning' bool
 * 'cache_lifetime' int seconds (may be 0)
 * 'cache_file_blocking' bool
 * 'cache_file_locking' bool
 *
 * @see also CMSMS\SysDataCache class, which caches system data in memory, and
 *  uses this class for inter-request continuity
 * @final
 * @package CMS
 * @license GPL
*/
//TODO admin developer-mode UI for related site-preferences

final class SystemCache
{
	/* *
	 * @ignore
	 */
//  private static $_instance = null;

	/**
	 * @ignore
	 */
	private $_driver = null;

	/**
	 * @ignore
	 */
	public function __construct()
	{
		$this->connect();
	}

	/**
	 * @ignore
	 */
	private function __clone() {}

	/**
	 * Get the singleton general-purpose cache object.
	 * @deprecated since 2.9 instead use CMSMS\AppSingle::SystemCache()
	 * @return self | not at all
	 * @throws CmsException if driver-connection fails
	 */
	public static function get_instance() : self
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method','CMSMS\AppSingle::SystemCache()'));
		return AppSingle::SystemCache();
	}

	/**
	 * Connect to and record a driver class
	 *
	 * @since 2.3
	 * @param $opts Optional connection-parameters. Default []
	 * @return CacheDriver object | not at all
	 * @throws CmsException
	 */
	public function connect(array $opts = []) : CacheDriver
	{

		if( $this->_driver instanceof CacheDriver ) {
			return $this->_driver; //just one connection per request
		}

		// ordered by preference during an auto-selection
		$drivers = [
		 'predis' => 'CMSMS\\CachePredis',
		 'apcu' => 'CMSMS\\CacheApcu',
		 'memcached' => 'CMSMS\\CacheMemcached', //slowest !?
		 'yac' => 'CMSMS\\CacheYac',  // bit intersection risky
		 'file' => 'CMSMS\\CacheFile',
		];

		$parms = $opts;
		// preferences cache maybe N/A now, so get pref data directly
		$driver_name = $opts['driver'] ?? cms_siteprefs::getraw('cache_driver', 'auto');
		unset($parms['driver']);
		$ttl = $opts['lifetime'] ?? cms_siteprefs::getraw('cache_lifetime', 3600);
		$ttl = (int)$ttl;
		if( $ttl < 1 ) $ttl = 0;
		if( $ttl < 1 ) {
			$parms['lifetime'] = null; // unlimited
			$parms['auto_cleaning'] = false;
		}
		else {
			$parms['lifetime'] = $ttl;
			if( !isset($parms['auto_cleaning']) ) {
				$parms['auto_cleaning'] = cms_siteprefs::getraw('cache_autocleaning', true);
			}
		}

		if( AppState::test_state(AppState::STATE_INSTALL) ) {
			$parms['lifetime'] = 120;
			$parms['auto_cleaning'] = true;
		}

		$host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
		$tmp = cms_siteprefs::getraw(['cache_file_blocking', 'cache_file_locking'],[0, 1]);
		$settings = [
			 'predis' => [
			 'host' => $host,
			 'port' => 6379,
			 'read_write_timeout' => 10.0, // connection timeout, not data lifetime
			 'password' => '',
			 'database' => 0,
			 ],
			 'memcached' => [
			 'host' => $host,
			 'port' => 11211,
			 ],
			 'file' => [
			 'blocking' => reset($tmp),
			 'locking' => end($tmp),
			 ],
		];

		$type = strtolower($driver_name);
		switch( $type ) {
			case 'predis':
			case 'apcu':
			case 'memcached':
			case 'yac':
			case 'file':
				$classname = $drivers[$type];
				if( isset($settings[$type]) ) $parms += $settings[$type];
				try {
					$this->_driver = new $classname($parms);
					debug_buffer('Cache initiated, using driver: ' . $type);
					return $this->_driver;
				} catch (Exception $e) {}
				break;
			case 'auto':
//				debug_buffer('Start cache-driver polling');
				foreach( $drivers as $type => $classname ) {
				$tmp = $parms;
				if( isset($settings[$type]) ) $tmp += $settings[$type];
				try {
					$this->_driver = new $classname($tmp);
					debug_buffer('Cache initiated, using driver: ' . $type);
					return $this->_driver;
				}
				catch (Throwable $t) {}
				}
				break;
			default:
				break;
		}
		$this->_driver = null;
		throw new CmsException('Cache ('.$driver_name.') setup failed');
	}

	/**
	 * Set the driver to use for caching.
	 * This allows use of a custom driver other than supported by connect()
	 *
	 * @see SystemCache::connect()
	 * @param CacheDriver $driver
	 */
	public function set_driver(CacheDriver $driver)
	{
		$this->_driver = $driver;
	}

	/**
	 * Get the driver used for caching
	 * This may be used e.g. to adjust the driver parameters
	 *
	 * @return mixed CacheDriver object | null
	 */
	public function get_driver()
	{
		return $this->_driver;
	}

	/**
	 * Clear some/all of the cache
	 * If the group is not specified, the current set group will be used.
	 * If the group is empty, all cached values will be cleared.
	 * Use with caution, especially in shared public caches like Memcached.
	 *
	 * @see SystemCache::set_group()
	 * @param string $group
	 * @return bool
	 */
	public function clear(string $group = '') : bool
	{
//		if( $this->can_cache() ) {
		if( $this->_driver instanceof CacheDriver ) {
//		if( is_object($this->_driver) ) {
			return $this->_driver->clear($group);
		}
		return FALSE;
	}

	/**
	 * Get a cached value
	 *
	 * @see SystemCache::set_group()
	 * @param string $key The primary key for the cached value
	 * @param string $group An optional cache group name.
	 * @return mixed null if there is no cache-data for $key
	 */
	public function get(string $key, string $group = '')
	{
//		if( $this->can_cache() ) {
		if( $this->_driver instanceof CacheDriver ) {
//		if( is_object($this->_driver) ) {
			return $this->_driver->get($key, $group);
		}
		return NULL;
	}

	/**
	 * Get all cached values in the specified group
	 *
	 * @since 2.3
	 * @param string $group An optional cache group name.
	 * @return array
	 */
	public function getall(string $group = '') : array
	{
//		if( $this->can_cache() ) {
		if( $this->_driver instanceof CacheDriver ) {
//		if( is_object($this->_driver) ) {
			return $this->_driver->get_all($group);
		}
		return NULL;
	}

	/**
	 * Get all cached keys in the specified group
	 *
	 * @since 2.3
	 * @param string $group An optional cache group name.
	 * @return array
	 */
	public function getindex(string $group = '') : array
	{
//		if( $this->can_cache() ) {
		if( $this->_driver instanceof CacheDriver ) {
//		if( is_object($this->_driver) ) {
			return $this->_driver->get_index($group);
		}
		return NULL;
	}

	/**
	 * Test if a key/value pair is present in the cache
	 *
	 * @see SystemCache::set_group()
	 * @param string $key The primary key for the cached value
	 * @param string $group An optional cache group name.
	 * @return bool
	 */
	public function has(string $key, string $group = '') : bool
	{
//		if( $this->can_cache() ) {
		if( $this->_driver instanceof CacheDriver ) {
//		if( is_object($this->_driver) ) {
			return $this->_driver->has($key, $group);
		}
		return FALSE;
	}

	/**
	* @deprecated since 2.9 instead use interface-compatible has()
	* @param string $key The primary key for the cached value
	* @param string $group An optional cache group name.
	 */
	public function exists(string $key, string $group = '') : bool
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method','has()'));
		return $this->has($key, $group);
	}

	/**
	 * Erase a cached value
	 *
	 * @see SystemCache::set_group()
	 * @param string $key The primary key for the cached value
	 * @param string $group An optional cache group name.
	 * @return bool
	 */
	public function delete(string $key, string $group = '') : bool
	{
//		if( $this->can_cache() ) {
		if( $this->_driver instanceof CacheDriver ) {
//		if( is_object($this->_driver) ) {
			return $this->_driver->delete($key, $group);
		}
		return FALSE;
	}

	/**
	* @deprecated since 2.9 instead use interface-compatible delete()
	* @param string $key The primary key for the cached value
	* @param string $group An optional cache group name.
	 */
	public function erase(string $key, string $group = '') : bool
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method','delete()'));
		return $this->delete($key, $group);
	}

	/**
	 * Add or replace a value in the cache
	 * NOTE that to ensure detectability, a $value === null will be
	 * converted to 0, and in some cache-drivers, a $value === false
	 * will be converted to 0
	 *
	 * @see SystemCache::set_group()
	 * @param string $key The primary key for the cached value
	 * @param mixed  $value the value to save
	 * @param string $group An optional cache group name.
	 * @return bool
	 */
	public function set(string $key, $value, string $group = '') : bool
	{
//		if( $this->can_cache() ) {
		if( $this->_driver instanceof CacheDriver ) {
//		if( is_object($this->_driver) ) {
			if ($value === null ) { $value = 0; }
			return $this->_driver->set($key, $value, $group);
		}
		return FALSE;
	}

	/* *
	 * Set/replace the contents of an entire cache-group
	 * @since 2.3
	 *
	 * @param array $values assoc. array each member like Kkey=>$val
	 * @param string $group An optional cache group name.
	 * @return bool
	 */
/*public function set_all(array $values, string $group = '') : bool
	{
TODO
	}
*/
	/**
	 * Set the cache group
	 * Specifies the scope for all methods in this cache
	 *
	 * @param string $group
	 * @return bool
	 */
	public function set_group(string $group) : bool
	{
		if( $this->_driver instanceof CacheDriver ) {
//		if( is_object($this->_driver) ) {
			return $this->_driver->set_group($group);
		}
		return FALSE;
	}

	/* *
	 * Test if caching is possible
	 * Caching is not possible if there is no driver(, CHECKME or during an install-request?).
	 *
	 * @return bool
	 */
/*  public function can_cache() : bool
	{
		if( !is_object($this->_driver) ) return FALSE;
		return !AppState::test_state(CMSMS\AppState::STATE_INSTALL);
		return $this->_driver instanceof CMSMS\CacheDriver;
	}
*/
}

\class_alias(SystemCache::class, 'cms_cache_handler', false);
