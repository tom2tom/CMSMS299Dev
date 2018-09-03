<?php

namespace __installer\CMSMS;

use __installer\installer_base;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class nlstools
{
  private static $_instance;
  private $_nls;

  protected function __construct() {}

  public static function &get_instance()
  {
    if( !self::$_instance ) self::$_instance = new self();
    return self::$_instance;
  }

  public static function set_nlshandler(nlstools &$obj)
  {
    self::$_instance = $obj;
  }

  protected function get_nls_dir()
  {
    return installer_base::get_rootdir().'/lib/CMSMS/classes/nls';
  }

  protected function load_nls()
  {
    if( is_array($this->_nls) ) return;

    $rdi = new RecursiveDirectoryIterator($this->get_nls_dir());
    $rii = new RecursiveIteratorIterator($rdi);
    $want = __NAMESPACE__.'\\nls';

    $this->_nls = [];
    foreach( $rii as $file => $info ) {
      if( !endswith($file,'.nls.php') ) continue;
      $name = basename($file);
      $name = trim(substr($name,6,strlen($name)-14)).'_nls';

      include $file;

      $tmp = $want.'\\'.$name;
      $obj = new $tmp;
      if( !($obj instanceof $want) ) {
          unset($obj);
          continue;
      }
      $this->_nls[$name] = $obj;
    }
  }

  public function get_list()
  {
    $this->load_nls();
    return array_keys($this->_nls);
  }

  public function &find($str)
  {
    $this->load_nls();
    foreach( $this->_nls as $name => &$nls ){
      if( $str == $name ) return $nls;
      if( $nls->matches($str) ) return $nls;
    }
    $obj = null;
    return $obj;
  }
} // class
