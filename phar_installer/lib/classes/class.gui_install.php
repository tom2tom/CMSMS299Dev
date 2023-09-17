<?php
namespace cms_installer;

use cms_installer\installer_base;
use cms_installer\wizard\wizard;
use Exception;
use RuntimeException;
use function cms_installer\endswith;
use function cms_installer\smarty;
use function cms_installer\startswith;
use function cms_installer\translator;

$fp = __DIR__.DIRECTORY_SEPARATOR.'class.installer_base.php';
if (is_file($fp)) {
    require_once $fp;
} else {
    throw new Exception("Required file '$fp' is missing");
}

class gui_install extends installer_base
{
    /**
     * @param string $configfile Optional filepath of a '.ini' file
     *  containing installer settings to be used instead of defaults. Default ''.
     * @throws Exception
     */
    public function __construct(string $configfile = '')
    {
        parent::__construct($configfile);

        // make sure we are in UTF-8
        header('Content-Type:text/html; charset=utf-8');

        $config = $this->get_config(); // generic config data

        $this->fixup_tmpdir_environment();

        // setup smarty
        $smarty = smarty();
        $smarty->assign('APPNAME', 'cms_installer')
         ->assign('config', $config)
         ->assign('installer_version', $config['installer_version']);
        if (isset($config['build_time'])) {
            $smarty->assign('build_time', $config['build_time']);
        }

        if ($this->in_phar() && !$config['nobase']) {
            $base_href = $_SERVER['SCRIPT_NAME'];
            if (endswith($base_href, '.php')) {
                $base_href .= '/';
                $smarty->assign('BASE_HREF', $base_href);
            }
        }
    }

    public function get_root_url(): string
    {
        if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off') {
            $prefix = 'https';
        } else {
            $prefix = 'http';
        }
        //NOTE: never trust $_SERVER['HTTP_*'] variables which contain IP address
        //? maybe sanitize and/or whitelist-check
        $prefix .= '://'.$_SERVER['HTTP_HOST'];

        // if we are putting files somewhere else, we cannot determine
        // the root url of the site via $_SERVER variables.
        $b = $this->get_destdir();
        // __DIR__ might be phar://abspath/to/pharfile/relpath/to/thisfolder
        // cwd might be relative to pharfile i.e. relpath/to/thisfolder
        if ($b != getcwd()) {
            if (startswith($b, $_SERVER['DOCUMENT_ROOT'])) {
                $b = substr($b, strlen($_SERVER['DOCUMENT_ROOT']));
            }
            $b = str_replace('\\', '/', $b); // cuz windows blows
            if (!endswith($prefix, '/') && !startswith($b, '/')) {
                $prefix .= '/';
            }
            return $prefix.$b;
        }

        $b = dirname($_SERVER['PHP_SELF']);
        if ($this->in_phar()) {
            $tmp = basename($_SERVER['SCRIPT_NAME']);
            if (($p = strpos($b, $tmp)) !== false) {
                $b = substr($b, 0, $p);
            }
        }

        $b = str_replace('\\', '/', $b); // cuz windows blows.
        if (!endswith($prefix, '/') && !startswith($b, '/')) {
            $prefix .= '/';
        }
        return $prefix.$b;
    }

    public function run(): void
    {
        $ops = translator();
        // set the supported languages
        $list = $ops->get_available_languages();
        $ops->set_allowed_languages($list);

        // the default language
        $ops->set_default_language('en_US');

        // get the language preferred by the user (in the request, a cookie, the session, or custom config)
        $lang = $ops->get_selected_language();
        if (!$lang) {
            $lang = $ops->get_default_language(); // fallback to default (presumably still en_US)
        }
        // cache it for this session
        $ops->set_selected_language($lang);

        // and do our stuff
//        try {
        // __DIR__ might be phar://abspath/to/pharfile/relpath/to/thisfolder
        // cwd might be relative to pharfile i.e. relpath/to/thisfolder
        $wizard = wizard::get_instance(__DIR__.DIRECTORY_SEPARATOR.'wizard', __NAMESPACE__.'\\wizard');
        $res = $wizard->process();
        if ($res === null) {
            throw new Exception('Something went wrong!?');
        }
/*        }
        catch( Throwable $t ) {
            echo $t->GetMessage(); // DEBUG
            $smarty = smarty();
            $smarty->assign('error', $t->GetMessage());
            $smarty->display('error.tpl');
        }
*/
    }

    private function fixup_tmpdir_environment()
    {
        // if the system temporary directory is not the same as the config temporary directory
        // then we attempt to putenv the TMPDIR environment variable
        // so that tmpfile() will work as it uses the system temporary directory which can read from environment variables
        $sys_tmpdir = null;
        if (function_exists('sys_get_temp_dir')) {
            $sys_tmpdir = rtrim(sys_get_temp_dir(), ' \/');
        }
        $config = $this->get_config();
        if ((!$sys_tmpdir || !is_dir($sys_tmpdir) || !is_writable($sys_tmpdir)) && $sys_tmpdir != $config['tmpdir']) {
            @putenv('TMPDIR='.$config['tmpdir']);
            $try1 = getenv('TMPDIR');
            if ($try1 != $config['tmpdir']) {
                throw new RuntimeException('Sorry, putenv does not work on this system, and your system temporary directory is not set properly.');
            }
        }
    }
} // class
