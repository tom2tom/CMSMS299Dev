<?php
# Search module defaultadmin action tab populator
# Copyright (C) 2004-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

$words = [];
$query = 'SELECT word,count FROM '.CMS_DB_PREFIX.'module_search_words ORDER BY count DESC';
$dbr = $db->SelectLimit($query,50,0);
while ($dbr && $row = $dbr->FetchRow()) {
    $words[] = $row;
}
if ($words) {
	$tpl->assign('formstart1',$this->CreateFormStart($id,'defaultadmin'));
} else {
	$words = null;
}
$tpl->assign('topwords',$words);
