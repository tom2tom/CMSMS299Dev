<?php
/*
Simple tree class
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

/**
 * A tree / tree-node class that allows storing data with each node.
 *
 * @package CMS
 * @author  Robert Campbell
 *
 * @since 2.99
 * @since 1.9 as global-namespace cms_tree
 */
class Tree
{
	/**
	 * @ignore
	 */
	private $_parent;

	/**
	 * @ignore
	 */
	private $_tags;

	/**
	 * @ignore
	 */
	private $_children;

	/**
	 * Construct a new tree, or node of a tree.
	 *
	 * @param string $key An optional key for a tag. Default null.
	 * @param mixed  $value An optional value for the tag. Default ''.
	 */
	public function __construct($key = null,$value = '')
	{
		if( $key ) {
			if( is_string($key) ) {
				$this->set_tag($key,$value);
			}
			else if( is_array($key) ) {
				foreach( $key as $k => $v ) {
					$this->set_tag($k,$v);
				}
			}
		}
	}

	/**
	 * Recursively find a tree node matching a name/type and value.
	 *
	 * @param string $tag_name The tag name to search for
	 * @param mixed  $value The tag value to search for
	 * @param bool $case_insensitive Whether the (string) value should matched regardless of case. Default false.
	 * @return Tree object or null on failure.
	 */
	public function find_by_tag($tag_name,$value,$case_insensitive = FALSE)
	{
		$res = null;
		if( !is_string($tag_name) ) return $res;
		if( !is_string($value) ) $case_insensitive = FALSE;

		if( $this->_tags ) {
			if( isset($this->_tags[$tag_name]) ) {
				if( $case_insensitive ) {
					if( strcasecmp($this->_tags[$tag_name], $value) == 0 ) {
						return $this;
					}
				}
				else if( $this->_tags[$tag_name] == $value ) {
					return $this;
				}
			}
		}

		if( $this->has_children() ) {
			for( $i = 0, $n = count($this->_children); $i < $n; $i++ ) {
				$tmp = $this->_children[$i]->find_by_tag($tag_name,$value,$case_insensitive);
				if( $tmp ) {
					return $tmp;
				}
			}
		}

		return $res;
	}

	/**
	 * Test if this node has children.
	 *
	 * @return bool
	 */
	public function has_children()
	{
		return !empty($this->_children);
	}

	/**
	 * Set a tag value into this node
	 *
	 * @param string $key Tag name
	 * @param mixed  $value Tag value
	 */
	public function set_tag($key,$value)
	{
		if( !$this->_tags ) $this->_tags = [];
		$this->_tags[$key] = $value;
	}

	/**
	 * Retrieve a tag for this node.
	 *
	 * @param string $key The tag name
	 * @return mixed The tag value, or null
	 */
	public function get_tag($key)
	{
		$res = null;
		if( !$this->_tags ) return $res;
		if( !isset($this->_tags[$key]) ) return $res;
		$res = $this->_tags[$key];
		return $res;
	}

	/**
	 * Remove the specified node from the tree.
	 *
	 * Search through the children of this node for the specified node.
	 * If found, remove it. Optionally, all its its descendants too.
	 *
	 * Use this method with caution, as it is very easy to break your tree,
	 * corrupt memory and have tree nodes hanging out there with no parents.
	 *
	 * @param Tree $node Reference to the node to be removed.
	 * @param bool $search_children Whether to recursively remove decendants. Default false.
	 * @return bool
	 */
	protected function remove_node(Tree &$node, $search_children = false)
	{
		if( !$this->has_children() ) return FALSE;

		for( $i = 0, $n = count($this->_children); $i < $n; $i++ ) {
			if( $this->_children[$i] == $node ) {
				// item found.
				unset($this->_children[$i]);
				$this->_children = @array_values($this->_children);
				return TRUE;
			}
			elseif ($search_children && $this->_children[$i]->has_children()) {
				$res = $this->_children[$i]->remove_node($node,$search_children);
				if( $res ) return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * Remove this node from the tree. Not its descendants, if any.
	 *
	 * Use this method with caution, as it will break the tree if there are descendant(s).
	 *
	 * @return bool
	 */
	public function remove()
	{
		if( is_null($this->_parent) ) return FALSE;
		return $this->_parent->remove_node($this);
	}

	/**
	 * Get a reference to the parent node of this one.
	 * @since 2.0
	 *
	 * @return Tree Reference to the parent node, or null.
	 */
	public function get_parent()
	{
		return $this->_parent;
	}

	/**
	 * Get a reference to the parent node.
	 * @deprecated since 2.0 use get_parent()
	 *
	 * @return Tree Reference to the parent node, or null.
	 */
	public function getParent()
	{
		return $this->_parent;
	}

	/**
	 * Add the specified node as a child of this node.
	 *
	 * @param Tree $node The node to add
	 */
	public function add_node(Tree &$node)
	{
		if( !is_array($this->_children) ) $this->_children = [];

		for( $i = 0, $n = count($this->_children); $i < $n; $i++ ) {
			if( $this->_children[$i] == $node ) return FALSE;
		}
		$node->_parent = $this;
		$this->_children[] = $node;
	}

	/**
	 * Count the number of direct children of this node.
	 *
	 * @return int
	 */
	public function count_children()
	{
		if( $this->has_children() ) return count($this->_children);
		return 0;
	}

	/**
	 * Count the number of siblings of this node.
	 *
	 * @return int
	 */
	public function count_siblings()
	{
		if( $this->_parent ) return $this->_parent->count_children();
		return 1;
	}

	/**
	 * Count the total number of all nodes, including this one.
	 *
	 * @return int
	 */
	public function count_nodes()
	{
		$n = 1;
		if( $this->has_children() ) {
			foreach( $this->_children as &$one ) {
				$n += $one->count_nodes();
			}
		}
		return $n;
	}

	/**
	 * Find the depth of the current node.
	 *
	 * This method counts all of the parents in the tree until there are no more parents.
	 *
	 * @return int
	 */
	public function get_level()
	{
		$n = 1;
		$node = $this;
		while( $node->_parent ) {
			$n++;
			$node = $node->_parent;
		}
		return $n;
	}

	/**
	 * Return the children of this node.
	 *
	 * @return mixed  array of Tree objects | null if there are no children.
	 */
	public function &get_children()
	{
		if( $this->has_children() ) return $this->_children;
		$res = null;
		return $res;
	}
} // class
