<?php
# DesignManager module action: get stylesheets via ajax
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

use CMSMS\FormUtils;
use CMSMS\LockOperations;
use CMSMS\StylesheetOperations;

$handlers = ob_list_handlers();
for( $i = 0, $n = count($handlers); $i < $n; ++$i ) { ob_end_clean(); }

try {
    $tmp = get_parameter_value($_REQUEST,'filter');
    if( !$tmp ) throw new Exception($this->Lang('error_missingparam'));
    $filter = json_decode($tmp,TRUE);

    $tpl = $smarty->createTemplate($this->GetTemplateResource('ajax_get_stylesheets.tpl'),null,null,$smarty);

    $tpl->assign('css_filter',$filter)
        ->assign('filterimage',cms_join_path(__DIR__,'images','filter'));

    $designs = CmsLayoutCollection::get_all();
    if( $designs ) {
        $tpl->assign('list_designs',$designs);
        for( $i = 0, $n = count($designs); $i < $n; ++$i ) {
            $did = $designs[$i]->get_id();
            $tmp2[$did] = $designs[$i]->get_name();
        }
        $tpl->assign('design_names',$tmp2);
    }

    $tpl->assign('has_add_right',$this->CheckPermission('Manage Stylesheets'));

    $css_query = new CmsLayoutStylesheetQuery($filter);
    $csslist = $css_query->GetMatches();
	if( $csslist ) {
		$theme = cms_utils::get_theme_object();
		$u = $this->create_url($id, 'admin_edit_css', $returnid, ['css'=>'XXX']);
		$t = $this->Lang('edit_stylesheet');
		$icon = $theme->DisplayImage('icons/system/edit', $t, '', '', 'systemicon');
		$linkedit = '<a href="'.$u.'" data-css-id="XXX" class="edit_css">'.$icon.'</a>'."\n";

		$t = $this->Lang('prompt_steal_lock');
		$icon = $theme->DisplayImage('icons/system/permissions', $t, '', '', 'systemicon edit_css steal_css_lock');
		$linksteal = '<a href="'.$u.'" data-css-id="XXX" accesskey="e" class="steal_css_lock">'.$icon.'</a>'."\n";

		$u = $this->create_url($id, 'admin_copy_css', $returnid, ['css'=>'XXX']);
		$t = $this->Lang('copy_stylesheet');
		$icon = $theme->DisplayImage('icons/system/copy', $t, '', '', 'systemicon');
		$linkcopy = '<a href="'.$u.'">'.$icon.'</a>'."\n";

		$u = $this->create_url($id, 'admin_delete_css', $returnid, ['css'=>'XXX']);
		$t = $this->Lang('delete_stylesheet');
		$icon = $theme->DisplayImage('icons/system/delete', $t, '', '', 'systemicon');
		$linkdel = '<a href="'.$u.'">'.$icon.'</a>'."\n";

		$now = time();
		$menus = [];
		for( $i = 0, $n = count($csslist); $i < $n; ++$i ) {
			$acts = [];
			$css = $csslist[$i];
			$cid = $css->get_id();
			if( !$css->locked() ) {
				$acts[] = ['content'=>str_replace('XXX', $cid, $linkedit)];
				$acts[] = ['content'=>str_replace('XXX', $cid, $linkcopy)];
				$acts[] = ['content'=>str_replace('XXX', $cid, $linkdel)];
			}
			else {
			   $lock = $css->get_lock();
			   if( $lock['expires'] < $now ) {
				   $acts[] = ['content'=>str_replace('XXX', $cid, $linksteal)];
			   }
			}
			if( $acts ) {
				$menus[] = FormUtils::create_menu($acts, ['id'=>'Style'.$cid, 'class'=>'ContextMenu']);
			}
		}
		$tpl->assign('stylesheets',$csslist)
		 ->assign('menus1',$menus);

		$css_nav = [];
		$css_nav['pagelimit'] = $css_query->limit;
		$css_nav['numpages'] = $css_query->numpages;
		$css_nav['numrows'] = $css_query->totalrows;
		$css_nav['curpage'] = (int)($css_query->offset / $css_query->limit) + 1;
		$tpl->assign('css_nav',$css_nav)
			->assign('manage_designs',$this->CheckPermission('Manage Designs'));
		$locks = LockOperations::get_locks('stylesheet');
		$tpl->assign('have_css_locks',(($locks) ? count($locks) : 0))
			->assign('lock_timeout', $this->GetPreference('lock_timeout'));
	}
	else {
		$db = CmsApp::get_instance()->GetDb();
		$query = 'SELECT EXISTS (SELECT 1 FROM '.CMS_DB_PREFIX.StylesheetOperations::TABLENAME.')';
		if( $db->GetOne($query) ) {
			$tpl->assign('stylesheets',false); //signal rows exist, but none matches
		}
	}

    $tpl->display();
}
catch( Exception $e ) {
    echo '<div class="error">'.$e->GetMessage().'</div>';
}
exit;
