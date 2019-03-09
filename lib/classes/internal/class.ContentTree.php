<?php
#Class to interact with hierarchical CMSMS page-content
#Copyright (C) 2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

namespace CMSMS\internal;

use CMSMS\ArrayTree;
use CMSMS\ContentOperations;
use CMSMS\internal\content_cache;
use CMSMS\internal\global_cache;

/**
 * Class that provides content retrieval abilities, using the content cache
 * where possible. It also supports a tiny subset of the old Tree class used
 * in CMSMS versions prior to 1.9, for backward compatibility.
 * @since 2.3
 *
 * @package CMS
 * @license GPL
 */
class ContentTree
{
	/**
	 * Full array tree, actual or reference
	 * @ignore
	 * array
	 */
	private $tree;

	/**
	 * Array tree nodes count
	 * @ignore
	 * int
	 */
	private static $treecount = -1;

	/**
	 * Array tree node reference
	 * @ignore
	 * array | null
	 */
	public $node = null;

	/**
	 * @param optional flag whether to populate $tree property default false
	 */
	public function __construct(bool $deep = false)
	{
		if ($deep) {
			$node = global_cache::get('content_tree');
			$this->tree = &$node;
		} else {
			$this->tree = null;
		}
	}

	/**
	 * @ignore
	 */
	public function __clone()
	{
		$this->tree = &$this->tree; //CHECKME
		if ($this->node) {
			$this->node = null;
		}
	}

	/**
	 * @ignore
	 * @internal
	 */
	public function setdata(&$data)
	{
		$this->$node = $data;
	}

	/**
	 * Add the specified node as a child of this node (if not already in there).
	 *
	 * @param ContentTree $node The node to add
	 * @return bool
	 */
	public function add_node(ContentTree $node) : bool
	{
		$id = (int)$node->node['content_id'];
		if (isset($this->node['children'][$id])) {
			return false; //CHECKME support replacement ?
		}

		if (empty($this->node['children'])) {
			$this->node['children'] = [];
		}
		$this->node['children'][$id] = &$node->node;
		return true;
	}

	/**
	 * Remove the specified node from the tree.
	 *
	 * Search through the children, or optionally all descendants,
	 * of this node for the specified node.
	 * If found, remove it and all its its descendants.
	 *
	 * @param ContentTree $node Reference to the node to be removed.
	 * @param bool	 $search_children Whether to recursively remove decendants. Default false.
	 * @return bool
	 */
	protected function remove_node(ContentTree $node, bool $search_children = true) : bool
	{
		if (empty($this->node['children'])) {
			return false;
		}

		$id = (int)$node->node['content_id'];
		if (isset($this->node['children'][$id])) {
			// node found
			if (ArrayTree::drop_node($this->tree,$this->node['children'][$id]['path'])) {
				return true;
			}
			return false;
		}
		if ($search_children) {
// TODO recurse using iterator
		}
		return false;
	}

	/**
	 * Remove this node, and its descendents if any, from the tree.
	 *
	 * @return bool
	 */
	public function remove() : bool
	{
		return ArrayTree::drop_node($this->tree, $this->node['path']);
	}

	/**
	 * Interpret data-definer $typer
	 *
	 * @ignore
	 * @since 2.3
	 * @return 5-member array
	 */
	private function parse_type($typer)
	{
		if (!$typer) {
			return [true, false, false, false, []];
		}
		if (!is_array($typer)) {
			$typer = [$typer];
		}
		$asnode = $idalias = $aliasalias = $withcontent = false;
		for ($i = 0, $n = count($typer); $i < $n; ++$i) {
			$s = strtolower($typer[$i]);
			switch ($s) {
			 case 'id':
				$idalias = true;
				$typer[$i] = 'content_id';
				break;
			 case 'alias':
				$aliasalias = true;
				$typer[$i] = 'content_alias';
				break;
			 case 'content':
				$withcontent = true;
				unset($typer[$i]);
				break;
				case 'children':
				unset($typer[$i]);
				break;
			 case 'node':
			 case 'object':
				return [true, false, false, false, []];
			 default:
				$typer[$i] = $s; //lowercase
				break;
			}
		}
		$tags = array_values($typer);
		return [$asnode,$idalias,$aliasalias,$withcontent,$tags];
	}

