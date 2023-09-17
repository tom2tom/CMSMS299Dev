<?php
/*
OutMailer module add/modify platform action
Copyright (C) 2022-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple module OutMailer.

The OutMailer module is free software; you may redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published
by the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

The OutMailer module is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <http://www.gnu.org/licenses/licenses.html#AGPL>.
*/

use CMSMS\FormUtils;
use CMSMS\ResourceMethods;

if (isset($params['cancel'])) {
    $this->Redirect($id, 'defaultadmin', '', ['activetab' => 'gates']);
}

if (isset($params['submit'])) {
    //TODO save stuff
    $this->Redirect($id, 'defaultadmin', '', ['activetab' => 'gates']);
}

if ($params['gate_id'] == -1) {
    $sql = 'INSERT INTO '.CMS_DB_PREFIX.'module_outmailer_platforms (alias,title,description) VALUES (\'---\', \'---\',\'todo\')';
    $db->Execute($sql);
    $gid = $db->Insert_ID();

    $sql = 'INSERT INTO '.CMS_DB_PREFIX.'module_outmailer_props (gate_id,title,apiname,enabled,apiorder) VALUES (?,\'---\',\'todo\',0,99)';
    $db->Execute($sql, [$gid]);
    $params['gate_id'] = $gid;
}

$sql = 'SELECT * FROM '.CMS_DB_PREFIX.'module_outmailer_platforms WHERE id=?';
$row = $db->GetRow($sql, [$params['gate_id']]);
if ($row) {
    $startform = FormUtils::create_form_start($this, ['id' => $id, 'action' => 'opengate']);
    $textarea_desc = FormUtils::create_textarea([
        'enablewysiwyg' => 1,
        'getid' => $id,
        'name' => 'description',
        'class' => 'pageextrasmalltextarea',
        'value' => $row['description'],
        'addtext' => 'rows= "3" cols="40" style="height:3em;"',
    ]);

    if ($this instanceof ResourceMethods) { // light-module
        $tpl = $this->GetTemplateObject('opengate.tpl');
    } else {
        $tpl = $smarty->createTemplate($this->GetTemplateResource('opengate.tpl')); //,null,null,$smarty);
    }
//   'message' => $message,
    $tpl->assign([
     'pagetitle' => $this->Lang('TODO Title'),
     'startform' => $startform,
     'gateid' => $row['id'],
     'title_active' => $this->Lang('active'),
     'value_active' => $row['active'],
     'title_alias' => $this->Lang('alias'),
     'value_alias' => $row['alias'],
     'title_desc' => $this->Lang('description'),
     'textarea_desc' => $textarea_desc,
     'title_enabled' => $this->Lang('enabled'),
     'value_enabled' => $row['enabled'],
     'title_title' => $this->Lang('title'),
     'value_title' => $row['title'],
     'gatetitle' => '',
     'hidden' => null,
     'space' => $row['alias'],
    ]);
} else {
    //TODO handle bad id
    if ($this instanceof ResourceMethods) { // light-module
        $tpl = $this->GetTemplateObject('TODOerror.tpl');
    } else {
        $tpl = $smarty->createTemplate($this->GetTemplateResource('TODOerror.tpl')); //,null,null,$smarty);
    }
//    $tpl->assign([]);
}

$tpl->display();
return '';
