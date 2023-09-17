<?php
/*
Class for administering a layout template.
Copyright (C) 2014-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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
use CMSMS\Lock;
use CMSMS\LockOperations;
use CMSMS\Lone;
use CMSMS\Template;
use CMSMS\TemplateOperations;
use CMSMS\TemplateQuery;
use CMSMS\TemplatesGroup;
use CMSMS\TemplateType;
use CMSMS\User;
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
 * A class of methods for Template administration via the admin-console
 * UI, or by DesignManager-module etc. The class is not used for
 * template processing during page-display.
 *
 * @package CMS
 * @license GPL
 * @since 3.0
 * @since 2.0 as global-namespace CmsLayoutTemplate
 */
class Template
{
	/**
	 * @deprecated since 3.0 instead use the equivalent TemplateOperations::TABLENAME
	 * @ignore
	 */
	const TABLENAME = 'layout_templates';

	/**
	 * @deprecated since 3.0 instead use the equivalent TemplateOperations::ADDUSERSTABLE
	 * @ignore
	 */
	const ADDUSERSTABLE = 'layout_tpl_addusers';

	/**
	 * Originator for core templates
	 * @since 3.0
	 * @see also TemplateType::CORE
	 */
	const CORE = '__CORE__';

	/**
	 * @var array template-file on-save operations, populated on demand
	 * Each member like [optype,param,...] as below:
	 *  ['store',$tobasename,$content] $content might be empty if file is already saved
	 *  ['delete',$thebasename]
	 *  ['rename',$frombasename, $tobasename]
	 * @ignore
	 */
	public $fileoperations = [];

	/**
	 * @var assoc array of template properties, corresponding to a row of
	 *  TemplateOperations::TABLENAME. Any that are for internal-use only
	 *  are ignored in this class.
	 * @ignore
	 */
	private $props = [];

	/**
	 * @var array id's of authorized editors
	 * @ignore
	 */
	private $editors;

	/**
	 * @var array id's of groups that this template belongs to
	 * @ignore
	 */
	private $groups;  //unset triggers check on 1st use

	/**
	 * @var bool whether any member of $props, or either $editors or
	 * $groups, has been changed since last save
	 * @ignore
	 */
	private $dirty = false;

	/**
	 * @var string template-file content, populated on demand
	 * @ignore
	 */
	public $filecontent;

	// static properties here >> Lone properties ?
	/**
	 * @var array
	 * @ignore
	 */
	private static $lock_cache = [];

	/**
	 * @var bool
	 * @ignore
	 */
	private static $lock_cache_loaded = false;

	/**
	 * @var TemplateOperations object populated on demand
	 * @ignore
	 */
	private static $operations = null;

	/**
	 * @ignore
	 */
	public function __clone(): void
	{
		unset($this->props['id']);
		$this->props['type_dflt'] = false;
		$this->dirty = true;
	}

	/**
	* @ignore
	*/
	public function __set(string $key,$value): void
	{
		switch( $key ) {
			case 'id':
				if( !empty($this->props[$key]) ) {
					throw new LogicException("Template property 'id' cannot be changed");
				}
				if( $value < 1 ) {
					throw new UnexpectedValueException("Template property 'id' must be > 0");
				}
			// no break here
			case 'owner_id':
			case 'type_id':
				$this->props[$key] = (int)$value;
				break;
			case 'category_id': // derecated since 3.0
				if( !isset($this->groups) ) {
					$this->groups = [];
				}
				$this->groups[] = (int)$value;
				break;
			case 'name':
				$this->set_name($value);
				break;
			case 'content':
				$this->set_content($value);
				break;
			case 'originator':
				if( isset($this->props[$key]) ) {
					throw new LogicException("Template property '$key' cannot be changed");
				}
				$str = trim($value);
				if( $str === '' || strcasecmp($str,'core') == 0 ) $str = '__CORE__';
				$this->props[$key] = $str;
				break;
			case 'description':
			case 'hierarchy':
				$str = trim($value);
				$this->props[$key] = ( $str !== '' ) ? $str : null;
				break;
			case 'create_date':
				if( isset($this->props[$key]) ) {
					throw new LogicException("Template property '$key' cannot be changed");
				}
			// no break here
			case 'modified_date':
				$this->props[$key] = trim($value);
				break;
			case 'contentfile':
				$this->set_content_file($value);
				break;
			case 'listable':
			case 'type_dflt':
				$this->props[$key] = cms_to_bool($value);
				break;
			case 'categories': //deprecated since 3.0
			case 'groups':
				$this->set_groups($value);
				break;
			case 'designs':
				return; //deprecated since 3.0, unused
			case 'editors':
				$this->set_additional_editors($value);
				break;
			default:
				if( isset($this->props[$key]) ) return;
				throw new LogicException("Cannot set invalid template property '$key'");
		}
		$this->dirty = true;
	}

