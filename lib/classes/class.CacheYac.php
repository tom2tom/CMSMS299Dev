<?php
/*
A class to work with data cached using the PHP YAC extension.
Copyright (C) 2019-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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
use Yac;

/**
 * A driver to cache data using PHP's YAC extension
 *
 * Supports settable cache lifetime, automatic cleaning.
 *
 * @package CMS
 * @license GPL
 * @since 3.0
 */
class CacheYac extends CacheDriver
{
	/**
	 * @ignore
	 */
	private $instance;

	/**
	 * Constructor
	 *
	 * @param array $params Associative array of some/all
	 * cache-driver options as follows:
	 *  lifetime  => seconds (default 3600, min 600)
	 *  group => string (default 'default') TODO migrate to 'space'
	 *  myspace => string cache differentiator (default cms_)
	 */
	public function __construct(array $params)
	{
		if ($this->use_driver()) {
			if ($this->connectServer()) {
				parent::__construct($params);
				$this->_lifetime = max($this->_lifetime, 600);
				return;
			}
		}
		throw new Exception('no YAC storage');
	}

	/**
	 * @ignore
	 */
	private function use_driver()
	{
		return extension_loaded('yac') && ini_get('yac.enable');
	}

	/**
	 * @ignore
	 */
	private function connectServer()
	{
		$this->instance = new Yac();
		return $this->instance != null;
	}

	public function get_index(string $space = '')
	{
		if (!$space) { $space = $this->_space; }

		$prefix = $this->get_cacheprefix(static::class, $space);
		if ($prefix === '') { return []; }

		$out = [];
		$info = $this->instance->info();
		$c = (int)$info['slots_used'];
		if ($c) {
			$info = $this->instance->dump($c);
			if ($info) {
				$len = strlen($prefix);

				foreach ($info as $item) {
					$key = $item['key'];
					if (strncmp($key, $prefix, $len) == 0) {
						$out[] = substr($key,$len);
					}
				}
				sort($out);
			}
		}
		return $out;
	}

	public function get_all(string $space = '')
	{
		if (!$space) { $space = $this->_space; }

		$prefix = $this->get_cacheprefix(static::class, $space);
		if ($prefix === '') { return []; }

		$out = [];
		$info = $this->instance->info();
		$c = (int)$info['slots_used'];
		if ($c) {
			$info = $this->instance->dump($c);
			if ($info) {
				$len = strlen($prefix);

				foreach ($info as $item) {
					$key = $item['key'];
					if (strncmp($key, $prefix, $len) == 0) {
						$out[substr($key,$len)] = $this->instance->get($key);
					}
				}
				// TODO if all values are scalar: asort($out);
			}
		}
		return $out;
	}

	public function get(string $key, string $space = '')
	{
		if (!$space) { $space = $this->_space; }

		$key = $this->get_cachekey($key, static::class, $space);
		$res = $this->instance->get($key);
		return ($res || !is_bool($res)) ? $res : null;
	}

	public function has(string $key, string $space = '')
	{
		if (!$space) { $space = $this->_space; }
		$key = $this->get_cachekey($key, static::class, $space);
		$res = $this->instance->get($key);
		return $res || !is_bool($res);
	}

	public function set(string $key, $value, string $space = '')
	{
		if (!$space) { $space = $this->_space; }
		$key = $this->get_cachekey($key, static::class, $space);
		if ($value === false) $value = 0; //ensure actual false isn't ignored
		return $this->_write_cache($key, $value);
	}

	public function set_timed(string $key, $value, int $ttl = 0, string $space = '')
	{
		if (!$space) { $space = $this->_space; }
		$key = $this->get_cachekey($key, static::class, $space);
		return $this->_write_cache($key, $value, $ttl);
	}

	public function delete(string $key, string $space = '')
	{
		if (!$space) { $space = $this->_space; }
		$key = $this->get_cachekey($key, static::class, $space);
		return $this->instance->delete($key);
	}

	public function clear(string $space = ''): int
	{
		if (!$space) { $space = $this->_space; }
		elseif ($space == '*' || $space == '__ALL__') { $space = ''; }
		return $this->_clean($space, false);
	}

	/**
	 * @ignore
	 */
	private function _write_cache(string $key, $value, int $ttl = -1): bool
	{
		if ($ttl == -1) {
			$ttl = ($this->_auto_cleaning) ? 0 : $this->_lifetime;
		}
		if ($ttl > 0) {
			return $this->instance->set($key, $value, $ttl);
		} else {
			return $this->instance->set($key, $value);
		}
	}

	/**
	 * @ignore
	 * @return int No of items removed (i.e. 0 might indicate success)
	 */
	private function _clean(string $space): int
	{
		$prefix = ($space) ?
			$this->get_cacheprefix(static::class, $space):
			$this->_globlspace;
		if ($prefix === '') { return 0; } //no global interrogation in shared key-space

		$nremoved = 0;
		$info = $this->instance->info();
		$c = (int)$info['slots_used'];
		if ($c) {
			$info = $this->instance->dump($c);
			if ($info) {
				$len = strlen($prefix);

				foreach ($info as $item) {
					$key = $item['key'];
					if (strncmp($key, $prefix, $len) == 0) {
						if ($this->instance->delete($key)) {
							++$nremoved;
						}
					}
				}
			}
		}
		return $nremoved;
	}
} // class
