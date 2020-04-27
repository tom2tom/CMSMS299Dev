<?php
#Class for dealing with a CmsLayoutStylesheet object
#Copyright (C) 2010-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
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

namespace CMSMS;

use CmsInvalidDataException;
use CmsLayoutCollection;
use CmsLayoutStylesheet;
use CmsLogicException;
use CMSMS\AdminUtils;
use CMSMS\App;
use CMSMS\Config;
use CMSMS\LockOperations;
use CMSMS\Stylesheet;
use CMSMS\StylesheetOperations;
use CMSMS\StylesheetsGroup;
use CmsSQLErrorException;
use DeprecationNotice;
use const CMS_DB_PREFIX;
use const CMS_DEPREC;
use function cms_join_path;
use function cms_to_bool;
use function cms_to_stamp;
use function munge_string_to_url;

/**
 * A class of methods for dealing with a CmsStylesheet object.
 * This class is for stylesheet administration, by DesignManager module
 * and the like. It is not used for runtime stylesheet retrieval.
 *
 * @package CMS
 * @license GPL
 * @since 2.9
 * @since 2.0 as CmsLayoutStylesheet
 * @author Robert Campbell <calguy1000@cmsmadesimple.org>
 * @see CmsLayoutCollection
 */
class Stylesheet
{
   /**
	* @ignore
	* @deprecated since 2.3 use StylesheetOperations::TABLENAME
	*/
	const TABLENAME = 'layout_stylesheets';

    // static properties here >> StaticProperties class ?
	/**
	* @ignore
	*/
	private static $_operations = null;

   /**
	* @ignore
	*/
	private $_dirty = FALSE;

   /**
    * No setter/getter, so this is populated directly
	* @ignore
	*/
	public $_data = [];

   /**
	* @var array id's of groups that this stylesheet belongs to
	* @ignore
	*/
	private $_groups = null; //null triggers check on 1st use

   /**
	* @var array id's of designs that this stylesheet belongs to
	* @ignore
	*/
//	private $_designs = null; //null triggers check on 1st use

   /**
	* @ignore
	*/
	private static $_lock_cache = [];

   /**
	* @ignore
	*/
	private static $_lock_cache_loaded = FALSE;

   /**
	* @ignore
	*/
	public function __clone()
	{
		if( isset($this->_data['id']) ) unset($this->_data['id']);
		$this->_dirty = TRUE;
	}

   /**
	* Get the id of this stylesheet
	* Returns null if this stylesheet has not yet been saved to the database.
	*
	* @return mixed int | null
	*/
	public function get_id()
	{
		return $this->_data['id'] ?? null;
	}

   /**
	* Get the name of this stylesheet
	*
	* @return string
	*/
	public function get_name()
	{
		return $this->_data['name'] ?? '';
	}

   /**
	* Set the name of this stylesheet
	* Stylesheet names must be unique throughout the system.
	*
	* @throws CmsInvalidDataException
	* @param string $str acceptable name per AdminUtils::is_valid_itemname()
	*/
	public function set_name($str)
	{
		if( !AdminUtils::is_valid_itemname($str)) throw new CmsInvalidDataException("Invalid characters in name: $str");
		$this->_data['name'] = $str;
		$this->_dirty = TRUE;
	}

   /**
	* Get the content of this stylesheet
	*
	* @return string
	*/
	public function get_content()
	{
		return $this->_data['content'] ?? '';
	}

   /**
	* Set the content of this stylesheet
	*
	* @throws CmsInvalidDataException
	* @param string $str not empty
	*/
	public function set_content($str)
	{
		$str = trim($str);
		if( !$str ) throw new CmsInvalidDataException('stylesheet content cannot be empty');
		$this->_data['content'] = $str;
		$this->_dirty = TRUE;
	}

   /**
	* Get the description of this stylesheet
	*
	* @return string
	*/
	public function get_description()
	{
		return $this->_data['description'] ?? '';
	}

