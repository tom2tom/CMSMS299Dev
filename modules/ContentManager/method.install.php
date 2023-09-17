<?php
/*
ContentManager module installation process
Copyright (C) 2013-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\Events;
use CMSMS\Group;
use CMSMS\Lone;

if (empty($this) || !($this instanceof ContentManager)) {
	exit;
}
//$installing = AppState::test(AppState::INSTALL);
//if (!($installing || $this->CheckPermission('Modify Modules'))) exit;

Lone::get('ContentTypeOperations')->RebuildStaticContentTypes();

$this->SetPreference('locktimeout', 60);
$this->SetPreference('lockrefresh', 120);
$this->SetPreference('list_visiblecolumns', 'hier,page,alias,template,type,active,default'); //user-adjustable column identifiers (some others are always shown)

$me = $this->GetName();

$obj = Lone::get('GroupOperations')->LoadGroupByName('Editor');
$flag = ($obj == null);
if ($flag) {
	// new site
	$this->CreatePermission('Add Pages', $this->Lang('perm_add'));
	$this->CreatePermission('Manage All Content', $this->Lang('perm_manage'));
	$this->CreatePermission('Modify Any Page', $this->Lang('perm_modify'));
	$this->CreatePermission('Remove Pages', $this->Lang('perm_remove'));
	$this->CreatePermission('Reorder Content', $this->Lang('perm_reorder'));

	$group = new Group();
	$group->name = 'Editor';
	$group->description = $this->Lang('group_desc');
	$group->active = 1;
	Events::SendEvent('Core', 'AddGroupPre', ['group' => &$group]);
	$group->Save();
	Events::SendEvent('Core', 'AddGroupPost', ['group' => &$group]);

	$group->GrantPermission('Manage All Content');
	$group->GrantPermission('Manage My Account');
	$group->GrantPermission('Manage My Bookmarks');
	$group->GrantPermission('Manage My Settings');
	$group->GrantPermission('View Tag Help');
} else {
	// existing site, replacement module
	$sql = 'UPDATE '.CMS_DB_PREFIX."permissions SET originator='$me',description=? WHERE `name`=?";
	$vals = [
	 'Add Pages', $this->Lang('perm_add'),
	 'Manage All Content', $this->Lang('perm_manage'),
	 'Modify Any Page', $this->Lang('perm_modify'),
	 'Remove Pages', $this->Lang('perm_remove'),
	 'Reorder Content', $this->Lang('perm_reorder'),
	];
	for ($i = 0; $i < 10; $i += 2) {
		$db->execute($sql, [$vals[$i + 1], $vals[$i]]);
	}
}

// register events for which other parts of the system may listen
foreach ([
 'AddPost',
 'AddPre',
 'DeletePost',
 'DeletePre',
 'EditPost',
 'EditPre',
 'OrderPost',
 'OrderPre',
] as $name) {
	Events::CreateEvent($me, $name); //since 2.0
	if ($flag) {
		Events::CreateEvent('Core', $name); //deprecated since 2.0, replicate ancient infrastructure
	}
}

// semi-permanent alias for back-compatibility
$ops = Lone::get('ModuleOperations');
$ops->set_module_classname('CMSContentManager', get_class($this));
