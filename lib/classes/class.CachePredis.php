<?php
/*
A class to work with data cached using the PHP Predis (aka phpredis) extension
https://github.com/phpredis/phpredis
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
use Redis;
use function startswith;

/**
 * A driver to cache data using the PHP Predis (aka phpredis) extension
 *
 * Supports settable cache lifetime, automatic cleaning.
 *
 * @package CMS
 * @license GPL
 * @since 3.0
 */
class CachePredis extends CacheDriver
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
	 *  port  => int
	 *  read_write_timeout => float
	 *  password => string
	 *  database => int
	 */
	public function __construct(array $params)
	{
		if ($this->use_driver()) {
			if ($this->connectServer($params)) {
				parent::__construct($params);
				$this->_lifetime = max($this->_lifetime, 600);
				return;
			}
		}
		throw new Exception('no Predis storage');
	}

	/**
	 * @ignore
	 */
	private function use_driver()
	{
		return class_exists('Redis');
	}

	/**
	 * @ignore
	 * $params[] may include
	 *  'host' => string
	 *  'port'  => int
	 *  'password' => string
	 *  'database' => int
	 */
	private function connectServer(array $params)
	{
		$params = array_merge([
		 'host' => '127.0.0.1',
		 'port' => 6379,
		 'read_write_timeout' => 10.0,
		 'password' => '',
		 'database' => 0,
		], $params);

		$this->instance = new Redis();
		try {
			//trap any connection-failure warning
			$res = @$this->instance->connect($params['host'], (int)$params['port'], (float)$params['read_write_timeout']);
		} catch (Exception $e) {
			unset($this->instance);
			return false;
		}
		if (!$res) {
			unset($this->instance);
			return false;
		} elseif ($params['password'] && !$this->instance->auth($params['password'])) {
			$this->instance->close();
			unset($this->instance);
			return false;
		}
		if ($params['auto_cleaning']) {
//TODO
			if ($params['lifetime']) {
			}
		}

		register_shutdown_function([$this, 'cachequit']);
		if ($params['database']) {
			return $this->instance->select((int)$params['database']);
		}
		return true;
	}

	public function cachequit()
	{
		$this->instance->close();
	}

	public function get_index(string $space = '')
	{
		if (!$space) { $space = $this->_space; }
		$prefix = $this->get_cacheprefix(static::class, $space);
		if ($prefix === '') { return []; } //no global interrogation in shared key-space
		$len = strlen($prefix);

		$out = [];
		$keys = $this->instance->keys($prefix.'*');
		foreach ($keys as $key) {
			$out[] = substr($key,$len);
		}
		sort($out);
		return $out;
	}

	public function get_all(string $space = '')
	{
		if (!$space) { $space = $this->_space; }
		$prefix = $this->get_cacheprefix(static::class, $space);
		if ($prefix === '') { return []; }//no global interrogation in shared key-space
		$len = strlen($prefix);

		$out = [];
		$keys = $this->instance->keys($prefix.'*');
		foreach ($keys as $rawkey) {
			$key = substr($rawkey,$len);
			$out[$key] = $this->_read_cache($rawkey);
		}
		// TODO if all values are scalar: asort($out);
		return $out;
	}

	public function get($key, string $space = '')
	{
		if (!$space) { $space = $this->_space; }
		$key = $this->get_cachekey($key, static::class, $space);
		return $this->_read_cache($key);
	}

	public function has($key, string $space = '')
	{
		if (!$space) { $space = $this->_space; }
		$key = $this->get_cachekey($key, static::class, $space);
		return $this->instance->exists($key) > 0;
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
	private function _read_cache($key)
	{
		$value = $this->instance->get($key);
		if ($value !== false) {
			if (startswith($value, parent::SERIALIZED)) {
				$value = unserialize(substr($value, strlen(parent::SERIALIZED)));
			} elseif (is_numeric($value)) {
				return $value + 0;
			}
			return $value;
		}
		return null;
	}

	/**
	 * @ignore
	 */
	private function _write_cache($key, $value, $ttl = null) : bool
	{
		if (is_scalar($value)) {
			$value = (string)$value;
		} else {
			$value = parent::SERIALIZED.serialize($value);
		}
		if ($ttl === null) {
			$ttl = ($this->_auto_cleaning) ? 0 : $this->_lifetime;
		}
		if ($ttl > 0) {
			return $this->instance->setEx($key, $ttl, $value);
		} else {
			return $this->instance->set($key, $value);
		}
	}

	/**
	 * @ignore
	 */
	private function _clean(string $space) : int
	{
		$prefix = ($space) ?
			$this->get_cacheprefix(static::class, $space):
			$this->_globlspace;
		if ($prefix === '') { return 0; } //no global interrogation in shared key-space

		$nremoved = 0;
		$keys = $this->instance->keys($prefix.'*');
		foreach ($keys as $key) {
			if ($this->instance->delete($key)) {
				++$nremoved;
			}
		}
		return $nremoved;
	}
} // class
