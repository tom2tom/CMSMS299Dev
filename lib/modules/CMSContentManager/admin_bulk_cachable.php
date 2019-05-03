<?php
# CMSContentManager module action: flag multiple pages as cachable
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
if( !isset($params['bulk_content']) ) {
    $this->SetError($this->Lang('error_missingparam'));
    $this->RedirectToAdminTab();
}

$active = 0;
if( isset($params['active']) ) $active = (int)$params['active'];

$multicontent = [];
if( $this->CheckPermission('Manage All Content') || $this->CheckPermission('Modify Any Page') ) {
    $multicontent = unserialize($params['bulk_content']);
}
else {
    foreach( unserialize($params['bulk_content']) as $pid ) {
        if( !check_authorship(get_userid(),$pid) ) continue;
        $multicontent[] = $pid;
    }
}
if( count($multicontent) == 0 ) {
    $this->SetError($this->Lang('error_missingparam'));
    $this->RedirectToAdminTab();
}

// do the real work
try {
    $contentops = ContentOperations::get_instance()->LoadChildren(-1,FALSE,TRUE,$multicontent);
    $hm = cmsms()->GetHierarchyManager();
    foreach( $multicontent as $pid ) {
        $node = $hm->find_by_tag('id',$pid);
        if( !$node ) continue;
        $content = $node->getContent(FALSE,FALSE,TRUE);
        if( !is_object($content) ) continue;
        $content->SetActive($active);
        $content->SetLastModifiedBy(get_userid());
        $content->Save();
    }
    audit('','Content','Changed active status on '.count($multicontent).' pages');
    $this->SetMessage($this->Lang('msg_bulk_successful'));
}
catch( Exception $e ) {
    $this->SetError($e->GetMessage());
}
$this->RedirectToAdminTab();
