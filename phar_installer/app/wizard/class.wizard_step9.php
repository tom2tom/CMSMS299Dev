<?php

namespace cms_autoinstaller;
use \__appbase;

class wizard_step9 extends \cms_autoinstaller\wizard_step
{
    protected function process()
    {
        // nothing here
    }

    private function do_upgrade($version_info)
    {
        $app = \__appbase\get_app();
        $destdir = $app->get_destdir();
        if( !$destdir ) throw new \Exception(\__appbase\lang('error_internal',900));

        $this->connect_to_cmsms($destdir);

        // upgrade modules
        $this->message(\__appbase\lang('msg_upgrademodules'));
        $modops = \ModuleOperations::get_instance();
        $allmodules = $modops->FindAllModules();
        foreach( $allmodules as $name ) {
            // we force all system modules to be loaded, if it's a system module
            // and needs upgrade, then it should automagically upgrade.
            if( $modops->IsSystemModule($name) ) {
                $this->verbose(\__appbase\lang('msg_upgrade_module',$name));
                $module = $modops->get_module_instance($name,'',TRUE);
                if( !is_object($module) ) {
                    $this->error("FATAL ERROR: could not load module {$name} for upgrade");
                }
            }
        }

        // clear the cache
        \cmsms()->clear_cached_files();
        $this->message(\__appbase\lang('msg_clearedcache'));

        // write protect config.php
        @chmod("$destdir/config.php",0440);

        // todo: write history

        // set the finished message.
        $app = \__appbase\get_app();
        if( $app->has_custom_destdir() || !$app->in_phar() ) {
            $this->set_block_html('bottom_nav',\__appbase\lang('finished_custom_upgrade_msg'));
        }
        else {
            $url = $app->get_root_url();
            $admin_url = $url;
            if( !endswith($url,'/') ) $admin_url .= '/';
            $admin_url .= 'admin';
            $this->set_block_html('bottom_nav',\__appbase\lang('finished_upgrade_msg', $url, $admin_url));
        }
    }

