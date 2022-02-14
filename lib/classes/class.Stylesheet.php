<?php
/*
Class for dealing with a Stylesheet object
Copyright (C) 2010-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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

use CMSMS\AdminUtils;
use CMSMS\DataException;
use CMSMS\DeprecationNotice;
use CMSMS\LockOperations;
use CMSMS\StylesheetOperations;
use InvalidArgumentException;
use LogicException;
use UnexpectedValueException;
use const CMS_ASSETS_PATH;
use const CMS_DEPREC;
use const CMSSAN_FILE;
use function cms_join_path;
use function cms_to_bool;
use function cms_to_stamp;
use function CMSMS\sanitizeVal;
use function endswith;

/**
 * A class of methods for Stylesheet administration via the admin-console
 * UI, or by DesignManager-module etc. The class is not used for
 * stylesheet processing during page-display.
 *
 * @package CMS
 * @license GPL
 * @since 2.99
 * @since 2.0 as global-namespace CmsLayoutStylesheet
 */
class Stylesheet
{
	/**
	 * @ignore
	 * @deprecated since 2.99 use the equivalent StylesheetOperations::TABLENAME
	 */
	const TABLENAME = 'layout_stylesheets';

	/**
	 * @var array stylesheet-file on-save operations, populated on demand
	 * Each member like [optype,param,...] optype = 'delete' etc
	 * @ignore
	 */
	public $fileoperations = [];

	/**
	 * @var assoc array of sheet properties, corresponding to a row of
	 *  StylesheetOperations::TABLENAME. Any that are for internal-use
	 *  only are ignored in this class.
	 * @ignore
	 */
	private $props = [];

	/* *
	 * @var array id's of authorized editors
	 * @ignore
	 */
//	private $editors;

	/**
	 * @var array id's of groups that this stylesheet belongs to
	 * @ignore
	 */
	private $groups; //unset triggers check on 1st use

	/**
	 * @var bool whether any member of $props, or $groups, has been
	 *  changed since last save
	 * @ignore
	 */
	private $dirty = FALSE;

	/**
	 * @var string stylesheet-file content, populated on demand
	 * @ignore
	 */
	private $filecontent;

	// static properties here >> SingleItem properties ?
	/**
	 * @var array
	 * @ignore
	 */
	private static $lock_cache = [];

	/**
	 * @var bool
	 * @ignore
	 */
	private static $lock_cache_loaded = FALSE;

	/**
	 * @var StylesheetOperations object populated on demand
	 * @ignore
	 */
	private static $operations = NULL;

	/**
	 * @ignore
	 */
	public function __clone()
	{
		unset($this->props['id']);
		$this->props['type_dflt'] = FALSE;
		$this->dirty = TRUE;
	}

	/**
	 * @ignore
	 */
	public function __set(string $key, $value)
	{
		switch( $key ) {
			case 'id':
				if( !empty($this->props[$key]) ) {
					throw new LogicException("Stylesheet property '$key' cannot be changed");
				}
				if( $value < 1 ) {
					throw new UnexpectedValueException("Stylesheet property '$key' must be > 0");
				}
			// no break here
			case 'owner_id':
			case 'type_id': // for theming, not yet used
				$this->props[$key] = (int)$value;
				break;
			case 'name':
				$this->set_name($value);
				break;
			case 'content':
				$this->set_content($value);
				break;
			case 'description':
				$str = trim($value);
				$this->props[$key] = ( $str !== '' ) ? $str : NULL;
				break;
			case 'originator':
				if( isset($this->props[$key]) ) {
					throw new LogicException("Stylesheet property '$key' cannot be changed");
				}
				$str = trim($value);
				if( $str === '' || strcasecmp($str, 'core') == 0 ) $str = '__CORE__';
				$this->props[$key] = $str;
				break;
			case 'create_date':
				if( isset($this->props[$key]) ) {
					throw new LogicException("Stylesheet property '$key' cannot be changed");
				}
			// no break here
			case 'modified_date':
				$this->props[$key] = trim($value);
				break;
			case 'contentfile':
				$this->set_content_file($value);
				break;
			case 'listable':
			case 'type_dflt': // for theming, not yet used
				$this->props[$key] = cms_to_bool($value);
				break;
			case 'media_query':
				$this->props[$key] = trim($value);
				break;
			case 'media_type':
				$this->props[$key] = (is_array($value)) ? $value : [$value];
				break;
			case 'groups':
				$this->set_groups($value);
				break;
//			case 'editors':
//				$this->set_additional_editors($value);
//				break;
			default:
				if( isset($this->props[$key]) ) return;
				throw new LogicException("Cannot set invalid stylesheet property '$key'");
		}
		$this->dirty = TRUE;
	}

