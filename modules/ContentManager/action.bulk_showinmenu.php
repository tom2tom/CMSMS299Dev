<?php
/*
ContentManager module action: flag multiple pages as in-menu
Copyright (C) 2013-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\SingleItem;
use function CMSMS\log_error;
use function CMSMS\log_notice;

//if( some worthy test fails ) exit;
if( !isset($action) || $action != 'bulk_showinmenu' ) exit;

if( !$this->CheckPermission('Manage All Content') ) {
    $this->SetError($this->Lang('error_bulk_permission'));
    $this->Redirect($id,'defaultadmin',$returnid);
}
if( !isset($params['bulk_content']) ) {
    $this->SetError($this->Lang('error_missingparam'));
    $this->Redirect($id,'defaultadmin',$returnid);
}

$pagelist = $params['bulk_content'];
$showinmenu = !empty($params['showinmenu']);
$user_id = get_userid();
$n = 0;

try {
    foreach( $pagelist as $pid ) {
        $content = $this->GetContentEditor($pid);
        if( !is_object($content) ) continue;

        $content->SetShowInMenu($showinmenu);
        $content->SetLastModifiedBy($user_id);
        $content->Save();
        ++$n;
    }
    log_notice('ContentManager','Changed show-in-menu status of '.$n.' pages');
    $this->SetMessage($this->Lang('msg_bulk_successful'));
}
catch (Throwable $t) {
    log_error('Multi-page in-menu change failed',$t->getMessage());
    $this->SetError($t->getMessage());
}

$cache = SingleItem::LoadedData();
// TODO or refresh() & save, ready for next stage ?
$cache->delete('content_quicklist');
$cache->delete('content_tree');
$cache->delete('content_flatlist');

$this->Redirect($id,'defaultadmin',$returnid);
