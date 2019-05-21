<?php
#Ajax processor to refresh template locks
#Copyright (C) 2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

use CMSMS\LockOperations;

$CMS_ADMIN_PAGE = 1;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

if (!isset($_REQUEST[CMS_SECURE_PARAM_NAME]) || !isset($_SESSION[CMS_USER_KEY]) || $_REQUEST[CMS_SECURE_PARAM_NAME] != $_SESSION[CMS_USER_KEY]) {
    exit;
}

$handlers = ob_list_handlers();
for( $cnt = 0, $n = count($handlers); $cnt < $n; ++$cnt ) { ob_end_clean(); }

$userid = get_userid();
$lock_timeout = cms_siteprefs::get('lock_timeout');
$now = time();

$list = LockOperations::get_locks('template');
$locks1 = [];
foreach( $list as $lock ) {
    if( $lock['uid'] != $userid) {
        $id = $lock['oid'];
        if( $lock_timeout && $lock['expires'] < $now ) {
            $locks1[$id] = 1; // stealable
        } else { 
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
        } else { 
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
        } else { 
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
