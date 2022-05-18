<?php
/*
TreeOperations - a tree-populator class
Copyright (C) 2010-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS;

use CMSMS\ContentTree;
use RuntimeException;

/**
 * A utility class of methods to populate a data tree
 *
 * @package CMS
 * @license GPL
 * @author  Robert Campbell
 *
 * @since 3.0
 * @since 1.9 as global-namespace cms_tree_operations
 */
class TreeOperations
{
    /* *
     * @ignore
     */
//    private static $_keys;

    /* *
     * Add a unique key to the key index
     * Unused across the core, and useless - any recorded key is never used or usable
     * @deprecated since 3.0 Does nothing
     * @ignore
     *
     * @param string key to add
     */
    public static function add_key(string $key)
    {
/*        if( !is_array(self::$_keys) ) {
            self::$_keys = [];
        }
        if( !in_array($key,self::$_keys) ) {
            self::$_keys[] = $key;
        }
*/
    }

    /**
     * Recursively populate the (single) content tree from a flat array
     * i.e. LoadedData 'content_flatlist'
     *
     * @internal
     * @param array The data to import, a row for each page, with members
     *  content_id, parent_id, content_alias, active - all strings
     * @return ContentTree representing the topmost/root node
     */
    public static function load_from_list(array $data)
    {
        // create the root-node object.
        $tree = new ContentTree();

        for( $i = 0, $n = count($data); $i < $n; $i++ ) {
            $row = $data[$i];

            // create page node.
            $node = new ContentTree(['id'=>$row['content_id'],'alias'=>$row['content_alias'],'active'=>$row['active']]);

            // find where to insert it.
            if( $row['parent_id'] < 1 ) {
                $parent_node = $tree;
            }
            else {
                //no id quick-finds possible yet, so a clunky top-down walk to find it!
                $parent_node = $tree->find_by_tag('id',$row['parent_id'],FALSE,FALSE);
                if( !$parent_node ) {
                    // ruh-roh
                    throw new RuntimeException('Problem with internal content organization... could not get a parent node for content with id '.$row['content_id']);
                }
            }

            // insert it.
            $parent_node->add_node($node);
            unset($parent_node); // garbage-collector assistance
        }
        $parent_node = null; // garbage-collector assistance
        return $tree;
    }
} // class
