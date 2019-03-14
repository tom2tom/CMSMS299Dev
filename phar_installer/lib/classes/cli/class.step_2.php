<?php

namespace cms_installer\cli;

use cms_installer\cli\cli_step;
use console;
use Exception;
use function cms_installer\lang;
use function cms_installer\translator;
use function cms_installer\get_app;

class step_2 extends cli_step
{
    protected function get_cmsms_info()
    {
        $dir = $this->app()->get_destdir();

        //if( !is_dir($dir.'/lib/modules') ) return;
        if( !is_file($dir.'/version.php') && !is_file("$dir/lib/version.php") ) return;
        if( !is_file($dir.'/include.php') && !is_file("$dir/lib/include.php") ) return;
        if( !is_file($dir.'/config.php') ) return;
        if( !is_file($dir.'/lib/misc.functions.php') ) return;

        $info = [];
        if( is_file("$dir/lib/version.php") ) {
            @include_once "$dir/lib/version.php";
            $info['mtime'] = filemtime($dir.'/lib/version.php');
        } else {
            @include_once $dir.'/version.php';
            $info['mtime'] = filemtime($dir.'/version.php');
        }
        $info['version'] = CMS_VERSION;
        $info['version_name'] = CMS_VERSION_NAME;
        $info['schema_version'] = CMS_SCHEMA_VERSION;
        $info['config_file'] = $dir.'/config.php';

        $app = get_app();
        $app_config = $app->get_config();
        if( !isset($app_config['min_upgrade_version']) ) throw new Exception(lang('error_missingconfigvar','min_upgrade_version'));
        if( version_compare($info['version'],$app_config['min_upgrade_version']) < 0 ) $info['error_status'] = 'too_old';
        if( version_compare($info['version'],$app->get_dest_version()) == 0 ) $info['error_status'] = 'same_ver';
        if( version_compare($info['version'],$app->get_dest_version()) > 0 ) $info['error_status'] = 'too_new';

        $fn = $dir.'/config.php';
        include_once $fn;
        $info['config'] = $config;

        return $info;
    }

    public function run()
    {
        // do basic tests, determine the operation
        $info = $this->get_cmsms_info();
        $config = $this->app()->get_config();

        if( $this->app()->is_interactive() ) {
            $op = 'install';
            $error_status = null;
            if( $info ) {
                $error_status = ( isset($info['error_status']) ) ? $info['error_status'] : null;
                if( $error_status == 'same_ver' ) {
                    $op = 'freshen';
                } else if( !$error_status ) {
                    $op = 'upgrade';
                }
            }

            // show info about cmsms installed
            // ask to continue, if interactive
            $mylang = translator()->get_current_language();
            $console = new console();
            $console->clear();
            $console->show_centered(lang('cli_welcome'), 'bold+underline' )->lf();
            $console->show_centered('v'.$config['installer_version'])->lf();
            $console->show_centered(lang('cli_cmsver', $this->app()->get_dest_version() ), 'bold' )->lf()->show_centered('----')->lf()->lf();
            switch( $op ) {
            case 'install':
                $console->show(lang('destination_directory'), 'bold' )->show( $this->app()->get_destdir() )->lf();
                $console->show(lang('step2_nocmsms'), 'green' )->lf();
                $ans = $console->ask_bool('> '.lang('step2_confirminstall').' [y/N]: ');
                if( !$ans ) throw new user_aborted();
                $this->app()->set_op( 'install' );
                break;
            case 'upgrade':
                $console->show(lang('destination_directory').': ', 'bold' )->show( $this->app()->get_destdir() )->lf();
                $console->show(lang('step2_hdr_upgradeinfo').': ', 'bold' )->show( sprintf( '%s (%s)', $info['version'], $info['version_name']) )->lf();
                $console->show(lang('step2_installdate').': ', 'bold' )->show( strftime('%x',$info['mtime']) )->lf();
                $console->show(lang('step2_cmsmsfound'), 'cyan' )->lf();
                $ans = $console->ask_bool('> '.lang('step2_confirmupgrade').' [y/N]: ');
                if( !$ans ) throw new user_aborted();
                // set our operation
                $this->app()->set_op( 'upgrade' );
                // copy the info['config'] stuff into $app->config so that we can use it for defaults later when prompting.
                $this->app()->merge_options( $info['config'] );
                break;
            case 'freshen':
                die('incomplete at '.__FILE__.'::'.__LINE__."\n");
            }
        } else {
            die('incomplete at '.__FILE__.'::'.__LINE__."\n");
        }
    }
} // class
