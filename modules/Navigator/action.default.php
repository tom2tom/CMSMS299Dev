<?php
/*
Navigator module action: default
Copyright (C) 2013-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\TemplateOperations;
use Navigator\Utils;
use function CMSMS\log_error;

//if( some worthy test fails ) exit;
/*
if( !empty($params['template']) ) {
    $X = $params['template'];
}
$params['template'] = 'Breadcrumbs';
include_once __DIR__.DIRECTORY_SEPARATOR.'action.breadcrumbs.php';
if( isset($X) ) {
    $params['template'] = $X;
}
else {
    unset($params['template']);
}
*/
debug_buffer('Start Navigator default action');

if( !empty($params['template']) ) {
    $tplname = trim($params['template']); //= sanitizeVal(trim($params['template']), CMSSAN_TODO);
    $tpl = $smarty->createTemplate($this->GetTemplateResource($tplname)); //,null,null,$smarty);
    if( !is_object($tpl) ) {
        $msg = "Unrecognized navigator template '$tplname'";
    }
}
else {
    $tpl = TemplateOperations::get_default_template_by_type('Navigator::navigation');
    if( is_object($tpl) ) {
        $tplname = $tpl->name;
        $tpl = $smarty->createTemplate($this->GetTemplateResource($tplname)); //,null,null,$smarty);
    }
    else {
        $msg = 'No default navigator template found';
    }
}
if( !is_object($tpl) ) {
    log_error($msg,$this->GetName().'::default');
    $this->ShowErrorPage($msg);
    debug_buffer('Finished Navigator default action');
    return;
}

// Possible conflicts among these variables, by reason of the
// order of $params[] processing here, must be prevented by
// judicious choice of parameters in the initiating tag
$childrenof = ''; // node id or alias
$collapse = false;
$items = ''; // comma-separated series of ...
$nlevels = -1; // OR 1?
$show_all = false;
$show_root_siblings = false;
$start_element = ''; // pages-hierarchy identifier like 00x.00y ...
$start_level = 0; // hierarchy-level 0 = none
$start_page = ''; // node id or alias
$ptops = $gCms->GetHierarchyManager();

foreach( $params as $key => $value ) {
    //TODO $value = sanitizeVal($value,CMSSAN_TODO) when relevant
    switch( $key ) {
    case 'loadprops':
        // unused, deprecated since 2.0
        break;

    case 'items':
        // comma-separated series of node aliases and/or ids
        Utils::clear_excludes();
        $childrenof = '';
        $items = trim($value);
        $nlevels = 1;
        $start_element = '';
        $start_level = 0;
        $start_page = '';
        break;

    case 'includeprefix':
        // comma-separated series of node-alias prefixes
        Utils::clear_excludes();
        $list = explode(',',$value);
        if( $list ) {
            foreach( $list as &$one ) {
                $one = trim($one);
            }
            $list = array_unique($list);
            if( $list ) {
                //TODO efficient reconciliation list-members with all node-aliases
                $itemprops = $ptops->props;
                if( $itemprops ) {
                    $tmp = [];
                    foreach( $itemprops as $row ) {
                        $alias = $row('alias');
                        foreach( $list as $t1 ) {
                            if( startswith($alias,$t1) ) {
                                $tmp[] = $alias;
                                break;
                            }
                        }
                    }
                    if( $tmp ) { $items = implode(',',$tmp); }
                }
            }
        }
        $childrenof = '';
        $nlevels = 1;
        $start_element = '';
        $start_level = 0;
        $start_page = '';
        break;

    case 'excludeprefix':
        // comma-separated series of node-alias prefixes
        Utils::set_excludes(trim($value));
        $items = '';
        break;

    case 'nlevels':
    case 'no_of_levels':
    case 'number_of_levels':
        // maximum number of levels
        if( (int)$value > 0 ) $nlevels = (int)$value;
        break;

    case 'show_all':
        // whether to also process nodes marked as 'not shown in menu'
        $show_all = cms_to_bool($value);
        break;

    case 'show_root_siblings':
        // given a start element or start page ... show its siblings too
        $show_root_siblings = cms_to_bool($value);
        break;

    case 'start_element':
        // pages-hierarchy identifier like 00x.00y ...
        $childrenof = '';
        $items = '';
        $start_element = trim($value);
        $start_level = 0;
        $start_page = '';
        break;

    case 'start_page':
        // page id or alias
        $childrenof = '';
        $items = '';
        $start_element = '';
        $start_level = 0;
        $start_page = trim($value);
        break;

    case 'start_level':
        $value = (int)$value;
        if( $value > 1 ) {
            $items = '';
            $start_element = '';
            $start_level = $value;
            $start_page = '';
        }
        break;

    case 'childrenof':
        // page id or alias
        $childrenof = trim($value);
        $items = '';
        $start_element = '';
        $start_level = 0;
        $start_page = '';
        break;

    case 'collapse':
        $collapse = cms_to_bool($value);
        break;
    }
} // params

