<?php
/*
Procedure to display recorded events and their details
Copyright (C) 2018-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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
use CMSMS\SingleItem;
use CMSMS\Utils;
use function CMSMS\sanitizeVal;

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

check_login();

//$urlext = get_secure_param();
if (empty($_GET['originator'])) {
	return;
}
if (empty($_GET['event'])) {
	return;
}
// de_entitize() not relevant for parameters used here
$event = sanitizeVal($_GET['event'], CMSSAN_PURE); // letters only
$sender = sanitizeVal($_GET['originator'], CMSSAN_FILE); // 'Core' | modulename
if ($sender == 'Core') {
	$desctext = Events::GetEventDescription($event);
	$text = Events::GetEventHelp($event);
} else {
	$mod = Utils::get_module($sender);
	if (is_object($mod)) {
		$desctext = $mod->GetEventDescription($event);
		$text = $mod->GetEventHelp($event);
	} else {
		$desctext = ''; //TODO error message
		$text = '';
	}
}
$hlist = Events::ListEventHandlers($sender, $event);

$smarty = SingleItem::Smarty();
$smarty->assign([
	'desctext' => $desctext,
	'event' => $event,
	'hlist' => $hlist,
	'text' => $text,
]);

$content = $smarty->fetch('eventhelp.tpl');
require ".{$dsep}header.php";
echo $content;
require ".{$dsep}footer.php";
