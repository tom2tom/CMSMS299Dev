<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Module: Content (c) 2013 by Robert Campbell
#         (calguy1000@cmsmadesimple.org)
#  A module for managing content in CMSMS.
#
#-------------------------------------------------------------------------
# CMS - CMS Made Simple is (c) 2004 by Ted Kulp (wishy@cmsmadesimple.org)
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#-------------------------------------------------------------------------
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
#
#-------------------------------------------------------------------------
#END_LICENSE
if( !isset($gCms) ) exit;

$this->SetCurrentTab('pages');
if( !$this->CheckPermission('Manage All Content') ) {
    $this->SetError($this->Lang('error_bulk_permission'));
    $this->RedirectToAdminTab();
}
if( !isset($params['multicontent']) ) {
    $this->SetError($this->Lang('error_missingparam'));
    $this->RedirectToAdminTab();
}

$cachable = 1;
if( isset($params['cachable']) ) $cachable = (int)$params['cachable'];

$multicontent = unserialize(base64_decode($params['multicontent']));
if( count($multicontent) == 0 ) {
    $this->SetError($this->Lang('error_missingparam'));
    $this->RedirectToAdminTab();
}

// do the real work
try {
    $contentops = ContentOperations::get_instance()->LoadChildren(-1,FALSE,TRUE,$multicontent);
    $hm = cmsms()->GetHierarchyManager();
    $i = 0;
    foreach( $multicontent as $pid ) {
        $node = $hm->find_by_tag('id',$pid);
        if( !$node ) continue;
        $content = $node->getContent(FALSE,FALSE,TRUE);
        if( !is_object($content) ) continue;
        $content->SetCachable($cachable);
        $content->SetLastModifiedBy(get_userid());
        $content->Save();
        $i++;
    }
    audit('','Content','Changed cachable status on '.count($multicontent).' pages');
    $this->SetMessage($this->Lang('msg_bulk_successful'));
}
catch( Exception $e ) {
    $this->SetError($e->GetMessage());
}
$this->RedirectToAdminTab();

#
# EOF
#