if( $items ) {
    $collapse = false;
}
if( $collapse) {
    $nlevels = 1;
}

$rootnodes = [];
if( $start_element ) {
    // get an alias... from a hierarchy level.
    $node = $ptops->get_node_by_hierarchy($start_element);
    if( is_object($node) ) {
        if( !$show_root_siblings ) {
            $rootnodes[] = $node;
        }
        else {
            $parent = $node->get_parent();
            if( is_object($parent) && $parent->has_children() ) {
                $rootnodes = $parent->get_children();
            }
            else {
                $rootnodes[] = $node;
            }
        }
    }
}
elseif( $start_page ) {
    $nid = $ptops->find_by_identifier($start_page,false);
    if( $nid ) {
        $node = $ptops->get_node_by_id($nid);
        if( $show_root_siblings ) {
            $parent = $node->get_parent();
            if( is_object($parent) && $parent->has_children() ) {
                $rootnodes = $parent->get_children();
            }
            else {
                $rootnodes[] = $node;
            }
        }
        else {
            $rootnodes[] = $node;
        }
    }
}
elseif( $start_level > 1 ) {
    //TODO directly interrogate $ptops->props for this
    $node = $ptops->get_node_by_id($gCms->get_content_id());
    if( $node ) {
        $arr = $arr2 = [];
        while( $node ) {
            $nid = $node->getId();
            if( $nid < 1 ) break;
            $arr[$nid] = $node;
            $arr2[] = $nid;
            $node = $node->get_parent();
        }
        if( ($start_level - 2) < count($arr2) ) {
            $arr2 = array_reverse($arr2);
            $nid = $arr2[$start_level-2];
            $node = $arr[$nid];
            if( $node->has_children() ) {
                // do children of this element
                $rootnodes = $node->get_children();
            }
        }
    }
}
elseif( $childrenof ) {
    $obj = $ptops->find_by_identifier(trim($childrenof));
    if( $obj && $obj->has_children() ) {
        $rootnodes = $obj->get_children();
    }
}
elseif( $items ) {
    if( $nlevels < 1 ) {
        $nlevels = 1;
    }
    $arr = explode(',',$items);
    $arr = array_unique($arr);
    foreach( $arr as $item ) {
        $obj = $ptops->find_by_identifier(trim($item));
        if( $obj ) {
            $rootnodes[] = $obj;
        }
    }
}
elseif( $ptops->has_children() ) {
    // start at the top
    $rootnodes = $ptops->get_children();
}

if( $rootnodes ) {
    $asnodes = $this->TemplateNodes($params,$tplname);
    $asnodes = FALSE; //DEBUG
    $nodelist = [];
    $idslist = [];
    foreach( $rootnodes as $node ) {
        $nid = $node->getId();
        $alias = $ptops->props[$nid]['alias'];
        if( Utils::is_excluded($alias) ) {
            continue;
        }
        $tmp = Utils::fill_context($nid,$nlevels,$show_all,$collapse);
        if( $tmp ) {
            $nodelist += $tmp;
            $idslist[] = $nid;
        }
    }

    if( $asnodes ) {
        foreach( $nodelist as &$node ) {
            if( $node->children ) {
                foreach( $node->children as &$pid ) {
                    $pid = $nodelist[$pid];
                }
                unset($pid);
            }
        }
        unset($node);
        $tmp = [];
        foreach( $idslist as $pid ) {
            $tmp[] = $nodelist[$pid];
        }
        $nodelist = $tmp;
    }
    else {
        $nodelist[-1] = (object)['id'=>-1,'children'=>$idslist];
    }
    $tpl->assign('nodes',$nodelist)
      ->display();
    unset($tpl); // garbage-collector assistance
}

if( isset($params['excludeprefix']) ) { Utils::clear_excludes(); }

debug_buffer('Finished Navigator default action');
