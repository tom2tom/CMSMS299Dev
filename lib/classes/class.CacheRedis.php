<?php
# A class to work with data cached using the PHP Redis extension
# https://github.com/phpredis/phpredis
# Copyright (C) 2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace CMSMS;

use Exception;
use Redis;
use function startswith;

/**
 * A driver to cache data using PHP's PHPRedis extension
 *
 * Supports settable cache lifetime, automatic cleaning.
 *
 * @package CMS
 * @license GPL
 * @since 2.3
 */
class CacheRedis extends CacheDriver
{
    /**
     * @ignore
     */
    private $instance;

    /**
     * Constructor
     *
     * @param array $opts
     * Associative array of some/all options as follows:
     *  lifetime  => seconds (default 3600, NULL => unlimited)
     *  auto_cleaning => boolean (default false)
     *  group => string (no default)
     */
    public function __construct($opts)
    {
        if ($this->use_driver()) {
            if ($this->connectServer()) {
                if (is_array($opts)) {
/*	'host' => string
	'port'  => int
	'password' => string
	'database' => int
*/
                    $_keys = ['lifetime','auto_cleaning','group'];
                    foreach ($opts as $key => $value) {
                        if (in_array($key,$_keys)) {
                            $tmp = '_'.$key;
                            $this->$tmp = $value;
                        }
                    }
                }
                return;
            }
        }
        throw new Exception('no Redis storage');
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
     */
    public function connectServer()
    {
		$params = array_merge([
			'host' => '127.0.0.1',
			'port'  => 6379,
			'password' => '',
			'database' => 0,
			'timeout' => 0.0,
			], $this->config);

		$this->instance = new Redis();
		if (!$this->instance->connect($params['host'], (int)$params['port'], (float)$params['timeout'])) {
			return false;
		} elseif ($params['password'] && !$this->instance->auth($params['password'])) {
			return false;
		}
		if ($params['database']) {
			return $this->instance->select((int)$params['database']);
		}
		return true;
    }

    public function get($key, $group = '')
    {
        if (!$group) $group = $this->_group;

        $key = $this->get_cachekey($key, __CLASS__, $group);
        return $this->_read_cache($key);
    }

    public function exists($key, $group = '')
    {
        if (!$group) $group = $this->_group;

        $key = $this->get_cachekey($key, __CLASS__, $group);
        return $this->instance->get($key) !== null;
    }

    public function set($key, $value, $group = '')
    {
        if (!$group) $group = $this->_group;

        $key = $this->get_cachekey($key, __CLASS__, $group);
        return $this->_write_cache($key, $value);
    }

    public function erase($key, $group = '')
    {
        if (!$group) $group = $this->_group;

        $key = $this->get_cachekey($key, __CLASS__, $group);
        return $this->instance->delete($key);
    }

    public function clear($group = '')
    {
        return $this->_clean($group, false);
    }

    /**
     * @ignore
     */
    private function _read_cache(string $key)
    {
        $data = $this->instance->get($key);
        if ($data !== null) {
            if (startswith($data, parent::SERIALIZED)) {
                $data = unserialize(substr($data, strlen(parent::SERIALIZED)));
            }
            return $data;
        }
        return null;
    }

    /**
     * @ignore
     */
    private function _write_cache(string $key, $data) : bool
    {
        if (!is_scalar($data)) {
            $data = parent::SERIALIZED.serialize($data);
        }
        $ttl = ($this->auto_cleaning) ? 0 : $this->_lifetime;
        if ($ttl > 0) {
            $expire = time() + $ttl;
            return $this->instance->set($key, $data, $expire);
        } else {
            return $this->instance->set($key, $data);
        }
    }

    /**
     * @ignore
     */
    private function _clean(string $group, bool $aged = true) : int
    {
        $nremoved = 0;
        return $nremoved;
    }
} // class