	/**
	 * Generate return-data in accord with supplied parameters.
	 *
	 * @ignore
	 * @since 2.3
	 * @return mixed
	 */

	private function get_node_data(&$node, bool $asnode, bool $idalias, bool $aliasalias, bool $withcontent, array $tags)
	{
		if ($asnode) {
			$obj = clone $this;
			$obj->node = &$node;
			return $obj;
		}
	}

	/**
	 * Get the parent node of this one.
	 * @since 2.0
	 *
	 * @param mixed $typer since 2.3 optional data-type indicator false
	 *  or string or strings[] - tag-name(s). Default false.
	 * @return mixed data for the parent node in accord with $typer | null
	 */
	public function get_parent($typer = false)
	{
		$node = ArrayTree::path_get_ancestor($this->tree, $this->node['path'], -1);
		if ($node) {
			return $this->get_node_data($node,$this->parse_type($typer));
		}
		return $node;
	}

	/**
	 * Get the parent node.
	 * @deprecated since 2.0
	 * @see ContentTree::get_parent()
	 *
	 * @return ContentTree for the parent node, or null.
	 */
	public function getParent()
	{
		return $this->get_parent();
	}

	/**
	 * Report whether this node has children.
	 *
	 * @return bool
	 */
	public function has_children() : bool
	{
		return !empty($this->node['children']);
	}

	/**
	 * Return the number of direct children of this node.
	 *
	 * @return int
	 */
	public function count_children() : int
	{
		if (empty($this->node['children'])) {
			return 0;
		}
		return count($this->node['children']);
	}

	/**
	 * Return the children of this node.
	 *
	 * @param mixed $typer since 2.3 optional data-type indicator false
	 *  or string or strings[] - tag-name(s). Default false.
	 * @return mixed array of data in accord with $typer | null
	 */
	public function &get_children($typer = false)
	{
		$res = null;
		if (!empty($this->node['children'])) {
			list ($asnode,$idalias,$aliasalias,$withcontent,$tags) = $this->parse_type($typer);
			$res = [];
			foreach ($this->node['children'] as $id => &$node) {
				$res[$id] = $this->get_node_data($node,$asnode,$idalias,$aliasalias,$withcontent,$tags);
			}
			unset($node);
		}
		return $res;
	}

	/**
	 * Return the number of siblings of this node.
	 *
	 * @return int
	 */
	public function count_siblings() : int
	{
		$node = ArrayTree::path_get_ancestor($this->tree, $this->node['path'], -1);
		if ($node) {
			return (!empty($node['children'])) ? count($node['children']) : 1; // send 1 instead of 0
		}
		return 1;
	}

	/**
	 * Return the siblings of this node (including this node).
	 * @since 2.3
	 *
	 * @param mixed $typer since 2.3 optional data-type indicator false
	 *  or string or strings[] - tag-name(s). Default false.
	 * @return mixed array of data in accord with $typer | null
	 */
	public function &get_siblings($typer = false)
	{
		$res = null;
		$node = ArrayTree::path_get_ancestor($this->tree, $this->node['path'], -1);
		if ($node && !empty($node['children'])) {
			list ($asnode,$idalias,$aliasalias,$withcontent,$tags) = $this->parse_type($typer);
			$res = [];
			foreach ($node['children'] as $id => &$sibnode) {
				$res[$id] = $this->get_node_data($sibnode,$asnode,$idalias,$aliasalias,$withcontent,$tags);
			}
			unset($node);
		}
		return $res;
	}

	/**
	 * Return the total number of tree-nodes, including this one.
	 *
	 * @return int
	 */
	public function count_nodes() : int
	{
		if (self::$treecount < 0) {
			$list = global_cache::get('content_quicklist');
			self::$treecount = count($list);
		}
		return self::$treecount;
	}

