<?php
/*
A class to manage template-types.
Copyright (C) 2014-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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
namespace CMSMS;

use CMSMS\DataException;
use CMSMS\Events;
use CMSMS\LockOperations;
use CMSMS\Lone;
use CMSMS\SQLException;
use CMSMS\Template;
use CMSMS\TemplateOperations;
use CMSMS\TemplateTypeAssistant;
use CMSMS\Utils;
use LogicException;
use RuntimeException;
use UnexpectedValueException;
use const CMS_DB_PREFIX;
use function cms_to_stamp;
use function CMSMS\log_info;
use function lang;

/**
 * A class to manage template-types
 *
 * @package CMS
 * @license GPL
 * @since 3.0
 * @since 2.0 as global-namespace CmsLayoutTemplateType
 */
class TemplateType
{
	/**
	 * This constant indicates a core template-type
	 * @see also Template::CORE
	 */
	const CORE = '__CORE__';

	/**
	 * This constant indicates a serialized string
	 */
	const SERIAL = '_|SE6ED|_'; // shortened '_SERIALIZED_'

	/**
	 * @ignore
	 */
	const TABLENAME = 'layout_tpl_types';

	/**
	 * @ignore
	 * self::TABLENAME fields
	 */
	private const PROPS = [
		'id',
		'originator',
		'name',
		'description',
		'lang_cb',
		'dflt_content_cb',
		'help_content_cb',
		'has_dflt',
		'requires_contentblocks',
		'one_only',
		'owner_id',
		'dflt_content',
		'create_date',
		'modified_date',
	];

	/**
	 * @ignore
	 */
	private $dirty;

	/**
	 * @ignore
	 */
	private $assistant;

	/**
	 * @ignore
	 * This object's properties, some or all of self::PROPS
	 */
	private $props;

	// static properties here >> Lone property|ies ?
	/**
	 * @ignore
	 * Intra-request cache of loaded type-objects
	 * Each member like: id => object
	 */
	private static $cache;

	/**
	 * @ignore
	 * Intra-request cache of loaded type-objects' source
	 * Each member like: originator::name => id
	 */
	private static $name_cache;

	/**
	 * @ignore
	 * Intra-request cache of all locks on TemplateType objects
	 * Each member like: oid => lock-object
	 */
	private static $lock_cache;

	/**
	 * @ignore
	 * Intra-request cache of flag whether lock_cache has been populated
	 */
	private static $lock_cache_loaded = FALSE;

	/**
	 * Constructor
	 * @param mixed $props array | null Optional type-properties Since 3.0
	 */
	public function __construct($props = NULL)
	{
		$this->props = [
			'id' => 0,
			'originator' => NULL,
			'name' => NULL,
			'description' => NULL,
			'lang_cb' => NULL,
			'dflt_content_cb' => NULL,
			'help_content_cb' => NULL,
			'has_dflt' => 0,
			'requires_contentblocks' => 0,
			'one_only' => 0,
			'owner_id' => 1,
			'dflt_content' => NULL,
			'create_date' => NULL,
			'modified_date' => NULL,
		];
		if( $props && is_array($props) ) {
			$keeps = array_intersect_key($props, $this->props);
			$this->props = array_merge($this->props, $keeps);
		}
		$this->dirty = TRUE;
	}

	/**
	 * Get the template-type id
	 *
	 * @return mixed int type id, or 0 or null if this type has no id ATM.
	 */
	public function get_id()
	{
		return $this->props['id'] ?? NULL;
	}

	/**
	 * Get the template-type originator (often '__CORE__' or a module name)
	 *
	 * @param  bool $viewable Whether the originator should be viewable (friendly) format Default false
	 * @return string
	 */
	public function get_originator($viewable = FALSE)
	{
		$out = $this->props['originator'] ?? NULL;
		if( $viewable ) {
			if( !$out ) { $out = ''; }
			elseif( $out == self::CORE ) { $out = 'Core'; }
		}
		return $out;
	}

	/**
	 * Record the template-type originator.
	 *
	 * @param string $str The originator name, normally '__CORE__' or a module name, not falsy.
	 * @throws LogicException
	 */
	public function set_originator($str)
	{
		$str = trim($str);
		if( !$str ) throw new LogicException('Originator cannot be empty');
		if( $str == 'Core' ) $str = self::CORE;
		$this->props['originator'] = $str;
		$this->dirty = TRUE;
	}

