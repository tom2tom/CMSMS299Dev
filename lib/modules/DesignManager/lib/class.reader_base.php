<?php
# Module: DesignManager - A CMSMS addon module to provide template management.
# Copyright (C) 2012-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\CmsException;

abstract class reader_base
{
  private $_suggested_name;
  private $_suggested_description;

  public function __construct($filename)
  {
    $this->_filename = $filename;
  }

  public function set_suggested_name($name)
  {
    if( $name ) $this->_suggested_name = $name;
  }

  public function set_suggested_description($description)
  {
    if( $description ) $this->_suggested_description = $description;
  }

  public function get_filename()
  {
    return $_filename;
  }

  public function get_suggested_name()
  {
    return $this->_suggested_name;
  }

  public function get_suggested_description()
  {
    return $this->_suggested_description;
  }

  abstract public function validate();

  /**
   * Retrieve information about the design
   *
   * @return a hash containing design name, description, generated, and cmsversion
   */
  abstract public function get_design_info();

  /**
   * Retrieve an array of hashes representing template information.
   * each hash will have a name,key,desc,data,type_originator,type_name fields.
   * all data should be base64 decoded.
   */
  abstract public function get_template_list();

  /**
   * Return information about stylesheets in the xml file
   * returns an array of hashes.  each hash should contain name, key, desc, data, mediatype,
   * and mediaquery values.  All data should be base64 decoded.
   */
  abstract public function get_stylesheet_list();

  /**
   * Actually do the importing..
   * can throw exceptions.
   */
  abstract public function import();

  /**
   * Get the destination directory for this designs files.
   *
   * Directory should be created, and checked for writable...
   * throw exception on failure.
   */
  abstract protected function get_destination_dir();

  /**
   * Get a finalized new name for this new design.
   *
   * Use the suggested name if possible, check for duplicate names
   * throw exception on failure.
   */
  public function get_new_name()
  {
    $name = $this->get_suggested_name();
    if( !$name ) {
      // no suggested name... get one from the design.
      $info = $this->get_design_info();
      $name = $info['name'];
    }
    if( !$name ) {
      // still no name... try to use the filename.
      $t = $this->get_filename();
      $x = strpos($t,'.');
      $name = substr($t,$x);
    }

    // now see if it's a duplicate name
    $list = Design::get_list();
    $orig_name = $name;
    if( $list ) {
      $name_list = array_values($list);
      $n = 1;
      while( $n < 100 ) {
        if( !in_array($name,$name_list) ) {
          break;
        }
        $n++;
        $name = "$orig_name $n";
      }
      if( $n >= 100 ) {
        throw new CmsException('Could not determine a new name for this design');
      }
    }

    return $name;
  }

} // class
