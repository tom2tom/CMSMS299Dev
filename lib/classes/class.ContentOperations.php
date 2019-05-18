<?php
#Class of methods for processing the pages-tree, and content objects generally
#Copyright (C) 2004-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.
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

use cms_cache_handler;
use cms_content_tree;
use cms_siteprefs;
use cms_utils;
use CmsApp;
use CmsCoreCapabilities;
use CMSMS\AdminUtils;
use CMSMS\ContentTypePlaceHolder;
use CMSMS\contenttypes\ContentBase;
use CMSMS\internal\global_cache;
use CMSMS\ModuleOperations;
use CMSMS\UserOperations;
use Exception;
use const CMS_DB_PREFIX;
use function check_permission;
use function cms_join_path;
use function cms_module_path;
use function debug_buffer;
use function lang;
use function munge_string_to_url;

/**
 * A singleton class for working with page properties.
 * This class includes generic content-related methods, plus some tailored
 * for processing the content tree. Care is needed when preparing content
 * for runtime page-display or page-listing/modification by the ContentManager module.
 * The page/node data are as minimal as possible, consistent with tree operations
 * & related menu generation.
 *
 * @final
 * @since 0.8
 * @package CMS
 * @license GPL
 */
final class ContentOperations
{
	const EDITORMODULE = 'CMSContentManager';

	/**
	 * @ignore
	 */
	private static $_instance = null;

	/* *
	 * @ignore
	 */
//	private $_quickfind;

	/**
	 * @ignore
	 */
	private $_content_types;

	/**
	 * @ignore
	 */
	private $_authorpages;

	/**
	 * @ignore
	 */
	private $_ownedpages;

	/**
	 * @ignore
	 */
	private function __construct() {}

	/**
	 * @ignore
	 */
	private function __clone() {}

