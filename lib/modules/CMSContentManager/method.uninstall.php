<?php
# CMSContentManager module uninstallation process
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

use CMSMS\Events;
use CMSMS\Group;

if (!isset($gCms)) {
    exit;
}

$group = new Group();
$group->name = 'Editor';
try {
    Events::SendEvent('Core', 'DeleteGroupPre', ['group' => &$group]);
    if ($group->Delete()) {
        Events::SendEvent('Core', 'DeleteGroupPost', ['group' => &$group]);
    }
} catch (Throwable $t) {
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
