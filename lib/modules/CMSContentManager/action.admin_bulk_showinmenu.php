<?php
# CMSContentManager module action: flag multiple pages as in-menu
# Copyright (C) 2013-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\internal\global_cache;

if( !isset($gCms) ) exit;
if( !isset($action) || $action != 'admin_bulk_showinmenu' ) exit;

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
$i = 0;

try {
    foreach( $pagelist as $pid ) {
        $content = $this->GetContentEditor($pid);
        if( !is_object($content) ) continue;

        $content->SetShowInMenu($showinmenu);
        $content->SetLastModifiedBy($user_id);
        $content->Save();
        ++$i;
    }
    audit('','Content','Changed show-in-menu status of '.$i.' pages');
    $this->SetMessage($this->Lang('msg_bulk_successful'));
}
catch( Throwable $t ) {
    $this->SetError($t->getMessage());
}

global_cache::clear('content_quicklist');
global_cache::clear('content_tree');
global_cache::clear('content_flatlist');

$this->Redirect($id,'defaultadmin',$returnid);
