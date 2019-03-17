<?php
# Navigator module utilities class
# Copyright (C) 2013-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

namespace Navigator;

use cms_siteprefs;
use CmsApp;
use CMSMS\ContentCache;
use NavigatorNode;
use function cms_htmlentities;
use function startswith;

final class utils
{
    private static $_excludes;
    private function __construct() {}

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
        if( !is_array(self::$_excludes) || count(self::$_excludes) == 0 ) return FALSE;
        foreach( self::$_excludes as $one ) {
            if( startswith($alias,$one) ) {
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
     * Populate a node to be used for generating a menu-item
     * @param object $node
     * @param bool $deep whether to deep-scan for children & properties?
     * @param int  $nlevels  max recursion depth
     * @param bool $show_all whether to process items flagged as not show-in-menu
     * @param bool $collapse whether to ? (recursion-related) default false
     * @param int  $depth current recursion level, 0-based
     * @return NavigatorNode
     */
    public static function fill_node($node,$deep,$nlevels,$show_all,$collapse = FALSE,$depth = 0)
    {
        if( !is_object($node) ) return;

        $content = $node->getContent(TRUE);
        if( is_object($content) ) {
            if( !$content->Active() ) return;
            if( !$content->ShowInMenu() && !$show_all ) return;

            $obj = new NavigatorNode();
            $obj->id = $content->Id();
            $obj->name = $content->Name();
            $obj->title = $content->Name();
            $obj->url = $content->GetURL();
            $obj->accesskey = $content->AccessKey();
            $obj->type = strtolower($content->Type());
            $obj->tabindex = $content->TabIndex();
            $obj->titleattribute = $content->TitleAttribute();
            $obj->modified = $content->GetModifiedDate();
            $obj->created = $content->GetCreationDate();
            $obj->hierarchy = $content->Hierarchy();
            $obj->depth = $depth+1;
            $obj->menutext = cms_htmlentities($content->MenuText());
            $obj->raw_menutext = $content->MenuText();
            $obj->target = '';
            $obj->alias = $content->Alias();
            $obj->current = FALSE;
            $obj->parent = FALSE;
            $obj->has_children = FALSE;
            $obj->children_exist = FALSE;

            $gCms = CmsApp::get_instance();
            $cur_content_id = $gCms->get_content_id();
            if( $obj->id == $cur_content_id ) {
                $obj->current = true;
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

            if( $content->DefaultContent() ) $obj->default = 1;
            if( $deep ) {
                if ($content->HasProperty('target')) $obj->target = $content->GetPropertyValue('target');
                $config = $gCms->GetConfig();
                $obj->extra1 = $content->GetPropertyValue('extra1');
                $obj->extra2 = $content->GetPropertyValue('extra2');
                $obj->extra3 = $content->GetPropertyValue('extra3');
                $tmp = $content->GetPropertyValue('image');
                if( !empty($tmp) && $tmp != -1 ) {
                    $url = cms_siteprefs::get('content_imagefield_path').'/'.$tmp;
                    if( !startswith($url,'/') ) $url = '/'.$url;
                    $url = $config['image_uploads_url'].$url;
                    $obj->image = $url;
                }
                $tmp = $content->GetPropertyValue('thumbnail');
                if( !empty($tmp) && $tmp != -1 ) {
                    $url = cms_siteprefs::get('content_thumbnailfield_path').'/'.$tmp;
                    if( !startswith($url,'/') ) $url = '/'.$url;
                    $url = $config['image_uploads_url'].$url;
                    $obj->thumbnail = $url;
                }
            }

            // poll all the children ... to see if we have any child that 'could' be displayed SLOW!
            $children = null;
            if( $node->has_children() ) {
                $children = $node->getChildren($deep,$show_all); //loads children into cache : SLOW! TODO just get id's
                if( $children ) {
					$cache = ContentCache::get_instance();
                    foreach( $children as &$node ) {
                        $id = $node->get_tag('id');
                        if( $cache->content_exists($id) ) {
                            $obj->children_exist = TRUE;
                            break;
                        }
                    }
                    unset($node);
                }
            }

            // are we able to recurse [further]?
            if( is_array($children) && count($children)
               && ($nlevels < 0 || $depth + 1 < $nlevels)
               && (!$collapse || $obj->parent || $obj->current) ) {
                $obj->has_children = TRUE;
                $child_nodes = [];
                foreach( $children as &$node ) {
                    if( self::is_excluded($node->get_tag('alias')) ) {
                        continue;
                    }
                    $tmp = self::fill_node($node,$deep,$nlevels,$show_all,$collapse,$depth+1); //recurse
                    if( is_object($tmp) ) {
                        $child_nodes[] = $tmp;
                    }
                }
                unset($node);
                if( $child_nodes ) {
                    $obj->children = $child_nodes;
                }
            }

            return $obj;
        }
    }
} // class
