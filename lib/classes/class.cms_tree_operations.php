<?php
# cms_tree_operations - a tree-populator class
# Copyright (C) 2010-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

/**
 * A utility class to provide functions to populate a tree
 * @package CMS
 * @license GPL
 */

/**
 * A utility class to provide functions to populate a tree
 *
 * @package CMS
 * @license GPL
 * @author  Robert Campbell
 *
 * @since 1.9
 */
class cms_tree_operations
{
  // static properties here >> StaticProperties class ?
  /**
   * @ignore
   */
  private static $_keys;

  /**
   * Add a unique key to the key index
   *
   * @internal
   * @access private
   * @param string key to add
   */
  public static function add_key(string $key)
  {
    if( !is_array(self::$_keys) ) self::$_keys = [];
    if( !in_array($key,self::$_keys) ) self::$_keys[] = $key;
  }


  /**
   * Load content tree from a flat array (from SysDataCache 'content_flatlist')
   *
   * This method uses recursion to load the tree.
   *
   * @internal
   * @param array The data to import, a row for each page, with members
   *  content_id, parent_id, item_order, content_alias, active - all strings
   * @return cms_content_tree
   */
  public static function load_from_list(array $data)
  {
      // create a tree object
      $tree = new cms_content_tree();
//      $sorted = [];

      for( $i = 0, $n = count($data); $i < $n; $i++ ) {
          $row = $data[$i];

          // create new node.
          $node = new cms_content_tree(['id'=>$row['content_id'],'alias'=>$row['content_alias'],'active'=>$row['active']]);

          // find where to insert it.
          $parent_node = null;
          if( $row['parent_id'] < 1 ) {
              $parent_node = $tree;
          }
          else {
              $parent_node = $tree->find_by_tag('id',$row['parent_id'],FALSE,FALSE); //no id quick-finds possiblke yet
              if( !$parent_node ) {
                  // ruh-roh
                  throw new RuntimeException('Problem with internal content organization... could not get a parent node for content with id '.$row['content_id']);
              }
          }

          // add it.
          $parent_node->add_node($node);
      }
      return $tree;
  }
} // class

