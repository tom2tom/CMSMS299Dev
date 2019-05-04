<?php
# CMSContentManager module action: flag multiple pages as [not] cachable
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

use CMSMS\ContentOperations;

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
$hm = cmsms()->GetHierarchyManager();

// do the real work
try {
    ContentOperations::get_instance()->LoadChildren(-1,FALSE,TRUE,$pagelist);
    $i = 0;
    foreach( $pagelist as $pid ) {
        $node = $hm->find_by_tag('id',$pid);
        if( !$node ) continue;
        $content = $node->getContent(FALSE,FALSE,TRUE);
        if( !is_object($content) ) continue;
        $content->SetCachable($cachable);
        $content->SetLastModifiedBy(get_userid());
        $content->Save();
        $i++;
    }
    audit('','Content','Changed cachable status on '.$i.' pages');
    $this->SetMessage($this->Lang('msg_bulk_successful'));
}
catch( Throwable $t ) {
    $this->SetError($t->getMessage());
}
$this->Redirect($id,'defaultadmin',$returnid);
