<?php
/*
FolderControls utility-methods class
Copyright (C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS;

use CMSMS\AppParams;
use CMSMS\DataException;
use CMSMS\FileType;
use CMSMS\FileTypeHelper;
use CMSMS\FolderControls;
use CMSMS\FolderOperationTypes;
use CMSMS\SingleItem;
use LogicException;
use RuntimeException;
use Throwable;
use UnexpectedValueException;
use const CMS_DB_PREFIX;
use const CMS_DEBUG;
use const CMS_ROOT_PATH;
use const CMSSAN_FILE;
use function check_permission;
use function cms_join_path;
use function debug_to_log;
use function endswith;
use function fnmatch;
use function get_session_value;
use function get_userid;
use function set_session_value;
use function startswith;

/**
 * A class of static utility-methods for working with FolderControls objects
 *
 * @package CMS
 * @license GPL
 * @since  2.99
 * Formerly a FilePicker module utilities class
 */
class FolderControlOperations
{
    /**
     * @ignore
     * AppParms key for the default-profile id
     */
    const DFLT_PREF = 'defaultControlsetId';

    /**
     * @ignore
     * Database table for recording set data
     */
    const TABLENAME = 'controlsets';

    /**
     * @ignore
     * Cached profile-data key-seed
     */
    private const SET_SEED = 'a23M3Pz6Khf_'; // hash'd and base-convert'd __CLASS__

    /**
     * Support (until further notice) old camel-case method-names
     */
    public static function __callStatic(string $oldname, array $args)
    {
         $newname = preg_replace_callback('/[ABDINP]/', function($m) { return '_'.strtolower($m); }, $oldname);
         try {
             return self::$newname(...$args);
         } catch (Throwable $t) {
            exit('Unexpected call to '.__CLASS__.'::'.$oldname);
         }
    }

// ~~~~~~~~~~~ FORMER FILEPICKER DAO-CLASS METHODS ~~~~~~~~~~~

    public static function table_name()
    {
        return CMS_DB_PREFIX.self::TABLENAME;
    }

    public static function get_default_profile_id()
    {
        return (int)AppParams::get(self::DFLT_PREF);
    }

    public static function clear_default()
    {
        AppParams::remove(self::DFLT_PREF);
    }

    /**
     * Set the default controls-set
     * @param mixed $a FolderControls object or integer id
     * @throws LogicException
     */
    public static function set_default($a)
    {
        if( is_int($a) ) {
            $id = $a;
        }
        elseif( $a instanceof FolderControls ) {
                $id = $a->id;
        }
        else {
            throw new UnexpectedValueException('Invalid identifier supplied to '.__METHOD__);
        }
        if( $id < 1 ) {
            throw new LogicException('Cannot assign an unsaved controlset as the default set');
        }
        AppParams::set(self::DFLT_PREF,$id);
    }

    public static function load_default()
    {
        $dflt_id = self::get_default_profile_id();
        if( $dflt_id > 0 ) {
            return self::load_by_id($dflt_id);
        }
    }

    public static function load_by_id($id)
    {
        $id = (int)$id;
        if( $id < 1 ) {
            throw new UnexpectedValueException('Invalid set-id provided to '.__METHOD__);
        }
        $db = SingleItem::Db();
        $sql = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id=?';
        $row = $db->getRow($sql,[$id]);
        if( $row ) {
            return self::object_from_row($row);
        }
    }

    public static function load_by_name($name)
    {
        $name = trim($name);
        if( !$name ) {
            throw new DataException('No set-name provided to '.__METHOD__);
        }
        $db = SingleItem::Db();
        $sql = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name=?';
        $row = $db->getRow($sql,[$name]);
        if( $row ) {
            return self::object_from_row($row);
        }
    }

