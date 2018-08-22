<?php
#News module action: delete a field definition
#Copyright (C) 2004-2018 Ted Kulp <ted@cmsmadesimple.org>
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

if (!isset($gCms)) exit;
if (!$this->CheckPermission('Modify Site Preferences')) return;

$fdid = '';
if (isset($params['fdid']))	$fdid = $params['fdid'];

// Get the category details
$query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news_fielddefs WHERE id = ?';
$row = $db->GetRow($query, [$fdid]);

//Now remove the category
$query = 'DELETE FROM '.CMS_DB_PREFIX.'module_news_fielddefs WHERE id = ?';
$db->Execute($query, [$fdid]);

//And remove it from any entries
$query = 'DELETE FROM '.CMS_DB_PREFIX.'module_news_fieldvals WHERE fielddef_id = ?';
$db->Execute($query, [$fdid]);

$db->Execute('UPDATE '.CMS_DB_PREFIX.'module_news_fielddefs SET item_order = (item_order - 1) WHERE item_order > ?', [$row['item_order']]);

// put mention into the admin log
audit('','News custom: '.$name, 'Field definition deleted');
$this->Setmessage($this->Lang('fielddefdeleted'));
$this->RedirectToAdminTab('customfields','','admin_settings');
