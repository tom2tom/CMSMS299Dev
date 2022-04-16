<?php
/*
Class and utilities for working with user preferences.
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

namespace CMSMS {

use CMSMS\SingleItem;

/**
 * A class for working with preferences associated with admin users
 *
 * @package CMS
 * @license GPL
 * @since 3.0
 * @since 1.10 as cms_userprefs
 */
final class UserParams
{
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

    // static properties here >> SingleItem property|ies ?
	/**
	 * @ignore
	 * Intra-request cache
	 */
	private static $_prefs = null;

	/**
	 * @ignore
	 */
	private function __construct() {}
	private function __clone() {}

	/**
	 * @ignore
	 */
	private static function _read(int $userid)
	{
		if( is_array(self::$_prefs) && isset(self::$_prefs[$userid]) && is_array(self::$_prefs[$userid]) ) return;

		$db = SingleItem::Db();
		$query = 'SELECT preference,value FROM '.CMS_DB_PREFIX.'userprefs WHERE user_id = ?';
		$dbr = $db->getAssoc($query,[$userid]);
		if( $dbr ) {
			self::$_prefs[$userid] = $dbr;
		}
		else {
			self::$_prefs[$userid] = [];
		}
	}

	/**
	 * @ignore
	 */
	private static function _userid()
	{
		return get_userid(false);
	}

	/**
	 * @ignore
	 */
	private static function _reset()
	{
		self::$_prefs = null;
	}

	/**
	 * Get a preference value (if such exists and is not '') for the specified user
	 * or otherwise return a default value.
	 *
	 * @param int    $userid The user id
	 * @param string $key The preference name
	 * @param mixed  $dflt Optional default value
	 * @return mixed
	 */
	public static function get_for_user(int $userid,string $key,$dflt = '')
	{
		self::_read($userid);
		if( isset(self::$_prefs[$userid][$key]) && self::$_prefs[$userid][$key] != '' ) {
			$value = self::$_prefs[$userid][$key];
			$l = strlen(self::SERIAL);
			if( strncmp($value,self::SERIAL,$l) == 0 ) {
				$value = unserialize(substr($val,$l),['allowed_classes' => self::PREF_CLASSES]);
			}
			return $value;
		}
		return $dflt;
	}

	/**
	 * Get a preference value (if such exists and is not '') for the current user
	 * or otherwise return a default value.
	 *
	 * @param string $key The preference name
	 * @param mixed  $dflt Optional default value
	 * @return mixed
	 */
	public static function get(string $key,$dflt = '')
	{
		return self::get_for_user(self::_userid(),$key,$dflt);
	}

	/**
	 * Return all preferences for the specified user.
	 *
	 * @param int $userid
	 * @return array Assoc. array of preferences and values | empty
	 */
	public static function get_all_for_user(int $userid)
	{
		self::_read($userid);
		if( isset(self::$_prefs[$userid]) ) {
			$ret = [];
			$l = strlen(self::SERIAL);
			foreach(self::$_prefs[$userid] as $key => $value ) {
				if( strncmp($value,self::SERIAL,$l) == 0 ) {
					$value = unserialize(substr($value,$l),['allowed_classes' => self::PREF_CLASSES]);
				}
				$ret[$key] = $value;
			}
			return $ret;
		}
		return [];
	}

	/**
	 * Test if a preference exists (set and value is not '') for a user
	 *
	 * @param int $userid The user id
	 * @param string $key The preference name
	 * @return bool
	 */
	public static function exists_for_user(int $userid,string $key)
	{
		self::_read($userid);
		return ( isset(self::$_prefs[$userid][$key]) && self::$_prefs[$userid][$key] !== '' ) ;
	}

	/**
	 * Test if a preference exists (set and value is not '') for the current user
	 *
	 * @param string $key The preference name
	 * @return bool
	 */
	public static function exists($key)
	{
		return self::exists_for_user(self::_userid(),$key);
	}

	/**
	 * Set a preference for the specified user
	 *
	 * @param int    $userid The user id
	 * @param string $key The preference name
	 * @param mixed  $value The preference value
	 */
	public static function set_for_user(int $userid,string $key,$value)
	{
		if( !(is_scalar($value) || is_null($value)) ) {
			$value = self::SERIAL.serialize($value);
		}
		self::_read($userid);
		$db = SingleItem::Db();
		if(  !isset(self::$_prefs[$userid][$key]) ) {
			$query = 'INSERT INTO '.CMS_DB_PREFIX.'userprefs (user_id,preference,value) VALUES (?,?,?)';
//			$dbr =
			$db->execute($query,[$userid,$key,$value]);
		}
		else {
			$query = 'UPDATE '.CMS_DB_PREFIX.'userprefs SET value = ? WHERE user_id = ? AND preference = ?';
//			$dbr = useless for update
			$db->execute($query,[$value,$userid,$key]);
		}
		self::$_prefs[$userid][$key] = $value;
	}

	/**
	 * Set a preference for the current logged in user.
	 *
	 * @param string $key The preference name
	 * @param mixed  $value The preference value
	 */
	public static function set(string $key,$value)
	{
		self::set_for_user(self::_userid(),$key,$value);
	}

	/**
	 * Remove preference(s) for the specified user
	 *
	 * @param int    $userid The user id
	 * @param string $key  Optional preference name. If not specified, all preferences for this user will be removed.
	 * @param bool   $like Optional flag whether to interpret $key as a preference-name prefix. Default false.
	 */
	public static function remove_for_user(int $userid,string $key = '',bool $like = FALSE)
	{
		$userid = (int)$userid;
		self::_read($userid);
		$db = SingleItem::Db();
		$query = 'DELETE FROM '.CMS_DB_PREFIX.'userprefs WHERE user_id = ?';
		$parms = [$userid];
		if( $key ) {
			if( $like ) {
				$query2 = ' AND preference LIKE ?';
				$key = $db->escStr($key).'%';
			}
			else {
				$query2 = ' AND preference = ?';
			}
			$query .= $query2;
			$parms[] = $key;
		}
		$db->execute($query,$parms);
		self::_reset();
	}

	/**
	 * Remove preference(s) for the current user
	 *
	 * @param string $key Optional preference name. If not specified, all preferences will be removed.
	 * @param bool   $like Optional flag whether to interpret $key as a preference-name prefix. Default false.
	 */
	public static function remove(string $key = '',bool $like = FALSE)
	{
		self::remove_for_user(self::_userid(),$key,$like);
	}
} // class

} //namespace

namespace {

use CMSMS\DeprecationNotice;
use CMSMS\UserParams;

/**
 * Retrieve the value of the named preference for the given user.
 *
 * @since 0.3
 * @deprecated since 1.10 Use CMSMS\UserParams::get_for_user()
 * @param int    $userid The user id
 * @param string $prefname The preference name
 * @param mixed  $default Optional default value.
 * @return array
 */
function get_preference($userid, $prefname, $default='')
{
	assert(empty(CMS_DEPREC), new DeprecationNotice('method','CMSMS\UserParams::get_for_user'));
	return UserParams::get_for_user($userid,$prefname,$default);
}

/**
 * Set the given preference for the given userid with the given value.
 *
 * @since 0.3
 * @deprecated since 1.10 Use CMSMS\UserParams::set_for_user()
 * @param int $userid The user id
 * @param string  $prefname The preference name
 * @param mixed   $value The preference value (will be stored as a string)
 */
function set_preference($userid, $prefname, $value)
{
	assert(empty(CMS_DEPREC), new DeprecationNotice('method','CMSMS\UserParams::set_for_user'));
	UserParams::set_for_user($userid,$prefname,$value);
}

} //global namespace
