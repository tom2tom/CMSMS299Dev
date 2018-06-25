<?php
# DesignManager module installation process
# Copyright (C) 2012-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
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

use CMSMS\Events;
use CMSMS\Group;
use CMSMS\HookManager;

if (!isset($gCms)) {
    exit;
}

$this->SetPreference('lock_timeout', 60);
$this->SetPreference('lock_refresh', 120);

$this->CreatePermission('Add Templates', $this->Lang('perm_add'));
$this->CreatePermission('Manage Designs', $this->Lang('perm_designs'));
$this->CreatePermission('Manage Stylesheets', $this->Lang('perm_styles'));
$this->CreatePermission('Modify Templates', $this->Lang('perm_modify'));

$group = new Group();
$group->name = 'Designer';
$group->description = $this->Lang('group_desc');
$group->active = 1;
HookManager::do_hook('Core::AddGroupPre', ['group'=>&$group]);
$group->Save();
HookManager::do_hook('Core::AddGroupPost', ['group'=>&$group]);

$group->GrantPermission('Add Templates');
$group->GrantPermission('Manage All Content'); //CHECKME ContentManager race when 1st installing?
$group->GrantPermission('Manage Designs');
$group->GrantPermission('Manage My Account');
$group->GrantPermission('Manage My Bookmarks');
$group->GrantPermission('Manage My Settings');
$group->GrantPermission('Manage Stylesheets');
$group->GrantPermission('Modify Files');
$group->GrantPermission('Modify Templates');
$group->GrantPermission('View Tag Help');

// register events for which other parts of the system may listen
// these have been migrated from the main installer
foreach([
 'AddDesignPost',
 'AddDesignPre',

 'AddStylesheetPost',
 'AddStylesheetPre',
 'AddTemplatePost',
 'AddTemplatePre',
 'AddTemplateTypePost',
 'AddTemplateTypePre',

 'DeleteDesignPost',
 'DeleteDesignPre',

 'DeleteStylesheetPost',
 'DeleteStylesheetPre',
 'DeleteTemplatePost',
 'DeleteTemplatePre',
 'DeleteTemplateTypePost',
 'DeleteTemplateTypePre',

 'EditDesignPost',
 'EditDesignPre',

 'EditStylesheetPost',
 'EditStylesheetPre',
 'EditTemplatePost',
 'EditTemplatePre',
 'EditTemplateTypePost',
 'EditTemplateTypePre',

 'StylesheetPostCompile',
 'StylesheetPostRender',
 'StylesheetPreCompile',

 'TemplatePostCompile',
 'TemplatePreCompile',
 'TemplatePreFetch',
] as $name) {
    Events::CreateEvent('Core',$name);
}

