<?php
#Class for administering a layout template.
#Copyright (C) 2014-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

//namespace CMSMS;

use CMSMS\AdminUtils;
use CMSMS\Lock;
use CMSMS\LockOperations;
use CMSMS\TemplateOperations;
use CMSMS\User;
use CMSMS\UserOperations;

/**
 * A class to administer a layout template.
 * This class is for onetime creation and destruction, and property-modification
 * using the DesignManager module or the like. It is not used for runtime
 * template retrieval.
 *
 * @package CMS
 * @license GPL
 * @since 2.0
 * @author Robert Campbell <calguy1000@cmsmadesimple.org>
 */
class CmsLayoutTemplate
{
   /**
	* @deprecated since 2.3 instead use TemplateOperations::TABLENAME
	* @ignore
	*/
	const TABLENAME = 'layout_templates';

   /**
	* @deprecated since 2.3 instead use TemplateOperations::ADDUSERSTABLE
	* @ignore
	*/
	const ADDUSERSTABLE = 'layout_tpl_addusers';

	/**
	 * Originator for core templates
	 * @since 2.3
	 * @see also CmsLayoutTemplateType::CORE
	 */
	const CORE = '__CORE__';

   /**
	* @var bool whether any property ($_data|$_editors|$_designs|$_groups)
	*  has been changed since last save
	* @ignore
	*/
	private $_dirty = false;

   /**
	* @var assoc array of template properties, corresponding to a row of TemplateOperations::TABLENAME
	* @ignore
	*/
	private $_data;

   /**
	* @var string populated on demand
	* @ignore
	*/
	private $_filecontent;

   /**
	* @var array id's of authorized editors
	* @ignore
	*/
	private $_editors;

   /* *
	* @var array id's of designs that this template belongs to
	* @ignore
	*/
//	private $_designs;

   /**
	* @var array id's of groups that this template belongs to
	* @ignore
	*/
	private $_groups;

   /**
	* @var array
	* @ignore
	*/
	private static $_lock_cache;

   /**
	* @var bool
	* @ignore
	*/
	private static $_lock_cache_loaded = false;

   /**
	* @ignore
	*/
	public function __clone()
	{
		if( isset($this->_data['id']) ) unset($this->_data['id']);
		$this->_data['type_dflt'] = false;
		$this->_dirty = true;
	}

	/**
 	* @ignore
 	*/
 	public function __get($key)
 	{
		switch( $key ) {
			case 'id':
		  	case 'type_id':
				return $this->_data[$key] ?? 0;
			case 'owner_id':
				return $this->_data[$key] ?? -1;
			case 'category_id': // deprecated since 2.3
				return (!empty($this->_groups)) ? reset($this->_groups) : 0;
			case 'name':
			case 'originator':
			case 'content': // raw, maybe a filename
			case 'description':
			case 'create_date':
			case 'modified_date':
				return $this->_data[$key] ?? '';
		  	case 'type_dflt':
		  	case 'contentfile':
				return $this->_data[$key] ?? false;
			case 'listable':
				return $this->_data[$key] ?? true;
			case 'categories': //deprecated since 2.3
			case 'groups':
				return $this->_groups ?? [];
			case 'designs':
				return null; //unused since 2.3
			default:
				throw new CmsLogicException("Attempt to retrieve invalid template property: $key");
		}
	}

	/**
 	* @ignore
 	*/
 	public function __set($key,$value)
 	{
		switch( $key ) {
			case 'id':
		  	case 'type_id':
			case 'owner_id':
				$this->_data[$key] = (int)$value;
				break;
			case 'category_id': // derecated since 2.3
				if (!isset($this->_groups)) {
					$this->_groups = [];
				}
				$this->_groups[] = (int)$value;
				break;
			case 'name':
				$str = trim($value);
				if( !$str || !AdminUtils::is_valid_itemname($str) ) {
					throw new CmsInvalidDataException('Invalid template name: '.$str);
				}
				$this->_data[$key] = $str;
				break;
			case 'content':
				$str = trim($value);
				$this->_data[$key] = ( $str !== '') ? $str : '{* empty Smarty template *}';
				break;
			case 'originator':
				$str = trim($value);
				$this->_data[$key] = ( $str !== '') ? $str : self::CORE;
				break;
			case 'description':
				$str = trim($value);
				$this->_data[$key] = ( $str !== '') ? $str : null;
				break;
			case 'create_date':
				if( isset($this->_data[$key]) ) {
					throw new CmsLogicException("Attempt to set invalid template property: $key");
				}
			// no break here
			case 'modified_date':
				$this->_data[$key] = trim($value);
				break;
			case 'type_dflt':
			case 'listable':
			case 'contentfile':
				$this->_data[$key] = cms_to_bool($value);
				break;
			case 'categories': //deprecated since 2.3
			case 'groups':
				$this->_groups = $value;
				break;
			case 'designs':
				return; //unused since 2.3
			default:
				throw new CmsLogicException("Attempt to set invalid template property: $key");
		}
		$this->_dirty = true;
	}

