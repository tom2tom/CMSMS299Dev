<?php

namespace cms_installer\wizard;

use cms_installer\utils;
use Exception;
use function cms_installer\lang;
use function cms_installer\smarty;
use function cms_installer\startswith;
use function cms_installer\translator;
use function cms_installer\get_app;

class wizard_step1 extends wizard_step
{
    protected function process()
    {
        if( isset($_POST['lang']) ) {
            $lang = trim(utils::clean_string($_POST['lang']));
            if( $lang ) translator()->set_selected_language($lang);
        }

        $app = get_app();
        if( isset($_POST['destdir']) ) {
            $dir = trim(utils::clean_string($_POST['destdir']));
            if( $dir) $app->set_destdir($dir);
        }

        if( isset($_POST['verbose']) ) $verbose = (int)$_POST['verbose'];
        else $verbose = 0;
        $app->set_config_val('verbose',$verbose);
//        $this->get_wizard()->set_data('verbose',$verbose);

        if( isset($_POST['next']) ) {
            // redirect to the next step.
            utils::redirect($this->get_wizard()->next_url());
        }
        return TRUE;
    }

   // Exclude most CMSMS directories from the dropdown for directory-choosing
    private function _is_valid_dir(string $dir) : bool
    {
        $bn = basename($dir);
        switch( $bn ) {
        case 'lang':
            if( is_file($dir.DIRECTORY_SEPARATOR.'en_US.php') ) return FALSE;
            break;

        case 'ext':
            if( is_file($dir.DIRECTORY_SEPARATOR.'fr_FR.php') || basename(dirname($dir)) == 'lang' ) return FALSE;
            break;

        case 'plugins':
            if( is_file($dir.DIRECTORY_SEPARATOR.'function.cms_selflink.php') ) return FALSE;
            break;
/*
        case 'install':
            if( is_dir($dir.DIRECTORY_SEPARATOR.'schemas') ) return FALSE;
            break;
*/
        case 'tmp':
            if( is_dir($dir.DIRECTORY_SEPARATOR.'cache') ) return FALSE;
            break;

        case 'phar_installer':
        case 'installer':
        case 'doc':
        case 'build':
        case 'admin':
        case 'module_custom':
        case 'out':
            return FALSE;

        case 'lib':
            if( is_dir($dir.DIRECTORY_SEPARATOR.'modules') ) return FALSE;
            break;

        case 'assets':
            if( is_file($dir.DIRECTORY_SEPARATOR.'config.ini') ) return FALSE;
            break;

        case 'modules':
            if( is_dir($dir.DIRECTORY_SEPARATOR.'ModuleManager') || is_dir($dir.DIRECTORY_SEPARATOR.'CmsJobManager') ) return FALSE;
            break;

        case 'data':
            if( is_file($dir.DIRECTORY_SEPARATOR.'data.tar.gz') ) return FALSE;
            break;
        }
        return TRUE;
    }

    private function _get_annotation(string $dir)
    {
        if( !is_dir($dir) || !is_readable($dir) ) return;
        $bn = basename($dir);
        if( $bn != 'lib' && is_file($dir.DIRECTORY_SEPARATOR.'version.php' ) ) {
            @include $dir.DIRECTORY_SEPARATOR.'version.php'; // defines in this file can throw notices
            return 'CMSMS '.CMS_VERSION;
        } elseif( is_file($dir.DIRECTORY_SEPARATOR.'lib/version.php') ) {
            @include $dir.DIRECTORY_SEPARATOR.'lib/version.php'; // defines in this file can throw notices
            return 'CMSMS '.CMS_VERSION;
        }

        if( is_dir($dir.DIRECTORY_SEPARATOR.'assets') && is_file($dir.DIRECTORY_SEPARATOR.'lib/classes/class.installer_base.php') ) {
            return 'CMSMS installation assistant';
        }
    }

    private function _find_dirs(string $start, int $depth = 0)
    {
        if( !is_readable( $start ) ) return;
        $dh = opendir($start);
        if( !$dh ) return;
        $out = [];
        while( ($file = readdir($dh)) !== FALSE ) {
            if( $file == '.' || $file == '..' ) continue;
            if( startswith($file,'.') || startswith($file,'_') ) continue;
            $dn = $start.DIRECTORY_SEPARATOR.$file;  // cuz windows blows, and windoze guys are whiners :)
            if( !@is_readable($dn) ) continue;
            if( !@is_dir($dn) ) continue;
            if( !$this->_is_valid_dir( $dn ) ) continue;
            $str = $dn;
            $ann = $this->_get_annotation( $dn );
            if( $ann ) $str .= " ($ann)";

            $out[$dn] = $str;
            if( $depth < 3 ) {
                $tmp = $this->_find_dirs($dn,$depth + 1); // recursion
                if( $tmp ) $out = array_merge($out,$tmp);
            }
        }
        if( $out ) return $out;
    }

    private function get_valid_install_dirs() : array
    {
        $start = get_app()->get_rootdir();
        $parent = realpath(dirname($start)); //we're working in a subdir of the main site

        $out = [];
        if( $this->_is_valid_dir($parent) ) $out[$parent] = $parent;
        $tmp = $this->_find_dirs($parent);
        if( $tmp ) $out = array_merge($out,$tmp);
        asort($out);
        return $out;
    }

    protected function display()
    {
        parent::display();

        // get the list of directories we can install to.
        $smarty = smarty();
        $app = get_app();
        $config = $app->get_config();
        if( !$app->in_phar() ) {
            // get the list of directories we can install to
            $dirlist = $this->get_valid_install_dirs();
            if( !$dirlist ) throw new Exception('No possible installation directories found.  This could be a permissions issue');
            if( count($dirlist) > 1 ) {
                $smarty->assign('dirlist',$dirlist);

                $custom_destdir = $app->has_custom_destdir();
                $smarty->assign('custom_destdir',$custom_destdir);
                $raw = $config['dest'] ?? null;
                $v = ($raw) ? trim($raw) : $app->get_destdir();
                $smarty->assign('destdir',$v);
            } else {
                $app->set_destdir(reset($dirlist));
            }
        }
        $raw = $config['verbose'] ?? 0;
//        $v = ($raw === null) ? $this->get_wizard()->get_data('verbose',0) : (int)$raw;
        $smarty->assign('verbose',(int)$raw);
        $tr = translator();
        $arr = $tr->get_language_list($tr->get_allowed_languages());
        asort($arr,SORT_LOCALE_STRING);
        $smarty->assign('languages',$arr);
        $raw = $config['lang'] ?? null;
        $v = ($raw) ? trim($raw) : $tr->get_current_language();
        $smarty->assign('curlang',$v);
        $smarty->assign('yesno',[0=>lang('no'),1=>lang('yes')]);
        $smarty->display('wizard_step1.tpl');

        $this->finish();
    }

} // class
