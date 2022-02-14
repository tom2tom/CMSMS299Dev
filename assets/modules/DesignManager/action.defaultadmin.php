<?php
/*
DesignManager module action: defaultadmin
Copyright (C) 2014-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\SingleItem;
use DesignManager\Design;

//if( some worthy test fails ) exit;
if( !$this->VisibleToAdminUser() ) exit;

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
    catch( Throwable $t ) {
        // do nothing
    }

    $new_dflt = Design::load($design_id);
    $new_dflt->set_default(true);
    $new_dflt->save();

    $this->SetCurrentTab('designs');
    $this->ShowMessage($this->Lang('msg_dflt_design_saved'));
}
*/

$tpl = $smarty->createTemplate($this->GetTemplateResource('listdesigns.tpl')); //,null,null,$smarty);

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
    $allusers = SingleItem::UserOperations()->LoadUsers();
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

//$tpl->assign('lock_timeout', $this->GetPreference('lock_timeout', 60));
//$ajax_templates_url = $this->create_action_url($id,'ajax_get_templates');
//$ajax_stylesheets_url = $this->create_action_url($id,'ajax_get_stylesheets');

//$jsm = new ScriptsMerger();
//$jsm->queue_matchedfile('jquery.cmsms_autorefresh.js', 1);
//$jsm->queue_matchedfile('jquery.ContextMenu.js', 2);

//$jsm->queue_string($js, 3);
//$out = $jsm->page_content();
//if ($out) {
//    add_page_foottext($out);
//}

$tpl->display();
