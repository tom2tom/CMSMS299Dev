<?php
#Ajax processor action to retrieve site pages data, used by jquery.cmsms_hierselector.js
#Copyright (C) 2013-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
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

use CMSContentManager\Utils;

if( !isset($gCms) ) exit;

$handlers = ob_list_handlers();
for ($cnt = 0, $n = count($handlers); $cnt < $n; $cnt++) { ob_end_clean(); }

if( isset($params['op']) ) {
    $op = trim($params['op']);
}
else {
    $op = 'pageinfo';
}
$allow_all = isset($params['allow_all']) && cms_to_bool($params['allow_all']);

$hm = $gCms->GetHierarchyManager();
$contentops = $gCms->GetContentOperations();
$display = Utils::get_pagenav_display();
$uid = get_userid(FALSE);

try {
    if( $uid < 1 ) {
        throw new CmsError403Exception(lang('permissiondenied'));
    }

    $can_edit_any = check_permission($uid,'Manage All Content') || check_permission($uid,'Modify Any Page');
    $out = [];

    switch( $op ) {
      case 'userlist':
      case 'userpages':
        $tmplist = $contentops->GetPageAccessForUser($uid);
        if( $tmplist ) {
            $displaylist = $pagelist = [];
            foreach( $tmplist as $item ) {
                // get all the parents
                $parents = [];
                $startnode = $node = $hm->quickfind_node_by_id($item);
                while( $node && $node->get_tag('id') > 0 ) {
                    $content = $node->getContent();
                    $rec = $content->ToData();
                    $rec['can_edit'] = $can_edit_any || $contentops->CheckPageAuthorship($uid,$content->Id());
                    if( $display == 'title' ) { $rec['display'] = strip_tags($rec['content_name']); }
                    else { $rec['display'] = strip_tags($rec['menu_text']); }
                    $rec['has_children'] = $node->has_children();
                    $parents[] = $rec;
                    $node = $node->get_parent();
                }
                // start at root
                // push items from list on the stack if they are root, or the previous item is in the opened array.
                $parents = array_reverse($parents);
                for( $i = 0, $n = count($parents); $i < $n; $i++ ) {
                    $content_id = $parents[$i]['content_id'];
                    if( !in_array($content_id,$pagelist) ) {
                        $pagelist[] = $content_id;
                        $displaylist[] = $parents[$i];
                    }
                }
                unset($parents);
            }
            usort($displaylist,function($a,$b) {
                    return strcmp($a['hierarchy'],$b['hierarchy']);
                });
            $out = $displaylist;
            unset($displaylist);
        }
        break;

      case 'here_up':
        // given a page id, get all of the info for all ancestors, and their peers.
        // and the info for children.
        if( !isset($params['page']) ) throw new CmsInvalidDataException(lang('missingparams').' (page)');

        $current = ( isset($params['current']) ) ? (int)$params['current'] : 0;
//UNUSED $for_child = isset($params['for_child']) && cms_to_bool($params['for_child']);
        $allow_current = isset($params['allowcurrent']) && cms_to_bool($params['allowcurrent']);
        $children_to_data = function($node) use ($display,$uid,$contentops,$allow_all,$can_edit_any,$allow_current,$current) {
            $children = $node->getChildren(FALSE,$allow_all);
            if( empty($children) ) return;

            $child_info = [];
            foreach( $children as $child ) {
                $content = $child->getContent();
                if( !is_object($content) ) continue;
                if( !$allow_all && !$content->Active() ) continue;
                if( !$allow_all && !$content->HasUsableLink() ) continue;
                if( !$allow_current && $current == $content->Id() ) continue;
                $rec = $content->ToData();
                $rec['can_edit'] = $can_edit_any || $contentops->CheckPageAuthorship($uid,$rec['content_id']);
                if( $display == 'title' ) { $rec['display'] = strip_tags($rec['content_name']); }
                else { $rec['display'] = strip_tags($rec['menu_text']); }
                $rec['has_children'] = $child->has_children();
                $child_info[] = $rec;
            }
            return $child_info;
        };

        $out = [];
        $page = (int)$params['page'];
        if( $page < 1 ) $page = -1;
        $node = $thiscontent = null;
        if( $page == -1 ) {
            $node = $hm; // root, cloned
        } else {
            $node = $hm->quickfind_node_by_id($page);
        }
        do {
            $out[] = $children_to_data($node); // get children of current page.
            $node = $node->get_parent();
        } while( $node );
        $out = array_reverse($out);
        break;

      case 'childrenof':
        if( !isset($params['page']) ) {
            throw new CmsInvalidDataException(lang('missingparams').' (page)');
        }
        else {
            $page = (int)$params['page'];
            if( $page < 1 ) $page = -1;
            $node = null;
            if( $page == -1 ) {
                $node = $hm;
            }
            else {
                $node = $hm->quickfind_node_by_id($page);
            }
            if( $node ) {
                $children = $node->getChildren(FALSE,$allow_all);
                if( $children ) {
                    $out = [];
                    foreach( $children as $child ) {
                        $content = $child->getContent();
                        if( !is_object($content) ) continue;
                        if( !$allow_all && !$content->Active() ) continue;
                        $rec = $content->ToData();
                        $rec['can_edit'] = check_permission($uid,'Manage All Content') || $contentops->CheckPageAuthorship($uid,$rec['content_id']);
                        if( $display == 'title' ) { $rec['display'] = strip_tags($rec['content_name']); }
                        else { $rec['display'] = strip_tags($rec['menu_text']); }
                        $out[] = $rec;
                    }
                }
            }
        }
        break;

      case 'pagepeers':
        if( !isset($params['pages']) || !is_array($params['pages']) ) {
            throw new CmsInvalidDataException(lang('missingparams'));
        }
        else {
            // clean up the data a bit
            $tmp = [];
            foreach( $params['pages'] as $one ) {
                $one = (int)$one;
                // discard negative values
                if( $one > 0 ) $tmp[] = $one;
            }
            $peers = array_unique($tmp);

            $out = [];
            foreach( $peers as $one ) {
                $node = $hm->find_by_tag('id',$one);
                if( !$node ) continue;

                // get the parent
                $parent_node = $node->get_parent();

                // and get its children
                $out[$one] = [];
                $children = $parent_node->getChildren(FALSE,$allow_all);
                for( $i = 0, $n = count($children); $i < $n; $i++ ) {
                    $content_obj = $children[$i]->getContent();
                    if( ! $content_obj->IsViewable() ) continue;
                    $rec = [];
                    $rec['content_id'] = $content_obj->Id();
                    $rec['id_hierarchy'] = $content_obj->IdHierarchy();
                    $rec['wants_children'] = $content_obj->WantsChildren();
                    $rec['has_children'] = $children[$i]->has_children();
                    $rec['display'] = ($display == 'title') ? $content_obj->Name() : $content_obj->MenuText();
                    $out[$one][] = $rec;
                }
            }
        }
        break;

      case 'pageinfo':
        if( !isset($params['page']) ) {
            throw new CmsInvalidDataException(lang('missingparams').' (page)');
        }
        else {
            $page = (int)$params['page'];
            if( $page < 1 ) {
                $page = $contentops->GetDefaultContent();
            }
            // get the page properties
            $contentobj = $contentops->LoadContentFromId($page);
            if( is_object($contentobj) ) {
                $out = $contentobj->ToData();
                if( $display == 'title' ) { $out['display'] = $out['content_name']; }
                else { $out['display'] = $out['menu_text']; }
            }
            else {
                throw new RuntimeException($this->Lang('errorgettingcontent'));
            }
        }
        break;

      default:
        throw new CmsInvalidDataException(lang('missingparams')." (operation: $op)");
    }
    $ret = ['status'=>'success','op'=>$op,'data'=>$out];
}
catch( Throwable $t ) {
    $ret = ['status'=>'error','message'=>$t->GetMessage()];
}
echo json_encode($ret);
exit;
