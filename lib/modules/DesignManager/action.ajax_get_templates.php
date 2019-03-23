<?php
# DesignManager module action: process ajax call to populate templates
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
use CMSMS\TemplateOperations;

$handlers = ob_list_handlers();
for( $i = 0, $n = count($handlers); $i < $n; ++$i ) { ob_end_clean(); }

$pmod = $this->CheckPermission('Modify Templates');
$padd = $pmod || $this->CheckPermission('Add Templates');
$lock_timeout = $this->GetPreference('lock_timeout');

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
        $theme = cms_utils::get_theme_object();

        $u = $this->create_url($id, 'admin_edit_template', $returnid, ['tpl'=>'XXX']);
        $t = $this->Lang('prompt_edit');
        $icon = $theme->DisplayImage('icons/system/edit', $t, '', '', 'systemicon');
        $linkedit = '<a href="'.$u.'" data-tpl-id="XXX" class="edit_tpl">'.$icon.'</a>'."\n";

        $t = $this->Lang('prompt_steal_lock');
        $icon = $theme->DisplayImage('icons/system/permissions', $t, '', '', 'systemicon edit_tpl steal_tpl_lock');
        $linksteal = '<a href="'.$u.'" data-tpl-id="XXX" accesskey="e" class="steal_tpl_lock">'.$icon.'</a>'."\n";

        if( $padd ) {
            $u = $this->create_url($id, 'admin_copy_template', $returnid, ['tpl'=>'XXX']);
            $t = $this->Lang('prompt_copy_template');
            $icon = $theme->DisplayImage('icons/system/copy', $t, '', '', 'systemicon');
            $linkcopy = '<a href="'.$u.'">'.$icon.'</a>'."\n";
        }

        $u = $this->create_url($id, 'admin_delete_template', $returnid, ['tpl'=>'XXX']);
        $t = $this->Lang('delete_template');
        $icon = $theme->DisplayImage('icons/system/delete', $t, '', '', 'systemicon');
        $linkdel = '<a href="'.$u.'">'.$icon.'</a>'."\n";
/*
//<a href="{$edit_tpl}" data-tpl-id="{$template->get_id()}" class="edit_tpl" title="{$mod->Lang('edit_template')}">{admin_icon icon='edit.gif' title=$mod->Lang('prompt_edit')}</a></td>
//<a href="{$copy_tpl}" title="{$mod->Lang('copy_template')}">{admin_icon icon='copy.gif' title=$mod->Lang('prompt_copy_template')}</a></td>
//<a href="{$edit_tpl}" data-tpl-id="{$template->get_id()}" accesskey="e" class="steal_tpl_lock">{admin_icon icon='permissions.gif' class='edit_tpl steal_tpl_lock' title=$mod->Lang('prompt_steal_lock')}</a>
//<a href="{$delete_tpl}" title="{$mod->Lang('delete_template')}">{admin_icon icon='delete.gif' title=$mod->Lang('delete_template')}</a>
*/
        $now = time();
        $menus = [];
        for( $i = 0, $n = count($templates); $i < $n; ++$i ) {
            $acts = [];
            $template = $templates[$i];
            $tid = $template->get_id();

            if( !$lock_timeout || !$template->locked() ) {
                $acts[] = ['content'=>str_replace('XXX', $tid, $linkedit)];
                if( $padd ) {
                    $acts[] = ['content'=>str_replace('XXX', $tid, $linkcopy)];
                }
            } else {
                $lock = $template->get_lock();
                if( $lock['expires'] < $now ) {
                    $acts[] = ['content'=>str_replace('XXX', $tid, $linksteal)];
                }
            }

            if( !$template->get_type_dflt() && !$template->locked() ) {
                if( $pmod || $template->get_owner_id() == get_userid() ) {
                    $acts[] = ['content'=>str_replace('XXX', $tid, $linkdel)];
                }
            }

            if( $acts ) {
                $menus[] = FormUtils::create_menu($acts, ['id'=>'Template'.$tid, 'class'=>'ContextMenu']);
            }
        }

        $tpl->assign('templates', $templates)
         ->assign('menus2', $menus)
         ->assign('tpl_nav', [
            'pagelimit' => $limit,
            'numpages' => $numpages,
            'numrows' => $totalrows,
            'curpage' => (int)($offset / $limit) + 1,
        ]);
    }
    else {
		$db = CmsApp::get_instance()->GetDb();
		$query = 'SELECT EXISTS (SELECT 1 FROM '.CMS_DB_PREFIX.TemplateOperations::TABLENAME.')';
		if( $db->GetOne($query) ) {
			$tpl->assign('templates',false); //signal row(s) exist, but none matches
		}
    }

    $designs = CmsLayoutCollection::get_all();
    if( $designs ) {
        $tpl->assign('list_designs',$designs);
        $tmp2 = [];
        for( $i = 0, $n = count($designs); $i < $n; ++$i ) {
            $tmp2[$designs[$i]->get_id()] = $designs[$i]->get_name();
        }
        $tpl->assign('design_names',$tmp2);
    }

    $types = CmsLayoutTemplateType::get_all();
    if( $types ) {
        $originators = [];
        $tmp = [];
        $tmp2 = [];
        $tmp3 = [];
        for( $i = 0, $n = count($types); $i < $n; ++$i ) {
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

    $locks = LockOperations::get_locks('template');
    $tpl->assign('have_locks',$locks ? count($locks) : 0)
     ->assign('lock_timeout',$lock_timeout)
     ->assign('coretypename',CmsLayoutTemplateType::CORE)
     ->assign('manage_templates',$pmod)
     ->assign('has_add_right',$padd)
     ->assign('manage_designs',$this->CheckPermission('Manage Designs'));

    $tpl->display();
}
catch( Exception $e ) {
    echo '<div class="error">'.$e->GetMessage().'</div>';
}
exit;
