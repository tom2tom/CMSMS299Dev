<?php
/*
Singleton wrapper-class for engaging with a system/global cache
Copyright (C) 2010-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS;

use CMSMS\AppParams;
use CMSMS\AppState;
use CMSMS\CacheDriver;
use CMSMS\DeprecationNotice;
use CMSMS\Lone;
use Exception;
use Throwable;
use const CMS_DEPREC;
use function debug_buffer;

/**
 * Singleton wrapper-class for engaging with a system/global cache
 *
 * Site-preferences which affect cache operation (unless overridden by supplied params):
 * 'cache_driver' string 'predis','memcached','apcu','yac','file' or 'auto'
 * 'cache_autocleaning' bool
 * 'cache_lifetime' int seconds (may be 0 for unlimited)
 * 'cache_file_blocking' bool for a file-cache
 * 'cache_file_locking' bool ditto
 *
 * @see also CMSMS\LoadedData class, which caches system data in memory,
 *  and uses this class for inter-request continuity
 * @final
 * @package CMS
 * @license GPL
 */
final class SystemCache
{
	/* *
	 * @ignore
	 */
//	private static $_instance = null;

	/**
	 * @ignore
	 */
	private static $driver;

	/**
	 * Constructor
	 *
	 * @param $params Optional connection-parameters. Default []
	 * NOTE: if this instance is created as a Lone
	 * (normally the case), then no $params will be supplied, and
	 * any parameter-tailoring will need to happen via
	 * get,tweak,set_driver() calls.
	 */
	public function __construct(array $params = [])
	{
		$this->connect($params);
	}

	/**
	 * @ignore
	 */
	private function __clone(): void {}

	/**
	 * Get the singleton general-purpose cache object.
	 * @deprecated since 3.0 Instead use CMSMS\Lone::get('SystemCache')
	 *
	 * @return self | not at all
	 * @throws Exception if driver-connection fails
	 */
	public static function get_instance(): self
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'CMSMS\Lone::get(\'SystemCache\')'));
		return Lone::get('SystemCache');
	}

	/**
	 * Connect to and record a driver class
	 * @since 3.0
	 *
	 * @param $params Optional connection-parameters. Default []
	 * @throws Exception
	 */
	public function connect(array $params = [])
	{
		if( self::$driver instanceof CacheDriver ) {
			return; // only one connection per request
		}

		// these are ordered by preference during an auto-selection
		$drivers = [
			'predis' => 'CMSMS\CachePredis',
			'apcu' => 'CMSMS\CacheApcu',
			'memcached' => 'CMSMS\CacheMemcached', // slowest !?
			'yac' => 'CMSMS\CacheYac',  // bit intersection risky
			'file' => 'CMSMS\CacheFile',
		];

		$parms = $params;
		// preferences cache maybe N/A now, so get pref data directly
		$driver_name = $params['driver'] ?? AppParams::getraw('cache_driver', 'auto');
		unset($parms['driver']);
		$ttl = $params['lifetime'] ?? AppParams::getraw('cache_lifetime', 3600);
		$ttl = (int)$ttl;
		if( $ttl < 1 ) $ttl = 0;
		if( $ttl < 1 ) {
			$parms['lifetime'] = null; // unlimited
			$parms['auto_cleaning'] = false;
		}
		else {
			$parms['lifetime'] = $ttl;
			if( !isset($parms['auto_cleaning']) ) {
				$parms['auto_cleaning'] = AppParams::getraw('cache_autocleaning', true);
			}
		}

		if( AppState::test(AppState::INSTALL) ) {
			$parms['lifetime'] = 120;
			$parms['auto_cleaning'] = true;
		}

		//NOTE: never trust $_SERVER['HTTP_*'] variables which contain IP address
		//? maybe sanitize and/or whitelist-check
		$host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
		$tmp = AppParams::getraw(['cache_file_blocking', 'cache_file_locking'],[0, 1]);
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
					self::$driver = new $classname($parms);
					debug_buffer('Cache initiated, using specified driver: ' . $type);
					return self::$driver;
				} catch (Exception $e) {}
				break;
			case 'auto':