   /**
	* Get all the current properties of this template
	* @since 2.3
	*/
	public function get_properties() : array
	{
		$res = [];
		foreach( array_keys($this->_data) as $key ) {
			$res[$key] = $this->__get($key);
		}

		$res['editors'] = $this->_editors ?? [];
//		$res['designs'] = $this->_designs ?? [];
		$res['groups'] = $this->_groups ?? [];

		return $res;
	}

   /**
	* Set all the current properties of this template
	* @since 2.3
	* @throws CmsInvalidDataException, CmsLogicException
	*/
	public function set_properties(array $params)
	{
		$this->_editors = $params['editors'] ?? [];
		unset($params['editors']);
//		$this->_designs = $params['designs'] ?? [];
//		unset($params['designs']);
		$this->_groups = $params['groups'] ?? [];
		unset($params['groups']);

		foreach( $params as $key=>$value ) {
			$this->__set($key,$value);
		}
		$this->_dirty = true;
	}

   /**
	* Get the id (number) of this template (default 0)
	*
	* @return int
	*/
	public function get_id()
	{
		return $this->id;
	}

   /**
	* Get the name of this template (default '')
	*
	* @return string
	*/
	public function get_name()
	{
		return $this->name;
	}

   /**
	* Set the name of this template
	*
	* The name cannot be empty, can only consist of a few characters
	* in the name and must be unique
	*
	* @param string $str
	* @throws CmsInvalidDataException
	*/
	public function set_name($str)
	{
		$this->name = $str;
		$this->_dirty = true;
	}

	/**
	* Get the owner/originator of this template (default '')
	* @since 2.3
	*
	* @return string
	*/
	public function get_originator() : string
	{
		return $this->originator;
	}

   /**
	* Set the owner/originator of this template
	* @since 2.3
	*
	* @param string $str
	*/
	public function set_originator(string $str)
	{
		$this->originator = $str;
		$this->_dirty = true;
	}

   /**
	* Get the content of this template (default '')
	*
	* @return string
	*/
	public function get_content()
	{
		if( $this->contentfile ) {
			if( !isset($this->_filecontent) ) {
				if( ($fp = $this->get_content_filename()) ) {
					$this->_filecontent = file_get_contents($fp);
				}
				else {
					$this->_filecontent = 'Missing file';
				}
			}
			return $this->_filecontent;
		}
		return $this->content;
	}

   /**
	* Set the content of this template
	* No sanitization
	*
	* @param string $str Smarty template text
	*/
	public function set_content($str)
	{
		if( $this->contentfile) {
			$this->_filecontent = $str;
		}
		else {
			$this->content = $str;
		}
		$this->_dirty = true;
	}

   /**
	* Get this template's description (default '')
	*
	* @return string
	*/
	public function get_description()
	{
		return $this->description;
	}

   /**
	* Set this template's description
	* No sanitization
	*
	* @param string $str
	*/
	public function set_description($str)
	{
		$this->description = $str;
		$this->_dirty = true;
	}

   /**
	* Get the type id of this template (default 0)
	*
	* @return int
	*/
	public function get_type_id()
	{
		return $this->type_id;
	}

