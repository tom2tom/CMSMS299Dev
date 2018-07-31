<?php
/*
A class for working with ControlSets.
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

use CmsApp;
use CMSMS\BasicEnum;
use CMSMS\FileType;
use CMSMS\FileTypeHelper;
use Exception;
use FolderControls\ControlSet;
use const CMS_DB_PREFIX;
use const CMS_ROOT_PATH;
use function startswith;

final class OpType extends BasicEnum
{
    const MKDIR = 1;
    const MKFILE = 2;
    const MODFILE = 2;
    const DELETE = 3;
    const VIEWFILE = 4;
    const LISTALL = 10;
    const SHOWHIDDEN = 20;
    const SHOWTHUMBS = 21;

    private function __construct() {}
    private function __clone() {}
} // class

/**
 * A class for working with 'conntrolsets'. A controlset is a collection of
 * extended permissions for, and properties of, a folder and (unless and until
 * countervailed by a more-folder-specific profile) inherited by the folder's
 * descendants.
 */
class Operations
{
    private $_allcache = []; // each key=toppath, val=db-row
    private $_cache = []; //key=supplied-path, val=matching _data array

    /**
     * Create a new ControlSet-object based on the one supplied, with
     *  property-adjustments per $params.
     *
     * @param array $params Optional associative array of parameters
     * used for re-populating some/all of the object's $_data
     *
     * @return ControlSet object
     */
    public static function cloneWith(ControlSet $set, array $params = []) : ControlSet
    {
        $obj = clone $set;
        foreach ($params as $key => $val) {
            try {
                $obj->__set($key, $val);
            } catch (Exception $e) {
            }
        }
        return $obj;
    }

    /**
     * Retrieve a ControlSet-object from stored data
     *
     * @param mixed $id Set identifier, either numeric id or string name
     * @return mixed ControlSet-object or null
     */
    public static function load($id)
    {
        if (is_numeric($id)) {
            $sql = 'SELECT id,toppath,data FROM '.CMS_DB_PREFIX.'module_excontrols WHERE id=?';
        } else {
            $sql = 'SELECT id,toppath,data FROM '.CMS_DB_PREFIX.'module_excontrols WHERE name=?';
        }
        $db = CmsApp::get_instance()->GetDb();
        $row = $db->GetRow($sql,[$id]);
        if ($row) {
            $data = json_decode($row['data'], true);
            if ($data !== null) {
                $set = new ControlSet();
                $set->id = (int)$row['id'];
                $set->topdir = (string)$row['toppath'];
                $set->setRawData($data);
                return $set;
            }
        }
        return null;
    }

    /**
     * Save (upsert) a ControlSet
     *
     * @param mixed $id Set identifier, a numeric id or string name
     * @return bool indicating success (well, maybe ... UPDATES are not reported properly)
     */
    public static function save(ControlSet $set) : bool
    {
        $raw = json_encode($set->getRawData());
        $db = CmsApp::get_instance()->GetDb();
        $now = $db->DbTimeStamp(time());
        $db->Execute($sql,[$set->topdir, $raw, $now, $id]);

        $pref = CMS_DB_PREFIX;
        $name = $set->name;
        $sql = <<<EOS
INSERT INTO {$pref}module_excontrols (name,toppath,data,create_date,modified_date) SELECT ?,?,?,?,? FROM (SELECT 1 AS dmy) Z
WHERE NOT EXISTS (SELECT 1 FROM {$pref}module_excontrols T WHERE T.name=?)
EOS;
        $dbr = $db->Execute($sql,[$name, $set->topdir, $raw, $now, $now, $name]);
        return $dbr != false;
    }

    /**
     * Delete a ControlSet
     *
     * @param mixed $id Set identifier, a numeric id or string name
     * @return bool indicating success
     */
    public static function delete($id) : bool
    {
        if (is_numeric($id)) {
            $sql = 'DELETE FROM '.CMS_DB_PREFIX.'module_excontrols WHERE id=?';
        } else {
            $id = filter_var(strtr($id, ' ', '_'), FILTER_SANITIZE_STRING);
            $sql = 'DELETE FROM '.CMS_DB_PREFIX.'module_excontrols WHERE name=?';
        }
        $db = CmsApp::get_instance()->GetDb();
        $dbr = $db->Execute($sql,[$id]);
        return $dbr != false;
    }

