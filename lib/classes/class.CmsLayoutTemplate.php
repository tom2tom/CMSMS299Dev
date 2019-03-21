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
use CMSMS\TemplateOperations;
use CMSMS\LockOperations;
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
	* @deprecated since 2.3 this is re-defined, and used, in TemplateOperations
	* @ignore
	*/
	const TABLENAME = 'layout_templates';

   /**
	* @deprecated since 2.3 this is re-defined, and primarily used, in TemplateOperations
	* @ignore
	*/
	const ADDUSERSTABLE = 'layout_tpl_addusers';

   /**
	* @var TemplateOperations object populated on demand
	* @ignore
	*/
	private $_operations = null;

   /**
	* @var bool whether any property ($_data etc) has been changed since last save
	* @ignore
	*/
	private $_dirty = false;

   /**
	* @var assoc array of template properties, corresponding to a row of the templates-table
	* @ignore
	*/
	private $_data;

   /**
	* @var array id's of authorized editors
	* @ignore
	*/
	private $_editors;

   /**
	* @var array id's of associated designs
	* @ignore
	*/
	private $_designs;

   /**
	* @var array id's of categories that this template is in
	* @ignore
	*/
	private $_categories;

   /**
	* @var type
	* @ignore
	*/
//	private static $_obj_cache;

   /**
	* @var type
	* @ignore
	*/
