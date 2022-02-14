<?php
/*
Method: display an error page
Copyright (C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\SingleItem;

if (empty($smarty)) {
	$smarty = SingleItem::Smarty();
}

$tplvars = [];
foreach ([
	'title',
	'titleclass',
	'message',
	'messageclass',
	'backlink',
] as $param) {
	$tplvars[$param] = (!empty($$param)) ? $$param : null;
}
$smarty->assign($tplvars);
$content = $smarty->fetch('error.tpl');
$pref = __DIR__.DIRECTORY_SEPARATOR;
require "{$pref}header.php";
echo $content;
require "{$pref}footer.php";
exit;