   /**
	* Set the type id of this template
	*
	* @throws CmsLogicException
	* @param mixed $a Either an instance of CmsLayoutTemplateType object,
	*  an integer type id, or a string template type identifier
	* @see CmsLayoutTemplateType
	*/
	public function set_type($a)
	{
		if( $a instanceof CmsLayoutTemplateType ) {
			$id = $a->get_id();
		}
		elseif( is_numeric($a) && (int)$a > 0 ) {
			$id = (int)$a;
		}
		elseif( is_string($a) && $a !== '' ) {
			$type = CmsLayoutTemplateType::load($a);
			$id = $type->get_id();
		}
		else {
			throw new CmsLogicException('Invalid data passed to '.__METHOD__);
		}

		$this->type_id = $id;
		$this->_dirty = true;
	}

   /**
	* Test whether this template is the default template for its type
	*
	* @return bool
	* @see CmsLayoutTemplateType
	*/
	public function get_type_dflt()
	{
		return $this->type_dflt;
	}

   /**
	* Set the value of the flag that indicates whether this template is the default template for its type
	* If true, then when this template is saved this property will be unset for all other
	* templates of the same type.
	*
	* @param mixed $flag recognized by cms_to_bool(). Default true.
	* @see CmsLayoutTemplateType
	*/
	public function set_type_dflt($flag = true)
	{
		$this->type_dflt = $flag;
		$this->_dirty = true;
	}

   /**
	* Get 'the' (actually, the first-recorded) group id for this template (default 0)
	* A template is not required to be in any group
	*
	* @deprecated since 2.3 templates may belong to multiple groups
	* @return int, 0 if no group exists
	*/
	public function get_category_id()
	{
		if( !empty($this->_groups) ) {
			return reset($this->_groups); // just grab the 1st
		}
		return 0;
	}

   /**
	* Get 'the' group-(aka category-)object for this template (if any)
	* A template is not required to be in any group
	*
	* @deprecated since 2.3 templates may be in multiple groups
	* @return mixed CmsLayoutTemplateCategory object | null
	* @see CmsLayoutTemplateCategory
	*/
	public function get_category()
	{
		$id = $this->get_category_id();
		if( $id > 0 ) return CmsLayoutTemplateCategory::load($id);
	}

   /**
	* Get the numeric id corresponding to $a
	* @since 2.3
	* @param mixed $a A CmsLayoutTemplateCategory object, an integer group id, or a string group name.
	* @return int
	* @throws CmsLogicException if nothing matches
	*/
	protected function get_groupid($a)
	{
		if( is_numeric($a) && (int)$a > 0 ) {
			return (int)$a;
		}
		elseif( (is_string($a) && strlen($a)) || (int)$a > 0 ) {
			$ob = CmsLayoutTemplateCategory::load($a);
			if( $ob ) {
				return $ob->get_id();
			}
		}
		elseif( $a instanceof CmsLayoutTemplateCategory ) {
			return $a->get_id();
		}
		throw new CmsLogicException('Invalid data passed to '.__METHOD__);
	}

   /**
	* Get a list of the groups (id's) that this template belongs to
	*
	* @since 2.3
	* @return array of integers, maybe empty
	*/
	public function get_groups() : array
	{
		return $this->_groups ?? [];
	}

   /**
	* Set 'the' group of this template
	*
	* @deprecated since 2.3 templates may be in multiple groups
	* @throws CmsLogicException
	* @param mixed $a Either a CmsLayoutTemplateCategory object,
	*  a group name (string) or group id (int)
	* @see CmsLayoutTemplateCategory
	*/
	public function set_category($a)
	{
		if( !$a ) return;
		$id = $this->get_groupid($a);
		if( empty($this->_groups) ) {
			$this->_groups = [(int)$id];
			$this->_dirty = true;
		}
		elseif( !in_array($id, $this->_groups) ) {
			$this->_groups[] = (int)$id;
			$this->_dirty = true;
		}
	}

   /**
	* Set the list of groups that this template belongs to
	*
	* @since 2.3
	* @param array $all integers[], may be empty
	* @throws CmsInvalidDataException
	*/
	public function set_groups(array $all)
	{
		foreach( $all as $id ) {
			if( !is_numeric($id) || (int)$id < 1 ) throw new CmsInvalidDataException('Invalid data in the nominated groups.  Expect array of integers, each > 0');
		}
		$this->_groups = $all;
		$this->_dirty = true;
	}

