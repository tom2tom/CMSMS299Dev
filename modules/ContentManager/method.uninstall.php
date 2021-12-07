<?php
/*
ContentManager module uninstallation process
Copyright (C) 2013-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
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
use CMSMS\SingleItem;

if (empty($this) || !($this instanceof ContentManager)) exit;
//$installing = AppState::test(AppState::INSTALL);
//if (!($installing || $this->CheckPermission('Modify Modules'))) exit;

$group = new Group();
$group->name = 'Editor';
try {
    Events::SendEvent('Core', 'DeleteGroupPre', ['group' => &$group]);
    if ($group->Delete()) {
        Events::SendEvent('Core', 'DeleteGroupPost', ['group' => &$group]);
    }
} catch (Throwable $t) {
    // ignore this error
}

$this->RemovePreference();

$this->RemovePermission('Add Pages');
$this->RemovePermission('Manage All Content');
$this->RemovePermission('Modify Any Page');
$this->RemovePermission('Remove Pages');
$this->RemovePermission('Reorder Content');

$me = $this->GetName();
// unregister events
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
    Events::RemoveEvent($me,$name);
}

$ops = SingleItem::ModuleOperations();
$alias = $ops->get_module_classname('CMSContentManager');
$mine = get_class($this);
if ($alias && strpos($alias, $mine) !== false) {
    $ops->set_module_classname('CMSContentManager', null);
}
