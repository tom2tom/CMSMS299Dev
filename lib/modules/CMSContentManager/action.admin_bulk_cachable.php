<?php
/*
CMSContentManager module action: flag multiple pages as [not] cachable
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

use CMSMS\AppSingle;

if( !isset($gCms) ) exit;
if( !isset($action) || $action != 'admin_bulk_cachable' ) exit;

if( !$this->CheckPermission('Manage All Content') ) {
    $this->SetError($this->Lang('error_bulk_permission'));
    $this->Redirect($id,'defaultadmin',$returnid);
}
if( !isset($params['bulk_content']) ) {
    $this->SetError($this->Lang('error_missingparam'));
    $this->Redirect($id,'defaultadmin',$returnid);
}

$pagelist = $params['bulk_content'];
$cachable = !empty($params['cachable']);
$user_id = get_userid();
$i = 0;

try {
    foreach( $pagelist as $pid ) {
        $content = $this->GetContentEditor($pid);
        if( !is_object($content) ) continue;

        $content->SetCachable($cachable);
        $content->SetLastModifiedBy($user_id);
        $content->Save();
        ++$i;
    }
    audit('','Content','Changed cachable status on '.$i.' pages');
    $this->SetMessage($this->Lang('msg_bulk_successful'));
}
catch( Throwable $t ) {
    $this->SetError($t->getMessage());
}
$cache = AppSingle::SysDataCache();
$cache->release('content_quicklist');
$cache->release('content_tree');
$cache->release('content_flatlist');

$this->Redirect($id,'defaultadmin',$returnid);
