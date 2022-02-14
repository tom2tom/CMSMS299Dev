<?php
/*
Class of methods for processing the pages-tree, and content objects generally
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

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
use CMSMS\ContentType;
use CMSMS\contenttypes\ContentBase;
use CMSMS\DeprecationNotice;
use CMSMS\SingleItem;
use Exception;
use const CMS_DB_PREFIX;
use const CMS_DEPREC;
use function check_permission;
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
 */
final class ContentOperations
{
	/* *
	 * @ignore
	 * Singleton to protect static properties
	 */
//	private static $_instance = null;

	/* *
	 * @ignore
	 */
//	private $_quickfind;

	/**
	 * @ignore
	 */
	private $_authorpages;

	/**
	 * @ignore
	 */
	private $_ownedpages;

	/* *
	 * @ignore
	 * @private to prevent direct creation (even by SingleItem class)
	 */
	//private function __construct() {} TODO public iff wanted by SingleItem ?

	/**
	 * @ignore
	 */
	private function __clone() {}

	/**
	 * Get the singleton instance of this class.
	 * This method is called over a hundred times during a typical request,
	 * so warrants being a singleton.
	 * @deprecated since 2.99 instead use CMSMS\SingleItem::ContentOperations()
	 * @return ContentOperations
	 */
	public static function get_instance() : self
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'CMSMS\SingleItem::ContentOperations()'));
		return SingleItem::ContentOperations();
	}

	/**
	 * Register a new content type
	 *
	 * @since 1.9
	 * @deprecated since 2.99 instead use ContentTypeOperations::AddContentType()
	 * @param ContentTypePlaceHolder Reference to placeholder object
	 * @return bool
	 */
	public function register_content_type($obj) : bool
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'ContentTypeOperations::AddContentType'));
		return SingleItem::ContentTypeOperations()->AddContentType($obj);
	}

	/**
	 * Load a specific content type
	 *
	 * @since 1.9
	 * @deprecated since 2.99 instead use ContentTypeOperations::LoadContentType()
	 * @param mixed $type string type name or ContentType object
	 * @return mixed ContentType object | null
	 */
	public function LoadContentType($type)
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'ContentTypeOperations::LoadContentType'));
		return SingleItem::ContentTypeOperations()->LoadContentType($type);
	}

	/**
	 * Return a hash of known content types.
	 * Values are respective 'public' names (from the class FriendlyName() method)
	 * if any, otherwise the raw type-name.
	 * @deprecated since 2.99 instead use ContentTypeOperations::ListContentTypes()
	 *
	 * @param bool $byclassname optionally return keys as class names instead of type names. Default false.
	 * @param bool $allowed optionally filter the list of content types by the
	 *  'disallowed_contenttypes' site preference. Default false.
	 * @param bool $system return only CMSMS-internal content types. Default false.
	 * @return array Content type(s) registered in the system | empty
	 */
	public function ListContentTypes(bool $byclassname = FALSE, bool $allowed = FALSE, bool $system = FALSE)
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'ContentTypeOperations::ListContentTypes'));
		return SingleItem::ContentTypeOperations()->ListContentTypes($byclassname, $allowed, $system);
	}

	// =========== END OF CONTENT-TYPE METHODS ===========

	/**
	 * Return all recorded user id's and group id's in a format suitable
	 * for use in a select field.
	 *
	 * @since 2.99 Migrated from ContentBase::GetAdditionalEditorOptions()
	 * @return array each member like id => name
	 * Note: group id's are expressed as negative integers in the keys.
	 */
	public function ListAdditionalEditors() : array
	{
		$opts = [];
		$allusers = SingleItem::UserOperations()->LoadUsers();
		foreach( $allusers as &$one ) {
			$opts[$one->id] = $one->username;
		}
		$allgroups = SingleItem::GroupOperations()->LoadGroups();
		foreach( $allgroups as &$one ) {
			if( $one->id == 1 ) continue; // exclude super-admin group (they have all privileges anyways)
			$val = - (int)$one->id;
			$opts[$val] = lang('group').': '.$one->name;
		}
		unset($one);

		return $opts;
	}

	/**
	 * Return a content object for the currently requested page.
	 *
	 * @since 1.9
	 * @return mixed A content object derived from ContentBase | null
	 */
	public function getContentObject()
	{
		return SingleItem::App()->get_content_object();
	}

	/**
	 * Construct a content object representing the specified content type
	 * and serialized content.
	 * Loads the content type if that hasn't already been done.
	 *
	 * Expects an associative array with 1 or 2 members (at least):
	 *   content_type: string Optional content type name, default 'content'
	 *   serialized_content: string Serialized form data
	 *
	 * @see ContentBase::ListContentTypes()
	 * @param  array $data
	 * @return mixed A content object derived from ContentBase | null
	 */
	public function LoadContentFromSerializedData(array &$data)
	{
		if( !isset($data['serialized_content']) ) return;

		$contenttype = $data['content_type'] ?? 'content';
		$this->CreateNewContent($contenttype);

		$contentobj = unserialize($data['serialized_content']);
		return $contentobj;
	}

	/**
	 * Create a new empty content object representing the given type.
	 *
	 * If the content-type is registered with the system, and the class does not
	 * exist, the appropriate filename will be included and then, if possible,
	 * a new object of the designated type will be instantiated.
	 *
	 * @param mixed $type string type name or an instance of ContentType
	 * @param array since 2.99 initial object properties (replaces subsequent LoadFromData())
	 * @param bool since 2.99 optional flag whether to create a IContentEditor-compatible class
	 * object. Default false (hence a shortform object)
	 * @return mixed An object derived from ContentBase | null
	 */
	public function CreateNewContent($type, array $params = [], bool $editable = false)
	{
		if( $type instanceof ContentType ) {
			$type = $type->type;
		}
		$ctype = SingleItem::ContentTypeOperations()->LoadContentType($type, $editable);
		if( is_object($ctype) ) {
			if( $editable && empty($ctype->editorclass) ) {
				$editable = false; //revert to using displayable form, hopefully also editable
			}
			if( $editable ) {
				if( class_exists($ctype->editorclass) ) {
					return new $ctype->editorclass($params);
				}
			}
			elseif( class_exists($ctype->class) ) {
				return new $ctype->class($params);
			}
		}
		return null;
	}

	/**
	 * Load and return an editable content object representing the
	 * specified content id.
	 * @since 2.99
	 *
	 * @param mixed $id int | null The id of the content object to load. If < 1, the default id will be used. TODO OR create empty object of some type
	 * @param bool $loadprops Optional flag whether to load the properties of that content object. Default false.
	 * @return mixed The loaded content object | null if nothing is found.
	 */
	public function LoadEditableContentFromId($id, bool $loadprops = false)
	{
		$id = (int)$id;
		if( $id < 1 ) {
			$id = $this->GetDefaultContent();
		}

		$db = SingleItem::Db();
		$pref = CMS_DB_PREFIX;
		// content_types::name and content::type are both case-insensitive fields
		$query = <<<EOS
SELECT originator,editclass FROM {$pref}content_types T
LEFT JOIN {$pref}content C ON T.name = C.type
WHERE C.content_id = $id
EOS;
		$row = $db->getRow($query);
		if( $row ) {
			$mod = Utils::get_module($row['originator']); // ensure it's loaded
			if( $mod ) {
				$params = $db->getRow('SELECT * FROM '.CMS_DB_PREFIX.'content WHERE content_id='.$id);
				$classname = $row['editclass'];
				$content = new $classname($params);
				if( $content && $loadprops ) {
					// force-load extended properties
					$content->HasProperty('anything');
				}
				return $content;
			}
		}
	}

	/**
	 * Load and return a (non-editable) content object corresponding to the
	 * specified identifier. The object is loaded from the content cache
	 * if possible, or else added to that cache after loading.
	 *
	 * @param mixed $id int | null The id of the content object to load. If < 1, the default id will be used.
	 * @param bool $loadprops Optional flag whether to load the properties of that content object. Default false.
	 * @return mixed The loaded content object | null if nothing is found.
	 */
	public function LoadContentFromId($id, bool $loadprops = false)
	{
		$id = (int)$id;
		if( $id < 1 ) {
			$id = $this->GetDefaultContent();
		}

		$cache = SingleItem::SystemCache();
		$contentobj = $cache->get($id, 'tree_pages');
		if( !$contentobj ) {
			$db = SingleItem::Db();
			$query = 'SELECT * FROM '.CMS_DB_PREFIX.'content WHERE content_id=?';
			$row = $db->getRow($query, [$id]);
			if( $row ) {
				$ctype = SingleItem::ContentTypeOperations()->get_content_type($row['type']);
				if( $ctype ) {
//					unset($row['metadata']);
					$classname = $ctype->class;
					$contentobj = new $classname($row);
					 // legacy support deprecated since 2.99
					if( method_exists( $contentobj, 'LoadFromData') ) {
						$contentobj->LoadFromData($row);
					}
					$cache->set($id, $contentobj, 'tree_pages');
				}
				else {
					throw new Exception('Unrecognized content-type \''.$row['type'].'\' used in '.__METHOD__);
				}
			}
//		}
//		else {
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
	 * @param bool $only_active If true, only return the object if its active flag
	 *  is true. Default false.
	 * @return mixed The matched ContentBase object | null
	 */
	public function LoadContentFromAlias($alias, bool $only_active = false)  //TODO ensure appropriate variant of content-object
	{
		$contentobj = SingleItem::SystemCache()->get($alias, 'tree_pages');
		if( $contentobj === null ) {
			$db = SingleItem::Db();
			$query = 'SELECT content_id FROM '.CMS_DB_PREFIX.'content WHERE (content_id=? OR content_alias=?)';
			if( $only_active ) { $query .= ' AND active=1'; }
			$id = $db->getOne($query, [ $alias, $alias ]);
			if( $id ) {
				return $this->LoadContentFromId($id);
			}
//		}
//		else {
			//TODO trigger module-loading etc, so page tags get registered
		}

		if( $contentobj && (!$only_active || $contentobj->active) ) {
			return $contentobj;
		}
	}

	/**
	 * Update the hierarchy position of one item
	 *
	 * @internal
	 * @ignore
	 * @param integer $content_id The numeric id of the page to update
	 * @param array $hash A hash of some properties of all content objects
	 * @return array maybe empty
	 */
	private function _set_hierarchy_position(int $content_id, array $hash)
	{
		$row = $hash[$content_id];
		$saved_row = $row;
		$hier = $idhier = $pathhier = '';
		$current_parent_id = $content_id;

		while( $current_parent_id > 0 ) {
			$item_order = max($row['item_order'], 1);
			$hier = str_pad($item_order, 3, '0', STR_PAD_LEFT) . '.' . $hier; //max usable order 999 (tho in practice > 9 is a sucky design) since 2.99, was 99999
			$idhier = $current_parent_id . '.' . $idhier;
			$pathhier = $row['alias'] . '/' . $pathhier;
			$current_parent_id = $row['parent_id'];
			if( $current_parent_id < 1 ) break;
			$row = $hash[$current_parent_id];
		}

		if( strlen($hier) > 0 ) $hier = substr($hier, 0, -1);
		if( strlen($idhier) > 0 ) $idhier = substr($idhier, 0, -1);
		if( strlen($pathhier) > 0 ) $pathhier = substr($pathhier, 0, -1);

		// static properties here >> SingleItem property|ies ?
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
		return [];
	}

	/**
	 * Update the hierarchy position of all content items.
	 * This is an expensive operation on the database, but must be called each
	 * time one or more content pages are updated if positions have changed in
	 * the page structure.
	 */
	public function SetAllHierarchyPositions()
	{
		// load some data about all pages into memory... and convert into a hash.
		$db = SingleItem::Db();
		$pref = CMS_DB_PREFIX;
		// when installer is running, there may be no hierarchy value to order by, so supplement with parent_id etc
		$sql = <<<EOS
SELECT content_id,parent_id,item_order,content_alias AS alias,hierarchy,id_hierarchy,hierarchy_path FROM {$pref}content
ORDER BY hierarchy,parent_id,item_order
EOS;
/*
		$list = $db->getArray($sql);
		if( !$list ) {
			// nothing to do, get outa here.
			return;
		}
		$hash = [];
		foreach( $list as $row ) {
			$hash[$row['content_id']] = $row;
		}
		unset($list);
*/
		$hash = $db->getAssoc($sql);
		if( !$hash ) {
			return;
		}
		// would be nice to use a transaction here.
//		static $_n;
		$stmt = $db->prepare('UPDATE '.CMS_DB_PREFIX.'content SET hierarchy = ?, id_hierarchy = ?, hierarchy_path = ? WHERE content_id = ?');
		foreach( $hash as $content_id => $row ) {
			$changed = $this->_set_hierarchy_position($content_id, $hash);
			if( $changed ) {
				$db->execute($stmt, [$changed['hierarchy'], $changed['id_hierarchy'], $changed['hierarchy_path'], $content_id]);
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
		return SingleItem::LoadedData()->get('latest_content_modification');
	}

	/**
	 * Set the last modified date of content so that on the next request the content cache will be loaded from the database
	 *
	 * @internal
	 * @access private
	 */
	public function SetContentModified()
	{
		$cache = SingleItem::LoadedData();
// OR ->refresh () ready for next use ?
		$cache->delete('latest_content_modification');
		$cache->delete('default_content');
		$cache->delete('content_flatlist');
		$cache->delete('content_tree');
		$cache->delete('content_quicklist');
		SingleItem::SystemCache()->clear('tree_pages');
		//etc for CM list
	}

	/**
	 * Loads a set of content objects into the cached tree.
	 *
	 * @param bool $loadcontent UNUSED If false, only create the nodes in the tree, don't load the content objects
	 * @return ContentTree The cached tree of content
	 * @deprecated
	 */
	public function GetAllContentAsHierarchy(bool $loadcontent = false)
	{
		return SingleItem::App()->GetHierarchyManager();
	}

	/**
	 * Loads all content in the database into memory
	 * Use with caution this can chew up a lot of memory on larger sites.
	 *
	 * @param bool $loadprops Load extended content properties or just the page structure and basic properties
	 * @param bool $inactive  Load inactive pages as well
	 * @param bool $showinmenu Load pages marked as show in menu
	 */
	public function LoadAllContent(bool $loadprops = FALSE, bool $inactive = FALSE, bool $showinmenu = FALSE)
	{
		static $_loaded = 0;
		if( $_loaded == 1 ) {
			return;
		}
		$_loaded = 1;
		$cache = SingleItem::SystemCache();

		$expr = [];
		$loaded_ids = $cache->getindex('tree_pages');
		if( $loaded_ids ) {
			$expr[] = 'content_id NOT IN ('.implode(', ', $loaded_ids).')';
		}
		if( !$inactive ) {
			$expr[] = 'active = 1';
		}
		if( $showinmenu ) {
			$expr[] = 'show_in_menu = 1';
		}
		// NOTE, this 'index-hint' can be a problem! the name might be different now ??
		$query = 'SELECT * FROM '.CMS_DB_PREFIX.'content FORCE INDEX (i_contentid_hierarchy)';
		if( $expr ) {
			$query .= ' WHERE '.implode(' AND ', $expr);
		}

		$db = SingleItem::Db();
		$rst = $db->execute($query);

		if( $loadprops ) {
			$child_ids = [];
			while( !$rst->EOF() ) {
				$child_ids[] = $rst->fields['content_id'];
				$rst->MoveNext();
			}
			$rst->MoveFirst();

			$tmp = null;
			if( $child_ids ) {
				// get all the properties for the child_ids
				$query = 'SELECT * FROM '.CMS_DB_PREFIX.'content_props WHERE content_id IN ('.implode(', ', $child_ids).') ORDER BY content_id';
				$tmp = $db->getArray($query);
			}

			// re-organize the tmp data into a hash of arrays of properties for each content id.
			if( $tmp ) {
				$contentprops = [];
				for( $i = 0, $n = count($tmp); $i < $n; $i++ ) {
					$content_id = $tmp[$i]['content_id'];
					if( in_array($content_id, $child_ids) ) {
						if( !isset($contentprops[$content_id]) ) $contentprops[$content_id] = [];
						$contentprops[$content_id][] = $tmp[$i];
					}
				}
				unset($tmp);
			}
		}

		$valids = array_keys(SingleItem::ContentTypeOperations()->get_content_types());

		// build the content objects
		while( !$rst->EOF() ) {
			$row = $rst->fields;

			if( !in_array($row['type'], $valids) ) continue;

			$id = (int)$row['content_id'];
			$contentobj = $cache->get($id, 'tree_pages');
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
							$contentobj->SetPropertyValueNoLoad($oneprop['prop_name'], $oneprop['content']);
						}
					}
					$cache->set($id, $contentobj, 'tree_pages');
				}
			}
			unset($contentobj);
			$rst->MoveNext();
		}
		$contentobj = null; //force-garbage
		$rst->Close();
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
		$db = SingleItem::Db();
		$cache = SingleItem::SystemCache();

		if( $explicit_ids ) {
			$loaded_ids = $cache->getindex('tree_pages');
			if( $loaded_ids ) {
				$explicit_ids = array_diff($explicit_ids, $loaded_ids);
			}
		}
		if( $explicit_ids ) {
			$expr = 'content_id IN ('.implode(', ', $explicit_ids).')';
			if( !$all ) $expr .= ' AND active = 1';
			// NOTE, this 'index-hint' can be a problem! the name might be different now ??
			$query = 'SELECT * FROM '.CMS_DB_PREFIX.'content FORCE INDEX (i_contentid_hierarchy) WHERE '.$expr.' ORDER BY hierarchy';
			$contentrows = $db->getArray($query);
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
			$contentrows = $db->getArray($query, [$id]);
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
				$query = 'SELECT * FROM '.CMS_DB_PREFIX.'content_props WHERE content_id IN ('.implode(', ', $child_ids).') ORDER BY content_id';
				$tmp = $db->getArray($query);
			}
			else {
				$tmp = null;
			}

			// re-organize the tmp data into a hash of arrays of properties for each content id.
			if( $tmp ) {
				$contentprops = [];
				for( $i = 0, $n = count($tmp); $i < $n; $i++ ) {
					$content_id = $tmp[$i]['content_id'];
					if( in_array($content_id, $child_ids) ) {
						if( !isset($contentprops[$content_id]) ) $contentprops[$content_id] = [];
						$contentprops[$content_id][] = $tmp[$i];
					}
				}
				$tmp = null;
			}
		}

		$valids = array_keys(SingleItem::ContentTypeOperations()->get_content_types());

		// build the content objects
		for( $i = 0, $n = count($contentrows); $i < $n; $i++ ) {
			$row = &$contentrows[$i];

			if( !in_array($row['type'], $valids) ) {
				continue;
			}

			$id = (int)$row['content_id'];
			$contentobj = $cache->get($id, 'tree_pages');
			if( !$contentobj ) {
//				unset($row['metadata']);
				$contentobj = $this->CreateNewContent($row['type'], $row);
				if( $contentobj ) {
					// legacy support
					if( method_exists($contentobj, 'LoadFromData') ) {
						$contentobj->LoadFromData($row, false);
					}
					if( $loadprops && $contentprops && isset($contentprops[$id]) ) {
						// load the properties from local cache
						foreach( $contentprops[$id] as $oneprop ) {
							$contentobj->SetPropertyValueNoLoad($oneprop['prop_name'], $oneprop['content']);
						}
						unset($contentprops[$id]);
					}
					$cache->set($id, $contentobj, 'tree_pages');
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
		$db = SingleItem::Db();
		$sql = 'UPDATE '.CMS_DB_PREFIX.'content SET default_content=0 WHERE default_content!=0';
		$db->execute($sql);

		$contentobj = $this->LoadContentFromId($id); //TODO ensure relevant content-object
		$contentobj->SetDefaultContent(true);
		$contentobj->Save();

		SingleItem::LoadedData()->refresh('default_content');
	}

	/**
	 * Return the (cached) id of the content marked as default.
	 *
	 * @return int The id of the default content page, or 0 if none is recorded
	 */
	public function GetDefaultContent() : int
	{
		return (int)SingleItem::LoadedData()->get('default_content');
	}

	/**
	 * Return the content id of the page marked as default.
	 * This is an alias for GetDefaultContent()
	 *
	 * @return int The id of the default page, 0 if not found.
	 */
	public function GetDefaultPageID() : int
	{
		return $this->GetDefaultContent();
	}

	/**
	 * Return an array of all content objects in the system, active or not.
	 *
	 * Caution:  it is entirely possible that this method (and other similar methods of loading content) will result in a memory outage
	 * if there are a lot of content objects AND/OR large amounts of content properties.  Use with caution.
	 *
	 * @param bool $loadprops optional parameter for LoadAllContent(). Default true
	 * @return array The array of content objects
	 */
	public function GetAllContent(bool $loadprops = true)
	{
		debug_buffer('get all content...');
		$hm = SingleItem::App()->GetHierarchyManager();
		$list = $hm->getFlatList();

		$this->LoadAllContent($loadprops);
		$output = [];
		foreach( $list as &$node ) {
			$tmp = $node->getContent(false, true, true);
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
	 * @deprecated since 2.99 instead use CMSMS\AdminUtils::CreateHierarchyDropdown()
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
			$current, $value, $name, $allow_current, $use_perms, $allow_all, $for_child
		);
	}

	/**
	 * Return the content id corresponding to the specified content alias.
	 *
	 * @param string $alias The alias to query
	 * @return int The resulting id.  null if not found.
	 */
	public function GetPageIDFromAlias(string $alias)
	{
		$hm = SingleItem::App()->GetHierarchyManager();
		$node = $hm->find_by_tag('alias', $alias);
		if( $node ) return $node->get_tag('id');
	}

	/**
	 * Return the content id corresponding to the specified pages-hierarchy position.
	 *
	 * @param string $position The position to query
	 * @return int The resulting id.  false if not found.
	 */
	public function GetPageIDFromHierarchy(string $position)
	{
		$db = SingleItem::Db();

		$query = 'SELECT content_id FROM '.CMS_DB_PREFIX.'content WHERE hierarchy = ?';
		$content_id = $db->getOne($query, [$this->CreateUnfriendlyHierarchyPosition($position)]);

		if( $content_id ) return $content_id;
		return false;
	}

	/**
	 * Return the content alias corresponding to the specified content id.
	 *
	 * @param int $content_id The content id to query
	 * @return mixed string content alias | null if not found.
	 */
	public function GetPageAliasFromID(int $content_id)
	{
		$hm = SingleItem::App()->GetHierarchyManager();
		$node = $hm->quickfind_node_by_id($content_id);
		if( $node ) return $node->get_tag('alias');
	}

	/**
	 * Check if a content alias is used
	 *
	 * @param string $alias The alias to check
	 * @param int $content_id The id of the current page, if any
	 * @return bool
	 * @since 2.2.2
	 */
	public function CheckAliasUsed(string $alias, int $content_id = -1)
	{
		$alias = trim($alias);
		$content_id = (int) $content_id;

		$params = [ $alias ];
		$query = 'SELECT content_id FROM '.CMS_DB_PREFIX.'content WHERE content_alias = ?';
		if( $content_id > 0 ) {
			$query .= ' AND content_id != ?';
			$params[] = $content_id;
		}
		$db = SingleItem::Db();
		$out = (int) $db->getOne($query, $params);
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
		$tmp = munge_string_to_url($alias, TRUE);
		return $tmp == mb_strtolower($alias); // TODO if mb_string N/A
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
		if( $this->CheckAliasUsed($alias, $content_id) ) return lang('aliasalreadyused');
		return FALSE;
	}

	/**
	 * Convert a 0-padded ('unfriendly') display-order hierarchy (like 004.002.003)
	 * to a non-padded ('friendly') equivalent (like 4.2.3).
	 *
	 * @param string $position The hierarchy position to convert
	 * @return string The unfriendly version of the hierarchy string
	 */
	public function CreateFriendlyHierarchyPosition(string $position)
	{
		$tmp = '';
		$levels = explode('.', $position);
		$n = count($levels);
		$m = $n - 1;
		for( $i = 0; $i < $n; ++$i ) {
			$tmp .= ltrim($levels[$i], '0');
			if( $i < $m ) { $tmp .= '.'; }
		}
		return $tmp;
	}

	/**
	 * Convert a display-order-based ('friendly') hierarchy (like 1.2.3) to
	 * a 0-padded ('unfriendly') equivalent (like 0001.0002.0003).
	 * Too bad if any page order is > 9999!
	 *
	 * @param string $position The hierarchy position to convert
	 * @return string The friendly version of the hierarchy string
	 */
	public function CreateUnfriendlyHierarchyPosition(string $position)
	{
		$tmp = '';
		$levels = explode('.', $position);
		$n = count($levels);
		$m = $n - 1;
		for( $i = 0; $i < $n; ++$i ) {
			$tmp .= str_pad($levels[$i], 3, '0', STR_PAD_LEFT); //max usable order 999 (tho in practice > 9 is a sucky design) since 2.99, was 99999
			if( $i < $m ) { $tmp .= '.'; }
		}
		return $tmp;
	}

	/**
	 * Check whether the supplied page id is a parent of the specified
	 * base page (or the current page)
	 *
	 * @since 2.0
	 * @param int $test_id Page ID to test
	 * @param int $base_id (optional) Page ID to act as the base page.  The current page is used if not specified.
	 * @return bool
	 */
	public function CheckParentage(int $test_id, int $base_id = null)
	{
		$gCms = SingleItem::App();
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
	 * Return a list of pages that the user owns.
	 *
	 * @since 2.0
	 * @param int $userid The userid
	 * @return array Array of integer page id's
	 */
	public function GetOwnedPages(int $userid)
	{
		if( !is_array($this->_ownedpages) ) {
			$this->_ownedpages = [];

			$db = SingleItem::Db();
			$query = 'SELECT content_id FROM '.CMS_DB_PREFIX.'content WHERE owner_id = ? ORDER BY hierarchy';
			$tmp = $db->getCol($query, [$userid]);
			$data = [];
			for( $i = 0, $n = count($tmp); $i < $n; $i++ ) {
				if( $tmp[$i] > 0 ) $data[] = $tmp[$i];
			}

			if( $data ) $this->_ownedpages = $data;
		}
		return $this->_ownedpages;
	}

	/**
	 * Test whether the specified user owns the specified page
	 *
	 * @param int $userid
	 * @param int $pageid
	 * @return bool
	 */
	public function CheckPageOwnership(int $userid, int $pageid)
	{
		$pagelist = $this->GetOwnedPages($userid);
		return in_array($pageid, $pagelist);
	}

	/**
	 * Return a list of pages for which the user has edit-authority.
	 *
	 * @since 2.0
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
			$groups = SingleItem::UserOperations()->GetMemberGroups($userid);
			if( $groups ) {
				foreach( $groups as $group ) {
					$list[] = $group * -1;
				}
			}

			$db = SingleItem::Db();
			$query = 'SELECT A.content_id FROM '.CMS_DB_PREFIX.'additional_users A
LEFT JOIN '.CMS_DB_PREFIX.'content B ON A.content_id = B.content_id
WHERE A.user_id IN ('.implode(', ', $list).')
ORDER BY B.hierarchy';
			$tmp = $db->getCol($query);
			for( $i = 0, $n = count($tmp); $i < $n; $i++ ) {
				if( $tmp[$i] > 0 && !in_array($tmp[$i], $data) ) $data[] = $tmp[$i];
			}

			if( $data ) asort($data);
			$this->_authorpages = $data;
		}
		return $this->_authorpages;
	}

	/**
	 * Test whether the user has authority to edit the specified page
	 *
	 * @param int $userid
	 * @param int $contentid
	 * @return bool
	 */
	public function CheckPageAuthorship(int $userid, int $contentid)
	{
		$author_pages = $this->GetPageAccessForUser($userid);
		return in_array($contentid, $author_pages);
	}

	/**
	 * Test whether the user has edit-authority for all peers of the specified page
	 *
	 * @param int $userid
	 * @param int $contentid
	 * @return bool
	 */
	public function CheckPeerAuthorship(int $userid, int $contentid)
	{
		if( check_permission($userid, 'Manage All Content') ) return TRUE;

		$access = $this->GetPageAccessForUser($userid);
		if( !$access ) return FALSE;

		$hm = SingleItem::App()->GetHierarchyManager(); //TODO below siblings?
		$node = $hm->quickfind_node_by_id($contentid);
		if( !$node ) return FALSE;
		$parent = $node->get_parent();
		if( !$parent ) return FALSE;

		$peers = $parent->get_children();
		if( $peers ) {
			for( $i = 0, $n = count($peers); $i < $n; $i++ ) { //CHECKME valid index?
				if( !in_array($peers[$i]->get_tag('id'), $access) ) return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * Find in the cache a hierarchy node corresponding to a given page id
	 * This method is replicated in ContentTree class
	 *
	 * @param int $contentid The page id
	 * @return mixed ContentTree | null
	 */
	public function quickfind_node_by_id(int $contentid)
	{
		$list = SingleItem::LoadedData()->get('content_quicklist');
		if( isset($list[$contentid]) ) return $list[$contentid];
	}
} // class

//backward-compatibility shiv
\class_alias(ContentOperations::class, 'ContentOperations', false);
