<?php
#...
#Copyright (C) 2009-2018 The CMSMS Dev Team <coreteam@cmsmadesimple.org>
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

if( version_compare($oldversion,'1.1') < 0 ) {
  $this->CreatePermission('MicroTiny View HTML Source','MicroTiny View HTML Source');
}

if( version_compare($oldversion,'2.0') < 0 ) {
  $this->RemovePreference();
  $this->DeleteTemplate();
  include_once(__DIR__.'/method.install.php');
}

#
# EOF
#
?>