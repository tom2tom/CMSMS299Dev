<?php

namespace cms_installer\wizard;

use cms_installer\request;
use cms_installer\session;
use Exception;

final class wizard
{
    const STATUS_OK    = 'OK';
    const STATUS_ERROR = 'ERROR';
    const STATUS_BACK  = 'BACK';
    const STATUS_NEXT  = 'NEXT';

    private static $_instance = null;
//    private $_name = null;
    private $_stepvar = 's';
    private $_steps;
    private $_stepobj;
    private $_classdir;
    private $_namespace;
    private $_initialized;

    /**
     * @access private
     * @param string $classdir Optional file-path of folder containing step-classes. Default current
     * @param string $namespace Optional namespace of step-classes. Default current
     * @throws Exception if supplied $classdir is invalid
     */
    private function __construct($classdir = '', $namespace = '')
    {
        if( !$classdir ) {
            $classdir = __DIR__;
        }
        else {
            $classdir = rtrim($classdir,' \\/');
            if( !is_dir($classdir) ) throw new Exception('Invalid wizard directory '.$classdir);
        }
        $this->_classdir = $classdir;
//        $this->_name = basename($classdir);

        if( !$namespace ) $namespace = __NAMESPACE__;
        $this->_namespace = $namespace;
    }

    /**
     * Get the wizard object, after creation if necessary
     * @param string $classdir Optional file-path of folder containing wizard-step classes
     * @param string $namespace Optional namespace of wizard-step classes
     * @return singleton object
     */
    public static function get_instance($classdir = '', $namespace = '')
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

        // find all step-classes in the wizard directory (not recursive)
        $files = glob($this->_classdir.DIRECTORY_SEPARATOR.'class.wizard_step*.php',GLOB_NOSORT);
        if( !$files ) throw new Exception('Could not find wizard steps in '.$this->_classdir);

        $_data = [];
        $s = self::cur_step();
        for( $i = 0, $n = count($files); $i < $n; $i++ ) {
            $filename = basename($files[$i],'.php');
            if( $filename != 'class.wizard_step' ) {
                $classname = substr($filename,6);
                $idx = (int)substr($classname,11);
                $rec = ['fn'=>$filename.'.php','class'=>'','classname'=>$classname,'description'=>'','active'=>0];
                $rec['class'] = ( $this->_namespace ) ? $this->_namespace.'\\'.$classname : $classname;
                if( $idx == $s ) $rec['active'] = 1;
                $_data[$idx] = $rec;
            }
        }
        ksort($_data,SORT_NUMERIC);

        $this->_steps = $_data;
        $this->_initialized = true;
    }

    public function get_nav()
    {
        $this->_init();
        return $this->_steps;
    }

    public function get_step_var()
    {
        return $this->_stepvar;
    }

    public function set_step_var($str)
    {
        if( $str ) $this->_stepvar = $str;
    }

    public function cur_step() : int
    {
        if( $this->_stepvar && isset($_GET[$this->_stepvar]) ) $val = (int)$_GET[$this->_stepvar];
        else $val = 1;
        return $val;
    }

    public function finished() : bool
    {
        $this->_init();
        return $this->cur_step() > $this->num_steps();
    }

    public function num_steps() : int
    {
        $this->_init();
        return count($this->_steps);
    }

    public function get_step()
    {
        if( is_object($this->_stepobj) ) return $this->_stepobj;

        $this->_init();
        $rec = $this->_steps[$this->cur_step()];
        if( !class_exists($rec['class']) ) {
            require_once $this->_classdir.DIRECTORY_SEPARATOR.$rec['fn'];
        }
        $obj = new $rec['class']();
        if( is_object($obj) ) {
            $this->_stepobj = $obj;
            return $obj;
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
    public function step_url($idx) : string
    {
        // get the url to the specified step index
        $idx = (int)$idx;
        if( $idx < 1 || $idx > $this->num_steps() ) return '';

        $request = request::get();
        $url = $request->raw_server('REQUEST_URI');
        $urlmain = explode('?',$url);

        $parts = [];
        parse_str($url,$parts);
        $parts[$this->_stepvar] = $idx;

        $tmp = [];
        foreach( $parts as $k => $v ) {
            $tmp[] = $k.'='.$v;
        }
        $url = $urlmain[0].'?'.implode('&',$tmp);
        return $url;
    }

    /**
     * @return string
     */
    public function next_url() : string
    {
        $this->_init();
        $request = request::get();
        $url = $request->raw_server('REQUEST_URI');
        $urlmain = explode('?',$url);

        $parts = [];
        parse_str($url,$parts);
        $parts[$this->_stepvar] = $this->cur_step() + 1;
        if( $parts[$this->_stepvar] > $this->num_steps() ) return '';

        $tmp = [];
        foreach( $parts as $k => $v ) {
            $tmp[] = $k.'='.$v;
        }
        $url = $urlmain[0].'?'.implode('&',$tmp);
        return $url;
    }

    /**
     * @return string
     */
    public function prev_url() : string
    {
        $this->_init();
        $request = request::get();
        $url = $request->raw_server('REQUEST_URI');
        $urlmain = explode('?',$url);

        $parts = [];
        parse_str($url,$parts);
        $parts[$this->_stepvar] = $this->cur_step() - 1;
        if( $parts[$this->_stepvar] <= 0 ) return '';

        $tmp = [];
        if( $parts ) {
            foreach( $parts as $k => $v ) {
                $tmp[] = $k.'='.$v;
            }
        }
        $url = $urlmain[0].'?'.implode('&',$tmp);
        return $url;
    }
} // class

