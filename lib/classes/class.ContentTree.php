<?php
/*
A caching tree for CMSMS content objects
Copyright (C) 2010-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS;

use CMSMS\DeprecationNotice;
use CMSMS\SingleItem;
use CMSMS\Tree;
use const CMS_DEPREC;

/**
 * Class that provides content retrieval abilities, using the content cache
 * where possible. It also supports a tiny subset of the old Tree class used
 * in CMSMS versions prior to 1.9, for backward compatibility.
 *
 * @since 2.99
 * @since 1.9 as global-namespace cms_content_tree
 * @package CMS
 * @license GPL
 * @author  Robert Campbell
 */
class ContentTree extends Tree
{
	/**
	 * @ignore
	 */
	protected $cache = null;

	/**
	 * Find a tree node given a tag-type and its value.
	 *
	 * @param string $tag_name The tag name/type to search for
	 * @param mixed  $value The tag value to search for
	 * @param bool $case_insensitive Whether the value should be treated as case insensitive.
	 * @param bool $usequick Optionally, when searching by id... use the quickfind method if possible.
	 * @return mixed Tree | null
	 */
	public function find_by_tag($tag_name,$value,$case_insensitive = false,$usequick = true)
	{
		if( $tag_name == 'id' ) $case_insensitive = true;
		if( $usequick && $tag_name == 'id' /*&& $case_insensitive == false*/ && ($this->get_parent() == null || $this->get_tag('id') == '') ) {
			$res = $this->quickfind_node_by_id($value);
			return $res;
		}
		return parent::find_by_tag($tag_name,$value,$case_insensitive); //go walk the nodes-tree
	}

	/**
	 * Retrieve from cache the node for a page id.
	 * Method replicated in ContentOperations class
	 *
	 * @param int $id The page id
	 * @return mixed ContentTree | null
	 */
	public function quickfind_node_by_id($id)
	{
		$list = SingleItem::LoadedData()->get('content_quicklist');
		if( isset($list[$id]) ) return $list[$id];
	}

	/**
	 * Return the node (if any) or numeric id of such node, corresponding to the
	 * supplied identifier, which is assumed to be a numeric id or alias string.
	 *
	 * @since 2.99
	 * @param mixed  $a     The identifier to search for
	 * $param bool   $typer Optional return-type indicator. Default true.
	 *  False to return the numeric identifier.
	 * @return mixed int | false | ContentTree
	 */
	public function find_by_identifier($a, bool $typer = true)
	{
		if( is_numeric($a) && $a >= 0 ) {
			$res = $this->quickfind_node_by_id((int)$a);
			if( $res ) {
				return ($typer) ? $res : (int)$a;
			}
		}
		$res = $this->find_by_tag('alias',$a);
		if( $res ) {
			return ($typer) ? $res : $res->get_tag('id');
		}
		return false;
	}

	/* *
	 * Retrieve a node by its id.
	 * A backwards compatibility method.
	 *
	 * @deprecated since 1.9 use quickfind_node_by_id()
	 * @param int $id
	 * @return ContentTree
	 */
/*	public function sureGetNodeById($id)
	{
		return $this->quickfind_node_by_id($id);
	}
*/
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
		return $this->find_by_tag('id',$id);
	}
*/
	/**
	 * Retrieve a node by its alias
	 *
	 * A backwards compatibility method
	 *
	 * @deprecated since 1.9 use find_by_identifier()
	 * @param mixed $alias null|bool|int|string
	 * @return ContentTree
	 */
	public function sureGetNodeByAlias($alias)
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method','find_by_identifier'));
		return $this->find_by_identifier($alias);
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
		return $this->find_by_tag('alias',$alias,true);
	}
*/
	/**
	 * Retrieve a node by hierarchy position.
	 *
	 * @param string $position
	 * @return ContentTree or null.
	 */
	public function getNodeByHierarchy($position)
	{
		$id = SingleItem::ContentOperations()->GetPageIDFromHierarchy($position);
		if( $id ) $result = $this->quickfind_node_by_id($id);
		else $result = null;
		return $result;
	}

	/* *
	 * Test if this node has children.
	 *
	 * A backwards compatibility method.
	 *
	 * @deprecated since 1.9
	 * @see Tree:has_children()
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
	 * @see Tree::set_tag()
	 * @param string $key The tag name/key
	 * @param mixed  $value The tag value
	 */
/*	public function setTag(string $key,$value)
	{
		return $this->set_tag($key,$value);
	}
*/
	/**
	 * Get this node's id.
	 *
	 * A backwards compatibility method
	 *
	 * @deprecated since 1.9
	 * @see Tree::get_tag()
	 * @return int The node id.
	 */
	public function getId()
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method','get_tag(id'));
		return $this->get_tag('id');
	}

	/* *
	 * Get a node tag.
	 *
	 * A backwards compatibility method
	 *
	 * @deprecated since 1.9
	 * @see Tree::get_tag()
	 * @param  string $key Tag name/key
	 * @return mixed Node tag value | null
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
	 * @see Tree::get_parent()
	 * @return Tree or null.
	 */
/*	public function getParentNode()
	{
		return $this->getParent();
	}
*/
	/* *
	 * Add a node to the tree
	 *
	 * A backwards compatibility method.
	 *
	 * @deprecated since 1.9
	 * @see Tree::add_node()
	 * @param ContentTree The node to add
	 */
