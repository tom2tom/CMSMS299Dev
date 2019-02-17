<?php

namespace cms_installer\tests;

use cms_installer\CMSMS\http_request;
use cms_installer\utils;
use Exception;
use function cms_installer\CMSMS\lang;

/*
This method is useless, extension_loaded() is caseless
*/
function test_extension_loaded(string $name) : bool
{
  $a = extension_loaded($name);
//  if( !$a ) $a = extension_loaded(strtoupper($name));
  return $a;
}


function test_apache_module($name) : bool
{
  if( !$name ) return FALSE;
  if( !function_exists('apache_get_modules') ) return FALSE;
  $modules = apache_get_modules();
  return in_array($name,$modules);
}


function test_is_false($val) : bool
{
  return (utils::to_bool($val) == FALSE);
}


function test_is_true($val) : bool
{
  return (utils::to_bool($val) == TRUE);
}


function test_remote_file(string $url, int $timeout = 3,string $searchString = '') : bool
{
  $timeout = max(1,min(360,$timeout));
  $req = new http_request();
  $req->setTarget($url);
  $req->setTimeout($timeout);
  $req->execute();
  if( $req->getStatus() != 200 ) return FALSE;
  if( $searchString && strpos($req->getResult(),$searchString) === FALSE ) return FALSE;
  return TRUE;
}

abstract class test_base
{
  const TEST_UNTESTED = 'test_untested';
  const TEST_PASS = 'test_pass';
  const TEST_FAIL = 'test_fail';
  const TEST_WARN = 'test_warn';
  const KEYS = [
    'fail_key',
    'fail_msg',
    'maximum',
    'minimum',
    'msg',
    'msg_key',
    'name',
    'name_key',
    'pass_key',
    'pass_msg',
    'recommended',
    'required',
    'status',
    'value',
    'warn_key',
    'warn_msg',
  ];
  private $_data = [];

  public function __construct(string $name,$value,string $key = '')
  {
    if( !$name ) throw new Exception(lang('error_test_name'));
    $this->name = $name;
    $this->name_key = $key; //possibly empty
    $this->value = $value;
    $this->status = self::TEST_UNTESTED;
    $this->required = 0;
  }

  public function __get($key)
  {
    if( !in_array($key,self::KEYS) ) throw new Exception(lang('error_invalidkey',$key,__CLASS__));
    if( isset($this->_data[$key]) ) return $this->_data[$key];
  }

  public function __isset($key)
  {
    if( !in_array($key,self::KEYS) ) throw new Exception(lang('error_invalidkey',$key,__CLASS__));
    return isset($this->_data[$key]);
  }

  public function __set($key,$value)
  {
    if( !in_array($key,self::KEYS) ) throw new Exception(lang('error_invalidkey',$key,__CLASS__));
    if( is_null($value) || $value === '' ) {
      unset($this->_data[$key]);
      return;
    }

    $this->_data[$key] = $value;
  }

  public function __unset($key)
  {
    if( !in_array($key,self::KEYS) ) throw new Exception(lang('error_invalidkey',$key,__CLASS__));
    unset($this->_data[$key]);
  }

  abstract public function execute();

  public function run()
  {
    $res = $this->execute();
    switch( $res ) {
    case self::TEST_PASS:
    case self::TEST_FAIL:
    case self::TEST_WARN:
      $this->status = $res;
      break;

    case self::TEST_UNTESTED:
    default:
      throw new Exception(lang('error_test_invalidresult').' '.$res);
    }

    return $this->status;
  }

  public function msg()
  {
    if( $this->msg ) return $this->msg;
    if( $this->msg_key ) return $this->msg_key;

    switch( $this->status ) {
    case self::TEST_PASS:
      if( $this->pass_msg ) return $this->pass_msg;
      if( $this->pass_key ) return lang($this->pass_key);
      break;

    case self::TEST_FAIL:
      if( $this->fail_msg ) return $this->fail_msg;
      if( $this->fail_key ) return lang($this->fail_key);
      break;

    case self::TEST_WARN:
      if( $this->warn_msg ) return $this->warn_msg;
      if( $this->warn_key ) return lang($this->warn_key);
      break;

    default:
      throw new Exception(lang('error_test_invalidstatus'));
    }
  }

  protected function returnBytes($val)
  {
      if(is_string($val) && $val != '') {
          $val = trim($val);
          $last = strtolower(substr($val,-1));
          $val = (float) substr($val,0,-1);
          switch($last) {
          case 'g':
              $val *= 1024000000.0;
          case 'm':
              $val *= 1024000.0;
          case 'k':
              $val *= 1024.0;
          }
      }

      return $val;
  }
} // class
