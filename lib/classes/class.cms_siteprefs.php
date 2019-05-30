<?php
#Class and utilities for working with site- and module-preferences.
#Copyright (C) 2004-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
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

use CMSMS\internal\global_cachable;
use CMSMS\internal\global_cache;

/**
 * A class for working with site- and module-preferences
 *
 * @package CMS
 * @license GPL
 * @since 1.10
 * @author Robert Campbell (calguy1000@cmsmadesimple.org)
 */
final class cms_siteprefs
{
	/**
	 * @ignore
	 */
	private function __construct() {}

	/**
	 * @ignore
	 * @internal
	 */
	public static function setup()
	{
		$obj = new global_cachable(__CLASS__,function()
		{
			return self::_read();
		});
		global_cache::add_cachable($obj);
	}

	/**
	 * Cache site- and module-preferences
	 * @ignore
	 * @internal
	 */
	private static function _read()
	{
		$db = CmsApp::get_instance()->GetDb();

		if( !$db ) {
			return;
		}
		$query = 'SELECT sitepref_name,sitepref_value FROM '.CMS_DB_PREFIX.'siteprefs';
		$dbr = $db->GetArray($query);
		if( is_array($dbr) ) {
			$_prefs = [];
			for( $i = 0, $n = count($dbr); $i < $n; $i++ ) {
				$row = $dbr[$i];
				$_prefs[$row['sitepref_name']] = $row['sitepref_value'];
			}
			return $_prefs;
		}
	}

	/**
	 * Retrieve specified preference(s) without using the cache.
	 * This is mostly for getting parameter(s) needed to init the cache.
	 * Also for use in async tasks, where the cache is N/A.
	 *
	 * @since 2.3
	 * @param mixed string | array $key Preference name(s)
	 * @param mixed string | array $dflt Optional default value(s)
	 * @return mixed value | array
	 */
	public static function getraw($key, $dflt = '')
	{
		$db = CmsApp::get_instance()->GetDb();

		if( !$db ) {
			return $dflt;
		}
		$query = 'SELECT sitepref_name,sitepref_value FROM '.CMS_DB_PREFIX.'siteprefs WHERE sitepref_name';
		if( is_array($key) ) {
			$query .= ' IN ('.str_repeat('?,', count($key) - 1).'?)';
			$dbr = $db->GetAssoc($query, $key);
			foreach( $key as $i => $one ) {
				if( !isset($dbr[$one]) ) {
					$dbr[$one] = $dflt[$i] ?? end($dflt);
				}
			}
			return $dbr;
		}
		else {
			$query .= '=?';
			$dbr = $db->GetRow($query, [$key]);
			return ( $dbr ) ? end($dbr) : $dflt;
		}
	}

	/**
	 * Retrieve a site/module preference
	 *
	 * @param string $key The preference name
	 * @param string $dflt Optional default value
	 * @return string
	 */
	public static function get($key,$dflt = '')
	{
		$prefs = global_cache::get(__CLASS__);
		if( isset($prefs[$key]) && $prefs[$key] !== '' ) {
			return $prefs[$key];
		}
		return $dflt;
	}


	/**
	 * Test if a preference exists and it's value is not ''
	 *
	 * @param string $key The preference name
	 * @return bool
	 */
	public static function exists($key)
	{
		$prefs = global_cache::get(__CLASS__);
		return ( is_array($prefs) && isset($prefs[$key]) && $prefs[$key] !== '' );
	}


	/**
	 * Set a site/module preference
	 *
	 * @param string $key The preference name
	 * @param string $value The preference value
	 */
	public static function set($key,$value)
	{
		$db = CmsApp::get_instance()->GetDb();
		$tbl = CMS_DB_PREFIX.'siteprefs';
		$now = $db->DbTimeStamp(time());
		//self::exists() is uselsss here, it ignores null (hence '') values
		//upsert TODO MySQL ON DUPLICATE KEY UPDATE useful here?
		$query = "UPDATE $tbl SET sitepref_value=?,modified_date=$now WHERE sitepref_name=?";
//		$dbr =
		$db->Execute($query,[$value,$key]);
		$query = <<<EOS
INSERT INTO $tbl (sitepref_name,sitepref_value,create_date)
SELECT ?,?,$now FROM (SELECT 1 AS dmy) Z
WHERE NOT EXISTS (SELECT 1 FROM $tbl T WHERE T.sitepref_name=?)
EOS;
//		$dbr =
		$db->Execute($query,[$key,$value,$key]);

		global_cache::release(__CLASS__);
	}


	/**
	 * Remove a site/module preference
	 *
	 * @param string $key The preference name
	 * @param bool $like Optional flag whether to use preference name approximation, default false
	 */
	public static function remove($key,$like = FALSE)
	{
		$query = 'DELETE FROM '.CMS_DB_PREFIX.'siteprefs WHERE sitepref_name = ?';
		if( $like ) {
			$query = 'DELETE FROM '.CMS_DB_PREFIX.'siteprefs WHERE sitepref_name LIKE ?';
			$key .= '%';
		}
		$db = CmsApp::get_instance()->GetDb();
		$db->Execute($query,[$key]);
		global_cache::release(__CLASS__);
	}

	/**
	 * List preferences by prefix
	 *
	 * @param string $prefix
	 * @return mixed array of preference names that match the prefix, or null
	 * @since 2.0
	 */
	public static function list_by_prefix($prefix)
	{
		if( !$prefix ) return;
		$query = 'SELECT sitepref_name FROM '.CMS_DB_PREFIX.'siteprefs WHERE sitepref_name LIKE ?';
		$db = CmsApp::get_instance()->GetDb();
		$dbr = $db->GetCol($query,[$prefix.'%']);
		if( $dbr ) return $dbr;
	}
} // class
