<?php
#...
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
#
#$Id$
if (!isset($gCms)) exit;
if (!$this->CheckPermission('Modify Site Preferences')) return;

$order = 1;
$fdid = $params['fdid'];

#Grab necessary info for fixing the item_order
$order = $db->GetOne("SELECT item_order FROM ".CMS_DB_PREFIX."module_news_fielddefs WHERE id = ?", array($fdid));
$time = $db->DbTimeStamp(time());

if ($params['dir'] == "down")
  {
    $query = 'UPDATE '.CMS_DB_PREFIX.'module_news_fielddefs SET item_order = (item_order - 1), modified_date = '.$time.' WHERE item_order = ?';
    $db->Execute($query, array($order + 1));

    $query = 'UPDATE '.CMS_DB_PREFIX.'module_news_fielddefs SET item_order = (item_order + 1), modified_date = '.$time.' WHERE id = ?';
    $db->Execute($query, array($fdid));

  }
else if ($params['dir'] == "up")
  {
    $query = 'UPDATE '.CMS_DB_PREFIX.'module_news_fielddefs SET item_order = (item_order + 1), modified_date = '.$time.' WHERE item_order = ?';
    $db->Execute($query, array($order - 1));
    $query = 'UPDATE '.CMS_DB_PREFIX.'module_news_fielddefs SET item_order = (item_order - 1), modified_date = '.$time.' WHERE id = ?';
    $db->Execute($query, array($fdid));
  }

$this->RedirectToAdminTab('customfields','','admin_settings');
?>
