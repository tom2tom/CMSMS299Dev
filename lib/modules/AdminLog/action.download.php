<?php
/*
AdminLog module action: download the log
Copyright (C) 2017-2021 CMS Made Simple Foundation <foundationcmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
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
$rst = new resultset($db, $filter);
if( $rst && !$rst->EOF() ) {
    $dateformat = trim(cms_userprefs::get_for_user(get_userid(),'date_format_string','%x %X'));
    if( empty($dateformat) ) $dateformat = '%x %X';
    header('Content-type: text/plain');
    header('Content-Disposition: attachment; filename="adminlog.txt"');
    do {
        $row = $rst->GetObject();
        echo strftime($dateformat,$row['timestamp']).'|';
        echo $row['username'] . '|';
        echo (((int)$row['item_id']==-1)?'':$row['item_id']) . '|';
        echo $row['item_name'] . '|';
        echo $row['action'];
        echo "\n";
        $rst->MoveNext();
    } while( !$rst->EOF() );
}
if( $rst ) {
    $rst->Close();
}
exit;
