<?php

namespace __installer\wizard;

use __installer\utils;
use Exception;
use function __installer\CMSMS\lang;
use function __installer\CMSMS\smarty;
use function __installer\CMSMS\translator;
use function __installer\get_app;

class wizard_step6 extends wizard_step
{
    private $_siteinfo;

    public function run()
    {
        $app = get_app();

        $tz = date_default_timezone_get();
        if( !$tz ) @date_default_timezone_set('UTC');

        $this->_siteinfo = array( 'sitename'=>'','languages'=>[] );
        $tmp = $this->get_wizard()->get_data('config');
        if( $tmp ) $this->_siteinfo = array_merge($this->_siteinfo,$tmp);
        $lang = translator()->get_selected_language();
        if( $lang != 'en_US' ) $this->_siteinfo['languages'] = [ $lang ];

        $tmp = $this->get_wizard()->get_data('siteinfo');
        if( is_array($tmp) && count($tmp) ) $this->_siteinfo = $tmp;
        return parent::run();
    }

    private function validate($siteinfo)
    {
        $action = $this->get_wizard()->get_data('action');
        if( $action !== 'freshen' ) {
            if( !isset($siteinfo['sitename']) || !$siteinfo['sitename'] ) throw new Exception(lang('error_nositename'));
        }
    }

    protected function process()
    {
        $app = get_app();
        $config = $app->get_config();

        if( isset($_POST['sitename']) ) $this->_siteinfo['sitename'] = trim(utils::clean_string($_POST['sitename']));
        if( isset($_POST['languages']) ) {
            $tmp = array();
            foreach ( $_POST['languages'] as $lang ) {
                $tmp[] = utils::clean_string($lang);
            }
            $this->_siteinfo['languages'] = $tmp;
        }

        $this->get_wizard()->set_data('siteinfo',$this->_siteinfo);
        try {
            $this->validate($this->_siteinfo);
            $url = $this->get_wizard()->next_url();
            if( $config['nofiles'] ) $url = $this->get_wizard()->step_url(8);
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
        $action = $this->get_wizard()->get_data('action');

        $smarty = smarty();
        $smarty->assign('action',$action);
        $smarty->assign('verbose',$this->get_wizard()->get_data('verbose',0));
        $smarty->assign('siteinfo',$this->_siteinfo);
        $smarty->assign('yesno',array('0'=>lang('no'),'1'=>lang('yes')));
        $languages = get_app()->get_language_list();
        unset($languages['en_US']);
        $smarty->assign('language_list',$languages);

        $smarty->display('wizard_step6.tpl');
        $this->finish();
    }
} // class