   /**
	* Add this template to a group
	*
	* @since 2.3
	* @param mixed $a A CmsLayoutCollection object, an integer group id, or a string group name.
	* @see CmsLayoutTemplateCategory
	* @throws CmsLogicException
	*/
	public function add_group($a)
	{
		$id = $this->get_groupid($a);
		$this->get_groups();
		if( !is_array($this->_groups) ) {
			$this->_groups = [$id];
			$this->_dirty = true;
		}
		elseif( !in_array($id, $this->_groups) ) {
			$this->_groups[] = (int)$id;
			$this->_dirty = true;
		}
	}

   /**
	* Remove this template from a group
	*
	* @since 2.3
	* @param mixed $a A CmsLayoutCollection object, an integer group id, or a string group name.
	* @see CmsLayoutTemplateCategory
	* @throws CmsLogicException
	*/
	public function remove_group($a)
	{
		$this->get_groups();
		if( empty($this->_groups) ) return;

		$id = $this->get_groupid($a);
		if( ($i = array_search($id, $this->_groups)) !== false ) {
			unset($this->_groups[$i]);
			$this->_dirty = true;
		}
	}

   /* *
	* Get a list of the designs that this template is associated with
	*
	* @return array of integers, or maybe empty
	*/
/*	public function get_designs() DISABLED
	{
		return $this->_designs ?? [];
	}
*/
   /* *
	* Set the list of designs that this template is associated with
	*
	* @param array $all integers[], may be empty
	* @throws CmsInvalidDataException
	*/
/*	public function set_designs($all)
	{
		if( !is_array($all) ) throw new CmsInvalidDataException('Invalid designs list. Expect array of integers');
		foreach( $all as $id ) {
			if( !is_numeric($id) || (int)$id < 1 ) throw new CmsInvalidDataException('Invalid data in design list. Expect array of integers, each > 0');
		}

		$this->_designs = $all;
		$this->_dirty = true;
	}
*/
   /* *
	* Get a numeric id corresponding to $a
	* @param mixed $a A CmsLayoutCollection object, an integer design id, or a string design name.
	* @return int
	* @throws CmsLogicException
	*/
/*	protected function get_designid($a)
	{
		if( is_numeric($a) && (int)$a > 0 ) {
			return (int)$a;
		}
		elseif( is_string($a) && $a !== '' ) {
			$ob = DesignManager\Design::load($a); DISABLED
			if( $ob ) {
				return $ob->get_id();
			}
		}
		elseif( $a instanceof DesignManager\Design ) {
			return $a->get_id();
		}
		throw new CmsLogicException('Invalid data passed to '.__METHOD__);
	}
*/
   /**
	* Associate another design with this template
	* @deprecated since 2.3 does nothing
	*
	* @param mixed $a A DesignManager\Design object, an integer design id, or a string design name.
	* @see CmsLayoutCollection
	* @throws CmsLogicException
	*/
	public function add_design($a)
	{
/*
		$id = $this->get_designid($a);
		$this->get_designs(); DISABLED
		if( !is_array($this->_designs) ) {
			$this->_designs = [$id];
			$this->_dirty = true;
		}
		elseif( !in_array($id, $this->_designs) ) {
			$this->_designs[] = (int)$id;
			$this->_dirty = true;
		}
*/
	}

   /**
	* Remove a design from the ones associated with this template
	* @deprecated since 2.3 does nothing
	*
	* @param mixed $a A CmsLayoutCollection object, an integer design id, or a string design name.
	* @see CmsLayoutCollection
	* @throws CmsLogicException
	*/
	public function remove_design($a)
	{
/*
		$this->get_designs(); DISABLED
		if( empty($this->_designs) ) return;

		$id = $this->get_designid($a);
		if( ($i = array_search($id, $this->_designs)) !== false ) {
			unset($this->_designs[$i]);
			$this->_dirty = true;
		}
*/
	}

	/**
	 * @deprecated since 2.3 use get_owner()
	 * @return int
	 */
	public function get_owner_id()
	{
		return $this->owner_id;
	}

   /**
	* Get the owner id of this template (default -1)
	*
	* @return int
	*/
	public function get_owner()
	{
		return $this->owner_id;
	}

