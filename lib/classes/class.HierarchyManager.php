<?php
/*
Content-tree manager class
Copyright (C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use CMSMS\Lone;
use CMSMS\Tree;
use const CMS_DB_PREFIX;
use function debug_display;

/**
 * Singleton pages/content-tree manager class
 * @since 3.0
 */
class HierarchyManager
{
//	const SHALLOW = ['parent','alias','hierarchy','deep']; default keys in each $this->props value
//TODO merit of other tree-related props ? 'active' etc
	/**
	 * @var array
	 * Each member like content_id => assoc array ['parent','alias','hierarchy','deep' ....]
	 *  e.g. 16 => [-1, 'home', '001', 0] OR after loading all props
	 *	   16 => [-1, 'home', '001', 1, extra1 if any ....]
	 * @ignore
	 */
	protected $props;

	/**
	 * @var array
	 * Each member like parent_id => [child-content_ids]
	 *  e.g. -1 => [16,17,20,28,134,37,38,39,43]
	 * @ignore
	 */
	protected $children;

	/**
	 * @var array
	 * Each member like content_id => Tree object
	 * Populated incrementally, on demand
	 * @ignore
	 */
	protected $nodes = [];

	/* *
	 * @var object SystemCache singleton
	 * @ignore
	 */
//	protected $cache = null;

	/**
	 * @var object ContentOperations singleton
	 * @ignore
	 */
	protected $ops = null;

	#[\ReturnTypeWillChange]
	public function __construct()
	{
		$db = Lone::get('Db');
		$pref = CMS_DB_PREFIX;
//		debug_display('Start loading content arrays');
		$sql = "SELECT content_id,parent_id AS parent,content_alias AS alias,hierarchy,0 AS deep
FROM {$pref}content ORDER BY hierarchy";
		$this->props = $db->getAssoc($sql);
		foreach ($this->props as &$row) {
			$row['parent'] = (int)$row['parent'];
		}
		$sql = "SELECT parent_id,GROUP_CONCAT(content_id ORDER BY item_order SEPARATOR ',') AS children
FROM {$pref}content GROUP BY parent_id ORDER BY hierarchy";
		$this->children = $db->getAssoc($sql);
		foreach ($this->children as &$row) {
			$row = array_map('intval', explode(',', $row));
		}
		unset($row);
//		debug_display('End loading content arrays');
	}

	/**
	 * Add a node to the tree, as a child of the specified one
	 * @param int $id Optional node enumerator. Default -1 (root)
	 */
	public function add_node(int $id = -1)
	{
		$newid = max(array_keys($this->props)) + 1 ; //TODO get new item id from dB UPSERT
		$this->props[$newid] = ['parent'=>$id, 'alias'=>'page'.$newid, 'deep'=>0]; //'active'=>1,
		$this->children[$id][] = $newid;
		$this->nodes[$newid] = new Tree($newid, $this);
		//TODO OR 'deep'=>1, later update dB accordingly?? flag for action in __destruct() ??
	}

	/**
	 * Remove a node from the tree, optionally along with all its descendants
	 * @param bool $with_descends whether to also remove any descendant(s) Default false
	 *  If false, any children are re-parented to their grandparent.
	 * @param int $id Optional node enumerator. Default -99 (N/A)
	 */
	public function remove_node(bool $with_descends = false, int $id = -99)
	{
		$parent = $this->props[$id]['parent'];
		$tmp = $this->children[$id] ?? [];
		unset ($this->props[$id]);
		if ($tmp) {
			if ($with_descends) {
				foreach ($tmp as $cid) {
					$this->remove_node($cid, true); //recurse
				}
			} else {
				$tmp = array_merge($this->children[$parent], $tmp);
				$this->children[$parent] = array_unique($tmp, SORT_NUMERIC);
			}
		}
		unset($this->children[$id]);
		unset($this->nodes[$id]);
		//TODO update dB accordingly?? flag for action in __destruct() ??
		//pre-3.0 this made no permanent change
	}

