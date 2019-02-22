<?php
# A class to work with cache data in filesystem files.
# Copyright (C) 2013-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
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
 * @author Robert Campbell
 */
class CacheFile extends CacheDriver
{
    /**
     * @ignore
     */
    const LOCK_READ   = '_read';

    /**
     * @ignore
     */
    const LOCK_WRITE  = '_write';

    /**
     * @ignore
     */
    const LOCK_UNLOCK = '_unlock';

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
     * @param array $opts
     * Associative array of some/all options as follows:
     *  lifetime  => seconds (default 3600, NULL => unlimited)
     *  locking   => boolean (default true)
     *  cache_dir => string (default TMP_CACHE_LOCATION)
     *  auto_cleaning => boolean (default false)
     *  blocking => boolean (default false)
     *  group => string (no default)
     *  myspace => string cache differentiator (default cms_)
     */
    public function __construct($opts)
    {
        $this->_auto_cleaning = false; //change default value
        if (is_array($opts)) {
            $_keys = ['lifetime','locking','cache_dir','auto_cleaning','blocking','group', 'myspace'];
            foreach ($opts as $key => $value) {
                if (in_array($key,$_keys)) {
                    $tmp = '_'.$key;
                    $this->$tmp = $value;
                }
            }
        }
    }


    public function get($key,$group = '')
    {
        if (!$group) $group = $this->_group;

        $this->_auto_clean_files();
        $fn = $this->_get_filename($key, $group);
        $data = $this->_read_cache_file($fn);
        return $data;
    }


    public function exists($key,$group = '')
    {
        if (!$group) $group = $this->_group;

        $this->_auto_clean_files();
        $fn = $this->_get_filename($key, $group);
        clearstatcache(false, $fn);
        return is_file($fn);
    }


    public function set($key,$value,$group = '')
    {
        if (!$group) $group = $this->_group;

        $fn = $this->_get_filename($key,$group);
        $res = $this->_write_cache_file($fn, $value);
        return $res;
    }


    public function erase($key,$group = '')
    {
        if (!$group) $group = $this->_group;

        $fn = $this->_get_filename($key, $group);
        if (is_file($fn)) {
            @unlink($fn);
            return true;
        }
        return false;
    }


    public function clear($group = '')
    {
        if (!$group) $group = $this->_group;
        return $this->_clean_dir($this->_cache_dir, $group, false);
    }

    /**
     * @ignore
     */
    private function _get_filename(string $key, string $group) : string
    {
        $fn = $this->_cache_dir . DIRECTORY_SEPARATOR . $this->get_cachekey($key, __CLASS__, $group) . '.cache';
        return $fn;
    }


    /**
     * @ignore
     */
    private function _flock($res,string $flag) : bool
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

        if ($this->_blocking) return flock($res,$mode);

        // non blocking lock
        $mode = $mode | LOCK_NB;
        for($n = 0; $n < 5; $n++) {
            $res2 = flock($res,$mode);
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
        $data = null;
        if (is_file($fn)) {
            clearstatcache();
            $fp = @fopen($fn,'rb');
            if ($fp) {
                if ($this->_flock($fp,self::LOCK_READ)) {
                    $len = @filesize($fn);
                    if ($len > 0) $data = fread($fp,$len);
                    $this->_flock($fp,self::LOCK_UNLOCK);
                }
                @fclose($fp);

                if (startswith($data,parent::SERIALIZED)) {
                    $data = unserialize(substr($data,strlen(parent::SERIALIZED)));
                }
                return $data;
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
    private function _write_cache_file(string $fn,$data) : bool
    {
        $fp = @fopen($fn,'wb');
        if ($fp) {
            if (!$this->_flock($fp,self::LOCK_WRITE)) {
                @fclose($fp);
                @unlink($fn);
                return false;
            }

            if (!is_scalar($data)) {
                $data = parent::SERIALIZED.serialize($data);
            }
            $res = @fwrite($fp,$data);
            $this->_flock($fp,self::LOCK_UNLOCK);
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
     */
    private function _clean_dir(string $dir, string $group, bool $aged = true) : int
    {
        $mask = ($group) ?
         $dir.DIRECTORY_SEPARATOR.$this->get_cacheprefix(__CLASS__, $group).'*.cache':
         $dir.DIRECTORY_SEPARATOR.self::MYSPACE.'*:*.cache';

        $files = glob($mask, GLOB_NOSORT);
        if (!is_array($files)) return 0;

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

//backward-compatibility shiv
\class_alias(CacheFile::class, 'cms_filecache_driver', false);