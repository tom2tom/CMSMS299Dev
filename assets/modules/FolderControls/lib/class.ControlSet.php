<?php
/*
A class defining folder-specific permissions and properties.
Copyright (C) 2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

namespace FolderControls;

use CmsInvalidDataException;
use CMSMS\FileType;
use Exception;
use const CMS_ROOT_PATH;
use function cms_join_path;
use function cms_to_bool;
use function startswith;

/**
 * A class defining a 'conntrolset', which is a collection of extended
 * permissions for, and properties of, a folder and (unless and until
 * countervailed by a more-folder-specific set) inherited by the folder's
 * descendants.
 *
 * @package CMS
 * @license GPL
 * @since  2.3
 * @property-read FileType $type A FileType enumerator representing files which may be used.
 * @property-read bool $can_mkdir   Authorized users can create new directories (conforming to other test(s), if any).
 * @property-read bool $can_mkfile  Authorized users can modify and create new files (conforming to other test(s), if any).
 * @property-read bool $can_delete  Authorized users can remove files and/or directories (conforming to other test(s), if any).
 * @property-read bool $show_thumbs Whether thumbnail images should be shown in place of normal icons for images.
 * @property-read bool $show_hidden Indicates that hidden files should be shown.
 * @property-read mixed $sort Indicates whether and how files should be sorted before listing them.
 *   false, or one of 'name','size','created','modified' & optionally appended ',a[sc]' or ',d[esc]'
 * @property-read array $match_patterns Process only files/items whose name matches any of these pattern(s).
 * @property-read array $exclude_patterns Exclude from processing any files/items whose name matches any of these pattern(s).
 * @property-read array $match_groups  Group-id(s) which are permitted to perform the suitably-flagged operations defined in the set. Default ['*']
 * @property-read array $exclude_groups  Group-id(s) which may not perform the suitably-flagged operations defined in the set. Default []
 * @property-read array $match_users   User-id(s) which are permitted to perform the suitably-flagged operations defined in the set. Default ['*']
 * @property-read array $exclude_groups User-id(s) which may not perform the suitably-flagged operations defined in the set. Default []
 */
class ControlSet
{
    /**
     * @ignore
     */
    protected $_id = null;
    protected $_topdir = null; // CMS_ROOT_PATH-relative filepath
    protected $_data; // other specific properties array

    /**
     * Constructor
     *
     * @param array $params Optional associative array used for re-populating
     * @throws CmsInvalidDataException
     * some/all of $_data
     */
    public function __construct(array $params = [])
    {
        $this->_data = $this->defaults();
        if ($params) {
            $this->setRawData($params);
        }
    }

    /**
     * @ignore
     */
    public function __clone()
    {
        $this->_id = null;
        $this->_topdir = null;
    }

    /**
     * @ignore
     */
    public function __get(string $key)
    {
        switch ($key) {
            case 'top_dir':
            case 'top':
            case 'reltop':
                return $this->_topdir;
            case 'sort':
            case 'sort_by':
                return trim($this->_data[$key]);
            case 'can_upload':
                $key = 'can_mkfile';
                // no break
            case 'can_mkdir':
            case 'can_mkfile':
            case 'can_delete':
            case 'show_thumbs':
            case 'show_hidden':
                return (bool) $this->_data[$key];

            default:
                return $this->_data[$key] ?? null;
        }
    }

    /**
     * @ignore
     */
    public function __set(string $key, $val)
    {
        switch ($key) {
            case 'top_dir':
            case 'top':
            case 'reltop':
                if (!startswith($val, CMS_ROOT_PATH)) {
                    $val = cms_join_path(CMS_ROOT_PATH, $val);
                }
                $flag = is_dir($val);
                $val = trim(substr($val, strlen(CMS_ROOT_PATH)), ' \//');
                if ($flag) {
                    $this->_topdir = $val;
                } else {
                    throw new CmsInvalidDataException("$val is not a valid directory, in ".__CLASS__);
                }
                break;
            case 'can_upload':
                $key = 'can_mkfile';
                // no break
            case 'can_mkdir':
            case 'can_mkfile':
            case 'can_delete':
            case 'show_thumbs':
            case 'show_hidden':
                $this->_data[$key] = cms_to_bool($val);
                break;
            case 'type':
                $key = 'file_types';
                // no break
            case 'file_types':
                if (!is_array($val)) {
                    $val = [$val];
                }
                $res = [];
                foreach ($val as $type) {
                    $type = trim($type);
                    //TODO FileType::isValidName($type);
                    //TODO FileType::getNames();
//                  switch ($type) {
                        $res[] = $type;
                        break;
//                  }
                }
                break;
            case 'sort':
                $key = 'sort_by';
                // no break
            case 'sort_by':
                //TODO relevant checks name|size|created|modified|date [a[sc] d[esc]]
                $this->_data[$key] = $val;
                break;
            case 'exclude_groups':
            case 'exclude_users':
            case 'exclude_pattern':
            case 'match_groups':
            case 'match_users':
            case 'match_pattern':
                if (!is_array($val)) {
                    $val = [$val];
                }
                $this->_data[$key] = $val;
                break;
            case 'id':
                $this->_id = (int)$val;
                break;
            default:
                throw new CmsInvalidDataException("$key is not a valid property of ".__CLASS__);
        }
    }

    /**
     * Return the default properties of each ControlSet
     *
     * @return array
     */
    public function defaults() : array
    {
        return [
            'can_delete'=>true,
            'can_mkdir'=>true,
            'can_mkfile'=>true,
            'exclude_groups'=>[], //array of group-id's
            'exclude_users'=>[],  //array of user-id's
            'exclude_patterns'=>[], //array of regex's - barred item-names
            'file_types'=>[FileType::ANY], //array of acceptable type-enumerators
            'match_groups'=>[], //array of group-id's
            'match_users'=>[], //array of user-id's
            'match_patterns'=>[], //array of regex's - acceptable item-names
            'show_hidden'=>false,
            'show_thumbs'=>true,
            'sort_by'=>'name', // item-property - name,size,created,modified perhaps + [,a[sc]] | ,d[esc]
        ];
    }

    /**
     * Return all the properties of this set
     *
     * @return array
     */
    public function getRawData() : array
    {
        return $this->_data;
    }

    /**
     * Set multiple properties of this set
     *
     * @param array $data
     */
    public function setRawData(array $data)
    {
		foreach ($data as $key=>$val) {
		    try {
		        $this->__set($key, $val);
		    } catch (Exception $e) {
		    }
		}
    }
} // class