	/**
	 * Get the template-type name.
	 *
	 * @return string the template-type
	 */
	public function get_name()
	{
		return $this->props['name'] ?? '';
	}

	/**
	 * Record the template-type name
	 *
	 * @param sting $str The template-type name, not falsy.
	 * @throws LogicException
	 */
	public function set_name($str)
	{
		$str = trim($str);
		if( !$str ) throw new LogicException('Name cannot be empty');
		$this->props['name'] = $str;
		$this->dirty = TRUE;
	}

	/**
	 * Get the flag indicating whether this template-type has a 'default' template.
	 *
	 * @return bool
	 */
	public function get_dflt_flag()
	{
		return !empty($this->props['has_dflt']);
	}

	/**
	 * Record whether this template-type has a 'default' template.
	 *
	 * @param bool $flag Optional value, default true
	 * @throws UnexpectedValueException
	 */
	public function set_dflt_flag($flag = TRUE)
	{
		if( !is_bool($flag) ) throw new UnexpectedValueException('value is invalid for set_dflt_flag');
		$this->props['has_dflt'] = $flag;
		$this->dirty = TRUE;
	}

	/**
	 * Get the default content used when creating a new template of this type.
	 *
	 * @return string
	 */
	public function get_dflt_contents()
	{
		return $this->props['dflt_content'] ?? '';
	}

	/**
	 * Record the default content to be used when creating a new template of this type.
	 *
	 * @param string $str The default template contents.
	 */
	public function set_dflt_contents($str)
	{
		$this->props['dflt_content'] = $str;
		$this->dirty = TRUE;
	}

	/**
	 * Get the template-type description.
	 *
	 * @return string
	 */
	public function get_description()
	{
		return $this->props['description'] ?? '';
	}

	/**
	 * Record the description for this template-type.
	 *
	 * @param string $str The description
	 */
	public function set_description($str)
	{
		$this->props['description'] = $str;
		$this->dirty = TRUE;
	 }

	/**
	 * Get the owner of this template-type.
	 *
	 * @return int
	 */
	public function get_owner()
	{
		return $this->props['owner'] ?? 0;
	}

	/**
	 * Record the owner of this template-type
	 *
	 * @param mixed $owner a number other than 0
	 * @throws LogicException
	 */
	public function set_owner($owner)
	{
		if( !is_numeric($owner) || (int)$owner == 0 ) throw new LogicException('value is invalid for owner in '.__METHOD__);
		$this->props['owner'] = (int)$owner;
		$this->dirty = TRUE;
	}

	/**
	 * Get the timestamp for when this template-type was first saved
	 * @since 3.0
	 *
	 * @return mixed Unix timestamp, or null if this object has not been saved.
	 */
	public function get_created()
	{
		$str = $this->props['create_date'] ?? '';
		return ($str) ? cms_to_stamp($str) : NULL;
	}

	/**
	 * @deprecated since 3.0 use get_created()
	 */
	public function get_create_date()
	{
		return $this->get_created();
	}

	/**
	 * Get the timestamp for when this template-type was last saved.
	 * @since 3.0
	 *
	 * @return int Unix timestamp. Default 1 (i.e. non-falsy)
	 */
	public function get_modified()
	{
		$str = $this->props['modified_date'] ?? $this->props['create_date'] ?? '';
		return ($str) ? cms_to_stamp($str) : 1;
	}

	/**
	 * @deprecated since 3.0 use get_modified()
	 */
	public function get_modified_date()
	{
		return $this->get_modified();
	}

	/**
	 * Record the callable to be used to retrieve a translated version of the originator and name strings.
	 *
	 * @param callable $callback A static [class::]function name string, or
	 * an array representing a class name and method name.
	 * Not checked here for callability, so may be null.
	 */
	public function set_lang_callback($callback)
	{
		$this->props['lang_callback'] = $callback;
		$this->dirty = TRUE;
	}

	/**
	 * Get the callable used to translate the originator and name strings.
	 *
	 * @return mixed string or array or null
	 */
	public function get_lang_callback()
	{
		return $this->props['lang_callback'] ?? NULL; //NOTE key != database field name
	}