	/**
	 * @ignore
	 */
	public function __get(string $key)
	{
		switch( $key ) {
			case 'id':
			case 'owner_id':
			case 'type_id': // for theming, not yet used
				return $this->props[$key] ?? 0;
			case 'name':
			case 'content': // raw, maybe a filename
			case 'description':
			case 'originator':
			case 'media_query':
			case 'create_date':
			case 'modified_date':
				return $this->props[$key] ?? '';
			case 'media_type':
				return $this->props[$key] ?? [];
			case 'contentfile':
			case 'type_dflt': // for theming, not yet used
				return $this->props[$key] ?? FALSE;
			case 'listable':
				return $this->props[$key] ?? TRUE;
			case 'groups':
				return $this->groups ?? [];
//			case 'editors':
//				return $this->editors ?? [];
			default:
				throw new LogicException("Cannot retrieve invalid stylesheet property '$key'");
		}
	}

	/**
	 * Get the recordable properties of this stylesheet
	 * @since 2.99
	 * @internal
	 * @ignore
	 *
	 * @return array
	 */
	public function get_properties() : array
	{
		$props = $this->props;
		$props['groups'] = $this->groups ?? [];
		return $props;
	}

	/**
	 * Set the private properties of this stylesheet
	 * @since 2.99
	 * @internal
	 * @ignore
	 *
	 * @param array $props sheet properties from db tables
	 * @throws UnexpectedValueException
	 */
	public function set_properties(array $props)
	{
		$this->groups = $props['groups'] ?? [];
		unset($props['groups']);
		// no support (yet?) for additional editors c.f. Template

		if( $props['media_type'] ) {
			$props['media_type'] = explode(',', $props['media_type']);
		}
		else {
			$props['media_type'] = [];
		}
		// verbatim, no validate / sanitize
		$this->props = $props;

		if( !empty($props['contentfile']) ) {
			if( ($fp = $this->get_content_filename()) ) {
				$this->filecontent = file_get_contents($fp);
			}
			else {
				$this->filecontent = '/* Missing stylsheet file */';
			}
		}
	}

	/**
	 * Get the numeric id of this stylesheet
	 *
	 * @return int, 0 if this stylesheet has not yet
	 *  been saved to the database
	 */
	public function get_id() : int
	{
		return $this->props['id'] ?? 0;
	}

	/**
	 * Set the id of this stylesheet, after it is initially-saved.
	 *
	 * @param int $id
	 * @throws LogicException or UnexpectedValueException
	 */
	public function set_id(int $id)
	{
		if( !empty($this->props['id']) ) {
			throw new LogicException("Stylesheet 'id' property cannot be changed");
		}
		if( $id < 1 ) {
			throw new UnexpectedValueException("Stylesheet 'id' property must be > 0");
		}
		$this->props['id'] = $id;
		// not dirty - this is set only after 1st save
	}

	/**
	 * Get the originator of this stylesheet
	 * @since 2.99
	 *
	 * @return string, '' if the originator has not yet been nominated.
	 */
	public function get_originator() : string
	{
		return $this->props['originator'] ?? '';
	}

	/**
	 * Set the originator of this stylesheet
	 * The originator would normally be '__CORE__' or a module name or a
	 * theme name.
	 * $str is not checked/validated here, other than non-empty.
	 * Any case-variant of 'core' is converted to '__CORE__'.
	 * @since 2.99
	 *
	 * @param string $str
	 * @throws DataException if $str is empty
	 */
	public function set_originator(string $str)
	{
		$str = trim($str);
		if( !$str ) throw new DataException('Stylesheet originator cannot be nameless');
		if( strcasecmp($str, 'core') == 0 ) { $str = '__CORE__'; }
		$this->props['originator'] = $str;
		$this->dirty = TRUE;
	}

