<?php

namespace cms_installer\wizard;

use cms_installer\session;
use cms_config;
use CMSMS\AdminUtils;
use CMSMS\ModuleOperations;
use Exception;
use const CMS_DB_PREFIX;
use function cms_installer\lang;
use function cms_installer\smarty;
use function cms_installer\get_app;
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

        // write history
        audit('', 'System Upgraded', 'New version '.CMS_VERSION);

        // clear the cache
        $this->message(lang('msg_clearcache'));
        AdminUtils::clear_cache();

        $cfgfile = $destdir.DIRECTORY_SEPARATOR.'config.php';
        // write protect config.php
        @chmod($cfgfile,0440);

        // set the finished message
        $url = $app->get_root_url();
        $admin_url = $url;
        if( !endswith($url,'/') ) $admin_url .= '/';
        include_once $cfgfile;
        $aname = (!empty($config['admin_dir'])) ? $config['admin_dir'] : 'admin';
        $admin_url .= $aname;

        if( $app->has_custom_destdir() || !$app->in_phar() ) {
            $msg = lang('finished_custom_upgrade_msg',$admin_url,$url);
        }
        else {
            $msg = lang('finished_upgrade_msg',$url,$admin_url);
        }
        $this->set_block_html('bottom_nav',$msg);
    }

    public function do_install()
    {
        $app = get_app();
        $destdir = $app->get_destdir();
        if( !$destdir ) throw new Exception(lang('error_internal',901));

        $this->connect_to_cmsms($destdir);

        // install modules
        $this->message(lang('install_modules'));
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
                if( is_file($fp) ) {
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

        // write history
        audit('', 'System Installed', 'Version '.CMS_VERSION);

        // write-protect config.php
        @chmod("$destdir/config.php",0440);

//        $adminacct = $this->get_wizard()->get_data('adminaccount');
        $root_url = $app->get_root_url();
        if( !endswith($root_url,'/') ) $root_url .= '/';
        $admin_url = $root_url.'admin';

        $this->message(lang('msg_clearcache'));
        AdminUtils::clear_cache();

        // set the finished message.
        if( !$root_url || !$app->in_phar() ) {
            $msg = lang('finished_custom_install_msg',$admin_url);
        }
        else {
            $msg = lang('finished_install_msg',$root_url,$admin_url);
        }
        $this->set_block_html('bottom_nav',$msg);
    }

    /**
     * @ignore
     * @throws Exception
     */
    private function do_freshen()
    {
        $app = get_app();
        $destdir = $app->get_destdir();
        if( !$destdir ) throw new Exception(lang('error_internal',901));

        $this->connect_to_cmsms($destdir);

        // in case they're gone, try to create tmp directories
        @mkdir(TMP_CACHE_LOCATION,0771,TRUE);
        @mkdir(TMP_TEMPLATES_C_LOCATION,0771,TRUE);
        // another failsafe - write protect the config file
        @chmod(CONFIG_FILE_LOCATION,0440);

        // write history
        audit('', 'System Freshened', 'All core files renewed');

        // clear the cache
        $this->message(lang('msg_clearcache'));
        AdminUtils::clear_cache();

        // set the finished message
        if( $app->has_custom_destdir() ) {
            $msg = lang('finished_custom_freshen_msg');
        }
        else {
            include_once CONFIG_FILE_LOCATION;
            $aname = (!empty($config['admin_dir'])) ? $config['admin_dir'] : 'admin';

            $url = $app->get_root_url();
            $admin_url = $url;
            if( !endswith($url,'/') ) $admin_url .= '/';
            $admin_url .= $aname;
            $msg = lang('finished_freshen_msg',$url,$admin_url);
        }
        $this->set_block_html('bottom_nav',$msg);
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
            $sess = session::get_instance();
            $sess->clear();

            $this->finish();
        }
        catch( Exception $e ) {
            $this->error($e->GetMessage());
        }

        $app->cleanup();
    }
} // class