    public function do_install()
    {
        $app = \__appbase\get_app();
        $destdir = $app->get_destdir();
        if( !$destdir ) throw new \Exception(\__appbase\lang('error_internal',901));
/* done in step 8
        // create tmp directories
        $this->message(\__appbase\lang('install_createtmpdirs'));
        @mkdir($destdir.'/tmp/cache',0771,TRUE);
        @mkdir($destdir.'/tmp/templates_c',0771,TRUE);
*/
        // install modules
        $this->message(\__appbase\lang('install_modules'));
        $this->connect_to_cmsms($destdir);
        $modops = \cmsms()->GetModuleOperations();
        $db = \cmsms()->GetDb();
        $stmt1 = $db->Prepare('INSERT INTO '.CMS_DB_PREFIX.'modules
(module_name,version,status,admin_only,active,allow_fe_lazyload,allow_admin_lazyload)
VALUES (?,?,\'installed\',?,1,?,?)');
        $stmt2 = $db->Prepare('INSERT INTO '.CMS_DB_PREFIX.'module_deps
(parent_module,child_module,minimum_version,create_date,modified_date)
VALUES (?,?,?,NOW(),NOW())');

        $patn = $destdir.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.'*';
        $dirs = glob($patn, GLOB_NOSORT | GLOB_NOESCAPE | GLOB_ONLYDIR);
        $patn = $destdir.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.'*';
        $dirs = array_merge($dirs, glob($patn, GLOB_NOSORT | GLOB_NOESCAPE | GLOB_ONLYDIR));

        foreach ($dirs as $bp) {
            $modname = basename($bp);
            $fp = $bp.DIRECTORY_SEPARATOR.$modname.'.module.php';
            if (file_exists($fp)) {
                require_once $fp;
                $modname = '\\'.$modname;
                $module_obj = new $modname();
                if ($module_obj) {
                    $this->verbose(\__appbase\lang('install_module', $modname));
                    $this->mod_install($modops, $module_obj, $db, $stmt1, $stmt2);
                }
            }
        }

        // write-protect config.php
        @chmod("$destdir/config.php",0440);

        $adminacct = $this->get_wizard()->get_data('adminaccount');
        $root_url = $app->get_root_url();
        if( !endswith($root_url,'/') ) $root_url .= '/';
        $admin_url = $root_url.'admin';
/*
        if( is_array($adminacct) && isset($adminacct['emailaccountinfo']) && $adminacct['emailaccountinfo'] && isset($adminacct['emailaddr']) && $adminacct['emailaddr'] ) {
            try {
                $this->message(\__appbase\lang('send_admin_email'));
                $mailer = new \cms_mailer();
                $mailer->AddAddress($adminacct['emailaddr']);
                $mailer->SetSubject(\__appbase\lang('email_accountinfo_subject'));
                $body = null;
                if( $app->in_phar() ) {
                    $body = \__appbase\lang('email_accountinfo_message',
                                            $adminacct['username'],$adminacct['password'],
                                            $destdir, $root_url);
                }
                else {
                    $body = \__appbase\lang('email_accountinfo_message_exp',
                                            $adminacct['username'],$adminacct['password'],
                                            $destdir);
                }
                $body = html_entity_decode($body, ENT_QUOTES);
                $mailer->SetBody($body);
                $mailer->Send();
            }
            catch( \Exception $e ) {
                $this->error(\__appbase\lang('error_sendingmail').': '.$e->GetMessage());
            }
        }
*/
        // todo: set initial preferences.

        // todo: write history

        \cmsms()->clear_cached_files();
        $this->message(\__appbase\lang('msg_clearedcache'));

        // set the finished message.
        if( !$root_url || !$app->in_phar() ) {
            // find the common part of the SCRIPT_FILENAME and the destdir
            // /var/www/phar_installer/index.php
            // /var/www/foo
            $this->set_block_html('bottom_nav',\__appbase\lang('finished_custom_install_msg'));
        }
        else {
            if( endswith($root_url,'/') ) $admin_url = $root_url.'admin';
            $this->set_block_html('bottom_nav',\__appbase\lang('finished_install_msg',$root_url,$admin_url));
        }
    }

    private function do_freshen()
    {
        // create tmp directories
        $app = \__appbase\get_app();
        $destdir = \__appbase\get_app()->get_destdir();
        if( !$destdir ) throw new \Exception(\__appbase\lang('error_internal',901));
        $this->message(\__appbase\lang('install_createtmpdirs'));
        @mkdir($destdir.'/tmp/cache',0771,TRUE);
        @mkdir($destdir.'/tmp/templates_c',0771,TRUE);

        // write protect config.php
        @chmod("$destdir/config.php",0440);

        // clear the cache
        $this->connect_to_cmsms($destdir);
        \cmsms()->clear_cached_files();
        $this->message(\__appbase\lang('msg_clearedcache'));

        // todo: write history

        // set the finished message.
        if( $app->has_custom_destdir() ) {
            $this->set_block_html('bottom_nav',\__appbase\lang('finished_custom_freshen_msg'));
        }
        else {
            $url = $app->get_root_url();
            $admin_url = $url;
            if( !endswith($url,'/') ) $admin_url .= '/';
            $admin_url .= 'admin';
            $this->set_block_html('bottom_nav',\__appbase\lang('finished_freshen_msg', $url, $admin_url ));
        }
    }

    private function connect_to_cmsms($destdir)
    {
        // this loads the standard CMSMS stuff, except smarty cuz it's already done.
        // we do this here because both upgrade and install stuff needs it.
        global $CMS_INSTALL_PAGE, $DONT_LOAD_SMARTY, $CMS_VERSION, $CMS_PHAR_INSTALLER;
        $CMS_INSTALL_PAGE = 1;
        $CMS_PHAR_INSTALLER = 1;
        $DONT_LOAD_SMARTY = 1;
        $CMS_VERSION = $this->get_wizard()->get_data('destversion');
        if( is_file("$destdir/include.php") ) {
            include_once($destdir.'/include.php');
        } else {
            include_once($destdir.'/lib/include.php');
        }

        if( !defined('CMS_VERSION') ) {
            define('CMS_VERSION',$CMS_VERSION);
        }
        // we do this here, because the config.php class may not set the define when in an installer.
        if( !defined('CMS_DB_PREFIX') ) {
            $config = \cms_config::get_instance();
            define('CMS_DB_PREFIX',$config['db_prefix']);
        }
    }

    private function mod_install(\ModuleOperations &$modops, \CmsModule &$module_obj, $db, $stmt1, $stmt2)
    {
        $result = $module_obj->Install();
        if( !isset($result) || $result === FALSE ) {
            // a successful installation
            $modname = $module_obj->GetName();
            $admin = ($module_obj->IsAdminOnly()) ? 1 : 0;
            $lazy_fe = ($admin || (method_exists($module_obj,'LazyLoadFrontend') && $module_obj->LazyLoadFrontend())) ? 1 : 0;
            $lazy_admin = (method_exists($module_obj,'LazyLoadAdmin') && $module_obj->LazyLoadAdmin()) ? 1 : 0;
/*
stmt1: 'INSERT INTO '.CMS_DB_PREFIX.'modules
(module_name,version,status,admin_only,active,allow_fe_lazyload,allow_admin_lazyload)
VALUES (?,?,\'installed\',?,1,?,?)');
 */
            $rs = $db->Execute($stmt1, [
                $modname,$module_obj->GetVersion(),$admin,$lazy_fe,$lazy_admin
            ]);

            $deps = $module_obj->GetDependencies();
            if( is_array($deps) && count($deps) ) {
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
            $modops->generate_moduleinfo($module_obj); //uses defined CMS_VERSION
        } else {
            throw new \Exception($result);
        }
    }

    protected function display()
    {
        $app = \__appbase\get_app();
        $smarty = \__appbase\smarty();

        // display the template right off the bat.
        parent::display();
        $smarty->assign('back_url',$this->get_wizard()->prev_url());
        $smarty->display('wizard_step9.tpl');
        $destdir = $app->get_destdir();
        if( !$destdir ) throw new \Exception(\__appbase\lang('error_internal',903));


        // here, we do either the upgrade, or the install stuff.
        try {
            $action = $this->get_wizard()->get_data('action');
            $tmp = $this->get_wizard()->get_data('version_info');
            if( $action == 'upgrade' && is_array($tmp) && count($tmp) ) {
                $this->do_upgrade($tmp);
            }
            else if( $action == 'freshen' ) {
                $this->do_freshen();
            }
            else if( $action == 'install' ) {
                $this->do_install();
            }
            else {
                throw new \Exception(\__appbase\lang('error_internal',910));
            }

            // clear the session.
            $sess = \__appbase\session::get();
            $sess->clear();

            $this->finish();
        }
        catch( \Exception $e ) {
            $this->error($e->GetMessage());
        }

        $app->cleanup();
    }
} // class

