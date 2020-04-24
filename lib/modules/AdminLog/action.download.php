<?php
/*
AdminLog module action: download the log
Copyright (C) 2017-2020 CMS Made Simple Foundation <foundationcmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

use AdminLog\filter;
use AdminLog\resultset;

if( !isset($gCms) ) exit;
if( !$this->VisibleToAdminUser() ) exit;

$filter = new filter();
if( isset($_SESSION['adminlog_filter']) && $_SESSION['adminlog_filter'] instanceof filter ) {
    $filter = $_SESSION['adminlog_filter'];
}
// override the limit to 1000000 lines
$filter->limit = 1000000;
$result = new resultset( $db, $filter );

$dateformat = trim(cms_userprefs::get_for_user(get_userid(),'date_format_string','%x %X'));
if( empty($dateformat) ) $dateformat = '%x %X';
header('Content-type: text/plain');
header('Content-Disposition: attachment; filename="adminlog.txt"');
while( !$result->EOF() ) {
    $row = $result->GetObject();
    echo strftime($dateformat,$row['timestamp']).'|';
    echo $row['username'] . '|';
    echo (((int)$row['item_id']==-1)?'':$row['item_id']) . '|';
    echo $row['item_name'] . '|';
    echo $row['action'];
    echo "\n";
    $result->MoveNext();
}
exit;