	/**
	 * Return the tree-depth of this node.
	 *
	 * @return int
	 */
	public function get_level() : int
	{
		return min(1,count($this->node['path']) - 2);
	}

	/**
	 * Set a tag value in this node.
	 *
	 * @param string $key Tag name
	 * @param mixed  $value Tag value
	 */
	public function set_tag(string $key, $value)
	{
		$this->node[$key] = $value;
	}

	/**
	 * Retrieve a tag-value for this node.
	 *
	 * @param string $key The tag name
	 * @return mixed The tag value, or null
	 */
	public function get_tag(string $key)
	{
		return $this->node[$key] ?? null;
	}

	/* *
	 * Recursively find a tree node matching a name/type and value.
	 *
	 * @param string $tag_name The tag name to search for (lower case)
	 * @param mixed  $value The tag value to search for
	 * @param bool $case_insensitive Whether the (string) value should matched regardless of case. Default false.
	 * @param mixed $typer since 2.3 optional data-type indicator false
	 *  or string or strings[] - tag-name(s). Default false.
	 * @return mixed data in accord with $typer | null
	 */
/*	public function find_by_tag_raw($tag_name, $value, $case_insensitive = false, $typer = false)
	{
		switch (strtolower($tag_name)) {
			case 'id':
				$tag_name = 'content_id';
				break;
			case 'alias':
				$tag_name = 'content_alias';
				break;
		}
		$path = ArrayTree::find($this->tree, $tag_name, $value, !$case_insensitive);
		if ($path) {
			$node = ArrayTree::node_get_data($this->tree, $path, '*');
			return $this->get_node_data($node,$this->parse_type($typer));
		}
		return $path; //null
	}
*/
	/**
	 * Find a tree node matching the given tag-type and its value.
	 *
	 * @param string $tag_name The tag name to search for
	 * @param mixed  $value The tag value to search for
	 * @param bool $case_insensitive Whether the value should be treated as case insensitive.
	 * @param bool $usequick Optionally, when searching by id... use the quickfind method if possible.
	 * @param mixed $typer since 2.3 optional data-type indicator false
	 *  or string or strings[] - tag-name(s). Default false.
	 * @return mixed data in accord with $typer | null
	 */
	public function find_by_tag($tag_name, $value, $case_insensitive = false, $usequick = true, $typer = false)
	{
		if ($tag_name == 'id') {
			$case_insensitive = true;
		}
		if ($usequick && $tag_name == 'id') {
			return $this->quickfind_node_by_id($value, $typer);
		}

		$tag_low = strtolower($tag_name);
		switch ($tag_low) {
			case 'id':
				$tag_low = 'content_id';
				$match = $tag_low == 'content_'.$tag_name;
				break;
			case 'alias':
				$tag_low = 'content_alias';
				$match = $tag_low == 'content_'.$tag_name;
				break;
			default:
				$match = $tag_low == $tag_name;
				break;
		}

		$list = global_cache::get('content_quicklist');
		foreach ($list as &$node) {
			if (isset($node[$tag_low]) && $node[$tag_low] == $value) { //non-strict match OK ?
				if ($match || !$case_insensitive) {
					$res = $this->get_node_data($node,$this->parse_type($typer));
					unset($node);
					return $res;
				}
			}
		}
		unset($node);
		return null;
	}

	/**
	 * Find a tree node corresponding to the supplied tag-value whose type
	 * is unspecified, but assumed to be an id or alias.
	 *
	 * @since 2.3
	 * @param mixed  $value The tag value to search for
	 * @param string $type  I/O parameter supplies initial type-guess or '',
	 *  and returns the discovered type: 'id' 'alias' or ''
	 * @param mixed $typer optional data-type indicator false
	 *  or string or strings[] - tag-name(s). Default false.
	 * @return mixed ContentTree | null
	 */
	public function find_by_tag_anon($value, string &$type, $typer = false)
	{
		if (!$type) {
			$type = 'alias'; //default intial tag-type
		}
		$obj = $this->find_by_tag($type, $value, false, true, $typer);
		if ($obj) {
			return $obj;
		}
		if ($type != 'id' && is_numeric($value) && $value >= 0) {
			$obj = $this->quickfind_node_by_id((int)$value, $typer);
			if ($obj) {
				$type = 'id';
				return $obj;
			}
		}
		if ($type != 'alias') {
			$obj = $this->find_by_tag('content_alias', $value, false, true, $typer);
			if ($obj) {
				$type = 'alias';
				return $obj;
			}
		}
		$type = '';
		$obj = null;
		return $obj;
	}