	/**
	 * Get the singleton instance of this class.
	 * This method is called over a hundred times during a typical request,
	 * so warrants being a singleton.
	 * @return ContentOperations
	 */
	public static function get_instance() : self
	{
		if( !self::$_instance ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Return a content object for the currently requested page.
	 *
	 * @since 1.9
	 * @return mixed A content object derived from ContentBase, or null
	 */
	public function getContentObject()
	{
		return CmsApp::get_instance()->get_content_object();
	}

	/**
	 * Given an array of content_type and serialized_content, construct a
	 * content object. Load the content type if that hasn't already been done.
	 *
	 * Expects an associative array with 1 or 2 members (at least):
	 *   content_type: string Optional content type name, default 'content'
	 *   serialized_content: string Serialized form data
	 *
	 * @see ContentBase::ListContentTypes()
	 * @param  array $data
	 * @return mixed A content object derived from ContentBase, or false
	 */
	public function LoadContentFromSerializedData(array &$data)
	{
		if( !isset($data['serialized_content']) ) return FALSE;

		$contenttype = $data['content_type'] ?? 'content';
		$this->CreateNewContent($contenttype);

		$contentobj = unserialize($data['serialized_content']);
		return $contentobj;
	}

	/**
	 * Creates a new, empty content object of the given type.
	 *
	 * If the content-type is registered with the system, and the class does not
	 * exist, the appropriate filename will be included and then, if possible,
	 * a new object of the designated type will be instantiated.
	 *
	 * @param mixed $type string type name or an instance of ContentTypePlaceHolder
	 * @param array since 2.3 initial object properties (replaces subsequent LoadFromData())
	 * @param bool since 2.3 optional flag whether to create a ContentEditor-class
	 * object. Default false (hence a shortform object)
	 * @return mixed  object derived from ContentBase | null
	 */
	public function CreateNewContent($type, array $params=[], bool $editable=false)
	{
		if( $type instanceof ContentTypePlaceHolder ) {
			$type = $type->type;
		}
		$ctph = $this->LoadContentType($type, $editable);
		if( is_object($ctph) ) {
			if( $editable && empty($ctph->editorclass) ) {
				$editable = false; //revert to using displayable form, hopefully also editable
			}
			if( $editable ) {
				if( class_exists($ctph->editorclass) ) {
					return new $ctph->editorclass($params);
				}
			}
			elseif ( class_exists($ctph->class) ) {
				return new $ctph->class($params);
			}
		}
		return null;
	}

	/**
	 * Given a content id, load and return the corresponding content object.
	 * It is loaded from the content cache if possible, or else added to that
	 * cache after loading.
	 *
	 * @param mixed $id int | null The id of the content object to load. If < 1, the default id will be used.
	 * @param bool $loadprops Optional flag whether to load the properties of that content object. Defaults to false.
	 * @return mixed The loaded content object. If nothing is found, returns null.
	 */
	public function LoadContentFromId($id, bool $loadprops=false)
	{
		$id = (int)$id;
		if( $id < 1 ) { $id = $this->GetDefaultContent(); }

		$cache = cms_cache_handler::get_instance();
		$contentobj = $cache->get($id,'tree_pages'); //TODO relevant object type
		if( !$contentobj ) {
			$db = CmsApp::get_instance()->GetDb();
			$query = 'SELECT * FROM '.CMS_DB_PREFIX.'content WHERE content_id=?';
			$row = $db->GetRow($query, [$id]);
			if( $row ) {
				$ctph = $this->_get_content_type($row['type']);
				if( $ctph ) {
					unset($row['metadata']);
					$classname = $ctph->class;
					$contentobj = new $classname($row);
					 // legacy support deprecated since 2.3
					if( method_exists( $contentobj,'LoadFromData') ) { $contentobj->LoadFromData($row); }
					$cache->set($id,$contentobj,'tree_pages');
				}
				else {
					throw new Exception('Unrecognized class '.$row['type'].' used in '.__METHOD__);
				}
			}
//		} else {
			//TODO trigger module-loading etc, so that page tags get registered
		}

		if( $loadprops ) {
			// the tag which intiated the menu specified 'deep' pre-loading
			// doesn't help anything, ignored
		}

		return $contentobj;
	}

	/**
	 * Load and return the content object corresponding to the given identifier (alias|id).
	 *
	 * @param mixed $alias null|bool|int|string The identifier of the content object to load
	 * @param bool $only_active If true, only return the object if it's active flag is true. Defaults to false.
	 * @return mixed The matched ContentBase object, or null.
	 */
	public function LoadContentFromAlias($alias, bool $only_active = false)  //TODO ensure relevant content-object
	{
		$contentobj = cms_cache_handler::get_instance()->get($alias,'tree_pages');
		if( $contentobj === null ) {
			$db = CmsApp::get_instance()->GetDb();
			$query = 'SELECT content_id FROM '.CMS_DB_PREFIX.'content WHERE (content_id=? OR content_alias=?)';
			if( $only_active ) { $query .= ' AND active=1'; }
			$id = $db->GetOne($query,[ $alias,$alias ]);
			if( $id ) {
				return $this->LoadContentFromId($id);
			}
//		} else {
			//TODO trigger module-loading etc, so page tags get registered
		}

		if( $contentobj && (!$only_active || $contentobj->active) ) {
			return $contentobj;
		}
	}

	/**
	 * Returns the id of the content marked as default.
	 *
	 * @return int The id of the default content page, or 0 if none is recorded
	 */
	public function GetDefaultContent() : int
	{
		return (int)global_cache::get('default_content');
	}

	/**
	 * Load standard CMSMS content types
	 *
	 * This method polls the contenttypes directory and constructs a placeholder
	 * object for each discovered type. Types are not distinguished other than by name.
	 * Corresponding ContentEditor classes are recorded if available.
	 *
	 * @since 1.9
	 * @access private
	 * @return array of ContentTypePlaceHolder objects
	 */
	private function _get_std_content_types() : array
	{
		$result = [];
		$patn = __DIR__.DIRECTORY_SEPARATOR.'contenttypes'.DIRECTORY_SEPARATOR.'class.*.php';
		$files = glob($patn);
		if( is_array($files) ) {
			$fp = cms_module_path(self::EDITORMODULE);
			if( $fp ) {
				$fp = cms_join_path(dirname($fp), 'lib', 'contenttypes', ''); //trailing separator
			}
			foreach( $files as $one ) {
				$class = substr(basename($one,'.php'), 6);
				$type = strtolower($class);
				if( $type == 'contentbase' ) continue;

				$obj = new ContentTypePlaceHolder();
				$obj->class = 'CMSMS\\contenttypes\\'.$class;
				$obj->type = strtolower($class);
				$obj->filename = $one;
				$obj->friendlyname_key = 'contenttype_'.$obj->type;
				$obj->friendlyname = '';

				if( $fp ) {
					$path = $fp.basename($one);
				}
				else {
					$path = '';
				}
				if( $path && is_file($path) ) {
					$obj->editorclass = self::EDITORMODULE.'\\contenttypes\\'.$class;
					$obj->editorfilename = $path;
				}
				else {
					$obj->editorclass = $obj->class;
					$obj->editorfilename = $obj->filename;
				}

				$result[$type] = $obj;
			}
		}
		return $result;
	}

	/**
	 * @ignore
	 */
	private function _get_content_types() : array
	{
		if( !is_array($this->_content_types) ) {
			// get the standard types
			$this->_content_types = $this->_get_std_content_types();
			// get any additional types from relevant modules.
			// such types are registered in the modules' respective constructors,
			// which process eventually shoves them into $this->_content_types.
			$module_list = (new ModuleOperations())->get_modules_with_capability(CmsCoreCapabilities::CONTENT_TYPES);
			if( $module_list ) {
				foreach( $module_list as $module_name ) {
					cms_utils::get_module($module_name);
				}
			}
		}

		return $this->_content_types;
	}

	/**
	 * Returns a content type placeholder corresponding to $name, if any
	 *
	 * @since 1.9
	 * @access private
	 * @internal
	 * @param string $name The content type name
	 * @return mixed ContentTypePlaceHolder object or null
	 */
	private function _get_content_type(string $name)
	{
		$this->_get_content_types();
		if( is_array($this->_content_types) ) {
			$name = strtolower($name);
			if( isset($this->_content_types[$name]) && $this->_content_types[$name] instanceof ContentTypePlaceHolder ) {
				return $this->_content_types[$name];
			}
		}
	}

	/**
	 * Register a new content type
	 *
	 * @since 1.9
	 * @param ContentTypePlaceHolder Reference to placeholder object
	 * @return bool
	 */
	public function register_content_type(ContentTypePlaceHolder $obj) : bool
	{
		$this->_get_content_types();
		if( isset($this->_content_types[$obj->type]) ) return FALSE;

		$this->_content_types[$obj->type] = $obj;
		return TRUE;
	}

	/**
	 * Load a specific content type placeholder (settings)
	 *
	 * @since 1.9
	 * @param mixed $type string type name or an instance of ContentTypePlaceHolder
	 * @param bool since 2.3 optional flag whether to create a ContentEditor-class
	 * object. Default false (hence a shortform object)
	 * @return mixed ContentTypePlaceHolder object or null
	 */
	public function LoadContentType($type, bool $editable=false)
	{
		if( $type instanceof ContentTypePlaceHolder ) {
			$type = $type->type;
		}

		$ctph = $this->_get_content_type($type);
		if( is_object($ctph) ) {
			if( $editable && empty($ctph->editorclass) ) {
				$editable = false; //revert to using displayable form, hopefully also editable
			}
			if( $editable ) {
				if( !class_exists($ctph->editorclass) && is_file($ctph->editorfilename) ) {
					require_once $ctph->editorfilename;
				}
			}
			elseif( !class_exists( $ctph->class ) && is_file( $ctph->filename ) ) {
				require_once $ctph->filename;
			}
		}

		return $ctph;
	}

	/**
	 * Returns a hash of known content types (classes that extend ContentBase).
	 * Values are respective 'public' names (from the class FriendlyName() method)
	 * if any, otherwise the raw type-name.
	 *
	 * @param bool $byclassname optionally return keys as class names instead of type names. Default false.
	 * @param bool $allowed optionally filter the list of content types by the
	 *  'disallowed_contenttypes' site preference. Default false.
	 * @param bool $system return only CMSMS-internal content types. Default false.
	 * @param string $realm optional lang-strings realm. Default 'admin'.
	 * @return mixed array List of content types registered in the system | null
	 */
	public function ListContentTypes(bool $byclassname = FALSE, bool $allowed = FALSE, bool $system = FALSE, string $realm = 'admin')
	{
		$tmp = cms_siteprefs::get('disallowed_contenttypes');
		if( $tmp ) { $disallowed_a = explode(',',$tmp); }
		else { $disallowed_a = []; }

		$types = $this->_get_content_types();
		if( $types ) {
			$result = [];
			foreach( $types as $obj ) {
				if( $allowed && $disallowed_a && in_array($obj->type,$disallowed_a) ) {
					continue;
				}
				if( $system && !startswith($obj->class,'CMSMS\\contenttypes\\') ) {
					continue;
				}

				if( empty($obj->friendlyname) ) {
					global $CMS_ADMIN_PAGE;
					if( !(empty($obj->friendlyname_key) || empty($CMS_ADMIN_PAGE)) ) {
						$obj->friendlyname = LangOperations::lang_from_realm($realm,$obj->friendlyname_key);
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
	}

	/**
	 * Updates the hierarchy position of one item
	 *
	 * @internal
	 * @ignore
	 * @param integer $content_id The numeric id of the page to update
	 * @param array $hash A hash of some properties of all content objects
	 * @return mixed array|null
	 */
	private function _set_hierarchy_position(int $content_id,array $hash)
	{
		$row = $hash[$content_id];
		$saved_row = $row;
		$hier = $idhier = $pathhier = '';
		$current_parent_id = $content_id;

		while( $current_parent_id > 0 ) {
			$item_order = max($row['item_order'],1);
			$hier = str_pad($item_order, 5, '0', STR_PAD_LEFT) . '.' . $hier;
			$idhier = $current_parent_id . '.' . $idhier;
			$pathhier = $row['alias'] . '/' . $pathhier;
			$current_parent_id = $row['parent_id'];
			if( $current_parent_id < 1 ) break;
			$row = $hash[$current_parent_id];
		}

		if (strlen($hier) > 0) $hier = substr($hier, 0, strlen($hier) - 1);
		if (strlen($idhier) > 0) $idhier = substr($idhier, 0, strlen($idhier) - 1);
		if (strlen($pathhier) > 0) $pathhier = substr($pathhier, 0, strlen($pathhier) - 1);

		// if we actually did something, return the row.
		static $_cnt;
		$a = ($hier == $saved_row['hierarchy']);
		$b = ($idhier == $saved_row['id_hierarchy']);
		$c = ($pathhier == $saved_row['hierarchy_path']);
		if( !$a || !$b || !$c ) {
			$_cnt++;
			$saved_row['hierarchy'] = $hier;
			$saved_row['id_hierarchy'] = $idhier;
			$saved_row['hierarchy_path'] = $pathhier;
			return $saved_row;
		}
	}

	/**
	 * Updates the hierarchy position of all content items.
	 * This is an expensive operation on the database, but must be called each
	 * time one or more content pages are updated if positions have changed in
	 * the page structure.
	 */
	public function SetAllHierarchyPositions()
	{
		// load some data about all pages into memory... and convert into a hash.
		$db = CmsApp::get_instance()->GetDb();
		$sql = 'SELECT content_id, parent_id, item_order, content_alias AS alias, hierarchy, id_hierarchy, hierarchy_path FROM '.CMS_DB_PREFIX.'content ORDER BY hierarchy';
/*
		$list = $db->GetArray($sql);
		if( !count($list) ) {
			// nothing to do, get outa here.
			return;
		}
		$hash = [];
		foreach( $list as $row ) {
			$hash[$row['content_id']] = $row;
		}
		unset($list);
*/
		$hash = $db->GetAssoc($sql);
		if( !$hash ) {
			return;
		}
		// would be nice to use a transaction here.
//		static $_n;
		$stmt = $db->Prepare('UPDATE '.CMS_DB_PREFIX.'content SET hierarchy = ?, id_hierarchy = ?, hierarchy_path = ? WHERE content_id = ?');
		foreach( $hash as $content_id => $row ) {
			$changed = $this->_set_hierarchy_position($content_id,$hash);
			if( is_array($changed) ) {
				$db->Execute($stmt, [$changed['hierarchy'], $changed['id_hierarchy'], $changed['hierarchy_path'], $content_id]);
			}
		}
		$stmt->close();
		$this->SetContentModified();
	}

	/**
	 * Get the date of last content modification
	 *
	 * @since 2.0
	 * @return unix timestamp representing the last time a content page was modified.
	 */
	public function GetLastContentModification()
	{
		return global_cache::get('latest_content_modification');
	}

	/**
	 * Set the last modified date of content so that on the next request the content cache will be loaded from the database
	 *
	 * @internal
	 * @access private
	 */
	public function SetContentModified()
	{
		global_cache::clear('latest_content_modification');
		global_cache::clear('default_content');
		global_cache::clear('content_flatlist');
		global_cache::clear('content_tree');
		global_cache::clear('content_quicklist');
		cms_cache_handler::get_instance()->clear('tree_pages');
		//etc for CM list
	}

	/**
	 * Loads a set of content objects into the cached tree.
	 *
	 * @param bool $loadcontent UNUSED If false, only create the nodes in the tree, don't load the content objects
	 * @return cms_content_tree The cached tree of content
	 * @deprecated
	 */
	public function GetAllContentAsHierarchy(bool $loadcontent = false)
	{
		return CmsApp::get_instance()->GetHierarchyManager();
	}

	/**
	 * Loads all content in the database into memory
	 * Use with caution this can chew up a lot of memory on larger sites.
	 *
	 * @param bool $loadprops Load extended content properties or just the page structure and basic properties
	 * @param bool $inactive  Load inactive pages as well
	 * @param bool $showinmenu Load pages marked as show in menu
	 */
	public function LoadAllContent(bool $loadprops = FALSE,bool $inactive = FALSE,bool $showinmenu = FALSE)
	{
		static $_loaded = 0;
		if( $_loaded == 1 ) {
			return;
		}
		$_loaded = 1;
		$cache = cms_cache_handler::get_instance();

		$expr = [];
		$loaded_ids = $cache->getindex('tree_pages');
		if( $loaded_ids ) {
			$expr[] = 'content_id NOT IN ('.implode(',',$loaded_ids).')';
		}
		if( !$inactive ) {
			$expr[] = 'active = 1';
		}
		if( $showinmenu ) {
			$expr[] = 'show_in_menu = 1';
		}
		$query = 'SELECT * FROM '.CMS_DB_PREFIX.'content FORCE INDEX (idx_content_by_idhier)';
		if( $expr ) {
			$query .= ' WHERE '.implode(' AND ',$expr);
		}

		$db = CmsApp::get_instance()->GetDb();
		$dbr = $db->Execute($query);

		if( $loadprops ) {
			$child_ids = [];
			while( !$dbr->EOF() ) {
				$child_ids[] = $dbr->fields['content_id'];
				$dbr->MoveNext();
			}
			$dbr->MoveFirst();

			$tmp = null;
			if( $child_ids ) {
				// get all the properties for the child_ids
				$query = 'SELECT * FROM '.CMS_DB_PREFIX.'content_props WHERE content_id IN ('.implode(',',$child_ids).') ORDER BY content_id';
				$tmp = $db->GetArray($query);
			}

			// re-organize the tmp data into a hash of arrays of properties for each content id.
			if( $tmp ) {
				$contentprops = [];
				for( $i = 0, $n = count($tmp); $i < $n; $i++ ) {
					$content_id = $tmp[$i]['content_id'];
					if( in_array($content_id,$child_ids) ) {
						if( !isset($contentprops[$content_id]) ) $contentprops[$content_id] = [];
						$contentprops[$content_id][] = $tmp[$i];
					}
				}
				unset($tmp);
			}
		}

		$valids = array_keys($this->_get_content_types()); // no translation needed

		// build the content objects
		while( !$dbr->EOF() ) {
			$row = $dbr->fields;

			if (!in_array($row['type'], $valids)) continue;

			$id = (int)$row['content_id'];
			$contentobj = $cache->get($id,'tree_pages');
			if( !$contentobj ) {
				$contentobj = $this->CreateNewContent($row['type'], $row);
				if( $contentobj ) {
					// legacy support
					if( method_exists($contentobj, 'LoadFromData') ) {
						$contentobj->LoadFromData($row, false);
					}
					if( $loadprops && $contentprops && isset($contentprops[$id]) ) {
						// load the properties from local cache.
						$props = $contentprops[$id];
						foreach( $props as $oneprop ) {
							$contentobj->SetPropertyValueNoLoad($oneprop['prop_name'],$oneprop['content']);
						}
					}
					$cache->set($id,$contentobj,'tree_pages');
				}
			}
			unset($contentobj);
			$dbr->MoveNext();
		}
		$contentobj = null; //force-garbage
		$dbr->Close();
	}

	/**
	 * Loads active children into a given tree node
	 *
	 * @param int $id The parent of the content objects to load into the tree
	 * @param bool $loadprops If true, load the properties of all loaded content objects
	 * @param bool $all If true, load all content objects, even inactive ones.
	 * @param array   $explicit_ids (optional) array of explicit content ids to load
	 * @author Ted Kulp
	 */
	public function LoadChildren(int $id = null, bool $loadprops = false, bool $all = false, array $explicit_ids = [] )
	{
		$db = CmsApp::get_instance()->GetDb();
		$cache = cms_cache_handler::get_instance();

		if( $explicit_ids ) {
			$loaded_ids = $cache->getindex('tree_pages');
			if( $loaded_ids ) {
				$explicit_ids = array_diff($explicit_ids,$loaded_ids);
			}
		}
		if( $explicit_ids ) {
			$expr = 'content_id IN ('.implode(',',$explicit_ids).')';
			if( !$all ) $expr .= ' AND active = 1';

			// note, this is MySQL specific... why is index hint needed?
			$query = 'SELECT * FROM '.CMS_DB_PREFIX.'content FORCE INDEX (idx_content_by_idhier) WHERE '.$expr.' ORDER BY hierarchy';
			$contentrows = $db->GetArray($query);
		}
		elseif( isset($loaded_ids) ) {
			return;
		}
		else {
			// get the content rows
			if( !$id ) {
				$id = -1;
			}
			if( $all ) {
				$query = 'SELECT * FROM '.CMS_DB_PREFIX.'content WHERE parent_id = ? ORDER BY hierarchy';
			}
			else {
				$query = 'SELECT * FROM '.CMS_DB_PREFIX.'content WHERE parent_id = ? AND active = 1 ORDER BY hierarchy';
			}
			$contentrows = $db->GetArray($query, [$id]);
		}

		// get the content ids from the returned data
		$contentprops = null;
		if( $loadprops ) {
			$child_ids = [];
			for( $i = 0, $n = count($contentrows); $i < $n; $i++ ) {
				$child_ids[] = $contentrows[$i]['content_id'];
			}

			if( $child_ids ) {
				// get all the properties for all the children
				$query = 'SELECT * FROM '.CMS_DB_PREFIX.'content_props WHERE content_id IN ('.implode(',',$child_ids).') ORDER BY content_id';
				$tmp = $db->GetArray($query);
			}
			else {
				$tmp = null;
			}

			// re-organize the tmp data into a hash of arrays of properties for each content id.
			if( $tmp ) {
				$contentprops = [];
				for( $i = 0, $n = count($tmp); $i < $n; $i++ ) {
					$content_id = $tmp[$i]['content_id'];
					if( in_array($content_id,$child_ids) ) {
						if( !isset($contentprops[$content_id]) ) $contentprops[$content_id] = [];
						$contentprops[$content_id][] = $tmp[$i];
					}
				}
				$tmp = null;
			}
		}

		$valids = array_keys($this->_get_content_types()); // no translation needed

		// build the content objects
		for( $i = 0, $n = count($contentrows); $i < $n; $i++ ) {
			$row = &$contentrows[$i];

			if (!in_array($row['type'], $valids)) {
				continue;
			}

			$id = (int)$row['content_id'];
			$contentobj = $cache->get($id,'tree_pages');
			if( !$contentobj ) {
				unset($row['metadata']);
				$contentobj = $this->CreateNewContent($row['type'], $row);
				if ($contentobj) {
					// legacy support
					if( method_exists($contentobj, 'LoadFromData') ) {
						$contentobj->LoadFromData($row, false);
					}
					if( $loadprops && $contentprops && isset($contentprops[$id]) ) {
						// load the properties from local cache
						foreach( $contentprops[$id] as $oneprop ) {
							$contentobj->SetPropertyValueNoLoad($oneprop['prop_name'],$oneprop['content']);
						}
						unset($contentprops[$id]);
					}
					$cache->set($id,$contentobj,'tree_pages');
				}
			}
			unset($contentobj);
		}
		//cleanup
		unset($row);
		$contentrows = null; //force-garbage
		$contentprops = null;
	}

	/**
	 * Sets the default content to the given id
	 *
	 * @param int $id The id to set as default
	 * @author Ted Kulp
	 */
	public function SetDefaultContent(int $id)
	{
		$db = CmsApp::get_instance()->GetDb();

		$sql = 'UPDATE '.CMS_DB_PREFIX.'content SET default_content=0 WHERE default_content=1';
		$db->Execute($sql);
		$contentobj = $this->LoadContentFromId($id); //TODO ensure relevant content-object
		$contentobj->SetDefaultContent(true);
		$contentobj->Save();
	}

	/**
	 * Returns an array of all content objects in the system, active or not.
	 *
	 * Caution:  it is entirely possible that this method (and other similar methods of loading content) will result in a memory outage
	 * if there are a lot of content objects AND/OR large amounts of content properties.  Use with caution.
	 *
	 * @param bool $loadprops optional parameter for LoadAllContent(). Default true
	 * @return array The array of content objects
	 */
	public function &GetAllContent(bool $loadprops=true)
	{
		debug_buffer('get all content...');
		$hm = CmsApp::get_instance()->GetHierarchyManager();
		$list = $hm->getFlatList();

		$this->LoadAllContent($loadprops);
		$output = [];
		foreach( $list as &$node ) {
			$tmp = $node->getContent(false,true,true);
			if( is_object($tmp) ) $output[] = $tmp;
			unset($node); //free space a bit
		}

		$list = null;
		debug_buffer('end get all content...');
		return $output;
	}

	/**
	 * Create a hierarchical ordered ajax-populated dropdown of some or all the pages in the system.
	 *
	 * @deprecated since 2.3 instead use CMSMS\AdminUtils::CreateHierarchyDropdown()
	 * @return string
	 */
	public function CreateHierarchyDropdown(
		$current = 0,
		$value = 0,
		$name = 'parent_id',
		$allow_current = false,
		$use_perms = false,
		$ignore_current = false, // unused since 2.0
		$allow_all = false,
		$for_child = false)
	{
		return AdminUtils::CreateHierarchyDropdown(
			$current,$value,$name,$allow_current,$use_perms,$allow_all,$for_child
		);
	}

	/**
	 * Gets the content id of the page marked as default
	 *
	 * @return int The id of the default page. false if not found.
	 */
	public function GetDefaultPageID()
	{
		return $this->GetDefaultContent();
	}

	/**
	 * Returns the content id given a valid content alias.
	 *
	 * @param string $alias The alias to query
	 * @return int The resulting id.  null if not found.
	 */
	public function GetPageIDFromAlias( string $alias )
	{
		$hm = CmsApp::get_instance()->GetHierarchyManager();
		$node = $hm->find_by_tag('alias',$alias);
		if( $node ) return $node->get_tag('id');
	}

	/**
	 * Returns the content id given a valid hierarchical position.
	 *
	 * @param string $position The position to query
	 * @return int The resulting id.  false if not found.
	 */
	public function GetPageIDFromHierarchy( string $position )
	{
		$db = CmsApp::get_instance()->GetDb();

		$query = 'SELECT content_id FROM '.CMS_DB_PREFIX.'content WHERE hierarchy = ?';
		$content_id = $db->GetOne($query, [$this->CreateUnfriendlyHierarchyPosition($position)]);

		if( $content_id ) return $content_id;
		return false;
	}

	/**
	 * Returns the content alias given a valid content id.
	 *
	 * @param int $content_id The content id to query
	 * @return mixed string The resulting content alias.  null if not found.
	 */
	public function GetPageAliasFromID( int $content_id )
	{
		$hm = CmsApp::get_instance()->GetHierarchyManager();
		$node = $hm->quickfind_node_by_id($content_id);
		if( $node ) return $node->get_tag('alias');
	}

	/**
	 * Check if a content alias is used
	 *
	 * @param string $alias The alias to check
	 * @param int $content_id The id of hte current page, if any
	 * @return bool
	 * @since 2.2.2
	 */
	public function CheckAliasUsed(string $alias,int $content_id = -1)
	{
		$alias = trim($alias);
		$content_id = (int) $content_id;

		$params = [ $alias ];
		$query = 'SELECT content_id FROM '.CMS_DB_PREFIX.'content WHERE content_alias = ?';
		if ($content_id > 0) {
			$query .= ' AND content_id != ?';
			$params[] = $content_id;
		}
		$db = CmsApp::get_instance()->GetDb();
		$out = (int) $db->GetOne($query, $params);
		return $out > 0;
	}

	/**
	 * Check if a potential alias is valid.
	 *
	 * @param string $alias The alias to check
	 * @return bool
	 * @since 2.2.2
	 */
	public function CheckAliasValid(string $alias)
	{
		if( ((int)$alias > 0 || (float)$alias > 0.00001) && is_numeric($alias) ) return FALSE;
		$tmp = munge_string_to_url($alias,TRUE);
		return $tmp == mb_strtolower($alias);
	}

	/**
	 * Checks to see if a content alias is valid and not in use.
	 *
	 * @param string $alias The content alias to check
	 * @param int $content_id The id of the current page, for used alias checks on existing pages
	 * @return string The error, if any.  If there is no error, returns FALSE.
	 */
	public function CheckAliasError(string $alias, int $content_id = -1)
	{
		if( !$this->CheckAliasValid($alias) ) return lang('invalidalias2');
		if ($this->CheckAliasUsed($alias,$content_id)) return lang('aliasalreadyused');
		return FALSE;
	}

	/**
	 * Converts a friendly hierarchy (1.1.1) to an unfriendly hierarchy (00001.00001.00001) for
	 * use in the database.
	 *
	 * @param string $position The hierarchy position to convert
	 * @return string The unfriendly version of the hierarchy string
	 */
	public function CreateFriendlyHierarchyPosition(string $position)
	{
		#Change padded numbers back into user-friendly values
		$tmp = '';
		$levels = explode('.',$position);

		foreach ($levels as $onelevel) {
			$tmp .= ltrim($onelevel, '0') . '.';
		}
		$tmp = rtrim($tmp, '.');
		return $tmp;
	}

	/**
	 * Converts an unfriendly hierarchy (00001.00001.00001) to a friendly hierarchy (1.1.1) for
	 * use in the database.
	 *
	 * @param string $position The hierarchy position to convert
	 * @return string The friendly version of the hierarchy string
	 */
	public function CreateUnfriendlyHierarchyPosition(string $position)
	{
		#Change user-friendly values into padded numbers
		$tmp = '';
		$levels = explode('.',$position);

		foreach ($levels as $onelevel) {
			$tmp .= str_pad($onelevel, 5, '0', STR_PAD_LEFT) . '.';
		}
		$tmp = rtrim($tmp, '.');
		return $tmp;
	}

	/**
	 * Check if the supplied page id is a parent of the specified base page (or the current page)
	 *
	 * @since 2.0
	 * @author Robert Campbell <calguy1000@cmsmadesimple.org>
	 * @param int $test_id Page ID to test
	 * @param int $base_id (optional) Page ID to act as the base page.  The current page is used if not specified.
	 * @return bool
	 */
	public function CheckParentage(int $test_id,int $base_id = null)
	{
		$gCms = CmsApp::get_instance();
		if( !$base_id ) $base_id = $gCms->get_content_id();
		$base_id = (int)$base_id;
		if( $base_id < 1 ) return FALSE;

		$hm = $gCms->GetHierarchyManager();
		$node = $hm->quickfind_node_by_id($base_id);
		while( $node ) {
			if( $node->get_tag('id') == $test_id ) return TRUE;
			$node = $node->get_parent();
		}
		return FALSE;
	}

	/**
	 * Return a list of pages that the user is owner of.
	 *
	 * @since 2.0
	 * @author Robert Campbell <calguy1000@cmsmadesimple.org>
	 * @param int $userid The userid
	 * @return array Array of integer page id's
	 */
	public function GetOwnedPages(int $userid)
	{
		if( !is_array($this->_ownedpages) ) {
			$this->_ownedpages = [];

			$db = CmsApp::get_instance()->GetDb();
			$query = 'SELECT content_id FROM '.CMS_DB_PREFIX.'content WHERE owner_id = ? ORDER BY hierarchy';
			$tmp = $db->GetCol($query,[$userid]);
			$data = [];
			for( $i = 0, $n = count($tmp); $i < $n; $i++ ) {
				if( $tmp[$i] > 0 ) $data[] = $tmp[$i];
			}

			if( $data ) $this->_ownedpages = $data;
		}
		return $this->_ownedpages;
	}

	/**
	 * Test if the user specified owns the specified page
	 *
	 * @param int $userid
	 * @param int $pageid
	 * @return bool
	 */
	public function CheckPageOwnership(int $userid,int $pageid)
	{
		$pagelist = $this->GetOwnedPages($userid);
		return in_array($pageid,$pagelist);
	}

	/**
	 * Return a list of pages that the user has edit access to.
	 *
	 * @since 2.0
	 * @author Robert Campbell <calguy1000@cmsmadesimple.org>
	 * @param int $userid The userid
	 * @return int[] Array of page id's
	 */
	public function GetPageAccessForUser(int $userid)
	{
		if( !is_array($this->_authorpages) ) {
			$this->_authorpages = [];
			$data = $this->GetOwnedPages($userid);

			// Get all of the pages this user has access to.
			$list = [$userid];
			$groups = (new UserOperations())->GetMemberGroups($userid);
			if( $groups ) {
				foreach( $groups as $group ) {
					$list[] = $group * -1;
				}
			}

			$db = CmsApp::get_instance()->GetDb();
			$query = 'SELECT A.content_id FROM '.CMS_DB_PREFIX.'additional_users A
					  LEFT JOIN '.CMS_DB_PREFIX.'content B ON A.content_id = B.content_id
					  WHERE A.user_id IN ('.implode(',',$list).')
					  ORDER BY B.hierarchy';
			$tmp = $db->GetCol($query);
			for( $i = 0, $n = count($tmp); $i < $n; $i++ ) {
				if( $tmp[$i] > 0 && !in_array($tmp[$i],$data) ) $data[] = $tmp[$i];
			}

			if( $data ) asort($data);
			$this->_authorpages = $data;
		}
		return $this->_authorpages;
	}

	/**
	 * Check if the specified user has the ability to edit the specified page id
	 *
	 * @param int $userid
	 * @param int $contentid
	 * @return bool
	 */
	public function CheckPageAuthorship(int $userid,int $contentid)
	{
		$author_pages = $this->GetPageAccessForUser($userid);
		return in_array($contentid,$author_pages);
	}

	/**
	 * Test if the specified user account has edit access to all of the peers of the specified page id
	 *
	 * @param int $userid
	 * @param int $contentid
	 * @return bool
	 */
	public function CheckPeerAuthorship(int $userid,int $contentid)
	{
		if( check_permission($userid,'Manage All Content') ) return TRUE;

		$access = $this->GetPageAccessForUser($userid);
		if( !$access ) return FALSE;

		$hm = CmsApp::get_instance()->GetHierarchyManager(); //TODO below siblings?
		$node = $hm->quickfind_node_by_id($contentid);
		if( !$node ) return FALSE;
		$parent = $node->get_parent();
		if( !$parent ) return FALSE;

		$peers = $parent->get_children();
		if( $peers ) {
			for( $i = 0, $n = count($peers); $i < $n; $i++ ) { //CHECKME valid index?
				if( !in_array($peers[$i]->get_tag('id'),$access) ) return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * Find in the cache a hierarchy node corresponding to a given page id
	 * This method is replicated in cms_content_tree class
	 *
	 * @param int $contentid The page id
	 * @return mixed cms_content_tree | null
	 */
	public function quickfind_node_by_id(int $contentid)
	{
		$list = global_cache::get('content_quicklist');
		if( isset($list[$contentid]) ) return $list[$contentid];
	}
} // class

//backward-compatibility shiv
\class_alias(ContentOperations::class, 'ContentOperations', false);
