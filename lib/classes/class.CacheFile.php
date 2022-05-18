<?php
/*
A class to work with cache data in filesystem files.
Copyright (C) 2013-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\CacheDriver;

/**
 * A driver to cache data in filesystem files.
 *
 * Supports settable read and write locking, cache location/folder and
 * lifetime, automatic cleaning, hashed keys and groups so that those
 * cannot be readily understood from filenames.
 *
 * @package CMS
 * @license GPL
 * @since 2.0
 */
class CacheFile extends CacheDriver
{
	/**
	 * @ignore
	 */
	private const LOCK_READ   = '_read';

	/**
	 * @ignore
	 */
	private const LOCK_WRITE  = '_write';

	/**
	 * @ignore
	 */
	private const LOCK_UNLOCK = '_unlock';

	/**
	 * @ignore
	 */
	protected $_blocking = false;

	/**
	 * @ignore
	 */
	protected $_locking = true;

	/**
	 * @ignore
	 */
	protected $_cache_dir = TMP_CACHE_LOCATION;

	/**
	 * Constructor
	 *
	 * @param array $params
	 * Associative array of some/all options as follows:
	 *  lifetime  => seconds (default 3600, NULL => unlimited)
	 *  locking   => boolean (default true)
	 *  cache_dir => string (default TMP_CACHE_LOCATION)
	 *  auto_cleaning => boolean (default false)
	 *  blocking => boolean (default false)
	 *  group => string (no default) TODO migrate to 'space'
	 *  myspace => string cache differentiator (default cms_)
	 */
	#[\ReturnTypeWillChange]
	public function __construct(array $params)
	{
		parent::__construct($params);
		$this->_globlspace = ''; //change default value
		$this->_auto_cleaning = false; //ditto
		if ($params) {
			$_keys = ['locking', 'cache_dir', 'blocking'];
			foreach ($params as $key => $value) {
				if (in_array($key, $_keys)) {
					$tmp = '_'.$key;
					$this->$tmp = $value;
				}
			}
		}
	}

	public function get_index(string $space = '')
	{
		if (!$space) { $space = $this->_space; }
		$prefix = $this->get_cacheprefix(static::class, $space);
		$fn = $this->_cache_dir.DIRECTORY_SEPARATOR.$prefix;
		$patn = ($space) ? $fn.'*.cache':$fn.'*:*.cache';
		$files = glob($patn, GLOB_NOSORT);

		if (!$files) { return []; }
		$len = strlen(prefix);

		$out = [];
		foreach ($files as $fn) {
			if (is_file($fn)) {
				$base = basename($fn, $space.'.cache');
				$out[] = substr($base, $len);
			}
		}
		sort($out);
		return $out;
	}

	public function get_all(string $space = '')
	{
		if (!$space) { $space = $this->_space; }
		$prefix = $this->get_cacheprefix(static::class, $space);
		$fn = $this->_cache_dir.DIRECTORY_SEPARATOR.$prefix;
		$patn = ($space) ? $fn.'*.cache':$fn.'*:*.cache';
		$files = glob($patn, GLOB_NOSORT);

		if (!$files) { return []; }
		$len = strlen(prefix);

		$out = [];
		foreach ($files as $fn) {
			if (is_file($fn)) {
				$base = basename($fn, $space.'.cache');
				$out[substr($base, $len)] = $this->_read_cache_file($fn);
			}
		}
		// TODO if all values are scalar: asort($out);
		return $out;
	}

	public function get($key, string $space = '')
	{
		if (!$space) { $space = $this->_space; }
		$this->_auto_clean_files();
		$fn = $this->_get_filename($key, $space);
		return $this->_read_cache_file($fn);
	}

	public function has($key, string $space = '')
	{
		if (!$space) { $space = $this->_space; }
		$this->_auto_clean_files();
		$fn = $this->_get_filename($key, $space);
		clearstatcache(false, $fn);
		return is_file($fn);
	}

	public function set($key, $value, string $space = '')
	{
		if (!$space) { $space = $this->_space; }
		$fn = $this->_get_filename($key, $space);
		$res = $this->_write_cache_file($fn, $value);
		return $res;
	}

	// custom lifetime N/A for file-cache
	public function set_timed($key, $value, int $ttl = 0, string $space = '')
	{
		return $this->set($key, $value, $space);
	}

	public function delete($key, string $space = '')
	{
		if (!$space) { $space = $this->_space; }
		$fn = $this->_get_filename($key, $space);
		if (is_file($fn)) {
			@unlink($fn);
			return true;
		}
		return false;
	}

