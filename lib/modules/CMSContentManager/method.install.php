<?php
# CMSContentManager module installation process
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

use CMSMS\ContentTypeOperations;
use CMSMS\Events;
use CMSMS\Group;

if (!isset($gCms)) {
    exit;
}

ContentTypeOperations::get_instance()->RebuildStaticContentTypes();

$this->SetPreference('locktimeout', 60);
$this->SetPreference('lockrefresh', 120);

$this->CreatePermission('Add Pages', $this->Lang('perm_add'));
$this->CreatePermission('Manage All Content', $this->Lang('perm_manage'));
$this->CreatePermission('Modify Any Page', $this->Lang('perm_modify'));
$this->CreatePermission('Remove Pages', $this->Lang('perm_remove'));
$this->CreatePermission('Reorder Content', $this->Lang('perm_reorder'));

$group = new Group();
$group->name = 'Editor';
$group->description = $this->Lang('group_desc');
$group->active = 1;
Events::SendEvent('Core', 'AddGroupPre', ['group'=>&$group]);
$group->Save();
Events::SendEvent('Core', 'AddGroupPost', ['group'=>&$group]);

$group->GrantPermission('Manage All Content');
$group->GrantPermission('Manage My Account');
$group->GrantPermission('Manage My Bookmarks');
$group->GrantPermission('Manage My Settings');
$group->GrantPermission('View Tag Help');

$me = $this->GetName();
// register events for which other parts of the system may listen
foreach([
 'ContentDeletePost',
 'ContentDeletePre',
 'ContentEditPost',
 'ContentEditPre',
 'ContentPostCompile',
 'ContentPostRender',
 'ContentPreCompile',
 'ContentPreRender', // 2.2
] as $name) {
    Events::CreateEvent($me,$name); //since 2.3
    Events::CreateEvent('Core',$name); //deprecated since 2.3, migrated from the main installer
}