    /**
     * @param bool $objects Optional flag whether to return ControlSet objects or just raw data
     * @return array
     */
    public static function load_all(bool $objects = true)
    {
        $db = SingleItem::Db();
        $sql = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' ORDER BY name';
        $list = $db->getArray($sql);
        if( !$list ) {
            return [];
        }
        if( !$objects ) {
            foreach( $list as &$row ) {
                $exp = json_decode($row['data'], true);
                unset($row['data']);
                $row = $exp + $row;
            }
            unset($row);
            return $list;
        }
        $out = [];
        foreach( $list as $row ) {
            $out[] = self::object_from_row($row);
        }
        return $out;
    }

    /**
     * Delete the specified controls-set
     * @param mixed $a FolderControls object or integer id
     * @throws LogicException
     */
    public static function delete($a)
    {
        if( is_int($a) ) {
            $id = $a;
        }
        elseif( $a instanceof FolderControls ) {
            $id = $a->id;
        }
        else {
            throw new UnexpectedValueException('Invalid identifier supplied to '.__METHOD__);
        }
        if( $id < 1 ) {
            throw new UnexpectedValueException('Invalid set-id provided to '.__METHOD__);
        }
        $db = SingleItem::Db();
        $sql = 'DELETE FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id=?';
        $db->execute($sql,[$cset->id]);
        return $cset->withNewId();
    }

    public static function save(FolderControls $cset)
    {
        $cset->validate();
        if( $cset->id < 1 ) {
            return self::insert($cset);
        } else {
            return self::update($cset);
        }
    }