	public function clear(string $space = '') : int
	{
		if (!$space) { $space = $this->_space; }
		elseif ($space == '*' || $space == '__ALL__') { $space = ''; }
		return $this->_clean_dir($this->_cache_dir, $space, false);
	}

	/**
	 * @ignore
	 * TODO need distinguishable "group" files
	 */
	private function _get_filename($key, string $space) : string
	{
		$fn = $this->_cache_dir . DIRECTORY_SEPARATOR . $this->get_cachekey($key, static::class, $space) . $space . '.cache';
		return $fn;
	}

	/**
	 * @ignore
	 */
	private function _flock($res, string $flag) : bool
	{
		if (!$this->_locking) return true;
		if (!$res) return false;

		$mode = '';
		switch(strtolower($flag)) {
		case self::LOCK_READ:
			$mode = LOCK_SH;
			break;

		case self::LOCK_WRITE:
			$mode = LOCK_EX;
			break;

		case self::LOCK_UNLOCK:
			$mode = LOCK_UN;
		}

		if ($this->_blocking) return flock($res, $mode);

		// non blocking lock
		$mode = $mode | LOCK_NB;
		for($n = 0; $n < 5; $n++) {
			$res2 = flock($res, $mode);
			if ($res2) return true;
			$tl = rand(5, 300);
			usleep($tl);
		}
		return false;
	}

	/**
	 * @ignore
	 */
	private function _read_cache_file(string $fn)
	{
		$this->_cleanup($fn);
		$value = null;
		if (is_file($fn)) {
			clearstatcache();
			$fp = @fopen($fn, 'rb');
			if ($fp) {
				if ($this->_flock($fp, self::LOCK_READ)) {
					$len = @filesize($fn);
					if ($len > 0) $value = fread($fp, $len);
					$this->_flock($fp, self::LOCK_UNLOCK);
				}
				@fclose($fp);

				if (startswith($value, parent::SERIALIZED)) {
					$value = unserialize(substr($value, strlen(parent::SERIALIZED)));
				}
				return $value;
			}
		}
	}

	/**
	 * @ignore
	 */
	private function _cleanup(string $fn)
	{
		if (empty($this->_lifetime)) return;
		clearstatcache();
		if (@filemtime($fn) < time() - $this->_lifetime) @unlink($fn);
	}

	/**
	 * @ignore
	 */
	private function _write_cache_file(string $fn, $value) : bool
	{
		$fp = @fopen($fn, 'wb');
		if ($fp) {
			if (!$this->_flock($fp, self::LOCK_WRITE)) {
				@fclose($fp);
				@unlink($fn);
				return false;
			}

			if (!is_scalar($value)) {
				$value = parent::SERIALIZED.serialize($value);
			}
			$res = @fwrite($fp, $value);
			$this->_flock($fp, self::LOCK_UNLOCK);
			@fclose($fp);
			return ($res !== false);
		}
		return false;
	}

	/**
	 * @ignore
	 */
	private function _auto_clean_files() : int
	{
		if ($this->_auto_cleaning) {
			// static properties here >> Lone property|ies ?
			// only clean files once per request.
			static $_have_cleaned = false;
			if (!$_have_cleaned) {
				$res = $this->_clean_dir($this->_cache_dir, '');
				if ($res) $_have_cleaned = true;
				return $res;
			}
		}
		return 0;
	}

	/**
	 * @ignore
	 * @return int No of items deleted (i.e. 0 might indicate success)
	 */
	private function _clean_dir(string $dir, string $space, bool $aged = true) : int
	{
		$prefix = ($space) ?
			$this->get_cacheprefix(static::class, $space):
			$this->_globlspace;
		if (!$prefix) { return 0; }

		$fn = $dir.DIRECTORY_SEPARATOR.$prefix;
		$patn = ($space) ? $fn.'*.cache':$fn.'*:*.cache'; // TODO
		$files = glob($patn, GLOB_NOSORT);
		if (!$files) { return 0; }

		if ($aged) {
			if ($this->_lifetime) {
				$limit = time() - $this->_lifetime;
			} else {
				$aged = false;
			}
		}
		$nremoved = 0;
		foreach ($files as $fn) {
			if (is_file($fn)) {
				if ($aged) {
					if (@filemtime($fn) < $limit)  {
						@unlink($n);
						$nremoved++;
					}
				}
				else {
					// all files...
					@unlink($fn);
					$nremoved++;
				}
			}
		}
		return $nremoved;
	}
} // class
