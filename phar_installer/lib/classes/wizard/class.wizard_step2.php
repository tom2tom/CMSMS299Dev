<?php
namespace cms_installer\wizard;

use DateTime;
use DateTimeZone;
use Exception;
use function cms_installer\specialize;
use function cms_installer\get_app;
use function cms_installer\get_upgrade_changelog;
use function cms_installer\get_upgrade_readme;
use function cms_installer\get_upgrade_versions;
use function cms_installer\lang;
use function cms_installer\redirect;
use function cms_installer\smarty;

class wizard_step2 extends wizard_step
{
    protected function process()
    {
        if (isset($_REQUEST['install'])) {
            $this->get_wizard()->set_data('action', 'install');
        } elseif (isset($_REQUEST['upgrade'])) {
            $this->get_wizard()->set_data('action', 'upgrade');
        } elseif (isset($_REQUEST['freshen'])) {
            $this->get_wizard()->set_data('action', 'freshen');
        } else {
            throw new Exception(lang('error_internal', 200));
        }
        redirect($this->get_wizard()->next_url());
    }

    protected function display()
    {
        // search for installs of CMSMS
        parent::display();
        $app = get_app();

        $rpwd = $app->get_destdir();
        $info = $this->get_cmsms_info($rpwd); //null when installing
        $wizard = $this->get_wizard();
        $smarty = smarty();
        $smarty->assign('pwd', $rpwd);
        $app_config = $app->get_config();
        $smarty->assign('nofiles', $app_config['nofiles']);

        if ($info) {
            // we're doing an upgrade|freshen
            $wizard->set_data('version_info', $info); //store data in session
            $smarty->assign('cmsms_info', $info);
            if (!isset($info['error_status']) || $info['error_status'] != 'same_ver') {
                $versions = get_upgrade_versions();
                $out = [];
                foreach ($versions as $version) {
                    if (version_compare($version, $info['version']) < 1) {
                        continue;
                    }
                    $readme = get_upgrade_readme($version);
                    $changelog = get_upgrade_changelog($version);
                    if ($readme || $changelog) {
                        $out[$version] = ['readme' => specialize($readme), 'changelog' => specialize($changelog)];
                    }
                }
                $smarty->assign('upgrade_info', $out);
            }
        } else {
            // looks like a new install
            // double-check for the phar stuff
            if (is_file($rpwd.DIRECTORY_SEPARATOR.'index.php') && is_dir($rpwd.DIRECTORY_SEPARATOR.'lib') && is_file($rpwd.DIRECTORY_SEPARATOR.'lib/classes/class.installer_base.php')) {
                // should never happen except if you're working on this project.
                throw new Exception(lang('error_invalid_directory'));
            }

            $wizard->clear_data('version_info'); //actually does nothing (no config info available yet when installing)

            $empty_dir = $this->is_dir_empty($rpwd, $app->get_phar());
            $existing_files = $this->list_files($rpwd);
            $smarty->assign('install_empty_dir', $empty_dir)
             ->assign('existing_files', $existing_files)
             ->assign('retry_url', $_SERVER['REQUEST_URI']); // TODO only if problem occurred
        }

        $smarty->display('wizard_step2.tpl');
        $this->finish();
    }