   /**
	* Set the description of this stylesheet
	*
	* @param string $str
	*/
	public function set_description($str)
	{
		$str = trim($str);
		$this->_data['description'] = $str;
		$this->_dirty = TRUE;
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
		return $this->_data['media_type'] ?? [];
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
		if( $str && isset($this->_data['media_type']) ) {
			if( in_array($str,$this->_data['media_type']) ) return TRUE;
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
		$str = trim((string) $str);
		if( !$str ) return;
		if( !is_array($this->_data['media_type']) ) $this->_data['media_type'] = [];
		$this->_data['media_type'][] = $str;
		$this->_dirty = TRUE;
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

		$this->_data['media_type'] = $arr;
		$this->_dirty = TRUE;
	}

   /**
	* Get the media query property of this stylesheet
	*
	* @see http://en.wikipedia.org/wiki/Media_queries
	* @return string
	*/
	public function get_media_query()
	{
		return $this->_data['media_query'] ?? '';
	}

   /**
	* Set the media query property of this stylesheet
	*
	* @see http://en.wikipedia.org/wiki/Media_queries
	* @param string $str
	*/
	public function set_media_query($str)
	{
		$str = trim($str);
		$this->_data['media_query'] = $str;
		$this->_dirty = TRUE;
	}

   /**
	* Get the timestamp for when this stylesheet was first saved
	*
	* @return int UNIX UTC timestamp Default 1 (not falsy)
	*/
	public function get_created()
	{
		$str = $this->_data['create_date'] ?? '';
		return ($str) ? cms_to_stamp($str) : 1;
	}

   /**
	* Get the timestamp for when this stylesheet was last saved
	*
	* @return int UNIX UTC timestamp Default 1
	*/
	public function get_modified()
	{
		$str = $this->_data['modified_date'] ?? '';
		return ($str) ? cms_to_stamp($str) : $this->get_created();
	}

   /* *
	* Get the list of design id's (if any) that this stylesheet is assigned to
	*
	* @see CmsLayoutCollection
	* @return array Array of integer design id's
	*/
/*	public function get_designs() DISABLED
	{
		$sid = $this->get_id();
		if( !$sid ) return [];
		if( !is_array($this->_designs) ) {
			$db = App::get_instance()->GetDb();
			$query = 'SELECT design_id FROM '.CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE.' WHERE css_id = ?'; DISABLED
			$tmp = $db->GetCol($query,[$sid]);
			if( $tmp ) $this->_designs = $tmp;
			else $this->_designs = [];
		}
		return $this->_designs;
	}
*/
   /* *
	* Get the numeric id corresponding to $a
	* @since 2.3
	* @param mixed $a An Instance of a CmsLayoutCollection object, or an integer design id, or a string design name
	* @return int
	* @throws UnexpectedValueException
	*/
/*	protected function get_designid($a) : int
	{
		if( is_numeric($a) && $a > 0 ) {
			return (int)$a;
		}
		elseif( is_string($a) && $a !== '' ) {
			$ob = CmsLayoutCollection::load($a);
			if( $ob ) {
				return $ob->get_id();
			}
		}
		elseif( $a instanceof CmsLayoutCollection ) {
			return $a->get_id();
		}

		throw new UnexpectedValueException('Invalid data passed to '.__METHOD__);
	}
*/
   /* *
	* Set the list of design id's that this stylesheet is assigned to
	*
	* @see CmsLayoutCollection
	* @throws CmsInvalidDataException
	* @param array $all Array of integer design id's, maybe empty
	*/
/*	public function set_designs($all)
	{
		if( !is_array($all) ) return;

		foreach( $all as $id ) {
			if( !is_numeric($id) ) throw new CmsInvalidDataException('Invalid data in design list. Expect array of integers');
		}

		$this->_designs = $all;
		$this->_dirty = TRUE;
	}
*/
   /**
	* Assign this stylesheet to a design
	*
	* @throws CmsLogicException
	* @see CmsLayoutCollection
	* @param mixed $a An Instance of a CmsLayoutCollection object, or an integer design id, or a string design name
	*/
/*	public function add_design($a)
	{
		$id = $this->get_designid($a);
		$this->get_designs(); DISABLED
		if( !in_array($id, $this->_designs) ) {
			$this->_designs[] = (int)$id;
			$this->_dirty = TRUE;
		}
	}
*/
   /* *
	* Remove this stylesheet from a design
	*
	* @throws CmsLogicException
	* @see CmsLayoutCollection
	* @param mixed $a An Instance of a CmsLayoutCollection object, or an integer design id, or a string design name
	*/
/*	public function remove_design($a)
	{
		$this->get_designs(); DISABLED
		if( !$this->_designs ) return;

		$id = $this->get_designid($a);
		if( in_array($id, $this->_designs) ) {
			foreach( $this->_designs as $i => $one ) {
				if( $id == $one ) {
					unset($this->_designs[$i]);
					$this->_dirty = TRUE;
					break;
				}
			}
		}
	}
*/
   /**
	* Get the list of group id's (if any) that this stylesheet belongs to
    * @since 2.3
	*
	* @return array Array of integer group id's
	*/
	public function get_groups() : array
	{
		$sid = $this->get_id();
		if( !$sid ) return [];
		if( !is_array($this->_groups) ) {
			$db = App::get_instance()->GetDb();
			$query = 'SELECT group_id FROM '.CMS_DB_PREFIX.StylesheetsGroup::MEMBERSTABLE.' WHERE css_id = ?';
			$tmp = $db->GetCol($query,[$sid]);
			if( $tmp ) $this->_groups = $tmp;
			else $this->_groups = [];
		}
		return $this->_groups;
	}
/*
	public function get_groups() : array
	{
		return $this->get_groups();
	}
*/

   /**
	* Set the list of group id's that this stylesheet belongs to
    * @since 2.3
	*
	* @throws CmsInvalidDataException
	* @param array $all Array of integer group id's, maybe empty
	*/
	public function set_groups(array $all)
	{
		if( !is_array($all) ) return;

		foreach( $all as $id ) {
			if( !is_numeric($id) ) throw new CmsInvalidDataException('Invalid data in groups list. Expect array of integers');
		}

		$this->_groups = $all;
		$this->_dirty = TRUE;
	}
/*
	public function set_groups(array $all)
	{
		$this->set_groups($all);
	}
*/
   /**
	* Add this stylesheet to a group
    * @since 2.3
	*
	* @throws CmsLogicException
	* @see CmsLayoutCollection
	* @param mixed $a An integer group id, or a string group name
	*/
	public function add_group($a)
	{
		$group = StylesheetOperations::get_group($a);
		if( $group ) {
			$id = $this->get_id();
			$group->add_members($id); //TODO support member-order other than last
			$group->save();
			$this->get_groups();
			if( !in_array($id, $this->_groups) ) {
				$this->_groups[] = (int)$id;
				$this->_dirty = TRUE;
			}
        }
	}

   /**
	* Remove this stylesheet from a group
    * @since 2.3
	*
	* @throws CmsLogicException
	* @see CmsLayoutCollection
	* @param mixed $a An integer group id, or a string group name
	*/
	public function remove_group($a)
	{
		$group = StylesheetOperations::get_group($a);
		if( $group ) {
			$id = $this->get_id();
			$group->remove_members($id);
			$group->save();
			$this->get_groups();
			if( $this->_groups ) {
				if( ($p = array_search($id, $this->_groups)) !== FALSE ) {
				     unset($this->_groups[$p]);
				}
			}
			$this->_dirty = TRUE;
		}
	}

   /**
	* @ignore
	*/
	private static function get_locks() : array
	{
		if( !self::$_lock_cache_loaded ) {
			self::$_lock_cache = [];
			$tmp = LockOperations::get_locks('stylesheet');
			if( $tmp ) {
				foreach( $tmp as $one ) {
					self::$_lock_cache[$one['oid']] = $one;
				}
			}
			self::$_lock_cache_loaded = TRUE;
		}
		return self::$_lock_cache;
	}

   /**
	* Get a lock (if any exist) for this object
	*
	* @return mixed Lock | null
	*/
	public function get_lock()
	{
		$locks = self::get_locks();
		return $locks[$this->get_id()] ?? null;
	}

   /**
	* Test whether this stylesheet object is locked
	*
	* @return bool
	*/
	public function locked()
	{
		$lock = $this->get_lock();
		return is_object($lock);
	}

   /**
	* Test whether this stylesheet object is locked by an expired lock.
	* If the object is not locked false is returned
	*
	* @return bool
	*/
	public function lock_expired()
	{
		$lock = $this->get_lock();
		if( !is_object($lock) ) return FALSE;
		return $lock->expired();
	}

   /**
	* Get the filepath of the file which (if relevant) contains this stylesheet's content
	*
	* @since 2.2
	* @return string
	*/
	public function get_content_filename()
	{
		if( $this->get_content_file() ) {
			$config = Config::get_instance();
			return cms_join_path($config['assets_path'],'css',$this->get_content());
		}
		return '';
	}

   /**
	* Get whether this stylesheet's content resides in a file (as distinct from the database)
	*
	* @since 2.3
	* @return bool
	*/
	public function get_content_file()
	{
		return $this->_data['contentfile'] ?? false;
	}

   /**
	* Get whether this stylesheet's content resides in a file
	*
	* @since 2.2
	* @deprecated since 2.3 this is an alias for get_content_file()
	* @return bool
	*/
	public function has_content_file()
	{
        assert(empty(CMS_DEPREC), new DeprecationNotice('method','get_content_file'));
		return $this->get_content_file();
	}

   /**
	* Set the value of the flag indicating the content of this stylesheet resides in a filesystem file
	*
	* @since 2.3
	* @param mixed $flag recognized by cms_to_bool(). Default true.
	*/
	public function set_content_file($flag = true)
	{
		$state = cms_to_bool($flag);
		if( $state ) {
			$this->_data['content'] = munge_string_to_url($this->get_name()).'.'.$this->get_id().'.css';
		}
		elseif( $this->get_content_file() ) {
			$this->_data['content'] = '{* empty Smarty stylesheet *}';
		}
		$this->_data['contentfile'] = $state;
	}

	/**
	 * @ignore
	 * @since 2.3
	 * @return StylesheetOperations
	 */
	protected function get_operations()
	{
		if( !self::$_operations ) self::$_operations = new StylesheetOperations;
		return self::$_operations;
	}

   /**
	* Validate this stylesheet for suitability for saving to the database
	* Stylesheets must have a valid name (only certain characters accepted),
	* and must have at least some content
	* @deprecated since 2.3 use the corresponding StylesheetOperations method
	*
	* @throws CmsInvalidDataException
	*/
	protected function validate()
	{
		$this->get_operations()::validate_stylesheet(); //TODO bug not public
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
	* @deprecated since 2.3 use the corresponding StylesheetOperations method
	* @throws CmsSQLErrorException
	*/
	public function save()
	{
		if( $this->_dirty ) {
			$this->get_operations()::save_stylesheet($this);
			$this->_dirty = FALSE;
		}
	}

   /**
	* Delete this stylesheet from the database
	* This method deletes the appropriate records from the database,
	* deletes the id from this object, and marks the object as dirty so that it can be saved again
	*
	* This method triggers the DeleteStylesheetPre and DeleteStylesheetPost events
	* @deprecated since 2.3 use the corresponding StylesheetOperations method
	*/
	public function delete()
	{
		$this->get_operations()::delete_stylesheet($this);
		$this->_dirty = TRUE;
	}

   /**
	* Load the specified stylesheet
	* @deprecated since 2.3 use the corresponding StylesheetOperations method
	*
	* @param mixed $a Either an integer stylesheet id, or a string stylesheet name.
	* @return CmsLayoutStylesheet
	* @throws CmsInvalidDataException
	*/
	public static function load($a)
	{
		return $this->get_operations()::get_stylesheet($a);
	}

   /**
	* Load multiple stylesheets in an optimized fashion
	*
	* This method does not throw exceptions if one requested id, or name does not exist.
	* @deprecated since 2.3 use the corresponding StylesheetOperations method
	*
	* @param array $ids Array of integer stylesheet id's or an array of string stylesheet names.
	* @param bool $deep whether or not to load associated data
	* @return array Array of CmsLayoutStylesheet objects
	* @throws CmsInvalidDataException
	*/
	public static function load_bulk($ids,$deep = true)
	{
		return $this->get_operations()::get_bulk_stylesheets($ids,$deep);
	}

   /**
	* Return all stylesheet objects or stylesheet names.
	* @deprecated since 2.3 use the corresponding StylesheetOperations method
	*
	* @param bool $by_name Optional flag indicating the output format. Default false.
	* @return mixed If $by_name is true then the output will be an array of rows
    *  each with stylesheet id and name. Otherwise, id and CmsLayoutStylesheet object
	*/
	public static function get_all($by_name = false)
	{
		return $this->get_operations()::get_all_stylesheets($by_name);
	}

   /**
	* Test if the specific stylesheet (by name or id) is loaded
	* @deprecated since 2.3
	*
	* @param mixed $id Either an integer stylesheet id, or a string stylesheet name
	* @return bool false always
	*/
	public static function is_loaded($id)
	{
		return FALSE;
	}

   /**
	* Generate a unique name for a stylesheet
	* @deprecated since 2.3 use the corresponding StylesheetOperations method
	*
	* @param string $prototype A prototype stylesheet name
	* @param string $prefix An optional name prefix
	* @return string
	* @throws CmsInvalidDataException
	* @throws CmsLogicException
	*/
	public static function generate_unique_name($prototype,$prefix = null)
	{
		return $this->get_operations()::get_unique_name($prototype,$prefix);
	}
} // class

\class_alias(Stylesheet::class, 'CmsLayoutStylesheet', false);
