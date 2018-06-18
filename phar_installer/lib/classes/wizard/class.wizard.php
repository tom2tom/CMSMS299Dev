<?php

namespace __installer\wizard;

use __installer\request;
use __installer\session;
use DirectoryIterator;
use Exception;
use RegexIterator;

class wizard
{
  private static $_instance = null;
  private $_name = null;
  private $_stepvar = 's';
  private $_steps;
  private $_stepobj;
  private $_classdir;
  private $_namespace;
  private $_initialized;

  const STATUS_OK    = 'OK';
  const STATUS_ERROR = 'ERROR';
  const STATUS_BACK  = 'BACK';
  const STATUS_NEXT  = 'NEXT';

  /**
   * @access private
   * @param string $classdir Optional file-path of folder containing step-classes. Default current
   * @param string $namespace Optional namespace of step-classes. Default current
   * @throws Exception
   */
  private function __construct($classdir = '', $namespace = '')
  {
    if( !$classdir ) $classdir = __DIR__;
    elseif( !is_dir($classdir) ) throw new Exception('Could not find wizard steps in '.$classdir);
    $this->_classdir = $classdir;
    $this->_name = basename($classdir);

    if( !$namespace ) $namespace = __NAMESPACE__;
    $this->_namespace = $namespace;
  }

  /**
   * Get the wizard object, after creation if necessary
   * @param string $classdir Optional file-path of folder containing wizard-step classes
   * @param string $namespace Optional namespace of wizard-step classes
   * @return singleton object
   */
  final public static function &get_instance($classdir = '', $namespace = '')
  {
    if( !self::$_instance ) {
      self::$_instance = new self($classdir,$namespace);
	}
    return self::$_instance;
  }

  /**
   * One-time setup
   * @throws Exception
   */
  private function _init()
  {
      if( $this->_initialized ) return;
      $this->_initialized = true;

      // find all of the classes in the wizard directory.
      $di = new DirectoryIterator($this->_classdir);
      $ri = new RegexIterator($di,'/^class\.wizard.*\.php$/');
    $me = basename(__FILE__,'.php');
    $me2 = $me.'_step.php';
    $me .='.php';

    $files = [];
      foreach( $ri as $one ) {
      $name = $one->getFilename();
      if( !($name == $me || $name == $me2) ) $files[] = $name;
      }
      if( !count($files) ) throw new Exception('Could not find wizard steps in '.$classdir);
      sort($files);

    $_data = [];
    for( $i = 0, $n = count($files);  $i < $n; $i++ ) {
          $idx = $i+1;
          $filename = $files[$i];
          $classname = substr($filename,6,strlen($filename)-10);
      $rec = ['fn'=>$filename,'class'=>'','classname'=>$classname,'description'=>'','active'=>''];
      $rec['class'] = ( $this->_namespace ) ? $this->_namespace.'\\'.$classname : $classname;
          $rec['active'] = ($idx == $this->cur_step())?1:0;
          $_data[$idx] = $rec;
      }
      $this->_steps = $_data;
  }

  final public function get_nav()
  {
    $this->_init();
    return $this->_steps;
  }

  final public function get_step_var()
  {
    return $this->_stepvar;
  }

  final public function set_step_var($str)
  {
    if( $str ) $this->_stepvar = $str;
  }

  final public function cur_step() : int
  {
    $val = 1;
    if( $this->_stepvar && isset($_GET[$this->_stepvar]) ) $val = (int)$_GET[$this->_stepvar];
    return $val;
  }

  final public function finished() : bool
  {
    $this->_init();
    return $this->cur_step() > $this->num_steps();
  }

  final public function num_steps() : int
  {
    $this->_init();
    return count($this->_steps);
  }

  final public function get_step()
  {
    $this->_init();
    if( is_object($this->_stepobj) ) return $this->_stepobj;

    $rec = $this->_steps[$this->cur_step()];
    if( isset($rec['class']) && class_exists($rec['class']) ) {
      $obj = new $rec['class']();
      if( is_object($obj) ) {
        $this->_stepobj = $obj;
        return $obj;
      }
    }
  }

  public function get_data($key,$dflt = null)
  {
      $sess = session::get();
      if( !isset($sess[$key]) ) return $dflt;
      return $sess[$key];
  }

  public function set_data($key,$value)
  {
      $sess = session::get();
      $sess[$key] = $value;
  }

  public function clear_data($key)
  {
      $sess = session::get();
      if( isset($sess[$key]) ) unset($sess[$key]);
  }

  /**
   * @return mixed output from the current step
   */
  public function process()
  {
      $this->_init();
      $obj = $this->get_step();
      $res = ($obj) ? $obj->run() : null;
      return $res;
  }

  /**
   * @param mixed $idx numeric 1 .. no. of steps
   * @return string
   */
  final public function step_url($idx) : string
  {
      $this->_init();

      // get the url to the specified step index
      $idx = (int)$idx;
      if( $idx < 1 || $idx > $this->num_steps() ) return '';

      $request = request::get();
      $url = $request->raw_server('REQUEST_URI');
      $urlmain = explode('?',$url);

      $parts = parse_str($url);
      $parts[$this->_stepvar] = $idx;

      $tmp = array();
      foreach( $parts as $k => $v ) {
          $tmp[] = $k.'='.$v;
      }
      $url = $urlmain[0].'?'.implode('&',$tmp);
      return $url;
  }

  /**
   * @return string
   */
  final public function next_url() : string
  {
      $this->_init();
      $request = request::get();
      $url = $request->raw_server('REQUEST_URI');
      $urlmain = explode('?',$url);

      $parts = parse_str($url);
      $parts[$this->_stepvar] = $this->cur_step() + 1;
      if( $parts[$this->_stepvar] > $this->num_steps() ) return '';

      $tmp = array();
      foreach( $parts as $k => $v ) {
          $tmp[] = $k.'='.$v;
      }
      $url = $urlmain[0].'?'.implode('&',$tmp);
      return $url;
  }

  /**
   * @return string
   */
  final public function prev_url() : string
  {
      $this->_init();
      $request = request::get();
      $url = $request->raw_server('REQUEST_URI');
      $urlmain = explode('?',$url);

      $parts = parse_str($url);
      $parts[$this->_stepvar] = $this->cur_step() - 1;
      if( $parts[$this->_stepvar] <= 0 ) return '';

      $tmp = array();
      if( count($parts) ) {
          foreach( $parts as $k => $v ) {
              $tmp[] = $k.'='.$v;
          }
      }
      $url = $urlmain[0].'?'.implode('&',$tmp);
      return $url;
  }
} // class

