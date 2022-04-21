<?php
/*
Navigator module action: default
Copyright (C) 2013-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

debug_buffer('Start Navigator default action');
$items = null;
$nlevels = -1;
$show_all = false;
$show_root_siblings = false;
$start_element = null;
$start_page = null;
$start_level = null;
$childrenof = null;
$deep = false; //true; properties not generally needed
$collapse = false;

if( !empty($params['template']) ) {
    $template = trim($params['template']);
}
else {
    $tpl = TemplateOperations::get_default_template_by_type('Navigator::navigation');
    if( !is_object($tpl) ) {
        log_error('No default template found',$this->GetName().'::default');
        $this->ShowErrorPage('No default template found');
        return;
    }
    $template = $tpl->get_name();
}

$hm = $gCms->GetHierarchyManager();
foreach( $params as $key => $value ) {
    switch( $key ) {
    case 'loadprops':
        $deep = cms_to_bool($value); //true achieves nothing. Deprecated since 3.0
        break;

    case 'items':
        // hardcoded list of items (and their children)
        Utils::clear_excludes();
        $items = trim($value);
        $nlevels = 1;
        $start_element = null;
        $start_page = null;
        $start_level = null;
        $childrenof = null;
        break;

    case 'includeprefix':
        Utils::clear_excludes();
        $list = explode(',',$value);
        if( $list ) {
            foreach( $list as &$one ) {
                $one = trim($one);
            }
            $list = array_unique($list);
            if( $list ) {
                $flatlist = $hm->getFlatList();
                if( $flatlist ) {
                    $tmp = [];
                    foreach( $flatlist as $node ) {
                        $alias = $node->get_tag('alias');
                        foreach( $list as $t1 ) {
                            if( startswith( $alias, $t1 ) ) {
                                $tmp[] = $alias;
                                break;
                            }
                        }
                    }
                    if( $tmp ) $items = implode(',',$tmp);
                }
            }
        }
        $nlevels = 1;
        $start_element = null;
        $start_page = null;
        $start_level = null;
        $childrenof = null;
        break;

    case 'excludeprefix':
        Utils::set_excludes($value);
        $items = null;
        break;

    case 'nlevels':
    case 'number_of_levels':
        // a maximum number of levels;
        if( (int)$value > 0 ) $nlevels = (int)$value;
        break;

    case 'show_all':
        // show all items, even if marked as 'not shown in menu'
        $show_all = cms_to_bool($value);
        break;

    case 'show_root_siblings':
        // given a start element or start page ... show its siblings too
        $show_root_siblings = cms_to_bool($value);
        break;

    case 'start_element':
        $start_element = trim($value);
        $start_page = null;
        $start_level = null;
        $childrenof = null;
        $items = null;
        break;

    case 'start_page':
        $start_element = null;
        $start_page = trim($value);
        $start_level = null;
        $childrenof = null;
        $items = null;
        break;

    case 'start_level':
        $value = (int)$value;
        if( $value > 1 ) {
            $start_element = null;
            $start_page = null;
            $items = null;
            $start_level = $value;
        }
        break;

    case 'childrenof':
        $start_page = null;
        $start_element = null;
        $start_level = null;
        $childrenof = trim($value);
        $items = null;
        break;

    case 'collapse':
        $collapse = (int)$value;
        break;
    }
} // params

if( $items ) $collapse = false;

$rootnodes = [];
if( $start_element ) {
    // get an alias... from a hierarchy level.
    $tmp = $hm->getNodeByHierarchy($start_element);
    if( is_object($tmp) ) {
        if( !$show_root_siblings ) {
            $rootnodes[] = $tmp;
        }
        else {
            $tmp = $tmp->get_parent();
            if( is_object($tmp) && $tmp->has_children() ) {
                $rootnodes = $tmp->get_children();
            }
        }
    }
}
elseif( $start_page ) {
    $id = $hm->find_by_identifier($start_page,false);
    if( $id ) {
        $tmp = $hm->find_by_tag('id',$id);
        if( $show_root_siblings ) {
            $tmp = $tmp->get_parent();
            if( is_object($tmp) && $tmp->has_children() ) {
                $rootnodes = $tmp->get_children();
            }
        }
        else {
            $rootnodes[] = $tmp;
        }
    }
}
elseif( $start_level > 1 ) {
    $tmp = $hm->find_by_tag('id',$gCms->get_content_id());
    if( $tmp ) {
        $arr = $arr2 = [];
        while( $tmp ) {
            $id = $tmp->get_tag('id');
            if( !$id ) break;
            $arr[$id] = $tmp;
            $arr2[] = $id;
            $tmp = $tmp->get_parent();
        }
        if( ($start_level - 2) < count($arr2) ) {
            $arr2 = array_reverse($arr2);
            $id = $arr2[$start_level-2];
            $tmp = $arr[$id];
            if( $tmp->has_children() ) {
                // do children of this element
                $rootnodes = $tmp->get_children();
            }
        }
    }
}
elseif( $childrenof ) {
    $obj = $hm->find_by_identifier(trim($childrenof));
    if( $obj && $obj->has_children() ) {
        $rootnodes = $obj->get_children();
    }
}
elseif( $items ) {
    if( $nlevels < 1 ) $nlevels = 1;
    $items = explode(',',$items);
    $items = array_unique($items);
    foreach( $items as $item ) {
        $obj = $hm->find_by_identifier(trim($item));
        if( $obj ) $rootnodes[] = $obj;
    }
}
else {
    // start at the top
    if( $hm->has_children() ) {
        $rootnodes = $hm->get_children();
    }
}

if( !$rootnodes ) return; // nothing to do.

// ready to fill the nodes
$outtree = [];
foreach( $rootnodes as $node ) {
    if( Utils::is_excluded($node->get_tag('alias')) ) {
        continue;
    }
    $tmp = Utils::fill_node($node,$deep,$nlevels,$show_all,$collapse);
    if( $tmp ) {
        $outtree[] = $tmp;
    }
}

Utils::clear_excludes();

$tpl = $smarty->createTemplate($this->GetTemplateResource($template)); //,null,null,$smarty);
$tpl->assign('nodes',$outtree)
  ->display();

unset($tpl);
debug_buffer('Finished Navigator default action');