	/**
	* @ignore
	*/
	#[\ReturnTypeWillChange]
	public function __get(string $key)//: mixed
	{
		switch( $key ) {
			case 'id':
			case 'owner_id':
			case 'type_id':
				return $this->props[$key] ?? 0;
			case 'category_id': // deprecated since 3.0
				return (!empty($this->groups)) ? reset($this->groups) : 0;
			case 'name':
			case 'content': // raw, maybe a filename
			case 'description':
			case 'hierarchy':
			case 'create_date':
			case 'modified_date':
				return $this->props[$key] ?? '';
			case 'originator':
				return $this->props[$key] ?? '__CORE__';
			case 'type_dflt':
			case 'contentfile':
				return $this->props[$key] ?? false;
			case 'listable':
				return $this->props[$key] ?? true;
			case 'categories': //deprecated since 3.0
			case 'groups':
				return $this->groups ?? [];
			case 'designs':
				return null; //deprecated since 3.0, unused
			case 'editors':
				return $this->editors ?? [];
			default:
				throw new LogicException("Cannot retrieve invalid template property '$key'");
		}
	}

	/**
	 * Get the recordable properties of this template
	 * @since 3.0
	 * @internal
	 * @ignore
	 *
	 * @return array
	 */
	public function get_properties(): array
	{
		$props = $this->props;
		$props['editors'] = $this->editors ?? [];
		$props['groups'] = $this->groups ?? [];
		return $props;
	}

	/**
	 * Set the private properties of this template
	 * @since 3.0
	 * @internal
	 * @ignore
	 *
	 * @param array $props
	 * @throws UnexpectedValueException
	 */
	public function set_properties(array $props)
	{
		$this->editors = $props['editors'] ?? [];
		unset($props['editors']);
		$this->groups = $props['groups'] ?? [];
		unset($props['groups']);

		// verbatim, no validate / sanitize
		$this->props = $props;

		if( !empty($props['contentfile'] )) {
			if( ($fp = $this->get_content_filename()) ) {
				$this->filecontent = file_get_contents($fp);
			}
			else {
				$this->filecontent = '{* Missing template file *}';
			}
		}
	}

	/**
	 * Get the numeric id of this template
	 *
	 * @return int, 0 if this template has not yet
	 *  been saved to the database
	 */
	public function get_id(): int
	{
		return $this->props['id'] ?? 0;
	}

	/**
	 * Set the id of this template, after it is initially-saved.
	 * @since 3.0
	 *
	 * @param int $id
	 * @throws LogicException if used afterwards
	 */
	public function set_id(int $id)
	{
		if( !empty($this->props['id']) ) {
			throw new LogicException("Template 'id' property cannot be changed");
		}
		if( $id < 1 ) {
			throw new UnexpectedValueException("Template 'id' property must be > 0");
		}
		$this->props['id'] = $id;
		// not dirty - this is set only after 1st save
	}

	/**
	 * Get the originator of this template
	 * @since 3.0
	 *
	 * @return string, '' if the originator has not yet been nominated.
	 */
	public function get_originator(): string
	{
		return $this->props['originator'] ?? '';
	}

	/**
	 * Set the originator of this template
	 * The originator would normally be '__CORE__' or a module name or a
	 * theme name.
	 * $str is not checked/validated here, other than non-empty.
	 * Any case-variant of 'core' is converted to '__CORE__'.
	 * @since 3.0
	 *
	 * @param string $str
	 * @throws DataException if $str is empty
	 */
	public function set_originator(string $str)
	{
		$str = trim($str);
		if( !$str ) throw new DataException('Template originator cannot be nameless');
		if( strcasecmp($str, 'core') == 0 ) { $str = '__CORE__'; }
		$this->props['originator'] = $str;
		$this->dirty = true;
	}