	/**
	 * Retrieve from cache the node for a page id.
	 * Method replicated in ContentOperations class
	 *
	 * @param int $id The page id
	 * @param mixed $typer since 2.3 optional data-type indicator false
	 *  or string or strings[] - tag-name(s). Default false.
	 * @return mixed data in accord with $typer | null
	 */
	public function quickfind_node_by_id($id, $typer = false)
	{
		$list = global_cache::get('content_quicklist');
		if (isset($list[$id])) {
			return $this->get_node_data($node,$this->parse_type($typer));
		}
	}

	/**
	 * Retrieve a node by its id.
	 * A backwards compatibility method.
	 *
	 * @deprecated since 1.9
	 * @see ContentTree::quickfind_node_by_id()
	 * @param int $id
	 * @return ContentTree
	 */
	public function sureGetNodeById($id)
	{
		return $this->quickfind_node_by_id($id); //no $typer
	}

	/* *
	 * Retrieve a node by its id.
	 *
	 * A backwards compatibility method.
	 *
	 * @deprecated since 1.9
	 * @param int $id
	 * @return ContentTree
	 */
	/*	public function getNodeById(int $id)
		{
			return $this->find_by_tag('id', $id); //no $typer
		}
	*/
	/**
	 * Retrieve a node by its alias
	 *
	 * A backwards compatibility method
	 *
	 * @deprecated since 1.9
	 * @see ContentTree::find_by_tag() or find_by_tag_anon()
	 * @param mixed $alias null|bool|int|string
	 * @return mixed ContentTree | null
	 */
	public function sureGetNodeByAlias($alias)
	{
		if ($alias == '') {
			return;
		}
		if (is_numeric($alias) && $alias > 0) {
			return $this->quickfind_node_by_id((int)$alias); //no $typer
		}
		return $this->find_by_tag('alias', $alias); //no $typer
	}

	/* *
	 * Retrieve a node by its alias
	 *
	 * A backwards compatibility method.
	 *
	 * @deprecated since 1.9
	 * @param mixed? $alias
	 * @return ContentTree
	 */
	/*	public function getNodeByAlias($alias)
		{
			return $this->find_by_tag_raw('alias', $alias, true); //no $typer
		}
	*/
	/**
	 * Retrieve a node by hierarchy position.
	 *
	 * @param string $position
	 * @param mixed $typer since 2.3 optional data-type indicator false
	 *  or string or strings[] - tag-name(s). Default false.
	 * @return mixed data in accord with $typer | null
	 */
	public function getNodeByHierarchy($position, $typer = false)
	{
		$id = ContentOperations::get_instance()->GetPageIDFromHierarchy($position);
		if ($id) {
			return $this->quickfind_node_by_id($id, $typer);
		}
		return null;
	}

	/**
	 * Get the (estimated) hierarchy position of this node among its siblings.
	 *
	 * @return mixed int | null
	 */
	private function _getPeerIndex()
	{
		$res = $this->get_tag('hierarchy');
		if ($res) {
			//TODO interpret it
			$parts = explode('.', $res);
			$ADBG = 0; // (int) one of them;
			//OR tree iterator
		}
		// find index of current node among its peers.
		$node = ArrayTree::path_get_ancestor($this->tree, $this->node['path'], -1);
		if ($node) {
			$i = 1;
			$match = (int)$this->node['content_id'];
			foreach ($node['children'] as $id => &$node) {  //array must exist unless tree is borked ?
				if ($id == $match) {
					unset($node);
					return $i;
				}
				++$i;
			}
			unset($node);
			return $i;
		}
		return 1;
	}

