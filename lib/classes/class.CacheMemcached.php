<?php
/*
A class to work with data cached using the PHP Memcached extension.
Copyright (C) 2019-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use CMSMS\CacheDriver;
use Exception;
use Memcached;

/**
 * A driver to cache data using PHP's Memcached extension
 *
 * Supports settable cache lifetime, automatic cleaning.
 *
 * @package CMS
 * @license GPL
 * @since 3.0
 */
class CacheMemcached extends CacheDriver
{
	/**
	 * @ignore
	 */
	private $instance;

	/**
	 * Constructor
	 *
	 * @param array $params
	 * Associative array of some/all options as follows:
	 *  lifetime  => seconds (default 3600, min 600)
	 *  group => string (default 'default') TODO migrate to 'space'
	 *  myspace => string cache differentiator (default cms_)
	 *  host => string
	 *  port => int
	 */
	#[\ReturnTypeWillChange]
	public function __construct(array $params)
	{
		if ($this->use_driver()) {
			if ($this->connectServer($params)) {
				if ($params) {
					// TODO migrate 'group' to 'space'
					$_keys = ['lifetime', 'group', 'myspace'];
					foreach ($params as $key => $value) {
						if (in_array($key,$_keys)) {
							$tmp = '_'.$key;
							$this->$tmp = $value;
						}
					}
				}
				$this->_lifetime = max($this->_lifetime, 600);
				return;
			}
		}
		throw new Exception('no Memcached storage');
	}

	/**
	 * @ignore
	 */
	private function use_driver()
	{
		return class_exists('Memcached');
	}

	/**
	 * @ignore
	 */
	private function connectServer(array $params)
	{
		$params = array_merge([
		 'host' => '127.0.0.1',
		 'port' => 11211,
		], $params);
		$host = $params['host'];
		$port = (int)$params['port'];

		$this->instance = new Memcached();

		$servers = $this->instance->getServerList();
		if (is_array($servers)) {
			foreach ($servers as $server) {
				if ($server['host'] == $host && $server['port'] == $port) {
					register_shutdown_function([$this, 'cachequit']);
					return true;
				}
			}
		}

		try {
			if ($this->instance->addServer($host, $port)) { //may throw Exception
				register_shutdown_function([$this, 'cachequit']);
				return true;
			}
		} catch (Exception $e) {}
		unset($this->instance);
		return false;
	}

	public function cachequit()
	{
		$this->instance->quit();
	}

	public function get_index(string $space = '')
	{
		if (!$space) { $space = $this->_space; }
		$prefix = $this->get_cacheprefix(static::class, $space);
		if ($prefix === '') { return []; }

		$out = [];
		$info = $this->instance->getAllKeys(); //NOT RELIABLE
		if ($info) {
			$len = strlen($prefix);
			foreach ($info as $key) {
				if (strncmp($key, $prefix, $len) == 0) {
					$res = $this->instance->get($key);
					if ($res || $this->instance->getResultCode() == Memcached::RES_SUCCESS) {
						$out[] = substr($key,$len);
					}
				}
			}
			sort($out);
		}
		return $out;
	}

	public function get_all(string $space = '')
	{
		if (!$space) { $space = $this->_space; }
		$prefix = $this->get_cacheprefix(static::class, $space);
		if ($prefix === '') { return []; }

		$out = [];
		$info = $this->instance->getAllKeys(); //NOT RELIABLE
		if ($info) {
			$len = strlen($prefix);
			foreach ($info as $key) {
				if (strncmp($key, $prefix, $len) == 0) {
					$res = $this->instance->get($key);
					if ($res || $this->instance->getResultCode() == Memcached::RES_SUCCESS) {
						$out[substr($key,$len)] = $res;
					}
				}
			}
			// TODO if all values are scalar: asort($out);
		}
		return $out;
	}

	public function get($key, string $space = '')
	{
		if (!$space) { $space = $this->_space; }
		$key = $this->get_cachekey($key, static::class, $space);
		$res = $this->instance->get($key);
		if (!$res && ($dbg = $this->instance->getResultCode()) != Memcached::RES_SUCCESS) {
			return null;
		}
		return $res;
	}

	public function has($key, string $space = '')
	{
		if (!$space) { $space = $this->_space; }
		$key = $this->get_cachekey($key, static::class, $space);
		return ($this->instance->get($key) != false ||
				$this->instance->getResultCode() == Memcached::RES_SUCCESS);
	}

	public function set($key, $value, string $space = '')
	{
		if (!$space) { $space = $this->_space; }
		$key = $this->get_cachekey($key, static::class, $space);
		return $this->_write_cache($key, $value);
	}

	public function set_timed($key, $value, int $ttl = 0, string $space = '')
	{
		if (!$space) { $space = $this->_space; }
		$key = $this->get_cachekey($key, static::class, $space);
		return $this->_write_cache($key, $value, $ttl);
	}

	public function delete($key, string $space = '')
	{
		if (!$space) { $space = $this->_space; }
		$key = $this->get_cachekey($key, static::class, $space);
		return $this->instance->delete($key);
	}

	public function clear(string $space = '') : int
	{
		if (!$space) { $space = $this->_space; }
		elseif ($space == '*' || $space == '__ALL__') { $space = ''; }
		return $this->_clean($space);
	}

	/**
	 * @ignore
	 */
	private function _write_cache($key, $value, $ttl = null) : bool
	{
		if ($ttl === null) {
			$ttl = ($this->_auto_cleaning) ? 0 : $this->_lifetime;
		}
		if ($ttl > 0) {
			$expire = time() + $ttl;
			return $this->instance->set($key, $value, $expire);
		} else {
			return $this->instance->set($key, $value);
		}
	}

	/**
	 * @ignore
	 * @return int No of items deleted (i.e. 0 might indicate success)
	 */
	private function _clean(string $space, bool $aged = true) : int
	{
		$prefix = ($space) ?
			$this->get_cacheprefix(static::class, $space):
			$this->_globlspace;
		if ($prefix === '') { return 0; }//no global interrogation in shared key-space

		$nremoved = 0;
		$info = $this->instance->getAllKeys(); //NOT RELIABLE
		if ($info) {
			$len = strlen($prefix);
			if ($aged) {
				$ttl = ($this->_auto_cleaning) ? 0 : $this->_lifetime;
				$limit = time() - $ttl;
			}

			foreach ($info as $key) {
				if (strncmp($key, $prefix, $len) == 0) {
					if ($aged) {
						//TODO ageing is bad
						if (1 && $this->instance->delete($key)) {
							++$nremoved;
						}
					} elseif ($this->instance->delete($key)) {
						++$nremoved;
					}
				}
			}
		}
		return $nremoved;
	}
} // class
