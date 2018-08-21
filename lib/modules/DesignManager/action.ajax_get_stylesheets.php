<?php
# DesignManager module action: get stylesheets via ajax
# Copyright (C) 2012-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
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
for ($cnt = 0; $cnt < sizeof($handlers); $cnt++) { ob_end_clean(); }

try {
    if( !$this->CheckPermission('Manage Stylesheets') ) throw new Exception($this->Lang('error_permission'));
    $tmp = get_parameter_value($_REQUEST,'filter');
    if( !$tmp ) throw new Exception($this->Lang('error_missingparam'));

    $tpl = $smarty->createTemplate($this->GetTemplateResource('ajax_get_stylesheets.tpl'),null,null,$smarty);
    $filter = json_decode($tmp,TRUE);
    $tpl->assign('css_filter',$filter);

    $designs = CmsLayoutCollection::get_all();
    if( count($designs) ) {
        $tpl->assign('list_designs',$designs);
        $tmp = [];
        for( $i = 0; $i < count($designs); $i++ ) {
            $tmp['d:'.$designs[$i]->get_id()] = $designs[$i]->get_name();
            $tmp2[$designs[$i]->get_id()] = $designs[$i]->get_name();
        }
        $tpl->assign('design_names',$tmp2);
    }

	$css_query = new CmsLayoutStylesheetQuery($filter);
	$csslist = $css_query->GetMatches();
	$tpl->assign('stylesheets',$csslist);
	$css_nav = [];
	$css_nav['pagelimit'] = $css_query->limit;
	$css_nav['numpages'] = $css_query->numpages;
	$css_nav['numrows'] = $css_query->totalrows;
	$css_nav['curpage'] = (int)($css_query->offset / $css_query->limit) + 1;
	$tpl->assign('css_nav',$css_nav)
     ->assign('manage_designs',$this->CheckPermission('Manage Designs'));
    $locks = \CmsLockOperations::get_locks('stylesheet');
    $tpl->assign('have_css_locks',($locks) ? count($locks) : 0)
     ->assign('lock_timeout', $this->GetPreference('lock_timeout'));

    $tpl->display();
}
catch( Exception $e ) {
    echo '<div class="error">'.$e->GetMessage().'</div>';
}
exit;
