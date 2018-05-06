<?php
# CoreFileManager module method: install
# Copyright (C) 2018 The CMSMS Dev Team <coreteam@cmsmadesimple.org>
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

if (!function_exists('cmsms')) exit;

$this->SetPreference('editortheme', 'clouds')); //ACE editor
//$this->SetPreference('highlight', 1);
//$this->SetPreference('highlightstyle', 'default')); //hilight.js
$this->SetPreference('showhiddenfiles', 0);
$this->SetPreference('uploadable', '%image%,txt,text,pdf');

$this->CreateEvent('OnFileUploaded');
$this->CreateEvent('OnFileDeleted');
