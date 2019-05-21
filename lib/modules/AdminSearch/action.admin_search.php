<?php
# AdminSearch module action: ajax-processor to search database tables
# Copyright (C) 2012-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use AdminSearch\tools;

if( !isset($gCms) ) exit;
if( !$this->VisibleToAdminUser() ) exit;

function utf8_urldecode($str)
{
    $str = preg_replace("/%u([0-9a-f]{3,4})/i","&#x\\1;",urldecode($str));
    return html_entity_decode($str,null,'UTF-8');
}

// end/disable buffering
$handlers = ob_list_handlers();
for ($cnt = 0, $n = count($handlers); $cnt < $n; ++$cnt) { ob_end_clean(); }

if( !isset($params['search_text']) || $params['search_text'] === '' ) {
    echo '<p class="red">'.$this->Lang('error_nosearchtext').'</p>';
    exit;
}
if( empty($params['slaves']) ) {
    echo '<p class="red">'.$this->Lang('error_noscopes').'</p>';
    exit;
}

// find search-slave classes
$slaves = tools::get_slave_classes();
if( $slaves ) {
     // cache this search
    $searchparams = [
     'search_text' => utf8_urldecode($params['search_text']),
     'slaves' => explode(',',$params['slaves']),
     'search_descriptions' => !empty($params['search_descriptions']),
	];
    $userid = get_userid(false);
    cms_userprefs::set_for_user($userid,$this->GetName().'saved_search',serialize($searchparams));

	$types = $searchparams['slaves'];
	unset($searchparams['slaves']);

    $sections = [];
    foreach( $slaves as $one_slave ) {
        if( !in_array($one_slave['class'],$types) ) {
            continue;
        }
        //assume a module must be present for its associated classes to function ...
        $module = cms_utils::get_module($one_slave['module']);
        if( !is_object($module) ) {
            continue;
        }
        $obj = new $one_slave['class'];
        if( !is_object($obj) ) {
            continue;
        }
        if( !is_subclass_of($obj,'AdminSearch\\slave') ) {
            continue;
        }
        if( !$obj->check_permission() ) {
            continue;
        }

        $obj->set_params($searchparams);
        $results = $obj->get_matches();
        if( $results ) {
            $oneset = new stdClass();
            $oneset->id = $one_slave['class'];
            $oneset->lbl = $obj->get_name();
            $oneset->desc = $obj->get_section_description();
            $oneset->count = count($results);
            $tmp = [];
            foreach( $results as $one ) {
                $text = $one['text'] ?? '';
                if( $text ) $text = addslashes($text);
                $url = $one['edit_url'] ?? '';
                if( $url ) $url = str_replace('&amp;','&',$url);
                $tmp[] = [
                 'description'=>$one['description'] ?? '',
                 'text'=>$text,
                 'title'=>addslashes(str_replace(["\r\n","\r","\n"],[' ',' ',' '],$one['title'])),
                 'url'=>$url,
                ];
            }
            $oneset->matches = $tmp;
            $sections[] = $oneset;
         }
    }
    if( $sections ) {
        $tpl = $smarty->createTemplate($this->GetTemplateResource('adminsearch.tpl'),null,null,$smarty);
        $tpl->assign('sections',$sections);
        $tpl->display();
    }
	else {
		echo '<p class="pageinput">'.$this->Lang('nomatch').'</p>';
	}
}
else {
	echo '<p class="red">'.$this->Lang('error_noslaves').'</p>';
}

exit;