	/**
	 * Get the name of this template (default '')
	 *
	 * @return string Default ''
	 */
	public function get_name()
	{
		return $this->props['name'] ?? '';
	}

	/**
	 * Set the name of this template
	 * Template names must be unique throughout the system.
	 *
	 * @param string $str acceptable name per AdminUtils::is_valid_itemname()
	 * @throws UnexpectedValueException
	 */
	public function set_name($str)
	{
		$str = trim($str);
		if( !$str || !AdminUtils::is_valid_itemname($str) ) { // allows ' /' chars, unacceptable in a filename
			throw new UnexpectedValueException('Invalid template name: '.$str);
		}
		if( isset($this->props['name']) ) {
			if( $this->props['name'] == $str ) return;
		}
		if( !empty($this->props['contentfile']) ) {
			$fn = sanitizeVal($str, CMSSAN_FILE); // TODO advise user if $fn != $str
			$this->props['name'] = $fn;
			// want ['rename',$frombasename,$tobasename]
			$this->fileoperations[] = ['rename', $this->props['content'], $fn.'.tpl'];
			$this->props['content'] = $fn.'.tpl';
		}
		else {
			$this->props['name'] = $str; // TODO might be unsuitable for future export to file
		}
		// TODO duplicate-name check & reject
		$this->dirty = true;
	}

	/**
	 * Get this template's description
	 *
	 * @return string  Default ''
	 */
	public function get_description(): string
	{
		return $this->props['description'] ?? '';
	}

	/**
	 * Set this template's description
	 * No sanitization
	 *
	 * @param string $str
	 */
	public function set_description($str)
	{
		$this->props['description'] = trim($str);
		$this->dirty = true;
	}

	/**
	 * Get the template-type object for this template, if any.
	 * @since 2.2
	 *
	 * @return mixed TemplateType object | null
	 */
	public function get_type()
	{
		$id = $this->props['type_id'] ?? 0;
		return ( $id > 0 ) ? TemplateType::load($id) : null;
	}

	/**
	 * Set the type-identifier of this template
	 *
	 * @param mixed $a Either an instance of TemplateType object,
	 *  an integer type id, or a string template type identifier
	 * @see TemplateType
	 * @throws UnexpectedValueException
	 */
	public function set_type($a)
	{
		if( $a instanceof TemplateType ) {
			$id = $a->get_id();
		}
		elseif( is_numeric($a) && (int)$a > 0 ) {
			$id = (int)$a;
		}
		elseif( is_string($a) && $a !== '' ) {
			$type = TemplateType::load($a);
			$id = $type->get_id();
		}
		else {
			throw new UnexpectedValueException('Invalid identifier provided to '.__METHOD__);
		}

		$this->props['type_id'] = $id;
		$this->dirty = true;
	}

	/**
	 * Get the type id of this template (default 0)
	 *
	 * @return int Default 0
	 */
	public function get_type_id()
	{
		return $this->props['type_id'] ?? 0;
	}

	/**
	 * Test whether this template is the default template for its type, if any
	 *
	 * @return bool
	 * @see TemplateType
	 */
	public function get_type_default(): bool
	{
		return $this->props['type_dflt'] ?? false;
	}

