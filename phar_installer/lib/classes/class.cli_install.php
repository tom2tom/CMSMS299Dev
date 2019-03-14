<?php

namespace cms_installer;

use Exception;
use user_aborted; //TODO
use function cms_installer\translator;

require_once __DIR__.DIRECTORY_SEPARATOR.'class.installer_base.php';

class cli_install extends installer_base
{
    private $_config = ['tmpdir'=>null,'op'=>'null','dofiles'=>true,'interactive'=>true,'dest'=>null];

    public function __construct(string $configfile = '')
    {
        parent::__construct($configfile);
        $this->load_config();
        $this->load_version_details();
    }

    protected function load_config()
    {
        static $_loaded = false;

        if( $_loaded ) return;
        $_loaded = true;

        parent::load_config();

        if( !$this->in_phar() ) {
            $this->_config['dest'] = dirname( $this->_config['dest'] );
        }
        else {
            $this->_config['dest'] = realpath( getcwd() );
        }

        // parse command arguments
        $opts = getopt('nhf:o:',[ 'nofiles', 'tmpdir:','op:','dest:','help']);
        if( isset( $opts['h'] ) || isset($opts['help']) ) {
            $this->show_help();
            return;
        }

        if( isset( $opts['f']) ) {
            $file = $opts['f'];
            if( is_file( $file ) ) {
                $config = parse_ini_file( $file );
                if( $config ) {
                    $this->_config = $config;
                }
            }
        }

        foreach( $opts as $key => $val ) {
            switch( $key ) {
            case 'n':
                $this->_config['interactive'] = false;
                break;

            case 'tmpdir':
                $this->_config['tmpdir'] = $val;
                break;

            case 'dest':
                $this->_config['dest'] = realpath($val);
                break;

            case 'nofiles':
                $this->_config['dofiles'] = false;
                break;

            case 'o':
            case 'op':
                $this->_config['op'] = strtolower( $val );
                break;
            }
        }
    }

    public function get_op()
    {
        if( !$this->_config['op'] ) return 'install';
        return $this->_config['op'];
    }

    public function is_interactive()
    {
        return $this->_config['interactive'];
    }

    public function get_options()
    {
        return $this->_config;
    }

    public function set_op( string $op )
    {
        $this->_config['op'] = trim($op);
    }

    public function merge_options( $params )
    {
        if( is_array( $params ) && count($params ) ) {
            $this->_config = array_merge( $this->_config, $params );
        }
    }

    protected function get_steps() : array
    {
        $patn = __DIR__.DIRECTORY_SEPARATOR.'cli'.DIRECTORY_SEPARATOR.'class.step_*.php';
        $files = glob($patn, GLOB_NOSORT);

        $out = [];
        foreach( $files as $one ) {
            $bn = basename($one, '.php');
            $class = substr($bn, 6);
            $classname = '\\cms_installer\\cli\\'.$class;
            $out[] = [ $one, $classname ];
        }
        if( $out ) {
            $tmp = array_column($out, 1);
            array_multisort($tmp, SORT_ASC, SORT_NATURAL, $out);
        }
        return $out;
    }

    public function run()
    {
        translator()->set_default_language('en_US');

        try {
            // get all the steps, and run them in sequence
            $tasks = $this->get_steps();
            foreach( $tasks as $task ) {
                require_once $task[0];
                $task_class = $task[1];
                $task = new $task_class( $this );
                $task->run();
            }
        }
        catch( user_aborted $e ) {
            exit(0);
        }
        catch( Exception $e ) {
            fprintf(STDERR,"ERROR: ".$e->GetMessage()."\n");
            exit(1);
        }
    }
} // class
