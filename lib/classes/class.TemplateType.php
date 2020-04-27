<?php
# A class to manage template-types.
# Copyright (C) 2014-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace CMSMS;

use CmsDataNotFoundException;
use CmsException;
use CmsInvalidDataException;
use CmsLayoutTemplate;
use CmsLayoutTemplateType;
use CMSMS\App;
use CMSMS\Events;
use CMSMS\Layout\TemplateTypeAssistant;
use CMSMS\LockOperations;
use CMSMS\Template;
use CMSMS\TemplateOperations;
use CMSMS\Utils;
use CmsSQLErrorException;
use const CMS_DB_PREFIX;
use function audit;
use function cms_to_stamp;
use function lang;

/**
 * A class to manage template-types
 *
 * @package CMS
 * @license GPL
 * @since 2.9
 * @since 2.0 as CmsLayoutTemplateType
 * @author Robert Campbell <calguy1000@cmsmadesimple.org>
 */
class TemplateType
{
	/**
	 * This constant indicates a core template-type
	 * @see also CmsLayoutTemplate::CORE
	 */
	const CORE = '__CORE__';

	/**
	 * This constant indicates a serialized string
	 */
	const SERIAL = '_|SE6ED|_'; // shortened '_SERIALIZED_'

	/**
	 * @ignore
	 */
	const TABLENAME = 'layout_tpl_type';

	/**
	 * @ignore
	 */
	private $_dirty = false;

	/**
	 * @ignore
	 */
	private $_data = [];

    // static properties here >> StaticProperties class ?
	/**
	 * @ignore
	 */
	private static $_cache;

	/**
	 * @ignore
	 */
	private static $_name_cache;

	/**
	 * @ignore
	 */
	private $_assistant;

	private static $_lock_cache;
	private static $_lock_cache_loaded = false;

	/**
	 * Get the template-type id
	 *
	 * @return mixed int type id, or null if this type has no id ATM.
	 */
	public function get_id()
	{
		return $this->_data['id'] ?? null;
	}

	/**
	 * Get the template-type originator (often '__CORE__' or a module name)
	 *
	 * @param  bool $viewable Whether the originator should be viewable (friendly) format Default false
	 * @return string
	 */
	public function get_originator($viewable = FALSE)
	{
		$out = $this->_data['originator'] ?? '';
		if( $viewable && $out == self::CORE ) $out = 'Core';
		return $out;
	}

	/**
	 * Record the template-type originator.
	 *
	 * @param string $str The originator name, normally '__CORE__' or a module name, not falsy.
	 * @throws CmsInvalidDataException
	 */
	public function set_originator($str)
	{
		$str = trim($str);
		if( !$str ) throw new CmsInvalidDataException('Originator cannot be empty');
		if( $str == 'Core' ) $str = self::CORE;
		$this->_data['originator'] = $str;
		$this->_dirty = TRUE;
	}

	/**
	 * Get the template-type name.
	 *
	 * @return string the template-type
	 */
	public function get_name()
	{
		return $this->_data['name'] ?? '';
	}

	/**
	 * Record the template-type name
	 *
	 * @param sting $str The template-type name, not falsy.
	 * @throws CmsInvalidDataException
	 */
	public function set_name($str)
	{
		$str = trim($str);
		if( !$str ) throw new CmsInvalidDataException('Name cannot be empty');
		$this->_data['name'] = $str;
		$this->_dirty = TRUE;
	}

	/**
	 * Get the flag indicating whether this template-type has a 'default' template.
	 *
	 * @return bool
	 */
	public function get_dflt_flag()
	{
		return !empty($this->_data['has_dflt']);
	}

	/**
	 * Record whether this template-type has a 'default' template.
	 *
	 * @param bool $flag Optional value, default true
	 * @throws CmsInvalidDataException
	 */
	public function set_dflt_flag($flag = TRUE)
	{
		if( !is_bool($flag) ) throw new CmsInvalidDataException('value is invalid for set_dflt_flag');
		$this->_data['has_dflt'] = $flag;
		$this->_dirty = TRUE;
	}

