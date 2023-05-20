<?php
/*
Navigator module utilities singleton class
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
//use Navigator\Node;
//use Navigator\NodeStatic;
use CMSMS\AppConfig;
use CMSMS\AppParams;
use CMSMS\Lone;
use CMSMS\PageLoader;
use RuntimeException;
use const CMS_DB_PREFIX;
use const CMS_ROOT_URL;
use function cms_to_stamp;
use function cmsms;
use function CMSMS\specialize;
use function debug_display;
use function startswith;

require_once __DIR__.DIRECTORY_SEPARATOR.'class.Node.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'class.NodeStatic.php';

final class Utils
{
    // static properties here >> Lone property|ies ?
    private static $excludes;

    private function __construct() {}
    #[\ReturnTypeWillChange]
    private function __clone() {}// : void {}

    public static function set_excludes($data)
    {
        if( is_string($data) ) $data = explode(',',$data);
        if( $data ) {
            foreach( $data as &$one ) {
                $one = trim($one);
            }
            unset($one);
            $data = array_unique($data);
            self::$excludes = $data;
        }
    }

    public static function clear_excludes()
    {
        self::$excludes = [];
    }

    public static function is_excluded($alias)
    {
        if( !self::$excludes || !is_array(self::$excludes) ) return FALSE;
        foreach( self::$excludes as $one ) {
            if( startswith($alias,$one) ) {
                return TRUE;
            }
        }
        return FALSE;
    }

    /* *
     * Populate a navigation-node for the specified tree-node (if active),
     * and ditto for the node's active descendants (if any),
     * to be used for generating navigation-item(s)
     *
     * @param PageTreeNode $node
     * @param bool $extend whether to also grab some 'non-core' (and not
     *  necessarily navigation-related??) properties of content-objects:
     *  extra1, extra2, extra3, image, thumbnail
     * @param int  $nlevels  maximum recursion depth. Might be 0 or -1 (== no limit).
     * @param bool $show_all whether to process items regardless of their 'show-in-menu' status
     * @param bool $collapse whether to definitely skip child-node processing. Default false
     * @param int  $depth current recursion level, 0-based, for internal use
     * @return mixed Node | null | not at all
     * @throws RuntimeException upon failure to retrieve the content-object for $node
     */
