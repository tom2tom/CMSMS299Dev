<?php
/*
Edit/add template action for CMSMS News module.
Copyright (C) 2019-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

if( !function_exists('cmsms') ) exit;
$fp = cms_join_path(CMS_ROOT_PATH, 'lib', 'assets', 'method.edittemplate.php');
if( is_file($fp) ) {
    $user_id = get_userid();
    $can_manage = check_permission($user_id, 'Modify News Preferences');  // || Modify Templates etc ??
    if( !$can_manage ) exit;
    $content_only = false; //TODO per actual permmissions

    $module = $this;
    $returntab = 'templates';

    if( $params['tpl'] > 0 ) {
        $title = $this->Lang('prompt_edittemplate');
    }
    else {
        $title = $this->Lang('prompt_addtemplate');
    }
    $show_buttons = true;
    $show_cancel = true;

    include_once $fp;
}
else {
    echo '<p class="page_error">'.lang('error_internal').'</p>';
}
return '';
