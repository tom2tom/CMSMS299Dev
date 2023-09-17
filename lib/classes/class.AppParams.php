<?php
/*
Class for working with (optionally-namespaced) recorded parameters.
Copyright (C) 2004-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

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

use CMSMS\LoadedDataType;
use CMSMS\Lone;
use const CMS_DB_PREFIX;

/**
 * A class for working with (optionally-namespaced) recorded properties/parameters
 *
 * @package CMS
 * @license GPL
 * @since 3.0
 * @since 1.10 as global-namespace cms_siteprefs
 */
final class AppParams
{
	/**
	 * @ignore
	 */
	const NAMESPACER = '#]]#'; //c.f. formerly '_mapi_pref_' Note: no sep-chars with particular SQL meaning e.g. '\','_','%'

	/**
	 * @ignore
	 * Constant indicating a serialized value, prepended to the stored value
	 */
	private const SERIAL = '_S8D_'; // shortened '_SERIALIZED_'

	/**
	 * @ignore
	 * Acceptable serialized classes in property values
	 */
	private const PREF_CLASSES = ['stdClass'];

	/**
	 * @ignore
	 */
	private function __construct() {}
	private function __clone(): void {}

	/**
	 * @ignore
	 * @internal
	 */
	public static function load_setup()
	{
		$obj = new LoadedDataType('site_params',function() {
			return self::_read();
		});
		Lone::get('LoadedData')->add_type($obj);
	}

	/**
	 * Read cached site-preferences, NOT module-preferences
	 * @ignore
	 * @internal
	 * @return array, maybe empty
	 */
	private static function _read()
	{
		$db = Lone::get('Db');

		// Note: extra '\' follows spacer, to prevent escaping what's next
		$query = 'SELECT sitepref_name,sitepref_value FROM '.CMS_DB_PREFIX.'siteprefs WHERE sitepref_name NOT LIKE \'%'.self::NAMESPACER.'\%\' ORDER BY sitepref_name';
		$dbr = $db->getAssoc($query);
		if( $dbr ) {
			return $dbr;
		}
		return [];
	}

	/**
	 * Retrieve specified preference(s) without using the cache.
	 * This is for getting parameter(s) needed to init the site-prefs
	 * cache, and for getting module-preferences, and for use in async
	 * tasks, where the cache is N/A.
	 *
	 * @since 3.0
	 * @param mixed string (may be empty) | array $key Preference name(s)
	 * @param mixed scalar | array $dflt Optional default value(s)
	 * @param bool   $like Optional flag whether to interpret $key as
	 *  wildcarded. Default false.
	 * @return mixed value | array
	 */
	public static function getraw($key = '',$dflt = '',bool $like = FALSE)
	{
		$db = Lone::get('Db');

		if( !$db ) {
			return $dflt;
		}
		$l = strlen(self::SERIAL);
		if ($like) {
			// TODO SELECT sitepref_name,sitepref_value .... WHERE sitepref_name LIKE strtr($key,'*?','%_');
			return $dflt;
		} else {
			$query = 'SELECT sitepref_name,sitepref_value FROM '.CMS_DB_PREFIX.'siteprefs WHERE sitepref_name';
			if( is_array($key) ) {
				$query .= ' IN ('.str_repeat('?,',count($key) - 1).'?)';
				$dbr = $db->getAssoc($query,$key);
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
				$dbr = $db->getRow($query,[$key]);
				if( $dbr ) {
					$value = (string)end($dbr);
					if( strncmp($value,self::SERIAL,$l) == 0 ) {
						$value = unserialize(substr($val,$l),['allowed_classes' => self::PREF_CLASSES]);
					}
					return $value;
				}
				return $dflt;
			}
		}
	}

