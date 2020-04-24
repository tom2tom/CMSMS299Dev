<?php
# Module: DesignManager - A CMSMS addon module to provide template management.
# Copyright (C) 2012-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

namespace DesignManager;

use cms_utils;
use CmsFileSystemException;
use CMSMS\CmsException;

final class reader_factory
{
  private function __construct() {}

  public static function get_reader($xmlfile)
  {
    $mod = cms_utils::get_module('DesignManager');
    if( !is_readable($xmlfile) ) throw new CmsFileSystemException($mod->Lang('error_filenotfound',$xmlfile));
    $fh = fopen($xmlfile,'r');
    if( !$fh ) throw new CmsException($this->Lang('error_fileopen',$xmlfile));
    $str = fread($fh,200);
    fclose($fh);
    if( strpos($str,'<!DOCTYPE') === FALSE ) throw new CmsException($mod->Lang('error_readxml'));

    // get the first element
    $x = '<!ELEMENT ';
    $p = strpos($str,$x);
    if( $p === FALSE ) throw new CmsException($this->Lang('error_readxml'));
    $str = substr($str,$p+strlen($x));
    $p = strpos($str,' ');
    if( $p === FALSE ) throw new CmsException($this->Lang('error_readxml'));  // highly unlikely.
    $word = substr($str,0,$p);

        $ob = null;
    switch( $word ) {
    case 'theme':
      $ob = new theme_reader($xmlfile);
      break;

    case 'design':
      $ob = new design_reader($xmlfile);
      break;
    }
        return $ob;
  }
} // class
