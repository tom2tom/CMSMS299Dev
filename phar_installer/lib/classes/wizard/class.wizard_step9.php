<?php

namespace cms_installer\wizard;

use cms_installer\installer_base;
use cms_installer\wizard\wizard_step;
use CMSMS\AdminUtils;
use CMSMS\AppState;
use CMSMS\ContentOperations;
use CMSMS\ContentTypeOperations;
use CMSMS\ModuleOperations;
use CMSMS\SysDataCache;
use CMSMS\SystemCache;
use Exception;
use Throwable;
use const CMS_DB_PREFIX;
use const CMS_VERSION;
use const CONFIG_FILE_LOCATION;
use const PUBLIC_CACHE_LOCATION;
use const TMP_CACHE_LOCATION;
use const TMP_TEMPLATES_C_LOCATION;
use function audit;
use function cms_installer\endswith;
use function cms_installer\get_app;
use function cms_installer\get_server_permissions;
use function cms_installer\joinpath;
use function cms_installer\lang;
use function cms_installer\rrmdir;
use function cms_installer\smarty;
use function cms_module_places;
use function cmsms;

class wizard_step9 extends wizard_step
{
    protected function process()
    {
        // nothing here
    }

    //Try to create local cache directories if they're gone, otherwise try to clear
    private function clear_filecaches()
    {
        $dirmode = get_server_permissions()[3]; // read+write

        if( is_dir(TMP_CACHE_LOCATION) ) {
            if( is_writable(TMP_CACHE_LOCATION) ) {
                rrmdir(TMP_CACHE_LOCATION, FALSE);
				chmod(TMP_CACHE_LOCATION, $dirmode);
            }
        }
        else {
            @mkdir(TMP_CACHE_LOCATION, $dirmode, TRUE);
        }
        if( TMP_CACHE_LOCATION != PUBLIC_CACHE_LOCATION ) {
            if( is_dir(PUBLIC_CACHE_LOCATION) ) {
                if( is_writable(PUBLIC_CACHE_LOCATION) ) {
                    rrmdir(PUBLIC_CACHE_LOCATION, FALSE);
					chmod(PUBLIC_CACHE_LOCATION, $dirmode);
                }
            }
            else {
                @mkdir(PUBLIC_CACHE_LOCATION, $dirmode, TRUE);
            }
        }
        if( is_dir(TMP_TEMPLATES_C_LOCATION) ) {
            if( is_writable(TMP_TEMPLATES_C_LOCATION) ) {
                rrmdir(TMP_TEMPLATES_C_LOCATION, FALSE);
				chmod(TMP_TEMPLATES_C_LOCATION, $dirmode);
            }
        }
        else {
            @mkdir(TMP_TEMPLATES_C_LOCATION, $dirmode, TRUE);
        }
    }

    private function do_upgrade($version_info)
    {
        $app = get_app();
        $destdir = $app->get_destdir();
        if( !$destdir ) throw new Exception(lang('error_internal',900));

        $this->connect_to_cmsms($destdir);

        // upgrade modules
        $this->message(lang('msg_upgrademodules'));

        $modops = ModuleOperations::get_instance();
        $coremodules = $app->get_config()['coremodules'];
        $modops->RegisterSystemModules($coremodules);
        $choices = $this->get_wizard()->get_data('sessionchoices');
        $allmodules = $choices['havemodules'] ?? $coremodules;

        foreach( $allmodules as $name ) {
            if( in_array($name, $coremodules) ) {
                // TODO merge upgraded modules|files back into main module-place (we don't use location to define status)
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
                    // TODO merge upgraded modules|files back into main module-place (we don't use location to define status)
                    $res = $modops->UpgradeModule($name);
                    if( $res[0] ) {
                        $this->verbose(lang('msg_upgrade_module',$name));
                    }
                    else {
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
                    }
                    else {
                        $msg = lang('error_modulebad',$name);
                        $msg .= ': '.$res[1];
                        $this->error($msg);
                    }
                }
*/
            }
        }

        // content types
        ContentTypeOperations::get_instance()->RebuildStaticContentTypes();

        // write history
        audit('', 'System Upgraded', 'New version '.CMS_VERSION);

        // security for the config file
        $filemode = get_server_permissions()[0]; // read-only
        @chmod(CONFIG_FILE_LOCATION,$filemode);