   /**
	* Set the owner id of this template
	*
	* @param mixed $a An integer admin user id, a string admin username,
	*  or an instance of a User object
	* @see User
	* @throws CmsInvalidDataException
	*/
	public function set_owner($a)
	{
		$id = 0;
		if( is_numeric($a) && $a > 0 ) {
			$id = (int)$a;
		}
		elseif( is_string($a) && $a !== '' ) {
			// load the user by name.
			$ob = (new UserOperations())->LoadUserByUsername($a);
			if( $ob instanceof User ) $id = $a->id;
		}
		elseif( $a instanceof User ) {
			$id = $a->id;
		}

		if( $id < 1 ) throw new CmsInvalidDataException('Owner id must be valid in '.__METHOD__);
		$this->owner_id = $id;
		$this->_dirty = true;
	}

   /**
	* Get the timestamp for when this template was first saved.
	*
	* @return int UNIX UTC timestamp. Default 1 (i.e. not falsy)
	*/
	public function get_created()
	{
		$str = $this->create_date ?? '';
		return ($str) ? cms_to_stamp($str) : 1;
	}

   /**
	* Get the timestamp for when this template was last saved.
	*
	* @return int UNIX UTC timestamp. Default 1
	*/
	public function get_modified()
	{
		$str = $this->modified_date ?? '';
		return ($str) ? cms_to_stamp($str) : $this->get_created();
	}

   /**
	* Get a list of userid's (other than the owner) that are authorized to edit this template
	*
	* @return array of integer user id's, maybe empty
	*/
	public function get_additional_editors()
	{
		return $this->_editors ?? [];
	}

   /**
	* @ignore
	* @param mixed $a
	* @return int
	* @throws CmsLogicException
	*/
	private static function _resolve_user($a) : int
	{
		if( is_numeric($a) && $a > 0 ) return $a;
		if( is_string($a) && strlen($a) ) {
			$ob = (new UserOperations())->LoadUserByUsername($a);
			if( $ob instanceof User ) return $a->id;
		}
		if( $a instanceof User ) return $a->id;
		throw new CmsLogicException('Could not resolve '.$a.' to a user id');
	}

