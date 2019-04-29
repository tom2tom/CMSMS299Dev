<?php
#ajax lock
#Copyright (C) 2013-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
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

use CMSMS\CmsLockException;
use CMSMS\CmsLockOwnerException;
use CMSMS\CmsNoLockException;
use CMSMS\Lock;
use CMSMS\LockOperations;

$handlers = ob_list_handlers();
for ($cnt = 0, $n = count($handlers); $cnt < $n; $cnt++) { ob_end_clean(); }

$CMS_ADMIN_PAGE = 1;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

$ruid = get_userid(FALSE);
if( !$ruid ) {
	return;
}

$fh = fopen('php://input','r');
$txt = fread($fh,8192);
if( $txt ) {
    $data = json_decode($txt,TRUE);
} else {
    $data = null;
}
if( !is_array($data) ) {
    $data = $_REQUEST;
}
$opt = get_parameter_value($data,'opt','setup');
$type = get_parameter_value($data,'type');
$oid = get_parameter_value($data,'oid');
$uid = get_parameter_value($data,'uid');
$lock_id = get_parameter_value($data,'lock_id');
$lifetime = (int) get_parameter_value($data,'lifetime',0);
if( $lifetime == 0 ) {
    $lifetime = cms_siteprefs::get('lock_timeout',60);
}
$out = [];
$out['status'] = 'success';

try {
  switch( $opt ) {
  case 'setup':
    $out['lang'] = [];
    $out['lang']['error_lock_useridmismatch'] = lang('CMSEX_L006');
    $out['lang']['error_lock_othererror'] = lang('CMSEX_L007');
    $out['lang']['error_lock_cmslockexception'] = lang('CMSEX_L008');
    $out['lang']['error_lock_cmslockownerexception'] = lang('CMSEX_L006');
    $out['lang']['error_lock_cmsunlockexception'] = lang('CMSEX_L009');
    $out['lang']['error_lock_cmsnolockexception'] = lang('CMSEX_L005');
    if( $uid != $ruid ) {
      $out['status'] = 'error';
      $out['error'] = ['type'=>'useridmismatch','msg'=>lang('CMSEX_L006')];
    }
    $out['uid'] = $ruid;
    break;

  case 'test':      // alias for check
  case 'is_locked': // alias for check
  case 'check':
      if( !$type ) throw new CmsInvalidDataException(lang('missingparams'));
	  //TODO handle case where lock held by supplied uid
      if( $oid ) {
          $out['lock_id'] = LockOperations::is_locked($type,$oid) ? 1 : 0;
      }
      else {
          $tmp = LockOperations::get_locks($type);
          if( $tmp ) $out['lock_id'] = -1;
      }
      break;

  case 'lock':
      if( $lifetime < 1 ) break; // do not lock, basically a noop
      if( !$type || !$oid || !$uid ) throw new CmsInvalidDataException(lang('missingparams'));
      if( $uid != $ruid ) throw new CmsLockOwnerException(lang('CMSEX_L006'));

      // see if we can get this lock... if we can, it's just a touch
      try {
          $lock = Lock::load($type,$oid,$uid);
      }
      catch( CmsNoLockException $e ) {
          // lock doesn't exist, gotta create one.
          $lock = new Lock($type,$oid,$lifetime);
      }
      $lock->save();
      $out['lock_id'] = $lock['id'];
      $out['lock_expires'] = $lock['expires'];
      // and we''re done.
      break;

  case 'touch':
      if( !$type || !$oid || !$uid || $lock_id < 1 ) throw new CmsInvalidDataException(lang('missingparams'));
      if( $uid != $ruid ) throw new CmsLockOwnerException(lang('CMSEX_L006'));
      $out['lock_expires'] = LockOperations::touch($lock_id,$type,$oid);
      break;

  case 'unlock':
      if( !$type || !$oid || !$uid || $lock_id < 1 ) throw new CmsInvalidDataException(lang('missingparams'));
      if( $uid != $ruid ) throw new CmsLockOwnerException(lang('CMSEX_L006'));
      LockOperations::delete($lock_id,$type,$oid);
      break;
  }
}
catch( CmsNoLockException $e ) {
  $out['status'] = 'error';
  $out['error'] = ['type'=>strtolower(get_class($e)),'msg'=>$e->GetMessage()];
}
catch( CmsLockException $e ) {
  $out['status'] = 'error';
  $out['error'] = ['type'=>strtolower(get_class($e)),'msg'=>$e->GetMessage()];
}
catch( Exception $e ) {
  $out['status'] = 'error';
  $out['error'] = ['type'=>'othererror','msg'=>$e->GetMessage()];
}

if( $out['status'] != 'error' && isset($out['lock_id']) && $out['lock_id'] != 0 ) $out['locked'] = 1;

header('Expires: Tue, 01 Jan 2000 00:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Content-Type: application/json');

echo json_encode($out);
exit;
