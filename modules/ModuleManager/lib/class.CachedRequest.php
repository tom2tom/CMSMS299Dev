<?php
/*
ModuleManager class: engage with already-cached modules-data (if any, and
not too old) or else refresh the cache
Copyright (C) 2011-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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
namespace ModuleManager;

use CMSMS\Crypto;
use CMSMS\HttpRequest;
use CMSMS\Lone;
use CMSMS\Utils;

final class CachedRequest //was modmgr_cached_request
{
  private $_status;
  private $_result;
  private $_ttl = 5; // default cache lifetime (minutes)
  private $_timeout = 0; // maximum request duration (secs)

  /**
   * Retrieve request-result from cache or else from a new request
   * @param string $target optional URL of the target page
   * @param array $data optional POST-parameters array
   * @param mixed $age optional int or numeric string allowed cache-item age (minutes)
   */
  public function execute(string $target = '',array $data = [],$age = 0)
  {
    // build a cache key
    $signature = Crypto::hash_string(serialize([$target,$data]));

    $mod = Utils::get_module('ModuleManager');
    $force = $mod->GetPreference('disable_caching',0);
    if( !$force ) {
      $val = Lone::get('SystemCache')->get($signature.'_set',__CLASS__);
      if( $val ) {
        $limit = $age ? min(1,$age) : $this->_ttl;
        $force = $limit > 0 && (time() > $val + $limit * 60);
      }
    }
    if( !$force ) {
      $val = Lone::get('SystemCache')->get($signature,__CLASS__);
      if( $val ) {
        $data = unserialize($val,['allowed_classes'=>false]);
        $this->_status = $data[0];
        $this->_result = $data[1];
        return;
      }
    }
    // execute the request
    $req = new HttpRequest();
    if( $this->_timeout ) {
      $req->setTimeout($this->_timeout);
    }
    $this->_result = $req->execute($target,'','POST',$data);
    $this->_status = $req->getStatus();

    if( $this->_status == 200 ) {
      $val = serialize([$this->_status,$this->_result]);
      Lone::get('SystemCache')->set($signature,$val,__CLASS__);
      Lone::get('SystemCache')->set($signature.'_set',time(),__CLASS__);
    }
  }

  // @since 3.0 ?
  // @param int $val minutes 0 .. 1440
  public function setCacheLife($val)
  {
    $this->_timeout = max(0,min(1440,(int)$val)); // up to 24 hours
  }

  // @param int $val seconds 1 .. 1000
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
    Lone::get('SystemCache')->clear(__CLASS__);
  }
} // class