//	private static $_name_cache;

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
		switch( $key )
		{
			case 'id':
		  	case 'type_id':
			case 'created':
			case 'modified':
				return $data[$key] ?? 0;
			case 'owner_id':
				return $data[$key] ?? -1;
		  	case 'type_dflt':
		  	case 'contentfile':
				return $data[$key] ?? false;
			case 'listable':
				return $data[$key] ?? true;
			case 'name':
			case 'originator':
			case 'content':
			case 'description':
				return $data[$key] ?? '';
			default:
				throw new CmsLogicException("Attempt to retrieve invalid template property: $key");
		}
	}

	/**
 	* @ignore
 	*/
 	public function __set($key,$value)
 	{
		switch( $key )
		{
			case 'id':
		  	case 'type_id':
			case 'owner_id':
				$data[$key] = (int)$value;
				break;
			case 'name':
				$str = trim($value);
				if( !$str || !AdminUtils::is_valid_itemname($str) ) {
					throw new CmsInvalidDataException('Invalid template name: '.$str);
				}
				$data[$key] = $str;
				break;
			case 'content':
				$str = trim($value);
				$data[$key] = ( $str !== '') ? $str : '{* Empty Smarty Template *}';
				break;
			case 'originator':
				$str = trim($value);
				$data[$key] = ( $str !== '') ? $str : '__CORE__'; //aka CmsLayoutTemplateType::CORE
				break;
			case 'description':
				$str = trim($value);
				$data[$key] = ( $str !== '') ? $str : null;
				break;
			case 'type_dflt':
			case 'listable':
			case 'contentfile':
				$data[$key] = cms_to_bool($value);
				break;
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
		foreach( array_keys($this->data) as $key ) {
			$res[$key] = $this->__get($key);
		}

		$res['editors'] = $this->_editors ?? [];
		$res['designs'] = $this->_designs ?? [];
		$res['categories'] = $this->_categories ?? [];

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
		$this->_designs = $params['designs'] ?? [];
		unset($params['designs']);
		$this->_categories = $params['categories'] ?? [];
		unset($params['categories']);

		foreach( $params as $key=>$value ) {
			$this->__set($key,$value);
		}
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
	}

   /**
	* Get the content of this template (default '')
	*
	* @return string
	*/
	public function get_content()
	{
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
		$this->content = $str;
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
	* Only one template can be the default for a template type
	*
	* @param mixed $flag recognized by cms_to_bool(). Default true.
	* @see CmsLayoutTemplateType
	*/
	public function set_type_dflt($flag = true)
	{
		$this->type_dflt = $flag;
	}

   /**
	* Get 'the' (actually, the first-recorded) category id for this template (default 0)
	* A template is not required to be in any category
	*
	* @deprecated since 2.3 templates may belong to multiple categories
	* @return int, 0 if no category exists
	*/
	public function get_category_id()
	{
		if( !empty($this->_categories) ) {
			return reset($this->_categories); // just grab the 1st
		}
		return 0;
	}

   /**
	* Get 'the' category-object for this template (if any)
	* A template is not required to be in any category
	*
	* @deprecated since 2.3 templates may be in multiple categories
	* @return mixed CmsLayoutTemplateCategory object | null
	* @see CmsLayoutTemplateCategory
	*/
	public function &get_category()
	{
		$id = $this->get_category_id();
		if( $id > 0 ) return CmsLayoutTemplateCategory::load($id);
	}

   /**
	* Get the numeric id corresponding to $a
	* @since 2.3
	* @param mixed $a A CmsLayoutTemplateCategory object, an integer category id, or a string category name.
	* @return int
	* @throws CmsLogicException if nothing matches
	*/
	protected function get_categoryid($a)
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
	* Get a list of the category id's that this template is associated with
	*
	* @since 2.3
	* @return array of integers, or maybe empty
	*/
	public function get_categories() : array
	{
		return $this->_categories ?? [];
	}

   /**
	* Set 'the' category of this template
	*
	* @deprecated since 2.3 templates may be in multiple categories
	* @throws CmsLogicException
	* @param mixed $a Either a CmsLayoutTemplateCategory object,
	*  a category name (string) or category id (int)
	* @see CmsLayoutTemplateCategory
	*/
	public function set_category($a)
	{
		if( !$a ) return;
		$id = $this->get_categoryid($a);
		if( empty($this->_categories) ) {
			$this->_categories = [(int)$id];
			$this->_dirty = true;
		}
		elseif( !in_array($id, $this->_categories) ) {
			$this->_categories[] = (int)$id;
			$this->_dirty = true;
		}
	}

   /**
	* Set the list of categories that this template belongs to
	*
	* @since 2.3
	* @param array $all integers[], may be empty
	* @throws CmsInvalidDataException
	*/
	public function set_categories(array $all)
	{
		foreach( $all as $id ) {
			if( !is_numeric($id) || (int)$id < 1 ) throw new CmsInvalidDataException('Invalid data in categories list.  Expect array of integers, each > 0');
		}
		$this->_categories = $all;
		$this->_dirty = true;
	}

   /**
	* Add this template to a category
	*
	* @since 2.3
	* @param mixed $a A CmsLayoutCollection object, an integer design id, or a string design name.
	* @see CmsLayoutTemplateCategory
	* @throws CmsLogicException
	*/
	public function add_category($a)
	{
		$id = $this->get_categoryid($a);
		$this->get_categories();
		if( !is_array($this->_categories) ) {
			$this->_categories = [$id];
			$this->_dirty = true;
		}
		elseif( !in_array($id, $this->_categories) ) {
			$this->_categories[] = (int)$id;
			$this->_dirty = true;
		}
	}

   /**
	* Remove this template from a category
	*
	* @since 2.3
	* @param mixed $a A CmsLayoutCollection object, an integer design id, or a string design name.
	* @see CmsLayoutTemplateCategory
	* @throws CmsLogicException
	*/
	public function remove_category($a)
	{
		$this->get_categories();
		if( empty($this->_categories) ) return;

		$id = $this->get_categoryid($a);
		if( ($i = array_search($id, $this->_categories)) !== false ) {
			unset($this->_categories[$i]);
			$this->_dirty = true;
		}
	}

   /**
	* Get a list of the designs that this template is associated with
	*
	* @return array of integers, or maybe empty
	*/
	public function get_designs()
	{
		return $this->_designs ?? [];
	}

   /**
	* Set the list of designs that this template is associated with
	*
	* @param array $all integers[], may be empty
	* @throws CmsInvalidDataException
	*/
	public function set_designs($all)
	{
		if( !is_array($all) ) throw new CmsInvalidDataException('Invalid designs list. Expect array of integers');
		foreach( $all as $id ) {
			if( !is_numeric($id) || (int)$id < 1 ) throw new CmsInvalidDataException('Invalid data in design list. Expect array of integers, each > 0');
		}

		$this->_designs = $all;
		$this->_dirty = true;
	}

   /**
	* Get a numeric id corresponding to $a
	* @param mixed $a A CmsLayoutCollection object, an integer design id, or a string design name.
	* @return int
	* @throws CmsLogicException
	*/
	protected function get_designid($a)
	{
		if( is_numeric($a) && (int)$a > 0 ) {
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
		throw new CmsLogicException('Invalid data passed to '.__METHOD__);
	}

   /**
	* Associate another design with this template
	*
	* @param mixed $a A CmsLayoutCollection object, an integer design id, or a string design name.
	* @see CmsLayoutCollection
	* @throws CmsLogicException
	*/
	public function add_design($a)
	{
		$id = $this->get_designid($a);
		$this->get_designs();
		if( !is_array($this->_designs) ) {
			$this->_designs = [$id];
			$this->_dirty = true;
		}
		elseif( !in_array($id, $this->_designs) ) {
			$this->_designs[] = (int)$id;
			$this->_dirty = true;
		}
	}

   /**
	* Remove a design from the ones associated with this template
	*
	* @param mixed $a A CmsLayoutCollection object, an integer design id, or a string design name.
	* @see CmsLayoutCollection
	* @throws CmsLogicException
	*/
	public function remove_design($a)
	{
		$this->get_designs();
		if( empty($this->_designs) ) return;

		$id = $this->get_designid($a);
		if( ($i = array_search($id, $this->_designs)) !== false ) {
			unset($this->_designs[$i]);
			$this->_dirty = true;
		}
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
			$ops = UserOperations::get_instance();
			$ob = $ops->LoadUserByUsername($a);
			if( $a instanceof User ) $id = $a->id;
		}
		elseif( $a instanceof User ) {
			$id = $a->id;
		}

		if( $id < 1 ) throw new CmsInvalidDataException('Owner id must be valid in '.__METHOD__);
		$this->owner_id = $id;
	}

   /**
	* Get the timestamp for when this template was initially stored in the database. Default 0
	*
	* @return int
	*/
	public function get_created()
	{
		return $this->created;
	}

   /**
	* Get the timestamp for when this template was last saved to the database. Default 0
	*
	* @return int
	*/
	public function get_modified()
	{
		return $this->modified;
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
			$ops = UserOperations::get_instance();
			$ob = $ops->LoadUserByUsername($a);
			if( $a instanceof User ) return $a->id;
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
			$tmp = LockOperations::get_locks('template');
			if( $tmp ) {
				self::$_lock_cache = [];
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

//============= EXPORTED OPERATIONS =============

	private function get_operations()
	{
		if( !$this->_operations ) $this->_operations = new TemplateOperations();
		return $this->_operations;
	}

   /**
	* Save this template to the database
	* @deprecated since 2.3 use corresponding TemplateOperations method
	*/
	public function save()
	{
		if( $this->_dirty ) {
			$this->get_operations()->save_template($this);
			$this->_dirty = false;
		}
	}

   /**
	* Delete this template from the database
	* @deprecated since 2.3 use corresponding TemplateOperations method
	*/
	public function delete()
	{
		$this->get_operations()->delete_template($this);
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
		return $this->get_operations()::load_bulk_templates($list,$deep);
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
		if( $this->get_operations()->populate_template($this,$a) ) {
			$this->_dirty = false;
			return $this;
		}
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
		return $this->get_operations()::get_owned_templates($a); //static downsteam (ONLY STATIC NOW?)
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
		$this->get_operations()::template_query($params);
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
		return $this->get_operations()::get_editable_templates($a);
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
		return $this->get_operations()::user_can_edit($tpl,$userid);
	}

   /**
	* Create a new template of the specific type
	* @deprecated since 2.3 use corresponding TemplateOperations method
	*
	* @param mixed $t A CmsLayoutTemplateType object, an integer template type id, or a string template type identifier
	* @return CmsLayoutTemplate
	* @throws CmsInvalidDataException
	*/
	public static function &create_by_type($t)
	{
		return $this->get_operations()::create_by_type($t);
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
		return $this->get_operations()::load_default_template_by_type($t);
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
		return $this->get_operations()::load_all_templates_by_type($type);
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
		return $this->get_operations()::process_by_name($name);
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
		return $this->get_operations()::process_dflt($t);
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
		return $this->get_operations()::generate_unique_template_name($prototype,$prefix);
	}

	//============= END EXPORTED OPERATIONS =============

   /**
	* Get the template-type object for this template.
	*
	* @since 2.2
	* @return mixed CmsLayoutTemplateType or null
	*/
	public function &get_type()
	{
		$id = $this->type_id;
		$obj = ( $id > 0 ) ? CmsLayoutTemplateType::load($id) : null;
		return $obj;
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
		$fn = munge_string_to_url($this->name).'.'.$this->id.'.tpl';
		$config = cms_config::get_instance();
		return cms_join_path($config['assets_path'],'templates',$fn);
	}

   /**
	* Get whether this template's content is in a file (as distinct from the database)
	*
    * @since 2.2
	* @return bool
	*/
	public function has_content_file()
	{
		if( $this->contentfile ) {
			return true;
		}
		$fn = $this->get_content_filename();
		if( is_file($fn) && is_readable($fn) ) {
			$this->contentfile = true;
			return true;
		}
		return false;
	}

   /**
	* Set the value of the flag indicating this template stores its content in a filesystem file
	*
	* @since 2.3
	* @param mixed $flag recognized by cms_to_bool(). Default true.
	*/
	public function set_content_file($flag = true)
	{
		$this->contentfile = $flag;
	}
} // class

