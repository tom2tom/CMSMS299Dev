<?php

namespace cms_installer\wizard;

use cms_installer\request;
use cms_installer\session;
use DirectoryIterator;
use Exception;
use RegexIterator;

final class wizard
{
    const STATUS_OK    = 'OK';
    const STATUS_ERROR = 'ERROR';
    const STATUS_BACK  = 'BACK';
    const STATUS_NEXT  = 'NEXT';

//    private static $_instance = null;

    //TODO namespaced global variables here
//    private static $_name = null;
    private static $_stepvar = 's';
    private static $_steps;
    private static $_stepobj;
    private static $_classdir;
    private static $_namespace;
    private static $_initialized;

    /**
     * @access private
     * @param string $classdir Optional file-path of folder containing step-classes. Default current
     * @param string $namespace Optional namespace of step-classes. Default current
     * @throws Exception if supplied $classdir is invalid
     */
    public function __construct($classdir = '', $namespace = '')
    {
        if( !$classdir ) {
            $classdir = __DIR__;
        }
        else {
            $classdir = rtrim($classdir,' \\/');
            if( !is_dir($classdir) ) throw new Exception('Invalid wizard directory '.$classdir);
        }
        self::$_classdir = $classdir;
//        $this->_name = basename($classdir);

        if( !$namespace ) $namespace = __NAMESPACE__;
        self::$_namespace = $namespace;
    }

    /**
     * Get a wizard object
     * @deprecated since 2.3 use new wizard() instead
     * @param string $classdir Optional file-path of folder containing wizard-step classes
     * @param string $namespace Optional namespace of wizard-step classes
     * @return object
     */
    public static function get_instance($classdir = '', $namespace = '')
    {
//        if( !self::$_instance ) { self::$_instance = new self($classdir,$namespace); } return self::$_instance;
        return new self($classdir, $namespace);
    }

    /**
     * One-time setup
     * @throws Exception
     */
    private function _init()
    {
        if( self::$_initialized ) return;

        // find all step-classes in the wizard directory (not recursive) (intra-phar globbing N/A)
        $iter = new RegexIterator(
            new DirectoryIterator(self::$_classdir),
            '/^class\.wizard_step\d+\.php$/'
        );

        $s = self::cur_step();
        $_data = [];
        foreach( $iter as $inf ) {
            $filename = $inf->getFilename();
            $tmp = substr($filename,0,strlen($filename) - 4);
            $classname = substr($tmp,6);
            $idx = (int)substr($classname,11);
            $rec = ['fn'=>$filename,'class'=>'','classname'=>$classname,'description'=>'','active'=>0];
            $rec['class'] = ( self::$_namespace ) ? self::$_namespace.'\\'.$classname : $classname;
            if( $idx == $s ) $rec['active'] = 1;
            $_data[$idx] = $rec;
        }
        if( !$_data ) throw new Exception('Could not find wizard steps in '.self::$_classdir);
        ksort($_data,SORT_NUMERIC);

        self::$_steps = $_data;
        self::$_initialized = true;
    }

    public function get_nav()
    {
        $this->_init();
        return self::$_steps;
    }

    public function get_step_var()
    {
        return self::$_stepvar;
    }

    public function set_step_var($str)
    {
        if( $str ) self::$_stepvar = $str;
    }

    public function cur_step() : int
    {
        if( self::$_stepvar && isset($_GET[self::$_stepvar]) ) $val = (int)$_GET[self::$_stepvar];
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
        return count(self::$_steps);
    }

    public function get_step()
    {
        if( is_object(self::$_stepobj) ) return self::$_stepobj;

        $this->_init();
        $rec = self::$_steps[$this->cur_step()];
        if( !class_exists($rec['class']) ) {
            require_once self::$_classdir.DIRECTORY_SEPARATOR.$rec['fn'];
        }
        $obj = new $rec['class']();
        if( is_object($obj) ) {
            self::$_stepobj = $obj;
            return $obj;
        }
    }

    public function get_data($key,$dflt = null)
    {
        $sess = session::get_instance();
        return $sess[$key] ?? $dflt;
    }

    public function set_data($key,$value)
    {
        $sess = session::get_instance();
        $sess[$key] = $value;
    }

    public function clear_data($key)
    {
        $sess = session::get_instance();
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

        $request = request::get_instance();
        $url = $request->raw_server('REQUEST_URI');
        $urlmain = explode('?',$url);

        $parts = [];
        parse_str($url,$parts);
        $parts[self::$_stepvar] = $idx;

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
        $request = request::get_instance();
        $url = $request->raw_server('REQUEST_URI');
        $urlmain = explode('?',$url);

        $parts = [];
        parse_str($url,$parts);
        $parts[self::$_stepvar] = $this->cur_step() + 1;
        if( $parts[self::$_stepvar] > $this->num_steps() ) return '';

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
        $request = request::get_instance();
        $url = $request->raw_server('REQUEST_URI');
        $urlmain = explode('?',$url);

        $parts = [];
        parse_str($url,$parts);
        $parts[self::$_stepvar] = $this->cur_step() - 1;
        if( $parts[self::$_stepvar] <= 0 ) return '';

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

