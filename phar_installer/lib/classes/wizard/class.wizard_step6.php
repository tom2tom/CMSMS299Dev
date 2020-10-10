<?php

namespace cms_installer\wizard;

use Exception;
use Throwable;
use function cms_installer\cleanString;
use function cms_installer\get_app;
use function cms_installer\de_entitize;
use function cms_installer\entitize;
use function cms_installer\is_email;
use function cms_installer\lang;
use function cms_installer\redirect;
use function cms_installer\smarty;

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
        if( $tmp ) {
            $this->_adminacct = array_merge($this->_adminacct, $tmp);
        }
    }

    private function valid_name($str)
    {
        //TODO name-check c.f. UserOperations::UsernameCheck()
        return true;
    }

    private function valid_pass($str)
    {
        //TODO P/W-check c.f. UserOperations::PasswordCheck()
        return true;
    }

    private function validate($acct)
    {
        if( $acct['username'] !== null ) {
            if( !$acct['username'] ) {
                throw new Exception(lang('error_adminacct_username'));
            }
            $tmp = cleanString($acct['username'],2);
            if( $tmp !== trim($acct['username']) ) {
                throw new Exception(lang('error_adminacct_username'));
            }
            if( !$this->valid_name($tmp) ) {
                throw new Exception(lang('error_adminacct_username'));
            }
        }

        if( $acct['password'] !== null ) {
            if( !$acct['password'] ) {
                throw new Exception(lang('error_adminacct_password'));
            }
            $tmp = trim(cleanString($acct['password'],0));
            if( $tmp !== $acct['password'] ) {
                throw new Exception(lang('error_adminacct_password'));
            }
            $tmp = trim(cleanString($acct['repeatpw'],0));
            if( $tmp !== $acct['password'] ) {
                throw new Exception(lang('error_adminacct_repeatpw'));
            }
            if( !$this->valid_pass($tmp) ) {
                throw new Exception(lang('error_adminacct_password'));
            }
        }

        if ($acct['emailaddr'] !== null) {
            if ($acct['emailaddr']) {
                $tmp = trim(filter_var($acct['emailaddr'],FILTER_SANITIZE_EMAIL));
                if( $tmp !== $acct['emailaddr'] || !is_email($acct['emailaddr']) ) {
                    throw new Exception(lang('error_adminacct_emailaddr'));
                }
            }
/*          if( empty($acct['emailaddr']) && !empty($acct['emailaccountinfo']) ) {
                throw new Exception(lang('error_adminacct_emailaddrrequired'));
            }
*/
        }
    }

    protected function process()
    {
        if( isset($_POST['username']) ) $this->_adminacct['username'] = de_entitize($_POST['username']);
        if( isset($_POST['emailaddr']) ) $this->_adminacct['emailaddr'] = de_entitize($_POST['emailaddr']);
        if( isset($_POST['password']) ) $this->_adminacct['password'] = de_entitize($_POST['password']);
        if( isset($_POST['repeatpw']) ) $this->_adminacct['repeatpw'] = de_entitize($_POST['repeatpw']);
/*
        if( isset($_POST['emailaccountinfo']) ) $this->_adminacct['emailaccountinfo'] = (int)$_POST['emailaccountinfo'];
        else $this->_adminacct['emailaccountinfo'] = 1;
*/
        try {
            $this->validate($this->_adminacct);
            $this->get_wizard()->set_data('adminaccount',$this->_adminacct);
            $url = $this->get_wizard()->next_url();
            redirect($url);
        }
        catch( Throwable $t ) {
            $smarty = smarty();
            $smarty->assign('error',$t->GetMessage());
        }
    }

    protected function display()
    {
        parent::display();
        $smarty = smarty();

        $app = get_app();
        $config = $app->get_config();
        foreach( $config as &$tmp ) {
            if( $tmp ) {
                $tmp = entitize($tmp);
            }
        }
        unset($tmp);

        $raw = $config['verbose'] ?? 0;
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
