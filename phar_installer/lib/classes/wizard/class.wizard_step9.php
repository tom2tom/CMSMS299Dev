<?php

namespace __installer\wizard;

use __installer\session;
use cms_config;
use CMSMS\ModuleOperations;
use Exception;
use const CMS_DB_PREFIX;
use function __installer\CMSMS\endswith;
use function __installer\CMSMS\lang;
use function __installer\CMSMS\smarty;
use function __installer\get_app;
use function cms_module_places;
use function cmsms;

class wizard_step9 extends wizard_step
{
    protected function process()
    {
        // nothing here
    }

    private function do_upgrade($version_info)
    {
        $app = get_app();
        $destdir = $app->get_destdir();
        if( !$destdir ) throw new Exception(lang('error_internal',900));

        $this->connect_to_cmsms($destdir);

        // upgrade modules
        $this->message(lang('msg_upgrademodules'));

        $siteinfo = $this->get_wizard()->get_data('siteinfo');
        $allmodules = $siteinfo['havemodules'] ?? [];
        $modops = ModuleOperations::get_instance();
        foreach( $allmodules as $name ) {
            if( $modops->IsSystemModule($name) ) {
                $this->verbose(lang('msg_upgrade_module',$name));
                // force all system modules to be loaded
                // any such module which needs upgrade should automagically do so
                $module = $modops->get_module_instance($name,'',TRUE);
                if( !is_object($module) ) {
                    $this->error("FATAL ERROR: could not load module {$name} for upgrade");
                }
            }
            else {
                $module = $modops->get_module_instance($name,'',FALSE);
                if( is_object($module) ) {
                    $res = $modops->UpgradeModule($name);
                    if( $res[0] ) {
                        $this->verbose(lang('msg_upgrade_module',$name));
                    } else {
                        $msg = lang('error_modulebad',$name);
                        $msg .= ': '.$res[1];
                        $this->error($msg);
                    }
                }
/* no extra installs during an upgrade
                else {
                    // not-yet-installed non-system module
                    $res = $modops->InstallModule($name);
                    if( $res[0] ) {
                        $this->verbose(lang('install_module',$name));
                    } else {
                        $msg = lang('error_modulebad',$name);
                        $msg .= ': '.$res[1];
                        $this->error($msg);
                    }
                }
*/
            }
        }

        // clear the cache
        $this->message(lang('msg_clearcache'));
        cmsms()->clear_cached_files();

        $cfgfile = $destdir.DIRECTORY_SEPARATOR.'config.php';
        // write protect config.php
        @chmod($cfgfile,0440);

        // todo: write history

        // set the finished message
        $url = $app->get_root_url();
        $admin_url = $url;
        if( !endswith($url,'/') ) $admin_url .= '/';
        include_once $cfgfile;
        $aname = (!empty($config['admin_dir'])) ? $config['admin_dir'] : 'admin';
        $admin_url .= $aname;

        if( $app->has_custom_destdir() || !$app->in_phar() ) {
            $this->set_block_html('bottom_nav',lang('finished_custom_upgrade_msg',$admin_url,$url));
        }
        else {
            $this->set_block_html('bottom_nav',lang('finished_upgrade_msg',$url,$admin_url));
        }
    }

