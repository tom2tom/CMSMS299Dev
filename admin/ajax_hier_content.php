<?php
/*
Ajax processor to retrieve site pages data, used by jquery.cmsms_hierselector.js
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

use CMSMS\DataException;
use CMSMS\Error403Exception;
use CMSMS\Lone;
use ContentManager\Utils;
//use function CMSMS\sanitizeVal;

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

$handlers = ob_list_handlers();
for ($cnt = 0, $n = count($handlers); $cnt < $n; ++$cnt) { ob_end_clean(); }

//$urlext = get_secure_param();
$userid = get_userid(false);
$ptops = Lone::get('PageTreeOperations');
$contentops = Lone::get('ContentOperations');
try {
    $display = Utils::get_pagenav_display();
}
catch( Throwable $t ) {
    $display = 'title';
}

// $_REQUEST[] members cleaned individually as needed
$op = trim($_REQUEST['op'] ?? 'pageinfo'); // no sanitizeVal() etc cuz only explicit vals accepted
$allow_all = isset($_REQUEST['allow_all']) && cms_to_bool($_REQUEST['allow_all']);

try {
    if( $userid < 1 ) {
        throw new Error403Exception(_la('permissiondenied'));
    }

    $can_edit_any = check_permission($userid,'Manage All Content') || check_permission($userid,'Modify Any Page');
    $out = [];

    switch( $op ) {
      case 'userlist':
      case 'userpages':
        $tmplist = $contentops->GetPageAccessForUser($userid);
        if( $tmplist ) {
            $displaylist = $pagelist = [];
            foreach( $tmplist as $nid ) {
                // get all this one's ancestors
                $parents = [];
                $node = $ptops->get_node_by_id($nid);
                while( $node && $node->getId() > 0 ) {
                    $content = $node->get_content();
                    $rec = $content->ToData();
                    $rec['can_edit'] = $can_edit_any || $contentops->CheckPageAuthorship($userid,$content->Id());
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
                unset($parents); //garbage-collector assistance
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
        if( !isset($_REQUEST['page']) ) {
            throw new DataException(_la('missingparams').' (page)');
        }
        $current = ( isset($_REQUEST['current']) ) ? (int)$_REQUEST['current'] : 0;
//UNUSED $for_child = isset($_REQUEST['for_child']) && cms_to_bool($_REQUEST['for_child']);
        $allow_current = isset($_REQUEST['allowcurrent']) && cms_to_bool($_REQUEST['allowcurrent']);
        $children_to_data = function($node) use ($display,$userid,$contentops,$allow_all,$can_edit_any,$allow_current,$current) {
            $children = $node->load_children(false,$allow_all);
            if( empty($children) ) return;

            $child_info = [];
            foreach( $children as $child ) {
                $content = $child->get_content();
                if( !is_object($content) ) continue;
                if( !$allow_all && !$content->Active() ) continue;
                if( !$allow_all && !$content->HasUsableLink() ) continue;
                if( !$allow_current && $current == $content->Id() ) continue;
                $rec = $content->ToData();
                $rec['can_edit'] = $can_edit_any || $contentops->CheckPageAuthorship($userid,$rec['content_id']);
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
            $node = $ptops; // PageTreeOperations object, not a PageTreeNode node
        } else {
            $node = $ptops->get_node_by_id($page);
        }
        do {
            $out[] = $children_to_data($node); // get children of current page.
            $node = $node->get_parent();
        } while( $node );
        $out = array_reverse($out);
        break;

      case 'childrenof':
        if( !isset($_REQUEST['page']) ) {
            throw new DataException(_la('missingparams').' (page)');
        }
        else {
            $page = (int)$_REQUEST['page'];
            if( $page < 1 ) $page = -1;
            $node = null;
            if( $page == -1 ) {
                $node = $ptops; // PageTreeOperations, not a PageTreeNode node
            }
            else {
                $node = $ptops->get_node_by_id($page);
            }
            if( $node ) {
                $children = $node->load_children(false,$allow_all);
                if( $children ) {
                    $out = [];
                    foreach( $children as $child ) {
                        $content = $child->get_content();
                        if( !is_object($content) ) continue;
                        if( !$allow_all && !$content->Active() ) continue;
                        $rec = $content->ToData();
                        $rec['can_edit'] = check_permission($userid,'Manage All Content') || $contentops->CheckPageAuthorship($userid,$rec['content_id']);
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
            throw new DataException(_la('missingparams'));
        }
        else {
            // clean up the data a bit
            $tmp = [];
            foreach( $_REQUEST['pages'] as $one ) {
                $one = (int)$one;
                // ignore invalid identifiers
                if( $one > 0 ) $tmp[] = $one;
            }
            $peers = array_unique($tmp);

            $out = [];
            foreach( $peers as $one ) {
                $node = $ptops->get_node_by_id($one);
                if( !$node ) continue;

                // get the parent
                $parent_node = $node->get_parent();

                // and its (viewable) children
                $out[$one] = [];
                $children = $parent_node->load_children(false,$allow_all);
                for( $i = 0, $n = count($children); $i < $n; $i++ ) {
                    $content = $children[$i]->get_content();
                    if( ! $content->IsViewable() ) continue;
                    $rec = [];
                    $rec['content_id'] = $content->Id();
                    $rec['id_hierarchy'] = $content->IdHierarchy();
                    $rec['wants_children'] = $content->WantsChildren();
                    $rec['has_children'] = $children[$i]->has_children();
                    $rec['display'] = ($display == 'title') ? $content->Name() : $content->MenuText();
                    $out[$one][] = $rec;
                }
            }
        }
        break;

      case 'pageinfo':
        if( !isset($_REQUEST['page']) ) {
            throw new DataException(_la('missingparams').' (page)');
        }
        else {
            $page = (int)$_REQUEST['page'];
            if( $page < 1 ) {
                $page = $contentops->GetDefaultContent();
            }
            // get the page properties
            $content = $contentops->LoadContentFromId($page);
            if( is_object($content) ) {
                $out = $content->ToData();
                if( $display == 'title' ) { $out['display'] = $out['content_name']; }
                else { $out['display'] = $out['menu_text']; }
            }
            else {
                throw new RuntimeException(_la('errorgettingcontent'));
            }
        }
        break;

      default:
        throw new DataException(_la('missingparams')." (operation: $op)");
    }
    $ret = ['status'=>'success','op'=>$op,'data'=>$out];
}
catch( Throwable $t ) {
    $ret = ['status'=>'error','message'=>$t->GetMessage()];
}

echo json_encode($ret);
exit;
