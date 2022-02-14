<?php
/*
Ajax processor to refresh template locks
Copyright (C) 2019-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use CMSMS\AppParams;
use CMSMS\LockOperations;

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

$handlers = ob_list_handlers();
for( $cnt = 0, $n = count($handlers); $cnt < $n; ++$cnt ) { ob_end_clean(); }

$userid = get_userid();
$lock_timeout = AppParams::get('lock_timeout', 60);
$now = time();

$list = LockOperations::get_locks('template');
$locks1 = [];
foreach( $list as $lock ) {
    if( $lock['uid'] != $userid) {
        $id = $lock['oid'];
        if( $lock_timeout && $lock['expires'] < $now ) {
            $locks1[$id] = 1; // stealable
        }
        else {
            $locks1[$id] = -1; // blocked
        }
    }
}

$list = LockOperations::get_locks('templatetype');
$locks2 = [];
foreach( $list as $lock ) {
    if( $lock['uid'] != $userid) {
        $id = $lock['oid'];
        if( $lock_timeout && $lock['expires'] < $now ) {
            $locks2[$id] = 1;
        }
        else {
            $locks2[$id] = -1;
        }
    }
}

$list = LockOperations::get_locks('templategroup');
$locks3 = [];
foreach( $list as $lock ) {
    if( $lock['uid'] != $userid) {
        $id = $lock['oid'];
        if( $lock_timeout && $lock['expires'] < $now ) {
            $locks3[$id] = 1;
        }
        else {
            $locks3[$id] = -1;
        }
    }
}

$out = json_encode([
    'templates' => $locks1,
    'types' => $locks2,
    'groups' => $locks3,
], JSON_NUMERIC_CHECK+JSON_FORCE_OBJECT);
echo $out;
exit;
