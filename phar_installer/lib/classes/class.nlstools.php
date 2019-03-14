<?php

namespace cms_installer;

use cms_installer\installer_base;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class nlstools
{
//  private static $_instance;
  //TODO namespaced global variables here
  private static $_nls;

//  private function __construct() {}

  /**
   * Get an instance of this class.
   * @deprecated since 2.3 use new nlstools()
   * @return self
   */
  public static function get_instance() : self
  {
//    if( !self::$_instance ) { self::$_instance = new self(); } return self::$_instance;
    return new self();
  }

  /**
   * @deprecated since 2.3 does nothing
   * @param \cms_installer\nlstools $obj
   */
  public static function set_nlshandler(nlstools &$obj)
  {
//    self::$_instance = $obj;
  }

  private function get_nls_dir() : string
  {
    return installer_base::get_rootdir().'/lib/CMSMS/classes/nls';
  }

  private function load_nls()
  {
    if( is_array(self::$_nls) ) return;

    $rdi = new RecursiveDirectoryIterator($this->get_nls_dir());
    $rii = new RecursiveIteratorIterator($rdi);
    $want = __NAMESPACE__.'\\nls';

    self::$_nls = [];
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
      self::$_nls[$name] = $obj;
    }
  }

  public function get_list() : array
  {
    $this->load_nls();
    return array_keys(self::$_nls);
  }

  public function &find(string $str)
  {
    $this->load_nls();
    foreach( self::$_nls as $name => &$nls ){
      if( $str == $name ) return $nls;
      if( $nls->matches($str) ) return $nls;
    }
    $obj = null;
    return $obj;
  }
} // class