/*
    public static function fill_node($node,$extend,$nlevels,$show_all,$collapse = FALSE,$depth = 0)
    {
        static $ptops = null;

        if( !is_object($node) ) return;
        $content = $node->get_content($extend);
        if( is_object($content) ) {
            if( !$content->Active() ) return; //TODO also consider $show_all?
            if( !($show_all || $content->ShowInMenu()) ) return;

            $obj = new NodeStatic();
            $obj->accesskey = $content->AccessKey();
            $obj->alias = $content->Alias();
            $obj->children = []; // PageTreeNode object(s)
            $obj->children_exist = FALSE; // whether child-page(s) recorded in cache now
            $obj->has_children = FALSE; // whether recursion pending (various tests passed)
            $obj->parent = FALSE; // whether this content represents an ancestor of the currently-requested page
            $obj->created = $content->GetCreationDate();
            $obj->current = FALSE; // whether this content represents the currently-requested page
            $obj->default = $content->DefaultContent(); // this content represents the default page
            $obj->depth = $depth+1; // TODO 0-based?
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
                if( !$ptops ) {
                    $ptops = cmsms()->GetHierarchyManager(); // OR Lone::get('PageTreeOperations');
                }
                $tmp_node = $ptops->get_node_by_id($cur_content_id);
                while( $tmp_node ) { //iterate up the tree
                    if( $tmp_node->getId() == $obj->id ) {
                        $obj->parent = TRUE;
                        break;
                    }
                    $tmp_node = $tmp_node->get_parent();
                }
            }
        }
        else {
            //TODO handle error
            throw new RuntimeException('');
        }
        // are we able to recurse [further]?
        $flag = ($nlevels < 0 || $depth + 1 < $nlevels) &&
               (!$collapse || $obj->parent || $obj->current);
        if( $flag ) {
            $flag = FALSE;
            if( $node->has_children() ) {
                $children = $node->load_children(FALSE,$show_all,FALSE,FALSE); // OR ->get_children(false, $node->getId()) ?
                if( $children ) {
                    $flag = TRUE; // we can keep going
/ *                  $cache = Lone::get('SystemCache');
                    foreach( $children as $id ) {
                        if( $cache->has($id,'site_pages') ) {
                            $obj->children_exist = TRUE;
                            break;
                        }
                    }
* /
                }
            }
        }

        if( $flag ) {
            $obj->has_children = TRUE;
            $child_nodes = [];
            foreach( $children as $id ) {
                if( self::$excludes ) {
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
            $values = $content->GetPropertyValue(['extra1', 'extra2', 'extra3', 'target', 'image', 'thumbnail']);
            $obj->extra1 = $values['extra1'];
            $obj->extra2 = $values['extra2'];
            $obj->extra3 = $values['extra3'];
            $obj->target = $values['target'] ?: ''; //default empty, hence '_self'
            $tmp = $values['image'];
            if( $tmp && $tmp != -1 ) {
                $part = AppParams::get('content_imagefield_path');
                if( $part ) {
                    $part = rawurlencode(trim($part,' \\/'));
                    $part = strtr($part,['%2F'=>'/','%5C'=>'/']);
                    $url = $part.'/'.$tmp;
                }
                else {
                    $url = $tmp;
                }
                if( !startswith($url,'/') ) $url = '/'.$url;
                $config = Lone::get('Config');
                $obj->image = $config['image_uploads_url'].$url;
            }
            else {
                $obj->image = FALSE;
            }
            $tmp = $values['thumbnail'];
            if( $tmp && $tmp != -1 ) {
                $part = AppParams::get('content_thumbnailfield_path');
                if( $part ) {
                    $part = rawurlencode(trim($part,' \\/'));
                    $part = strtr($part,['%2F'=>'/','%5C'=>'/']);
                    $url = $part.'/'.$tmp;
                }
                else {
                    $url = $tmp;
                }
                if( !startswith($url,'/') ) $url = '/'.$url;
                if (!isset($config)) { $config = Lone::get('Config'); }
                $obj->thumbnail = $config['image_uploads_url'].$url;
            }
            else {
                $obj->thumbnail = FALSE;
            }
        }
        else {
            //@digi recommendation: always retrieve extended-property 'target'
            //which operationally implies $extend is true always
            $obj->target = $content->GetPropertyValue('target');
        }
        return $obj;
    }
*/
    /**
     * Populate runtime navigation data for the specified node (if active),
     * and ditto for the node's active descendants (if any), to be used,
     * along with corresponding NodeStatic data, for generating navigation-item(s)
     *
     * @param int  $id enumerator of the, or the topmost, Node to process
     * @param int  $nlevels  maximum recursion depth. Might be 0 or -1 (== no limit).
     * @param bool $show_all whether to process items regardless of their 'show-in-menu' status
     * @param bool $collapse whether to definitely skip child-node processing. Default false
     * @param int  $depth current recursion level, 0-based, for internal use
     * @return array Node objects keyed by their numeric id | empty
     * @throws RuntimeException upon failure to find the enumerated node in the cache
     */
    public static function fill_context($id,$nlevels,$show_all,$collapse = FALSE,$depth = 0)
    {
        static $cache = null;
        static $ptops = null;
        static $cur_content_id = -1;

        $foundnodes = [];

        if( !$cache ) {
            $cache = Lone::get('LoadedData')->get('navigator_data');
        }

        $stnode = $cache[$id] ?? null;
        if( !$stnode ) {
            throw new RuntimeException('Navigator data-cache : unrecognized node id '.$id);
        }
        if( !($show_all || $stnode->inmenu) ) {
            return $foundnodes;
        }
        if( $cur_content_id == -1 ) {
            $cur_content_id = cmsms()->get_content_id();
        }
        $current = ($id == $cur_content_id);
        if( $current ) {
            $parent = FALSE;
        }
        else {
            if( !$ptops ) {
                $ptops = cmsms()->GetHierarchyManager(); // OR Lone::get('PageTreeOperations');
            }
            $all = $ptops->get_ancestors($cur_content_id);
            $parent = in_array($id,$all);
        }
        $child_nodes = [];
        // recurse?
        if( ($nlevels < 0 || $depth + 1 < $nlevels) &&
            (!$collapse || $current || $parent) &&
            $stnode->children ) {
            if( self::$excludes ) {
                if( !$ptops ) {
                    $ptops = cmsms()->GetHierarchyManager(); // OR Lone::get('PageTreeOperations');
                }
                $props = $ptops->props;
            }
            foreach( $stnode->children as $cid ) {
                if( self::$excludes ) {
                    $str = $props[$cid]['alias'] ?? '';
                    if( $str && self::is_excluded($str) ) {
                        continue;
                    }
                }
                $tmp = self::fill_context($cid,$nlevels,$show_all,$collapse,$depth+1); //recurse
                if( $tmp ) {
                    $foundnodes += $tmp;
                    $child_nodes[] = $cid; // TODO directly support $asnode ? hence $tmp[$cid]
                }
            }
        }
        $obj = new Node();
        $obj->children = $child_nodes;
        $obj->has_children = ($child_nodes != FALSE);
        $obj->current = $current;
        $obj->parent = $parent;
        $obj->depth = $depth + 1;
        $obj->statics = $stnode;
        if( $foundnodes ) {
            $foundnodes[$id] = $obj;
            return $foundnodes;
        }
        return [$id => $obj];
    }

    /**
     * Populator for the 'navigator_data' LoadedDataType
     * @param bool $force UNUSED, processed as if true always
     * @return array each member like $id => NodeStatic
     */
    public static function fill_cache($force) : array
    {
        static $config = null;
        $data = [];
        $ptops = cmsms()->GetHierarchyManager(); // OR Lone::get('PageTreeOperations');
        $db = Lone::get('Db');
        $pref = CMS_DB_PREFIX;
        debug_display('Start loading navigation data');
        $sql = "SELECT content_id,
CASE prop_name WHEN 'target' THEN content ELSE '' END AS target,
CASE prop_name WHEN 'image' THEN content ELSE '' END AS image,
CASE prop_name WHEN 'thumbnail' THEN content ELSE '' END AS thumbnail,
CASE prop_name WHEN 'extra1' THEN content ELSE '' END AS extra1,
CASE prop_name WHEN 'extra2' THEN content ELSE '' END AS extra2,
CASE prop_name WHEN 'extra3' THEN content ELSE '' END AS extra3
FROM {$pref}content_props
GROUP BY content_id";
        $extended = $db->getAssoc($sql);

        $sql = "SELECT
content_id,
content_name,
type,
default_content,
show_in_menu,
hierarchy,
menu_text,
content_alias,
hierarchy_path,
titleattribute,
page_url,
tabindex,
accesskey,
create_date,
modified_date
FROM {$pref}content
ORDER BY content_id";
        $props = $db->getArray($sql);

        foreach( $props as &$row ) {
            $obj = new NodeStatic();
            $obj->accesskey = $row['accesskey'];
            $obj->alias = $row['content_alias'];
            $obj->id = $nid = $row['content_id'];
            $obj->children = $ptops->children[$nid] ?? []; // TODO ->get_children(false, $nid) populates unnecessary nodes
            $obj->created = cms_to_stamp($row['create_date']);
            $obj->default = (bool)$row['default_content'];
            $obj->hierarchy = preg_replace('/(?<=^|\.)0+/', '', $row['hierarchy']); // friendly format
            $obj->inmenu = (bool)$row['show_in_menu'];
            $obj->raw_menutext = $row['menu_text'];
            $obj->menutext = specialize($obj->raw_menutext);
            $obj->modified = cms_to_stamp($row['modified_date']);
            $obj->name = $row['content_name'];
            $obj->title = $obj->name; //c.f. ->name, ->titleattribute
            $obj->titleattribute = $row['titleattribute'];
            $obj->tabindex = (int)$row['tabindex'];
            $obj->type = strtolower($row['type']);
            switch( $obj->type ) {
                case 'content':
                    if( !$config ) {
                        $config = Lone::get('Config');
                    }
                    $obj->url = self::GetContentUrl($row,$config);
                    break;
                case 'separator':
                case 'sectionheader':
                    $obj->url = '#';
                    break;
                case 'pagelink':
                    $content = PageLoader::LoadContent($nid);
                    if( is_object($content) ) {
                        $params = $content->GetPropertyValue('params');
                        if( $params ) {
                            if( strpos($params, '%') === FALSE ) {
                                $str = rawurlencode($params);
                                $params = strtr($str, ['%26'=>'&', '%3D'=>'=']);
                            }
                            $url = $content->GetURL(FALSE);
                            $obj->url = $url . $params;
                        }
                        else {
                            $obj->url = $content->GetURL();
                        }
                    }
                    else {
                        $obj->url = ''; //OR '&lt;page missing&gt;';
                    }
                    break;
                default:
                    $content = PageLoader::LoadContent($nid);
                    if( is_object($content) ) {
                        $obj->url = $content->GetURL();
                    }
                    else {
                        $obj->url = ''; // OR &lt;page missing&gt;';
                    }
            }
            $extras = $extended[$nid] ?? null;
            if( $extras ) {
                $obj->extra1 = $extras['extra1'];
                $obj->extra2 = $extras['extra2'];
                $obj->extra3 = $extras['extra3'];
                $obj->target = $extras['target'];
                $str = $extras['image'];
                if( $str && $str != -1 ) {
                    if( !isset($imgpart) ) {
                        $imgpart = AppParams::get('content_imagefield_path');
                        if( $imgpart ) {
                            $imgpart = rawurlencode(trim($imgpart,' \\/'));
                            $imgpart = strtr($imgpart,['%2F'=>'/','%5C'=>'/']);
                        }
                    }
                    if( $imgpart ) {
                        $url = $imgpart.'/'.$str;
                    }
                    else {
                        $url = $str;
                    }
                    if( !startswith($url,'/') ) $url = '/'.$url;
                    if( !$config ) {
                        $config = Lone::get('Config');
                    }
                    $obj->image = $config['image_uploads_url'].$url;
                }
                else {
                    $obj->image = FALSE;
                }
                $str = $extras['thumbnail'];
                if( $str && $str != -1 ) {
                    if( !isset($thumbpart) ) {
                        $thumbpart = AppParams::get('content_thumbnailfield_path');
                        if( $thumbpart ) {
                            $thumbpart = rawurlencode(trim($thumbpart,' \\/'));
                            $thumbpart = strtr($thumbpart,['%2F'=>'/','%5C'=>'/']);
                        }
                    }
                    if( $thumbpart ) {
                        $url = $thumbpart.'/'.$str;
                    }
                    else {
                        $url = $str;
                    }
                    if( !startswith($url,'/') ) $url = '/'.$url;
                    if( !$config ) {
                        $config = Lone::get('Config');
                    }
                    $obj->thumbnail = $config['image_uploads_url'].$url;
                }
                else {
                    $obj->thumbnail = FALSE;
                }
            }
            else {
                $obj->extra1 = '';
                $obj->extra2 = '';
                $obj->extra3 = '';
                $obj->target = '';
                $obj->image = FALSE;
                $obj->thumbnail = FALSE;
            }
            $data[$nid] = $obj;
        }
        unset($row);
        debug_display('End loading navigation data');
        return $data;
    }

    /**
     * Replicate Content::GetUrl()
     * Avoids loading a content-object to get the URL for that most-common node type
     *
     * @param array $props dB query row
     * @param AppConfig $config
     * @return string
     */
    private static function GetContentUrl(array $props, AppConfig $config) : string
    {
        if( !empty($props['default_content']) ) {
            // always use root url for default content
            return CMS_ROOT_URL . '/';
        }

        $url_rewriting = $config['url_rewriting'];
        if( $url_rewriting == 'mod_rewrite' ) {
            if ($props['page_url']) {
                $str = $props['page_url']; // we have an URL path
            }
            else {
                $str = $props['hierarchy_path'];
            }
            return CMS_ROOT_URL . '/' . $str . $config['page_extension'];
        }
        elseif( $url_rewriting == 'internal' && isset($_SERVER['PHP_SELF']) ) {
            if ($props['page_url']) {
                $str = $props['page_url'];
            }
            else {
                $str = $props['hierarchy_path'];
            }
            return CMS_ROOT_URL . '/index.php/' . $str . $config['page_extension'];
        }

        $alias = $props['content_alias'] ?: $props['content_id'];
        return CMS_ROOT_URL . '/index.php?' . $config['query_var'] . '=' . $alias;
    }
} // class
