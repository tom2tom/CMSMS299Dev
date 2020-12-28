<?php
# Class which defines folder-specific properties and roles
# Copyright (C) 2016-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

namespace FilePicker;

use CMSMS\AppSingle;
use CMSMS\FileSystemControls;
use CMSMS\FileTypeHelper;
use Exception;
use Throwable;
use const CMS_DEBUG;
use const PUBLIC_CACHE_LOCATION;
use function cms_to_bool;
use function debug_to_log;
use function lang;
use function startswith;

class Profile extends FileSystemControls
{
    /**
     * Constructor
     * @internal
     * @param array $params optional assoc array of profile props and vals. Default []
     */
    public function __construct( array $params = [] )
    {
        $props = [
          'id'=>0, // if the profile data are db-stored, this is for the row-index
          'name'=>'',
          'file_extensions'=>'',
          'file_mimes'=>'', //since 2.99
        ] + $params;

        if( empty($props['create_date']) ) {
            $props['create_date'] = time();
            $props['modified_date'] = 0;
        }
        $props['id'] = (int) $props['id'];
        if( isset($params['case_sensitive']) ) {
            $props['case_sensitive'] = cms_to_bool($params['case_sensitive']);
        }
        else {
            $fp = tempnam(PUBLIC_CACHE_LOCATION, 'cased');
            $fn = $fp.'UC';
            $fh = fopen($fn, 'c');
            $props['case_sensitive'] = !is_file($fp.'uc');
            fclose($fh);
            unlink($fn);
        }
        parent::__construct($props);
    }

    /**
     * @ignore
     */
    public function __clone()
    {
        $this->id = 0;
        $this->top = null;
    }

    // NOTE immutable class, no __set()

    /**
     * @ignore
     * @param string $key
     * @return mixed
     */
    public function __get( $key )
    {
        switch( $key ) {
        case 'id':
        case 'create_date':
        case 'modified_date':
            return (int) $this->_data[$key];

        case 'name':
            return trim($this->_data[$key]);
        case 'file_extensions':
        case 'file_mimes':
            return trim($this->_data[$key], ' ,');

        case 'case_sensitive':
            return (bool) $this->_data[$key];

        case 'relative_top':
        case 'reltop':
            // parent top is checked for relative or absolute
            // return relative to uploads path
            $val = parent::__get('top');
            if( preg_match('~^ *(?:\/|\\\\|\w:\\\\|\w:\/)~', $val) ) {
               // path is absolute
                $config = AppSingle::Config();
               //TODO sometimes relative to site root
                $uploads_path = $config['uploads_path'];
                if( startswith( $val, $uploads_path ) ) { $val = substr($val,strlen($uploads_path)); }
                if( startswith( $val, DIRECTORY_SEPARATOR) ) { $val = substr($val,1); }
            }
            return $val;

        case 'top':
            // parent top is checked for relative or absolute
            // if relative, prepend uploads path
            $val = parent::__get('top');
            if( !preg_match('~^ *(?:\/|\\\\|\w:\\\\|\w:\/)~', $val) ) {
                //TODO sometimes relative to site root
                $config = AppSingle::Config();
                $val = $config['uploads_path'].DIRECTORY_SEPARATOR.$val;
            }
            return $val;

        default:
            return parent::__get($key);
        }
    }

    /**
     *
     * @param string $key
     * @param mixed $val
     */
    protected function setValue( $key, $val )
    {
        switch( $key ) {
            case 'name':
                $this->_data[$key] = trim($val);
                break;
            case 'file_extensions':
                if( is_array($val) ) {
                    $s = implode(',', $val);
                }
                else {
                    $s = (string) $val;
                }
                if( $s ) {
                    // setup for easier searching
                    $s = strtr($s, [' ' => '', '.' => '']);
                    $this->_data[$key] = ',' . $s . ',';
                }
                else {
                    $this->_data[$key] = '';
                }
                break;
            case 'file_mimes':
                if( is_array($val) ) {
                    $s = implode(',', $val);
                }
                else {
                    $s = (string) $val;
                }
                if( $s ) {
                    // setup for easier searching
                    $s = trim($s, ' ,');
                    $s = str_replace([' ,',', '], [',',','], $s);
                    $this->_data[$key] = ',' . strtolower($s) . ',';
                }
                else {
                    $this->_data[$key] = '';
                }
                break;
            case 'case_sensitive':
                $this->_data[$key] = cms_to_bool($val);
                break;
            case 'id':
            case 'create_date':
            case 'modified_date':
                $this->_data[$key] = (int) $val;
                break;
            default:
                parent::setValue($key, $val);
                break;
        }
    }