	/**
	 * Get the default content used when creating a new template of this type.
	 *
	 * @return string
	 */
	public function get_dflt_contents()
	{
		return $this->_data['dflt_contents'] ?? '';
	}

	/**
	 * Record the default content to be used when creating a new template of this type.
	 *
	 * @param string $str The default template contents.
	 */
	public function set_dflt_contents($str)
	{
		$this->_data['dflt_contents'] = $str;
		$this->_dirty = TRUE;
	}

	/**
	 * Get the template-type description.
	 *
	 * @return string
	 */
	public function get_description()
	{
		return $this->_data['description'] ?? '';
	}

	/**
	 * Record the description for this template-type.
	 *
	 * @param string $str The description
	 */
	public function set_description($str)
	{
		$this->_data['description'] = $str;
		$this->_dirty = TRUE;
	 }

	/**
	 * Get the owner of this template-type.
	 *
	 * @return int
	 */
	public function get_owner()
	{
		return $this->_data['owner'] ?? 0;
	}

	/**
	 * Record the owner of this template-type
	 *
	 * @param mixed $owner a number other than 0
	 * @throws CmsInvalidDataException
	 */
	public function set_owner($owner)
	{
		if( !is_numeric($owner) || (int)$owner == 0 ) throw new CmsInvalidDataException('value is invalid for owner in '.__METHOD__);
		$this->_data['owner'] = (int)$owner;
		$this->_dirty = TRUE;
	}

	/**
	 * Get the timestamp for when this template-type was first saved
	 * @since 2.3
	 *
	 * @return mixed Unix timestamp, or null if this object has not been saved.
	 */
	public function get_created()
	{
		$str = $this->_data['create_date'] ?? '';
		return ($str) ? cms_to_stamp($str) : null;
	}

	/**
	 * @deprecated since 2.3 use get_created()
	 */
	public function get_create_date()
	{
		return $this->get_created();
	}

	/**
	 * Get the timestamp for when this template-type was last saved.
	 * @since 2.3
	 *
	 * @return mixed Unix timestamp, or null if this object has not been saved.
	 */
	public function get_modified()
	{
		$str = $this->_data['modified_date'] ?? '';
		return ($str) ? cms_to_stamp($str) : $this->get_created();
	}

	/**
	 * @deprecated since 2.3 use get_modified()
	 */
	public function get_modified_date()
	{
		return $this->get_modified();
	}

	/**
	 * Record the callback to be used to retrieve a translated version of the originator and name strings.
	 *
	 * @param callable $callback A static [class::]function name string, or
	 * an array representing a class name and method name.
	 * Not checked here for callability, so may be null.
	 */
	public function set_lang_callback($callback)
	{
		$this->_data['lang_callback'] = $callback;
		$this->_dirty = TRUE;
	}

	/**
	 * Get the callback used to translate the originator and name strings.
	 *
	 * @return mixed string or array or null
	 */
	public function get_lang_callback()
	{
		return $this->_data['lang_callback'] ?? null; //NOTE key != database field name
	}

	/**
	 * Record the callback to be used to display help for this template when editing.
	 *
	 * @param callable $callback A static [class::]function name string, or
	 * an array of a class name and member name.
	 * Not checked here for callability, so may be null.
	 */
	public function set_help_callback($callback)
	{
		$this->_data['help_callback'] = $callback; //NOTE key != database field name
		$this->_dirty = TRUE;
	}

	/**
	 * Get the callback used to retrieve help for this template-type.
	 *
	 * @return mixed string or array or null
	 */
	public function get_help_callback()
	{
		return $this->_data['help_callback'] ?? null;
	}

	/**
	 * Record the callback to be used to reset the template content to factory-default value.
	 *
	 * @param callable $callback A static [class::]function name string, or
	 * an array of a class name and member name.
	 * Not checked here for callability, so may be null.
	 */
	public function set_content_callback($callback)
	{
		$this->_data['content_callback'] = $callback; //NOTE key != database field name
		$this->_dirty = TRUE;
	}

	/**
	 * Get the callback used to reset a template to its factory default value.
	 *
	 * @return mixed string or array or null
	 */
	public function get_content_callback()
	{
		return $this->_data['content_callback'] ?? null;
	}