	/**
	 * Record the callable to be used to display help for this template when editing.
	 *
	 * @param callable $callback A static [class::]function name string, or
	 * an array of a class name and member name.
	 * Not checked here for callability, so may be null.
	 */
	public function set_help_callback($callback)
	{
		$this->props['help_callback'] = $callback; //NOTE key != database field name
		$this->dirty = TRUE;
	}

	/**
	 * Get the callable used to retrieve help for this template-type.
	 *
	 * @return mixed string or array or null
	 */
	public function get_help_callback()
	{
		return $this->props['help_callback'] ?? NULL;
	}

	/**
	 * Record the callable to be used to reset the template content to factory-default value.
	 *
	 * @param callable $callback A static [class::]function name string, or
	 * an array of a class name and member name.
	 * Not checked here for callability, so may be null.
	 */
	public function set_content_callback($callback)
	{
		$this->props['content_callback'] = $callback; //NOTE key != database field name
		$this->dirty = TRUE;
	}

	/**
	 * Get the callable used to reset a template to its factory default value.
	 *
	 * @return mixed string or array or null
	 */
	public function get_content_callback()
	{
		return $this->props['content_callback'] ?? NULL;
	}

	/**
	 * Record whether at most one template of this type is permitted.
	 *
	 * @param bool $flag Optional, default true
	 * @throws UnexpectedValueException
	 */
	public function set_oneonly_flag($flag = TRUE)
	{
		if( !is_bool($flag) ) throw new UnexpectedValueException('value is invalid for set_oneonly_flag');
		$this->props['one_only'] = $flag;
		$this->dirty = TRUE;
	}

	/**
	 * Get the flag indicating whether at most one template of this type is permitted.
	 *
	 * @return bool
	 */
	public function get_oneonly_flag()
	{
		return !empty($this->props['one_only']);
	}

	/**
	 * Record whether this template-type requires content blocks
	 *
	 * @param bool $flag
	 */
	public function set_content_block_flag($flag)
	{
		$flag = (bool)$flag;
		$this->props['requires_contentblocks'] = $flag;
		$this->dirty = TRUE;
	}

	/**
	 * Get the flag indicating whether this template-type requires content blocks
	 *
	 * @return bool
	 */
	public function get_content_block_flag()
	{
		return !empty($this->props['requires_contentblocks']);
	}

