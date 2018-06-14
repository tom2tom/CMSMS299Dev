<?php

namespace cms_autoinstaller;

use CMSMS\Database\mysqli\Connection;
use Exception;
use LogicException;

class wizard_step4 extends wizard_step
{
    private $_config;
    private $_dbms_options;

    public function __construct()
    {
        if( !extension_loaded('mysqli') ) throw new Exception(\__appbase\lang('error_nodatabases'));

        parent::__construct();

        $tz = date_default_timezone_get();
        if( !$tz ) {
			$tz = 'UTC';
			@date_default_timezone_set('UTC');
		}
        $this->_config = [
//			'db_type'=>'mysqli',
			'db_hostname'=>'localhost',
			'db_name'=>'',
			'db_username'=>'',
            'db_password'=>'',
			'db_prefix'=>'cms_',
			'db_port'=>'',
			'timezone'=>$tz,
            'query_var'=>'',
            'samplecontent'=>TRUE,
		];

        // get saved data
        $tmp = $this->get_wizard()->get_data('config');
        if( $tmp ) $this->_config = array_merge($this->_config,$tmp);

        $action = $this->get_wizard()->get_data('action');
        if( $action == 'freshen' || $action == 'upgrade' ) {
            // read config data from config.php for these actions
            $app = \__appbase\get_app();
            $destdir = $app->get_destdir();
            $config_file = $destdir.DIRECTORY_SEPARATOR.'config.php';
            include_once $config_file;
//            $this->_config['db_type'] = $config['dbms'];
            $this->_config['db_hostname'] = $config['db_hostname'];
            $this->_config['db_username'] = $config['db_username'];
            $this->_config['db_password'] = $config['db_password'];
            $this->_config['db_name'] = $config['db_name'];
            $this->_config['db_prefix'] = $config['db_prefix'];
            if( isset($config['db_port']) ) $this->_config['db_port'] = $config['db_port'];
            if( isset($config['timezone']) ) $this->_config['timezone'] = $config['timezone'];
            if( isset($config['query_var']) ) $this->_config['query_var'] = $config['query_var'];
        }
    }

    private function validate($config)
    {
//        if( empty($config['db_type']) ) throw new Exception(\__appbase\lang('error_nodbtype'));
        if( empty($config['db_hostname']) ) throw new Exception(\__appbase\lang('error_nodbhost'));
        if( empty($config['db_name']) ) throw new Exception(\__appbase\lang('error_nodbname'));
        if( empty($config['db_username']) ) throw new Exception(\__appbase\lang('error_nodbuser'));
        if( empty($config['db_password']) ) throw new Exception(\__appbase\lang('error_nodbpass'));
        if( empty($config['db_prefix']) ) throw new Exception(\__appbase\lang('error_nodbprefix'));
        if( empty($config['timezone']) ) throw new Exception(\__appbase\lang('error_notimezone'));

		//TODO filter_var($config['query_var'], FILTER_SANITIZE ...);
        $re = '/^[a-zA-Z0-9_\.]*$/';
        if( !empty($config['query_var']) && !preg_match($re,$config['query_var']) ) {
            throw new Exception(\__appbase\lang('error_invalidqueryvar'));
        }

        $all_timezones = timezone_identifiers_list();
        if( !in_array($config['timezone'],$all_timezones) ) throw new Exception(\__appbase\lang('error_invalidtimezone'));

		$config['db_password'] = trim($config['db_password']);
        if( $config['db_password'] ) {
            $tmp = filter_var($config['db_password'], FILTER_SANITIZE_STRING,
			FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK | FILTER_FLAG_NO_ENCODE_QUOTES);
            if( $tmp != $config['db_password'] ) {
                throw new Exception(\__appbase\lang('error_invaliddbpassword'));
            }
        }

        // try a test connection
		try {
	        $db = new Connection($config);
		}
        catch( Exception $e ) {
            throw new Exception(\__appbase\lang('error_createtable'));
        }
        // see if we can create and drop a table.
        $action = $this->get_wizard()->get_data('action');
        try {
            $db->Execute('CREATE TABLE '.$config['db_prefix'].'_dummyinstall (i INT)');
        }
        catch( Exception $e ) {
            throw new Exception(\__appbase\lang('error_createtable'));
        }

        try {
            $db->Execute('DROP TABLE '.$config['db_prefix'].'_dummyinstall');
        }
        catch( Exception $e ) {
            throw new Exception(\__appbase\lang('error_droptable'));
        }

        // see if a smattering of core tables exist
        if( $action == 'install' ) {
            try {
                $res = $db->GetOne('SELECT content_id FROM '.$config['db_prefix'].'content');
                if( $res > 0 ) throw new Exception(\__appbase\lang('error_cmstablesexist'));
            }
            catch( LogicException $e ) {
                // if this fails it's not a problem
            }

            try {
                $db->GetOne('SELECT module_name FROM '.$config['db_prefix'].'modules');
                if( $res > 0 ) throw new Exception(\__appbase\lang('error_cmstablesexist'));
            }
            catch( LogicException $e ) {
                // if this fails it's not a problem.
            }
        }
    }

