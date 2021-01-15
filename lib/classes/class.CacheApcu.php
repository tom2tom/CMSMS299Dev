<?php
/*
A class to work with data cached using the PHP APCu extension.
Copyright (C) 2019-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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
     * @param array $opts
     * Associative array of some/all options as follows:
     *  lifetime  => seconds (default 3600, min 600)
     *  group => string (default 'default')
     *  globlspace => string cache differentiator (default hashed const)
     */
    public function __construct($opts)
    {
        if ($this->use_driver()) {
            parent::__construct($opts);
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

    public function get_index($group = '')
    {
        if (!$group) $group = $this->_group;
        $prefix = $this->get_cacheprefix(static::class, $group);
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

    public function get_all($group = '')
    {
        if (!$group) $group = $this->_group;

        $prefix = $this->get_cacheprefix(static::class, $group);
        if ($prefix === '') { return []; }//no global interrogation in shared key-space

        $i = 0;
        $out = [];
        $iter = new APCUIterator('/^'.$prefix.'/', APC_ITER_KEY | APC_ITER_VALUE, 20);
        $n = $iter->getTotalCount();
        while ($i < $n) {
            foreach ($iter as $item) {
                $out[substr($item['key'], $len)] = $item['value'];
                ++$i;
            }
        }
        asort($out);
        return $out;
    }

    public function get($key, $group = '')
    {
        if (!$group) $group = $this->_group;

        $key = $this->get_cachekey($key, static::class, $group);
        $success = false;
        $data = apcu_fetch($key, $success);
        return ($success) ? $data : null;
    }

    public function has($key, $group = '')
    {
        if (!$group) $group = $this->_group;

        $key = $this->get_cachekey($key, static::class, $group);
        return apcu_exists($key);
    }

    public function set($key, $value, $group = '')
    {
        if (!$group) $group = $this->_group;

        $key = $this->get_cachekey($key, static::class, $group);
        return $this->_write_cache($key, $value);
    }

    public function delete($key, $group = '')
    {
        if (!$group) $group = $this->_group;

        $key = $this->get_cachekey($key, static::class, $group);
        return apcu_delete($key);
    }

    public function clear($group = '')
    {
//        if (!$group) { $group = $this->_group; }
        return $this->_clean($group, false);
    }

    /**
     * @ignore
     */
    private function _write_cache(string $key, $data) : bool
    {
        $ttl = ($this->_auto_cleaning) ? 0 : $this->_lifetime;
        return apcu_store($key, $data, $ttl);
    }

    /**
     * @ignore
     */
    private function _clean(string $group, bool $aged = true) : int
    {
        $prefix = ($group) ?
            $this->get_cacheprefix(static::class, $group):
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
