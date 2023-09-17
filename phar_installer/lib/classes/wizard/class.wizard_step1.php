<?php
namespace cms_installer\wizard;

use cms_installer\nlstools;
use cms_installer\wizard\wizard_step;
use Exception;
use const cms_installer\ICMSSAN_NONPRINT;
use const cms_installer\ICMSSAN_PATH;
use function cms_installer\get_app;
use function cms_installer\joinpath;
use function cms_installer\lang;
use function cms_installer\redirect;
use function cms_installer\sanitizeVal;
use function cms_installer\smarty;
use function cms_installer\startswith;
use function cms_installer\translator;

class wizard_step1 extends wizard_step
{
    protected function process()
    {
        if (isset($_POST['lang'])) {
            // see format info at https://www.unicode.org/reports/tr35
            $lang = sanitizeVal($_POST['lang'], ICMSSAN_NONPRINT);
            if ($lang) {
                translator()->set_selected_language($lang);
                $obj = (new nlstools())->find($lang);
                if ($obj) {
                    $v = $obj->direction();
                    $this->set_langdir($v);
                }
            }
        }

        $app = get_app();
        if (isset($_POST['destdir'])) {
            //this is not a new installation
            $dir = sanitizeVal($_POST['destdir'], ICMSSAN_PATH);
            if ($dir) {
                if (is_dir($dir)) {
                    $app->set_destdir($dir);
                } else {
                    throw new Exception('Invalidid sources directory');
                }
            }
        }

        $verbose = (int)(!empty($_POST['verbose']));
        $app->set_config_val('verbose', $verbose);
//      $this->get_wizard()->set_data('verbose', $verbose);

        if (isset($_POST['next'])) {
            // redirect to the next step.
            redirect($this->get_wizard()->next_url());
        }
        return true;
    }

    protected function display()
    {
        parent::display();

        // get a list of directories we could install into.
        $smarty = smarty();
        $app = get_app();
        $config = $app->get_config();
        if (!$app->in_phar()) {
            // get the list of directories we can install to
            $dirlist = $this->get_valid_install_dirs();
            if (!$dirlist) {
                throw new Exception('No possible installation directories found.  This could be a permissions issue');
            }
            if (count($dirlist) > 1) {
                $smarty->assign('dirlist', $dirlist); // assume specialize() not needed

                $custom_destdir = $app->has_custom_destdir();
                $smarty->assign('custom_destdir', $custom_destdir);
                $raw = $config['dest'] ?? null;
                $v = ($raw) ? trim($raw) : $app->get_destdir();
                $smarty->assign('destdir', $v);
            } else {
                $app->set_destdir(reset($dirlist));
            }
        }
        $raw = $config['verbose'] ?? 0;
//      $v = ($raw === NULL) ? $this->get_wizard()->get_data('verbose', 0) : (int)$raw;
        $smarty->assign('verbose', (int)$raw);
//      $smarty->assign('checksum', $this->generate_checksum(); TODO
        $tr = translator();
        $arr = $tr->get_language_list($tr->get_allowed_languages());
        asort($arr, SORT_LOCALE_STRING);
        $v = $tr->get_current_language();
        $smarty->assign('languages', $arr)
         ->assign('curlang', $v)
         ->assign('yesno', [0 => lang('no'), 1 => lang('yes')])
         ->display('wizard_step1.tpl');

        $this->finish();
    }

    // Exclude most CMSMS directories from the dropdown for directory-choosing
    private function _is_valid_dir(string $dir): bool
    {
        $bn = basename($dir);
        switch ($bn) {
        case 'lang':
            if (is_file($dir.DIRECTORY_SEPARATOR.'en_US.php')) {
                return false;
            }
            break;

        case 'ext':
            if (is_file($dir.DIRECTORY_SEPARATOR.'fr_FR.php') || basename(dirname($dir)) == 'lang') {
                return false;
            }
            break;

        case 'plugins':
            if (is_file($dir.DIRECTORY_SEPARATOR.'function.cms_selflink.php')) {
                return false;
            }
            break;
/*
        case 'install':
            if( is_dir($dir.DIRECTORY_SEPARATOR.'schemas') ) return FALSE;
            break;
*/
        case 'tmp':
            if (is_dir($dir.DIRECTORY_SEPARATOR.'cache')) {
                return false;
            }
            break;

        case 'phar_installer':
        case 'installer':
        case 'doc':
        case 'build':
        case 'admin':
        case 'module_custom':
        case 'out':
            return false;

        case 'lib':
            if (is_dir($dir.DIRECTORY_SEPARATOR.'classes')) {
                return false;
            }
            break;

        case 'assets':
            if (is_dir($dir.DIRECTORY_SEPARATOR.'layouts')) {
                return false;
            }
            break;

        case 'modules':
            //TODO check for presence of $app_config['coremodules'] member(s)
            if (is_dir($dir.DIRECTORY_SEPARATOR.'ModuleManager')) {
                return false;
            }
            break;

        case 'sources':
            if (is_file($dir.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'version.php')) {
                return false;
            }
            break;
        }
        return true;
    }