	/**
	 * Get the name of this stylesheet
	 *
	 * @return string Default ''
	 */
	public function get_name()
	{
		return $this->props['name'] ?? '';
	}

	/**
	 * Set the name of this stylesheet
	 * Stylesheet names must be unique throughout the system.
	 *
	 * @param string $str acceptable name per AdminUtils::is_valid_itemname()
	 * @throws UnexpectedValueException
	 */
	public function set_name($str)
	{
		$str = trim($str);
		if( !$str || !AdminUtils::is_valid_itemname($str) ) { // allows ' /' chars, unacceptable in a filename
			throw new UnexpectedValueException('Invalid stylesheet name: '.$str);
		}
		if( isset($this->props['name']) ) {
			if( $this->props['name'] == $str ) return;
		}
		if( !empty($this->props['contentfile']) ) {
			$fn = sanitizeVal($str, CMSSAN_FILE); // TODO advise user if $fn != $str
			$this->props['name'] = $fn;
			$this->fileoperations[] = ['rename', $this->props['content'], $fn.'.css'];
			$this->props['content'] = $fn.'.css';
		}
		else {
			$this->props['name'] = $str; // TODO might be unsuitable for future export to file
		}
		// TODO duplicate-name check & reject
		$this->dirty = TRUE;
	}

	/**
	 * Get this stylesheet's description
	 *
	 * @return string  Default ''
	 */
	public function get_description() : string
	{
		return $this->props['description'] ?? '';
	}

	/**
	 * Set this stylesheet's description
	 * No sanitization
	 *
	 * @param string $str
	 */
	public function set_description($str)
	{
		$this->props['description'] = trim($str);
		$this->dirty = TRUE;
	}

	/**
	 * Get the type-id of this stylesheet, if any
	 * @since 2.99
	 *
	 * @return int, 0 if un-typed
	 */
	public function get_type() : int
	{
		return $this->props['type_id'] ?? 0;
		//return StylesheetType::load($id);
	}

	/**
	 * Set the type of this stylesheet
	 * @since 2.99
	 *
	 * @param mixed $a int | null
	 */
	public function set_type($a)
	{
		if (is_null($a)) {
			$this->props['type_id'] = 0;
		}
		elseif (is_numeric($a)) {
			$this->props['type_id'] = (int)$a;
		}
/*		elseif (is_string($a) { TODO
			$this->props['type_id'] = get_idforname($a);
		}
		elseif ($a instanceof StylesheetType) {
			$this->props['type_id'] = $a->id;
		}
*/
		$this->dirty = TRUE;
	}

	/**
	 * Get the flag indicating this stylesheet is the default for its type, if any
	 * @since 2.99
	 *
	 * @return bool
	 */
	public function get_type_default() : bool
	{
		return $this->props['type_dflt'] ?? FALSE;
	}

	/**
	 * Set the flag indicating this stylesheet is the default for its type
	 * @since 2.99
	 *
	 * @param mixed $flag bool or interpretable bool-string
	 */
	public function set_type_default($flag = TRUE)
	{
		$this->props['type_dflt'] = cms_to_bool($flag);
		$this->dirty = TRUE;
	}

	/**
	 * Get the assigned media type(s) for this stylesheet
	 * Media types are used with the \@media css rule
	 *
	 * @deprecated since ?
	 * @return array
	 * @see http://www.w3schools.com/css/css_mediatypes.asp
	 */
	public function get_media_types()
	{
		return $this->props['media_type'] ?? [];
	}

	/**
	 * Test if this stylesheet has the specified media type
	 * Media types are used with the \@media css rule
	 *
	 * @deprecated since ?
	 * @param string $str The media type name
	 * @return bool
	 */
	public function has_media_type($str)
	{
		$str = trim($str);
		if( $str && isset($this->props['media_type']) ) {
			if( in_array($str, $this->props['media_type']) ) return TRUE;
		}
		return FALSE;
	}

