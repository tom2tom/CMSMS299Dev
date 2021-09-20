<?php
/*
Classes for creating, interrogating, modifying tree-structured arrays
Copyright (C) 2018-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

namespace CMSMS
{

use ArrayTreeIterator;
use RecursiveArrayTreeIterator;
use RecursiveIteratorIterator;

/**
 * A class for creating and modifying tree-structured arrays
 *
 * @since 2.99
 * @package CMS
 * @license GPL
 */
class ArrayTree
{
    private const SELFKEY = 'name';
    private const PARENTKEY = 'parent';
    private const CHILDKEY = 'children';

    /**
     * Converts flat $data to corresponding tree-form array, using
     *  modified pre-order tree traversal (a.k.a. nested set)
     * See http://bytes.schibsted.com/building-tree-structures-in-php-using-references
     * Node-data for a parent must be present in $data before any child of that parent.
     * @param array $data      The data to be converted, each member is
     *  assoc. array with members $selfkey, $parentkey (at least)
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
                // Add a root-parented node directly to the tree
                $node['path'] = [$node[$selfkey]];
                $tree[$node[$selfkey]] = &$node;
            } elseif (isset($references[$node[$parentkey]])) {
                // Otherwise, add this node as a reference in its parent (if any)
                $parentpath = $references[$node[$parentkey]]['path'];
                $node['path'] = $parentpath + [count($parentpath)=>$node[$selfkey]];
                $references[$node[$parentkey]][$childkey][$node[$selfkey]] = &$node;
            }
            unset($node[$parentkey]);
        }
        unset($node);
        return $tree;
    }

    /**
     * Support function remove one child-data array from $array
     *
     * @param array $keys     tree-array keys
     * @param array $array    tree-array
     * @return bool indicating success
     */
    private static function removeChild(array $keys, array &$array)
    {
        while (1) {
            $index = array_shift($keys);
            if (isset($keys[0])) {
                $array = &$array[$index];
            } else {
                unset($array[$index]);
                return true;
            }
        }
        return false;
    }

    /**
     * Remove from $tree the node corresponding to $path. Inherently recursive.
     *
     * @param array $tree     tree-structured data to process
     * @param mixed $path     array, or ':'-separated string, of keys
     * @param string $childkey $tree key-identifier default self::CHILDKEY
     * @return bool indicating success
     */
    public static function drop_node(array &$tree, $path, $recurse = true,
        string $childkey = self::CHILDKEY)
    {
        $pathkeys = self::process_path($path);
        if ($pathkeys) {
            $key = array_shift($pathkeys);
            $p = [$key];
            $node = $tree[$key];
            foreach ($pathkeys as $key) {
                if (isset($node[$childkey][$key])) {
                    $p[] = $childkey; $p[] = $key;
                    $node = $node[$childkey][$key];
                } else {
                    return false;
                }
            }
            return self::removeChild($p, $tree);
        }
        return false;
    }

    /* *
     *
     * @param array $tree       Tree-structured data to process
     * @param mixed $parentname $tree key-identifier or null for the root.
     *   Other than for root node, a node with the specified 'name' property must exist.
     * @param string $parentkey $tree key-identifier default self::PARENTKEY
     */
/*  public static function attach_dangles(array &$tree, $parentname,
        string $parentkey = self::PARENTKEY)
    {
        //IS THIS POSSIBLE ?
    }
*/
    /* *
     *
     * @param array $tree       Tree-structured data to process
     * @param string $parentkey $tree key-identifier default self::PARENTKEY
     */
/*  public static function drop_dangles(array &$tree,
        string $parentkey = self::PARENTKEY)
    {
        //IS THIS POSSIBLE ?
    }
*/
    /**
     *
     * @param array $tree    Tree-structured data to process
     * @param string $getkey $tree key-identifier
     * @param mixed $getval  Value to be matched
     * @param bool  $strict  Optional flag for strict comparison, default true
     * @param string $childkey $tree key-identifier default self::CHILDKEY
     * @return mixed path-array or null if not found
     */
    public static function find(array $tree, string $getkey, $getval,
        bool $strict = true, string $childkey = self::CHILDKEY)
    {
        $iter = new RecursiveArrayTreeIterator(
                new ArrayTreeIterator($tree, 0, $childkey),
                RecursiveIteratorIterator::SELF_FIRST
                );
        foreach ($iter as $node) {
            if (isset($node[$getkey])) {
                if ($strict && $node[$getkey] === $getval) {
                    return $node['path'];
                }
                if (!$strict && $node[$getkey] == $getval) {
                    return $node['path'];
                }
            }
        }
    }

