<?php
# Abstract class providing a framework for additonal content types
# Copyright (C) 2004-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# BUT withOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

/**
 * Class that module defined content types must extend.
 *
 * @since		0.9
 * @package		CMS
 */
abstract class CMSModuleContentType extends ContentBase
{
  /**
   * A method for returning the module that the content type belongs to.
   *
   * @abstract
   * @return string
   */
  abstract public function ModuleName();

  /**
   * Retrieve a language string from the module.
   *
   * @param string $name The key for the language string
   * @param array  $params Optional parameters for use in vsprintf
   */
  public function Lang($name, $params=[])
  {
    $obj = cms_utils::get_module($this->ModuleName());
    if( $obj ) {
      return $obj->Lang($name, $params);
    }
    else {
      return 'ModuleName() not defined properly';
    }
  }

  /**
   * Returns the instance of the module this content type belongs to
   */
  final public function GetModuleInstance()
  {
    $mod = cms_utils::get_module($this->ModuleName());
    if( $mod ) return $mod;
    return 'ModuleName() not defined properly';
  }
} // class