    /**
     * Retrieve all (raw) data for all recorded profiles. Each returned row
     * includes a 'data' member, whose value is an array of specific properties.
     *
     * @return array
     */
    public static function get_all() : array
    {
        $sets = [];
        $db = CmsApp::get_instance()->GetDb();
        $data = $db->GetArray('SELECT * FROM '.CMS_DB_PREFIX.'module_excontrols ORDER BY name');
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
     * @param int    $default Optional numeric identifier of the controls-set to use in the
     *   absence of an explicitly relevant set. Default -1 hence return the module-defaults
     * @return assoc array (members like 'prop'=>$value), or empty
     */
    public static function get_for_folder(string $path, int $default = -1) : array
    {
        if (startswith($path, CMS_ROOT_PATH)) {
            $path = substr($path, strlen(CMS_ROOT_PATH));
        }

        $path = trim($path, ' /\\');
        if ($set->_cache && key($set->_cache) == $path) {
            return $set->_cache; // same $path as last-processed
        }

        if (!$set->_allcache) {
            $db = CmsApp::get_instance()->GetDb();
            $set->_allcache[] = $db->GetAssoc('SELECT toppath,id,data FROM '.CMS_DB_PREFIX.'module_excontrols ORDER BY toppath');
        }
        // no gain here from a file-cache per the cms_filecache_driver class
        if ($set->_allcache) {
            $lt = strlen($path);
            $lb = -1;
            $params = null;
            foreach ($set->_allcache as $tp=>&$row) {
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
                $set->_cache = [$path => $params];
                return $params;
            }

            if ($default >= 0) {
                foreach ($set->_allcache as &$row) {
                    if ($row['id'] == $default) {
                        $arr = json_decode($row['data'], true);
                        if ($arr !== null) {
                            return $arr;
                        }
                        break;
                    }
                }
                unset($row);
            } else {
                return $set->defaults();
            }
        }
        return [];
    }

    /**
     * Determine whether $op is acceptable for item named $name, folder $dirpath, user $user_id
     *
     * @param int    $op   OpType enumerator of intended operation - create, delete etc
     * @param string $name of item to be 'operated' per $op
     * @param int    $user_id current user identifier
     * @param string $dirpath Absolute or site-root-relative filesystem path of directory
     * @param int    $default Optional numeric identifier of the controls-set to use in the
     *   absence of an explicitly relevant set. Default -1 hence return the module-defaults
     * @return bool
     */
    public static function test_for_folder(int $op, string $name, int $user_id, string $dirpath, int $default = -1) : bool
    {
        $params = $this->get_for_folder($dirpath, $default);
        if ($params) {
            return $this->test_for_set($op, $name, $user_id, $params);
        }
        return true;
    }

    /**
     * Determine whether $op is acceptable for an item named $name, user $user_id,
     *  with respect to the folder properties in $params
     *
     * @param int    $op   Optype enumerator of intended operation - create, delete etc
     * @param string $name (base)name of item to be 'operated' per $op
     * @param int    $user_id current user identifier
     * @param array  $params data returned by a previous get_for_folder() call
     * @return bool
     */
    public static function test_for_set(int $op, string $name, int $user_id, array $params) : bool
    {
        if ($params) {
            $key = OpType::getName($op);
            switch ($key) {
                case 'LISTALL':
                case 'SHOWHIDDEN':
                case 'SHOWTHUMBS':
                    break;
                default:
                    if (!empty($params['match_patterns'])) {
                        foreach ($params['match_patterns'] as $p) {
                            if (preg_match ($p, $name)) {
                                break 2;
                            }
                        }
                        return false;
                    }
                    if (!empty($params['exclude_patterns'])) {
                        foreach ($params['exclude_patterns'] as $p) {
                            if (preg_match ($p, $name)) {
                                return false;
                            }
                        }
                    }
                    break;
            }

            if (!empty($params['file_types']) && !in_array(FileType::ANY, $params['file_types'])) {
                switch ($key) {
                    case 'MKDIR':
                    case 'LISTALL':
                    case 'SHOWHIDDEN':
                    case 'SHOWTHUMBS':
                        break;
                    default:
                        $obj = new FileTypeHelper();
						$path = TODOfunc($name);
                        $t = $obj->get_file_type($path);
                        if (!in_array($t, $params['file_types'])) {
                            return false;
                        }
                        break;
                }
            }

            if ($user_id == 1 ||
               (!empty($params['match_users']) && in_array($user_id, $params['match_users'])) ||
               (!empty($params['exclude_users']) && !in_array($user_id, $params['exclude_users']))) {
                return true;
            }

            $db = Cmsapp::get_instance()->GetDb();
            $grps = $db->GetCol('SELECT group_id FROM '.CMS_DB_PREFIX.'user_groups WHERE user_id=?', [$user_id]);
            if ($grps) {
                if (in_array(1, $grps) ||
                   (!empty($params['match_groups']) && array_intersect($grps, $params['match_groups'])) ||
                   (!empty($params['exclude_groups']) && !array_intersect($grps, $params['exclude_groups']))) {
                    return true;
                }
            } elseif (empty($params['match_groups'])) {
                return true;
            }
        }
        return false;
    }
} // class