    /**
     * Process $path into clean array
     *
     * @param mixed $path array, or ':'-separated string, of node names
     *  Keys may be 'name'-property strings and/or 0-based children-index ints.
     *  The first key is for the root node.
     * @return array of node name strings, or false
     */
    public static function process_path($path)
    {
        if (is_string($path)) {
            $pathkeys = explode(':',$path);
        } elseif (is_array($path)) {
            $pathkeys = $path;
        } else {
            return false;
        }

        foreach ($pathkeys as &$key) {
            if (is_numeric($key)) {
                $key = (int)$key;
            } else {
                $key = trim($key);
            }
        }
        unset($key);
        return $pathkeys;
    }

    /**
     * Get the value of $getkey for each node of $tree which is on $path
     *
     * @param array $tree     Tree-structured data to process
     * @param mixed $path     array, or ':'-separated string, of keys
     *  Keys may be 'name'-property strings and/or 0-based children-index ints.
     *  The first key for the root node.
     * @param string $getkey   $tree key-identifier for nodes in $path
     * @param mixed $default   Optional value to return if no match found, default null
     * @param string $childkey $tree key-identifier default self::CHILDKEY
     * @return array of values, missing values null'd
     */
    public static function path_get_data(array $tree, $path, string $getkey,
        $default = null, string $childkey = self::CHILDKEY) : array
    {
        $pathkeys = self::process_path($path);
        if (!$pathkeys) {
            return []; //TODO handle error
        }

        $ret = [];
        $name = array_shift($pathkeys);
        $node = $tree[$name];
        foreach ($pathkeys as $key) {
            //TODO support index-keys too
            if (isset($node[$childkey][$key])) {
                $node = $node[$childkey][$key];
                $ret[] = $node[$getkey] ?? $default;
            } else {
                return []; //error
            }
        }
        return $ret;
    }

    /**
     * Set/add array-member $setkey=>$setval to each node of $tree which is on $path
     *
     * @param array $tree     tree-structured data to process
     * @param mixed $path     array, or ':'-separated string, of keys
     *  Keys may be 'name'-property strings and/or 0-based children-index ints.
     *  The first key for the root node.
     * @param string $setkey   $tree key-identifier
     * @param mixed $setval value to be added as $setkey=>$setval in each node of $path
     * @param string $childkey $tree key-identifier default self::CHILDKEY
     * @return boolean indicating success
     */
    public static function path_set_data(array &$tree, $path, string $setkey, $setval,
        string $childkey = self::CHILDKEY) : bool
    {
        $pathkeys = self::process_path($path);
        if (!$pathkeys) {
            return false; //TODO handle error
        }

        $name = array_shift($pathkeys);
        $node = &$tree[$name];
        foreach ($pathkeys as $key) {
            //TODO support index-keys too
            if (isset($node[$childkey][$key])) {
                $node = &$node[$childkey][$key];
                $node[$setkey] = $setval;
            } else {
                return false; //error
            }
        }
        return true;
    }

    /**
     * Get the node corresponding to an ancestor of the node represented by $path
     *
     * @param array $tree     Tree-structured data to process
     * @param mixed $path array, or ':'-separated string, of node names
     *  Keys may be 'name'-property strings and/or 0-based children-index ints.
     *  The first key is for the root node.
     * @param int $offset     Wanted path-index, 0=root node, 1=root-child, 2 ...
     *   -1=current-parent -2=current-grandparent ...
     * @param string $childkey $tree key-identifier default self::CHILDKEY
     * @return mixed array or null
     */
    public static function path_get_ancestor(array $tree, $path, int $offset,
        string $childkey = self::CHILDKEY)
    {
        $pathkeys = self::process_path($path);
        if (!$pathkeys) {
            return null;
        }
        if ($offset == 0) {
            $getpath = [reset($pathkeys)];
        } elseif ($offset < 0) {
            $c = count($pathkeys) + $offset;
            $getpath = array_slice($pathkeys, 0, $c);
            if (count($getpath) < $c) {
                return null;
            }
        } else {
            $getpath = array_slice($pathkeys, 0, $offset + 1);
            if (count($getpath) < $offset) {
                return null;
            }
        }
        return self::node_get_data($tree, $getpath, '*', null, $childkey);
    }