//				debug_buffer('Start cache-driver polling');
				foreach( $drivers as $type => $classname ) {
				$tmp = $parms;
				if( isset($settings[$type]) ) $tmp += $settings[$type];
				try {
					self::$driver = new $classname($tmp);
					debug_buffer('Cache initiated, using detected driver: ' . $type);
					return self::$driver;
				}
				catch (Throwable $t) {}
				}
				break;
			default:
				break;
		}
		self::$driver = null;
		throw new Exception('Cache ('.$driver_name.') setup failed');
	}

	/**
	 * Set the driver to use for caching.
	 * This allows deployment of a driver other than the ones supported by connect()
	 * @see SystemCache::connect()
	 *
	 * @param CacheDriver $driver
	 */
	public function set_driver(CacheDriver $driver)
	{
		self::$driver = $driver;
	}

	/**
	 * Get the driver used for caching
	 * This may be used e.g. to adjust the driver parameters
	 *
	 * @return mixed CacheDriver object | null
	 */
	public function get_driver()
	{
		return self::$driver;
	}

	/**
	 * Remove all cached values from a keys-space, or from the whole cache
	 *
	 * @param string $space Optional keys-space name, default ''.
	 *  If not specified, the default keys-space will be used.
	 *  If $space is '*' or '__ALL__', the whole cache (i.e. all spaces) will be cleared.
	 * @return mixed false | int no. of values deleted (might be 0)
	 */
	public function clear(string $space = '')
	{
		if( self::$driver instanceof CacheDriver ) {
			return self::$driver->clear($space);
		}
		return FALSE;
	}

	/**
	 * Get a cached value
	 * @see SystemCache::set_space()
	 *
	 * @param mixed $key The key/identifier of the cached value
	 * @param string $space Optional keys-space name, default ''.
	 *  If not specified, the default keys-space will be used.
	 * @return mixed cached value | null if there is no cache-data for $key
	 */
	public function get($key, string $space = '')
	{
		if( self::$driver instanceof CacheDriver ) {
			return self::$driver->get($key, $space);
		}
		return NULL;
	}

	/**
	 * Get all cached values in a keys-space
	 * @since 3.0
	 *
	 * @param string $space Optional keys-space name, default ''.
	 *  If not specified, the default keys-space will be used.
	 * @return array maybe empty
	 */
	public function getall(string $space = '')
	{
		if( self::$driver instanceof CacheDriver ) {
			return self::$driver->get_all($space);
		}
		return [];
	}

	/**
	 * Get all value-keys in a keys-space
	 * @since 3.0
	 *
	 * @param string $space Optional keys-space name, default ''.
	 *  If not specified, the default keys-space will be used.
	 * @return array maybe empty
	 */
	public function getindex(string $space = '')
	{
		if( self::$driver instanceof CacheDriver ) {
			return self::$driver->get_index($space);
		}
		return [];
	}

	/**
	 * Report whether a value-key is present in the cache
	 * @see SystemCache::set_space()
	 *
	 * @param mixed $key The key/identifier of the cached value
	 * @param string $space Optional keys-space name, default ''.
	 *  If not specified, the default keys-space will be used.
	 * @return bool
	 */
	public function has($key, string $space = ''): bool
	{
		if( self::$driver instanceof CacheDriver ) {
			return self::$driver->has($key, $space);
		}
		return FALSE;
	}

	/**
	 * Report whether a value-key is present in the cache
	 * @deprecated since 3.0 Instead use interface-compatible SystemCache::has()
	 */
	public function exists($key, string $space = ''): bool
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'CMSMS\SystemCache::has()'));
		return $this->has($key, $space);
	}

	/**
	 * Remove a value from the cache
	 * @see SystemCache::set_space()
	 *
	 * @param mixed $key The key/identifier of the cached value
	 * @param string $space Optional keys-space name, default ''.
	 *  If not specified, the default keys-space will be used.
	 * @return bool
	 */
	public function delete($key, string $space = ''): bool
	{
		if( self::$driver instanceof CacheDriver ) {
			return self::$driver->delete($key, $space);
		}
		return FALSE;
	}

	/**
	 * Remove a value from the cache
	 * @deprecated since 3.0 Instead use interface-compatible Systemcache::delete()
	 */
	public function erase($key, string $space = ''): bool
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'CMSMS\Systemcache::delete()'));
		return $this->delete($key, $space);
	}

	/**
	 * Add or replace a value in the cache
	 * NOTE that to ensure detectability, a null $value will be
	 * converted to 0, and in some cache-drivers, a $value === false
	 * will be converted to 0
	 * @see SystemCache::set_space()
	 *
	 * @param mixed $key The key/identifier of the cached value
	 * @param mixed  $value the value to save
	 * @param string $space Optional keys-space name, default ''.
	 *  If not specified, the default keys-space will be used.
	 * @return bool
	 */
	public function set($key, $value, string $space = ''): bool
	{
		if( self::$driver instanceof CacheDriver ) {
			if ($value === null ) { $value = 0; }
			return self::$driver->set($key, $value, $space);
		}
		return FALSE;
	}

	/**
	 * Add or replace a value in the cache, giving the value a custom lifetime
	 * NOTE some cache-drivers (e.g. file) might not support value-specific lifetimes
	 * @since 3.0
	 * @see SystemCache::set(), SystemCache::set_space()
	 *
	 * @param mixed $key
	 * @param mixed $value
	 * $param int $ttl Optional value-lifetime (seconds), default 0. Hence unlimited.
	 * @param string $space Optional keys-space name, default ''.
	 *  If not specified, the default keys-space will be used.
	 */
	public function set_timed($key, $value, int $ttl, string $space = ''): bool
	{
		if( self::$driver instanceof CacheDriver ) {
			if ($value === null ) { $value = 0; }
			$ttl = max(0, (int)$ttl);
			return self::$driver->set_timed($key, $value, $ttl, $space);
		}
		return FALSE;
	}

	/* *
	 * Set/replace the contents of an entire cache-space
	 * @since 3.0
	 *
	 * @param array $values assoc. array each member like $key=>$val
	 * @param string $space Optional keys-space name, default ''.
	 *  If not specified, the default keys-space will be used.
	 * @return bool
	 */
