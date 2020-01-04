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
 * A class for working with site- and module-preferences/properties/parameters
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
	const MODULE_SIG = '_mapi_pref_';

	/**
	 * @ignore
	 * Constant indicating a serialized value
	 */
	const SERIAL = '_S8D_'; // shortened '_SERIALIZED_'

	/**
	 * @ignore
	 * Acceptable serialized classes in property values
	 */
	const PREF_CLASSES = ['stdClass'];

	/**
	 * @ignore
	 */
	private function __construct() {}
	private function __clone() {}

	/**
	 * @ignore
	 * @internal
	 */
	public static function setup()
	{
		$obj = new global_cachable(self::class,function()
		{
			return self::_read();
		});
		global_cache::add_cachable($obj);
	}

	/**
	 * Read cached site-preferences, NOT module-preferences
	 * @ignore
	 * @internal
	 * @return mixed array | null The array might be empty
	 */
	private static function _read()
	{
		$db = CmsApp::get_instance()->GetDb();

		if( !$db ) {
			return;
		}
		$query = 'SELECT sitepref_name,sitepref_value FROM '.CMS_DB_PREFIX.'siteprefs WHERE sitepref_name NOT LIKE "%'.self::MODULE_SIG.'%" ORDER BY sitepref_name';
		$dbr = $db->GetAssoc($query);
		if( $dbr ) {
			return $dbr;
		}
		return [];
	}

	/**
	 * Retrieve specified preference(s) without using the cache.
	 * This is for getting parameter(s) needed to init the site-prefs cache, and
	 * for getting module-preferences, and for use in async tasks, where the cache
	 * is N/A.
	 *
	 * @since 2.3
	 * @param mixed string | array $key Preference name(s)
	 * @param mixed singular | array $dflt Optional default value(s)
	 * @return mixed value | array
	 */
	public static function getraw($key, $dflt = '')
	{
		$db = CmsApp::get_instance()->GetDb();

		if( !$db ) {
			return $dflt;
		}
		$l = strlen(self::SERIAL);
		$query = 'SELECT sitepref_name,sitepref_value FROM '.CMS_DB_PREFIX.'siteprefs WHERE sitepref_name';
		if( is_array($key) ) {
			$query .= ' IN ('.str_repeat('?,', count($key) - 1).'?)';
			$dbr = $db->GetAssoc($query, $key);
			foreach( $key as $i => $one ) {
				if( isset($dbr[$one]) ) {
					if( strncmp($dbr[$one],self::SERIAL,$l) == 0 ) {
						$dbr[$one] = unserialize(substr($prefs[$key],$l),['allowed_classes' => self::PREF_CLASSES]);
					}
				}
				else {
					$dbr[$one] = $dflt[$i] ?? end($dflt);
				}
			}
			return $dbr;
		}
		else {
			$query .= '=?';
			$dbr = $db->GetRow($query,[$key]);
			if( $dbr ) {
				$value = end($dbr);
				if( strncmp($value,self::SERIAL,$l) == 0 ) {
					$value = unserialize(substr($val,$l),['allowed_classes' => self::PREF_CLASSES]);
				}
				return $value;
			}
			return $dflt;
		}
	}

	/**
	 * Retrieve a site/module preference if it is set, or else
	 * return a default value.
	 *
	 * @param string $key The preference name
	 * @param mixed  $dflt Optional default value
	 * @return string
	 */
	public static function get(string $key,$dflt = '')
	{
		$prefs = global_cache::get(self::class);
		if( isset($prefs[$key]) && $prefs[$key] !== '' ) {
			$l = strlen(self::SERIAL);
			if( strncmp($prefs[$key],self::SERIAL,$l) == 0 ) {
				return unserialize(substr($prefs[$key],$l),['allowed_classes' => self::PREF_CLASSES]);
			}
			return $prefs[$key];
		}
		return $dflt;
	}

	/**
	 * Test if a preference exists and its value is not ''
	 *
	 * @param string $key The preference name
	 * @return bool
	 */
	public static function exists($key)
	{
		$prefs = global_cache::get(self::class);
		return ( is_array($prefs) && isset($prefs[$key]) && $prefs[$key] !== '' );
	}

	/**
	 * Set a site/module preference
	 *
	 * @param string $key The preference name
	 * @param mixed  $value The preference value
	 */
	public static function set(string $key,$value)
	{
		$db = CmsApp::get_instance()->GetDb();
		$tbl = CMS_DB_PREFIX.'siteprefs';
		$now = $db->DbTimeStamp(time());
		if( !(is_scalar($value) || is_null($value)) ) {
			$value = self::SERIAL.serialize($value);
		}
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

		if( strpos($key,self::MODULE_SIG) === FALSE ) {
			global_cache::release(self::class);
		}
	}

	/**
	 * Remove a site/module preference
	 *
	 * @param string $key The preference name
	 * @param bool   $like Optional flag whether to interpret $key wildcard-enabled. Default false.
	 * If $key does not include '%' char(s), then one such is appended i.e. $key
	 * is treated as a prefix.
	 */
	public static function remove(string $key,bool $like = FALSE)
	{
		if( $like ) {
			$query = 'DELETE FROM '.CMS_DB_PREFIX.'siteprefs WHERE sitepref_name LIKE ?';
			if (strpos($key, '%') === FALSE) {
				$key .= '%';
			}
		}
		else {
			$query = 'DELETE FROM '.CMS_DB_PREFIX.'siteprefs WHERE sitepref_name = ?';
		}
		$db = CmsApp::get_instance()->GetDb();
		$db->Execute($query,[$key]);
		if( strpos($key,self::MODULE_SIG) === FALSE) {
			global_cache::release(self::class);
		}
	}

	/**
	 * List preference-names having the specified prefix
	 * @since 2.0
	 *
	 * @param string $prefix Preference-name prefix
	 * @return mixed array of preference names that match the prefix, or null
	 */
	public static function list_by_prefix(string $prefix)
	{
		if( !$prefix ) return;
		$query = 'SELECT sitepref_name FROM '.CMS_DB_PREFIX.'siteprefs WHERE sitepref_name LIKE ?';
		$db = CmsApp::get_instance()->GetDb();
		$dbr = $db->GetCol($query,[$prefix.'%']);
		if( $dbr ) return $dbr;
	}
} // class
