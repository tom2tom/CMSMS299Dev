<?php
/*
Base class for data-cache drivers.
Copyright (C) 2019-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS;

use const CMS_ROOT_URL;

/**
 * Base class for data-cache drivers
 *
 * @since 2.99
 * @package CMS
 * @license GPL
 */
abstract class CacheDriver
{
    /**
     * @ignore
     */
    const SERIALIZED = '_|SE6ED|_';

    /**
     * @ignore
     * Cache keys prefix for targeting in shared public data caches, may be '' in non-shared caches
     */
    protected $_globlspace;

    /**
     * @ignore
     */
    protected $_group = 'default'; //not empty

    /**
     * @ignore
     */
    protected $_auto_cleaning = true;

    /**
     * @ignore
     * NULL for unlimited
     */
    protected $_lifetime = 3600; //1 hour

    public function __construct($opts)
    {
        $uuid = AppSingle::App()->GetSiteUUID();
        $this->_globlspace = $this->hash($uuid); //might be replaced in $opts or subclass

        if (is_array($opts)) {
            $_keys = ['lifetime', 'group', 'myspace', 'auto_cleaning'];
            foreach ($opts as $key => $value) {
                if (in_array($key,$_keys)) {
                    $tmp = '_'.$key;
                    $this->$tmp = $value;
                }
            }
        }
    }

    /**
     * Get a cached value
     * If the $group parameter is not specified the current group will be used
     * @see CacheDriver::set_group()
     *
     * @param string $key
     * @param string $group Optional name, default ''
     */
    abstract public function get($key, $group = '');

    /**
     * Get all cached values in a group
     * If the $group parameter is not specified the current group will be used
     * @see CacheDriver::set_group()
     *
     * @param string $group Optional keys-space name, default ''
     * @return array, each member like $key=>$value, or maybe empty
     */
    abstract public function get_all($group = '');

    /**
     * Get all cached keys in a group
     * If the $group parameter is not specified the current group will be used
     * @see CacheDriver::set_group()
     *
     * @param string $group Optional keys-space name, default ''
     * @return array, each member like $key=>$value, or maybe empty
     */
    abstract public function get_index($group = '');

    /**
     * Test if a cached value exists.
     * If the $group parameter is not specified the current group will be used
     * @see CacheDriver::set_group()
     *
     * @param string $key
     * @param string $group Optional keys-space name, default ''
     * @return bool
     */
    abstract public function has($key, $group = '');

    /**
     * Set a cached value
     * If the $group parameter is not specified the current group will be used
     * @see CacheDriver::set_group()
     *
     * @param string $key
     * @param mixed $value
     * @param string $group Optional keys-space name, default ''
     */
    abstract public function set($key, $value, $group = '');

    /**
     * Delete a cached value
     * If the $group parameter is not specified the current group will be used
     *
     * @see CacheDriver::set_group()
     * @param string $key
     * @param string $group Optional keys-space name, default ''
     */
    abstract public function delete($key, $group = '');

    /**
     * Delete all cached values in a group
     * If the $group parameter is empty, all groups will be cleared
     * @see CacheDriver::set_group()
     *
     * @param string $group Optional keys-space name, default ''
     */
    abstract public function clear($group = '');

    /**
     * Set the current group
     *
     * @param string $group Ignored if empty
     */
    public function set_group($group)
    {
        if ($group) $this->_group = trim($group);
    }

    /**
     * Hash $str
     * @param string $str
     * @param int $len hash-length default 10
     * @return string
     */
    private function hash(string $str, int $len = 10) : string
    {
        $value = hash('fnv132', $str);
        //conversion generates 6 output-bytes for each 8 input-bytes
        for ($l = 6; $l < $len; $l += $l) {
            $value .= $value;
        }
        $s = base_convert($value, 16, 36);
        return substr($s, 0, $len);
    }

    /**
     * Construct a cache-key with identifiable group-prefix
     * @param string $key cache-item key
     * @param string $class initiator class
     * @param string $group cache keys-space
     * @return string
     */
    protected function get_cachekey(string $key, string $class, string $group) : string
    {
        return $this->_globlspace.$this->hash(CMS_ROOT_URL.$class.$group).$key;
    }

    /**
     * Construct a cache-key group-prefix (matching the one generated by get_cachekey())
     * @param string $class initiator class
     * @param string $group cache keys-space name
     * @return string
     */
    protected function get_cacheprefix(string $class, string $group) : string
    {
        return $this->get_cachekey('', $class, $group);
    }
} // class