	/**
	 * Retrieve a site/module preference if it is set, or else
	 * return a default value.
	 *
	 * @param string $key The preference name (may be empty)
	 * @param mixed scalar | array $dflt Optional default value(s)
	 * @param bool  Since 3.0 $like Optional flag whether to interpret
	 *  $key as (filename) wildcarded. Default false.
	 * @return string
	 */
	public static function get(string $key = '',$dflt = '',bool $like = FALSE)
	{
		$prefs = Lone::get('LoadedData')->get('site_params');
		if( $like ) {
			$arr = array_filter($prefs,function($name) use ($key) {
				return fnmatch($name,$key);
			},ARRAY_FILTER_USE_KEY);
			if( $arr ) {
				return reset($prefs);
			}
			return $dflt;
		} else {
			if( isset($prefs[$key]) && $prefs[$key] !== '' ) {
				$l = strlen(self::SERIAL);
				if( strncmp($prefs[$key],self::SERIAL,$l) == 0 ) {
					return unserialize(substr($prefs[$key],$l),['allowed_classes' => self::PREF_CLASSES]);
				}
				return $prefs[$key];
			}
			return $dflt;
		}
	}

	/**
	 * Test if a preference exists and its value is not ''
	 *
	 * @param string $key The preference name
	 * @return bool
	 */
	public static function exists($key)
	{
		$prefs = Lone::get('LoadedData')->get('site_params');
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
		$db = Lone::get('Db');
		$tbl = CMS_DB_PREFIX.'siteprefs';
		$longnow = $db->DbTimeStamp(time());
		if( !(is_scalar($value) || is_null($value)) ) {
			$value = self::SERIAL.serialize($value);
		}
		//NB self::exists() ignores '' (hence null) values, leading possibly to key duplication error
		//upsert TODO MySQL ON DUPLICATE KEY UPDATE useful here?
		$query = "UPDATE $tbl SET sitepref_value=?,modified_date=$longnow WHERE sitepref_name=?";
//		$dbr =
		$db->execute($query,[$value,$key]);
		//just in case sitepref_name is not unique-indexed by the db
		$query = <<<EOS
INSERT INTO $tbl (sitepref_name,sitepref_value,create_date)
SELECT ?,?,$longnow FROM (SELECT 1 AS dmy) Z
WHERE NOT EXISTS (SELECT 1 FROM $tbl T WHERE T.sitepref_name=?)
EOS;
//		$dbr =
		$db->execute($query,[$key,$value,$key]);

		if( strpos($key,self::NAMESPACER) === FALSE ) {
			Lone::get('LoadedData')->refresh('site_params');
		}
	}

	/**
	 * Remove a site/module preference
	 * If $like is true and $key does not include '%' char(s), then one such is
	 * appended i.e. $key is treated as a prefix.
	 *
	 * @param string $key The preference name (may be empty)
	 * @param bool   $like Optional flag whether to interpret $key as
	 *  wildcarded. Default false. If true, $key may include '%' '_' wildcard(s)
	 *  which will not be escaped, or absent any '%', '%' will be appended.
	 */
	public static function remove(string $key = '',bool $like = FALSE)
	{
		if( $like ) {
			$query = 'DELETE FROM '.CMS_DB_PREFIX.'siteprefs WHERE sitepref_name LIKE ?';
			if (strpos($key,'%') === FALSE) {
				$key .= '%';
			}
		}
		else {
			$query = 'DELETE FROM '.CMS_DB_PREFIX.'siteprefs WHERE sitepref_name = ?';
		}
		$db = Lone::get('Db');
		$db->execute($query,[$key]);
		if( strpos($key,self::NAMESPACER) === FALSE) {
			Lone::get('LoadedData')->refresh('site_params');
		}
	}

	/**
	 * List preference-names having the specified prefix
	 * @since 2.0
	 *
	 * @param string $prefix Preference-name (not-falsy) prefix
	 * @return array, maybe empty
	 */
	public static function list_by_prefix(string $prefix)
	{
		if( !$prefix ) return [];
		$query = 'SELECT sitepref_name FROM '.CMS_DB_PREFIX.'siteprefs WHERE sitepref_name LIKE ?';
		$db = Lone::get('Db');
		$wm = $db->escStr($prefix).'%';
		$dbr = $db->getCol($query,[$wm]);
		if( $dbr ) return $dbr;
		return [];
	}
} // class
//if (!\class_exists('cms_siteprefs', false)) \class_alias(AppParams::class, 'cms_siteprefs', false);
