<?php
/*
Navigator module utilities class
Copyright (C) 2013-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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
namespace Navigator;

use CMSMS\AppParams;
use CMSMS\Lone;
use function cmsms;
use function CMSMS\specialize;
use function startswith;

final class Utils
{
    // static properties here >> Lone property|ies ?
    private static $_excludes;

    #[\ReturnTypeWillChange]
    private function __construct() {}
    #[\ReturnTypeWillChange]
    private function __clone() {}

    public static function set_excludes($data)
    {
        if( is_string($data) ) $data = explode(',',$data);
        if( $data ) {
            foreach( $data as &$one ) {
                $one = trim($one);
            }
            unset($one);
            $data = array_unique($data);
            self::$_excludes = $data;
        }
    }

    public static function clear_excludes()
    {
        self::$_excludes = null;
    }

    public static function is_excluded($alias)
    {
        if( !self::$_excludes || !is_array(self::$_excludes) ) return FALSE;
        foreach( self::$_excludes as $one ) {
            if( startswith($alias,$one) ) {
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
     * Populate a navigation-node for the specified tree-node, and ditto
     * for the latter's descendants (if any), to be used for generating
     * navigation-item(s)
     *
     * @param mixed $node HierarchyManager or Tree object
     * @param bool $extend whether to also grab some 'non-core' (and not
     *  particularly hierarchy-related) properties of content-objects:
     *  extra1, extra2, extra3, image, target, thumbnail 
     * @param int  $nlevels  maximum recursion depth
     * @param bool $show_all whether to process items flagged as not show-in-menu
     * @param bool $collapse whether to definitely skip child-node processing. Default false
     * @param int  $depth current recursion level, 0-based, for internal use
     * @return mixed Navigator\Node | null
     */
    public static function fill_node($node,$extend,$nlevels,$show_all,$collapse = FALSE,$depth = 0)
    {
        if( !is_object($node) ) return;

        $content = $node->getContent($extend);
        if( is_object($content) ) {
            if( !$content->Active() ) return;
            if( !$content->ShowInMenu() && !$show_all ) return;

            $obj = new Node();
            $obj->accesskey = $content->AccessKey();
            $obj->alias = $content->Alias();
            $obj->children = []; // Tree object(s)
            $obj->children_exist = FALSE; // whether child-page(s) recorded in cache now
            $obj->has_children = FALSE; // whether recursion pending (various tests passed)
            $obj->parent = FALSE; // whether this content represents an ancestor of the currently-requested page
            $obj->created = $content->GetCreationDate();
            $obj->current = FALSE; // whether this content represents the currently-requested page
            $obj->default = $content->DefaultContent(); // this content represents the default page
            $obj->depth = $depth+1;
            $obj->hierarchy = $content->Hierarchy();
            $obj->id = $content->Id();
            $obj->raw_menutext = $content->MenuText();
            $obj->menutext = specialize($obj->raw_menutext);
            $obj->modified = $content->GetModifiedDate();
            $obj->name = $content->Name();
            $obj->title = $content->Name(); //c.f. ->name, ->titleattribute
            $obj->titleattribute = $content->TitleAttribute();
            $obj->tabindex = $content->TabIndex();
            $obj->type = strtolower($content->Type());
            $obj->url = $content->GetURL();

            $cur_content_id = cmsms()->get_content_id();
            if( $obj->id == $cur_content_id ) {
                $obj->current = TRUE;
            }
            else {
                $tmp_node = $node->find_by_tag('id',$cur_content_id);
                while( $tmp_node ) { //iterate up the tree
                    if( $tmp_node->get_tag('id') == $obj->id ) {
                        $obj->parent = TRUE;
                        break;
                    }
                    $tmp_node = $tmp_node->get_parent();
                }
            }
            // are we able to recurse [further]?
            $flag = ($nlevels < 0 || $depth + 1 < $nlevels) &&
                   (!$collapse || $obj->parent || $obj->current);
            if( $flag ) {
                $flag = false;
                if( $node->has_children() ) {
                    $children = $node->getChildren(false,$show_all,false,false);
                    if( $children ) {
                        $flag = true; // we can keep going
                        $cache = Lone::get('SystemCache');
                        foreach( $children as $id ) {
                            if( $cache->has($id,'tree_pages') ) {
                                $obj->children_exist = TRUE;
                                break;
                            }
                        }
                    }
                }
            }
            if( $flag ) {
                $obj->has_children = TRUE;
                $child_nodes = [];
                foreach( $children as $id ) {
                    if( self::$_excludes ) {
                        $alias = $node->get_tag('alias', $id);
                        if( self::is_excluded($alias) ) {
                            continue;
                        }
                    }
                    $child = $node->get_node_by_id($id);
                    $tmp = self::fill_node($child,$extend,$nlevels,$show_all,$collapse,$depth+1); //recurse
                    if( is_object($tmp) ) {
                        $child_nodes[] = $tmp;
                    }
                }
                if( $child_nodes ) {
                    $obj->children = $child_nodes;
                }
            }

            if( $extend ) {
                $obj->extra1 = $content->GetPropertyValue('extra1');
                $obj->extra2 = $content->GetPropertyValue('extra2');
                $obj->extra3 = $content->GetPropertyValue('extra3');
                if( $content->HasProperty('target') ) {
                    $obj->target = $content->GetPropertyValue('target');
                }
                else {
                    $obj->target = '';
                }
                $tmp = $content->GetPropertyValue('image');
                if( !empty($tmp) && $tmp != -1 ) {
                    $config = Lone::get('Config');
                    $url = AppParams::get('content_imagefield_path').'/'.$tmp;
                    if( !startswith($url,'/') ) $url = '/'.$url;
                    $obj->image = $config['image_uploads_url'].$url;
                }
                else {
                    $obj->image = FALSE;
                }
                $tmp = $content->GetPropertyValue('thumbnail');
                if( !empty($tmp) && $tmp != -1 ) {
                    if (!isset($config)) { $config = Lone::get('Config'); }
                    $url = AppParams::get('content_thumbnailfield_path').'/'.$tmp;
                    if( !startswith($url,'/') ) $url = '/'.$url;
                    $obj->thumbnail = $config['image_uploads_url'].$url;
                }
                else {
                    $obj->thumbnail = FALSE;
                }
            }
            return $obj;
        }
    }
} // class

// defined data structure to support PHP optimisation
class Node
{
    public $accesskey;
    public $alias;
    public $children_exist; // whether child-page(s) recorded in cache now
    public $children; // Tree object(s) []
    public $created; // datetime
    public $current; // whether this content represents the currently-requested page
    public $default; // whether this content represents the default page
    public $depth;
    public $has_children; // whether recursion is pending (various tests passed)
    public $hierarchy;
    public $id;
    public $menutext; // specialize()'d raw_menutext
    public $modified; // datetime
    public $name;
    public $parent; // whether this content represents an ancestor of the currently-requested page
    public $raw_menutext;
    public $tabindex;
    public $title; //same as $name c.f. $titleattribute
    public $titleattribute;
    public $type; // lower-cased content-type
    public $url;
    // extend-only properties
    public $extra1;
    public $extra2;
    public $extra3;
    public $image;
    public $target;
    public $thumbnail;
}
