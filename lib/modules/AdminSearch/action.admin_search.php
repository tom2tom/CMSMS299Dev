<?php
/*
AdminSearch module action: ajax-processor to search database tables and display matches
Copyright (C) 2012-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use AdminSearch\Tools;
use CMSMS\UserParams;
use CMSMS\Utils;
use function CMSMS\htmlentities_decode;

if( !isset($gCms) ) exit;
if( !$this->VisibleToAdminUser() ) exit;

$handlers = ob_list_handlers();
for ($cnt = 0, $n = count($handlers); $cnt < $n; ++$cnt) { ob_end_clean(); }

if( !isset($params['search_text']) || $params['search_text'] === '' ) {
    $tpl = $smarty->createTemplate($this->GetTemplateResource('errorsearch.tpl')); //,null,null,$smarty);
    $tpl->assign('message',$this->Lang('error_nosearchtext'));
    $tpl->display();
    exit;
}
if( empty($params['slaves']) ) {
    $tpl = $smarty->createTemplate($this->GetTemplateResource('errorsearch.tpl')); //,null,null,$smarty);
    $tpl->assign('message',$this->Lang('error_noscopes'));
    $tpl->display();
    exit;
}

// find relevant search-slave classes
$slaves = Tools::get_slave_classes();
if( $slaves ) {
    // search-target was processed downstream by js encodeURIComponent()
    $str = preg_replace("/%u([0-9a-f]{3,4})/i","&#x\\1;",urldecode($params['search_text']));
    $text = htmlentities_decode($str); // TODO sanitize string e.g. CMSMS\cleanExec(), CMSMS\escape_sql()

    // cache this search
    $searchparams = [
     'search_text' => $text,
     'slaves' => explode(',',$params['slaves']),
     'search_descriptions' => !empty($params['search_descriptions']),
     'search_casesensitive' => !empty($params['case_sensitive']),
     'verbatim_search' => !empty($params['verbatim_search']),
     'save_search' => !empty($params['save_search']),
    ];
    $userid = get_userid(false);
    if( $searchparams['save_search'] ) {
        UserParams::set_for_user($userid,$this->GetName().'saved_search',serialize($searchparams));
    }
    else {
        UserParams::remove_for_user($userid,$this->GetName().'saved_search');
    }

    $types = $searchparams['slaves'];
    unset($searchparams['slaves']);

    $casewarn = false;
    $sections = [];
    foreach( $slaves as $one_slave ) {
        if( !in_array($one_slave['class'],$types) ) {
            continue;
        }

        $module = Utils::get_module($one_slave['module']);
        if( !is_object($module) ) {
            continue;
        }
        $obj = new $one_slave['class'];
        if( !is_object($obj) ) {
            continue;
        }
        if( !is_subclass_of($obj,'AdminSearch\\Base_slave') ) {
            continue;
        }
//        if( !$obj->check_permission() ) { done downsteam
//            continue;
//        }

        $obj->set_params($searchparams);

        $results = $obj->get_matches();
        if( !($searchparams['search_casesensitive'] || $searchparams['verbatim_search']) ) {
            if( $obj->has_badchars() ) {
                $casewarn = true;
            }
        }
        if( $results ) {
            $oneset = new stdClass();
            $oneset->id = $one_slave['class'];
            $oneset->lbl = $obj->get_name();
            $oneset->desc = $obj->get_section_description();
            $oneset->count = count($results);
            $tmp = [];
            foreach( $results as $one ) {
                $text = $one['text'] ?? ''; //aleady sanitized downstream
                $url = $one['edit_url'] ?? '';
                if( $url ) { $url = str_replace('&amp;','&',$url); }
                $tmp[] = [
                 'description'=>$one['description'] ?? '', //TODO proper sanitize for display
                 'text'=>$text,
                 'title'=>addslashes(str_replace(["\r\n","\r","\n"],[' ',' ',' '],$one['title'])), //TODO proper sanitize for display
                 'url'=>$url,
                ];
            }
            $oneset->matches = $tmp;
            $sections[] = $oneset;
         }
    }
    if( $sections ) {
        $tpl = $smarty->createTemplate($this->GetTemplateResource('adminsearch.tpl')); //,null,null,$smarty);
        if( $casewarn ) {
            $tpl->assign('casewarn',$this->Lang('warn_casedchars'));
        }
        $tpl->assign('sections',$sections);
    }
    else {
        $tpl = $smarty->createTemplate($this->GetTemplateResource('infosearch.tpl')); //,null,null,$smarty);
        $tpl->assign('message',$this->Lang('nomatch'));
    }
}
else {
    $tpl = $smarty->createTemplate($this->GetTemplateResource('infosearch.tpl')); //,null,null,$smarty);
    $tpl->assign('message',$this->Lang('error_noslaves'));
}

$tpl->display();
exit;