   /**
	* Set the admin-user account(s) (other than the owner) that are authorized to edit this template object
	*
	* @throws CmsInvalidDataException
	* @param mixed $a Accepts an array of strings (usernames) or an array of integers (user ids, and negative group ids)
	*/
	public function set_additional_editors($a)
	{
		if( !is_array($a) ) {
			if( is_string($a) || (is_numeric($a) && $a > 0) ) {
				// maybe a single value...
				$res = self::_resolve_user($a);
				$this->_editors = [$res];
				$this->_dirty = true;
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
			$this->_editors = $tmp;
			$this->_dirty = true;
		}
	}

   /**
	* Test whether the specified user is authorized to edit this template object
	*
	* @param mixed $a Either a username (string) or an integer user id.
	* @return bool
	*/
	public function can_edit($a)
	{
		$res = self::_resolve_user($a);
		if( $res == $this->owner_id ) return true;
		return !empty($this->_editors) && in_array($res,$this->_editors);
	}

   /**
	* Test whether this template is listable in public template lists. Default true.
	*
	* @since 2.1
	* @return bool
	*/
	public function get_listable()
	{
		return $this->listable;
	}

   /**
	* Test whether this template is listable in public template lists
	* An alias for get_listable()
	*
	* @since 2.1
	* @return bool
	*/
	public function is_listable()
	{
		return $this->listable;
	}

   /**
	* Set the value of the flag which indicates whether this template is listable in public template lists
	*
	* @since 2.1
	* @param mixed $flag recognized by cms_to_bool(). Default true.
	*/
	public function set_listable($flag = true)
	{
		$this->listable = $flag;
		$this->_dirty = true;
	}

   /**
	* Process this template through smarty
	*
	* @return string (unless smarty-processing failed?)
	*/
	public function process()
	{
		$smarty = CmsApp::get_instance()->GetSmarty();
		return $smarty->fetch('cms_template:id='.$this->id);
	}

   /**
	* @ignore
	* @deprecated since 2.3 unused here, now returns null always
	*/
	protected function _get_anyowner()
	{
		return null;
	}

   /**
	* @ignore
	*/
	private static function get_locks() : array
	{
		if( !self::$_lock_cache_loaded ) {
			self::$_lock_cache = [];
			$tmp = LockOperations::get_locks('template');
			if( $tmp ) {
				foreach( $tmp as $one ) {
					self::$_lock_cache[$one['oid']] = $one;
				}
			}
			self::$_lock_cache_loaded = true;
		}
		return self::$_lock_cache;
	}

   /**
	* Get any applicable lock for this template object
	*
	* @return mixed Lock | null
	* @see Lock
	*/
	public function get_lock()
	{
		$locks = self::get_locks();
		return $locks[$this->id] ?? null;
	}

   /**
	* Test whether this template object currently has a lock
	*
	* @return bool
	*/
	public function locked()
	{
		$lock = $this->get_lock();
		return is_object($lock);
	}

   /**
	* Test whether any lock associated with this object has expired
	*
	* @return bool
	*/
	public function lock_expired()
	{
		$lock = $this->get_lock();
		if( is_object($lock) ) return $lock->expired();
		return false;
	}

   /**
	* Get the template-type object for this template.
	*
	* @since 2.2
	* @return mixed CmsLayoutTemplateType or null
	*/
	public function get_type()
	{
		$id = $this->type_id;
		return ( $id > 0 ) ? CmsLayoutTemplateType::load($id) : null;
	}

   /**
	* Get a sample usage string (if any) for this template
	*
	* @since 2.2
	* @return string
	*/
	public function get_usage_string()
	{
		$type = $this->get_type();
		return ( $type ) ? $type->get_usage_string($this->name) : '';
	}

   /**
	* Get the filepath of the file which (if relevant) contains this template's content
	*
	* @since 2.2
	* @return string
	*/
	public function get_content_filename()
	{
		if( $this->contentfile ) {
			$config = cms_config::get_instance();
			return cms_join_path($config['assets_path'],'templates',$this->content);
		}
		return '';
	}

   /**
	* Get whether this template's content resides in a file (as distinct from the database)
	*
	* @since 2.3
	* @return bool
	*/
	public function get_content_file()
	{
		return $this->contentfile;
	}

   /**
	* Get whether this template's content resides in a file
	*
	* @since 2.2
	* @deprecated since 2.3 this is an alias for get_content_file()
	* @return bool
	*/
	public function has_content_file()
	{
		return $this->contentfile;
	}

   /**
	* Set the value of the flag indicating the content of this template resides in a filesystem file
	*
	* @since 2.3
	* @param mixed $flag recognized by cms_to_bool(). Default true.
	*/
	public function set_content_file($flag = true)
	{
		$state = cms_to_bool($flag);
		if( $state ) {
			$this->content = munge_string_to_url($this->name).'.'.$this->id.'.tpl';
		}
		elseif( $this->contentfile ) {
			$this->content = '';
		}
		$this->contentfile = $state;
		$this->_dirty = true;
	}

//======= DEPRECATED METHODS EXPORTED TO TemplateOperations CLASS =======

   /**
	* @var TemplateOperations object populated on demand
	* @ignore
	*/
	private static $_operations = null;

	private static function get_operations()
	{
		if( !self::$_operations ) self::$_operations = new TemplateOperations();
		return self::$_operations;
	}

   /**
	* Save this template to the database
	* @deprecated since 2.3 use corresponding TemplateOperations method
	*/
	public function save()
	{
		if( $this->_dirty ) {
			self::get_operations()::save_template($this);
			$this->_dirty = false;
		}
	}

   /**
	* Delete this template from the database
	* @deprecated since 2.3 use corresponding TemplateOperations method
	*/
	public function delete()
	{
		self::get_operations()::delete_template($this);
	}

   /**
	* Load a bulk list of templates
	* @deprecated since 2.3 use corresponding TemplateOperations method
	*
	* @param int[] $list Array of integer template id's
	* @param bool $deep Optionally load attached data. Default true.
	* @return array of CmsLayoutTemplate objects
	*/
	public static function load_bulk($list,$deep = true)
	{
		return self::get_operations()::load_bulk_templates($list,$deep);
	}

   /**
	* Load a specific template, replacing the properties of this one
	*
	* @param mixed $a Either an integer template id, or a template name (string)
	* @return mixed CmsLayoutTemplate | null
	* @throws CmsDataNotFoundException
	*/
	public static function load($a)
	{
		self::get_operations()::replicate_template($this,$a);
		$this->_dirty = false;
		return $this;
	}

   /**
	* Get a list of the templates owned by a specific user
	* @deprecated since 2.3 use corresponding TemplateOperations method
	*
	* @param mixed $a An integer user id, or a string user name
	* @return array Array of integer template ids
	* @throws CmsInvalidDataException
	*/
	public static function get_owned_templates($a)
	{
		return self::get_operations()::get_owned_templates($a); //static downsteam (ONLY STATIC NOW?)
	}

   /**
	* Perform an advanced query on templates
	* @deprecated since 2.3 use corresponding TemplateOperations method
	*
	* @see CmsLayoutTemplateQuery
	* @param array $params
	*/
	public static function template_query($params)
	{
		self::get_operations()::template_query($params);
	}

   /**
	* Get a list of the templates that a specific user can edit
	* @deprecated since 2.3 use corresponding TemplateOperations method
	*
	* @param mixed $a An integer user id or a string user name or null
	* @return type
	* @throws CmsInvalidDataException
	*/
	public static function get_editable_templates($a)
	{
		return self::get_operations()::get_editable_templates($a);
	}

   /**
	* Test if the user specified can edit the specified template
	* This is a convenience method that loads the template, and tests
	* whether the specified user has authority to  edit it.
	* @deprecated since 2.3 use corresponding TemplateOperations method
	*
	* @param mixed $tpl An integer template id, or a string template name
	* @param mixed $userid An integer user id, or a string user name, or null.
	*   If no userid is specified the currently logged in userid is used
	* @return bool
	*/
	public static function user_can_edit($tpl,$userid = null)
	{
		return self::get_operations()::user_can_edit_template($tpl,$userid);
	}

   /**
	* Create a new template of the specific type
	* @deprecated since 2.3 use corresponding TemplateOperations method
	*
	* @param mixed $t A CmsLayoutTemplateType object, an integer template type id,
	*  or a string template type identifier like originator::name
	* @return CmsLayoutTemplate
	* @throws CmsInvalidDataException
	*/
	public static function create_by_type($t)
	{
		return self::get_operations()::get_template_by_type($t);
	}

   /**
	* Load the default template of a specified type
	* @deprecated since 2.3 use corresponding TemplateOperations method
	*
	* @param mixed $t A CmsLayoutTemplateType object, An integer template type id, or a string template type identifier
	* @return CmsLayoutTemplate
	* @throws CmsInvalidDataException
	* @throws CmsDataNotFoundException
	*/
	public static function load_dflt_by_type($t)
	{
		return self::get_operations()::get_default_template_by_type($t);
	}

   /**
	* Load all templates of a specific type
	* @deprecated since 2.3 use corresponding TemplateOperations method
	*
	* @param CmsLayoutTemplateType $type
	* @return mixed array CmsLayoutTemplate objects or null
	* @throws CmsDataNotFoundException
	*/
	public static function load_all_by_type(CmsLayoutTemplateType $type)
	{
		return self::get_operations()::get_all_templates_by_type($type);
	}

   /**
	* Process a named template through smarty
	* @deprecated since 2.3 use corresponding TemplateOperations method
	*
	* @param string $name
	* @return string
	*/
	public static function process_by_name($name)
	{
		return self::get_operations()::process_named_template($name);
	}

   /**
	* Process the default template of a specified type
	* @deprecated since 2.3 use corresponding TemplateOperations method
	*
	* @param mixed $t A CmsLayoutTemplateType object, an integer template type id, or a string template type identifier
	* @return string
	*/
	public static function process_dflt($t)
	{
		return self::get_operations()::process_default_template($t);
	}

   /**
	* Get the id's of all loaded templates
	* @deprecated since 2.3 no local caching is done
	*
	* @return null
	*/
	public static function get_loaded_templates()
	{
		return null;
	}

   /**
	* Generate a unique name for a template
	* @deprecated since 2.3 use corresponding TemplateOperations method
	*
	* @param string $prototype A prototype template name
	* @param string $prefix An optional name prefix.
	* @return string
	* @throws CmsInvalidDataException
	* @throws CmsLogicException
	*/
	public static function generate_unique_name($prototype,$prefix = null)
	{
		return self::get_operations()::get_unique_template_name($prototype,$prefix);
	}
} // class