	/**
	 * Record whether at most one template of this type is permitted.
	 *
	 * @param bool $flag Optional, default true
	 * @throws CmsInvalidDataException
	 */
	public function set_oneonly_flag($flag = TRUE)
	{
		if( !is_bool($flag) ) throw new CmsInvalidDataException('value is invalid for set_oneonly_flag');
		$this->_data['one_only'] = $flag;
		$this->_dirty = TRUE;
	}

	/**
	 * Get the flag indicating whether at most one template of this type is permitted.
	 *
	 * @return bool
	 */
	public function get_oneonly_flag()
	{
		return !empty($this->_data['one_only']);
	}

	/**
	 * Record whether this template-type requires content blocks
	 *
	 * @param bool $flag
	 */
	public function set_content_block_flag($flag)
	{
		$flag = (bool)$flag;
		$this->_data['requires_contentblocks'] = $flag;
		$this->_dirty = TRUE;
	}

	/**
	 * Get the flag indicating whether this template-type requires content blocks
	 *
	 * @return bool
	 */
	public function get_content_block_flag()
	{
		return !empty($this->_data['requires_contentblocks']);
	}

	/**
 	* @ignore
 	*/
 	private static function get_locks() : array
 	{
 		if( !self::$_lock_cache_loaded ) {
 			self::$_lock_cache = [];
 			$tmp = LockOperations::get_locks('templatetype');
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
 	* Get any applicable lock for this template-type object
	* @since 2.3
 	*
 	* @return mixed Lock | null
 	* @see Lock
 	*/
 	public function get_lock()
 	{
 		$locks = self::get_locks();
 		return $locks[$this->get_id()] ?? null;
 	}

    /**
 	* Test whether this template-type object currently has a lock
	* @since 2.3
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
	* @since 2.3
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
	 * Validate the integrity of a template-type object.
	 *
	 * This method will check the contents of the object for validity,
	 * and ensure that the originator/name combination is unique.
	 *
	 * This method throws an exception if an error is found in the integrity of the object.
	 *
	 * @param bool $is_insert Optional flag whether this is a for new (as opposed to updated) template-type. Default true
	 * @throws CmsInvalidDataException
	 */
	protected function validate($is_insert = TRUE)
	{
		if( !$this->get_originator() ) throw new CmsInvalidDataException('Invalid Type Originator');
		if( !$this->get_name() ) throw new CmsInvalidDataException('Invalid Type Name');
		if( !preg_match('/[A-Za-z0-9_\,\.\ ]/',$this->get_name()) ) {
			throw new CmsInvalidDataException('Template type name cannot be \''.$this->get_name().'\'. Name must contain only letters, numbers and/or underscores.');
		}

		if( !$is_insert ) {
			if( !isset($this->_data['id']) || (int)$this->_data['id'] < 1 ) throw new CmsInvalidDataException('id is not set');

			// check for item with the same name
			$db = App::get_instance()->GetDb();
			$query = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.
			' WHERE originator = ? AND name = ? AND id != ?';
			$dbr = $db->GetOne($query,[$this->get_originator(),$this->get_name(),$this->get_id()]);
			if( $dbr ) throw new CmsInvalidDataException('A template-type named \''.$this->get_name().'\' already exists.');
		}
		else {
			// check for item with the same name
			$db = App::get_instance()->GetDb();
			$query = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.
			' WHERE originator = ? AND name = ?';
			$dbr = $db->GetOne($query,[$this->get_originator(),$this->get_name()]);
			if( $dbr ) throw new CmsInvalidDataException('A template-type named \''.$this->get_name().'\' already exists.');
		}
	}

	/**
	 * Insert the current template-type object into the database.
	 *
	 * This method will ensure that the current object is valid, generate an id, and
	 * insert the record into the database.  An exception will be thrown if errors occur.
	 *
	 * @throws CmsSQLErrorException
	 */
	protected function _insert()
	{
		if( !$this->_dirty ) return;
		$this->validate();
		$now = time();

		$cbl = $this->get_lang_callback();
		if( $cbl ) {
			if( !is_scalar($cbl) ) {
				$cbl = self::SERIAL.serialize($cbl);
			}
		}
		else {
			$cbl = null;
		}
		$cbh = $this->get_help_callback();
		if( $cbh ) {
			if( !is_scalar($cbh) ) {
				$cbh = self::SERIAL.serialize($cbl);
			}
		}
		else {
			$cbh = null;
		}
		$cbc = $this->get_content_callback();
		if( $cbc ) {
			if( !is_scalar($cbc) ) {
				$cbc = self::SERIAL.serialize($cbl);
			}
		}
		else {
			$cbc = null;
		}

		$db = App::get_instance()->GetDb();
		$query = 'INSERT INTO '.CMS_DB_PREFIX.self::TABLENAME.
' (originator,name,has_dflt,one_only,dflt_contents,description,lang_cb,help_content_cb,dflt_content_cb,requires_contentblocks,owner)
VALUES (?,?,?,?,?,?,?,?,?,?,?)';
		$dbr = $db->Execute($query,[
			$this->get_originator(),
			$this->get_name(),
			$this->get_dflt_flag(),
			$this->get_oneonly_flag(),
			$this->get_dflt_contents(),
			$this->get_description(),
			$cbl,
			$cbh,
			$cbc,
			$this->get_content_block_flag() ? 1 : 0,
			$this->get_owner(),
]);
		if( !$dbr ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());

		$this->_data['id'] = $db->Insert_ID();

		audit($this->get_id(),'CMSMS','template-type '.$this->get_name().' Created');
		$this->_dirty = null;
	}


	/**
	 * Update the contents of the database to match the current record.
	 *
	 * This method will ensure that the current object is valid, generate an id, and
	 * update the record in the database.  An exception will be thrown if errors occur.
	 *
	 * @throws CmsSQLErrorException
	 */
	protected function _update()
	{
		if( !$this->_dirty ) return;
		$this->validate(FALSE);

		$cbl = $this->get_lang_callback();
		if( $cbl ) {
			if( !is_scalar($cbl) ) {
				$cbl = self::SERIAL.serialize($cbl);
			}
		}
		else {
			$cbl = null;
		}
		$cbh = $this->get_help_callback();
		if( $cbh ) {
			if( !is_scalar($cbh) ) {
				$cbh = self::SERIAL.serialize($cbl);
			}
		}
		else {
			$cbh = null;
		}
		$cbc = $this->get_content_callback();
		if( $cbc ) {
			if( !is_scalar($cbc) ) {
				$cbc = self::SERIAL.serialize($cbl);
			}
		}
		else {
			$cbc = null;
		}

		$db = App::get_instance()->GetDb();
		$query = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET
originator = ?,
name = ?,
has_dflt = ?,
one_only = ?,
dflt_contents = ?,
description = ?,
lang_cb = ?,
help_content_cb = ?,
dflt_content_cb = ?,
requires_contentblocks = ?,
owner = ?
WHERE id = ?';
		$dbr = $db->Execute($query,[
			$this->get_originator(),
			$this->get_name(),
			$this->get_dflt_flag(),
			$this->get_oneonly_flag(),
			$this->get_dflt_contents(),
			$this->get_description(),
			$cbl,
			$cbh,
			$cbc,
			$this->get_content_block_flag() ? 1 : 0,
			$this->get_owner(),
			$this->get_id()
		]);
		if( !$dbr ) throw new CmsSQLErrorException($db->ErrorMsg());

		$this->_dirty = null;
		audit($this->get_id(),'CMSMS','template-type '.$this->get_name().' Updated');
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
	 * @return mixed array of CmsLayoutTemplate objects | null
	 */
	public function get_template_list()
	{
		return TemplateOperations::get_all_templates_by_type($this);
	}

	/**
	 * Delete the current object from the database (if it has been saved).
	 *
	 * @throws CmsInvalidDataException
	 * @throws CmsSQLErrorException
	 */
	public function delete()
	{
		if( !$this->get_id() ) return;

		Events::SendEvent('Core', 'DeleteTemplateTypePre', [ get_class($this) => &$this ]);
		$tmp = TemplateOperations::template_query(['t:'.$this->get_id()]);
		if( $tmp ) throw new CmsInvalidDataException('Cannot delete a template-type with existing templates');
		$db = App::get_instance()->GetDb();
		$query = 'DELETE FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id = ?';
		$dbr = $db->Execute($query,[$this->_data['id']]);
		if( !$dbr ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());

		$this->_dirty = TRUE;
		audit($this->get_id(),'CMSMS','template-type '.$this->get_name().' Deleted');
		Events::SendEvent('Core', 'DeleteTemplateTypePost', [ get_class($this) => &$this ]);
		unset($this->_data['id']);
	}

	/**
	 * Create a new template of this type
	 *
	 * This method will throw an exception if the template cannot be created.
	 *
	 * @param string $name Optional template name
	 * @return CmsLayoutTemplate object.
	 */
	public function create_new_template($name = '')
	{
		$ob = new CmsLayoutTemplate();
        $ob->set_originator($this->get_originator());
		if( $name ) $ob->set_name($name);
		$ob->set_type( $this );
		$ob->set_content( $this->get_dflt_contents() );
		return $ob;
	}

	/**
	 * Get the default template of this type
	 *
	 * This method will throw an exception if the template cannot be created.
	 *
	 * @see TemplateOperations::get_default_template_by_type()
	 * @return mixed CmsLayoutTemplate object | null
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

		$text = null;
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
			$to = $tn = null;
		}
		if( !$to ) { $to = $this->get_originator(); }
		if( $to == self::CORE ) { $to = lang('core'); }
		if( !$tn ) { $tn = $this->get_name(); }
		return $to.'::'.$tn;
	}

	 /**
	 * Reset the default contents of this template-type back to factory default
	 *
	 * @throws CmsException
	 * @throws CmsDataNotFoundException
	 */
	public function reset_content_to_factory()
	{
		if( !$this->get_dflt_flag() ) {
			throw new CmsException('This template-type does not have default contents');
		}
		$cb = $this->get_content_callback();
		if( !$cb || !is_callable($cb) ) {
			throw new CmsDataNotFoundException('No callback information to reset content');
		}
		$content = $cb($this);
		$this->set_dflt_contents($content);
	}

	/**
	 * Create a CmsLayoutTemplateType object reflecting the given properties array
	 * (typically read from the database)
	 *
	 * @internal
	 * @param array $row
	 * @return CmsLayoutTemplateType
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
			$t = null;
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
			$t = null;
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
			$t = null;
		}
		$row['content_callback'] = $t;
		unset($row['lang_cb'],$row['help_content_cb'],$row['dflt_content_cb']);

		$ob = new self();
		$ob->_data = $row;
		$ob->_dirty = FALSE;

		self::$_cache[$ob->get_id()] = $ob;
		self::$_name_cache[$ob->get_originator().'::'.$ob->get_name()] = $ob->get_id();
		return $ob;
	}

	/**
	 * Load a CmsLayoutTemplateType object from the database.
	 *
	 * This method throws an exception when the requested object cannot be found.
	 *
	 * @throws CmsDataNotFoundException
	 * @param mixed $val An integer template-type id, or a string in the form of Originator::Name
	 * @return CmsLayoutTemplateType
	 */
	public static function load($val)
	{
		$db = App::get_instance()->GetDb();
		$row = null;
		if( is_numeric($val) && (int)$val > 0 ) {
			$val = (int) $val;
			if( isset(self::$_cache[$val]) ) return self::$_cache[$val];

			$query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id = ?';
			$row = $db->GetRow($query,[$val]);
		}
		elseif( strlen($val) > 0 ) {
			if( isset(self::$_name_cache[$val]) ) {
				$id = self::$_name_cache[$val];
				return self::$_cache[$id];
			}

			$tmp = explode('::',$val);
			if( count($tmp) == 2 ) {
				$query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE originator = ? AND name = ?';
				if( $tmp[0] == 'Core' or $tmp[0] == 'core' ) $tmp[0] = self::CORE;
				$row = $db->GetRow($query,[trim($tmp[0]),trim($tmp[1])]);
			}
		}
		if( $row ) return self::_load_from_data($row);
		throw new CmsDataNotFoundException('Could not find template-type identified by '.$val);
	}

	/**
	 * Load all template-types whose originator is as specified.
	 *
	 * @param string $originator The originator name
	 * @return array of CmsLayoutTemplateType objects, or null if no match is found.
	 * @throws CmsInvalidDataException
	 */
	public static function load_all_by_originator($originator)
	{
		if( !$originator ) throw new CmsInvalidDataException('Orignator is empty');

		$db = App::get_instance()->GetDb();
		$query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE originator = ?';
		if( self::$_cache ) $query .= ' AND id NOT IN ('.implode(',',array_keys(self::$_cache)).')';
		$query .= ' ORDER BY IF(modified_date, modified_date, create_date) DESC';
		$list = $db->GetArray($query,[$originator]);
		if( !$list ) return;

		foreach( $list as $row ) {
			self::_load_from_data($row);
		}

		$out = [];
		foreach( self::$_cache as $id => $one ) {
			if( $one->get_originator() == $originator ) $out[] = $one;
		}
		return $out;
	}

	/**
	 * Load all template-types
	 *
	 * @return array Array of CmsLayoutTemplateType objects, or null
	 */
	public static function get_all()
	{
		$db = App::get_instance()->GetDb();
		$query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME;
		if( self::$_cache && count(self::$_cache) ) $query .= ' WHERE id NOT IN ('.implode(',',array_keys(self::$_cache)).')';
		$query .= '	ORDER BY IF(modified_date, modified_date, create_date)';
		$list = $db->GetArray($query);
		if( !$list ) return;

		foreach( $list as $row ) {
			self::_load_from_data($row);
		}

		return array_values(self::$_cache);
	}

	/**
	 * Load all template-types included in the provided list
	 *
	 * @param int[] $list Array of template-type ids
	 * @return array Array of CmsLayoutTemplateType objects, or null
	 */
	public static function load_bulk($list)
	{
		if( !$list ) return;

		$list2 = [];
		foreach( $list as $one ) {
			if( !is_numeric($one) || (int)$one < 1 ) continue;
			$one = (int)$one;
			if( isset(self::$_cache[$one]) ) continue;
			$list2[] = $one;
		}
		if( !$list2 ) return;

		$db = App::get_instance()->GetDb();
		$query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id IN ('.implode(',',$list).')';
		$list = $db->GetArray($query);
		if( !$list ) return;

		$out = [];
		foreach( $list as $row ) {
			$out[] = self::_load_from_data($row);
		}
		return $out;
	}

	/**
	 * Return the names of all loaded template-types
	 *
	 * @return array of the loaded type objects.
	 */
	public static function get_loaded_types()
	{
		if( is_array(self::$_cache) ) return array_keys(self::$_cache);
	}

	/**
	 * Get the assistant object with utility methods for this template-type (if such an assistant object can be instantiated)
	 *
	 * @return TemplateTypeAssistant
	 * @since 2.2
	 */
	public function get_assistant()
	{
		if( !$this->_assistant ) {
			$classnames = [];
			$classnames[] = 'CMSMS\\internal\\'.$this->get_originator().$this->get_name().'_Type_Assistant';
			$classnames[] = 'CMSMS\\Layout\\'.$this->get_originator().$this->get_name().'_Type_Assistant';
			$classnames[] = $this->get_originator().'_'.$this->get_name().'_Type_Assistant';
			foreach( $classnames as $cn ) {
				if( class_exists($cn) ) {
					$tmp = new $cn();
					if( $tmp instanceof TemplateTypeAssistant ) {
						$this->_assistant = $tmp;
						break;
					}
				}
			}
		}

		return $this->_assistant;
	}

	/**
	 * Get a usage string for this template-type.
	 *
	 * @since 2.2
	 * @param string $name The name of the template object.
	 * @return string
	 */
	public function get_usage_string($name)
	{
		$name = trim($name);
		if( !$name ) return;

		$assistant = $this->get_assistant();
		if( !$assistant ) return;

		return $assistant->get_usage_string($name);
	}
} // class

\class_alias(TemplateType::class, 'CmsLayoutTemplateType', false);