	/**
	 * @see Template::get_type_default()
	 * @deprecated since 3.0 instead use Template::get_type_default()
	 */
	public function get_type_dflt()
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method','Template::get_type_default()'));
		return $this->props['type_dflt'] ?? false;
	}

	/**
	 * Set the value of the flag that indicates whether this template is
	 * the default template for its type, if any
	 * If true, then when this template is saved this property will be
	 * unset for all other templates of the same type.
	 *
	 * @param mixed $flag recognized by cms_to_bool(). Default true.
	 * @see TemplateType
	 */
	public function set_type_default($flag = true)
	{
		$this->props['type_dflt'] = cms_to_bool($flag);
		$this->dirty = true;
	}

	/**
	 * @see Template::set_type_default()
	 * @deprecated since 3.0 instead use Template::set_type_default()
	 */
	public function set_type_dflt($flag = true)
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method','Template::set_type_default()'));
		$this->props['type_dflt'] = cms_to_bool($flag);
		$this->dirty = true;
	}

	/**
	 * Get 'the' (actually, the first-recorded) group id for this template (default 0)
	 * A template is not required to be in any group
	 *
	 * @deprecated since 3.0 templates may belong to multiple groups
	 * @return int, 0 if no group exists
	 */
	public function get_category_id()
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('templates may be in multiple groups',''));
		if( !empty($this->groups) ) {
			return reset($this->groups); // just grab the 1st
		}
		return 0;
	}

	/**
	 * Get 'the' group-(aka category-)object for this template (if any)
	 * A template is not required to be in any group
	 *
	 * @deprecated since 3.0 templates may be in multiple groups
	 * @return mixed TemplatesGroup object | null
	 * @see TemplatesGroup
	 */
	public function get_category()
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('templates may be in multiple groups',''));
		$id = $this->get_category_id();
		if( $id > 0 ) return TemplatesGroup::load($id);
	}

	/**
	 * Get the numeric id corresponding to $a
	 * @since 3.0
	 *
	 * @param mixed $a int group id (> 0) | string group name | TemplatesGroup object
	 * @return int
	 * @throws UnexpectedValueException if nothing matches
	 */
	protected function get_groupid($a)
	{
		if( is_numeric($a) && (int)$a > 0 ) {
			return (int)$a;
		}
		elseif( (is_string($a) && strlen($a)) || (int)$a > 0 ) {
			$ob = TemplatesGroup::load($a);
			if( $ob ) {
				return $ob->get_id();
			}
		}
		elseif( $a instanceof TemplatesGroup ) {
			return $a->get_id();
		}
		throw new UnexpectedValueException('Invalid identifier provided to '.__METHOD__);
	}

	/**
	 * Get the group(s) (id's) that this template belongs to
	 * @since 3.0
	 *
	 * @return array of integers, maybe empty
	 */
	public function get_groups(): array
	{
		return $this->groups ?? [];
	}

	/**
	 * Set 'the' group of this template
	 *
	 * @deprecated since 3.0 templates may be in multiple groups
	 * @param mixed $a a TemplatesGroup object, a group name (string)
	 *  or group id (int)
	 * @see TemplatesGroup
	 * @throws LogicException ?
	 */
	public function set_category($a)
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('templates may be in multiple groups',''));
		if( !$a ) return;
		$id = $this->get_groupid($a);
		if( empty($this->groups) ) {
			$this->groups = [(int)$id];
			$this->dirty = true;
		}
		elseif( !in_array($id, $this->groups) ) {
			$this->groups[] = (int)$id;
			$this->dirty = true;
		}
	}

	/**
	 * Set the group id's that this template belongs to
	 * @since 3.0
	 *
	 * @param mixed $all array | int integer group id(s), maybe empty
	 * @throws InvalidArgumentException
	 */
	public function set_groups($all)
	{
		if( !is_array($all) ) { $all = [$all]; }
		foreach( $all as $id ) {
			if( !is_numeric($id) || (int)$id < 1 ) {
				throw new InvalidArgumentException('Invalid template-groups data. Expect array of integers, each > 0');
			}
		}
		$this->groups = $all;
		$this->dirty = true;
	}

	/**
	 * Add this template to a group
	 * @since 3.0
	 *
	 * @param mixed $a int group id | string group name | TemplatesGroup object
	 * @see TemplatesGroup
	 * @throws LogicException ?
	 */
	public function add_group($a)
	{
		$id = $this->get_groupid($a);
		$this->get_groups();
		if( !in_array($id, $this->groups) ) {
			$this->groups[] = (int)$id;
			$this->dirty = true;
		}
	}

	/**
	 * Remove this template from a group
	 * @since 3.0
	 * @see TemplatesGroup
	 *
	 * @param mixed $a integer group id | string group name | TemplatesGroup object
	 * @throws LogicException ?
	 */
	public function remove_group($a)
	{
		$this->get_groups();
		if( !$this->groups ) return; // TODO warning to user

		$id = $this->get_groupid($a);
		if( ($i = array_search($id, $this->groups)) !== false ) {
			unset($this->groups[$i]);
			$this->dirty = true;
		}
	}

	/**
	 * Associate another design with this template
	 * @deprecated since 3.0 does nothing
	 *
	 * @param mixed $a integer design | string design name | Design object
	 */
	public function add_design($a)
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method does nothing',''));
	}

	/**
	 * Remove a design from the ones associated with this template
	 * @deprecated since 3.0 does nothing
	 *
	 * @param mixed $a integer design id | string design name | Design object
	 */
	public function remove_design($a)
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method does nothing',''));
	}

	/**
	 * @deprecated since 3.0 use get_owner()
	 * @return int
	 */
	public function get_owner_id()
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method','Template::get_owner()'));
		return $this->props['owner_id'] ?? 0;
	}

	/**
	 * Get the owner id of this template
	 *
	 * @return int Default 0
	 */
	public function get_owner(): int
	{
		return $this->props['owner_id'] ?? 0;
	}

	/**
	 * Set the owner id of this template
	 *
	 * @param mixed $a a username (string) or an integer user id, or a
	 *  User object
	 * @see User
	 * @throws UnexpectedValueException
	 */
	public function set_owner($a)
	{
		$id = 0;
		if( is_numeric($a) && $a > 0 ) {
			$id = (int)$a;
		}
		elseif( is_string($a) && $a !== '' ) {
			// load the user by name.
			$ob = Lone::get('UserOperations')->LoadUserByUsername($a);
			if( $ob instanceof User ) $id = $a->id;
		}
		elseif( $a instanceof User ) {
			$id = $a->id;
		}

		if( $id < 1 ) throw new UnexpectedValueException('Owner id must be valid in '.__METHOD__);
		$this->props['owner_id'] = $id;
		$this->dirty = true;
	}

	/**
	 * Get a timestamp representing when this template was first saved.
	 *
	 * @return int UNIX UTC timestamp. Default 1 (i.e. not falsy)
	 */
	public function get_created()
	{
		$str = $this->props['create_date'] ?? '';
		return ($str) ? cms_to_stamp($str) : 1;
	}

	/**
	 * Get a timestamp representing when this template was last saved.
	 *
	 * @return int UNIX UTC timestamp. Default 1
	 */
	public function get_modified()
	{
		$str = $this->props['modified_date'] ?? $this->props['create_date'] ?? '';
		return ($str) ? cms_to_stamp($str) : 1;
	}

	/**
	 * Get the userid's (other than the owner) that are authorized to edit this template
	 *
	 * @return array of integer user id's, maybe empty
	 */
	public function get_additional_editors()
	{
		return $this->editors ?? [];
	}

	/**
	 * @ignore
	 * @param mixed $a
	 * @return int
	 * @throws LogicException
	 */
	private static function _resolve_user($a): int
	{
		if( is_numeric($a) && $a > 0 ) return $a;
		if( is_string($a) && $a !== '' ) {
			$ob = Lone::get('UserOperations')->LoadUserByUsername($a);
			if( $ob instanceof User ) return $a->id;
		}
		if( $a instanceof User ) return $a->id;
		throw new LogicException('Could not resolve '.$a.' to a user id');
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
		if( isset($this->props['owner_id']) && $res == $this->props['owner_id'] ) return true;
		return !empty($this->editors) && in_array($res,$this->editors);
	}

	/**
	 * Set the admin-user account(s) (other than the owner) that are authorized to edit this template object
	 *
	 * @throws UnexpectedValueException
	 * @param mixed $a string[] (usernames) | int[] (user ids, and negative group ids) | single user identifier
	 */
	public function set_additional_editors($a)
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

	/**
	 * Test whether this template is listable in public template lists. Default true.
	 *
	 * @since 2.1
	 * @return bool
	 */
	public function get_listable(): bool
	{
		return $this->props['listable'] ?? true;
	}

	/**
	 * Test whether this template is listable in public template lists
	 * An alias for get_listable()
	 *
	 * @since 2.1
	 * @deprcated since 3.0
	 * @return bool
	 */
	public function is_listable()
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method','Template::get_listable()'));
		return $this->props['listable'] ?? true;
	}

	/**
	 * Set the value of the flag which indicates whether this template is listable in public template lists
	 *
	 * @since 2.1
	 * @param mixed $flag recognized by cms_to_bool(). Default true.
	 */
	public function set_listable($flag = true)
	{
		$this->props['listable'] = cms_to_bool($flag);
		$this->dirty = true;
	}

	/**
	 * Process this template through smarty
	 *
	 * @return string (unless smarty-processing failed?)
	 */
	public function process()
	{
		$smarty = Lone::get('Smarty');
		return $smarty->fetch('cms_template:'.$this->props['id']);
	}

	/**
	 * @ignore
	 * @deprecated since 3.0 unused here, now returns null always
	 */
	protected function _get_anyowner()
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method always returns null',''));
		return null;
	}

	/**
	 * @ignore
	 */
	private static function get_locks(): array
	{
		if( !self::$lock_cache_loaded ) {
			self::$lock_cache = [];
			$tmp = LockOperations::get_locks('template');
			if( $tmp ) {
				foreach( $tmp as $one ) {
					self::$lock_cache[$one['oid']] = $one;
				}
			}
			self::$lock_cache_loaded = true;
		}
		return self::$lock_cache;
	}

	/**
	 * Get a lock (if any exists) for this object
	 * @see Lock
	 *
	 * @return mixed Lock | null
	 */
	public function get_lock()
	{
		if( !isset($this->props['id']) ) return null;
		$locks = self::get_locks();
		return $locks[$this->props['id']] ?? null;
	}

	/**
	 * Test whether this template object is locked
	 *
	 * @return bool
	 */
	public function locked(): bool
	{
		$lock = $this->get_lock();
		return is_object($lock);
	}

	/**
	 * Test whether this template is locked by an expired lock.
	 * If the object is not locked FALSE is returned
	 *
	 * @return bool
	 */
	public function lock_expired(): bool
	{
		$lock = $this->get_lock();
		if( !is_object($lock) ) return false;
		return $lock->expired();
	}

	/**
	 * Get a sample usage string (if any) for this template
	 * @since 2.2
	 *
	 * @return string
	 */
	public function get_usage_string(): string
	{
		if( empty($this->props['name']) ) return '';
		$type = $this->get_type();
		if ( $type ) {
			// deal with possible null from downstream
			$str = $type->get_usage_string($this->props['name']);
			if ($str) { return $str; }
		}
		return '';
	}

	/**
	 * Get the content of this template
	 *
	 * @return string
	 */
	public function get_content(): string
	{
		if( !empty($this->props['contentfile']) ) {
			//NOTE CMSMS\internal\layout_template_resource replicates this, and must be manually conformed to any change
			if( !isset($this->filecontent) ) {
				if( ($fp = $this->get_content_filename()) ) {
					$this->filecontent = file_get_contents($fp);
				}
				else {
					$this->filecontent = '{* Missing template file *}';
				}
			}
			return $this->filecontent;
		}
		return $this->props['content'] ?? '';
	}

	/**
	 * Set the content of this template
	 * No sanitization
	 *
	 * @param string $str not empty, might be a templatefile name like X.tpl
	 */
	public function set_content($str)
	{
		$str = trim($str);
		if( !$str ) throw new LogicException('Template cannot be empty');
//		if( !$str ) $str = '{* empty Smarty template *}';
		if( !empty($this->props['contentfile']) ) {
			$this->filecontent = $str;
			// park new content for transfer to file when this object is saved
//			$fn = basename($this->get_content_filename()); // might be empty
			$fn = $str;
			$str = ''; // TODO only if already saved @ CMS_ASSETS_PATH / layouts / $fn
			// want ['store',$tobasename,$content];
			$this->fileoperations[] = ['store', $fn, $str];
		}
		else {
			$this->props['content'] = $str;
		}
		$this->dirty = true;
	}

	/**
	 * Get the filepath of the file which is supposed to contain this
	 *  template's content
	 * @since 2.2
	 *
	 * @return string
	 */
	public function get_content_filename()
	{
		$fn = $this->props['content'] ?? '';
		if( $fn && strpos($fn, ' ') === false && endswith($fn, '.tpl') ) {
			//NOTE CMSMS\internal\layout_template_resource replicates this,
			// and must be manually conformed to any change
			return cms_join_path(CMS_ASSETS_PATH, 'layouts', $fn);
		}
		return '';
	}

	/**
	 * Get whether this template's content resides in a file (as distinct from the database)
	 * @since 3.0
	 *
	 * @return bool
	 */
	public function get_content_file()
	{
		return $this->props['contentfile'] ?? false;
	}

	/**
	 * Get whether this template's content resides in a file
	 * @since 2.2
	 * @deprecated since 3.0 this is an alias for get_content_file()
	 *
	 * @return bool
	 */
	public function has_content_file()
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method','Template::get_content_file()'));
		return $this->props['contentfile'] ?? false;
	}

	/**
	 * Set the value of the flag indicating the content of this template
	 *  resides in a filesystem file
     * The template's content-filename must be set before this method is used
     * with $flag = true
     * @since 3.0
	 *
	 * @param mixed $flag recognized by cms_to_bool(). Default true.
	 */
	public function set_content_file($flag = true)
	{
		$state = cms_to_bool($flag);
		$current = !empty($this->props['contentfile']);
		if( $state === $current ) {
			if( !$current ) {
				$this->props['contentfile'] = false; // ensure it's present
			}
			return;
		}
		if( !empty($this->props['content']) ) {
			$fn = $this->get_content_filename();
			if( $state ) {
				if( !$fn ) {
					// not yet set up
					$fn = sanitizeVal($this->props['name'], CMSSAN_FILE);
					// park current content for save-in-file when this object is saved
                    // want ['store',$tobasename,$content];
					$this->fileoperations[] = ['store', $fn.'.tpl', ($this->props['content'] ?? '')];
					$this->props['content'] = $fn.'.tpl';
				}
			}
			elseif( $fn ) {
				$this->props['content'] = file_get_contents($fn);
				// park current filename for deletion when this object is saved
                // want ['delete',$thebasename];
				$this->fileoperations[] = ['delete', basename($fn)];
			}
			else {
				$this->props['content'] = '';
			}
		}
		$this->props['contentfile'] = $state;
		$this->dirty = true;
	}