        // clear the caches
        $this->message(lang('msg_clearcache'));
        $this->clear_filecaches();
        SysDataCache::get_instance()->clear(); // clear all global-types' content
        SystemCache::get_instance()->clear();
        AdminUtils::clear_cached_files();

        // set the finished message
        $url = $app->get_root_url();
        $admin_url = $url;
        if( !endswith($url,'/') ) $admin_url .= '/';
        include_once CONFIG_FILE_LOCATION;
        $aname = (!empty($config['admin_path'])) ? $config['admin_path'] : 'admin';
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
        $choices = $this->get_wizard()->get_data('sessionchoices');
        if( !$choices ) throw new Exception(lang('error_internal',902));

        $this->connect_to_cmsms($destdir);

        // install modules
        $this->message(lang('install_modules'));
        $coremodules = $app->get_config()['coremodules'];
        $modops = cmsms()->GetModuleOperations();
        $modops->RegisterSystemModules($coremodules);

        $db = cmsms()->GetDb();
//(module_name,version,status,admin_only,active,allow_fe_lazyload,allow_admin_lazyload)
        $stmt1 = $db->Prepare('INSERT INTO '.CMS_DB_PREFIX.'modules
(module_name,version,status,admin_only,active)
VALUES (?,?,\'installed\',?,1)');
        $stmt2 = $db->Prepare('INSERT INTO '.CMS_DB_PREFIX.'module_deps
(parent_module,child_module,minimum_version,create_date)
VALUES (?,?,?,NOW())');

        $allmodules = $choices['havemodules'] ?? $coremodules;
        $modplace = $destdir.DIRECTORY_SEPARATOR.'modules';
        $len = strlen($modplace);
        $dirs = cms_module_places();
        foreach( $dirs as $bp ) {
            $contents = scandir($bp, SCANDIR_SORT_NONE);
            foreach( $contents as $modname ) {
                if( $modname == '.' || $modname == '..' || $modname == 'index.html' ) continue;
                if( in_array($modname, $allmodules) ) {
                    $fp = $bp.DIRECTORY_SEPARATOR.$modname.DIRECTORY_SEPARATOR.$modname.'.module.php';
                    if( is_file($fp) ) {
                        // move modules to historical place (we don't need|use their location to define status)
                        if( strncmp($bp, $modplace, $len) != 0 ) {
                            $fp = $bp.DIRECTORY_SEPARATOR.$modname;
                            $tp = $modplace.DIRECTORY_SEPARATOR.$modname;
                            if( !@rename($fp, $tp)) { throw new Exception('Failed to migrate module '.$modname); }
                            $fp = $tp.DIRECTORY_SEPARATOR.$modname.'.module.php';
                        }
                        require_once $fp;
                        $name = '\\'.$modname;
                        $modinst = new $name();
                        if( $modinst ) {
                            try {
                                $this->mod_install($modops, $modinst, $db, $stmt1, $stmt2);
                            }
                            catch( Throwable $t ) {
                                $msg = lang('error_modulebad', $modname);
                                $tmp = $t->GetMessage();
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
                else {
                    rrmdir($bp.DIRECTORY_SEPARATOR.$modname);
                }
            }
        }
        $stmt1->close();
        $stmt2->close();

        // content types
        ContentTypeOperations::get_instance()->RebuildStaticContentTypes();

        // site content
        if( !empty($choices['samplecontent']) ) {
            $arr = installer_base::CONTENTXML;
            $fn = end($arr);
        }
        else {
            $fn = 'initial.xml';
        }

        $dir = $app->get_assetsdir();
        $xmlfile = $dir.DIRECTORY_SEPARATOR.$fn;
        if( is_file($xmlfile) ) {
            if( $fn != 'initial.xml' ) {
                $this->message(lang('install_samplecontent'));
            }
            // these are irrelevant for 'initial.xml' but the importer API still wants them
            $dir = $app->get_rootdir();
            $arr = installer_base::UPLOADFILESDIR;
            $uploadsfolder = joinpath($dir, ...$arr);
            $arr = installer_base::CUSTOMFILESDIR;
            $workersfolder = joinpath($dir, ...$arr);

            try {
                if( ($fp = $app->get_phar()) ) {//NOT url-format: wrappers may be disabled
                    $fp = joinpath($fp,'lib','iosite.functions.php');
                }
                else {
                    $fp = joinpath(dirname(__DIR__,2),'iosite.functions.php');
                }
                $space = require_once $fp;
                if ($space === false) { /* TODO handle error */ }
                elseif ($space === 1) { $space = ''; }

                $funcname = ($space) ? $space.'\\import_content' : 'import_content';
                if( ($res = $funcname($xmlfile, $uploadsfolder, $workersfolder)) ) {
                    $this->error($res);
                }
                else {
                    // update pages hierarchy
                    $this->verbose(lang('install_updatehierarchy'));
                    ContentOperations::get_instance()->SetAllHierarchyPositions();
                }
            } catch( Throwable $t ) {
                if( $fn != 'initial.xml' ) {
                    $msg = 'Demonstration-content';
                }
                else {
                    $msg = 'Default-content';
                }
                $this->error($msg.' installation error: '.$t->getMessage());
            }
        }
        else {
            $this->error(lang('error_nocontent',$fn));
        }

        // write history
        audit('', 'System Installed', 'Version '.CMS_VERSION);

        // write-protect config.php
        $filemode = get_server_permissions()[0]; // read-only
        @chmod($destdir.DIRECTORY_SEPARATOR.'config.php',$filemode);

//        $adminacct = $this->get_wizard()->get_data('adminaccount');
        $root_url = $app->get_root_url();
        if( !endswith($root_url,'/') ) $root_url .= '/';
        $admin_url = $root_url.'admin';

//      $this->message(lang('msg_clearcache'));
        AdminUtils::clear_cached_files(); //irrelevant during installation?

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
        if( !$destdir ) throw new Exception(lang('error_internal',903));

        $this->connect_to_cmsms($destdir);

        // freshen content types
        ContentTypeOperations::get_instance()->RebuildStaticContentTypes();

        // write history
        audit('', 'System Freshened', 'All core files renewed');

        // security for the config file
        $filemode = get_server_permissions()[0]; // read-only
        @chmod(CONFIG_FILE_LOCATION,$filemode);

        // clear the caches
        $this->message(lang('msg_clearcache'));
        $this->clear_filecaches();
        SysDataCache::get_instance()->clear(); // clear all global-types' content
        SystemCache::get_instance()->clear();
        AdminUtils::clear_cached_files();

        // set the finished message
        if( $app->has_custom_destdir() ) {
            $msg = lang('finished_custom_freshen_msg');
        }
        else {
            include_once CONFIG_FILE_LOCATION;
            $aname = (!empty($config['admin_path'])) ? $config['admin_path'] : 'admin';

            $url = $app->get_root_url();
            $admin_url = $url;
            if( !endswith($url,'/') ) $admin_url .= '/';
            $admin_url .= $aname;
            $msg = lang('finished_freshen_msg',$url,$admin_url);
        }
        $this->set_block_html('bottom_nav',$msg);
    }

    /**
     * @param sring $destdir
     */
    private function connect_to_cmsms($destdir)
    {
        require_once $destdir.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
        AppState::add_state(AppState::STATE_INSTALL);
        $fp = $destdir.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';
        if( is_file($fp) ) {
            include_once $fp;
        }
        else {
            require_once $destdir.DIRECTORY_SEPARATOR.'include.php';
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
//            $lazy_fe = ($admin || (method_exists($modinst,'LazyLoadFrontend') && $modinst->LazyLoadFrontend())) ? 1 : 0;
//            $lazy_admin = (method_exists($modinst,'LazyLoadAdmin') && $modinst->LazyLoadAdmin()) ? 1 : 0;
            $rs = $db->Execute($stmt1, [
                $modname,$modinst->GetVersion(),$admin//,$lazy_fe,$lazy_admin
            ]);

            $deps = $modinst->GetDependencies();
            if( $deps ) {
                foreach( $deps as $depname => $depversion ) {
                    if( $depname && $depversion ) {
/*
stmt2: 'INSERT INTO '.CMS_DB_PREFIX.'module_deps
(parent_module,child_module,minimum_version,create_date)
VALUES (?,?,?,NOW())');
*/
                        $rs = $db->Execute($stmt2,[$depname,$modname,$depversion]);
                    }
                }
            }
//            $modops->generate_moduleinfo($modinst); //uses defined CMS_VERSION
        }
        else {
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
        if( !$destdir ) throw new Exception(lang('error_internal',905));

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

            $this->finish();
        }
        catch( Throwable $t ) {
            $this->error($t->GetMessage());
        }

        $app->cleanup();
    }
} // class
