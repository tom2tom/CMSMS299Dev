<?php
# Class defining folder-specific properties and roles
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

use cms_config;
use CMSMS\FileSystemProfile;
use Exception;
use LogicException;
use const TMP_CACHE_LOCATION;
use function cms_to_bool;
use function startswith;

class ProfileException extends Exception {}

class Profile extends FileSystemProfile
{
    /**
     * Constructor
     * @internal
     * @param array $params optional assoc array of profile props and vals. Default []
     */
    public function __construct( array $params = [] )
    {
        $props = [
          'create_date'=>0,
          'modified_date'=>0,
          'file_extensions'=>'',
          'file_mimes'=>'', //since 2.3
          'id'=>0, //CHECKME just an index?
          'case_sensitive'=>true, //since 2.3
          'name'=>'',
        ] + $params;

        $props['id'] = (int) $props['id'];
        if( isset($params['case_sensitive']) ) {
            $props['case_sensitive'] = cms_to_bool($params['case_sensitive']);
        }
        else {
            $fp = tempnam(TMP_CACHE_LOCATION, ''); // or PUBLIC_CACHE_LOCATION
            $fn = $fp.'UC';
            $fh = fopen($fn, 'c');
            $props['case_sensitive'] = !is_file($fp.'uc');
            fclose($fh);
            unlink($fn);
        }
        parent::__construct($props);
    }

    /**
     *
     * @param string $key
     * @return string
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
                $config = cms_config::get_instance();
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
                $config = cms_config::get_instance();
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
                // setup for easier searching
                $s = strtr($s, [' ' => '', '.' => '']);
                $this->_data[$key] = ',' . $s . ',';
                break;
            case 'file_mimes':
                if( is_array($val) ) {
                    $s = implode(',', $val);
                }
                else {
                    $s = (string) $val;
                }
                // setup for easier searching
                $s = trim($s, ' ,');
                $s = str_replace([' ,',', '], [',',','], $s);
                $this->_data[$key] = ',' . strtolower($s) . ',';
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
                parent::setValue( $key, $val );
                break;
        }
    }

	/**
     * @return boolean
	 * @throws ProfileException
	 */
    public function validate()
    {
		if( !$this->name ) { throw new ProfileException('err_profile_name'); }
		if( $this->reltop && !is_dir($this->top) ) { throw new ProfileException('err_profile_topdir'); }
		return true;
    }

    /**
     * Get a clone of this profile with the specified id
     * @param mixed $new_id  Optional (number >= 1.0 | numeric string >= 1.0 | null) Default null
     * @return Profile
     * @throws LogicException
     */
    public function withNewId( $new_id = null )
    {
        if( !is_null($new_id) ) {
            $new_id = (int) $new_id;
            if( $new_id < 1 ) throw new LogicException('Invalid id passed to '.__METHOD__);
        }
        $obj = clone $this;
        $obj->_data['id'] = $new_id;
        $obj->_data['create_date'] = $obj->_data['modified_date'] = time();
        return $obj;
    }

    /**
     * Get a clone of this profile with replacement properties
     * @param array $params assoc. array of profile props and their vals
     * @return Profile
     */
    public function overrideWith( array $params = [] )
    {
        $obj = clone( $this );
        foreach( $params as $key => $val ) {
            switch( $key ) {
            case 'id':
                // cannot set a new id this way
                break;

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
} // class