    protected static function insert(FolderControls $cset)
    {
        $db = SingleItem::Db();
        $sql = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ?';
        $tmp = $db->getOne($sql,[$cset->name]);
        if( $tmp ) {
            throw new LogicException('err_profilename_exists');
        }
        $sql = 'INSERT INTO '.CMS_DB_PREFIX.self::TABLENAME.' (name,data,create_date) VALUES (?,?,?)';
        $data = json_encode($cset->getRawData(),JSON_NUMERIC_CHECK|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        $longnow = $db->DbTimeStamp(time());
        $dbr = $db->execute($sql,[$cset->name,$data,$longnow]);
        if( !$dbr ) {
            throw new RuntimeException('Problem inserting controlset record');
        }
        $new_id = $db->Insert_ID();
        return $cset->withNewID($new_id);
    }

    protected static function update(FolderControls $cset)
    {
        $db = SingleItem::Db();
        $sql = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ? AND id != ?';
        $tmp = $db->getOne($sql,[$cset->name,$cset->id]);
        if( $tmp ) {
            throw new LogicException('err_profilename_exists');
        }
        $sql = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET name=?,data=?,modified_date=? WHERE id=?';
        $data = json_encode($cset->getRawData(),JSON_NUMERIC_CHECK|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        $longnow = $db->DbTimeStamp(time());
//      $dbr = useless for update
        $db->execute($sql,[$cset->name,$data,$longnow,$cset->id]);
        if( $db->errorNo() > 0 ) {
            throw new RuntimeException('Problem updating controlset record');
        }
        return $cset->markModified();
    }

    protected static function object_from_row(array $row)
    {
        $old = json_decode($row['data'],true);
        $data = array_merge($old,$row);
        return new FolderControls($data);
    }

// ~~~~~~~~~ FORMER FILEPICKER UTILITIES-CLASS METHODS ~~~~~~~~~

    /**
     * @param mixed $dirpath
     * @ignore
     * @return string
     */
    protected static function processpath($dirpath) : string
    {
        $config = SingleItem::Config();
        $devmode = $config['develop_mode'];
        if (!$devmode) {
            $userid = get_userid(false);
            $devmode = check_permission($userid, 'Modify Restricted Files');
        }
        $rootpath = ($devmode) ? CMS_ROOT_PATH : $config['uploads'];

        if (!$dirpath) {
            $dirpath = $rootpath;
        } elseif (!preg_match('~^ *(?:\/|\\\\|\w:\\\\|\w:\/)~', $dirpath)) {
            // $dirpath is relative
            $dirpath = cms_join_path($rootpath, $dirpath);
        } elseif (!startswith($dirpath, CMS_ROOT_PATH)) {
            return '';
        }
        if (is_dir($dirpath)) {
            return $dirpath;
        }
        return '';
    }

    /**
     * Get the default profile
     *
     * @param mixed $dirpath Optional top-directory for the profile UNUSED TODO
     * @param mixed $userid Optional current user id UNUSED TODO
     * @return FolderControls
     */
    public static function get_default_profile() : FolderControls
    {
        $cset = self::load_default();
        if( !$cset ) {
            $cset = new FolderControls();
        }
        return $cset;
    }

    /**
     * Get the profile applicable to folder $dirpath and user $userid
     * If there is no applicable profile, a default is provided.
     *
     * @param mixed $cset_name string | falsy value optional name of existing profile
     * @param string $dir optional filesystem path of folder to be displayed
     * @param int $userid optional user identifier
     * @return FolderControls
     */
    public static function get_profile_for($dirpath = '', $userid = 0) : FolderControls
    {
        $dirpath = self::processpath($dirpath);
        if( $userid < 1 ) {
            $userid = get_userid(false);
        }
        $cset = null; // GET_THEONE_IFANY_FOR($dirpath, $userid);
        if( $cset ) {
            return $cset;
        }
        return self::get_default_profile();
    }

    /**
     * Get the named profile, or failing that, the profile for
     * place $dirpath and user $userid
     *
     * @param mixed $cset_name string | falsy value optional name of existing profile
     * @param string $dirpath optional filesystem path of folder to be displayed
     * @param int $userid Optional user identifier, Default current user
     * @return FolderControls object
     */
    public static function get_profile($cset_name, string $dirpath = '', int $userid = 0) : FolderControls
    {
        $cset_name = trim($cset_name);
        if( $cset_name ) {
            $cset = self::load_by_name($cset_name);
        }
        else {
            $cset = null;
        }
        if( !$cset ) {
            $cset = self::get_profile_for($dirpath, $userid);
        }
        return $cset;
    }

// ~~~~~~~~~ FORMER FOLDERCONTROLS-CLASS METHODS ~~~~~~~~~

    /**
     * Check whether $filename accords with relevant conditions among
     * the profile properties
     *
     * @param FolderControls $cset
     * @param string $filename Absolute|relative filesystem path, or
     *  just basename, of a file
     * @return boolean
     */
    public static function is_file_name_acceptable($cset, $filename)
    {
        $fn = basename($filename);
        try {
            if( !$cset->show_hidden && ($fn[0] === '.' || $fn[0] === '_') ) {
                throw new UnexpectedValueException($fn.': name is not acceptable');
            }

            if( !self::get_match($cset->match_prefix, $fn) ) {
                throw new UnexpectedValueException($fn.': name is not acceptable');
            }

            if( $cset->exclude_prefix ) {
                if( self::get_match($cset->exclude_prefix, $fn) ) {
                    throw new UnexpectedValueException($fn.': name is not acceptable');
                }
            }

            if( $cset->file_extensions === '' ) {
                return true;
            }
            // file must have an acceptable extension
            $p = strrpos($fn, '.');
            if( !$p ) { // file has no extension, or just an initial '.'
                throw new UnexpectedValueException("Type '$fn' is not acceptable");
            }
            $ext = substr($fn, $p+1);
            if( !$ext ) { // file has empty extension
                throw new UnexpectedValueException("Type '$fn' is not acceptable");
            }
            $s = &$cset->file_extensions;
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
            throw new UnexpectedValueException("Type '$fn' is not acceptable");
        }
        catch (Throwable $t) {
            if( CMS_DEBUG ) {
                debug_to_log($t->GetMessage());
            }
            return false;
        }
    }

    /**
     * Helper function: check for a match between $pattern and $name
     * Tries wildcard, regex and literal name-matching, case-insensitive
     *
     * @param string $pattern regular expression or fnmatch-compatible
     * @param string $name
     * @return bool indicating whether $name conforms to $pattern
     */
    protected static function get_match($pattern, $name)
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
        if( strncasecmp($name, $pattern, $l) == 0 ) {
            return true;
        }
        //etc?
        return false;
    }

// ~~~~~~~~~~~ NONE OF THE FOLLOWING HAS BEEN TESTED ~~~~~~~~~~~

    /*
     * For now at least, hardly seems worth a LoadedData mechanism for
     * robust caching of controls data. Maybe in future, if things here
     * get busy ...
     */

    /**
     * Simple session-cache to suit FilePicker-action needs (at least)
     *
     * @param mixed $a FolderControls object or int identifier
     * @return string identifier for retrieving the data
     * @throws UnexpectedValueException
     */
    public static function store_cached($a) : string
    {
        if( is_int($a) ) {
            $id = $a;
        }
        elseif( $a instanceof FolderControls ) {
            $id = $a->id;
        }
        else {
            throw new UnexpectedValueException('Invalid identifier supplied to '.__METHOD__);
        }
        if( $id > 0 ) {
            return '~'.$id.'~'; // something short and urlencode-immune
        }
        $setprops = $a->getRawData();
        $id = hash('fnv1a32', json_encode($setprops));
        set_session_value(self::SET_SEED.$id, $setprops); // TODO need flat value?
        return '-'.$id.'-'; // indicate cached-not-recorded profile data
    }

    /**
     * Retrieve an object from storage (database or session-cache)
     *
     * @param string $tag Identifier like '~N~' or '-hash-', the latter
     *  for an un-recorded set i.e. no numeric identifier
     * @return mixed FolderControls object | null
     */
    public static function get_cached(string $tag)
    {
        $id = trim($tag);
        switch( $id[0] ) {
            case '~':
                if( endswith($id, '~') ) {
                    // a db-stored profile
                    return self::load_by_id(trim($id, ' ~'));
                }
                return null;
            case '-':
                if( endswith($id, '-') ) {
                    // a session-stored profile
                    $session_key = self::SET_SEED.trim($id, ' -');
                    $setprops = get_session_value($session_key);
                    if( $setprops ) {
                        $cset = new FolderControls($setprops);
                        return $cset;
                    }
                }
            // no break here
            default:
                return null; // TODO feedback to caller
        }
    }

    /**
     * Retrieve properties for $dirpath
     * c.f. get_profile_for()
     *
     * @param string $dirpath Absolute or site-root-relative filesystem path of a directory
     * @param int    $userid Optional user-identifier (for one other than current)
     * @param int    $default Optional numeric identifier of the profile
     *   to use in the absence of an explicitly relevant profile.
     *   Default -1 hence return the module-defaults
     * @return mixed FolderControls object | null if none relevant | false upon error
     */
    public static function get_for_folder(string $dirpath, int $userid = 0, int $default = -1)
    {
        if (startswith($dirpath, CMS_ROOT_PATH)) {
            $dirpath = substr($dirpath, strlen(CMS_ROOT_PATH));
        }
        //TODO validate $dirpath
        if ($userid < 1) {
            $userid = get_userid(false);
        }
        $cset = new FolderControls();
        return $cset; //DEBUG
/* TODO
        $path = trim($path, ' \/');
        if ($cset->_cache && key($cset->_cache) == $path) {
            return $cset->_cache; // same $path as last-processed
        }

        if (!$cset->_allcache) {
            $tbl = CMS_DB_PREFIX.self::TABLENAME;
            $db = SingleItem::Db();
            $cset->_allcache[] = $db->getAssoc('SELECT reltoppath,id,data FROM '.$tbl.' ORDER BY reltoppath');
        }
        // no gain here from a file-cache per the cms_filecache_driver class
        if ($cset->_allcache) {
            $lt = strlen($path);
            $lb = -1;
            $params = null;
            foreach ($cset->_allcache as $tp=>&$row) {
                $ls = strlen($tp);
                if ($ls >= $lb && $ls <= $lt && ($ls == 0 || startswith($path, $tp))) {
                    $arr = json_decode($row['data'], true);
                    if ($arr !== null) {
                        if ($ls > $lb) {
                            $lb = $ls;
                            $params = [(int)$row['id'] => $arr];
                        } else {
                           // multiple profiles (should be different properties)
                           $params[(int)$row['id']] = $arr;
                        }
                    }
                }
            }
            unset($row);
            if ($params) {
                $cset->_cache = [$path => $params];
                return $params;
            }

            if ($default >= 0) {
                foreach ($cset->_allcache as &$row) {
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
                return $cset->defaults();
            }
        }
*/
        return null;
    }

    /**
     * Determine whether $op is acceptable for the item $filepath and
     * user $userid. Among other things, this checks for obvious
     * annoyances in the basename of $filepath.
     *
     * @param string $rootpath Absolute and valid filesystem path
     * @param string $filepath Absolute or $rootpath-relative filesystem
     *  path of item to be 'operated' per $op
     *  NOTE not that item's parent directory
     * @param int $op     FolderOperationTypes enumerator of intended operation - create, delete etc
     * @param int $userid Optional user-identifier (for one other than current)
     * @param int $default Optional numeric identifier of the profile to use in the
     *   absence of an explicitly relevant profile. Default -1 hence return the module-defaults
     * @return bool indicating acceptability
     */
    public static function test_for_folder(string $rootpath, string $filepath, int $op, int $userid = 0, int $default = -1) : bool
    {
        $filepath = self::absolute_path($rootpath, $filepath);
        $name = basename($filepath);
        $cleaned = sanitizeVal($name, CMSSAN_FILE);
        if ($cleaned !== $name) {
            return false; // unacceptable name
        }
        if (!startswith($filepath, $rootpath)) {
            return false;
        }
        $dirpath = dirname($filepath);
        if (!$dirpath || $dirpath === '.' || @realpath($dirpath) === false) {
            return false; // invalid filepath
        }
        $cset = self::get_for_folder($dirpath, $userid, $default);
        if ($cset) {
            return self::test_for_profile($cset, $filepath, $op, $userid);
        }
        return true; // ok, no controls applied here
    }

    /**
     * Determine whether $op is acceptable for a folder $filepath,
     *  user $userid, with respect to the folder properties in $cset
     *
     * @param FolderControls $cset  An object returned by a previous Operations::get_for_folder() call.
     *  May be null.
     * @param string $filepath Absolute or site-root-relative filesystem path of item
     *  to be 'operated' per $op. It should be, or be in, or be a subdir of, or be in
     *  a subdir of, $cset->top. Otherwise, the test fails
     * @param int  $op   Optype enumerator of intended operation - create, delete etc
     * @param int  $userid Current user identifier
     * @return bool
     */
    public static function test_for_profile(FolderControls $cset, string $filepath, int $op, int $userid = 0) : bool
    {
        if ($cset) {
            // cuz $cset exists, no need for another valid-path check
            // TODO must support multiple (e.g. user-specific) profiles for the folder
            $name = basename($filepath);
            $key = FolderOperationTypes::getName($op);
            switch ($key) {
                case 'LISTALL':
                case 'SHOWHIDDEN':
                case 'SHOWTHUMBS':
                    break;
                default:
                    if ($cset->match_patterns) {
                        foreach ($cset->match_patterns as $p) {
                            if (preg_match ($p, $name)) {
                                break 2;
                            }
                        }
                        return false;
                    }
                    if ($cset->exclude_patterns) {
                        foreach ($cset->exclude_patterns as $p) {
                            if (preg_match ($p, $name)) {
                                return false;
                            }
                        }
                    }
                    break;
            }

            if ($cset->file_types && !in_array(FileType::ANY, $cset->file_types)) {
                switch ($key) {
                    case 'MKDIR':
                    case 'LISTALL':
                    case 'SHOWHIDDEN':
                    case 'SHOWTHUMBS':
                        break;
                    default:
                        $obj = new FileTypeHelper();
                        $filepath = self::absolute_path($cset->top, $filepath);
                        $t = $obj->get_file_type($filepath);
                        if (!$t || !in_array($t, $cset->file_types)) {
                            return false;
                        }
                        break;
                }
            }

            if ($userid < 1) {
                $userid = get_userid(false);
            }
            if ($userid == 1 ||
               ($cset->match_users && in_array($userid, $cset->match_users)) ||
               ($cset->exclude_users && !in_array($userid, $cset->exclude_users))) {
                return true;
            }

            $db = SingleItem::Db();
            $grps = $db->getCol('SELECT group_id FROM '.CMS_DB_PREFIX.'user_groups WHERE user_id=?', [$userid]);
            if ($grps) {
                if (in_array(1, $grps) ||
                   ($cset->match_groups && array_intersect($grps, $cset->match_groups)) ||
                   ($cset->exclude_groups && !array_intersect($grps, $cset->exclude_groups))) {
                    return true;
                }
            } elseif (!$cset->match_groups) {
                return true;
            }
        }
        return true;
    }

    /**
     * Get an absolute path string (without trailing separator).
     * This involves string-operations only, no actual-filesystem-path checking
     * @ignore
     *
     * @param string $rootpath Absolute and valid filesystem path, or if empty
     *  then CMS_ROOT_PATH is used TODO OR CMSMS\SingleItem::Config()['uploads_path'] OR ?
     * @param string $filepath Absolute or $rootpath-relative filesystem path
     * @return string
     */
    protected static function absolute_path(string $rootpath, string $filepath) : string
    {
        if (!preg_match('~^ *(?:\/|\\\\|\w:\\\\|\w:\/)~', $filepath)) {
            //path is not absolute
            //strip any (bogus) leading relativizer(s) & ignore any other(s) embedded in there ...
            $cleaned = trim($filepath, ' \/');
            while ($cleaned[0] === '.') {
                $cleaned = ltrim($cleaned, '. \/');
            }
            if (!$rootpath) { $rootpath = CMS_ROOT_PATH; }
            $filepath = cms_join_path($rootpath, $cleaned);
        }
        if (endswith($filepath, DIRECTORY_SEPARATOR)) {
            $filepath = rtrim($filepath, ' \/');
        }
        return $filepath;
    }

    // permissions and places

    /**
     * Report whether the current user is entitled to view/open files
     * @return bool
     */
    public static function view_safe()
    {
        $userid = get_userid();
        if (0) { //TODO some view-only permission
            return true;
        }
        return self::view_all($userid);
    }

    /**
     * Report whether the current user is entitled to view/open files
     * anywhere on the site
     *
     * @param int $userid Optional user-identifier (for one other than current)
     * @return string
     */
    public static function view_all(int $userid = 0)
    {
        if ($userid == 0) {
            $userid = get_userid();
        }
        //TODO relevant view-all permission ||
        return self::modify_all($userid);
    }

    /**
     * Report whether the current user is entitled to modify files
     *
     * @return bool
     */
    public static function modify_safe() : bool
    {
        $userid = get_userid();
        if (check_permission($userid, 'Modify Files')) {
            return true;
        }
        return self::modify_all($userid);
    }

    /**
     * Report whether the current user or a specific user is entitled to
     * modify files anywhere on the site
     *
     * @param int $userid Optional user-identifier (for one other than current)
     * @return bool
     */
    public static function modify_all(int $userid = 0) : bool
    {
        if ($userid == 0) {
            $userid = get_userid();
        }
        if (check_permission($userid, ['Modify Files', 'Modify Restricted Files'])) {
            return true;
        }
        $config = SingleItem::Config();
        return !empty($config['develop_mode']);
    }

    /**
     * Return the topmost profile-compatible folder of the website.
     * Some users may be authorized to operate everywhere, but most will be
     * restricted to the uploads folder and its descendants.
     * Also, $config['develop_mode'] authorizes the whole site.
     *
     * @return string
     */
    public static function top_profiled_path() : string
    {
        $config = SingleItem::Config();
        $devmode = $config['develop_mode'];
        if (!$devmode) {
            $userid = get_userid(false);
            $devmode = check_permission($userid, 'Modify Restricted Files');
        }
        return ($devmode) ? CMS_ROOT_PATH : $config['uploads_path'];
    }
} // class
