<?php
#classes for creating, interrogating, modifying tree-structured arrays
#Copyright (C) 2018 The CMSMS Dev Team <coreteam@cmsmadesimple.org>
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

namespace CMSMS
{

/**
 * A class for creating and modifying tree-structured arrays
 *
 * @since 2.3
 * @package CMS
 * @license GPL
 */
class CmsArrayTree
{
	const SELFKEY = 'name';
	const PARENTKEY = 'parent';
	const CHILDKEY = 'children';

	/**
	 * @param
	 * @param string $selfkey   $data key-identifier default self::SELFKEY
	 * @param string $parentkey $data key-identifier default self::PARENTKEY
	 * @param string $childkey  $data key-identifier default self::CHILDKEY
	 * @return array
	 */
	public static function create_array($pattern,
		string $selfkey = self::SELFKEY, string $parentkey = self::PARENTKEY, string $childkey = self::CHILDKEY) : array
	{
	}

	/**
	 * Converts 'flat' $data to corresponding tree-form array, using
	 *  modified pre-order tree traversal (a.k.a. nested set)
	 * Posted at http://bytes.schibsted.com/building-tree-structures-in-php-using-references
	 * @param array $data	data to be converted, each member is assoc. array
	 *  with members $selfkey, $parentkey (at least)
	 * @param string $selfkey   $data key-identifier default self::SELFKEY
	 * @param string $parentkey $data key-identifier default self::PARENTKEY
	 * @param string $childkey  $data key-identifier default self::CHILDKEY
	 * @return array
	 */
	public static function load_array(array $data,
		string $selfkey = self::SELFKEY, string $parentkey = self::PARENTKEY, string $childkey = self::CHILDKEY) : array
	{
		$tree = [];
		$references = [];
		foreach ($data as &$node) {
			// Add the node to our associative array
			$references[$node[$selfkey]] = &$node;
			// Add empty placeholder for children
			$node[$childkey] = [];
			if (is_null($node[$parentkey])) {
   				// If it's a root node, add it directly to the tree
				$tree[$node[$selfkey]] = &$node;
			} else {
		  		// Otherwise, add this node as a reference in its parent
				$references[$node[$parentkey]][$childkey][$node[$selfkey]] = &$node;
			}
		}
		unset($node);
		return $tree;
	}

	/**
	 *
	 * @param
	 * @param
	 * @param string $selfkey   $data key-identifier default self::SELFKEY
	 * @param string $parentkey $data key-identifier default self::PARENTKEY
	 * @param string $childkey  $data key-identifier default self::CHILDKEY
	 */
	public static function attach_dangles(array &$tree, string $parentname,
		string $selfkey = self::SELFKEY, string $parentkey = self::PARENTKEY, string $childkey = self::CHILDKEY) : void
	{
	}

	/**
	 *
	 * @param
	 * @param string $selfkey   $data key-identifier default self::SELFKEY
	 * @param string $parentkey $data key-identifier default self::PARENTKEY
	 * @param string $childkey  $data key-identifier default self::CHILDKEY
	 */
	public static function drop_dangles(array &$tree,
		string $selfkey = self::SELFKEY, string $parentkey = self::PARENTKEY, string $childkey = self::CHILDKEY) : void
	{
	}

	/**
	 *
	 * @param
	 * @param
	 * @param
	 * @param string $childkey  $data key-identifier default self::CHILDKEY
	 * @return string
	 */
	public static function find(array $tree, string $getkey, $getval,
		string $childkey = self::CHILDKEY) : string
	{
		return '';
	}

	/**
	 * Get the value of $getkey for each node of $tree which is on $path
	 * @param mixed $path	  numeric-indices array, or ':'-separated string
	 *   first member/char must be 0 (for the root node)
	 * @param array $tree	  tree-structured data to process
	 * @param string $getkey   $tree key-identifier for nodes in $path
	 * @param string $childkey $tree key-identifier default self::CHILDKEY
	 * @return array of values, missing values null'd
	 */
	public static function path_get_data($path, array $tree, string $getkey, string $childkey = self::CHILDKEY) : array
	{
		if (is_string($path)) {
			$path = explode(',',$path);
		} elseif (!is_array($path)) {
			return [];
		}

		$ret = [];
		foreach ($path as $indx) {
			//TODO
		}
		return $ret;
	}

