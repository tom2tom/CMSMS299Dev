<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Module: Content (c) 2013 by Robert Campbell 
#         (calguy1000@cmsmadesimple.org)
#  A module for managing content in CMSMS.
# 
#-------------------------------------------------------------------------
# CMS - CMS Made Simple is (c) 2004 by Ted Kulp (wishy@cmsmadesimple.org)
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#-------------------------------------------------------------------------
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
#
#-------------------------------------------------------------------------
#END_LICENSE
if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Site Preferences') ) return;

echo $this->StartTabHeaders();
echo $this->SetTabHeader('general',$this->Lang('prompt_general'));
echo $this->SetTabHeader('listsettings',$this->Lang('prompt_listsettings'));
echo $this->SetTabHeader('pagedefaults',$this->Lang('prompt_pagedefaults'));
echo $this->EndTabHeaders();

echo $this->StartTabContent();

echo $this->StartTab('general',$params);
include(__DIR__.'/function.admin_general_tab.php');
echo $this->EndTab();

echo $this->StartTab('listsettings',$params);
include(__DIR__.'/function.admin_listsettings_tab.php');
echo $this->EndTab();

echo $this->StartTab('pagedefaults',$params);
include(__DIR__.'/function.admin_pagedefaults_tab.php');
echo $this->EndTab();

echo $this->EndTabHeaders();

#
# EOF
#
?>