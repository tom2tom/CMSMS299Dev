<?php
/*
Class defining folder-specific properties and permissions
Copyright (C) 2016-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS;

use CMSMS\DataException;
use CMSMS\FileType;
//use CMSMS\FileTypeHelper;
use CMSMS\FSControlValue;
use CMSMS\Lone;
use Exception;
use UnexpectedValueException;
use const CMS_ROOT_PATH;
use const PUBLIC_CACHE_LOCATION;
//use function check_permission;
use function cms_to_bool;
//use function get_userid;
use function startswith;

/**
 * A class that defines a suite of properties and permissions, a bit like
 * OS access controls. For use by e.g. a filepicker to indicate how it
 * should behave and what functionality should be provided.
 *
 * ```php
 * $obj = new CMSMS\FolderControls(
 *  [ 'type'=>FileType::TYPE_IMAGE,
 *    'exclude_prefix'=>'foo'
 *  ]);
 *
 * @package CMS
 * @license GPL
 * @since 3.0
 * @since 2.2 as FilePickerProfile
 */
class FolderControls
{
    /**
     * @ignore
     * Constants deprecated since 3.0. Instead use corresponding FSControlValue
     */
    const FLAG_NONE = 0;
    const FLAG_NO = 0;
    const FLAG_YES = 1;
    const FLAG_BYGROUP = 2;

    /**
     * @ignore
     * This object's properties may be supplemented by a subclass or by
     * anything via setValue()
     */
    protected $data = [];

    /**
     * Constructor
     *
     * @param array $params Associative array of profile properties to
     *  be used instead of class-defaults
     */
    public function __construct($params = [])
    {
        $longnow = date('Y-m-d H:i:s', time()); // i.e. $db->DbTimeStamp(time())
        $this->data = array_merge([
         'id'=>0, // if the profile data are db-stored, this is for the row-index
         'name'=>'',
         'can_delete'=>FSControlValue::YES,
         'can_mkdir'=>FSControlValue::YES,
         'can_mkfile'=>FSControlValue::YES,
         'can_upload'=>FSControlValue::YES,
         'case_sensitive'=>FSControlValue::NO,
         'create_date'=>$longnow,
         'exclude_groups'=>[], //array of group-id's
         'exclude_patterns'=>[], //array of regex's - barred item-names
         'exclude_prefix'=>'', // deprecated, unused since 3.0 see exclude_pattern
         'exclude_users'=>[],  //array of user-id's
         'file_extensions'=>'', // deprecated, unused since 2.99s see match_pattern allowed_types etc
         'file_mimes'=>'', //since 2.0
         'file_types'=>[FileType::ANY], //array of acceptable type-enumerators
         'match_groups'=>[], //array of group-id's
         'match_patterns'=>[], //array of regex's representing acceptable item-names
         'match_prefix'=>'', // deprecated, unused since 3.0 see match_pattern allowed_types etc
         'match_users'=>[], //array of user-id's
         'modified_date'=>null,
         'reltop'=>'', // topmost (aka 'senior') filepath relative to website root, generally the name of the uploads folder
         'show_hidden'=>FSControlValue::NO,
         'show_thumbs'=>FSControlValue::YES,
         'sort'=>FSControlValue::YES, // deprecated, unused since 3.0 see sort_asc etc
         'sort_asc'=>FSControlValue::YES,
         'sort_by'=>'name', // item-property - name,size,created,modified + [a[sc]] | d[esc]
         'type'=>FileType::ANY,
         'typename'=>'ANY',
        ], $params);

        $this->data['id'] = (int)$this->data['id'];
        if( isset($params['case_sensitive']) ) {
            $val = cms_to_bool($this->data['case_sensitive']);
            $this->data['case_sensitive'] = ($val) ? FSControlValue::YES : FSControlValue::NO;
        }
        else {
            $fp = tempnam(PUBLIC_CACHE_LOCATION, 'cased');
            $fn = $fp.'UC';
            $fh = fopen($fn, 'c');
            $val = !is_file($fp.'uc');
            fclose($fh);
            unlink($fn);
            $this->data['case_sensitive'] = ($val) ? FSControlValue::YES : FSControlValue::NO;
        }
        if( isset($params['top']) ) {
            unset($this->data['top']);
            $params['reltop'] = $params['top'];
        }
        if( !empty($params['reltop']) ) {
            $s = $this->rel_top($params['reltop']);
            if( $s !== null ) {
                $this->data['reltop'] = $s;
            }
        }
    }

