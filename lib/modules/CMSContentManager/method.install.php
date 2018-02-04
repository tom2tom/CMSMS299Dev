<?php
#-------------------------------------------------------------------------
# CMSContentManager - A CMSMS module to provide page-content management.
# Copyright (C) 2013-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
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
#-------------------------------------------------------------------------
if (!isset($gCms)) {
    exit;
}

$this->SetPreference('locktimeout', 60);
$this->SetPreference('lockrefresh', 120);

$this->CreatePermission('Add Pages', $this->Lang('perm_add'));
$this->CreatePermission('Manage All Content', $this->Lang('perm_manage'));
$this->CreatePermission('Modify Any Page', $this->Lang('perm_modify'));
$this->CreatePermission('Remove Pages', $this->Lang('perm_remove'));
$this->CreatePermission('Reorder Content', $this->Lang('perm_reorder'));

//CHECKME DesignManager race when 1st installing?
$group_id = $db->GetOne('SELECT group_id FROM '.CMS_DB_PREFIX."groups WHERE group_name = 'Editor'");
if (!$group_id) {
	$editor_group = new Group();
	$editor_group->name = 'Editor';
	$editor_group->description = $this->Lang('group_desc');
	$editor_group->active = 1;
	CMSMS\HookManager::do_hook('Core::AddGroupPre', ['group'=>&$editor_group]);
	$editor_group->Save();
	CMSMS\HookManager::do_hook('Core::AddGroupPost', ['group'=>&$editor_group]);

	$editor_group->GrantPermission('Manage All Content');
	$editor_group->GrantPermission('Manage My Account');
	$editor_group->GrantPermission('Manage My Bookmarks');
	$editor_group->GrantPermission('Manage My Settings');
	$editor_group->GrantPermission('View Tag Help');
}
