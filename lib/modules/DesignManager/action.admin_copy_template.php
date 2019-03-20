<?php
# DesignManager module action: copy template
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

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Templates') ) {
    // no manage templates permission
    if( !$this->CheckPermission('Add Templates') ) {
        // no add templates permission
        if( !isset($params['tpl']) || !TemplateOperations::user_can_edit($params['tpl']) ) {
            // no parameter, or no ownership/addt_editors.
            return;
        }
    }
}

$this->SetCurrentTab('templates');
if( !isset($params['tpl']) ) {
    $this->SetError($this->Lang('error_missingparam'));
    $this->RedirectToAdminTab();
}
if( isset($params['cancel']) ) {
    $this->SetInfo($this->Lang('msg_cancelled'));
    $this->RedirectToAdminTab();
}

try {
    $orig_tpl = TemplateOperations::load_template($params['tpl']);

    if( isset($params['submit']) || isset($params['apply']) ) {

        try {
            $new_tpl = clone($orig_tpl);
            $new_tpl->set_owner(get_userid());
            $new_tpl->set_name(trim($params['new_name']));
            $new_tpl->set_additional_editors([]);

            // only if have manage themes right.
            if( $this->CheckPermission('Modify Designs') ) {
				$new_tpl->set_designs($orig_tpl->get_designs());
            }
            else {
				$new_tpl->set_designs([]);
            }
            $new_tpl->save();

            if( isset($params['apply']) ) {
				$this->SetMessage($this->Lang('msg_template_copied_edit'));
				$this->Redirect($id,'admin_edit_template',$returnid,['tpl'=>$new_tpl->get_id()]);
            }
            else {
				$this->SetMessage($this->Lang('msg_template_copied'));
				$this->RedirectToAdminTab();
            }
        }
        catch( CmsException $e ) {
            $this->ShowErrors($e->GetMessage());
        }
    }

    // build a display.
    $tpl = $smarty->createTemplate($this->GetTemplateResource('admin_copy_template.tpl'),null,null,$smarty);

    $cats = CmsLayoutTemplateCategory::get_all();
    $out = [];
    $out[0] = $this->Lang('prompt_none');
    if( $cats ) {
        foreach( $cats as $one ) {
            $out[$one->get_id()] = $one->get_name();
        }
    }
    $tpl->assign('category_list',$out);

    $types = CmsLayoutTemplateType::get_all();
    if( $types ) {
        $out = [];
        foreach( $types as $one ) {
            $out[$one->get_id()] = $one->get_langified_display_value();
        }
        $tpl->assign('type_list',$out);
    }

    $designs = CmsLayoutCollection::get_all();
    if( $designs ) {
        $out = [];
        foreach( $designs as $one ) {
            $out[$one->get_id()] = $one->get_name();
        }
        $tpl->assign('design_list',$out);
    }

    $userops = cmsms()->GetUserOperations();
    $allusers = $userops->LoadUsers();
    $tmp = [];
    foreach( $allusers as $one ) {
        $tmp[$one->id] = $one->username;
    }
    if( $tmp ) {
        $tpl->assign('user_list',$tmp);
    }

    $new_name = $orig_tpl->get_name();
    $p = strrpos($new_name,' -- ');
    $n = 2;
    if( $p !== FALSE ) {
        $n = (int)substr($new_name,$p+4)+1;
        $new_name = substr($new_name,0,$p);
    }
    $new_name .= ' -- '.$n;
    $tpl->assign('new_name',$new_name);

    $tpl->assign('tpl',$orig_tpl);
    $tpl->display();
}
catch( CmsException $e ) {
    $this->SetError($e->GetMessage());
    $this->RedirectToAdminTab();
}