    /**
     * Collect data from config.php file, if any, plus some related params
     * @param string $dir filepath of site root directory
     * @return array maybe empty
     * @throws Exception
     */
    private function get_cmsms_info(string $dir)
    {
        if (!$dir) {
            return [];
        }

        $fn = $dir.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'config.php';
        if (!is_file($fn)) {
            $fn = $dir.DIRECTORY_SEPARATOR.'config.php';
            if (!is_file($fn)) {
                return [];
            }
        }
        $app = get_app();
        $app->set_config_val('config_file', $fn);

        $config = []; // populate this by inclusion
        include_once $fn;

        $aname = (!empty($config['admin_path'])) ? $config['admin_path'] : 'admin';
//      if( !is_file($dir.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'moduleinterface.php') ) return [];
        if (!is_file($dir.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php')) {
            return [];
        }
        $fv = $dir.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'version.php';
        if (!is_file($fv)) {
            return [];
        }
        $t = filemtime($fv);
        if (!defined('CMS_VERSION')) {
            include $fv; // see also installer_base::init()
            if (!defined('CMS_VERSION')) {
                define('CMS_VERSION', $CMS_VERSION);
                define('CMS_VERSION_NAME', $CMS_VERSION_NAME);
                define('CMS_SCHEMA_VERSION', $CMS_SCHEMA_VERSION);
            }
        }

        if ($aname != 'admin') {
            $str = trim($aname, ' \/');
            $app->set_config_val('admin_path', strtr($str, '\\', '/'));
        }
        if (!empty($config['assets_path']) && $config['assets_path'] != 'assets') {
            $str = trim($config['assets_path'], ' \/');
            $app->set_config_val('assets_path', strtr($str, '\\', '/'));
        }
        if (!empty($config['usertags_path'])) {
            $str = strtr(trim($config['usertags_path'], ' \/'), '\\', '/');
            if (!($str == 'user_plugins' || $str == 'assets/user_plugins')) {
                $app->set_config_val('usertags_path', $str);
            }
        }

        $info = [
            'config_file' => $fn,
            'config' => $config,
            'version' => $CMS_VERSION,
            'version_name' => $CMS_VERSION_NAME,
            'schema_version' => $CMS_SCHEMA_VERSION,
        ];
        $str = (!empty($config['timezone'])) ? $config['timezone'] : 'UTC';
        $dt = new DateTime('@0', new DateTimeZone($str));
        $dt->setTimestamp($t);
        $info['mdate'] = $dt->format('j F Y');
        $info['mtime'] = $t;

        $app_config = $app->get_config();
        if (!isset($app_config['min_upgrade_version'])) {
            throw new Exception(lang('error_missingconfigvar', 'min_upgrade_version'));
        }
        if (version_compare($CMS_VERSION, $app_config['min_upgrade_version']) < 0) {
            $info['error_status'] = 'too_old';
        }
        if (version_compare($CMS_VERSION, $app->get_dest_version()) == 0) {
            $info['error_status'] = 'same_ver';
        }
        if (version_compare($CMS_VERSION, $app->get_dest_version()) > 0) {
            $info['error_status'] = 'too_new';
        }

        return $info;
    }

    /**
     * Determine whether a specified folder is absent or empty or 'sufficiently' so
     * @internal
     *
     * @param string $dir filesystem path of site-root folder, or a descendant of that
     * @param string $phar_path like 'phar://path/to/this.phar', or '' if not in a phar
     * @return bool indicating acceptability
     */
    private function is_dir_empty(string $dir, string $phar_path) : bool
    {
        if (!$dir) {
            return false;
        }
        if (!is_dir($dir)) {
            return false;
        }
        $files = glob($dir.DIRECTORY_SEPARATOR.'*'); // filesystem path
        if (!$files) {
            return true;
        }
        if (count($files) > 3) {
            return false;
        }
        if ($phar_path) {
            $phar_bn = strtolower(basename($phar_path));
        }
        // trivial check for index.html
        foreach ($files as $file) {
            $bn = strtolower(basename($file));
            if (fnmatch('index.htm?', $bn)) {
                continue;
            } // this is ok
            if (fnmatch('readme*.txt', $bn)) {
                continue;
            } // this is ok
            if ($phar_path && $phar_bn == $bn) {
                continue;
            } // this is ok
            // found a not-ok file
            return false;
        }
        return true;
    }

    /**
     * Get names of some/all files in a specified folder
     * @internal
     *
     * @param string $dir filesystem path of site-root folder, or a descendant of that
     * @param int $n optional no. of files in $dir 1 .. 100 Default 5
     * @return array maybe empty
     */
    private function list_files(string $dir, int $n = 5)
    {
        if (!$dir) {
            return [];
        }
        if (!is_dir($dir)) {
            return [];
        }
        $files = glob($dir.DIRECTORY_SEPARATOR.'*'); // filesystem path
        $fc = count($files);
        $n = max(1, min(100, $n));
        $arr = [];
        for ($i = 0; $i < $n && $i < $fc; ++$i) {
            $fn = basename($files[$i]);
            if (!($fn == '.' || $fn == '..')) {
                $arr[] = $fn;
            } else {
                ++$n;
            }
        }
        return $arr;
    }
} // class
