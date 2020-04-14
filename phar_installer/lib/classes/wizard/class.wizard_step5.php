<?php

namespace cms_installer\wizard;

use Exception;
use Throwable;
use function cms_installer\clean_string;
use function cms_installer\get_app;
use function cms_installer\joinpath;
use function cms_installer\lang;
use function cms_installer\redirect;
use function cms_installer\smarty;
use function cms_installer\translator;

class wizard_step5 extends wizard_step
{
    private $_siteinfo;

    public function run()
    {
        $wiz = $this->get_wizard();

        $tz = date_default_timezone_get();
        if( !$tz ) @date_default_timezone_set('UTC');

        $this->_siteinfo = ['languages'=>[]];

        $action = $wiz->get_data('action');
        if( $action == 'install' ) {
            $this->_siteinfo += ['sitename'=>'','supporturl'=>''];
        }

        $tmp = $wiz->get_data('config');
        if( $tmp ) $this->_siteinfo = array_merge($this->_siteinfo,$tmp);
        $lang = translator()->get_selected_language();
        if( $lang != 'en_US' ) $this->_siteinfo['languages'] = [$lang];

        $tmp = $wiz->get_data('siteinfo');
        if( $tmp ) $this->_siteinfo = array_merge($this->_siteinfo,$tmp);
        return parent::run();
    }

    private function validate($siteinfo)
    {
        $action = $this->get_wizard()->get_data('action');
        if( $action == 'install' ) {
            if( empty($siteinfo['sitename']) ) throw new Exception(lang('error_nositename'));
        }
    }

    protected function process()
    {
        $app = get_app();
        $config = $app->get_config();

        if( isset($_POST['wantedextras']) ) {
            //record the selected members of $app_config['extramodules']
            $tmp = [];
            foreach ( $_POST['wantedextras'] as $name ) {
                $tmp[] = clean_string($name);
            }
            $this->_siteinfo['wantedextras'] = $tmp;
        }

        if( isset($_POST['samplecontent']) ) {
            $this->_siteinfo['samplecontent'] = filter_var($_POST['samplecontent'], FILTER_VALIDATE_BOOLEAN);
        }

        if( isset($_POST['sitename']) ) $this->_siteinfo['sitename'] = clean_string($_POST['sitename']);

        if( isset($_POST['supporturl']) ) {
            $url = filter_var($_POST['supporturl'], FILTER_SANITIZE_URL);
            $pass = filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED, FILTER_FLAG_HOST_REQUIRED);
            // the test above barfs for non-ASCII chars
            if( !$pass && preg_match('/[\x80-\xFF]/', $url) &&
                // fallback to a rough check (ignores dodgy chars in it)
                parse_url($url, PHP_URL_SCHEME) && parse_url($url, PHP_URL_HOST) ) {
                $pass = true;
            }
            if ($pass) {
                $this->_siteinfo['supporturl'] = $url;
            } else {
                unset($this->_siteinfo['supporturl']);
            }
        }

        if( isset($_POST['languages']) ) {
            $tmp = [];
            foreach( $_POST['languages'] as $lang ) {
                $tmp[] = clean_string($lang);
            }
            $this->_siteinfo['languages'] = $tmp;
        }

        $wiz = $this->get_wizard();
        $wiz->set_data('siteinfo',$this->_siteinfo);
        try {
            $this->validate($this->_siteinfo);

            if( $config['nofiles'] ) {
                $url = $wiz->step_url(8);
            }
            elseif( $wiz->get_data('action') == 'install' ) {
                $url = $wiz->next_url();
            }
            else {  // upgrade or freshen
                $url = $wiz->step_url(7);
            }
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
        $action = $this->get_wizard()->get_data('action');

        $smarty = smarty();
        $smarty->assign('action',$action);

        $app = get_app();
        $config = $app->get_config();
        $raw = $config['verbose'] ?? 0;
//        $v = ($raw === null) ? $this->get_wizard()->get_data('verbose',0) : (int)$raw;
        $smarty->assign('verbose',(int)$raw);

        if( $action == 'install' ) {
            $raw = $config['sitename'] ?? null;
            $v = ($raw === null) ? $this->_siteinfo['sitename'] : trim($raw);
            $smarty->assign('sitename',$v);

            $raw = $config['supporturl'] ?? null;
            $v = ($raw === null) ? '' : trim($raw);
            $smarty->assign('supporturl',$v);

            $smarty->assign('yesno',['0'=>lang('no'),'1'=>lang('yes')]);
        }
        elseif( $action == 'upgrade' ) {
            // if pertinent upgrade
            $version_info = $this->get_wizard()->get_data('version_info');
            if( version_compare($version_info['version'],'2.2.910') < 0 ) {
                $raw = $app->get_dest_version();
                if( version_compare($raw,'2.2.910') >= 0 ) { //should always be true, here
                    $raw = $config['supporturl'] ?? null;
                    $v = ($raw === null) ? '' : trim($raw);
                    $smarty->assign('supporturl',$v);
                }
            }
        }

        $languages = $app->get_language_list();
        unset($languages['en_US']);
        if( $languages && $action == 'upgrade' ) {
            // exclude installed languages
            $v = (!empty($config['admindir'])) ? $config['admindir'] : 'admin';
            $fp = joinpath($app->get_destdir(),$v,'lang','ext','');
            $raw = glob($fp.'*.php',GLOB_NOSORT);
            if( $raw ) {
                foreach( $languages as $key=>$v ) {
                    $tmp = $fp.$key.'.php';
                    if( in_array($tmp, $raw) ) {
                        unset($languages[$key]);
                    }
                }
            }
        }
        $smarty->assign('language_list',$languages);
        $raw = $config['selectlangs'] ?? null;
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

        if( $action != 'freshen' ) {
            $raw = $app->get_noncore_modules();
            if( $raw && $action == 'upgrade' ) {
                // exclude installed modules
                $fp = $app->get_destdir();
                //TODO if not using assets/modules for non-core modules
                $v = (!empty($config['assetsdir'])) ? $config['assetsdir'] : 'assets';
                $dirs = [
                    $fp.DIRECTORY_SEPARATOR.$v.DIRECTORY_SEPARATOR.'modules',
                    $fp.DIRECTORY_SEPARATOR.'modules',
                    ];
                foreach( $raw as $key=>$v ) {
                    foreach( $dirs as $dir) {
                        $fp = $dir.DIRECTORY_SEPARATOR.$v;
                        if( is_dir($fp) && is_file($fp.DIRECTORY_SEPARATOR.$v.'.module.php') ) {
                            unset($raw[$key]);
                            break;
                        }
                    }
                }
            }
            if( $raw ) {
                $modules = array_combine($raw, $raw);
            }
            else {
                $modules = null;
            }
            $smarty->assign('modules_list',$modules);
            $smarty->assign('modules_sel', (($modules) ? $config['selectmodules'] ?? null : null));
        }

        $smarty->display('wizard_step5.tpl');
        $this->finish();
    }
} // class
