<?php
/*
Ajax lock
Copyright (C) 2013-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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

use CMSMS\AppParams;
use CMSMS\DataException;
use CMSMS\Error403Exception;
use CMSMS\Lock;
use CMSMS\LockException;
use CMSMS\LockOperations;
use CMSMS\LockOwnerException;
use CMSMS\NoLockException;

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

$userid = get_userid(false);
if (!$userid) {
    throw new Error403Exception(_la('permissiondenied'));
}

$handlers = ob_list_handlers();
for ($cnt = 0, $n = count($handlers); $cnt < $n; ++$cnt) {
    ob_end_clean();
}

$fh = fopen('php://input', 'r');
$txt = fread($fh, 8192);
if ($txt) {
    $data = json_decode($txt, true);
} else {
    $data = null;
}
if (!is_array($data)) {
    $data = $_REQUEST;
}
$op = $data['op'] ?? 'setup'; // no cleanup, only specific vals recognised
$type = $data['type'] ?? ''; // de_specialize? sanitize? only specific vals recognised
$oid = (int)($data['oid'] ?? 0);
$uid = (int)($data['uid'] ?? 0);
$lock_id = (int)($data['lock_id'] ?? 0);
$lifetime = (int)($data['lifetime'] ?? 0);
if ($lifetime == 0) {
    $lifetime = AppParams::get('lock_timeout', 60);
}
$out = ['status' => 'success'];

try {
    switch (trim($op)) {
  case 'setup':
    $out['lang'] = [
      'error_lock_useridmismatch' => _la('CMSEX_L006'),
      'error_lock_othererror' => _la('CMSEX_L007'),
      'error_lock_cmslockexception' => _la('CMSEX_L008'),
      'error_lock_cmslockownerexception' => _la('CMSEX_L006'),
      'error_lock_cmsunlockexception' => _la('CMSEX_L009'),
      'error_lock_cmsnolockexception' => _la('CMSEX_L005'),
    ];
    if ($uid != $userid) {
        $out['status'] = 'error';
        $out['error'] = ['type' => 'useridmismatch', 'msg' => _la('CMSEX_L006')];
    }
    $out['uid'] = $userid;
    break;

  case 'test':      // alias for check
  case 'is_locked': // alias for check
  case 'check':
      if (!$type) {
          throw new DataException(_la('missingparams'));
      }
      if ($oid) {
          $out['lock_id'] = LockOperations::is_locked($type, $oid);
          if ($out['lock_id'] > 0) {
              //it's locked by somebody. If it's self-owned or expired, it could be stollen
              $out['stealable'] = (LockOperations::is_stealable($type, $oid)) ? 1 : 0;
          } else {
              //no lock, no steal
              $out['stealable'] = 0;
          }
      } else {
          $tmp = LockOperations::get_locks($type);
          if ($tmp) {
              if (count($tmp) > 1) {
                  $out['lock_id'] = -1; //< 0 signals no-spacific-lock
                  $out['stealable'] = 0; //non-specific lock is not stealable
              } else {
                  $out['lock_id'] = $tmp[0]['id'];
                  $out['stealable'] = (LockOperations::is_stealable($tmp[0]['type'],$tmp[0]['oid'])) ? 1 : 0;
              }
          } else {
              //no lock a.k.a. not locked
              $out['lock_id'] = 0;
              $out['stealable'] = 0;
          }
      }
      break;

  case 'lock':
      if ($lifetime < 1) {
          break; // do not lock, basically a noop
      }
      if (!$type || !$oid || !$uid) {
          throw new DataException(_la('missingparams'));
      }
      if ($uid != $userid) {
          throw new LockOwnerException('CMSEX_L006');
      }
      // try to get this lock... if we can, it's just a touch
      try {
          $lock = LockOperations::load($type, $oid, $uid);
      } catch (NoLockException $e) {
          // lock doesn't exist, create one
          $lock = new Lock([
              'type' => $type,
              'oid' => $oid,
              'uid' => $uid,
              'lifetime' => $lifetime,
          ]);
      }
      $lock->save();
      $out['lock_id'] = $lock['id'];
      $out['lock_expires'] = $lock['expires'];
      break;

  case 'touch':
      if (!$type || !$oid || !$uid || $lock_id < 1) {
          throw new DataException(_la('missingparams'));
      }
      if ($uid != $userid) {
          throw new LockOwnerException('CMSEX_L006');
      }
      $out['lock_expires'] = LockOperations::touch($lock_id, $type, $oid);
      break;

  case 'unlock':
// TODO some authority-indicator
      if (!$type || !$oid ||/* !$uid ||*/ $lock_id < 1) {
          throw new DataException(_la('missingparams'));
      }
/* any authorised user may steal
      if ($uid != $userid) {
          throw new CMSMS\LockOwnerException('CMSEX_L006');
      }
*/
      LockOperations::delete($lock_id, $type, $oid);
      break;
  }
} catch (NoLockException $e) {
    $out['status'] = 'error';
    $out['error'] = ['type' => strtolower(get_class($e)), 'msg' => $e->GetMessage()];
} catch (LockException $e) {
    $out['status'] = 'error';
    $out['error'] = ['type' => strtolower(get_class($e)), 'msg' => $e->GetMessage()];
} catch (Throwable $e) {
    $out['status'] = 'error';
    $out['error'] = ['type' => 'othererror', 'msg' => $e->GetMessage()];
}

if ($out['status'] != 'error') {
    $out['locked'] = (isset($out['lock_id']) && $out['lock_id'] !== 0) ? 1 : 0;
}

header('Expires: Tue, 01 Jan 2000 00:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Content-Type: application/json');

echo json_encode($out);
exit;