	/**
	 * @ignore
	 */
	private function _getHierarchyArray() : array
	{
		$res = [$this->_getPeerIndex()];
		//TODO ArrayTree iterator ::whatever ... or $node['path'] members
		$parent = $this->get_parent(); // no $typer
		if ($parent) {
			$out = $parent->_getHierarchyArray(); // recurse
			if ($out) {
				$res = array_merge($res, $out);
			}
		}
		return $res;
	}

	/**
	 * Get the hierarchy position of this node
	 *
	 * @return string
	 */
	public function getHierarchy()
	{
		if (($hier = $this->get_tag('hierarchy'))) {
			return $hier;  // only if tag has been checked before
		}

		$list = $this->_getHierarchyArray();
		foreach ($list as &$one) {
			$one = sprintf('%05d', $one);
		}
		unset($one);
		$res = implode('.', array_reverse(array_splice($list, 0, -1)));
		$this->set_tag('hierarchy', $res);

		return $res;
	}

	/* *
	 * Test if this node has children.
	 *
	 * A backwards compatibility method.
	 *
	 * @deprecated since 1.9
	 * @see ContentTree:has_children()
	 * @return bool
	 */
	/*	public function hasChildren()
		{
			return $this->has_children();
		}
	*/
	/* *
	 * Set a tag value
	 *
	 * A backwards compatibility method
	 *
	 * @deprecated since 1.9
	 * @see ContentTree::set_tag()
	 * @param string $key The tag name/key
	 * @param mixed  $value The tag value
	 */
	/*	public function setTag(string $key,$value)
		{
			return $this->set_tag($key,$value);
		}
	*/
	/* *
	 * Get this nodes id.
	 *
	 * A backwards compatibility method
	 *
	 * @deprecated since 1.9
	 * @see ContentTree::get_tag()
	 * @return int The node id.
	 */
	/*	public function getId()
		{
			return $this->get_tag('id');
		}
	*/
	/* *
	 * Get a node tag.
	 *
	 * A backwards compatibility method
	 *
	 * @deprecated since 1.9
	 * @see ContentTree::get_tag()
	 * @param  string $key Tag name/key
	 * @return mixed Node value.
	 */
	/*	public function getTag(string $key = 'id')
		{
			return $this->get_tag($key);
		}
	*/
	/* *
	 * Get this node's parent.
	 *
	 * A backwards compatibility method
	 *
	 * @deprecated since 1.9
	 * @see ContentTree::get_parent()
	 * @return ContentTree or null.
	 */
	/*	public function &getParentNode()
		{
			return $this->get_parent();
		}
	*/
	/* *
	 * Add a node to the tree
	 *
	 * A backwards compatibility method.
	 *
	 * @deprecated since 1.9
	 * @see ContentTree::add_node()
	 * @param ContentTree The node to add
	 */
	/*	public function addNode(ContentTree &$node)
		{
			return $this->add_node($node);
		}
	*/

	/**
	 * Report whether the content object for this node is cached.
	 *
	 * @return bool
	 */
	public function isContentCached() : bool
	{
		return content_cache::content_exists($this->get_tag('id'));
	}

	/**
	 * Return the content object associated with this node, after loading it
	 * if necessary, and placing it in the cache for subsequent requests.
	 *
	 * @param bool $deep load all child properties for the content object if loading is required. Default false
	 * @param bool $loadsiblings load all the siblings for the selected content object at the same time (a performance optimization) Default true
	 * @param bool $loadall If loading siblings, include inactive/disabled pages. Default false.
	 * @return ContentBase
	 */
	public function getContent($deep = false, $loadsiblings = true, $loadall = false)
	{
		$id = $this->get_tag('id');
		if (content_cache::content_exists($id)) {
			return content_cache::get_content($id);
		}
		// not yet in the cache
		if ($loadsiblings) {
			$parent = $this->get_parent(); //TODO $typer
			if ($parent) {
				$parent->getChildren($deep, $loadall);
			}
		}
		$content = ContentOperations::get_instance()->LoadContentFromId($id, $deep); //uses cache if possible
		return $content;
	}

