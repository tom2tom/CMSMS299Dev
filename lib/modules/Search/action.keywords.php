<?php
# Search module action: keywords
# Copyright (C) 2004-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

$pageid = $params['pageid'] ?? $returnid;

$query = 'SELECT idx.word
  FROM '.CMS_DB_PREFIX.'module_search_index idx INNER JOIN '.CMS_DB_PREFIX.'module_search_items i ON idx.item_id = i.id
  WHERE i.content_id = \''.$pageid.'\'
    AND i.module_name = \'Search\'
    AND i.extra_attr = \'content\'
  ORDER BY idx.count DESC';

$wordcount = $params['count'] ?? 500;
$dbr = $db->SelectLimit( $query, (int)$wordcount, 0 );

$wordlist = [];
while( $dbr && ($row = $dbr->FetchRow() ) ) {
    $wordlist[] = $row['word'];
}
echo implode(',',$wordlist);
return false;
