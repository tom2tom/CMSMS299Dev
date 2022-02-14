<?php
/*
Class of methods for processing page-content types.
Copyright (C) 2019-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use CMSMS\AppParams;
use CMSMS\AppState;
use CMSMS\ContentType;
use CMSMS\CoreCapabilities;
use CMSMS\DeprecationNotice;
use CMSMS\SingleItem;
use const CMS_DB_PREFIX;
use const CMS_DEPREC;
use function _ld;
use function cms_join_path;
use function cms_module_path;
use function startswith;

/**
 * TODO details
 */
class ContentTypeOperations
{
	const EDITORMODULE = 'ContentManager';

	/* *
	 * @ignore
	 * Singleton class, to protect $_content_types
	 */
//	private static $_instance = null;

	/**
	 * @ignore
	 */
	private $_content_types;

	/* *
	 * @ignore
	 * @private to prevent direct creation (even by SingleItem class)
	 */
//	private function __construct() {} TODO public iff wanted by SingleItem ?

	/**
	 * @ignore
	 */
	private function __clone() {}

	/**
	 * @ignore
	 */
	public function __get(string $key)
	{
		if( $key == 'content_types' ) {
			return $this->_content_types ?? null; //for PageLoader class to access
		}
	}

	/**
	 * Get the singleton instance of this class.
	 * This method is called over a hundred times during a typical request,
	 * so definitely the class warrants being a singleton.
	 * @deprecated since 2.99 instead use CMSMS\SingleItem::ContentTypeOperations()
	 * @return ContentTypeOperations
	 */
	public static function get_instance() : self
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method','CMSMS\SingleItem::ContentTypeOperations()'));
		return SingleItem::ContentTypeOperations();
	}

	/**
	 * Load static content types
	 *
	 * This method constructs a type-object for each recorded type.
	 *
	 * @access private
	 * @return array of ContentType objects
	 */
	private function _get_static_content_types() : array
	{
		$result = [];
		$db = SingleItem::Db();
		$query = 'SELECT * FROM '.CMS_DB_PREFIX.'content_types';
		$data = $db->getArray($query);
		foreach( $data as $row ) {
			$parms = [
				'type' => $row['name'],
				'locator' => $row['displayclass'],
				'editorlocator' => $row['editclass'],
			];
			if( $row['publicname_key'] ) {
				$r = $row['originator'];
				if( $r == '__CORE__' ) {
//					$r = LangOperations::CMSMS_ADMIN_REALM;
//					allow_admin_lang(TRUE);
					$r = self::EDITORMODULE; //public names are over there
				}
				$parms['friendlyname'] = _ld($r, $row['publicname_key']);
			}

			$result[$row['name']] = new ContentType($parms);
		}
		return $result;
	}

	/**
	 * Load all content types.
	 *
	 * This method constructs a content-type object for each recorded type,
	 * and each type initiated by polled modules.
	 *
	 * @param bool $force since 2.99 whether to force-reload cache data. Default false.
	 * @return array of ContentType objects, or maybe empty
	 */
	public function get_content_types(bool $force = false) : array
	{
		if( !is_array($this->_content_types) ) {
			// get the standard types
			$this->_content_types = $this->_get_static_content_types();
			// get any additional types from relevant modules.
			// such types are registered in the modules' respective constructors,
			// which process eventually shoves them into $this->_content_types.
			$modnames = SingleItem::LoadedMetadata()->get('capable_modules', $force, CoreCapabilities::CONTENT_TYPES);
			if( $modnames ) {
				$modops = SingleItem::ModuleOperations();
				foreach( $modnames as $name ) {
					$mod = $modops->get_module_instance($name);
				}
				$mod = null; // help the garbage-collector
			}
		}

		return $this->_content_types;
	}

	/**
	 * Return a content type corresponding to $name, if any
	 *
	 * @param string $name The content type name
	 * @return mixed ContentType object | null
	 */
	public function get_content_type(string $name)
	{
		$this->get_content_types();
		if( is_array($this->_content_types) ) {
			$name = strtolower($name);
			if( isset($this->_content_types[$name]) && $this->_content_types[$name] instanceof ContentType ) {
				return $this->_content_types[$name];
			}
		}
	}

	/**
	 * Load a specific content type
	 *
	 * @since 1.9
	 * @param mixed $type string type name or an instance of ContentType
	 * @param bool since 2.99 optional flag whether to create a IContentEditor-compatible class
	 * object. Default false (hence a shortform object)
	 * @return mixed ContentType object | null
	 */
	public function LoadContentType($type, bool $editable=false)
	{
		if( $type instanceof ContentType ) {
			$type = $type->type;
		}

		$ctype = $this->get_content_type($type);
		if( is_object($ctype) ) {
			if( $editable && empty($ctype->editorclass) ) {
				$editable = false; //revert to using displayable form, hopefully also editable
			}
			if( $editable ) {
				if( !class_exists($ctype->editorclass) && is_file($ctype->editorfilename) ) {
					require_once $ctype->editorfilename;
				}
			}
			elseif( !class_exists($ctype->class) && is_file($ctype->filename) ) {
				require_once $ctype->filename;
			}
		}

		return $ctype;
	}

	/**
	 * Return known content types.
	 *
	 * @param bool $byclassname optionally return keys as class names instead of type names. Default false.
	 * @param bool $allowed optionally filter the list of content types by the
	 *  'disallowed_contenttypes' site preference. Default false.
	 * @param bool $system return only CMSMS-internal content types. Default false.
	 * @param string $realm optional lang-strings realm. Default 'admin'.
	 * @return array content type(s) registered in the system | empty
	 *  Array values are respective 'public' names (from the class FriendlyName() method)
	 *  if any, otherwise the raw type-name.
	 */
	public function ListContentTypes(bool $byclassname = FALSE, bool $allowed = FALSE, bool $system = FALSE, string $realm = 'admin')
	{
		$tmp = AppParams::get('disallowed_contenttypes');
		if( $tmp ) {
			$disallowed_a = explode(',',$tmp);
		}
		else {
			$disallowed_a = [];
		}

		$types = $this->get_content_types();
		if( $types ) {
			$result = [];
			foreach( $types as $obj ) {
				if( $allowed && $disallowed_a && in_array($obj->type,$disallowed_a) ) {
					continue;
				}
				if( $system && !startswith($obj->class,'CMSMS\contenttypes\\') ) {
					continue;
				}

				if( empty($obj->friendlyname) ) {
					if( !(empty($obj->friendlyname_key) || !AppState::test(AppState::ADMIN_PAGE)) ) {
						$obj->friendlyname = _ld($realm,$obj->friendlyname_key);
					}
					else {
						$obj->friendlyname = ucfirst($obj->type);
					}
				}

				if( $byclassname ) {
					$result[$obj->class] = $obj->friendlyname;
				}
				else {
					$result[$obj->type] = $obj->friendlyname;
				}
			}
			return $result;
		}
		return [];
	}

	/**
	 * Cache an intra-request content type
	 *
	 * @param ContentType Reference to placeholder object
	 * @return bool
	 */
	public function AddContentType(ContentType $obj) : bool
	{
		$this->get_content_types();
		if( isset($this->_content_types[$obj->type]) ) return FALSE;

		$this->_content_types[$obj->type] = $obj;
		return TRUE;
	}

	/**
	 * Record a content type in the database
	 *
	 * @since 2.99
	 * @todo
	 */
	public function AddStaticContentType()
	{
		$Y = $TODO;
		$db = SingleItem::Db();
		//TODO UPSERT
		$query = 'INSERT INTO '.CMS_DB_PREFIX.'content_types (originator,name,publicname_key,displayclass,editclass) VALUES (?,?,?,?,?)';
		$db->execute($query,[$Y->module,$Y->type,$Y->friendlyname,$Y->class,$Y->editorclass]);
	}

	/**
	 * Remove a content type from the database
	 *
	 * @since 2.99
	 */
	public function DelStaticContentType()
	{
		$db = SingleItem::Db();
		$query = 'DELETE FROM '.CMS_DB_PREFIX.'content_types WHERE ';
		$query .= 'TODO';
		$db->execute($query);
	}

	/**
	 * Reset the database-recorded content types
	 * @since 2.99
	 *
	 * @param bool $force whether to force-reload cache data. Default false.
	 */
	public function RebuildStaticContentTypes(bool $force = false)
	{
		$db = SingleItem::Db();
		$query = 'TRUNCATE '.CMS_DB_PREFIX.'content_types';
		$db->execute($query);

		$patn = __DIR__.DIRECTORY_SEPARATOR.'contenttypes'.DIRECTORY_SEPARATOR.'class.*.php';
		$files = glob($patn, GLOB_NOSORT);
		if( is_array($files) ) {
			$query = 'INSERT INTO '.CMS_DB_PREFIX.'content_types (originator,name,publicname_key,displayclass,editclass) VALUES (?,?,?,?,?)';
			$fp = cms_module_path(self::EDITORMODULE);
			if( $fp ) {
				$fp = cms_join_path(dirname($fp), 'lib', 'contenttypes', ''); //trailing separator
			}

			foreach( $files as $one ) {
				$class = substr(basename($one), 6, -4);
				$type = strtolower($class);
				if( $type == 'contentbase' ) continue;

				$args = [self::EDITORMODULE, $type];
				$args[] = 'contenttype_'.$type; // editor-module lang key
				$args[] = 'CMSMS\contenttypes\\'.$class;

				if( $fp ) {
					$path = $fp.basename($one);
					if( is_file($path) ) {
						$args[] = self::EDITORMODULE.'\contenttypes\\'.$class;
					}
				}
				if( count($args) == 4) {
					$args[] = 'CMSMS\contenttypes\\'.$class;
				}
				$db->execute($query,$args);
			}
		}

		$modnames = SingleItem::LoadedMetadata()->get('methodic_modules', $force, 'CreateStaticContentTypes');
		if( $modnames ) {
			$modops = SingleItem::ModuleOperations();
			foreach( $modnames as $name ) {
				$mod = $modops->get_module_instance($name);
				if( $mod ) {
					$mod->CreateStaticContentTypes();
					$mod = null; // help the garbage-collector
				}
			}
		}
	}
}
