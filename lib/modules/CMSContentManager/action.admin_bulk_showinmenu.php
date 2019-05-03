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

if( !isset($gCms) ) exit;

$this->SetCurrentTab('pages');
if( !$this->CheckPermission('Manage All Content') ) {
    $this->SetError($this->Lang('error_bulk_permission'));
    $this->RedirectToAdminTab();
}
if( !isset($params['bulk_content']) ) {
    $this->SetError($this->Lang('error_missingparam'));
    $this->RedirectToAdminTab();
}

$showinmenu = 1;
if( isset($params['showinmenu']) ) $showinmenu = (int)$params['showinmenu'];

$multicontent = unserialize(base64_decode($params['bulk_content']));
if( !$multicontent ) {
    $this->SetError($this->Lang('error_missingparam'));
    $this->RedirectToAdminTab();
}

// do the real work
try {
    ContentOperations::get_instance()->LoadChildren(-1,FALSE,TRUE,$multicontent);
    $hm = cmsms()->GetHierarchyManager();

    foreach( $multicontent as $pid ) {
        $node = $hm->find_by_tag('id',$pid);
        if( !$node ) continue;
        $content = $node->getContent(FALSE,FALSE,TRUE);
        if( !is_object($content) ) continue;
        $content->SetShowInMenu($showinmenu);
        $content->SetLastModifiedBy(get_userid());
        $content->Save();
    }
    audit('','Content','Changed show-in-menu status on '.count($multicontent).' pages');
    $this->SetMessage($this->Lang('msg_bulk_successful'));
}
catch( Exception $e ) {
    $this->SetError($e->GetMessage());
}
$this->RedirectToAdminTab();
