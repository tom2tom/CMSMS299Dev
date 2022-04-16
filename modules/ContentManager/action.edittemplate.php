<?php
/*
ContentManager module action: edit page template
Copyright (C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

//if( some worthy test fails ) exit;

if( isset($params['cancel']) ) {
    $this->Redirect($id,'defaultadmin',$returnid);
}

// the selected template might apply to multiple pages, so the user must have a general permission
if( !($this->CheckPermission('Modify Any Page') || $this->CheckPermission('Modify Templates')) ) {
    $this->SetError($this->Lang('error_editpage_permission'));
    $this->Redirect($id,'defaultadmin',$returnid);
}

$fp = cms_join_path(CMS_ROOT_PATH, 'lib', 'method.edittemplate.php');
if( is_file($fp) ) {

    $userid = get_userid(false);
    $can_manage = true;
    $content_only = true;

    $module = $this;
    $returntab = ''; // N/A for this module

    if( $params['tpl'] > 0 ) {
        $title = _ld('layout', 'prompt_edit_template');
    }
    else {
        $title = _ld('layout', 'create_template');
    }
    $show_buttons = true;
    $show_cancel = true;

    include_once $fp;
    return;
}

log_error('Missing file', 'method.edittemplate.php');
$this->SetError(_ld('error_internal').': Missing script file');
$this->Redirect($id,'defaultadmin',$returnid);