/*	public function set_all(array $values, string $space = ''): bool
	{
	TODO
	}
*/
	/**
	 * Set the default keys-space (a.k.a. group) for all class-methods
	 * NOTE: cache users must self-manage the uniqueness of space names.
	 * @since 3.0
	 *
	 * @param string $space keys-space name
	 * @return bool
	 */
	public function set_space(string $space): bool
	{
		if( self::$driver instanceof CacheDriver ) {
			return self::$driver->set_space($space);
		}
		return FALSE;
	}

	/**
	 * Set the default keys-space (a.k.a. group) for all class-methods
	 * @deprecated since 3.0 Instead use set_space();
	 */
	public function set_group(string $space): bool
	{
		return $this->set_space($space);
	}

	/**
	 * Get the default keys-space (a.k.a. group) for all class-methods
	 * @since 3.0
	 *
	 * @return string, possibly 'UNKNOWN'
	 */
	public function get_space(): string
	{
		if( self::$driver instanceof CacheDriver ) {
			return self::$driver->get_space();
		}
		return 'UNKNOWN';
	}

	/**
	 * Convenience function to generate a keys-space name.
	 * Cache users may specify space-names as they see fit, so using
	 * this is optional.
	 * @since 3.0
	 *
	 * @param string $salt If empty, the value of __CLASS__ will be used
	 * @return string 16-hexits
	 */
	public function generate_space(string $salt = ''): string
	{
		if ($salt === '') { $salt = __CLASS__; }
		return hash('adler32', $salt.__FILE__).hash('adler32', $salt);
	}
}