	/**
	 * Return all node's respective content objects.
	 *
	 * @since 2.3
	 * @return array of ContentBase objects
	 */
	public function get_all_content() : array
	{
		$res = [];
		$list = global_cache::get('content_quicklist');
		$ops = ContentOperations::get_instance();
		foreach ($list as $id => &$node) {
			$res[$id] = $ops->LoadContentFromId($id); //uses cache if possible
		}
		unset($node);
		return $res;
	}

	/* *
	 * Count the number of children
	 *
	 * A backwards compatibility function
	 *
	 * @deprecated since 1.9
	 * @see ContentTree::count_children()
	 * @return int
	 */
	/*	public function getChildrenCount()
		{
			return $this->count_children();
		}
	*/
	/* *
	 * Count the number of siblings
	 *
	 * A backwards compatibility function
	 *
	 * @deprecated since 1.9
	 * @see ContentTree::count_siblings()
	 * @return int
	 */
	/*	public function getSiblingCount()
		{
			return $this->count_siblings();
		}
	*/
	/* *
	 * Get this nodes depth in the tree
	 *
	 * A backwards compatibility function
	 *
	 * @deprecated since 1.9
	 * @see ContentTree::get_level()
	 * @return int
	 */
	/*	public function getLevel()
		{
			return $this->get_level();
		}
	*/
	/**
	 * Get the children for this node.
	 *
	 * This method retrieves a list of the children of this node, optionally
	 * loading their content objects if not done before (as a performance enhancement).
	 * This method takes advantage of the content cache.
	 *
	 * @param bool $deep Load the properties of the children (only used when loadcontent is true) Default false
	 * @param bool $all Load all children, including inactive/disabled ones (only used when loadcontent is true) Default false
	 * @param bool $loadcontent Load content objects for children Default true
	 * @return mixed array of ContentTree objects | null
	 */
	public function getChildren($deep = false, $all = false, $loadcontent = true)
	{
		if ($loadcontent && !empty($this->node['children'])) {
			// check whether we need to load anything.
			$ids = [];
			foreach ($this->node['children'] as $id => &$node) {
				if (!content_cache::content_exists($id)) {
					$ids[] = $id;
				}
			}
			unset($node);

			if ($ids) {
				// load the children that aren't loaded yet.
				ContentOperations::get_instance()->LoadChildren($this->get_tag('id'), $deep, $all, $ids);
			}
		}

		$children = $this->get_children(); //TODO $typer
		return $children;
	}

	/* *
	 * Recursive node-accumulator
	 * @ignore
	 */
/*	private function &_buildFlatList() : array
	{
		$res = [];

		if ($this->get_tag('id') > 0) {
			$res[] = $this;
		}

		if ($this->has_children()) {
			$children = $this->get_children(); //no $typer
			for ($i = 0, $n = count($children); $i < $n; ++$i) {
				$res[$children[$i]->get_tag('id')] = &$children[$i];
				if ($children[$i]->has_children()) {
					$tmp = $children[$i]->_buildFlatList();
					foreach ($tmp as $key => &$node) {
						if ($key > 0) {
							$res[$key] = $node;
						}
					}
					unset($node);
				}
			}
		}

		return $res;
	}
*/
	/**
	 * Get an array of ContentTree nodes containing this node and all its descendants.
	 *
	 * @param mixed $typer since 2.3 optional data-type indicator false
	 *  or string or strings[] - tag-name(s). Default false.
	 * @return array, each member in accord with $typer
	 */
	public function getFlatList($typer = false) : array
	{
		list ($asnode,$idalias,$aliasalias,$withcontent,$tags) = $this->parse_type($typer);
		$res = [];
		$list = global_cache::get('content_quicklist');
		foreach ($list as $id => &$node) {
			$res[$id] = $this->get_node_data($node,$asnode,$idalias,$aliasalias,$withcontent,$tags);
		}
		unset($node);
		return $res;
	}
} // class

\class_alias('CMSMS\internal\ContentTree', 'cms_content_tree', false);
