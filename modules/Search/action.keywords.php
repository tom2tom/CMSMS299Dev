<?php
/*
Search module action: keywords
Copyright (C) 2004-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

//if (some worthy test fails) exit;

$pageid = $params['pageid'] ?? $returnid;

$query = 'SELECT IDX.word
FROM '.CMS_DB_PREFIX.'module_search_index IDX INNER JOIN '.CMS_DB_PREFIX.'module_search_items I ON IDX.item_id = I.id
WHERE I.content_id = \''.$pageid.'\' AND I.module_name = \'Search\' AND I.extra_attr = \'content\'
ORDER BY IDX.`count` DESC';

$wordcount = $params['count'] ?? 500;
$rst = $db->selectLimit($query, (int)$wordcount, 0);
if ($rst) {
    $wordlist = $rst->getCol();
    $rst->Close();
    echo implode(',', $wordlist);
    return;
}
echo '';
