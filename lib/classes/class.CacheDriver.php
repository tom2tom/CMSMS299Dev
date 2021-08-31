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
    protected $_space = 'default'; //anything not-empty

    /**
     * @ignore
     */
    protected $_auto_cleaning = true;

    /**
     * @ignore
     * default value-lifetime (seconds), 0 for unlimited
     */
    protected $_lifetime = 3600; //1 hour

    public function __construct(array $params)
    {
        $uuid = SingleItem::App()->GetSiteUUID();
        $this->_globlspace = $this->hash($uuid); //might be replaced in $params or subclass

        if ($params) {
            // TODO migrate 'group' to 'space'
            $_keys = ['lifetime', 'group', 'myspace', 'auto_cleaning'];
            foreach ($params as $key => $value) {
                if (in_array($key,$_keys)) {
                    $tmp = '_'.$key;
                    $this->$tmp = $value;
                }
            }
        }
    }

    /**
     * Get a cached value
     *
     * @param string $key
     * @param string $space Optional keys-space name, default ''.
     *  If not specified, the default keys-space will be used.
     */
    abstract public function get(string $key, string $space = '');

    /**
     * Get all cached values in a keys-space
     *
     * @param string $space Optional keys-space name, default ''.
     *  If not specified, the default keys-space will be used.
     * @return array, each member like $key=>$value, or maybe empty
     */
    abstract public function get_all(string $space = '');

    /**
     * Get all keys/identifiers in a keys-space
     *
     * @param string $space Optional keys-space name, default ''.
     *  If not specified, the default keys-space will be used.
     * @return array, each member like $key=>$value, or maybe empty
     */
    abstract public function get_index(string $space = '');

    /**
     * Report whether a cached value exists
     *
     * @param string $key
     * @param string $space Optional keys-space name, default ''.
     *  If not specified, the default keys-space will be used.
     * @return bool
     */
    abstract public function has(string $key, string $space = '');

    /**
     * Set a cached value
     *
     * @param string $key
     * @param mixed $value
     * @param string $space Optional keys-space name, default ''.
     *  If not specified, the default keys-space will be used.
     */
    abstract public function set(string $key, $value, string $space = '');

    /**
     * Set a cached value with a custom lifetime
     *
     * @param string $key
     * @param mixed $value
     * $param int $ttl Optional value-lifetime (seconds), default 0. Hence unlimited.
     * @param string $space Optional keys-space name, default ''.
     *  If not specified, the default keys-space will be used.
     */
    abstract public function set_timed(string $key, $value, int $ttl = 0, string $space = '');

    /**
     * Remove a cached value
     *
     * @param string $key
     * @param string $space Optional keys-space name, default ''.
     *  If not specified, the default keys-space will be used.
     */
    abstract public function delete(string $key, string $space = '');

    /**
     * Remove all cached values from a keys-space, or from the whole cache
     *
     * @param string $space Optional keys-space name, default ''.
     *  If not specified, the default keys-space will be used.
     *  If $space is '*' or '__ALL__', the whole cache (i.e. all spaces) will be cleared.
     * @return mixed bool | int no. of items removed
     */
    abstract public function clear(string $space = '');

    /**
     * Set the default keys-space
     * @since 2.99
     *
     * @param string $space Ignored if empty
     */
    public function set_space(string $space)
    {
        if ($space) { $this->_space = trim($space); }
    }

    /**
     * Get the default keys-space
     * @since 2.99
     *
     * @return string
     */
    public function get_space() : string
    {
        return $this->_space;
    }

    /**
     * Construct a cache keys-space identifier corresponding to the supplied string
     * @param string $str a unique identifier e.g. a class name
     * @return string 10 alphanum bytes
     */
    protected function get_cachespace(string $str) : string
    {
        $value = base_convert(hash('fnv1a64', $str), 16, 36);
        return substr($value, 0, 10);
    }

    /**
     * Hash $str
     * @param string $str
     * @param int $len hash-length default 10
     * @return string
     */
    private function hash(string $str, int $len = 10) : string
    {
        $value = hash('fnv1a64', $str);
        //conversion generates 6 output-bytes for each 8 input-bytes
        for ($l = 6; $l < $len; $l += $l) {
            $value .= $value;
        }
        $s = base_convert($value, 16, 36);
        return substr($s, 0, $len);
    }

    /**
     * Construct a cache-key with identifiable space-prefix
     * @param string $key cache-item key
     * @param string $class initiator class
     * @param string $space cache keys-space
     * @return string
     */
    protected function get_cachekey(string $key, string $class, string $space) : string
    {
        return $this->_globlspace.$this->hash(CMS_ROOT_URL.$class.$space).$key;
    }

    /**
     * Construct a cache-key space-prefix (matching the one generated by get_cachekey())
     * @param string $class initiator class
     * @param string $space cache keys-space name
     * @return string
     */
    protected function get_cacheprefix(string $class, string $space) : string
    {
        return $this->get_cachekey('', $class, $space);
    }
} // class
