<?php
# DesignManager module action: defaultadmin
# Copyright (C) 2014-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use DesignManager\Design;

if( !isset($gCms) ) exit;
if( !$this->VisibleToAdminUser() ) return;

$pmod = $this->CheckPermission('Manage Designs');

/*
if( $pmod && isset($params['design_setdflt']) ) {
    $design_id = (int)$params['design_setdflt'];
    try {
        $cur_dflt = Design::load_default(); DISABLED
        if( is_object($cur_dflt) && $cur_dflt->get_id() != $design_id ) {
            $cur_dflt->set_default(false);
            $cur_dflt->save();
        }
    }
    catch( Exception $e ) {
        // do nothing
    }

    $new_dflt = Design::load($design_id);
    $new_dflt->set_default(true);
    $new_dflt->save();

    $this->SetCurrentTab('designs');
    $this->ShowMessage($this->Lang('msg_dflt_design_saved'));
}
*/

$tpl = $smarty->createTemplate($this->GetTemplateResource('listdesigns.tpl'),null,null,$smarty);

// build lists of designs and stuff which may be assigned to them.
$opts = ['' => $this->Lang('prompt_none')];

$designs = Design::get_all();
if( $designs && ($n = count($designs)) ) {
    $tpl->assign('list_designs',$designs);
    $tmp = [];
    for( $i = 0; $i < $n; $i++ ) {
        $tmp['d:'.$designs[$i]->get_id()] = $designs[$i]->get_name();
        $tmp2[$designs[$i]->get_id()] = $designs[$i]->get_name();
    }
    asort($tmp);
    asort($tmp2);
    $tpl->assign('design_names',$tmp2);
    $opts[$this->Lang('prompt_design')] = $tmp;
}

if( $pmod ) {
    $userops = UserOperations::get_instance();
    $allusers = $userops->LoadUsers();
    $users = [-1=>$this->Lang('prompt_unknown')];
    $tmp = [];
    for( $i = 0, $n = count($allusers); $i < $n; $i++ ) {
        $tmp['u:'.$allusers[$i]->id] = $allusers[$i]->username;
        $users[$allusers[$i]->id] = $allusers[$i]->username;
    }
    asort($tmp);
    asort($users);
    $tpl->assign('list_users',$users);
    $opts[$this->Lang('prompt_user')] = $tmp;
}

$tpl->assign('pmod',$pmod);

//$admin_url = $config['admin_url'];
//$tpl->assign('lock_timeout', $this->GetPreference('lock_timeout'));
//$url = $this->create_url($id,'ajax_get_templates');
//$ajax_templates_url = str_replace('amp;','',$url);
//$url = $this->create_url($id,'ajax_get_stylesheets');
//$ajax_stylesheets_url = str_replace('amp;','',$url);

//$sm = new ScriptOperations();
//$sm->queue_matchedfile('jquery.cmsms_autorefresh.js', 1);
//$sm->queue_matchedfile('jquery.ContextMenu.js', 2);

//$sm->queue_string($js, 3);
//$out = $sm->render_inclusion('', false, false);
//if ($out) {
//    $this->AdminBottomContent($out);
//}

$tpl->display();