    /**
     * Try to interpret a suitable root-relative folder
     * @param string $path
     * @return mixed string (maybe empty) | null signals failure
     */
    private function rel_top(string $path)
    {
        $s = trim($path);
        if( $s === '' ) {
            return $s;
        }
        elseif( startswith($s, CMS_ROOT_PATH) ) {
            if( is_dir($s) ) {
               $s = substr($s, strlen(CMS_ROOT_PATH));
               return ltrim($s, ' \/');
            }
        }
        elseif( preg_match('~^ *(?:\/|\\\\|\w:\\\\|\w:\/)~', $s) ) {
            // path is absolute
            $s = preg_replace('~^\s*[a-zA-Z]\s*:~', '', $s);
            $s = ltrim($s, ' \/');
            if( is_dir(CMS_ROOT_PATH.DIRECTORY_SEPARATOR.$s) ) {
                return $s;
            }
        }
        elseif( is_dir(CMS_ROOT_PATH.DIRECTORY_SEPARATOR.$s) ) {
            return $s;
        }
        else {
            $ups = Lone::get('Config')['uploads_path'];
            if (is_dir(CMS_ROOT_PATH.DIRECTORY_SEPARATOR.$ups.DIRECTORY_SEPARATOR.$s) ) {
                return $ups.DIRECTORY_SEPARATOR.$s;
            }
        }
        return null; //failure
    }

    /**
     * @ignore
     */
    #[\ReturnTypeWillChange]
    public function __clone()
    {
        $this->data['id'] = 0;
        $this->data['create_date'] = date('Y-m-d H:i:s', time());
        $this->data['modified_date'] = null;
    }

