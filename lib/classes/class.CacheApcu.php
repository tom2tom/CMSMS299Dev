<?php
/*
A class to work with data cached using the PHP APCu extension.
Copyright (C) 2019-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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
use APCUIterator;
use Exception;
use const APC_ITER_KEY;
use const APC_ITER_MTIME;

/**
 * A driver to cache data using PHP's APCu extension
 *
 * Supports settable cache lifetime, automatic cleaning.
 *
 * @package CMS
 * @license GPL
 * @since 2.99
 */
class CacheApcu extends CacheDriver
{
	/**
	 * Constructor
	 *
	 * @param array $params
	 * Associative array of some/all options as follows:
	 *  lifetime  => seconds (default 3600, min 600)
	 *  group => string (default 'default') TODO migrate to 'space'
	 *  globlspace => string cache differentiator (default hashed const)
	 */
	public function __construct(array $params)
	{
		if ($this->use_driver()) {
			parent::__construct($params);
			$this->_lifetime = max($this->_lifetime, 600);
			return;
		}
		throw new Exception('no APCu storage');
	}

	/**
	 * @ignore
	 */
	private function use_driver()
	{
		if (extension_loaded('apcu') && ini_get('apc.enabled')) { //NOT 'apcu.enabled'
			if (class_exists('APCUIterator')) { // V.5+ needed for PHP7+
				return true;
			}
		}
		return false;
	}

	public function get_index(string $space = '')
	{
		if (!$space) { $space = $this->_space; }
		$prefix = $this->get_cacheprefix(static::class, $space);
		if ($prefix === '') { return []; }//no global interrogation in shared key-space
		$len = strlen($prefix);

		$i = 0;
		$out = [];
		$iter = new APCUIterator('/^'.$prefix.'/', APC_ITER_KEY, 20);
		$n = $iter->getTotalCount();
		while ($i < $n) {
			foreach ($iter as $item) {
				$out[] = substr($item['key'], $len);
				++$i;
			}
		}
		sort($out);
		return $out;
	}

	public function get_all(string $space = '')
	{
		if (!$space) { $space = $this->_space; }
		$prefix = $this->get_cacheprefix(static::class, $space);
		if ($prefix === '') { return []; }//no global interrogation in shared key-space

		$i = 0;
		$len = strlen($prefix);
		$out = [];
		$iter = new APCUIterator('/^'.$prefix.'/', APC_ITER_KEY | APC_ITER_VALUE, 20);
		$n = $iter->getTotalCount();
		while ($i < $n) {
			foreach ($iter as $item) {
				$out[substr($item['key'], $len)] = $item['value'];
				++$i;
			}
		}
		// TODO if all values are scalar: asort($out);
		return $out;
	}

	public function get(string $key, string $space = '')
	{
		if (!$space) { $space = $this->_space; }
		$key = $this->get_cachekey($key, static::class, $space);
		$success = false;
		$value = apcu_fetch($key, $success);
		return ($success) ? $value : null;
	}

	public function has(string $key, string $space = '')
	{
		if (!$space) { $space = $this->_space; }
		$key = $this->get_cachekey($key, static::class, $space);
		return apcu_exists($key);
	}

	public function set(string $key, $value, string $space = '')
	{
		if (!$space) { $space = $this->_space; }
		$key = $this->get_cachekey($key, static::class, $space);
		return $this->_write_cache($key, $value);
	}

	public function set_timed(string $key, $value, int $ttl = 0, string $space = '')
	{
		if (!$space) { $space = $this->_space; }
		$key = $this->get_cachekey($key, static::class, $space);
		return apcu_store($key, $value, $ttl);
	}

	public function delete(string $key, string $space = '')
	{
		if (!$space) { $space = $this->_space; }
		$key = $this->get_cachekey($key, static::class, $space);
		return apcu_delete($key);
	}

	public function clear(string $space = '') : int
	{
		if (!$space) { $space = $this->_space; }
		elseif ($space == '*' || $space == '__ALL__') { $space = ''; }
		return $this->_clean($space, false);
	}

	/**
	 * @ignore
	 */
	private function _write_cache(string $key, $value) : bool
	{
		$ttl = ($this->_auto_cleaning) ? 0 : $this->_lifetime;
		return apcu_store($key, $value, $ttl);
	}

	/**
	 * @ignore
	 * @return int No of items removed (i.e. 0 might indicate success)
	 */
	private function _clean(string $space, bool $aged = true) : int
	{
		$prefix = ($space) ?
			$this->get_cacheprefix(static::class, $space):
			$this->_globlspace;
		if ($prefix === '') { return 0; } //no global interrogation in shared key-space

		if ($aged) {
			$ttl = ($this->_auto_cleaning) ? 0 : $this->_lifetime;
			$limit = time() - $ttl;
		}

		$nremoved = 0;
		$format = APC_ITER_KEY;
		if ($aged) {
			$format |= APC_ITER_MTIME;
		}

		$iter = new APCUIterator('/^'.$prefix.'/', $format, 20);
		foreach ($iter as $item) {
			if ($aged) {
				if ($item['mtime'] <= $limit && apcu_delete($item['key'])) {
					++$nremoved;
				}
			} elseif (apcu_delete($item['key'])) {
				++$nremoved;
			}
		}
		return $nremoved;
	}
} // class