	/**
	 * Remove a node from the tree
	 * Formerly, remove_node() was for a specified node, and this was
	 * for the 'current' node and always without descendants (risky!).
	 * This is now effectively the same as remove_node().
	 * @deprecated since 3.0
	 *
	 * @param int $id Optional node enumerator. Default -99 (N/A)
	*/
	public function remove(int $id = -99)
	{
		$this->remove_node(false, $id);
	}

	/**
	 * Set a node property value
	 * @param string $key
	 * @param mixed $value
	 * @param int $id optional node enumerator Default -99 (N/A)
	 */
	public function set_tag(string $key, $value, int $id = -99)
	{
		$oval = $this->get_tag($id, $key); // ensure the property is loaded
		if ($oval != $value) {
			$this->props[$id][$key] = $value;
			//save in db now? flag for action in __destruct() ??
			//pre-3.0 did nothing for permanent change
		}
	}

	/**
	 * Retrieve a node property value
	 * @param string $key
	 * @param int $id optional node enumerator Default -99 (N/A)
	 * @return mixed value | null
	 */
	public function get_tag(string $key, int $id = -99)
	{
		if ($key == 'id') {
			if (isset($this->props[$id])) {
				return $id;
			}
			return;
		}
		if (isset($this->props[$id][$key]) || $this->props[$id][$key] === null) {
			return $this->props[$id][$key];
		}
		if (empty($this->props[$id]['deep'])) {
			$this->deepen($id);
		}
		if (isset($this->props[$id][$key]) || $this->props[$id][$key] === null) {
			return $this->props[$id][$key];
		}
	}

	/**
	 * Report the tree-depth of the specified node.
	 * @param int $id Optional node enumerator. Default -1 (root)
	 * @return int
	 * @throws RuntimeException if a broken tree is found
	 */
	public function get_level(int $id = -1) : int
	{
		$n = 1;
		while ($id > -1) {
			if (isset($this->props[$id])) {
				$id = $this->props[$id]['parent'];
				++$n;
			} else {
				throw new RuntimeException('Fatal error in '.__METHOD__.' - dangling subtree');
			}
		}
		return $n;
	}

	/**
	 * Report the number of nodes representing the specified one and all its descendants.
	 * (The root node would be counted though is conceptual only)
	 * @param int $id Optional node enumerator. Default -1 (root)
	 * @return int
	 */
	public function count_nodes(int $id = -1) : int
	{
		if ($id == -1) {
			return count($this->props) + 1;
		}
		$n = 1;
		if ($this->has_children($id)) {
			foreach($this->children[$id] as $a) {
				$n += $this->count_nodes($a); // recurse
			}
		}
		return $n;
	}

	/**
	 * Report the number of nodes representing the specified one and all its siblings.
	 * (The root node would be counted though is conceptual only)
	 * @param int $id Optional node enumerator. Default -1 (root)
	 * @return int
	 */
	public function count_siblings(int $id = -1) : int
	{
		if ($id == -1) {
			return 1;
		}
		if (isset($this->props[$id])) {
			return count($this->children[$this->props[$id]['parent']]);
		}
		return 0;
	}

	/**
	 * Return the parent node (if any) of the specified one.
	 *
	 * @param int $id Optional node enumerator. Default -99 (N/A)
	 * @return mixed Tree | null
	 */
	public function get_parent(int $id = -99)
	{
		if (isset($this->props[$id])) {
			$n = (int)$this->props[$id]['parent'];
			if (!isset($this->nodes[$n])) {
				$this->nodes[$n] = new Tree($n, $this);
			}
			return $this->nodes[$n];
		}
	}

	/**
	 * Report whether the specified node has children.
	 * @param int $id Optional node enumerator. Default -1 (root)
	 * @return bool
	 */
	public function has_children(int $id = -1) : bool
	{
		return (!empty($this->children[$id]));
	}

	/**
	 * Report the number of direct children of the identified node.
	 * @param int $id node enumerator
	 * @return int
	 */
	public function count_children(int $id = -1) : int
	{
		return count($this->children[$id]) ?? 0;
	}