	/**
	 * @ignore
	 */
	private static function get_locks() : array
	{
		if( !self::$lock_cache_loaded ) {
			self::$lock_cache = [];
			$tmp = LockOperations::get_locks('templatetype');
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
	 * Get any applicable lock for this template-type object
	 * @since 3.0
	 *
	 * @return mixed Lock | null
	 * @see Lock
	 */
	public function get_lock()
	{
		$locks = self::get_locks();
		return $locks[$this->get_id()] ?? NULL;
	}

	/**
	 * Test whether this template-type object currently has a lock
	 * @since 3.0
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
	 * @since 3.0
	 *
	 * @return bool
	 */
	public function lock_expired()
	{
		$lock = $this->get_lock();
		if( is_object($lock) ) return $lock->expired();
		return FALSE;
	}

	/**
	 * Validate the integrity of a template-type object.
	 *
	 * This method will check the contents of the object for validity,
	 * and ensure that the originator/name combination is unique.
	 *
	 * This method throws an exception if an error is found in the integrity of the object.
	 *
	 * @param bool $is_insert Optional flag whether this is a for new (as opposed to updated) template-type. Default true
	 * @throws DataException or UnexpectedValueException or LogicException
	 */
	protected function validate($is_insert = TRUE)
	{
		if( !$this->get_originator() ) throw new DataException('Missing Type Originator');
		if( !$this->get_name() ) throw new DataException('Missing Type Name');
		if( !preg_match('/[A-Za-z0-9_\,\.\ ]/',$this->get_name()) ) {
			throw new UnexpectedValueException('Template type name cannot be \''.$this->get_name().'\'. Name must contain only letters, numbers and/or underscores.');
		}

		if( !$is_insert ) {
			if( !isset($this->props['id']) || (int)$this->props['id'] < 1 ) throw new LogicException('Type id is not set');

			// check for item with the same name
			$db = Lone::get('Db');
			$query = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.
			' WHERE originator=? AND `name`=? AND id != ?';
			$dbr = $db->getOne($query,[$this->get_originator(),$this->get_name(),$this->get_id()]);
			if( $dbr ) throw new LogicException('A template-type named \''.$this->get_name().'\' already exists.');
		}
		else {
			// check for item with the same name
			$db = Lone::get('Db');
			$query = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.
			' WHERE originator=? AND `name`=?';
			$dbr = $db->getOne($query,[$this->get_originator(),$this->get_name()]);
			if( $dbr ) throw new LogicException('A template-type named \''.$this->get_name().'\' already exists.');
		}
	}

	/**
	 * Insert the current template-type object into the database.
	 *
	 * This method will ensure that the current object is valid, generate an id, and
	 * insert the record into the database.  An exception will be thrown if errors occur.
	 *
	 * @throws SQLException
	 */
	protected function _insert()
	{
		if( !$this->dirty ) return;
		$this->validate();
//		$now = time();

		$orig = $this->get_originator();
		$name = $this->get_name();
		$cbl = $this->get_lang_callback();
		if( $cbl ) {
			if( !is_scalar($cbl) ) {
				$cbl = self::SERIAL.serialize($cbl);
			}
		}
		else {
			$cbl = NULL;
		}
		$cbh = $this->get_help_callback();
		if( $cbh ) {
			if( !is_scalar($cbh) ) {
				$cbh = self::SERIAL.serialize($cbh);
			}
		}
		else {
			$cbh = NULL;
		}
		$cbc = $this->get_content_callback();
		if( $cbc ) {
			if( !is_scalar($cbc) ) {
				$cbc = self::SERIAL.serialize($cbc);
			}
		}
		else {
			$cbc = NULL;
		}
		$db = Lone::get('Db');
		$query = 'INSERT INTO '.CMS_DB_PREFIX.self::TABLENAME.
' (
originator,
`name`,
has_dflt,
one_only,
description,
lang_cb,
help_content_cb,
dflt_content_cb,
requires_contentblocks,
owner_id,
dflt_content,
create_date
) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)';
		$args = [
			$orig,
			$name,
			($this->get_dflt_flag() ? 1 : 0),
			($this->get_oneonly_flag() ? 1 : 0),
			$this->get_description(),
			$cbl,
			$cbh,
			$cbc,
			($this->get_content_block_flag() ? 1 : 0),
			$this->get_owner(),
			$this->get_dflt_contents(),
			$db->DbTimeStamp(time(), false),
		];
		$dbr = $db->execute($query,$args);
		if( !$dbr ) throw new SQLException($db->sql.' -- '.$db->errorMsg());

		$this->props['id'] = $db->Insert_ID(); // i.e. $dbr, here
		$this->dirty = NULL;

		log_info($this->get_id(),$orig,'Template-Type \''.$name.'\' Created');
	}


	/**
	 * Update the contents of the database to match the current record.
	 *
	 * This method will ensure that the current object is valid, generate an id,
	 * and update the record in the database.
	 *
	 * @throws SQLException if error occurs
	 */
	protected function _update()
	{
		if( !$this->dirty ) return;
		$this->validate(FALSE);

		$cbl = $this->get_lang_callback();
		if( $cbl ) {
			if( !is_scalar($cbl) ) {
				$cbl = self::SERIAL.serialize($cbl);
			}
		}
		else {
			$cbl = NULL;
		}
		$cbh = $this->get_help_callback();
		if( $cbh ) {
			if( !is_scalar($cbh) ) {
				$cbh = self::SERIAL.serialize($cbh);
			}
		}
		else {
			$cbh = NULL;
		}
		$cbc = $this->get_content_callback();
		if( $cbc ) {
			if( !is_scalar($cbc) ) {
				$cbc = self::SERIAL.serialize($cbc);
			}
		}
		else {
			$cbc = NULL;
		}

		$db = Lone::get('Db');
		$args = [
			$this->get_originator(),
			$this->get_name(),
			($this->get_dflt_flag() ? 1 : 0),
			($this->get_oneonly_flag() ? 1 : 0),
			$this->get_description(),
			$cbl,
			$cbh,
			$cbc,
			($this->get_content_block_flag() ? 1 : 0),
			$this->get_owner(),
			$this->get_dflt_contents(),
			$db->DbTimeStamp(time(), FALSE),
			$this->get_id(),
		];

		$query = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET
originator=?,
`name`=?,
has_dflt=?,
one_only=?,
description=?,
lang_cb=?,
help_content_cb=?,
dflt_content_cb=?,
requires_contentblocks=?,
owner_id=?
dflt_content=?,
modified_date=?
WHERE id=?';
		$db->execute($query,$args);
		if( $db->errorNo() === 0 ) {
			$this->dirty = FALSE;
			log_info($this->get_id(),'CMSMS','template-type '.$this->get_name().' Updated');
			return;
		}
		throw new SQLException($db->errMsg());
	}

