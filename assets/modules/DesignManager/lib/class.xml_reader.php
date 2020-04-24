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
use CmsXMLErrorException;
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
      cms_error('DesignManger\\xml_reader: '.$errstr);
      $mod = cms_utils::get_module('DesignManager');
      throw new CmsXMLErrorException($mod->Lang('error_xmlstructure').':<br />'.$errstr);
      return TRUE;
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