	/**
	 * Set/add array-member $setkey=>$setval to each node of $tree which is on $path
	 * @since 2.3
	 * @param mixed $path	  numeric-indices array, or ':'-separated string
	 *   first member/char must be 0 (for the root node)
	 * @param array $tree	  tree-structured data to process
	 * @param string $setkey   $tree key-identifier
	 * @param mixed $setval	value to be added as $setkey=>$setval in each node of $path
	 * @param string $childkey $tree key-identifier default self::CHILDKEY
	 * @return boolean indicating success
	 */
	public static function path_set_data($path, array &$tree, string $setkey, $setval, string $childkey = self::CHILDKEY) : bool
	{
		if (is_string($path)) {
			$path = explode(',',$path);
		} elseif (!is_array($path)) {
			return false;
		}

		foreach ($path as $indx) {
			//TODO
		}
		return false;
	}

	/**
	 *
	 * @param string $selfkey   $data key-identifier default self::SELFKEY
	 * @param string $parentkey $data key-identifier default self::PARENTKEY
	 * @param string $childkey  $data key-identifier default self::CHILDKEY
	 * @return array
	 */
	public static function node_get_data($path, array $tree, string $getkey, string $childkey = self::CHILDKEY) : array
	{
		return [];
	}

	/**
	 *
	 * @param string $selfkey   $data key-identifier default self::SELFKEY
	 * @param string $parentkey $data key-identifier default self::PARENTKEY
	 * @param string $childkey  $data key-identifier default self::CHILDKEY
	 * @return boolean indicating success
	 */
	public static function node_set_data($path, array &$tree, string $setkey, $setval, string $childkey = self::CHILDKEY) : bool
	{
		return false;
	}
} //class

} //namespace

namespace
{

/**
 * A RecursiveIterator that knows how to recurse into the array tree
 */
class ArrayTreeIterator extends RecursiveArrayIterator implements RecursiveIterator
{
	const CHILDKEY = 'children';
	protected $flags;
	protected $childkey;

	public function __construct($array = [], int $flags = 0, string $childkey = self::CHILDKEY)
	{
		parent::__construct($array, $flags | RecursiveArrayIterator::CHILD_ARRAYS_ONLY);
		$this->flags = $flags;
		$this->childkey = $childkey;
	}

	public function getChildren() : self
	{
		return new static($this->current()[$this->childkey], $this->flags, $this->childkey);
	}

	public function hasChildren() : bool
	{
		return !empty($this->current()[$this->childkey]);
	}
}

/* *
 * A RecursiveIterator that supports getting non-leaves only
 */
/* see FilterIterator
class RecursiveArrayTreeIterator extends RecursiveIteratorIterator implements OuterIterator
{
	const NONLEAVES_ONLY = 16384;
	protected $noleaves;

	public function __construct(Traversable $iterator, int $mode = RecursiveIteratorIterator::LEAVES_ONLY, int $flags = 0)
	{
		if ($mode & self::NONLEAVES_ONLY) {
            $this->noleaves = true;
            $mode &= ~self::NONLEAVES_ONLY;
		} else {
            $this->noleaves = false;
		}
		parent::__construct($iterator, $mode, $flags);
	}

	public function rewind() : void
	{
		parent::rewind();
		if ($this->noleaves) {
			$this->nextbranch();
		}
	}

	public function next() : void
	{
		parent::next();
		if ($this->noleaves) {
			$this->nextbranch();
		}
	}

	protected function nextbranch() : void
	{
		while ($this->valid() && !$this->getInnerIterator()->hasChildren()) {
			parent::next();
		}
	}
}
*/

} //namespace
