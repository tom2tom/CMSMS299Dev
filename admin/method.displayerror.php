<?php
/*
Method: display an error page
Copyright (C) 2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

if (empty($smarty)) { 
	$smarty = AppSingle::Smarty();
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
$sep = DIRECTORY_SEPARATOR;
require ".{$sep}header.php";
echo $content;
require ".{$sep}footer.php";
exit;
