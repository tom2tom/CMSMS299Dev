<?php
# CoreFileManager module action: admin_settings
# Copyright (C) 2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

if (!function_exists('cmsms')) {
    exit;
}
if (!$this->CheckPermission('Modify Site Preferences')) {
    //TODO relevant permission
    exit;
}

if (isset($params['submit'])) {
	//TODO record settings
}

//ACE theme selector https://ace.c9.io/build/kitchen-sink.html
//hilight.js theme demo  https://highlightjs.org/static/demo
//$edittheme = $this->GetPreference('acetheme', 'clouds');
//$hilite = $this->GetPreference('highlight', 1);
//$viewstyle = $this->GetPreference('highlightstyle', 'default'));

$showhiddenfiles = $this->GetPreference('showhiddenfiles', 0);
$uploadables = $this->GetPreference('uploadable', '%image%,txt,text,pdf');

$smarty->assign([
    'edittheme' => $edittheme,
//    'hilite' => $hilite,
//    'viewstyle' => $viewstyle,
    'showhidden' => $showhidden,
    'uploadables' => explode(',',$uploadables),
]);

echo $this->ProcessTemplate('settings.tpl');