	/**
	 * Add the specified media type to the types for this stylesheet
	 * Media types are used with the \@media css rule
	 *
	 * @deprecated since ?
	 * @param string $str The media type name
	 * @return bool
	 */
	public function add_media_type($str)
	{
		$str = trim($str);
		if( !$str ) return;
		if( !is_array($this->props['media_type']) ) $this->props['media_type'] = [];
		$this->props['media_type'][] = $str;
		$this->dirty = TRUE;
	}

	/**
	 * Set all the media type(s) for this stylesheet
	 * Media types are used with the \@media css rule
	 *
	 * @deprecated since ?
	 * @param mixed $arr string | strings array | null
	 */
	public function set_media_types($arr)
	{
		if( !is_array($arr) ) {
			if( $arr && is_string($arr) && !is_numeric($arr) ) {
				$arr = [$arr];
			}
			else {
				return;
			}
		}

		$this->props['media_type'] = $arr;
		$this->dirty = TRUE;
	}

	/**
	 * Get the media query property of this stylesheet
	 *
	 * @see http://en.wikipedia.org/wiki/Media_queries
	 * @return string
	 */
	public function get_media_query()
	{
		return $this->props['media_query'] ?? '';
	}

	/**
	 * Set the media query property of this stylesheet
	 *
	 * @see http://en.wikipedia.org/wiki/Media_queries
	 * @param string $str
	 */
	public function set_media_query($str)
	{
		$this->props['media_query'] = trim($str);
		$this->dirty = TRUE;
	}

	/**
	 * Get the list of group id's (if any) that this stylesheet belongs to
	 * @since 2.99
	 *
	 * @return array Array of integer group id's
	 */
	public function get_groups() : array
	{
		return $this->groups ?? [];
	}

	/**
	 * Set the group id's that this stylesheet belongs to
	 * @since 2.99
	 *
	 * @param mixed $all array | int integer group id(s), maybe empty
	 * @throws InvalidArgumentException
	 */
	public function set_groups($all)
	{
		if( !is_array($all) ) { $all = [$all]; }
		foreach( $all as $id ) {
			if( !is_numeric($id) || (int)$id < 1 ) {
				throw new InvalidArgumentException('Invalid stylesheet-groups data. Expect array of integers, each > 0');
			}
		}
		$this->groups = $all;
		$this->dirty = TRUE;
	}

	/**
	 * Add this stylesheet to a group
	 * @since 2.99
	 *
	 * @throws LogicException
	 * @param mixed $a An integer group id, or a string group name
	 */
	public function add_group($a)
	{
		$group = StylesheetOperations::get_group($a);
		if( $group ) {
			$id = $this->props['id'] ?? 0;
			if( $id < 1 ) return; // TODO warning to caller
			$group->add_members($id); //TODO support member-order other than last
			$group->save();
			$this->get_groups();
			if( !in_array($id, $this->groups) ) {
				$this->groups[] = (int)$id;
				$this->dirty = TRUE;
			}
		}
	}

	/**
	 * Remove this stylesheet from a group
	 * @since 2.99
	 *
	 * @throws LogicException
	 * @param mixed $a An integer group id, or a string group name
	 */
	public function remove_group($a)
	{
		$group = StylesheetOperations::get_group($a);
		if( $group ) {
			$id = $this->props['id'] ?? 0;
			if( $id < 1 ) return; // TODO warning to caller
			$group->remove_members($id);
			$group->save();
			$this->get_groups();
			if( $this->groups ) {
				if( ($p = array_search($id, $this->groups)) !== FALSE ) {
					 unset($this->groups[$p]);
				} // TODO else warn user about failure
			}
			$this->dirty = TRUE;
		}
	}

	/**
	 * Set the owner of this stylesheet
	 * @since 2.99
	 *
	 * @param mixed $a int | numeric string | null
	 */
	public function set_owner($a)
	{
		if (is_null($a)) {
			$this->props['owner_id'] = NULL;
		}
		elseif (is_numeric($a)) {
			$this->props['owner_id'] = (int)$a;
		}
/*		elseif (is_string($a) {
			$this->props['owner_id'] = get_idforname($a); TODO
		}
*/
		$this->dirty = TRUE;
	}

	/**
	 * Get the owner of this stylesheet
	 * @since 2.99
	 *
	 * @return int, 0 if no owner has been specified
	 */
	public function get_owner()
	{
		return $this->props['owner_id'] ?? 0;
	}