    /**
     * @return boolean
     * @throws Exception
     */
    public function validate()
    {
        if( !$this->name ) { throw new Exception(lang('errorbadname')); }
        if( $this->reltop && !is_dir($this->top) ) { throw new Exception(lang('TODOerr_profile_topdir')); }
        return true;
    }

    /**
     * Get a clone of this profile with the specified id
     * @param mixed $new_id  Optional (number >= 1.0 | numeric string >= 1.0 | null) Default null
     * @return Profile
     * @throws Exception
     */
    public function withNewId( $new_id = null )
    {
        if( !is_null($new_id) ) {
            $new_id = (int) $new_id;
            if( $new_id < 1 ) throw new Exception('Invalid id passed to '.__METHOD__);
        }
        else {
            $new_id = 0;
        }
        $obj = clone $this;
        $obj->_data['id'] = $new_id;
        $obj->_data['create_date'] = $obj->_data['modified_date'] = time();
        return $obj;
    }

    /**
     * Get a clone of this profile with replacement properties
     * @param array $params assoc. array of profile props and respective vals
     * @return Profile
     */
    public function overrideWith( array $params = [] )
    {
        $obj = clone( $this );
        foreach( $params as $key => $val ) {
            switch( $key ) {
            case 'id':
                // cannot change id this way
                break;

            case 'type':
                if( !isset($params['file_extensions']) ) {
                    $helper = new FileTypeHelper();
                    $exts = $helper->get_file_type_extensions((int)$val);
                    $obj->setValue('file_extensions',$exts);
                }
                if( !isset($params['file_mimes']) ) {
                    if (!isset($helper) ) $helper = new FileTypeHelper();
                    $mimes = $helper->get_file_type_mime((int)$val);
                    $obj->setValue('file_mimes',$mimes);
                }
                //no break here
            default:
                $obj->setValue($key,$val);
                break;
            }
        }
        return $obj;
    }

    /**
     * Get a clone of this profile with the current time as its 'modified_date' property
     * @return Profile
     */
    public function markModified()
    {
        $obj = clone $this;
        $obj->_data['modified_date'] = time();
        return $obj;
    }

    /**
     * Check whether $filename accords with relevant conditions among the profile properties
     * @since 2.99
     * @param string $filename Absolute|relative filesystem path, or just basename, of a file
     * @return boolean
     */
    public function is_file_name_acceptable($filename)
    {
        if (!parent::is_file_name_acceptable($filename)) {
            return false;
        }
        if( $this->_data['file_extensions'] === '' ) {
            return true;
        }
        // file must have acceptable extension
        $fn = basename($filename);
        $p = strrpos($fn, '.');
        try {
            if( !$p ) {
                // file has no extension, or just an initial '.'
                throw new Exception($fn.': type is not acceptable');
            }
            $ext = substr($fn, $p+1);
            if( !$ext ) {
                // file has empty extension
                throw new Exception($fn.': type is not acceptable');
            }
            $s =& $this->_data['file_extensions'];
            // we always do a caseless (hence ASCII) check,
            // cuz patterns and/or extension might be case-insensitive
            // and recognised extensions are all ASCII
            $p = stripos($s, $ext);
            if( $p !== false ) {
                if( $s[$p - 1] === ',' ) {
                    if( $s[$p + strlen($ext)] === ',' ) {
                        return true;
                    }
                }
            }
            throw new Exception($fn.': type is not acceptable');
        }
        catch (Throwable $t) {
            if( CMS_DEBUG ) { debug_to_log($t->GetMessage()); }
            return false;
        }
    }
} // class
