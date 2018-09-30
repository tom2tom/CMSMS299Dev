<?php
# DesignManager module action: process ajax call to populate templates
# Copyright (C) 2012-2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

$handlers = ob_list_handlers();
for ($cnt = 0, $n = sizeof($handlers); $cnt < $n; $cnt++) { ob_end_clean(); }

try {
    $tpl = $smarty->createTemplate($this->GetTemplateResource('ajax_get_templates.tpl'),null,null,$smarty);

    $tmp = get_parameter_value($_REQUEST,'filter');
    $filter = json_decode($tmp,TRUE);
    if( !$this->CheckPermission('Modify Templates') ) $filter[] = 'e:'.get_userid(false);
/*
    $tpl_query = new CmsLayoutTemplateQuery($filter);
    $templates = $tpl_query->GetMatches();
    if( $templates ) {
        $tpl->assign('templates',$templates);
        $tpl_nav = [];
        $tpl_nav['pagelimit'] = $tpl_query->limit;
        $tpl_nav['numpages'] = $tpl_query->numpages;
        $tpl_nav['numrows'] = $tpl_query->totalrows;
        $tpl_nav['curpage'] = (int)($tpl_query->offset / $tpl_query->limit) + 1;
        $tpl->assign('tpl_nav',$tpl_nav);
    }
*/
    $tpl->assign('tpl_filter',$filter)
		->assign('filterimage',cms_join_path(__DIR__,'images','filter'));

    include __DIR__.DIRECTORY_SEPARATOR.'method.TemplateQuery.php';
    if( $templates ) {
        $tpl->assign('templates', $templates)
         ->assign('tpl_nav', [
            'pagelimit' => $limit,
            'numpages' => $numpages,
            'numrows' => $totalrows,
            'curpage' => (int)($offset / $limit) + 1,
        ]);
    }

    $designs = CmsLayoutCollection::get_all();
    if( ($n = count($designs)) ) {
        $tpl->assign('list_designs',$designs);
        $tmp = [];
        for( $i = 0; $i < $n; $i++ ) {
            $tmp['d:'.$designs[$i]->get_id()] = $designs[$i]->get_name();
            $tmp2[$designs[$i]->get_id()] = $designs[$i]->get_name();
        }
        $tpl->assign('design_names',$tmp2);
    }

    $types = CmsLayoutTemplateType::get_all();
    $originators = [];
    if( ($n = count($types)) ) {
        $tmp = [];
        $tmp2 = [];
        $tmp3 = [];
        for( $i = 0; $i < $n; $i++ ) {
            $tmp['t:'.$types[$i]->get_id()] = $types[$i]->get_langified_display_value();
            $tmp2[$types[$i]->get_id()] = $types[$i]->get_langified_display_value();
            $tmp3[$types[$i]->get_id()] = $types[$i];
            if( !isset($originators[$types[$i]->get_originator()]) ) {
                $originators['o:'.$types[$i]->get_originator()] = $types[$i]->get_originator(TRUE);
            }
        }
        $tpl->assign('list_all_types',$tmp3)
         ->assign('list_types',$tmp2);
    }

    $locks = CmsLockOperations::get_locks('template');
    $tpl->assign('have_locks',$locks ? count($locks) : 0)
     ->assign('lock_timeout', $this->GetPreference('lock_timeout'))
     ->assign('coretypename',CmsLayoutTemplateType::CORE)
     ->assign('manage_templates',$this->CheckPermission('Modify Templates'))
     ->assign('manage_designs',$this->CheckPermission('Manage Designs'))
     ->assign('has_add_right',
                    $this->CheckPermission('Modify Templates') ||
                    $this->CheckPermission('Add Templates'));

    $tpl->display();
}
catch( Exception $e ) {
    echo '<div class="error">'.$e->GetMessage().'</div>';
}
exit;