	/**
	 * Get a timestamp representing when this stylesheet was first saved
	 *
	 * @return int UNIX UTC timestamp Default 1 (not falsy)
	 */
	public function get_created()
	{
		$str = $this->props['create_date'] ?? '';
		return ($str) ? cms_to_stamp($str) : 1;
	}

	/**
	 * Get a timestamp representing when this stylesheet was last saved
	 *
	 * @return int UNIX UTC timestamp. Default 1 (i.e. not falsy)
	 */
	public function get_modified()
	{
		$str = $this->props['modified_date'] ?? $this->props['create_date'] ?? '';
		return ($str) ? cms_to_stamp($str) : 1;
	}

	/* *
	 * Test whether the specified user is authorized to edit this template object
	 *
	 * @param mixed $a string username | int user id
	 * @return bool
	 */
/*	public function can_edit($a)
	{
		$res = self::_resolve_user($a);
		if( isset($this->props['owner_id']) && $res == $this->props['owner_id'] ) return true;
		return !empty($this->editors) && in_array($res,$this->editors);
	}
*/
	/* *
	 * Set the admin-user account(s) (other than the owner) that are
	 *  authorized to edit this stylesheet object
	 *
	 * @throws UnexpectedValueException
	 * @param mixed $a string[] (usernames) | int[] (user ids, and negative group ids) | single user identifier
	 */
/*	public function set_additional_editors($a)
	{
		if( !is_array($a) ) {
			if( is_string($a) || (is_numeric($a) && $a > 0) ) {
				// maybe a single value...
				$res = self::_resolve_user($a);
				$this->editors = [$res];
				$this->dirty = true;
			}
		}
		else {
			$tmp = [];
			for( $i = 0, $n = count($a); $i < $n; $i++ ) {
				if( !is_numeric($a[$i]) ) continue;
				$tmp2 = (int)$a[$i];  // intentional cast to int, may have negative values.
				if( $tmp2 != 0 ) {
					$tmp[] = $tmp2;
				}
				elseif( is_string($a[$i]) ) {
					$tmp[] = self::_resolve_user($a[$i]);
				}
			}
			$this->editors = $tmp;
			$this->dirty = true;
		}
	}
*/
	/* *
	 * Get the userid's (other than the owner) that are authorized to
	 *  edit this stylesheet
	 *
	 * @return array of integer user id's, maybe empty
	 */
/*	public function get_additional_editors()
	{
		return $this->editors ?? [];
	}
*/
	/**
	 * Get the flag indicating this sheet may be listed
	 * @since 2.99
	 *
	 * @return bool
	 */
	public function get_listable() : bool
	{
		return $this->props['listable'] ?? FALSE;
	}

	/**
	 * Set the flag indicating this sheet may be listed
	 * @since 2.99
	 *
	 * @param mixed $flag bool or interpretable bool-string
	 */
	public function set_listable($flag = TRUE)
	{
		$state = cms_to_bool($flag);
		$this->props['listable'] = $state;
		$this->dirty = TRUE;
	}

	/**
	 * @ignore
	 */
	private static function get_locks() : array
	{
		if( !self::$lock_cache_loaded ) {
			self::$lock_cache = [];
			$tmp = LockOperations::get_locks('stylesheet');
			if( $tmp ) {
				foreach( $tmp as $one ) {
					self::$lock_cache[$one['oid']] = $one;
				}
			}
			self::$lock_cache_loaded = TRUE;
		}
		return self::$lock_cache;
	}

	/**
	 * Get a lock (if any exists) for this stylesheet
	 * @see Lock
	 *
	 * @return mixed Lock | null
	 */
	public function get_lock()
	{
		if( empty($this->props['id']) ) return NULL;
		$locks = self::get_locks();
		return $locks[$this->props['id']] ?? NULL;
	}

	/**
	 * Test whether this stylesheet is locked
	 *
	 * @return bool
	 */
	public function locked() : bool
	{
		$lock = $this->get_lock();
		return is_object($lock);
	}

