<?php
#Ajax processor to retrieve site pages data, used by jquery.cmsms_hierselector.js
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

$CMS_ADMIN_PAGE = 1;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

if (!isset($_REQUEST[CMS_SECURE_PARAM_NAME]) || !isset($_SESSION[CMS_USER_KEY]) || $_REQUEST[CMS_SECURE_PARAM_NAME] != $_SESSION[CMS_USER_KEY]) {
    exit;
}

$handlers = ob_list_handlers();
for ($cnt = 0, $n = count($handlers); $cnt < $n; $cnt++) { ob_end_clean(); }

//$urlext = get_secure_param();
$user_id = get_userid(FALSE);
$gCms = CmsApp::get_instance();
$hm = $gCms->GetHierarchyManager();
$contentops = $gCms->GetContentOperations();
try {
    $display = Utils::get_pagenav_display();
}
catch( Throwable $t ) {
    $display = 'title';
}

cleanArray($_REQUEST);
if( isset($_REQUEST['op']) ) {
    $op = trim($_REQUEST['op']);
}
else {
    $op = 'pageinfo';
}
$allow_all = isset($_REQUEST['allow_all']) && cms_to_bool($_REQUEST['allow_all']);

try {
    if( $user_id < 1 ) {
        throw new CmsError403Exception(lang('permissiondenied'));
    }

    $can_edit_any = check_permission($user_id,'Manage All Content') || check_permission($user_id,'Modify Any Page');
    $out = [];

    switch( $op ) {
      case 'userlist':
      case 'userpages':
        $tmplist = $contentops->GetPageAccessForUser($user_id);
        if( $tmplist ) {
            $displaylist = $pagelist = [];
            foreach( $tmplist as $item ) {
                // get all the parents
                $parents = [];
                $startnode = $node = $hm->quickfind_node_by_id($item);
                while( $node && $node->get_tag('id') > 0 ) {
                    $content = $node->getContent();
                    $rec = $content->ToData();
                    $rec['can_edit'] = $can_edit_any || $contentops->CheckPageAuthorship($user_id,$content->Id());
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
        // given a page id, get all of the info for all ancestors, and their peers,
        // and the info for children.
        if( !isset($_REQUEST['page']) ) throw new CmsInvalidDataException(lang('missingparams').' (page)');

        $current = ( isset($_REQUEST['current']) ) ? (int)$_REQUEST['current'] : 0;
//UNUSED $for_child = isset($_REQUEST['for_child']) && cms_to_bool($_REQUEST['for_child']);
        $allow_current = isset($_REQUEST['allowcurrent']) && cms_to_bool($_REQUEST['allowcurrent']);
        $children_to_data = function($node) use ($display,$user_id,$contentops,$allow_all,$can_edit_any,$allow_current,$current) {
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
                $rec['can_edit'] = $can_edit_any || $contentops->CheckPageAuthorship($user_id,$rec['content_id']);
                if( $display == 'title' ) { $rec['display'] = strip_tags($rec['content_name']); }
                else { $rec['display'] = strip_tags($rec['menu_text']); }
                $rec['has_children'] = $child->has_children();
                $child_info[] = $rec;
            }
            return $child_info;
        };

        $out = [];
        $page = (int)$_REQUEST['page'];
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
        if( !isset($_REQUEST['page']) ) {
            throw new CmsInvalidDataException(lang('missingparams').' (page)');
        }
        else {
            $page = (int)$_REQUEST['page'];
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
                        $rec['can_edit'] = check_permission($user_id,'Manage All Content') || $contentops->CheckPageAuthorship($user_id,$rec['content_id']);
                        if( $display == 'title' ) { $rec['display'] = strip_tags($rec['content_name']); }
                        else { $rec['display'] = strip_tags($rec['menu_text']); }
                        $out[] = $rec;
                    }
                }
            }
        }
        break;

      case 'pagepeers':
        if( !isset($_REQUEST['pages']) || !is_array($_REQUEST['pages']) ) {
            throw new CmsInvalidDataException(lang('missingparams'));
        }
        else {
            // clean up the data a bit
            $tmp = [];
            foreach( $_REQUEST['pages'] as $one ) {
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
        if( !isset($_REQUEST['page']) ) {
            throw new CmsInvalidDataException(lang('missingparams').' (page)');
        }
        else {
            $page = (int)$_REQUEST['page'];
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