    /**
     * Get a CMSMS version without including the file declaring the version
     * (so we don't cause a re-definition problem for PHP)
     * @internal
     * @param string $filepath of a version.php file
     * @return string discovered version or ''
     */
    private function _read_version(string $filepath): string
    {
        $text = @file_get_contents($filepath);
        if (!$text) {
            return '';
        }
        // try first for a string like $CMS_VERSION = 'whatever'
        if (preg_match('~\$CMS_VERSION *= *[\'"] *([\d.]+) *[\'"]~', $text, $matches)) {
            return $matches[1];
        }
        // and then for a const CMS_VERSION declaration
        if (preg_match('~(const|CONST) +CMS_VERSION *= *[\'"] *([\d.]+) *[\'"]~', $text, $matches)) {
            return $matches[2];
        }
        // and finally for a defined 'CMS_VERSION'
        if (preg_match('~(define|DEFINE) +\( *[\'"]CMS_VERSION[\'"] *, *[\'"] *([\d.]+) *[\'"].*\)~', $text, $matches)) {
            return $matches[2];
        }
        return '';
    }

    /**
     * Get a short 'identifier' for the contents of folder $dir
     * @internal
     * @param string $dir filepath of folder
     * @return string, maybe empty
     */
    private function _get_annotation(string $dir): string
    {
        if (!is_dir($dir) || !is_readable($dir)) {
            return '';
        }
        if (basename($dir) != 'lib') {
            $p = $dir.DIRECTORY_SEPARATOR.'version.php';
            if (is_file($p)) {
                if (($ver = $this->_read_version($p))) {
                    return 'CMSMS '.$ver;
                }
                return 'Unrecognized CMSMS version';
            }
        }
        $p = joinpath($dir, 'lib', 'version.php');
        if (is_file($p)) {
            if (($ver = $this->_read_version($p))) {
                return 'CMSMS '.$ver;
            }
            return 'Unrecognized CMSMS version';
        }
        if (is_dir($dir.DIRECTORY_SEPARATOR.'lib')) {
            $p = joinpath($dir, 'lib', 'classes', 'class.installer_base.php');
            if (is_file($p)) {
                return 'CMSMS installation assistant';
            }
        }
        return '';
    }

    /**
     * Recursive method to identify potential installation-places,
     * folders down to 3 levels below the pre-recursion value of $start.
     * @internal
     * @param string $start filepath. Before recursion, the site-root.
     * @param int $depth current recursion-depth (internal use only)
     * @return array maybe empty
     */
    private function _find_dirs(string $start, int $depth = 0)
    {
        if (!is_readable($start)) {
            return [];
        }
        $dh = opendir($start);
        if (!$dh) {
            return [];
        }
        $out = [];
        while (($name = readdir($dh)) !== false) {
            if ($name == '.' || $name == '..') {
                continue;
            }
            if (startswith($name, '.') || startswith($name, '_')) {
                continue;
            }
            $fp = $start.DIRECTORY_SEPARATOR.$name;
            if (!@is_readable($fp)) {
                continue;
            }
            if (!@is_dir($fp)) {
                continue;
            }
            if (!$this->_is_valid_dir($fp)) {
                continue;
            }
            $str = $fp;
            $ann = $this->_get_annotation($fp);
            if ($ann) {
                $str .= " ($ann)";
            }

            $out[$fp] = $str;
            if ($depth < 3) {
                $tmp = $this->_find_dirs($fp, $depth + 1); // recursion
                if ($tmp) {
                    $out = array_merge($out, $tmp);
                }
            }
        }
        closedir($dh);
        return $out;
    }

    private function get_valid_install_dirs(): array
    {
        $start = get_app()->get_rootdir();
        $parent = realpath(dirname($start)); //we're working in a subdir of the main site

        $out = [];
        if ($this->_is_valid_dir($parent)) {
            $out[$parent] = $parent;
        }
        $tmp = $this->_find_dirs($parent);
        if ($tmp) {
            $out = array_merge($out, $tmp);
        }
        asort($out);
        return $out;
    }
/*
    private function generate_checksum()
    {
        $cxt = hash_init ('md5');
        $excludes = ['.*\.svn.*', '.*\.git.*', 'CVS$', '^\#.*\#$', '~$', '\.bak$', '^uploads$', '^tmp$', '^captchas$', '.*UNUSED.*', '.*phar_installer.*'];
TODO recursiveiterator for all non-excluded files {
            hash_update_file($ctx, string $fileURL);
        }
        return hash_final($ctx);
    }
*/
} // class