	/**
	 * Return the child-nodes (if any) of the specified one.
	 * @see also HierarchyManager::get_children(), which does no content-loading
	 * $param bool $as_node Since 3.0 Optional return-type indicator. Default true.
	 * @param int $id Optional node enumerator. Default -1 (root)
	 * @return array reference each member like id => Tree object | empty if there are no children.
	 * or each member like id if $as_node is false
	 */
	public function &get_children(bool $as_node = true, int $id = -1) : array
	{
		$ret = [];
		if (isset($this->children[$id])) {
			foreach ($this->children[$id] as $n) {
				if (!isset($this->nodes[$n])) {
					$this->nodes[$n] = new Tree($n, $this);
				}
				if ($as_node) {
					$ret[$n] = $this->nodes[$n];
				} else {
					$ret[] = $n;
				}
			}
		}
		return $ret;
	}

	/**
	 * Get the children of the specified node, loading their respective
	 * content objects as part of the process
	 * @see also HierarchyManager::getChildren(), which loads content
	 * @param bool $deep Optionally load the extended properties of the children (only used when $loadcontent is true)
	 * @param bool $all Load all children, including inactive/disabled ones (only used when $loadcontent is true)
	 * @param bool $loadcontent Load content objects for children
	 * $param bool $as_node Since 3.0 Optional return-type indicator. Default true.
	 * @param int $id Optional node enumerator. Default -1 (root)
	 * @return array reference each member like id => Tree object | empty if there are no children.
	 */
	public function &getChildren(bool $deep = false, bool $all = false, bool $loadcontent = true, bool $as_node = true, int $id = -1) : array
	{
		$children = $this->get_children($as_node, $id);
		if ($children && $loadcontent) {
			if (!$this->ops) {
				$this->ops = Lone::get('ContentOperations');
			}
			if ($as_node) {
				$this->ops->LoadChildren($id, $deep, $all, array_keys($children));
			} else {
				$this->ops->LoadChildren($id, $deep, $all, $children);
			}
		}
		return $children;
	}

	/**
	 * Return all nodes.
	 * @return array reference each member like id => Tree object
	 */
	public function &getFlatList() : array
	{
		debug_display('Start populate page-nodes flatlist');
		foreach ($this->props as $id => &$arr) {
			if (!isset($this->nodes[$id])) {
				$this->nodes[$id] = new Tree($id, $this);
			}
		}
		unset($arr);
		debug_display('End populate page-nodes flatlist');
		return $this->nodes;
	}

	/**
	 * Try to find a tree node (the specified one or a descendant) which
	 * has the specified property and value.
	 * @param string $prop_name The property to search for
	 * @param mixed  $value The property value to search for
	 * @param bool   $case_insensitive Whether the value (if a string) may
	 *  match regardless of case. Default false.
	 * @param int $id Optional node enumerator. Default -1 (root)
	 * @return mixed Tree object or null if not found.
	 */
	public function find_by_tag(string $prop_name, $value, bool $case_insensitive = false, int $id = -1)
	{
		if ($prop_name == 'id') {
			$id = (int)$value;
			if (isset($this->props[$id])) {
				if (!isset($this->nodes[$id])) {
					$this->nodes[$id] = new Tree($id, $this);
				}
				return $this->nodes[$id];
			}
			return;
		}
		if (array_search($prop_name, ['parent','alias','hierarchy','deep']) !== false) {
			//TODO if ($case_insensitive)
			$n = array_search($value, array_column($this->props, $prop_name));
			if ($n !== false) {
				$id = array_keys($this->props)[$n];
				if (!isset($this->nodes[$id])) {
					$this->nodes[$id] = new Tree($id, $this);
				}
				return $this->nodes[$id];
			}
			return;
		} else {
			foreach ($this->props as $id => &$row) {
				//TODO if ($case_insensitive)
				if (isset($row[$prop_name]) && $value == $row[$prop_name] || $value === null && $row[$prop_name] === null) {
					unset($row);
					if (!isset($this->nodes[$id])) {
						$this->nodes[$id] = new Tree($id, $this);
					}
					return $this->nodes[$id];
				}
				if (empty($row['deep'])) {
					$this->depeen($id, $prop_name); // CHECKME get all props at once? if not, when is deep true ?
					//TODO if ($case_insensitive)
					if (isset($row[$prop_name]) && $value == $row[$prop_name] || $value === null && $row[$prop_name] === null) {
						unset($row);
						if (!isset($this->nodes[$id])) {
							$this->nodes[$id] = new Tree($id, $this);
						}
						return $this->nodes[$id];
					}
				}
			}
			unset($row);
		}
	}

