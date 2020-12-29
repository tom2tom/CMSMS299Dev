<?php
/*
Class defining folder-specific properties and permissions
Copyright (C) 2016-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CmsInvalidDataException;
use CMSMS\AppSingle;
use CMSMS\FileType;
use CMSMS\FSControlValue;
use Exception;
use Throwable;
use const CMS_DEBUG;
use const CMS_ROOT_PATH;
use function cms_to_bool;
use function debug_to_log;
use function startswith;

/**
 * A class that defines a suite of properties and permissions, a bit like OS
 * access controls. For use by e.g. a filepicker to indicate how it should
 * behave and what functionality should be provided.
 *
 * This is an immutable class. (security?)
 *
 * The constructor and overrideWith methods of this class accept an associative
 * array of parameters (see the properties below) to allow building or altering
 * a profile object. Those are the only ways that properties of a profile can be adjusted.
 *
 * ```php
 * $obj = new CMSMS\FileSystemControls(
 *  [ 'type'=>FileType::TYPE_IMAGE,
 *    'exclude_prefix'=>'foo'
 *  ]);
 *
 * @package CMS
 * @license GPL
 * @author Robert Campbell <calguy1000@cmsmadesimple.org>
 * @since 2.99
 * @since 2.2 as FilePickerProfile
 * @property-read string $top The top directory for the filepicker (relative to the CMSMS uploads directory, or ... TODO)
 * @property-read FileType $type A FileType enumerator representing files which may be used.
 * @property-read string $typename enum identifier corresponding to $type.
 * @property-read string $match_prefix Identifier of filenames to be processed. Default ''
 * @property-read string exclude_prefix  Identifier of filenames to be skipped. Default ''
 * @property-read int $can_mkdir  Whether new directories may be created here.
 * @property-read int $can_upload  Whether new files (of the specified type) may be uploaded to here.
 * @property-read int $can_delete  Whether files/folders may be removed from here.
 * @property-read bool $show_thumbs Whether image thumbnails should be used. Default true.
 * @property-read bool $show_hidden Whether hidden files should be included when processing folder content. Default false.
 * @property-read bool $sort Whether files here should be sorted before listing. Default true.
 */
class FileSystemControls
{
    /**
     * @ignore
     * Constants deprecated since 2.99. Instead use corresponding FSControlValue
     */
    const FLAG_NONE = 0;
    const FLAG_NO = 0;
    const FLAG_YES = 1;
    const FLAG_BYGROUP = 2;

    /**
     * @ignore
     */
    protected $_data = [
       'top'=>'',
       'type'=>FileType::ANY,
       'can_upload'=>FSControlValue::YES,
       'can_delete'=>FSControlValue::YES,
       'can_mkdir'=>FSControlValue::YES,
       'match_prefix'=>'',
       'exclude_prefix'=>'',
       'show_thumbs'=>true,
       'show_hidden'=>false,
       'sort'=>true
    ];

    /**
     * Constructor
     *
     * @param array $params Associative array of params suitable for the setValue method
      */
    public function __construct($params)
    {
        if( empty($params) || !is_array($params) || count($params) == 0 ) return;
        foreach( $params as $key => $val ) {
            $this->setValue($key,$val);
        }
        if( !$this->_data['top'] ) {
            $config = AppSingle::Config();
            $this->setValue('top',$config['uploads_path']);
//          $devmode = $config['develop_mode'] || (($userid = get_userid(false) && check_permission($userid,'Modify Restricted Files')) || $userid == 0);
//          $toppath = ($devmode) ? CMS_ROOT_PATH : $config['uploads_path'];
        }
    }

    // NOTE immutable class, no __set()

