<?php
/*
Search module defaultadmin action tab populator
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

$words = [];
$query = 'SELECT word,count FROM '.CMS_DB_PREFIX.'module_search_words ORDER BY count DESC';
$dbr = $db->SelectLimit($query,50,0);
while ($dbr && $row = $dbr->FetchRow()) {
    $words[] = $row;
}
if ($words) {
    $tpl->assign('formstart1',$this->CreateFormStart($id,'defaultadmin'));
    //js onclick handler processed upstream
} else {
    $words = null;
}
$tpl->assign('topwords',$words);