	/**
	 * Return the node (if any) or numeric id of such node, corresponding
	 * to the supplied identifier.
	 * @since 3.0
	 * @param mixed $a string | int enumerator or alias
	 * $param bool  $as_node Optional return-type indicator. Default true.
	 *  False to return the numeric identifier (after populating node data if necessary).
	 * @return mixed Tree | null | int (maybe 0)
	 */
	public function find_by_identifier($a, bool $as_node = true)
	{
		if (is_numeric($a)) {
			$a = (int)$a;
			$obj = $this->get_node_by_id($a);
			if ($obj) {
				return ($as_node) ? $obj : $a;
			}
		} else {
			$obj = $this->get_node_by_alias($a);
			if ($obj) {
				return ($as_node) ? $obj : array_search($this->nodes, $obj);
			}
		}
		return ($as_node) ? null : 0;
	}

	/**
	 * Retrieve a node by page-enumerator.
	 * @since 3.0
	 * @param int $id Optional node enumerator. Default -99 (N/A)
	 * @return mixed Tree | null
	*/
	public function get_node_by_id(int $id = -99)
	{
		if (isset($this->props[$id])) {
			if (!isset($this->nodes[$id])) {
				$this->nodes[$id] = new Tree($id, $this);
			}
			return $this->nodes[$id];
		}
	}

	/**
	 * Retrieve a node by page-enumerator (unfriendly old method-name)
	 * @deprecated since 3.0 Instead use HierarchyManager::get_node_by_id()
	 * @param int $id Optional node enumerator. Default -99 (N/A)
	 * @return mixed Tree | null
	*/
	public function quickfind_node_by_id(int $id = -99)
	{
		return $this->get_node_by_id($id);
	}

	/**
	 * Retrieve a node by page-alias.
	 * @since 3.0
	 * @param string $alias
	 * @return mixed Tree | null
	 */
	public function get_node_by_alias(string $alias)
	{
		if (count($this->props) <= 200) {
			$n = array_search($alias, array_column($this->props, 'alias'));
			if ($n !== false) {
				$id = array_keys($this->props)[$n];
			} else {
				return;
			}
		} else {
			$db = Lone::get('Db');
			$id = $db->GetOne('SELECT content_id FROM '.CMS_DB_PREFIX.'content FORCE INDEX (i_contental_active) WHERE content_alias=?', [$alias]);
			if (!$id) {
				return;
			}
		}
		if (!isset($this->nodes[$id])) {
			$this->nodes[$id] = new Tree($id, $this);
		}
		return $this->nodes[$id];
	}

	/**
	 * Retrieve a node by page-alias (unfriendly old method-name)
	 * @deprecated since 3.0 Instead use HierarchyManager::get_node_by_alias()
	 * @param string $alias
	 * @return mixed Tree | null
	 */
	public function sureGetNodeByAlias(string $alias)
	{
		return $this->get_node_by_alias($alias);
	}

	/**
	 * Retrieve a node by hierarchy position.
	 * @param string $position pages-hierarchy identifier like 00x.00y. ...
	 * @return mixed Tree | null
	 */
	public function get_node_by_hierarchy(string $position)
	{
		if (count($this->props) <= 200) {
			$n = array_search($position, array_column($this->props, 'hierarchy'));
			if ($n !== false) {
				$id = array_keys($this->props)[$n];
			} else {
				return;
			}
		} else {
			$db = Lone::get('Db');
			$id = $db->GetOne('SELECT content_id FROM '.CMS_DB_PREFIX.'content FORCE INDEX (i_hierarchy) WHERE hierarchy=?', [$position]);
			if (!$id) {
				return;
			}
		}
		if (!isset($this->nodes[$id])) {
			$this->nodes[$id] = new Tree($id, $this);
		}
		return $this->nodes[$id];
	}

	/**
	 * Retrieve a node by hierarchy position.
	 * @deprecated since 3.0 Instead use HierarchyManager::get_node_by_hierarchy()
	 * @param string $position pages-hierarchy identifier like 00x.00y. ...
	 * @return mixed Tree | null
	 */
	public function getNodeByHierarchy(string $position)
	{
		return $this->get_node_by_hierarchy($position);
	}

