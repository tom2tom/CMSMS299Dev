<?php
# A class to work with data cached using the PHP APC extension.
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

/**
 * A driver to cache data using PHP's APC extension
 *
 * Supports settable cache lifetime, automatic cleaning.
 *
 * @package CMS
 * @license GPL
 * @since 2.3
 */
class CacheApcu extends CacheDriver
{
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
            if (is_array($opts)) {
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
        throw new Exception('no APC storage');
    }

    /**
     * @ignore
     */
    private function use_driver()
    {
        return (extension_loaded('apc') && ini_get('apc.enabled'));
    }

    public function get($key, $group = '')
    {
        if (!$group) $group = $this->_group;

        $key = $this->get_cachekey($key, __CLASS__, $group);
        $success = false;
        $data = apc_fetch($key, $success);
        return ($success) ? $data : null;
    }

    public function exists($key, $group = '')
    {
        if (!$group) $group = $this->_group;

        $key = $this->get_cachekey($key, __CLASS__, $group);
        return apc_exists($key);
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
        return apc_delete($key);
    }

    public function clear($group = '')
    {
        return $this->_clean($group, false);
    }

    /**
     * @ignore
     */
    private function _write_cache(string $key, $data) : bool
    {
        $ttl = ($this->auto_cleaning) ? 0 : $this->_lifetime;
        return apc_store($key, $data, $ttl);
    }

    /**
     * @ignore
     */
    private function _clean(string $group, bool $aged = true) : int
    {
        $prefix = $this->get_cacheprefix(__CLASS__, $group);
        if ($aged) {
            $ttl = ($this->auto_cleaning) ? 0 : $this->_lifetime;
            $limit = time() - $ttl;
        }

        $nremoved = 0;
        $format = APC_ITER_KEY;
        if ($aged) {
            $format |= APC_ITER_MTIME;
        }
        $iter = new APCIterator('user', '/^'.$prefix.'/', $format, 20);
        foreach ($iter as $item) {
            if ($aged) {
                if ($item['mtime'] <= $limit && apc_delete($item['key'])) {
                    ++$nremoved;
                }
            } elseif (apc_delete($item['key'])) {
                ++$nremoved;
            }
        }
        return $nremoved;
    }
} // class
