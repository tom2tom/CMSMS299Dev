<?php
/*
Procedure to display recorded events and their details
Copyright (C) 2004-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\Events;
use CMSMS\Lone;
use CMSMS\Utils;
use function CMSMS\sanitizeVal;

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

check_login();

$userid = get_userid();
$pmod = check_permission($userid, 'Modify Events');

$tmp = $_POST['senderfilter'] ?? '';
if (!$tmp || $tmp == _la('all')) {
    $senderfilter = '';
} else {
    $senderfilter = sanitizeVal($tmp, CMSSAN_FILE); // for event-originator 'Core' | modulename
}

$senders = [];
$events = Events::ListEvents();
if (is_array($events)) {
    foreach ($events as &$one) {
		if ($senderfilter && $senderfilter != $one['originator']) continue;
        if (!in_array($one['originator'], $senders)) {
            $senders[] = $one['originator'];
        }
        //TODO also need CMSMS\urlencode(one->originator, one->event_name)
        if ($one['originator'] == 'Core') {
            $one['description'] = Events::GetEventDescription($one['event_name']);
        } else {
            $modsend = Utils::get_module($one['originator']);
            $one['description'] = $modsend->GetEventDescription($one['event_name']);
        }
    }
    unset($one);
    sort($senders, SORT_NATURAL);
    $senders = [-1 => _la('all')] + $senders;
}

$themeObject = Lone::get('Theme');

if ($pmod) {
    $iconedit = $themeObject->DisplayImage('icons/system/edit.gif',_la('modifyeventhandlers'),'','','systemicon');
} else {
    $iconedit = '';
}
$iconinfo = $themeObject->DisplayImage('icons/system/info.png', _la('help'),'','','systemicon');

$selfurl = basename(__FILE__);
$extras = get_secure_param_array();
$urlext = get_secure_param();

$smarty = Lone::get('Smarty');
$smarty->assign([
    'access' => $pmod,
    'editurl' => 'editevent.php',
    'events' => $events,
    'helpurl' => 'eventhelp.php',
    'iconedit' => $iconedit,
    'iconinfo' => $iconinfo,
    'senders' => $senders,
    'senderfilter' => $senderfilter, // '' or one of $senders
    'selfurl' => $selfurl,
    'extraparms' => $extras,
    'urlext' => $urlext,
]);

$content = $smarty->fetch('listevents.tpl');
require ".{$dsep}header.php";
echo $content;
require ".{$dsep}footer.php";
