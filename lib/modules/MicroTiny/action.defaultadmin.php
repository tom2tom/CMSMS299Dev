<?php
#...
#Copyright (C) 2009-2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#This file is a component of the Microtiny module for CMS Made Simple
# <http://dev.cmsmadesimple.org/projects/microtiny>
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

if( !cmsms() ) exit;
if(!$this->VisibleToAdminUser() ) return;

echo $this->StartTabHeaders();
echo $this->SetTabHeader("example",$this->Lang("example"));
echo $this->SetTabHeader("settings",$this->Lang("settings"));
echo $this->EndTabHeaders();

echo $this->StartTabContent();

echo $this->StartTab("example");
include __DIR__.'/function.admin_example.php';
echo $this->EndTab();

echo $this->StartTab("settings");
include __DIR__.'/function.admin_settings.php';
echo $this->EndTab();

echo $this->EndTabContent();

#
# EOF
#
?>