	/**
	 * Save the current record to the database.
	 */
	public function save()
	{
		if( !$this->get_id() ) {
			Events::SendEvent('Core', 'AddTemplateTypePre', [ get_class($this) => &$this ]);
			$this->_insert();
			Events::SendEvent('Core', 'AddTemplateTypePost', [ get_class($this) => &$this ]);
			return;
		}
		Events::SendEvent('Core', 'EditTemplateTypePre', [ get_class($this) => &$this ]);
		$this->_update();
		Events::SendEvent('Core', 'EditTemplateTypePost', [ get_class($this) => &$this ]);
	}

	/**
	 * Get a list of templates for the current template-type.
	 *
	 * @return array of Template object(s) | empty
	 */
	public function get_template_list()
	{
		return TemplateOperations::get_all_templates_by_type($this);
	}

	/**
	 * Delete the current object from the database (if it has been saved).
	 *
	 * @throws LogicException or SQLException
	 */
	public function delete()
	{
		if( !$this->get_id() ) return;

		Events::SendEvent('Core', 'DeleteTemplateTypePre', [ get_class($this) => &$this ]);
		$tmp = TemplateOperations::template_query(['t:'.$this->get_id()]);
		if( $tmp ) throw new LogicException('Cannot delete a template-type with existing templates');
		$db = Lone::get('Db');
		$query = 'DELETE FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id=?';
		$dbr = $db->execute($query,[$this->props['id']]);
		if( !$dbr ) throw new SQLException($db->sql.' -- '.$db->errorMsg());

		$this->dirty = TRUE;
		log_info($this->get_id(),'CMSMS','template-type '.$this->get_name().' Deleted');
		Events::SendEvent('Core', 'DeleteTemplateTypePost', [ get_class($this) => &$this ]);
		unset($this->props['id']);
	}

	/**
	 * Create a new template of this type
	 *
	 * This method will throw an exception if the template cannot be created.
	 *
	 * @param string $name Optional template name
	 * @return Template object.
	 */
	public function create_new_template($name = '')
	{
		$ob = new Template();
		$ob->set_originator($this->get_originator());
		if( $name ) $ob->set_name($name);
		$ob->set_type($this);
		$ob->set_content($this->get_dflt_contents());
		return $ob;
	}

	/**
	 * Get the default template of this type
	 *
	 * This method will throw an exception if the template cannot be created.
	 *
	 * @see TemplateOperations::get_default_template_by_type()
	 * @return mixed Template object | null
	 */
	public function get_dflt_template()
	{
		return TemplateOperations::get_default_template_by_type($this);
	}

	/**
	 * Get HTML text for help with respect to the variables available in this template-type.
	 */
	public function get_template_helptext()
	{
		$cb = $this->get_help_callback();
		$name = $this->get_name();
		if( $cb && is_callable($cb) ) {
			$text = $cb($name);
			return $text;
		}

		$text = NULL;
		// no callback specified. Maybe a fallback if this originator is a loadable module.
		$originator = $this->get_originator();
		if( $originator !== self::CORE ) {
			// it's not a core page template, or generic
			$module = Utils::get_module($originator);
			if( $module ) {
				if( method_exists($module,'get_templatetype_help') ) {
					$text = $module->get_templatetype_help($name);
				}
			}
		}
		return $text;
	}

	/**
	 * Get translated public/displayable name representing this template-type
	 * and its originator.
	 */
	public function get_langified_display_value()
	{
		$cb = $this->get_lang_callback();
		if( is_callable($cb) ) {
			$to = $cb($this->get_originator());
			$tn = $cb($this->get_name());
		}
		else {
			$to = $tn = NULL;
		}
		if( !$to ) { $to = $this->get_originator(); }
		if( $to == self::CORE ) { $to = lang('core'); }
		if( !$tn ) { $tn = $this->get_name(); }
		return $to.'::'.$tn;
	}

