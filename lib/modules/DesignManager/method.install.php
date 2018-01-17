<?php
#-------------------------------------------------------------------------
# DesignManager - A CMSMS module to provide template management.
# Copyright (C) 2012-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
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
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
# Or read it online, at https://www.gnu.org/licenses/gpl-2.0.html
#-------------------------------------------------------------------------
if (!isset($gCms)) {
    exit;
}

$this->SetPreference('lock_timeout', 60);
$this->SetPreference('lock_refresh', 120);

$this->CreatePermission('Add Templates', $this->Lang('perm_add'));
$this->CreatePermission('Manage Designs', $this->Lang('perm_designs'));
$this->CreatePermission('Manage Stylesheets', $this->Lang('perm_styles'));
$this->CreatePermission('Modify Templates', $this->Lang('perm_modify'));
$this->CreatePermission('View Tag Help', $this->Lang('perm_viewhelp'));

$designer_group = new Group();
$designer_group->name = 'Designer';
$designer_group->description = $this->Lang('group_desc');
$designer_group->active = 1;
CMSMS\HookManager::do_hook('Core::AddGroupPre', ['group'=>&$designer_group]);
$designer_group->Save();
CMSMS\HookManager::do_hook('Core::AddGroupPost', ['group'=>&$designer_group]);

$designer_group->GrantPermission('Add Templates');
$designer_group->GrantPermission('Manage All Content'); //CHECKME ContentManager race when 1st installing?
$designer_group->GrantPermission('Manage Designs');
$designer_group->GrantPermission('Manage My Account');
$designer_group->GrantPermission('Manage My Bookmarks');
$designer_group->GrantPermission('Manage My Settings');
$designer_group->GrantPermission('Manage Stylesheets');
$designer_group->GrantPermission('Modify Files');
$designer_group->GrantPermission('Modify Templates');