    /**
     *
     * @param array $tree     tree-structured data to process
     * @param mixed $path     array, or ':'-separated string, of keys
     *  Keys may be 'name'-property strings and/or 0-based children-index ints.
     *  The first key for the root node.
     * @param string $getkey   $tree key-identifier. May be '*' or 'all' or 'node'
     *  to return the whole node
     * @param mixed $default   Optional value to return if no match found, default null
     * @param string $childkey  $data key-identifier default self::CHILDKEY
     * @return mixed value | false | $default
     */
    public static function node_get_data(array $tree, $path, string $getkey,
        $default = null, string $childkey = self::CHILDKEY)
    {
        $pathkeys = self::process_path($path);
        if (!$pathkeys) {
            return false; //TODO handle error
        }

        $name = array_shift($pathkeys);
        $node = $tree[$name];
        foreach ($pathkeys as $key) {
            //TODO support index-keys too
            if (isset($node[$childkey][$key])) {
                $node = $node[$childkey][$key];
            } else {
                return false; //error
            }
        }

        if (isset($node[$getkey])) {
            if ($getkey != $childkey) {
                return $node[$getkey];
            }
            $ret = &$node[$getkey];
            return $ret;
        }
        switch ($getkey) {
            case '*':
            case 'all':
            case 'node':
                $ret = &$node;
                return $ret;
        }
        return $default;
    }

    /**
     *
     * @param array $tree     Tree-structured data to process
     * @param mixed $path     Array, or ':'-separated string, of keys
     *  Keys may be 'name'-property strings and/or 0-based children-index ints.
     *  The first key for the root node.
     * @param string $setkey   $tree key-identifier
     * @param mixed $setval value to be added as $setkey=>$setval in each node of $path
     * @param string $childkey $tree key-identifier default self::CHILDKEY
     * @return boolean indicating success
     */
    public static function node_set_data(array &$tree, $path, string $setkey, $setval,
        string $childkey = self::CHILDKEY) : bool
    {
        $pathkeys = self::process_path($path);
        if (!$pathkeys) {
            return false; //TODO handle error
        }

        $name = array_shift($pathkeys);
        $node = &$tree[$name];
        foreach ($pathkeys as $key) {
            //TODO support index-keys too
            if (isset($node[$childkey][$key])) {
                $node = &$node[$childkey][$key];
            } else {
                return false; //error
            }
        }
        if ($setkey != $childkey) {
            $node[$setkey] = $setval;
        } else {
            unset($node[$setkey]);
            $node[$setkey] = $setval;
        }
        return true;
    }

    /**
     * Child-first descendants' property-accumulator
     * @param array $node     tree-structured array to process
     * @param string $getkey  $tree key-identifier
     * @param int $moredepth optional limit on no. of levels to descend. Default no limit.
     * @param string $childkey  $data key-identifier default self::CHILDKEY
     * @return array
     */
    public static function get_descend_data(array $node, string $getkey,
        int $moredepth = -1, string $childkey = self::CHILDKEY) : array
    {
        $ret = [];
        $tree = reset($node);
        if (isset($tree[$getkey])) {
            $k = isset($tree['path']) ? implode('/', $tree['path']) : $tree['name'];
            $ret[$k] = $tree[$getkey];
        }
        if ($moredepth != 0) {
            if (!empty($tree[$childkey])) {
                foreach ($tree[$childkey] as $subtree) {
                    $down = self::get_descend_data($subtree, $getkey, $moredepth - 1, $childkey);
                    if ($down) {
                        $ret = array_merge($ret, $down);
                    }
                }
            }
        }
        return $ret;
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
    const CHILDKEY = 'children'; //a.k.a. ArrayTree::CHILDKEY
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

/**
 * A RecursiveIterator that supports getting non-leaves only
 * (ParentIterator doesn't support mode)
 */
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

    public function rewind()
    {
        parent::rewind();
        if ($this->noleaves) {
            $this->nextbranch();
        }
    }

    public function next()
    {
        parent::next();
        if ($this->noleaves) {
            $this->nextbranch();
        }
    }

    protected function nextbranch()
    {
        while ($this->valid() && !$this->getInnerIterator()->hasChildren()) {
            parent::next();
        }
    }
}

} //namespace