	/**
	 * Reset the default contents of this template-type back to factory default
	 *
	 * @throws LogicException
	 */
	public function reset_content_to_factory()
	{
		if( !$this->get_dflt_flag() ) {
			throw new LogicException('This template-type does not have default contents');
		}
		$cb = $this->get_content_callback();
		if( !$cb || !is_callable($cb) ) {
			throw new LogicException('No callback information to reset content');
		}
		$content = $cb($this);
		$this->set_dflt_contents($content);
	}

	/**
	 * Create a TemplateType object reflecting the given properties array
	 * (typically read from the database)
	 *
	 * @internal
	 * @param array $row
	 * @return TemplateType
	 */
	private static function _load_from_data($row) : self
	{
		$pattern = '/^([as]:\d+:|[Nn](ull)?;)/';
		$l = strlen(self::SERIAL);
		if( !empty($row['lang_cb']) ) {
			$t = $row['lang_cb'];
			if( strncmp($t,self::SERIAL,$l) == 0 ) {
				$t = unserialize(substr($t,$l),['allowed_classes'=>FALSE]);
			}
			elseif( preg_match($pattern, $t) ) {
				$t = unserialize($t,['allowed_classes'=>FALSE]);
			}
		}
		else {
			$t = NULL;
		}
		$row['lang_callback'] = $t;

		if( !empty($row['help_content_cb']) ) {
			$t = $row['help_content_cb'];
			if( strncmp($t,self::SERIAL,$l) == 0 ) {
				$t = unserialize(substr($t,$l),['allowed_classes'=>FALSE]);
			}
			elseif( preg_match($pattern, $t) ) {
				$t = unserialize($t,['allowed_classes'=>FALSE]);
			}
		}
		else {
			$t = NULL;
		}
		$row['help_callback'] = $t;

		if( !empty($row['dflt_content_cb']) ) {
			$t = $row['dflt_content_cb'];
			if( strncmp($t,self::SERIAL,$l) == 0 ) {
				$t = unserialize(substr($t,$l),['allowed_classes'=>FALSE]);
			}
			elseif( preg_match($pattern, $t) ) {
				$t = unserialize($t,['allowed_classes'=>FALSE]);
			}
		}
		else {
			$t = NULL;
		}
		$row['content_callback'] = $t;
		unset($row['lang_cb'],$row['help_content_cb'],$row['dflt_content_cb']);

		$ob = new self($row);
		$ob->dirty = FALSE;

		$id = $ob->get_id();
		self::$cache[$id] = $ob;
		self::$name_cache[$ob->get_originator().'::'.$ob->get_name()] = $id;
		return $ob;
	}

	/**
	 * Retrieve a TemplateType from the request-cache or from database.
	 *
	 * @param mixed $a int template-type id, or string name like 'originator::name'
	 * @return TemplateType object
	 * @throws RuntimeException if nil or > 1 matches are found
	 */
	public static function load($a)
	{
		$db = Lone::get('Db');
		$row = NULL;
		if( is_numeric($a) && (int)$a > 0 ) {
			$a = (int)$a;
			if( isset(self::$cache[$a]) ) return self::$cache[$a];
			// just in case: check the database
			$query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id=?';
			$row = $db->getRow($query,[$a]);
			if( $row ) {
				$id = (int)$row['id'];
				self::$cache[$id] = self::_load_from_data($row);
				return self::$cache[$id];
			}
		}
		elseif( is_string($a) && $a !== '' ) {
			if( isset(self::$name_cache[$a]) ) {
				$id = self::$name_cache[$a];
				return self::$cache[$id];
			}

			$parts = explode('::',$a,2);
			if( count($parts) == 1 ) {
				$query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE `name`=?';
				$all = $db->getArray($query,[$a]);
				if( $all ) {
					if( count($all) == 1 ) {
						$row = $all[0];
					}
					else {
						throw new RuntimeException("Multiple template-types match '$a'");
					}
				}
				else {
					$row = false;
				}
			}
			else {
				$query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE originator=? AND `name`=?';
				if( !$parts[0] || strcasecmp($parts[0],'core') == 0 )  { $parts[0] = self::CORE; }
				$row = $db->getRow($query,[trim($parts[0]),trim($parts[1])]);
				if( $row ) {
					self::$cache[$row['id']] = self::_load_from_data($row);
					return self::$cache[$row['id']];
				}
			}
		}
		if( $row ) return self::_load_from_data($row);
		throw new RuntimeException('Could not find template-type identified by '.$a);
	}

