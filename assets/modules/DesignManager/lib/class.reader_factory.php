<?php
/*
Module: DesignManager - A CMSMS addon module to provide template management.
Copyright (C) 2012-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

namespace DesignManager;

use cms_utils;
use CmsFileSystemException;
use DesignManager\design_reader;
use DesignManager\theme_reader;

final class reader_factory
{
  private function __construct() {}

  public static function get_reader($xmlfile)
  {
    $mod = cms_utils::get_module('DesignManager');
    if( !is_readable($xmlfile) ) throw new CmsFileSystemException($mod->Lang('error_filenotfound',$xmlfile));
    $fh = fopen($xmlfile,'r');
    if( !$fh ) throw new Exception($this->Lang('error_fileopen',$xmlfile));
    $str = fread($fh,200);
    fclose($fh);
    if( strpos($str,'<!DOCTYPE') === FALSE ) throw new Exception($mod->Lang('error_readxml'));

    // get the first element
    $x = '<!ELEMENT ';
    $p = strpos($str,$x);
    if( $p === FALSE ) throw new Exception($this->Lang('error_readxml'));
    $str = substr($str,$p+strlen($x));
    $p = strpos($str,' ');
    if( $p === FALSE ) throw new Exception($this->Lang('error_readxml'));  // highly unlikely.
    $word = substr($str,0,$p);

    switch( $word ) {
    case 'theme':
      return new theme_reader($xmlfile);
    case 'design':
      return new design_reader($xmlfile);
    default:
      return null;
    }
  }
} // class