/*	public function addNode(ContentTree &$node)
	{
		return $this->add_node($node);
	}
*/
	/**
	 * Return the content object associated with this node, after loading it
	 * if necessary, and placing it in the cache for subsequent requests.
	 *
	 * @param bool $deep load all child properties for the content object if loading is required. Default false
	 * @param bool $loadsiblings load all the siblings for the selected content object at the same time (a performance optimization) Default true
	 * @param bool $loadall If loading siblings, include inactive/disabled pages. Default false.
	 * @return mixed CMSMS\ContentBase-derived object | null
	 */
	public function getContent($deep = false,$loadsiblings = true,$loadall = false)
	{
		$id = $this->get_tag('id');
		if( !$this->cache ) { $this->cache = SingleItem::SystemCache(); }
		if( !$this->cache->has($id,'tree_pages') ) {
			// not in cache
			$parent = $this->getParent();
			if( !$loadsiblings || !$parent ) {
				// only load this content object
				return SingleItem::ContentOperations()->LoadContentFromId($id, $deep);  //TODO ensure relevant content-object?
			}
			else {
				$parent->getChildren($deep,$loadall);
				if( $this->cache->has($id,'tree_pages') ) {
					return $this->cache->get($id,'tree_pages');
				}
			}
		}
		return SingleItem::ContentOperations()->LoadContentFromId($id, $deep);  //TODO ensure relevant content-object?
	}

	/* *
	 * Count the number of children
	 *
	 * A backwards compatibility function
	 *
	 * @deprecated since 1.9
	 * @see Tree::count_children()
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
	 * @see Tree::count_siblings()
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
	 * @see Tree::get_level()
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
	 * This method will retrieve a list of the children of this node, loading
	 * their content objects at the same time (as a performance enhancement).
	 *
	 * This method takes advantage of the content cache.
	 *
	 * @param bool $deep Optionally load the properties of the children (only used when loadcontent is true)
	 * @param bool $all Load all children, including inactive/disabled ones (only used when loadcontent is true)
	 * @param bool $loadcontent Load content objects for children
	 * @return Array of Tree objects.
	 */
	public function &getChildren($deep = false,$all = false,$loadcontent = true)
	{
		$children = $this->get_children();
		if( is_array($children) && count($children) && $loadcontent ) {
			// check to see if we need to load anything.
			$ids = [];
			for( $i = 0, $n = count($children); $i < $n; $i++ ) {
				if( !$children[$i]->isContentCached() ) $ids[] = $children[$i]->get_tag('id');
			}

			if( $ids ) {
				// load the children that aren't loaded yet.
				SingleItem::ContentOperations()->LoadChildren($this->get_tag('id'),$deep,$all,$ids);
			}
		}

		return $children;
	}

	/**
	 * Recursive node-accumulator
	 * @ignore
	 */
	private function &_buildFlatList() : array
	{
		$result = [];

		if( $this->get_tag('id') > 0 ) $result[] = $this;
		if( $this->has_children() ) {
			$children = $this->get_children();
			for( $i = 0, $n = count($children); $i < $n; $i++ ) {
				$result[$children[$i]->get_tag('id')] =& $children[$i];
				if( $children[$i]->has_children() ) {
					$tmp = $children[$i]->_buildFlatList();
					foreach( $tmp as $key => $node ) {
						if( $key > 0 ) $result[$key] = $node;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Get an array of Tree nodes containing this node and all its descendants.
	 *
	 * @return array of Tree nodes.
	 */
	public function &getFlatList()
	{
		// static properties here >> SingleItem property|ies ?
		static $result = null;
		if( is_null($result) ) {
			$result = $this->_buildFlatList();
		}
		return $result;
	}

	/**
	 * Report whether the content object for this node is cached.
	 *
	 * @return bool
	 */
	public function isContentCached()
	{
		if( !$this->cache ) $this->cache = SingleItem::SystemCache();
		return $this->cache->has($this->get_tag('id'),'tree_pages');
	}

	/**
	 * Get the (estimated) hierarchy position of this node.
	 *
	 * @return mixed int | null
	 */
	private function _getPeerIndex()
	{
		// find index of current node among its peers.
		$parent = $this->get_parent();
		if( $parent ) {
			$obj = $this->get_tag('id');
			$children = $parent->get_children();
			for( $i = 0, $n = count($children); $i < $n; $i++ ) {
				if( $children[$i]->get_tag('id') == $obj) return $i+1;
			}
		}
	}

	/**
	 * @ignore
	 */
	private function _getHierarchyArray() : array
	{
		$result = [$this->_getPeerIndex()];
		$parent = $this->get_parent();
		if( $parent ) {
			$out = $parent->_getHierarchyArray();
			if( $out ) $result = array_merge($result,$out);
		}
		return $result;
	}

	/**
	 * Get the hierarchy position of this node
	 *
	 * @return string
	 */
	public function getHierarchy()
	{
		if( ($hier = $this->get_tag('hierarchy')) != '' ) return $hier;

		$list = $this->_getHierarchyArray();
		foreach( $list as &$one ) {
			$one = sprintf('%03d',$one); //max 999 since 2.99, was 99999
		}
		unset($one);
		$out = implode('.',array_reverse(array_splice($list,0,-1)));
		$this->set_tag('hierarchy',$out);

		return $out;
	}
} // class
