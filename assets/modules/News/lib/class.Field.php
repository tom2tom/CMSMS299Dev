<?php
# Class representing a field definition
# Copyright (C) 2016-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

namespace News;

use CMSMS\CmsException;
use Exception;
use News\Ops;
use const CMS_DB_PREFIX;
use function cmsms;
use function munge_string_to_url;

final class Field
{
  private $_data = [];
  private $_displayvalue;

  public function _get_data($key)
  {
    if( isset($this->_data[$key]) ) return $this->_data[$key];
  }

  public function __get($key)
  {
    $fielddefs = Ops::get_fielddefs(FALSE);

    switch( $key ) {
    case 'alias':
      $alias = munge_string_to_url($this->name);
      return $alias;

    case 'id':
    case 'name':
    case 'type':
    case 'max_length':
    case 'create_date':
    case 'modified_date':
    case 'item_order':
    case 'public':
    case 'value':
      if( isset($this->_data[$key]) ) return $this->_data[$key];
      break;

    case 'extra':
      if( isset($this->_data['extra']) ) {
    if( !is_array($this->_data['extra']) ) $this->_data['extra'] = unserialize($this->_data['extra']);
    return $this->_data['extra'];
      }
      break;

    case 'options':
      $extra = $this->extra;
      if( is_array($extra) && isset($extra['options']) ) return $extra['options'];
      break;

    case 'displayvalue':
      if( !$this->_displayvalue ) {
    if( isset($this->_data['value']) ) {
      $value = $this->_data['value'];
      $this->_displayvalue = $value;
      if( $this->type == 'dropdown' ) {
        // dropdowns may have a different displayvalue than actual value.
        if( is_array($this->options) && isset($this->options[$value]) ) $this->_displayvalue = $this->options[$value];
      }
    }
      }
      return $this->_displayvalue;
      break;

    case 'fielddef_id':
      return $this->_data['id'];
    }
  }

  public function __isset($key)
  {
    switch( $key ) {
    case 'alias':
    case 'id':
    case 'name':
    case 'type':
    case 'max_length':
    case 'create_date':
    case 'modified_date':
    case 'item_order':
    case 'public':
      return TRUE;

    case 'value':
    case 'extra':
      return isset($this->_data[$key]);

    default:
      return FALSE;
    }
  }

  public function __set($key,$value)
  {
    switch( $key ) {
    case 'id':
    case 'name':
    case 'type':
    case 'max_length':
    case 'item_order':
    case 'public':
    case 'value':
    case 'extra':
      $this->_data[$key] = $value;
      break;

    case 'alias':
      throw new Exception('Attempt to set invalid data into field object: '.$key);
      break;

    case 'create_date':
    case 'modified_date':
      break;

    default:
      throw new Exception('Attempt to set invalid data into field object: '.$key);
    }
  }

  private function _validate()
  {
    if( $this->name == '' ) throw new CmsException('Invalid field definition name');
    if( $this->type == 'dropdown' && count($this->options) == 0 ) throw new CmsException('No options for dropdown field');
    if( $this->id > 0 && $this->item_order < 1 ) throw new CmsException('Invalid item order');
  }

  private function _insert()
  {
    $db = cmsms()->GetDb();
    if( $this->item_order < 1 ) {
      $query = 'SELECT MAX(item_order) FROM '.CMS_DB_PREFIX.'module_news_fielddefs';
      $num = (int)$db->GetOne($query);
      $this->item_order = $num+1;
    }
    $query = 'INSERT INTO '.CMS_DB_PREFIX.'module_news_fielddefs
(name,type,max_length,create_date,item_order,public,extra)
VALUES (?,?,?,?,?,?,?)';
    $now = time();
    $dbr = $db->Execute($query,[$this->name,$this->type,$this->max_length,$now,$this->item_order,$this->public,
                     serialize($this->extra)]);
    $this->_data['id'] = $db->Insert_ID();
    $this->create_date = $this->modified_date = $now;
  }

  private function _update()
  {
    $db = cmsms()->GetDb();
    $query = 'UPDATE '.CMS_DB_PREFIX.'module_news_fielddefs SET name = ?, type = ?, max_length = ?, modified_date = ?,
item_orderr = ?, public = ?, extra = ? WHERE id = ?';
    $now = time();
    $dbr = $db->Execute($query,[$this->name,$this->type,$this->max_length, $now, $this->item_order,$this->public,
                     serialize($this->extra),$this->id]);
    $this->modified_date = $now;
  }

  public function save()
  {
    $this->_validate();
    if( $this->_data['id'] ) {
      $this->_insert();
    }
    else {
      $this->_update();
    }
  }

  public static function load_by_id($id)
  {
    $id = (int)$id;
    if( $id < 1 ) return;

    $db = cmsms()->GetDb();
    $query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news_fielddefs WHERE id = ?';
    $row = $db->GetRow($query,[$id]);
    if( $row['extra'] ) $row['extra'] = unserialize($row['extra']);
    $obj = new self;
    $obj->_data = $row;
    return $obj;
  }

  public static function load_by_name($name)
  {
    $name = trim($name);
    if( !$name ) return;

    $db = cmsms()->GetDb();
    $query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news_fielddefs WHERE name = ?';
    $row = $db->GetRow($query,[$name]);
    if( $row['extra'] ) $row['extra'] = unserialize($row['extra']);
    $obj = new self;
    $obj->_data = $row;
    return $obj;
  }
} // class
