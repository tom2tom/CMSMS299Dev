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

$group = new Group();
$group->name = 'Editor';
try {
    CMSMS\HookManager::do_hook('Core::DeleteGroupPre', ['group' => &$group]);
    if ($group->Delete()) {
        CMSMS\HookManager::do_hook('Core::DeleteGroupPost', ['group' => &$group]);
    }
} catch (Exception $e) {
}

$this->RemovePreference();

$this->RemovePermission('Add Pages');
$this->RemovePermission('Manage All Content');
$this->RemovePermission('Modify Any Page');
$this->RemovePermission('Remove Pages');
$this->RemovePermission('Reorder Content');

