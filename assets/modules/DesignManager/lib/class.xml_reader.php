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

use CMSMS\Utils;
use CMSMS\XMLException;
use XMLReader;
use function cms_error;

class xml_reader extends XMLReader
{
  private $_setup;
  private $_old_err_handler;
  private $_old_internal_errors;

  public static function __errhandler($errno,$errstr,$errfile,$errline)
  {
    if( strpos($errstr,'XMLReader') !== FALSE ) {
      cms_error('DesignManger\xml_reader: '.$errstr);
      $mod = Utils::get_module('DesignManager');
      throw new XMLException($mod->Lang('error_xmlstructure').':<br />'.$errstr);
    }
  }

  public function __setup()
  {
    if( !$this->_setup ) {
      $this->_old_internal_errors = libxml_use_internal_errors(FALSE);
      $this->_old_err_handler = set_error_handler([$this,'__errhandler']);
      $this->_setup = 1;
    }
  }

  public function __destruct()
  {
    if( $this->_old_err_handler )
      set_error_handler($this->_old_err_handler);
  }

  public function read()
  {
    $this->__setup();
    return parent::read();
  }
} // class