	/**
	 * Test whether this stylesheet is locked by an expired lock.
	 * If the object is not locked FALSE is returned
	 *
	 * @return bool
	 */
	public function lock_expired() : bool
	{
		$lock = $this->get_lock();
		if( !is_object($lock) ) return FALSE;
		return $lock->expired();
	}

	/**
	 * Get the content of this stylesheet
	 *
	 * @return string
	 */
	public function get_content() : string
	{
		if( !empty($this->props['contentfile']) ) {
			if( !isset($this->filecontent) ) {
				if( ($fp = $this->get_content_filename()) ) {
					$this->filecontent = file_get_contents($fp);
				}
				else {
					$this->filecontent = '/* Missing stylesheet file */';
				}
			}
			return $this->filecontent;
		}
		return $this->props['content'] ?? '';
	}

	/**
	 * Set the content of this stylesheet
	 * No sanitization
	 *
	 * @param string $str not empty
	 * @throws LogicException
	 */
	public function set_content($str)
	{
		$str = trim($str);
		if( !$str ) throw new LogicException('Stylesheet cannot be empty');
//		if( !$str ) $str = '/* empty stylesheet */';
		if( !empty($this->props['contentfile']) ) {
			$this->filecontent = $str;
			// park new content for transfer to file when this object is saved
			$fn = basename($this->get_content_filename());
			$this->fileoperations[] = ['store', $fn, $str];
		}
		else {
			$this->props['content'] = $str;
		}
		$this->dirty = TRUE;
	}

	/**
	 * Get the filepath of the file which is supposed to contain this
	 *  stylesheet's content
	 * @since 2.2
	 *
	 * @return string
	 */
	public function get_content_filename() : string
	{
		$fn = $this->props['content'] ?? '';
		if( $fn && strpos($fn, ' ') === FALSE && endswith($fn, '.css') ) {
			return cms_join_path(CMS_ASSETS_PATH, 'styles', $fn);
		}
		return '';
	}

	/**
	 * Get whether this stylesheet's content resides in a file
	 *  (as distinct from the database)
	 * @since 2.99
	 *
	 * @return bool
	 */
	public function get_content_file() : bool
	{
		return $this->props['contentfile'] ?? FALSE;
	}

	/**
	 * Get whether this stylesheet's content resides in a file
	 * @since 2.2
	 * @deprecated since 2.99 this is an alias for get_content_file()
	 *
	 * @return bool
	 */
	public function has_content_file()
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'Stylesheet::get_content_file'));
		return $this->props['contentfile'] ?? FALSE;
	}

	/**
	 * Set the value of the flag indicating the content of this stylesheet
	 *  resides in a filesystem file
	 * @since 2.99
	 *
	 * @param mixed $flag recognized by cms_to_bool(). Default true.
	 */
	public function set_content_file($flag = TRUE)
	{
		$state = cms_to_bool($flag);
		$current = !empty($this->props['contentfile']);
		if( $state === $current ) {
			if( !$current ) {
				$this->props['contentfile'] = FALSE; // ensure it's present
			}
			return;
		}
		if( !empty($this->props['content']) ) {
			$fn = $this->get_content_filename();
			if( $state ) {
				if( $fn ) return; // already set up
				$fn = sanitizeVal($this->props['name'], CMSSAN_FILE);
				// park current content for save-in-file when this object is saved
				$this->fileoperations[] = ['store', $fn.'.css', ($this->props['content'] ?? '')];
				$this->props['content'] = $fn.'.css';
			}
			elseif( $fn ) {
				// park current filename for deletion when this object is saved
				$this->fileoperations[] = ['delete', basename($fn)];
				$this->props['content'] = file_get_contents($fn);
			}
			else {
				$this->props['content'] = '';
			}
		}
		$this->props['contentfile'] = $state;
		$this->dirty = TRUE;
	}

