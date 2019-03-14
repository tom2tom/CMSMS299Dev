<?php

namespace cms_installer\wizard;

use cms_installer\utils;
use Exception;
use function cms_installer\lang;
use function cms_installer\smarty;
use function cms_installer\get_app;

class wizard_step6 extends wizard_step
{
    private $_adminacct;

    public function __construct()
    {
        parent::__construct();
        $this->_adminacct = [
        'username'=>'admin',
        'emailaddr'=>'',
        'password'=>'',
        'repeatpw'=>'',
//       'emailaccountinfo'=>1,
         ];
        $tmp = $this->get_wizard()->get_data('adminaccount');
        if( $tmp ) $this->_adminacct = $tmp;
    }

    private function validate($acct)
    {
        if( !isset($acct['username']) || $acct['username'] == '' ) throw new Exception(lang('error_adminacct_username'));
        if( !isset($acct['password']) || $acct['password'] == '' || strlen($acct['password']) < 6 ) {
            throw new Exception(lang('error_adminacct_password'));
        }
        if( !isset($acct['repeatpw']) || $acct['repeatpw'] != $acct['password'] ) {
            throw new Exception(lang('error_adminacct_repeatpw'));
        }
        if( !empty($acct['emailaddr']) && !utils::is_email($acct['emailaddr']) ) {
            throw new Exception(lang('error_adminacct_emailaddr'));
        }
/*      if( empty($acct['emailaddr']) && !empty($acct['emailaccountinfo']) ) {
            throw new Exception(lang('error_adminacct_emailaddrrequired'));
        }
*/
    }

    protected function process()
    {
        $this->_adminacct['username'] = utils::clean_string($_POST['username']);
        $this->_adminacct['emailaddr'] = utils::clean_string($_POST['emailaddr']);
        $this->_adminacct['password'] = trim(filter_var($_POST['password'], FILTER_SANITIZE_STRING,
            FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK | FILTER_FLAG_NO_ENCODE_QUOTES));
        $this->_adminacct['repeatpw'] = trim(filter_var($_POST['repeatpw'], FILTER_SANITIZE_STRING,
            FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK | FILTER_FLAG_NO_ENCODE_QUOTES));
/*
        if( isset($_POST['emailaccountinfo']) ) $this->_adminacct['emailaccountinfo'] = (int)$_POST['emailaccountinfo'];
        else $this->_adminacct['emailaccountinfo'] = 1;
*/
        $this->get_wizard()->set_data('adminaccount',$this->_adminacct);
        try {
            $this->validate($this->_adminacct);
            $url = $this->get_wizard()->next_url();
            utils::redirect($url);
        }
        catch( Exception $e ) {
            $smarty = smarty();
            $smarty->assign('error',$e->GetMessage());
        }
    }

    protected function display()
    {
        parent::display();
        $smarty = smarty();

        $app = get_app();
        $config = $app->get_config();
        $raw = $config['verbose'] ?? 0;
//        $v = ($raw === null) ? $this->get_wizard()->get_data('verbose',0) : (int)$raw;
        $smarty->assign('verbose',(int)$raw);

        $tmp = $this->_adminacct;
        $raw = $config['adminlogin'] ?? null;
        if ($raw !== null) {
            $tmp['username'] = trim($raw);
        }
        $raw = $config['adminemail'] ?? null;
        if ($raw !== null) {
            $tmp['emailaddr'] = trim($raw);
        }
        $raw = $config['adminpw'] ?? null;
        if ($raw !== null) {
            $tmp['password'] = trim($raw);
        }
        $smarty->assign('account',$tmp);
        $smarty->display('wizard_step6.tpl');

        $this->finish();
    }

} // class
