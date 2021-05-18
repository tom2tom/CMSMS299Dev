<?php

namespace AdminSearch;

use Exception;
use function execSpecialchars;
use function cms_to_bool;

abstract class Slave
{
  private $_params = [];

  public function set_text($text)
  {
    if( $text ) {
      $this->_params['search_text'] = ($text); // TODO best sanitizer(s) - post-input, pre-store, pre-display
      return;
    }
    throw new Exception('Invalid parameter search_text');
  }

  protected function get_text()
  {
    if( isset($this->_params['search_text']) ) {
      return $this->_params['search_text'];
    }
  }

  public function set_params($params)
  {
    foreach( $params as $key => &$value ) {
      switch( $key ) {
        // valid keys
        case 'search_descriptions':
        case 'search_casesensitive':
          $value = cms_to_bool($value);
        case 'search_text':
          $value = execSpecialchars($value); // TODO best sanitizer(s)
          break;

      default:
        throw new Exception('Invalid parameter '.$key.' in search params');
      }
    }
    unset($value);

    $this->_params = $params;
  }

  /**
   * @since 1.2
   * @param mixed $params string | string(s)[]
   * @return mixed wanted param value (string|bool) | array of them | null if not found
   */
  protected function get_params($params)
  {
    if( is_array($params) ) {
      $ret = [];
      foreach( $params as $key ) {
        if( isset($this->_params[$key]) ) {
          $ret[$key] = $this->_params[$key];
        }
      }
      if( $ret ) {
        return $ret;
      }
    }
    elseif( isset($this->_params[$params]) ) {
      return $this->_params[$params];
    }
  }

  protected function search_descriptions()
  {
    if( isset($this->_params['search_descriptions']) ) {
      return cms_to_bool($this->_params['search_descriptions']);
    }
    return false;
  }

  protected function search_casesensitive()  {
    if( isset($this->_params['search_casesensitive']) ) {
      return cms_to_bool($this->_params['search_casesensitive']);
    }
    return false;
  }

  abstract public function check_permission();
  abstract public function get_name();
  abstract public function get_description();
  abstract public function get_matches();

  public function get_section_description() {}
}
