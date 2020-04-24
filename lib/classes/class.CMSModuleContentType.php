<?php
# Abstract class providing a framework for additonal content types
# Copyright (C) 2004-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\contenttypes\ContentBase;
use CMSMS\LangOperations;

/**
 * Class providing a framework for displaying non-core page-content-types.
 * Module-defined content types may extend this class.
 *
 * @since		0.9
 * @deprecated since 2.3 This does not provide anything useful.
 * Instead, the content-type class (implementing ContentEditor), and if appropriate
 * a separate display-only variant, should just be registered permanently or during
 * each request via the CMSModule API.
 * @package		CMS
 */
abstract class CMSModuleContentType extends ContentBase
{
  /**
   * Return the name of the module that the content type belongs to.
   *
   * @abstract
   * @return string
   */
  abstract public function ModuleName();

  /**
   * Return the module-object the content type belongs to
   */
  final public function GetModuleInstance()
  {
    $mod = cms_utils::get_module($this->ModuleName());
    if( $mod ) return $mod;
    return 'ModuleName() not defined properly';
  }

  /**
   * Retrieve a translated string from the module.
   * This method accepts variable arguments. The first one (required) is the
   * translations-array key (a string). Any extra arguments are assumed to be
   * sprintf arguments to be applied to the key.
   * The original API, providing extra arguments in an array, may be used.
   */
  public function Lang($key, $params = [])
  {
    $realm = $this->ModuleName();
    $args = func_get_args();
    if( count($args) == 2 && $args[1] && is_array($args[1]) ) {
      return LangOperations::lang_from_realm($realm, $args[0], ...$args[1]);
    }
    return LangOperations::lang_from_realm($realm, ...$args);
  }
} // class
