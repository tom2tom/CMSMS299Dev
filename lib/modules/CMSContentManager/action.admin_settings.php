<?php
# CMSContentManager module action: settings
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

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Site Preferences') ) return;

$tpl = $smarty->createTemplate($this->GetTemplateResource('settings.tpl'),null,null,$smarty);

include(__DIR__.DIRECTORY_SEPARATOR.'function.admin_general_tab.php');
include(__DIR__.DIRECTORY_SEPARATOR.'function.admin_listsettings_tab.php');
include(__DIR__.DIRECTORY_SEPARATOR.'function.admin_pagedefaults_tab.php');

$tpl->display();
