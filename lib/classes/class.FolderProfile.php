<?php
/*
A class for working with folder-specific permissions and properties.
Copyright (C) 2018 Robert Campbell <calguy1000@cmsmadesimple.org>
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

namespace CMSMS;

use CmsApp;
use CmsInvalidDataException;
use const CMS_DB_PREFIX;
use const CMS_ROOT_PATH;
use function cms_join_path;
use function startswith;

/**
 * A class for working with 'profiles'. A profile is a set of extended
 * permissions for, and properties of, a folder and (unless and until
 * countervailed by a more-folder-specific profile) inherited by the
 * folder's descendants.
 *
 * @package CMS
 * @license GPL
 * @author Robert Campbell <calguy1000@cmsmadesimple.org>
 * @since  2.3
 * @property-read string $top Removed since 2.3, in favour of a stand-alone property
 * @property-read FileType $type A FileType enumerator representing files which may be used.
 * @property-read string $match_prefix List only files/items that have the specified prefix.
 * @property-read string exclude_prefix  Exclude any files/items that have the specified prefix.
 * @property-read int $can_mkdir   Authorized users can create new directories (conforming to other test(s), if any).
 * @property-read int $can_mkfile Since 2.3 Authorized users can create new files (conforming to other test(s), if any).
 * @property-read int $can_upload Deprecated 2.3 use $can_mkfile Authorized users can upload new files (conforming to other test(s), if any).
 * @property-read int $can_delete  Authorized users can remove files and/or directories (conforming to other test(s), if any).
 * @property-read int $show_thumbs Whether thumbnail images should be shown in place of normal icons for images.
 * @property-read int $show_hidden Indicates that hidden files should be shown.
 * @property-read mixed $sort Indicates whether and how files should be sorted before listing them.
 *   FolderProfile::FLAG_NO, or one of 'name','size','date' & optionally appended ',a[sc]' or ',d[esc]'
 * @property-read array $match_groups Since 2.3 group-id's which are permitted to perform the suitably-flagged operations defined in the profile. Default ['*']
 * @property-read array $exclude_groups Since 2.3 group-id's which may not perform the suitably-flagged operations defined in the profile. Default []
 * @property-read array $match_users  Since 2.3 user-id's which are permitted to perform the suitably-flagged operations defined in the profile. Default ['*']
 * @property-read array $exclude_groups Since 2.3 user-id's which may not perform the suitably-flagged operations defined in the profile. Default []
 */
class FolderProfile
{
    const FLAG_NONE = 0;
    const FLAG_NO = 0;
    const FLAG_YES = 1;
    const FLAG_BYGROUP = 2;
    const FLAG_BYUSER = 3;
    const FLAG_BYGRPANDUSR = 4;

