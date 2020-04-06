<?php

namespace cms_installer\wizard;

use cms_installer\utils;
use DateTime;
use DateTimeZone;
use Exception;
use function cms_installer\lang;
use function cms_installer\smarty;
use function cms_installer\get_app;

class wizard_step2 extends wizard_step
{
    private function get_cmsms_info(string $dir)
    {
        if( !$dir ) return;

        $fn = $dir.DIRECTORY_SEPARATOR.'config.php';
        if( !is_file($fn) ) return;

        include_once $fn;

        $aname = ( !empty($config['admin_dir']) ) ? $config['admin_dir'] : 'admin';
//      if( !is_file($dir.DIRECTORY_SEPARATOR.$aname.DIRECTORY_SEPARATOR.'moduleinterface.php') ) return;
//      if( !is_dir($dir.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'modules') ) return;
        if( !is_file($dir.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php') ) return;
        $fv = $dir.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'version.php';
        if( !is_file($fv) ) return;
        $t = filemtime($fv);
        if( !defined('CMS_VERSION') ) {
            include $fv; // see also installer_base::init()
        }

        $app = get_app();
        if( $aname != 'admin' ) $app->set_config_val('admindir', $aname);
        if( !empty($config['assets_dir']) && $config['assets_dir'] != 'assets' ) $app->set_config_val('assetsdir', $config['assets_dir']);
        if( !empty($config['simpletags_dir']) && $config['simpletags_dir'] != 'simple_plugins' ) $app->set_config_val('pluginsdir', $config['simpletags_dir']);

        $info = [];
        $info['config_file'] = $fn;
        $s = ( !empty($config['timezone']) ) ? $config['timezone'] : 'UTC';
        $dt = new DateTime(null, new DateTimeZone($s));
        $dt->setTimestamp($t);
        $info['mdate'] = $dt->format('j F Y');
        $info['mtime'] = $t;
        $info['version'] = CMS_VERSION;
        $info['version_name'] = CMS_VERSION_NAME;
        $info['schema_version'] = CMS_SCHEMA_VERSION;

        $app_config = $app->get_config();
        if( !isset($app_config['min_upgrade_version']) ) throw new Exception(lang('error_missingconfigvar','min_upgrade_version'));
        if( version_compare(CMS_VERSION,$app_config['min_upgrade_version']) < 0 ) $info['error_status'] = 'too_old';
        if( version_compare(CMS_VERSION,$app->get_dest_version()) == 0 ) $info['error_status'] = 'same_ver';
        if( version_compare(CMS_VERSION,$app->get_dest_version()) > 0 ) $info['error_status'] = 'too_new';

        $info['config'] = $config;
        return $info;
    }

    private function is_dir_empty(string $dir, string $phar_url) : bool
    {
        if( !$dir ) return FALSE;
        if( !is_dir($dir) ) return FALSE;
        $files = glob($dir.DIRECTORY_SEPARATOR.'*');
        if( !$files ) return TRUE;
        if( count($files) > 3 ) return FALSE;
        if( $phar_url ) {
            $phar_bn = basename( $phar_url );
        }
        // trivial check for index.html
        foreach( $files as $file ) {
            $bn = strtolower(basename($file));
            if( fnmatch('index.htm?',$bn) ) continue; // this is ok
            if( fnmatch('readme*.txt',$bn) ) continue; // this is ok
            if( $phar_url ) {
                if( fnmatch($phar_bn, $bn) ) continue; // this is ok
            }
            // found a not-ok file
            return FALSE;
        }
        return TRUE;
    }

    private function list_files(string $dir, int $n = 5)
    {
        if( !$dir ) return;
        if( !is_dir($dir) ) return;
        $files = glob($dir.DIRECTORY_SEPARATOR.'*');
        $fc = count($files);
        $n = max(1,min(100,$n));
        $arr = [];
        for( $i = 0; $i < $n && $i < $fc; ++$i ) {
            $fn = basename($files[$i]);
            if( !($fn == '.' || $fn == '..') ) {
                $arr[] = $fn;
            }
            else {
                ++$n;
            }
        }
        return $arr;
    }

    protected function process()
    {
        if( isset($_REQUEST['install']) ) {
            $this->get_wizard()->set_data('action','install');
        }
        else if( isset($_REQUEST['upgrade']) ) {
            $this->get_wizard()->set_data('action','upgrade');
        }
        else if( isset($_REQUEST['freshen']) ) {
            $this->get_wizard()->set_data('action','freshen');
        }
        else {
            throw new Exception(lang('error_internal',200));
        }
        utils::redirect($this->get_wizard()->next_url());
    }

    protected function display()
    {
        // search for installs of CMSMS.
        parent::display();
        $app = get_app();

        $rpwd = $app->get_destdir();
        $info = $this->get_cmsms_info($rpwd); //null when installing
        $wizard = $this->get_wizard();
        $smarty = smarty();
        $smarty->assign('pwd',$rpwd);
        $app_config = $app->get_config();
        $smarty->assign('nofiles',$app_config['nofiles']);

        if( $info ) {
            // we're doing an upgrade/freshen
            $wizard->set_data('version_info',$info); //store data in session
            $smarty->assign('cmsms_info',$info);
            if( !isset($info['error_status']) || $info['error_status'] != 'same_ver' ) {
                $versions = utils::get_upgrade_versions();
                $out = [];
                foreach( $versions as $version ) {
                    if( version_compare($version,$info['version']) < 1 ) continue;
                    $readme = utils::get_upgrade_readme($version);
                    $changelog = utils::get_upgrade_changelog($version);
                    if( $readme || $changelog ) $out[$version] = ['readme'=>$readme,'changelog'=>$changelog];
                }
                $smarty->assign('upgrade_info',$out);
            }
        }
        else {
            // looks like a new install
            // double-check for the phar stuff.
            if( is_file($rpwd.DIRECTORY_SEPARATOR.'index.php') && is_dir($rpwd.DIRECTORY_SEPARATOR.'assets') && is_dir($rpwd.DIRECTORY_SEPARATOR.'lib') && is_file($rpwd.DIRECTORY_SEPARATOR.'lib/classes/class.installer_base.php') ) {
                // should never happen except if you're working on this project.
                throw new Exception(lang('error_invalid_directory'));
            }

            $wizard->clear_data('version_info'); //actually does nothing (no config info available yet when installing)

            $empty_dir = $this->is_dir_empty($rpwd,$app->get_phar());
            $existing_files = $this->list_files($rpwd);
            $smarty->assign('install_empty_dir',$empty_dir);
            $smarty->assign('existing_files',$existing_files);
        }

        $smarty->assign('retry_url',$_SERVER['REQUEST_URI']);
        $smarty->display('wizard_step2.tpl');
        $this->finish();
    }
} // class
