<?php
/*
Edit/add template action for CMSMS News module.
Copyright (C) 2019-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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
$fp = cms_join_path(CMS_ROOT_PATH, 'lib', 'method.edittemplate.php');
if( is_file($fp) ) {
    $userid = get_userid();
    $can_manage = check_permission($userid, 'Modify News Preferences');  // || Modify Templates etc ??
    if( !$can_manage ) exit;
    $content_only = false; //TODO per actual permmissions
    $show_buttons = true;
    $show_cancel = true;

    $module = $this;
    $returntab = 'templates';

    if( $params['tpl'] > 0 ) {
        $title = $this->Lang('prompt_edittemplate');
    }
    else {
        $title = $this->Lang('prompt_addtemplate');
    }

    include_once $fp;
}
else {
    echo '<p class="page_error">'.lang('error_internal').'</p>';
}