	/**
	 * Retrieve the hierarchy property for the specified node
	 * @param int $id Optional node enumerator. Default -99 (N/A)
	 * @return string
	 */
	public function getHierarchy(int $id = -99) : string
	{
		if (isset($this->props[$id])) {
			return $this->props[$id]['hierarchy'];
		}
		return '';
	}

	/**
	 * Retrieve [some/?]all sofar-unrecorded properties(s) from the
	 *  database content [& content_properties] table(s)
	 * @param int $id node enumerator
	 */
	protected function deepen(int $id)
	{
		$db = Lone::get('Db');
		$pref = CMS_DB_PREFIX;
//		debug_display('Start loading deep properties for '.$id);
		//TODO cache a prepared statement
		$sql = "SELECT parent_id AS parent,content_alias AS alias,1 AS deep,*
FROM {$pref}content WHERE content_id=?";
		$props = $db->getRow($sql, [$id]);
		unset($props['content_id'], $props['parent_id'], $props['content_alias']);
/*
		$sql = "SELECT prop_name,content FROM {$pref}content_props WHERE content_id=? ORDER BY prop_name";
		$arr = $db->getArray($sql,[$id]);
		foreach ($arr as &$row) {
			$props[$row['prop_name']] = $row['content'];
		}
		unset($row);
*/
//		debug_display('End loading deep properties for '.$id);
		$this->props[$id] = $props;
	}

	//The following are here for back-compatibility only

	/**
	 * Recursively populate the (single) content tree from a flat array
	 * i.e. LoadedData 'content_props'
	 * @deprecated since 3.0 Does nothing
	 * @internal
	 * @param array The data to import, a row for each page, with members
	 *  content_id, parent_id, item_order, content_alias, active - all strings
	 * @return null
	 */
	public static function load_from_list(array $data)
	{
		//TODO deprecation notice if used
	}

	/**
	 *
	 * @deprecated since 3.0 Instead just use the auto-cached content
	 * @param int $id Optional node enumerator. Default -99 (N/A)
	 * @return bool since 3.0 False always
	 */
	public function isContentCached(int $id = -99) : bool
	{
/*
		if (isset($this->props[$id])) {
			if (!$this->cache) {
				$this->cache = Lone::get('SystemCache');
			}
			return $this->cache->has($id,'tree_pages');
		}
*/
		return false;
	}

	/**
	 * Return the content object for the page associated with the specified
	 * node, after loading that object if necessary, and then recording it
	 * in the page-objects-cache for use in subsequent requests.
	 * @param bool $deep load all extended properties for the content object if loading is required. Default false
	 * @param bool $loadsiblings load all the siblings for the selected content object at the same time (a performance optimization) Default true
	 * @param bool $loadall If loading siblings, include inactive/disabled pages. Default false.
	 * @param int $id node enumerator Default -99 (N/A)
	 * @return mixed CMSMS\ContentBase-derived object | null
	 */
	public function getContent(bool $deep = false, bool $loadsiblings = true, bool $loadall = false, int $id = -99)
	{
		if (!$this->ops) {
			$this->ops = Lone::get('ContentOperations');
		}
/*
		if (!$this->cache) {
			$this->cache = Lone::get('SystemCache');
		}
		if (!$this->cache->has($id,'tree_pages')) {
			// not in cache
			if (isset($this->props[$id])) {
				$n = (int)$this->props[$id]['parent'];
				if (!$loadsiblings || $n == -1) {
					// only load this content object
					// TODO cache something here?
					return $this->ops->->LoadContentFromId($id, $deep);  //TODO ensure relevant content-object?
				} else {
					$this->getChildren($deep, $loadall, true, $n);
					if ($this->cache->has($id,'tree_pages')) {
						return $this->cache->get($id,'tree_pages');
					}
				}
			} else {
				$here = 1;
				//TODO e.g. throw
			}
		}
		// TODO cache something here?
*/
		return $this->ops->LoadContentFromId($id, $deep);  //TODO ensure relevant content-object?
	}
}