//======= DEPRECATED METHODS EXPORTED TO StylesheetOperations CLASS =======

	/**
	 * @ignore
	 * @since 2.99
	 * @return StylesheetOperations
	 */
	protected function get_operations()
	{
		if( !self::$operations ) self::$operations = new StylesheetOperations;
		return self::$operations;
	}

	/**
	 * Validate this stylesheet for suitability for saving to the database
	 * Stylesheets must have a valid name (only certain characters accepted),
	 * and must have at least some content
	 * @deprecated since 2.99 use the corresponding StylesheetOperations method
	 *
	 * @throws UnexpectedValueException
	 */
	protected function validate()
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'StylesheetOperations::validate_stylesheet()'));
		$this->get_operations()::validate_stylesheet();
	}

	/**
	 * Save this stylesheet to the database
	 * Objects are saved only if they are dirty (have been modified in some way, or have no id)
	 *
	 * This method sends events before and after saving.
	 * EditStylesheetPre is sent before an existing stylesheet is saved to the database
	 * EditStylesheetPost is sent after an existing stylesheet is saved to the database
	 * AddStylesheetPre is sent before a new stylesheet is saved to the database
	 * AddStylesheetPost is sent after a new stylesheet is saved to the database
	 *
	 * @deprecated since 2.99 use the corresponding StylesheetOperations method
	 * @throws SQLException
	 */
	public function save()
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'StylesheetOperations::save_stylesheet()'));
		if( $this->dirty ) {
			$this->get_operations()::save_stylesheet($this);
			$this->dirty = FALSE;
		}
	}

	/**
	 * Delete this stylesheet from the database
	 * This method deletes the appropriate records from the database,
	 * deletes the id from this object, and marks the object as dirty so that it can be saved again
	 *
	 * This method triggers the DeleteStylesheetPre and DeleteStylesheetPost events
	 * @deprecated since 2.99 use the corresponding StylesheetOperations method
	 */
	public function delete()
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'StylesheetOperations::delete_stylesheet()'));
		$this->get_operations()::delete_stylesheet($this);
		$this->dirty = TRUE;
	}

	/**
	 * Load the specified stylesheet
	 * @deprecated since 2.99 use the corresponding StylesheetOperations method
	 *
	 * @param mixed $a Either an integer stylesheet id, or a string stylesheet name.
	 * @return Stylesheet
	 * @throws UnexpectedValueException
	 */
	public static function load($a)
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'StylesheetOperations::get_stylesheet()'));
		return $this->get_operations()::get_stylesheet($a);
	}

	/**
	 * Load multiple stylesheets in an optimized fashion
	 *
	 * This method does not throw exceptions if one requested id, or name does not exist.
	 * @deprecated since 2.99 use the corresponding StylesheetOperations method
	 *
	 * @param array $ids Array of integer stylesheet id's or an array of string stylesheet names.
	 * @param bool $deep whether or not to load associated data
	 * @return array Stylesheet object(s) | empty
	 * @throws UnexpectedValueException
	 */
	public static function load_bulk($ids, $deep = TRUE)
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'StylesheetOperations::get_bulk_stylesheets()'));
		return $this->get_operations()::get_bulk_stylesheets($ids, $deep);
	}

	/**
	 * Return all stylesheet objects or stylesheet names.
	 * @deprecated since 2.99 use the corresponding StylesheetOperations method
	 *
	 * @param bool $by_name Optional flag indicating the output format. Default FALSE.
	 * @return array If $by_name is TRUE then each value will have
	 *  stylesheet id and name. Otherwise, id and a Stylesheet object
	 */
	public static function get_all($by_name = FALSE)
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'StylesheetOperations::get_all_stylesheets()'));
		return $this->get_operations()::get_all_stylesheets($by_name);
	}

	/**
	 * Test if the specific stylesheet (by name or id) is loaded
	 * @deprecated since 2.99
	 *
	 * @param mixed $id Either an integer stylesheet id, or a string stylesheet name
	 * @return bool FALSE always
	 */
	public static function is_loaded($id)
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method returns false always', ''));
		return FALSE;
	}

	/**
	 * Generate a unique name for a stylesheet
	 * @deprecated since 2.99 use the corresponding StylesheetOperations method
	 *
	 * @param string $prototype A prototype stylesheet name
	 * @param string $prefix An optional name prefix
	 * @return string
	 * @throws UnexpectedValueException or LogicException
	 */
	public static function generate_unique_name($prototype, $prefix = NULL)
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'StylesheetOperations::get_unique_name()'));
		return $this->get_operations()::get_unique_name($prototype, $prefix);
	}
} // class
