<?php

namespace cms_installer\wizard;

use cms_installer\utils;
use Exception;
use function cms_installer\CMSMS\lang;
use function cms_installer\CMSMS\smarty;
use function cms_installer\CMSMS\translator;
use function cms_installer\get_app;

class wizard_step5 extends wizard_step
{
    private $_siteinfo;

    public function run()
    {
        $app = get_app();

        $tz = date_default_timezone_get();
        if( !$tz ) @date_default_timezone_set('UTC');

        $this->_siteinfo = [ 'sitename'=>'','languages'=>[] ];
        $tmp = $this->get_wizard()->get_data('config');
        if( $tmp ) $this->_siteinfo = array_merge($this->_siteinfo,$tmp);
        $lang = translator()->get_selected_language();
        if( $lang != 'en_US' ) $this->_siteinfo['languages'] = [ $lang ];

        $tmp = $this->get_wizard()->get_data('siteinfo');
        if( $tmp ) $this->_siteinfo = $tmp;
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

        if( isset($_POST['xmodules']) ) {
            $tmp = [];
            foreach ( $_POST['xmodules'] as $name ) {
                $tmp[] = utils::clean_string($name);
            }
            $this->_siteinfo['xmodules'] = $tmp;
        }

        if( isset($_POST['samplecontent']) ) {
            $this->_siteinfo['samplecontent'] = filter_var($_POST['samplecontent'], FILTER_VALIDATE_BOOLEAN);
        }

        if( isset($_POST['sitename']) ) $this->_siteinfo['sitename'] = utils::clean_string($_POST['sitename']);
        if( isset($_POST['languages']) ) {
            $tmp = [];
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

        $app = get_app();
        $config = $app->get_config();
        $raw = $config['verbose'] ?? 0;
//        $v = ($raw === null) ? $this->get_wizard()->get_data('verbose',0) : (int)$raw;
        $smarty->assign('verbose',(int)$raw);

        $raw = $config['sitename'] ?? null;
        $v = ($raw === null) ? $this->_siteinfo['sitename'] : trim($raw);
        $smarty->assign('sitename',$v);

        $languages = $app->get_language_list();
        unset($languages['en_US']);
        $smarty->assign('language_list',$languages);
        $raw = $config['exlangs'] ?? null;
        if( $raw ) {
            if( is_array($raw) ) {
                array_walk($raw,function(&$v) {
                    $v = trim($v);
                });
                $v = $raw;
            }
            else {
                $v = [trim($raw)];
            }

        }
        else {
            $v = [];
        }
        $smarty->assign('languages',$v);
        $smarty->assign('yesno',['0'=>lang('no'),'1'=>lang('yes')]);

        $raw = $app->get_noncore_modules();
		if( $raw ) {
			$modules = array_combine($raw, $raw);
		}
		else {
			$modules = null;
		}
        $smarty->assign('modules_list',$modules);
        $smarty->assign('modules_sel', (($modules) ? $config['modules'] ?? null : null));

        $smarty->display('wizard_step5.tpl');
        $this->finish();
    }
} // class