    public function do_install()
    {
        $app = get_app();
        $destdir = $app->get_destdir();
        if( !$destdir ) throw new Exception(lang('error_internal',901));

        // install modules
        $this->message(lang('install_modules'));
        $this->connect_to_cmsms($destdir);
        $modops = cmsms()->GetModuleOperations();
        $db = cmsms()->GetDb();
        $stmt1 = $db->Prepare('INSERT INTO '.CMS_DB_PREFIX.'modules
(module_name,version,status,admin_only,active,allow_fe_lazyload,allow_admin_lazyload)
VALUES (?,?,\'installed\',?,1,?,?)');
        $stmt2 = $db->Prepare('INSERT INTO '.CMS_DB_PREFIX.'module_deps
(parent_module,child_module,minimum_version,create_date,modified_date)
VALUES (?,?,?,NOW(),NOW())');

        $dirs = cms_module_places();
        foreach( $dirs as $bp ) {
            $contents = scandir($bp, SCANDIR_SORT_NONE);
            foreach( $contents as $modname ) {
                if( $modname == '.' || $modname == '..' || $modname == 'index.html' ) continue;
                $fp = $bp.DIRECTORY_SEPARATOR.$modname.DIRECTORY_SEPARATOR.$modname.'.module.php';
                if( file_exists($fp) ) {
                    require_once $fp;
                    $name = '\\'.$modname;
                    $modinst = new $name();
                    if( $modinst ) {
                        try {
                            $this->mod_install($modops, $modinst, $db, $stmt1, $stmt2);
                        } catch ( Exception $e ) {
                            $msg = lang('error_modulebad', $modname);
                            $tmp = $e->GetMessage();
                            if( is_string($tmp) ) {
                                $msg .= ': '.$tmp;
                            }
                            $this->error($msg);
                            continue;
                        }
                        $this->verbose(lang('install_module', $modname));
                    }
                }
            }
        }

        // write-protect config.php
        @chmod("$destdir/config.php",0440);

//        $adminacct = $this->get_wizard()->get_data('adminaccount');
        $root_url = $app->get_root_url();
        if( !endswith($root_url,'/') ) $root_url .= '/';
        $admin_url = $root_url.'admin';

/*      if( is_array($adminacct) && !empty($adminacct['emailaccountinfo']) && !empty($adminacct['emailaddr']) ) {
            try {
                $this->message(lang('send_admin_email'));
                $mailer = new CMSMS\Mailer();
                $mailer->AddAddress($adminacct['emailaddr']);
                $mailer->SetSubject(lang('email_accountinfo_subject'));
                $body = null;
                if( $app->in_phar() ) {
                    $body = lang('email_accountinfo_message',
                                            $adminacct['username'],$adminacct['password'],
                                            $destdir, $root_url);
                }
                else {
                    $body = lang('email_accountinfo_message_exp',
                                            $adminacct['username'],$adminacct['password'],
                                            $destdir);
                }
                $body = html_entity_decode($body, ENT_QUOTES);
                $mailer->SetBody($body);
                $mailer->Send();
            }
            catch( \Exception $e ) {
                $this->error(lang('error_sendingmail').': '.$e->GetMessage());
            }
        }
*/

        // todo: write history

        $this->message(lang('msg_clearcache'));
        cmsms()->clear_cached_files();

        // set the finished message.
        if( !$root_url || !$app->in_phar() ) {
            // find the common part of the SCRIPT_FILENAME and the destdir
            // /var/www/phar_installer/index.php
            // /var/www/foo
            $this->set_block_html('bottom_nav',lang('finished_custom_install_msg',$admin_url));
        }
        else {
            if( endswith($root_url,'/') ) $admin_url = $root_url.'admin';
            $this->set_block_html('bottom_nav',lang('finished_install_msg',$root_url,$admin_url));
        }
    }

    /**
     * @ignore
     * @throws Exception
     */
    private function do_freshen()
    {
        // create tmp directories
        $app = get_app();
        $destdir = $app->get_destdir();
        if( !$destdir ) throw new Exception(lang('error_internal',901));
        $this->message(lang('install_createtmpdirs'));
        @mkdir($destdir.'/tmp/cache',0771,TRUE);
        @mkdir($destdir.'/tmp/templates_c',0771,TRUE);

        // write protect config.php
        @chmod("$destdir/config.php",0440);

        // clear the cache
        $this->connect_to_cmsms($destdir);
        $this->message(lang('msg_clearcache'));
        cmsms()->clear_cached_files();

        // todo: write history

        // set the finished message.
        if( $app->has_custom_destdir() ) {
            $this->set_block_html('bottom_nav',lang('finished_custom_freshen_msg'));
        }
        else {
            $url = $app->get_root_url();
            $admin_url = $url;
            if( !endswith($url,'/') ) $admin_url .= '/';
            $cfgfile = $destdir.DIRECTORY_SEPARATOR.'config.php';
            include_once $cfgfile;
            $aname = (!empty($config['admin_dir'])) ? $config['admin_dir'] : 'admin';
            $admin_url .= $aname;
            $this->set_block_html('bottom_nav',lang('finished_freshen_msg', $url, $admin_url ));
        }
    }

    /**
     * @global int $DONT_LOAD_SMARTY
     * @global type $CMS_VERSION
     * @global int $CMS_PHAR_INSTALLER
     * @param sring $destdir
     */
    private function connect_to_cmsms($destdir)
    {
        // this loads the standard CMSMS stuff, except smarty cuz it's already done.
        // we do this here because both upgrade and install stuff needs it.
        global $DONT_LOAD_SMARTY, $CMS_VERSION, $CMS_PHAR_INSTALLER;
        $CMS_PHAR_INSTALLER = 1;
        $DONT_LOAD_SMARTY = 1;
        $CMS_VERSION = $this->get_wizard()->get_data('destversion');
        if( is_file("$destdir/include.php") ) {
            include_once $destdir.'/include.php';
        } else {
            include_once $destdir.'/lib/include.php';
        }

        if( !defined('CMS_VERSION') ) {
            define('CMS_VERSION',$CMS_VERSION);
        }
        // we do this here, because the config.php class may not set the define when in an installer.
        if( !defined('CMS_DB_PREFIX') ) {
            $config = cms_config::get_instance();
            define('CMS_DB_PREFIX',$config['db_prefix']);
        }
    }

    /**
     * @ignore
     * @param ModuleOperations $modops
     * @param CmsModule-derivative $modinst
     * @param Connection-derivative $db
     * @param mysqli statement object $stmt1 for updating main table
     * @param mysqli statement object $stmt2 for updating deps table
     * @throws Exception
     */
    private function mod_install(ModuleOperations &$modops, &$modinst, $db, $stmt1, $stmt2)
    {
        $result = $modinst->Install();
        if( $result == FALSE ) {
            // a successful installation
            $modname = $modinst->GetName();
            $admin = ($modinst->IsAdminOnly()) ? 1 : 0;
            $lazy_fe = ($admin || (method_exists($modinst,'LazyLoadFrontend') && $modinst->LazyLoadFrontend())) ? 1 : 0;
            $lazy_admin = (method_exists($modinst,'LazyLoadAdmin') && $modinst->LazyLoadAdmin()) ? 1 : 0;
/*
stmt1: 'INSERT INTO '.CMS_DB_PREFIX.'modules
(module_name,version,status,admin_only,active,allow_fe_lazyload,allow_admin_lazyload)
VALUES (?,?,\'installed\',?,1,?,?)');
 */
            $rs = $db->Execute($stmt1, [
                $modname,$modinst->GetVersion(),$admin,$lazy_fe,$lazy_admin
            ]);

            $deps = $modinst->GetDependencies();
            if( $deps ) {
                foreach( $deps as $depname => $depversion ) {
                    if( $depname && $depversion ) {
/*
stmt2: 'INSERT INTO '.CMS_DB_PREFIX.'module_deps
(parent_module,child_module,minimum_version,create_date,modified_date)
VALUES (?,?,?,NOW(),NOW())');
*/
                        $rs = $db->Execute($stmt2,[$depname,$modname,$depversion]);
                    }
                }
            }
            $modops->generate_moduleinfo($modinst); //uses defined CMS_VERSION
        } else {
            throw new Exception($result); //send back numeric code or error-string
        }
    }

    /**
     *
     * @throws Exception
     */
    protected function display()
    {
        $app = get_app();
        $smarty = smarty();

        // display the template right off the bat.
        parent::display();
        $smarty->assign('back_url',$this->get_wizard()->prev_url());
        $smarty->display('wizard_step9.tpl');
        $destdir = $app->get_destdir();
        if( !$destdir ) throw new Exception(lang('error_internal',903));


        // here, we do either the upgrade, or the install stuff.
        try {
            $action = $this->get_wizard()->get_data('action');
            $tmp = $this->get_wizard()->get_data('version_info');
            if( $action == 'upgrade' && $tmp ) {
                $this->do_upgrade($tmp);
            }
            elseif( $action == 'freshen' ) {
                $this->do_freshen();
            }
            elseif( $action == 'install' ) {
                $this->do_install();
            }
            else {
                throw new Exception(lang('error_internal',910));
            }

            // clear the session.
            $sess = session::get();
            $sess->clear();

            $this->finish();
        }
        catch( Exception $e ) {
            $this->error($e->GetMessage());
        }

        $app->cleanup();
    }
} // class

