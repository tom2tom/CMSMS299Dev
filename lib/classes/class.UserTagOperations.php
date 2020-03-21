<?php
#class to process user-defined-tags (aka user-plugins)
#Copyright (C) 2004-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Ted Culp and all other contributors from the CMSMS Development Team.
#This file is part of CMS Made Simple <http://cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace CMSMS;

use CmsApp;
//use CMSMS\internal\SysDataCache;
use CMSMS\internal\Smarty;
use const CMS_DB_PREFIX;

/**
 * User-defined-tag related functions.
 *
 * @package CMS
 * @license GPL
 */
final class UserTagOperations
{
	/**
	 * @ignore
	 */
	private static $_instance;

	/* *
	 * @ignore
	 */
//	private $_cache = [];

	/**
	 * @ignore
	 */
	private function __construct() {}

	/**
	 * @ignore
	 */
	private function __clone() {}

	/**
	 * Support for simplified explicit tag-running
	 * @ignore
	 * @param string $name User defined tag name
	 * @param array $arguments NOTE enumerated i.e. useless for named UDT parameters
	 */
	public function __call($name, $arguments)
	{
		return $this->CallUserTag($name, $arguments);
	}

	/**
	 * Get the only allowed instance of this class
	 *
	 * @return UserTagOperations
	 */
	public static function get_instance()
	{
		if (!isset(self::$_instance)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Establish global cache for all UDT's
	 *
	 * @ignore
	 * @deprecated since 2.3 does nothing
	 * @internal
	 */
	public static function setup()
	{
/*		$obj = new global_cachable(__CLASS__, function () {
			$db = CmsApp::get_instance()->GetDb();

			$query = 'SELECT * FROM '.CMS_DB_PREFIX.'userplugins ORDER BY name';
			$data = $db->GetArray($query);
			if ($data) {
				$out = [];
				foreach ($data as $row) {
					$out[$row['name']] = $row;
				}
				return $out;
			}
		});
		SysDataCache::add_cachable($obj);
*/
	}

	/**
	 * Cache all information about all user tags (onetime only)
	 * @deprecated since 2.3 does nothing
	 */
	public function LoadUserTags()
	{
/*		if (!$this->_cache) {
			$this->_cache = SysDataCache::get(__CLASS__);
		}
*/
	}

	/* *
	 * Get a user tag record (by name or id) from the cache
	 *
	 * @internal
	 */
/*	private function _get_from_cache($name)
	{
		$this->LoadUserTags();
		if (isset($this->_cache[$name])) {
			return $this->_cache[$name];
		}
		foreach ($this->_cache as $row) {
			if ($name == $row['id']) {
				return $row;
			}
		}
	}
*/

	/**
	 * Retrieve stored property|ies of a user defined tag
	 *
	 * @param string $name User defined tag name
	 * @param mixed $props @since 2.3 string|strings[] Optional database userplugins-table field name(s) (comma-separated ok) or '*'. Default '*'.
	 *
	 * @return mixed array|null
	 */
	public function GetUserTag($name, $props = '*')
	{
//		return $this->_get_from_cache($name);
		if (is_array($props)) {
			$parms = implode(',', $props);
		} else {
			$parms = $props;
		}
		$db = CmsApp::get_instance()->GetDb();
		$query = 'SELECT '.$parms.' FROM '.CMS_DB_PREFIX.'userplugins WHERE name=?';
		$row = $db->GetRow($query, [$name]);
		if ($row) {
//			return [$name => $row];
			return $row;
		}
	}

	/**
	 * Test if a user defined tag with a specific name exists
	 *
	 * @param string $name User defined tag name
	 *
	 * @return mixed string|false
	 *
	 * @since 1.10
	 */
	public function UserTagExists($name)
	{
//		$row = $this->_get_from_cache($name);
		$row = $this->GetUserTag($name, 'code');
		if ($row && $row/*[$name]*/['code']) {
			return $name;
		}
		return false;
	}

	/**
	 * Test whether a UDT with the specified name exists
	 * @since 2.3, this no longer checks for regular (non-UDT) plugins,
	 * and is merely a minor embellishment of UserTagExists()
	 *
	 * @param string $name			The name to test
	 * @param bool   $check_functions Test if already registered to smarty
	 */
	public function SmartyTagExists($name, $check_functions = true)
	{
/*		// get the known smarty plugins.
		$phpfiles = glob(cms_join_path(CMS_ROOT_PATH, 'lib', 'plugins', 'function.*.php'));
		if ($phpfiles) {
			for ($i = 0, $n = count($phpfiles); $i < $n; ++$i) {
				$fn = basename($phpfiles[$i], '.php');
				$parts = explode('.', $fn);
				if (count($parts) < 2) {
					continue;
				}
				unset($parts[0]);
				$middle = implode('.', $parts);
				if ($name == $middle) {
					return true;
				}
			}
		}
		$config = \cms_config::get_instance();
		$phpfiles = glob(cms_join_path(CMS_ROOT_PATH,$config['admin_dir'],'plugins','function.*.php'));
		if ($phpfiles) {
			for ($i = 0, $n = count($phpfiles); $i < $n; ++$i) {
				$fn = basename($phpfiles[$i], '.php');
				$parts = explode('.', $fn);
				if (count($parts) < 2) {
					continue;
				}
				unset($parts[0]);
				$middle = implode('.', $parts);
				if ($name == $middle) {
					return true;
				}
			}
		}
*/
		if ($check_functions) {
			// registered by something else... maybe a module
			$smarty = Smarty::get_instance();
			if ($smarty->is_registered($name)) {
				return true;
			}
		}

		return $this->UserTagExists($name);
	}

	/**
	 * Insert or update a named user-defined-tag in the database
	 *
	 * @param string $name		UDT name
	 * @param string $text		UDT body/code
	 * @param string $description Optional UDT description
	 * @param int	$id			Optional UDT ID (>0 when updating existing tag) Default 0
	 *
	 * @return bool indicating success
	 */
	public function SetUserTag($name, $text, $description = null, $id = 0)
	{
		$name = trim($name);
		if (!preg_match('/[a-zA-Z0-9_]*/', $name)) { //c.f. valid identifiers (excl. 7f..ff), but in use will have prefix 'cms ....'
			return false;
		}
		$code = preg_replace(
				['/^[\s\r\n]*<\\?php\s*[\r\n]*/i', '/[\s\r\n]*\\?>[\s\r\n]*$/', '    '],
				['', '', "\t"],
				$text);
		if ($description && !is_numeric($description)) {
			$description = filter_var($description,FILTER_UNSAFE_RAW,FILTER_FLAG_STRIP_BACKTICK);
		} else {
			$description = NULL;
		}
		$db = CmsApp::get_instance()->GetDb();
		//upsert data
		$tbl = CMS_DB_PREFIX.'userplugins';
		//prevent duplicate names
		$query = "SELECT 1 FROM $tbl WHERE name=? AND id!=?";
		$dbr = $db->Execute($query,[$name,$id]);
		if ($dbr) {
			return false;
		}
		//a subquery on the table to be updated is not valid
		if ($id == 0) {
			$query = "INSERT INTO $tbl (name,code,description) VALUES (?,?,?)";
			$dbr = $db->Execute($query,[$name,$code,$description]);
			return ($dbr != false);
		} else {
			$query = "UPDATE $tbl SET name=?,code=?,description=? WHERE id=?";
			$db->Execute($query,[$name,$code,$description,$id]);
			return ($db->affected_rows() > 0);
		}
	}

	/**
	 * Remove a named user defined tag from the database
	 *
	 * @param string $name User defined tag name
	 *
	 * @return bool indicating success
	 */
	public function RemoveUserTag($name)
	{
		$db = CmsApp::get_instance()->GetDb();
		$query = 'DELETE FROM '.CMS_DB_PREFIX.'userplugins WHERE name=?';
		$dbr = $db->Execute($query, [$name]);

		return ($dbr != false);
/*		if ($dbr) {
			SysDataCache::clear(__CLASS__);
			$this->_cache = [];
			return true;
		}
		return false;
*/
	}

	/**
	 * Return a list (suitable for use in a pulldown) of user tags.
	 *
	 * @return array, each member like id=>name
	 */
	public function ListUserTags()
	{
		$db = CmsApp::get_instance()->GetDb();
		$query = 'SELECT id,name FROM '.CMS_DB_PREFIX.'userplugins ORDER BY name';
		return $db->GetAssoc($query);
	}

	/**
	 * Execute a user defined tag
	 *
	 * @param string $name   The name of the user defined tag
	 * @param array  $params Optional tag parameters. Default []
	 * @param mixed object|null $smarty_ob Optional Smarty_Internal_Template (or descendant) for the
	 *  caller's context
	 * Compiled smarty provides a Smarty_Internal_Template-object as the
	 * 2nd argument to tags. CMSMS\internal\Smarty Is not a sub-class of that.
	 *
	 * @return mixed value returned by tag|false
	 */
	public function CallUserTag($name, &$params = [], $smarty_ob = null)
	{
		$functionname = $this->CreateTagFunction($name);
		if ($functionname) {
			if (!$smarty_ob) {
				$smarty_ob = Smarty::get_instance(); //default to global smarty
			}
//			$result = call_user_func_array($functionname, [$params, $smarty_ob]);
			$result = $functionname($params, $smarty_ob);
			return $result;
		}
		return false;
	}

	/**
	 * Create a function to execute a named UDT
	 *
	 * @param string $name The name of the user defined tag to execute
	 *
	 * @return mixed function name | null
	 */
	public function CreateTagFunction($name)
	{
		$functionname = 'cms_user_tag_'.$name;
		if (!function_exists($functionname)) {
			$row = $this->GetUserTag($name, 'code');
			if (empty($row[$name]['code'])) {
				return;
			}
			$code = preg_replace(
				['/^[\s\r\n]*<\\?php\s*[\r\n]*/i', '/[\s\r\n]*\\?>[\s\r\n]*$/'],
				['', ''],
				$row[$name]['code']);
			if (!$code) {
				return;
			}
			if (1) {
//*			TODO ALT-eval DEBUG
			$strfunc =  '<?php function '.$functionname.'($params,$template) { $smarty=$template; '.$code.' }';
			$fh = fopen('php://temp', 'c+');
			fwrite($fh, $strfunc);
			rewind($fh);
            $v = ini_get('allow_url_include');
            if (!$v) { ini_set('allow_url_fopen', 1); ini_set('allow_url_include', 1); }
	        $dbg = require 'php://temp'; //useless?
	        //$dbg = require "php://fd/$fh"; //useless?
            if (!$v) { ini_restore('allow_url_include'); ini_restore('allow_url_fopen'); }
			fclose($fh);
//*/
			} else {
			eval('function '.$functionname.'($params,$template) { $smarty=$template; '.$code.' }');
			}
		}
		return $functionname;
	}
} // class

//backward-compatibility shiv
\class_alias(UserTagOperations::class, 'UserTagOperations', false);