//======= DEPRECATED METHODS EXPORTED TO TemplateOperations CLASS =======

	/**
	 * @ignore
	 * @since 3.0
	 * @return TemplateOperations
	 */
	private static function get_operations()
	{
		if( !self::$operations ) self::$operations = new TemplateOperations();
		return self::$operations;
	}

	/**
	 * Save this template to the database
	 * @deprecated since 3.0 use corresponding TemplateOperations method
	 */
	public function save()
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method','TemplateOperations::save_template()'));
		if( $this->dirty ) {
			self::get_operations()::save_template($this);
			$this->dirty = false;
		}
	}

	/**
	 * Delete this template from the database
	 * @deprecated since 3.0 use corresponding TemplateOperations method
	 */
	public function delete()
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method','TemplateOperations::delete_template()'));
		self::get_operations()::delete_template($this);
	}

	/**
	 * Load a bulk list of templates
	 * @deprecated since 3.0 use corresponding TemplateOperations method
	 *
	 * @param int[] $list Array of integer template id's
	 * @param bool $deep Optionally load attached data. Default true.
	 * @return array of Template objects
	 */
	public static function load_bulk($list,$deep = true)
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method','TemplateOperations::load_bulk_templates()'));
		return self::get_operations()::load_bulk_templates($list,$deep);
	}

	/**
	 * Load a specific template, replacing the properties of this one
	 *
	 * @param mixed $a Either an integer template id, or a template name (string)
	 * @return mixed Template object | null
	 * @throws DataException
	 */
	public static function load($a)
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method','TemplateOperations::replicate_template()'));
		self::get_operations()::replicate_template($this,$a);
		$this->dirty = false;
		return $this;
	}

	/**
	 * Get the templates owned by a specific user
	 * @deprecated since 3.0 use corresponding TemplateOperations method
	 *
	 * @param mixed $a An integer user id, or a string user name
	 * @return array Array of integer template ids
	 * @throws DataException
	 */
	public static function get_owned_templates($a)
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method','TemplateOperations::get_owned_templates()'));
		return self::get_operations()::get_owned_templates($a); //static downsteam (ONLY STATIC NOW?)
	}

	/**
	 * Perform an advanced query on templates
	 * @deprecated since 3.0 use corresponding TemplateOperations method
	 *
	 * @see TemplateQuery
	 * @param array $params
	 */
	public static function template_query($params)
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method','TemplateOperations::template_query()'));
		self::get_operations()::template_query($params);
	}

	/**
	 * Get the templates that a specific user can edit
	 * @deprecated since 3.0 use corresponding TemplateOperations method
	 *
	 * @param mixed $a An integer user id or a string user name or null
	 * @return type
	 * @throws DataException
	 */
	public static function get_editable_templates($a)
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method','TemplateOperations::get_editable_templates()'));
		return self::get_operations()::get_editable_templates($a);
	}

	/**
	 * Test whether the specified user may edit the specified template
	 * @deprecated since 3.0 use corresponding TemplateOperations method
	 *
	 * @param mixed $tpl An integer template id, or a string template name
	 * @param mixed $userid Optional int user id, or string user name, or null.
	 *   If no userid is specified the currently logged in user is assumed
	 * @return bool
	 */
	public static function user_can_edit($tpl,$userid = null)
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method','TemplateOperations::user_can_edit_template()'));
		return self::get_operations()::user_can_edit_template($tpl,$userid);
	}

	/**
	 * Create a new template of the specific type
	 * @deprecated since 3.0 use corresponding TemplateOperations method
	 *
	 * @param mixed $t A TemplateType object, an integer template type id,
	 *  or a string template type identifier like originator::name
	 * @return Template
	 * @throws DataException
	 */
	public static function create_by_type($t)
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method','TemplateOperations::get_template_by_type()'));
		return self::get_operations()::get_template_by_type($t);
	}

	/**
	 * Load the default template of a specified type
	 * @deprecated since 3.0 use corresponding TemplateOperations method
	 *
	 * @param mixed $t A TemplateType object, An integer template type id, or a string template type identifier
	 * @return Template
	 * @throws DataException
	 */
	public static function load_dflt_by_type($t)
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method','TemplateOperations::get_default_template_by_type()'));
		return self::get_operations()::get_default_template_by_type($t);
	}

	/**
	 * Load all templates of a specific type
	 * @deprecated since 3.0 use corresponding TemplateOperations method
	 *
	 * @param TemplateType $type
	 * @return array Template object(s) | empty
	 * @throws DataException
	 */
	public static function load_all_by_type(TemplateType $type)
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method','TemplateOperations::get_all_templates_by_type()'));
		return self::get_operations()::get_all_templates_by_type($type);
	}

	/**
	 * Process a named template through smarty
	 * @deprecated since 3.0 use corresponding TemplateOperations method
	 *
	 * @param string $name
	 * @return string
	 */
	public static function process_by_name($name)
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method','TemplateOperations::process_named_template()'));
		return self::get_operations()::process_named_template($name);
	}

	/**
	 * Process the default template of a specified type
	 * @deprecated since 3.0 use corresponding TemplateOperations method
	 *
	 * @param mixed $t A TemplateType object, an integer template type id, or a string template type identifier
	 * @return string
	 */
	public static function process_dflt($t)
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method','TemplateOperations::process_default_template()'));
		return self::get_operations()::process_default_template($t);
	}

	/**
	 * Get the id's of all loaded templates
	 * @deprecated since 3.0 no local caching is done
	 *
	 * @return null
	 */
	public static function get_loaded_templates()
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method does nothing',''));
		return null;
	}

	/**
	 * Generate a unique name for a template
	 * @deprecated since 3.0 use corresponding TemplateOperations method
	 *
	 * @param string $prototype A prototype template name
	 * @param string $prefix An optional name prefix.
	 * @return string
	 * @throws DataException
	 */
	public static function generate_unique_name($prototype,$prefix = '')
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method','TemplateOperations::get_unique_template_name()'));
		return self::get_operations()::get_unique_template_name($prototype,$prefix);
	}
} // class