    /**
     * @ignore
     * @param string $key Property name
     * @param mixed $val Property value
     */
    #[\ReturnTypeWillChange]
    public function __set(string $key, $val)
    {
        // TODO all 3.0 props
        switch( $key ) {
        case 'id':
            if( !empty($this->data[$key]) ) {
                throw new LogicException('The id of a recorded control-set may not be changed');
            }
            $this->data[$key] = (int)$val;
            break;
        case 'name':
            $this->data[$key] = trim($val);
            break;
        case 'match_prefix':
        case 'exclude_prefix':
            $this->data[$key] = trim($val);
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
                $this->data[$key] = ',' . $s . ',';
            }
            else {
                $this->data[$key] = '';
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
                $this->data[$key] = ',' . strtolower($s) . ',';
            }
            else {
                $this->data[$key] = '';
            }
            break;
        case 'type':
            if( is_numeric($val) ) {
                $n = (int)$val;
                if( FileType::isValidValue($n) ) {
                    $this->data[$key] = $n;
                    $this->data['typename'] = FileType::getName($n);
                    break;
                }
                throw new UnexpectedValueException("'$val' is not a valid value for '$key' in a ".__CLASS__.' object');
            }
            // no break here
        case 'typename':
            $s = strtoupper(trim($val));
            if( ($n = FileType::getValue($s)) !== null ) {
                $this->data['type'] = $n;
                $this->data[$key] = $s;
                break;
            }
            throw new UnexpectedValueException("'$val' is not a valid value for '$key' in a ".__CLASS__.' object');
        case 'case_sensitive':
            $val = cms_to_bool($val);
            $this->data[$key] = ($val) ? FSControlValue::YES : FSControlValue::NO;
            break;
        case 'can_mkdir':
        case 'can_mkfile':
        case 'can_upload':
        case 'can_delete':
        case 'show_thumbs':
        case 'show_hidden':
        case 'sort':
            if( is_string($val) ) {
                $n = (cms_to_bool($val)) ? FSControlValue::YES : FSControlValue::NO;
            } else {
                $n = (int)$val;
            }
            switch( $n ) {
            case FSControlValue::NO:
            case FSControlValue::YES:
            case FSControlValue::BYGROUP:
                $this->data[$key] = $val;
                break;
            default:
                throw new UnexpectedValueException("'$val' is not a valid value for '$key' in a ".__CLASS__.' object');
            }
            break;
        case 'reltop':
        case 'relative_top':
        case 'top': // accepted aliases (for setting only)
        case 'topdir':
            $s = $this->rel_top($val);
            if( $s !== null ) {
                $this->data['reltop'] = $s;
                break;
            }
            throw new UnexpectedValueException("'$val' is not a valid value for '$key' in a ".__CLASS__.' object');
        case 'create_date':
            if( !empty($this->data[$key]) ) {
                throw new LogicException('The creation datetime of an existing control-set may not be changed');
            }
            // no break here
        case 'modified_date':
            // TODO prevent mod <= create
            if( is_int($val) ) {
                if( $val > 0 ) {
                    $this->data[$key] = date('Y-m-d H:i:s', time()); // i.e. $db->DbTimeStamp(time())
                }
                else {
                    $this->data[$key] = null;
                }
            }
            elseif ($val) {
                $this->data[$key] = trim($val);
            }
            else {
                $this->data[$key] = null;
            }
            break;

        default:
            $this->data[$key] = $val;
        }
    }

    /**
     * @ignore
     * @param string $key
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function __get(string $key)
    {
        // TODO all 3.0 props
        switch( $key ) {
        case 'id':
        case 'can_mkdir': // FSControlValue::* value, sometimes non-0 handled just as true
        case 'can_mkfile': // FSControlValue::* value, sometimes non-0 handled just as true
        case 'can_upload':
        case 'can_delete':
        case 'show_thumbs':
        case 'show_hidden':
        case 'case_sensitive':
        case 'sort':
        case 'type': // FileType enum member
            return (int)$this->data[$key];
        case 'name':
        case 'match_prefix':
        case 'exclude_prefix':
            return trim($this->data[$key]);
        case 'typename':
            if( empty($this->data[$key]) ) {
               $this->data[$key] = FileType::getName($this->data['type']);
            }
            return $this->data[$key];
        case 'create_date':
        case 'modified_date':
            return (!empty($this->data[$key])) ? $this->data[$key] : 'Unspecified';
        case 'file_extensions':
        case 'file_mimes':
            return trim($this->data[$key], ' ,');
        case 'reltop':
        case 'relative_top':
            return trim($this->data['reltop']);
        case 'top':
        case 'topdir':
            return CMS_ROOT_PATH.DIRECTORY_SEPARATOR.trim($this->data['reltop']);
        default:
            return $this->data[$key] ?? null;
        }
    }

    /**
     * Set a property of this profile.
     * Since 3.0 just an alias for __set(). Use that instead.
     *
     * @param string $key Property name
     * @param mixed $val Property value
     */
    public function setValue($key, $val)
    {
        $this->__set($key, $val);
    }

    /**
     * Get all the properties of this profile
     *
     * @return array
     */
    public function getRawData()
    {
        return $this->data;
    }

    /**
     * Minimally check properties of this profile. Pretty weak, really.
     * @return boolean
     * @throws DataException or Exception
     */
    public function validate()
    {
        if( !$this->data['name'] ) {
            throw new DataException('No name-property provided to '.__METHOD__);
        }
        if( $this->data['reltop'] && !is_dir(CMS_ROOT_PATH.DIRECTORY_SEPARATOR.$this->data['reltop']) ) {
            throw new Exception('Invalid directories arrangement in '.__CLASS__);
        }
        return true;
    }

    /**
     * Get a clone of this profile
     * @param mixed $new_id ignored since 3.0
     *  Setting a specific id is automatic when a set is saved, and prohibited otherwise
     * @return self
     */
    public function withNewId($new_id = null)
    {
        return clone $this;
    }

    /**
     * Get a clone of this profile with allowed replacement properties
     * @param array $params assoc. array of profile props and respective values
     * Props id, create_date are ignored (not to be manually set)
     * @return self
     * @throws UnexpectedValueException
     */
    public function overrideWith(array $params = [])
    {
        $obj = clone $this;
        $keeps = array_diff_key($params, ['id'=>1, 'create_date'=>1]);
        foreach( $keeps as $key => $val ) {
            $obj->$key = $val;
        }
        return $obj;
    }

    /**
     * Get a clone of this profile with the current time as its 'modified_date' property
     * @return self
     */
    public function markModified()
    {
        $obj = clone $this;
        $obj->modified_date = date('Y-m-d H:i:s', time());
        return $obj;
    }
} // class
