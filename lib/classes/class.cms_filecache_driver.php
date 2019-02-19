<?php
# A class to work with cache data in files in the TMP_CACHE directory.
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

use CMSMS\CacheDriver;

/**
 * A driver to cache data in filesystem files.
 *
 * Supports settable read and write locking, cache location and lifetime,
 * automatic cleaning, hashed keys and groups so that filenames cannot
 * be easily determined.
 *
 * @package CMS
 * @license GPL
 * @since 2.0
 * @author Robert Campbell
 */
class cms_filecache_driver implements CacheDriver
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
    const SERIALIZED = '|~SE6ED^|';

    /**
     * @ignore
     * NULL for unlimited
     */
    private $_lifetime = 3600; //1 hour

    /**
     * @ignore
     */
    private $_locking = TRUE;

    /**
     * @ignore
     */
    private $_blocking = FALSE;

    /**
     * @ignore
     */
    private $_cache_dir = TMP_CACHE_LOCATION;

    /**
     * @ignore
     */
    private $_auto_cleaning = FALSE;

    /**
     * @ignore
     */
    private $_group = 'default';

    /**
     * Constructor
     *
     * @param array $opts
     * Associative array of some/all options as follows:
     *  lifetime  => seconds (default 3600, NULL => unlimited)
     *  locking   => boolean (default true)
     *  cache_dir => string (default TMP_CACHE_LOCATION)
     *  auto_cleaning => boolean (default FALSE)
     *  blocking => boolean (default FALSE)
     *  group => string (no default)
     */
    public function __construct($opts)
    {
        if( is_array($opts) ) {
            $_keys = ['lifetime','locking','cache_dir','auto_cleaning','blocking','group'];
            foreach( $opts as $key => $value ) {
                if( in_array($key,$_keys) ) {
                    $tmp = '_'.$key;
                    $this->$tmp = $value;
                }
            }
        }
    }


    /**
     * Get a cached value
     * if the $group parameter is not specified the current group will be used
     *
     * @see cms_filecache_driver::set_group()
     * @param string $key
     * @param string $group
     */
    public function get($key,$group = '')
    {
        if( !$group ) $group = $this->_group;

        $this->_auto_clean_files();
        $fn = $this->_get_filename($key, $group);
        $data = $this->_read_cache_file($fn);
        return $data;
    }


    /**
     * Test if a cached value exists.
     * if the $group parameter is not specified the current group will be used
     *
     * @see cms_filecache_driver::set_group()
     * @param string $key
     * @param string $group
     */
    public function exists($key,$group = '')
    {
        if( !$group ) $group = $this->_group;

        $this->_auto_clean_files();
        $fn = $this->_get_filename($key, $group);
        clearstatcache(FALSE, $fn);
        return is_file($fn);
    }


    /**
     * Set a cached value
     * if the $group parameter is not specified the current group will be used
     *
     * @see cms_filecache_driver::set_group()
     * @param string $key
     * @param mixed $value
     * @param string $group
     */
    public function set($key,$value,$group = '')
    {
        if( !$group ) $group = $this->_group;

        $fn = $this->_get_filename($key,$group);
        $res = $this->_write_cache_file($fn,$value);
        return $res;
    }


    /**
     * Set the current group
     *
     * @param string $group
     */
    public function set_group($group)
    {
        if( $group ) $this->_group = trim($group);
    }


    /**
     * Erase a cached value
     * if the $group parameter is not specified the current group will be used
     *
     * @see cms_filecache_driver::set_group()
     * @param string $key
     * @param string $group
     */
    public function erase($key,$group = '')
    {
        if( !$group ) $group = $this->_group;

        $fn = $this->_get_filename($key, $group);
        if( is_file($fn) ) {
            @unlink($fn);
            return TRUE;
        }
        return FALSE;
    }


    /**
     * Clear all cached values from a group
     * if the $group parameter is not specified the current group will be used
     *
     * @see cms_filecache_driver::set_group()
     * @param string $group
     */
    public function clear($group = '')
    {
        if( !$group ) $group = $this->_group;
        return $this->_clean_dir($this->_cache_dir,$group,FALSE);
    }

    /**
     * @ignore
     */
    private function _get_filename(string $key,string $group) : string
    {
        $fn = $this->_cache_dir . '/cache_'.md5(__DIR__.$group).'_'.md5($key.__DIR__).'.cms';
        return $fn;
    }


    /**
     * @ignore
     */
    private function _flock($res,string $flag) : bool
    {
        if( !$this->_locking ) return TRUE;
        if( !$res ) return FALSE;

        $mode = '';
        switch( strtolower($flag) ) {
        case self::LOCK_READ:
            $mode = LOCK_SH;
            break;

        case self::LOCK_WRITE:
            $mode = LOCK_EX;
            break;

        case self::LOCK_UNLOCK:
            $mode = LOCK_UN;
        }

        if( $this->_blocking ) return flock($res,$mode);

        // non blocking lock
        $mode = $mode | LOCK_NB;
        for( $n = 0; $n < 5; $n++ ) {
            $res2 = flock($res,$mode);
            if( $res2 ) return TRUE;
            $tl = rand(5, 300);
            usleep($tl);
        }
        return FALSE;
    }


    /**
     * @ignore
     */
    private function _read_cache_file(string $fn)
    {
        $this->_cleanup($fn);
        $data = null;
        if( is_file($fn) ) {
            clearstatcache();
            $fp = @fopen($fn,'rb');
            if( $fp ) {
                if( $this->_flock($fp,self::LOCK_READ) ) {
                    $len = @filesize($fn);
                    if( $len > 0 ) $data = fread($fp,$len);
                    $this->_flock($fp,self::LOCK_UNLOCK);
                }
                @fclose($fp);

                if( startswith($data,self::SERIALIZED) ) {
                    $data = unserialize(substr($data,strlen(self::SERIALIZED)));
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
        if( is_null($this->_lifetime) ) return;
        clearstatcache();
        $filemtime = @filemtime($fn);
        if( $filemtime < time() - $this->_lifetime ) @unlink($fn);
    }


    /**
     * @ignore
     */
    private function _write_cache_file(string $fn,$data) : bool
    {
        $fp = @fopen($fn,'wb');
        if( $fp ) {
            if( !$this->_flock($fp,self::LOCK_WRITE) ) {
                @fclose($fp);
                @unlink($fn);
                return FALSE;
            }

            if( !is_scalar($data) ) {
                $data = self::SERIALIZED.serialize($data);
            }
            $res = @fwrite($fp,$data);
            $this->_flock($fp,self::LOCK_UNLOCK);
            @fclose($fp);
            return ($res !== FALSE);
        }
        return FALSE;
    }


    /**
     * @ignore
     */
    private function _auto_clean_files() : int
    {
        if( $this->_auto_cleaning > 0 ) {
            // only clean files once per request.
            static $_have_cleaned = FALSE;
            if( !$_have_cleaned ) {
                $res = $this->_clean_dir($this->_cache_dir);
                if( $res ) $_have_cleaned = TRUE;
                return $res;
            }
        }
        return 0;
    }


    /**
     * @ignore
     */
    private function _clean_dir(string $dir,$group = '',bool $aged = TRUE) : int
    {
        if( !$group ) $group = $this->_group;

        if( $group ) $mask = $dir.'/cache_'.md5(__DIR__.$group).'_*.cms';
        else $mask = $dir.'/cache_*_*.cms';

        $files = glob($mask, GLOB_NOSORT);
        if( !is_array($files) ) return 0;

        $nremoved = 0;
        $now = time();
        foreach( $files as $file ) {
            if( is_file($file) ) {
                if( $aged ) {
                    if( !is_null($this->_lifetime) ) {
                        if( ($now - @filemtime($file)) > $this->_lifetime ) {
                            @unlink($file);
                            $nremoved++;
                        }
                    }
                }
                else {
                    // clean all files...
                    @unlink($file);
                    $nremoved++;
                }
            }
        }
        return $nremoved;
    }

} // class