    /**
     * @ignore
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        switch( $key ) {
        case 'top':
            return trim($this->_data[$key]);

        case 'type': // FileType enum member
        case 'can_mkdir': // FSControlValue::* value, sometimes non-0 handled just as true
        case 'can_upload':
        case 'can_delete':
            return (int) $this->_data[$key];

        case 'show_thumbs':
        case 'show_hidden':
        case 'sort':
            return (bool) $this->_data[$key];

        case 'typename':
            return FileType::getName($this->_data['type']);
        }
    }

    /**
     * Set a value into this profile
     *
     * @param string $key The key to set
     * @param mixed $val The value to set
     */
    protected function setValue($key, $val)
    {
        switch( $key ) {
        case 'top':
           $s = trim($val);
           //TODO other relevant top-path-checks
           if( startswith($s,CMS_ROOT_PATH) ) {
               $this->_data[$key] = $s;
               break;
           }
           throw new CmsInvalidDataException("$s is not a valid value for $key in ".self::class);
        case 'match_prefix':
        case 'exclude_prefix':
            $this->_data[$key] = trim($val);
            break;

        case 'type':
            if( is_numeric($val) ) {
                $n = (int)$val;
                if( FileType::isValidValue($n) ) {
                    $this->_data[$key] = $n;
                    break;
                }
                throw new CmsInvalidDataException("$val is not a valid value for $key in ".self::class);
            }
            // no break here
        case 'typename':
            $s = strtoupper(trim($val));
            if( ($n = FileType::getValue($s)) !== null ) {
                $this->_data['type'] = $n;
                break;
            }
            throw new CmsInvalidDataException("$val is not a valid value for $key in ".self::class);

        case 'can_mkdir':
        case 'can_delete':
        case 'can_upload':
            if( is_string($val) ) {
                $n = (cms_to_bool($val)) ? FSControlValue::YES : FSControlValue::NO;
            } else {
                $n = (int) $val;
            }
            switch( $n ) {
            case FSControlValue::NO:
            case FSControlValue::YES:
            case FSControlValue::BYGROUP:
                $this->_data[$key] = $val;
                break;
            default:
                throw new CmsInvalidDataException("$val is not a valid value for $key in ".self::class);
            }
            break;

        case 'sort':
           //TODO relevant checks
        case 'show_thumbs':
        case 'show_hidden':
            $this->_data[$key] = cms_to_bool( $val );
            break;
        }
    }

    /**
     * Create a new profile object based on the current one, with
     *  property-adjustments per $params.
     *
     * @param array $params Associative array of parameters for the setValue method
     * @return self (FileSystemControls)
     */
    public function overrideWith(array $params)
    {
        $obj = clone $this;
        foreach( $params as $key => $val ) {
            $obj->setValue( $key, $val );
        }
        return $obj;
    }

    /**
     * Get the raw data of this profile
     *
     * @return array
     */
    public function getRawData()
    {
        return $this->_data;
    }

   /**
    * Helper function: check for a match between $pattern and $name
    * Tries wildcard, regex and literal name-matching, case-insensitive
    * @since 2.99
    * @param string pattern
    * @param string name
    * @return bool indicating whether they match
    */
    protected function getMatch($pattern, $name)
    {
        if( !($pattern || is_numeric($pattern)) ) {
            return true;
        }
        if( 0 ) { //some mb_* method $name contains non-ASCII
            // TODO robust caseless name startswith pattern
        }
        if( preg_match('/[*?]/', $pattern) ) {
            $s = rtrim($pattern, ' *');
            if( fnmatch($s.'*', $name,
            FNM_NOESCAPE | FNM_PATHNAME | FNM_PERIOD | FNM_CASEFOLD) ) {
                return true;
            }
        }
        if( strpbrk($pattern, '[({^|*+-.,$') !== false ) {
            $s = trim($pattern, '^$ ');
            if( preg_match('/^'.$s.'.*$/i', $name) ) {
                return true;
            }
        }
        $l = strlen($pattern);
        if( strncasecmp($name, $pattern, $l) === 0 ) {
            return true;
        }
        //etc?
        return false;
    }

    /**
     * Check whether $filename accords with relevant conditions among the profile properties
     * @since 2.99 (migrated from sub-class)
     * @param string $filename Absolute|relative filesystem path, or just basename, of a file
     * @return boolean
     */
    public function is_file_name_acceptable($filename)
    {
        $fn = basename($filename);
        try {
            if( !$this->_data['show_hidden'] && ($fn[0] === '.' || $fn[0] === '_') ) {
                throw new Exception($fn.': name is not acceptable');
            }

            if( !$this->getMatch($this->_data['match_prefix'], $fn) ) {
                throw new Exception($fn.': name is not acceptable');
            }

            if( $this->_data['exclude_prefix'] ) {
                if( $this->getMatch($this->_data['exclude_prefix'], $fn) ) {
                    throw new Exception($fn.': name is not acceptable');
                }
            }
            return true;
        }
        catch (Throwable $t) {
            if( CMS_DEBUG ) { debug_to_log($t->GetMessage()); }
            return false;
        }
    }
} // class