    /**
     * @ignore
     */
    protected $_id = null;
    protected $_topdir = null; // CMS_ROOT_PATH-relative filepath
    protected $_data;
    private $_allcache = []; // each key=toppath, val=db-row
    private $_cache = []; //key=supplied-path, val=matching _data array

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
        foreach ($params as $key => $val) {
            $this->__set($key, $val);
        }
    }

    /**
     * @ignore
     */
    private function defaults()
    {
        return [
            'can_delete'=>self::FLAG_YES,
            'can_mkdir'=>self::FLAG_YES,
            'can_mkfile'=>self::FLAG_YES,
            'exclude_groups'=>[], //array of group-id's
            'exclude_users'=>[],  //array of user-id's
            'exclude_patterns'=>[], //array of regex's - barred item-names
            'match_groups'=>[], //array of group-id's
            'match_users'=>[], //array of user-id's
            'match_patterns'=>[], //array of regex's - acceptable item-names
            'show_hidden'=>self::FLAG_NO,
            'show_thumbs'=>self::FLAG_YES,
            'sort_by'=>'name', // item-property - name,size,created,modified
            'file_types'=>[FileType::ANY], //array of acceptable type-enumerators
        ];
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
                $this->_data[$key] = ($val) ? self::FLAG_YES : self::FLAG_NO;
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
//                    switch ($type) {
                            $res[] = $type;
                            break;
//                    }
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
                throw new CmsInvalidDataException("$key is not a valid property in ".__CLASS__);
        }
    }

    /**
     * @ignore
     */
    public function __clone()
    {
        $this->_id = null;
        $this->_topdir = null;
        $this->_allcache = [];
        $this->_cache = [];
    }

    /**
     * Set a property-value in this profile
     *
     * @param string $key The key to set
     * @param mixed $val The value to set
     * @return bool indicating success
     */
    protected function setValue(string $key, $val) : bool
    {
        try {
            $this->__set($key, $val);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Return all the properties of this profile
     *
     * @return array
     */
    public function getRawData() : array
    {
        return $this->_data;
    }

    /**
     * Create a new FolderProfile-object based on the current one, with
     *  property-adjustments per $params.
     *
     * @param array $params Optional associative array of parameters
     * used for re-populating some/all of the object's $_data
     *
     * @return FolderProfile object
     */
    public function cloneWith(array $params = []) : self
    {
        $obj = clone $this;
        foreach ($params as $key => $val) {
            try {
                $obj->__set($key, $val);
            } catch (Exception $e) {
            }
        }
        return $obj;
    }

    /**
     * @deprecated since 2.3 use cloneWith()
     */
    public function overrideWith(array $params = []) : self
    {
        return $this->cloneWith($params);
    }

    /**
     * Populate this object's properties from stored data for the named profile
     *
     * @param mixed $id Profile identifier, either numeric id or string name
     * @return bool indicating success
     */
    public function load($id) : bool
    {
        if (is_numeric($id)) {
            $sql = 'SELECT id,toppath,data FROM '.CMS_DB_PREFIX.'controlsets WHERE id=?';
        } else {
            $sql = 'SELECT id,toppath,data FROM '.CMS_DB_PREFIX.'controlsets WHERE name=?';
        }
        $db = CmsApp::get_instance()->GetDb();
        $row = $db->GetRow($sql,[$id]);
        if ($row) {
            $data = json_decode($row['data'], true);
            if ($data !== null) {
                $this->_id = (int)$row['id'];
                $this->_topdir = (string)$row['toppath'];
                $this->_data = array_merge_recursive($this->defaults(), $data);
                return true;
            }
        }
        return false;
    }

    /**
     * Save (upsert) this object's properties
     *
     * @param mixed $id Profile identifier, null to use the identifier of
     *  previously-loaded data, otherwise a numeric id or string name
     * @return bool indicating success
     */
    public function save($id) : bool
    {
        if (is_null($id) && $this->_id !== null) {
            $id = $this->_id;
        }
        if (is_numeric($id)) {
            $sql = 'UPDATE '.CMS_DB_PREFIX.'controlsets SET toppath=?,data=?,modified_date=? WHERE id=?';
        } else {
            $id = filter_var(strtr($id, ' ', '_'), FILTER_SANITIZE_STRING);
            $sql = 'UPDATE '.CMS_DB_PREFIX.'controlsets SET toppath=?,data=?,modified_date=? WHERE name=?';
        }
        $raw = json_encode($this->_data);
        $db = CmsApp::get_instance()->GetDb();
        $now = $db->DbTimeStamp(time());
        $db->Execute($sql,[$this->_topdir, $raw, $now, $id]);

        if (is_numeric($id)) {
            $name = 'Unnamed_profile_'.$id;
        } else {
            $name = $id;
        }
        $pref = CMS_DB_PREFIX;
        $sql = <<<EOS
INSERT INTO {$pref}controlsets (name,toppath,data,create_date,modified_date) SELECT ?,?,?,?,? FROM (SELECT 1 AS dmy) Z
WHERE NOT EXISTS (SELECT 1 FROM {$pref}controlsets T WHERE T.name=?)
EOS;
        $db->Execute($sql,[$name, $this->_topdir, $raw, $now, $now, $name]);
        return true;
    }

    /**
     * Retrieve all (raw) data for all recorded profiles. Each returned row
     * includes a 'data' member, whose value is an array of specific properties.
     *
     * @return array
     */
    public function get_all() : array
    {
        $sets = [];
        $db = CmsApp::get_instance()->GetDb();
        $data = $db->GetArray('SELECT * FROM '.CMS_DB_PREFIX.'controlsets ORDER BY name');
        if ($data) {
            foreach ($data as &$row) {
                $row['data'] = json_decode($row['data'], true);
                $sets[] = $row;
            }
            unset($row);
        }
        return $sets;
    }

    /**
     * Retrieve properties for $path
     *
     * @param string $path Absolute or site-root-relative filesystem path of directory
     * @param int $default Optional numeric identifier of the default profile
     *  to be used if no specific profile is found.
     * @return array [$id=>$data], or empty
     */
    public function get_for_folder(string $path, int $default = -1) : array
    {
        if (startswith($path, CMS_ROOT_PATH)) {
            $path = substr($path, strlen(CMS_ROOT_PATH));
        }

        $path = trim($path, ' /\\');
        if ($this->_cache && key($this->_cache) == $path) {
            return $this->_cache; // same $path as last-processed
        }

        if (!$this->_allcache) {
            $db = CmsApp::get_instance()->GetDb();
            $this->_allcache[] = $db->GetAssoc('SELECT toppath,id,data FROM '.CMS_DB_PREFIX.'controlsets ORDER BY toppath');
        }
        // no gain here from a file-cache per the cms_filecache_driver class
        if ($this->_allcache) {
            $lt = strlen($path);
            $lb = -1;
            $params = null;
            foreach ($this->_allcache as $tp=>&$row) {
                $ls = strlen($tp);
                if ($ls >= $lb && $ls <= $lt && ($ls == 0 || startswith($path, $tp))) {
                    $arr = json_decode($row['data'], true);
                    if ($arr !== null) {
                        if ($ls > $lb) {
                            $lb = $ls;
                            $params = [(int)$row['id'] => $arr];
                        } else {
                           // multiple sets (should be different properties)
                           $params[(int)$row['id']] = $arr;
                        }
                    }
                }
            }
            unset($row);
            if ($params) {
                $this->_cache = [$path => $params];
                return $params;
            }

            if ($default >= 0) {
                foreach ($this->_allcache as &$row) {
                    if ($row['id'] == $default) {
                        $arr = json_decode($row['data'], true);
                        if ($arr !== null) {
                            return $arr;
                        }
                        break;
                    }
                }
                unset($row);
            }
        }
        return [];
    }

    /**
     * Determine whether $test is acceptable for user $user_id and folder $path
     *
     * @param int   $op   enumerator of intended operation - create, delete etc
     * @param string $name of item to be 'operated' per $op
     * @param int $user_id
     * @param string $path Absolute or site-root-relative filesystem path of directory
     * @param int $default Optional numeric identifier of the default profile
     *  to be used if no specific profile is found.
     * @return bool
     */
    public function operation_permitted(int $op, string $name, int $user_id, string $path, int $default = -1) : bool
    {
        $params = $this->get_for_folder($path, $default);
        if ($params) {
/* TODO checks
enum for op 'can_delete' => $params[same]
enum for op 'can_mkdir' => $params[same]
enum for op 'can_mkfile' => $params[same]
enum for op 'show_hidden' => $params[same]
enum for op 'show_thumbs' => $params[same] no test ??

no test 'sort_by'=>'name', // item-property - name,size,created,modified

user_id in  $params['match_users'] if any
user_id NOT in  $params['exclude_users'] if any
user_id in group in $params['match_groups'] if any
user_id NOT in group in $params['exclude_groups'] if any

$name preg_match in $params['match_pattern'] if any
$name preg_match NOT in $params['exclude_pattern'] if any

FileTypeHelper +
filetype for absolute/$path/$name (how, if not yet exists?) in $params['file_types'] if any
*/
        }
        return true;
    }
} // class