    protected function process()
    {
		$this->_config['db_type'] = 'mysqli';
//        if( isset($_POST['db_type']) ) $this->_config['db_type'] = trim(\__appbase\utils::clean_string($_POST['db_type']));
        $this->_config['db_hostname'] = trim(filter_var($_POST['db_hostname'], FILTER_SANITIZE_STRING,
			FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK | FILTER_FLAG_ENCODE_HIGH));
        $this->_config['db_name'] = trim(filter_var($_POST['db_name'], FILTER_SANITIZE_STRING,
			FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK | FILTER_FLAG_ENCODE_HIGH));
        $this->_config['db_username'] = trim(filter_var($_POST['db_username'], FILTER_SANITIZE_STRING,
			FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK | FILTER_FLAG_ENCODE_HIGH));
        $this->_config['db_password'] = trim(filter_var($_POST['db_password'], FILTER_SANITIZE_STRING,
			FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK | FILTER_FLAG_NO_ENCODE_QUOTES));
        if( isset($_POST['db_port']) ) $this->_config['db_port'] = filter_var($_POST['db_port'],FILTER_SANITIZE_NUMBER_INT);
        if( isset($_POST['db_prefix']) ) $this->_config['db_prefix'] = trim(filter_var($_POST['db_prefix'], FILTER_SANITIZE_STRING,
			FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK | FILTER_FLAG_STRIP_HIGH));
        $this->_config['timezone'] = trim(filter_var($_POST['timezone'], FILTER_SANITIZE_STRING));
        if( isset($_POST['query_var']) ) $this->_config['query_var'] = trim(filter_var($_POST['query_var'], FILTER_SANITIZE_STRING,
			FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK | FILTER_FLAG_STRIP_HIGH));
        if( isset($_POST['samplecontent']) ) $this->_config['samplecontent'] = filter_var($_POST['samplecontent'], FILTER_VALIDATE_BOOLEAN);
        $this->get_wizard()->set_data('config',$this->_config);

        try {
            $app = \__appbase\get_app();
            $config = $app->get_config();
            $this->validate($this->_config);
            $url = $this->get_wizard()->next_url();
            $action = $this->get_wizard()->get_data('action');
            if( $action == 'freshen' ) $url = $this->get_wizard()->step_url(6);
            if( $action == 'upgrade' ) {
                if( $config['nofiles'] ) {
                    $url = $this->get_wizard()->step_url(8);
                } else {
                    $url = $this->get_wizard()->step_url(7);
                }
            }
            \__appbase\utils::redirect($url);
        }
        catch( Exception $e ) {
            $smarty = \__appbase\smarty();
            $smarty->assign('error',$e->GetMessage());
        }
    }

    protected function display()
    {
        parent::display();
        $smarty = \__appbase\smarty();

        $tmp = timezone_identifiers_list();
        if( !is_array($tmp) ) throw new Exception(\__appbase\lang('error_tzlist'));
        $tmp2 = array_combine(array_values($tmp),array_values($tmp));
        $smarty->assign('timezones',array_merge(array(''=>\__appbase\lang('none')),$tmp2));
//        $smarty->assign('db_types',$this->_dbms_options);
        $smarty->assign('action',$this->get_wizard()->get_data('action'));
        $smarty->assign('verbose',$this->get_wizard()->get_data('verbose',0));
        $smarty->assign('config',$this->_config);
        $smarty->assign('yesno',array('0'=>\__appbase\lang('no'),'1'=>\__appbase\lang('yes')));
        $smarty->display('wizard_step4.tpl');
        $this->finish();
    }

} // class
