<?php
# ModuleManager class: ..
# Copyright (C) 2011-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
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

namespace ModuleManager;

use cms_http_request;
use cms_siteprefs;
use cms_utils;
use const TMP_CACHE_LOCATION;
use function cmsms;

final class cached_request //was modmgr_cached_request
{
  private $_status;
  private $_result;
  private $_timeout;
  private $_signature;

  private function _getCacheFile()
  {
    if( $this->_signature ) {
      return TMP_CACHE_LOCATION.DIRECTORY_SEPARATOR.'modmgr_'.$this->_signature.'.dat';
    }
  }

  public function execute($target = '',$data = [], $age = '')
  {
    $mod = cms_utils::get_module('ModuleManager');
    $config = cmsms()->GetConfig();
    if( !$age ) $age = cms_siteprefs::get('browser_cache_expiry',60);
    if( $age ) $age = max(1,(int)$age);

    // build a signature
    $this->_signature = cms_utils::hash_string(serialize([$target,$data]));
    $fn = $this->_getCacheFile();
    if( !$fn ) return;

    // check for the cached file
    $atime = time() - ($age * 60);
    if( ($config['develop_mode'] && $mod->GetPreference('disable_caching',0)) ||
        !file_exists($fn) || filemtime($fn) <= $atime ) {
      // execute the request
      $req = new cms_http_request();
      if( $this->_timeout ) $req->setTimeout($this->_timeout);
      $req->execute($target,'','POST',$data);
      $this->_status = $req->getStatus();
      $this->_result = $req->getResult();

      @unlink($fn);
      if( $this->_status == 200 ) {
        // create a cache file
        $fh = fopen($fn,'w');
        fwrite($fh,serialize([$this->_status,$this->_result]));
        fclose($fh);
      }
    }
    else {
      // get data from the cache.
      $data = unserialize(file_get_contents($fn), ['allowed_classes'=>false]);
      $this->_status = $data[0];
      $this->_result = $data[1];
    }
  }

  public function setTimeout($val)
  {
    $this->_timeout = max(1,min(1000,(int)$val));
  }

  public function getStatus()
  {
    return $this->_status;
  }

  public function getResult()
  {
    return $this->_result;
  }

  public function clearCache()
  {
    $fn = $this->_getCacheFile();
    if( $fn ) @unlink($fn);
  }
} // class