	/**
	 * Load all template-types whose originator is as specified.
	 *
	 * @param string $originator The originator name
	 * @return array TemplateType object(s) | empty if no match is found.
	 * @throws LogicException
	 */
	public static function load_all_by_originator($originator)
	{
		if( !$originator ) throw new LogicException('Orignator is empty');

		$db = Lone::get('Db');
		$query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE originator=?';
		if( self::$cache ) $query .= ' AND id NOT IN ('.implode(',',array_keys(self::$cache)).')';
		$query .= ' ORDER BY IF(modified_date, modified_date, create_date) DESC';
		$list = $db->getArray($query,[$originator]);
		if( !$list ) return [];

		foreach( $list as $row ) {
			self::_load_from_data($row);
		}

		$out = [];
		foreach( self::$cache as $id => $one ) {
			if( $one->get_originator() == $originator ) $out[] = $one;
		}
		return $out;
	}

	/**
	 * Load all template-types
	 *
	 * @return array TemplateType object(s) | empty
	 */
	public static function get_all()
	{
		$db = Lone::get('Db');
		$query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME;
		if( self::$cache && count(self::$cache) ) $query .= ' WHERE id NOT IN ('.implode(',',array_keys(self::$cache)).')';
		$query .= '	ORDER BY IF(modified_date, modified_date, create_date)';
		$list = $db->getArray($query);
		if( !$list ) return [];

		foreach( $list as $row ) {
			self::_load_from_data($row);
		}
		return array_values(self::$cache);
	}

	/**
	 * Load the template-types in the provided list and which are not already loaded
	 *
	 * @param int[] $list Array of template-type ids
	 * @return array newly-loaded TemplateType object(s) | empty
	 */
	public static function load_bulk($list)
	{
		if( !$list ) return [];

		$list2 = [];
		foreach( $list as $one ) {
			if( !is_numeric($one) || (int)$one < 1 ) continue;
			$one = (int)$one;
			if( isset(self::$cache[$one]) ) continue;
			$list2[] = $one;
		}
		if( !$list2 ) return [];

		$db = Lone::get('Db');
		$query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id IN ('.implode(',',$list2).')';
		$dbr = $db->getArray($query);
		if( !$dbr ) return [];

		$out = [];
		foreach( $dbr as $row ) {
			$out[] = self::_load_from_data($row);
		}
		return $out;
	}

	/**
	 * Return the names of all loaded template-types
	 *
	 * @return array loaded TemplateType name(s) | empty
	 */
	public static function get_loaded_types()
	{
		if( is_array(self::$cache) ) return array_keys(self::$cache);
		return [];
	}

	/**
	 * Get the assistant object with utility methods for this template-type (if such an assistant object can be instantiated)
	 *
	 * @since 2.2
	 * @return mixed TemplateTypeAssistant | null
	 */
	public function getassistant()
	{
		if( !$this->assistant ) {
			$org = $this->get_originator(); // TODO if '__CORE__' ?
			$nm = $this->get_name();
			if( !$org || !$nm ) return;
			$classnames = [];
			$classnames[] = 'CMSMS\internal\\'.$org.$nm.'_Type_Assistant';
			$classnames[] = 'CMSMS\Layout\\'.$org.$nm.'_Type_Assistant';
			$classnames[] = $org.'_'.$nm.'_Type_Assistant';
			foreach( $classnames as $cn ) {
				if( class_exists($cn) ) {
					$tmp = new $cn();
					if( $tmp instanceof TemplateTypeAssistant ) {
						$this->assistant = $tmp;
						break;
					}
				}
			}
		}
		return $this->assistant;
	}

	/**
	 * Get a usage string for this template-type.
	 *
	 * @since 2.2
	 * @param string $name The name of this object.
	 * @return mixed string | null
	 */
	public function get_usage_string($name)
	{
		$name = trim($name);
		if( !$name ) return;

		$assistant = $this->getassistant();
		if( !$assistant ) return;

		return $assistant->get_usage_string($name);
	}
} // class
