<?php
namespace cms_installer\wizard;

use cms_installer\tests\boolean_test;
use cms_installer\tests\informational_test;
use cms_installer\tests\matchany_test;
use cms_installer\tests\range_test;
use cms_installer\tests\test_base;
use cms_installer\tests\version_range_test;
use cms_installer\wizard\wizard_step;
use function cms_installer\get_app;
use function cms_installer\get_writable_error;
use function cms_installer\is_directory_writable;
use function cms_installer\lang;
use function cms_installer\redirect;
use function cms_installer\smarty;
use function cms_installer\tests\test_is_false;
use function cms_installer\tests\test_is_true;
use function cms_installer\tests\test_remote_file;

class wizard_step3 extends wizard_step
{
    /**
     * @param bool $verbose
     * @param array $informational
     * @param array $tests
     * @return array
     */
    protected function perform_tests(bool $verbose, array &$informational, array &$tests): array
    {
        $app = get_app();
        $wiz = $this->get_wizard();
        $action = $wiz->get_data('action');
        $version_info = $wiz->get_data('version_info'); // non-empty array only for refreshes & upgrades
        $informational = [];
        $tests = [];

        if ($verbose) {
            // messages...
            $informational[] = new informational_test('server_api', PHP_SAPI, 'info_server_api');
            $informational[] = new informational_test('server_os', implode(', ', [PHP_OS, php_uname('r'), php_uname('m')]));
            $informational[] = new informational_test('server_software', $_SERVER['SERVER_SOFTWARE']); //,'info_server_software');
        }

        // required test ... php version
        $v = PHP_VERSION;
        $obj = new version_range_test('php_version', $v);
        $obj->minimum = '7.2';  // PHP 7.2 EOL late 2020, 7.3 EOL late 2021
        // set this to the current minimum security-supported micro-version
        // via www.php.net/supported-versions.php and www.php.net/releases/index.php
        $app_config = $app->get_config();
        $prefphp = (!empty($app_config['livephpmin'])) ? $app_config['livephpmin'] : '8.0.0';
        $obj->recommended = $prefphp;
        $obj->fail_msg = lang('fail_php_version', $v, $obj->minimum);
        if (version_compare($obj->minimum, $obj->recommended) < 0) {
            $obj->warn_msg = lang('fail_php_version2', $v, $obj->recommended);
        } else {
            $obj->warn_msg = lang('msg_yourvalue', $v);
        }
        $obj->pass_msg = lang('msg_yourvalue', $v);
        $obj->required = 1;
        $tests[] = $obj;

        // required test ... mysqli extension (mysqli version-check in step 4, after connection)
        $obj = new boolean_test('database_support', extension_loaded('mysqli'));
        $obj->required = 1;
        $obj->fail_key = 'fail_database_support';
        $tests[] = $obj;

        // required test ... multibyte extension or builtin
        $obj = new boolean_test('multibyte_support', extension_loaded('mbstring') || function_exists('mb_get_info'));
        $obj->required = 1;
        $obj->fail_key = 'fail_multibyte_support';
        $tests[] = $obj;

        // required test ... intl extension or builtin (Collator class required, others e.g. IntlDateFormatter recommended)
        $obj = new boolean_test('intl_support', extension_loaded('intl'));
        $obj->required = 1;
        $obj->fail_key = 'fail_intl_support';
        $tests[] = $obj;
/*
        // recommended test ... IntlDateFormatter class (intl extension)
        $obj = new boolean_test('intl_extension', extension_loaded('intl') && class_exists('IntlDateFormatter'));
        $obj->fail_key = 'fail_intl_support';
        $tests[] = $obj;
*/
        // required test ... xml extension
        $obj = new boolean_test('xml_functions', extension_loaded('xml'));
        $obj->required = 1;
        $obj->fail_key = 'fail_xml_functions';
        $tests[] = $obj;

        // required test ... gd extension V.2
        $obj = new version_range_test('gd_version', $this->_GDVersion());
        $obj->minimum = 2;
        $obj->required = 1;
        $obj->fail_msg = lang('msg_yourvalue', $this->_GDVersion());
        $tests[] = $obj;

        // recommended test ... curl extension
        $obj = new boolean_test('curl_extension', extension_loaded('curl'));
        $obj->fail_key = 'fail_curl_extension';
        $tests[] = $obj;

        if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 70200) {
            $obj = new matchany_test('cryption_functions');
            // recommended test ... sodium en/decryption built-in or via extension
            $t1 = new boolean_test('Sodium', defined('SODIUM_LIBRARY_VERSION'), 'crypter_sodium');
            $obj->add_child($t1);
            $t1 = new boolean_test('OpenSSL', extension_loaded('openssl'), 'crypter_ssl');
            $obj->add_child($t1);
            $obj->fail_key = 'fail_cryption_functions';
            $obj->pass_key = 'pass_cryption_functions';
            $tests[] = $obj;
        } else {
            // recommended test ... ssl extension for en/de-cryption
            $obj = new boolean_test('ssl_extension', extension_loaded('openssl'));
            $obj->fail_key = 'fail_ssl_extension';
            $tests[] = $obj;
        }

        // recommended test ... supported cache extension
        // preference order: [php]redis,apcu,yac,memcached(slowest)
        $obj = new matchany_test('cache_extension');
        $t1 = new boolean_test('PHPredis', class_exists('Redis'), 'cache_predis'); //too bad if server not running!
        $obj->add_child($t1);
        $t1 = new boolean_test('APCu', extension_loaded('apcu') && ini_get('apc.enabled'), 'cache_apcu');
        $obj->add_child($t1);
        $t1 = new boolean_test('YAC', extension_loaded('yac'), 'cache_yac');
        $obj->add_child($t1);
        $t1 = new boolean_test('Memcached', class_exists('Memcached'), 'cache_memcached'); //too bad if server not running!
        $obj->add_child($t1);
        $obj->fail_key = 'fail_cache_extension';
        $obj->pass_key = 'pass_cache_extension';
        $tests[] = $obj;
        $ctest = count($tests) - 1; //process this one specially

        // recommended test ... ziparchive class (zip extension)
        $obj = new boolean_test('func_ziparchive', class_exists('ZipArchive'));
        $obj->fail_key = 'fail_func_ziparchive';
        $tests[] = $obj;

        // required test ... tmpfile function
        $fh = tmpfile();
        $b = $fh !== false;
        $obj = new boolean_test('tmpfile', $b);
        $obj->required = 1;
        if ($b) {
            fclose($fh);
        } else {
            $obj->fail_msg = lang('fail_tmpfile');
        }
        $tests[] = $obj;

        // required test ... tempnam function
        $obj = new boolean_test('func_tempnam', function_exists('tempnam'));
        $obj->required = 1;
        $obj->fail_key = 'fail_func_tempnam';
        $tests[] = $obj;

        // required test ... some sort of gzopen/gzopen64 combo
        $obj = new boolean_test('func_gzopen', function_exists('gzopen') || function_exists('gzopen64'));
        $obj->required = 1;
        $obj->fail_key = 'fail_func_gzopen';
        $tests[] = $obj;

        // required test ... md5 function
        $obj = new boolean_test('func_md5', function_exists('md5'));
        $obj->fail_key = 'fail_func_md5';
        $obj->required = 1;
        $tests[] = $obj;

        // required test ... json function
        $obj = new boolean_test('func_json', function_exists('json_decode'));
        $obj->fail_key = 'pass_func_json';
        $obj->required = 1;
        $tests[] = $obj;

        // file get contents
        $obj = new boolean_test('file_get_contents', function_exists('file_get_contents'));
        $obj->required = 1;
        $obj->fail_key = 'fail_file_get_contents';
        $tests[] = $obj;
/* N/A PHP7+
        // required test ... magic_quotes_runtime
        $obj = new boolean_test('magic_quotes_runtime',!get_magic_quotes_runtime());
        $obj->required = 1;
        $obj->fail_key = 'fail_magic_quotes_runtime';
        $tests[] = $obj;
*/
        // recommended test ... open basedir
        $obj = new boolean_test('open_basedir', ini_get('open_basedir') == '');
        $obj->warn_key = 'warn_open_basedir';
        $obj->fail_key = 'fail_open_basedir';
        $tests[] = $obj;

        // required test ... sessions must use cookies
        $obj = new boolean_test('session_use_cookies', ini_get('session.use_cookies'));
        $obj->required = 1;
        $obj->fail_key = 'fail_session_use_cookies';
        $tests[] = $obj;

        if (ini_get('session.save_handler') == 'files') {
            $open_basedir = ini_get('open_basedir');
            if ($open_basedir) {
                // open basedir restrictions are in effect, can't test if the session save path is writable
                // so just talk about it.
                // note: if we got here, sessions are probably working just fine.
                $t2 = new boolean_test('open_basedir_session_save_path', 0);
                $t2->warn_key = 'warn_open_basedir_session_savepath';
                $t2->msg = lang('info_open_basedir_session_save_path');
                $tests[] = $t2;
            } else {
                // test if the session save path is writable.
                $tmp = $this->_get_session_save_path();
                if ($tmp) {
                    // session save path can be empty which should use the system temporary directory
                    $t2 = new boolean_test('session_save_path_exists', @is_dir($tmp));
                    $t2->required = 1;
                    $t2->fail_key = 'fail_session_save_path_exists';
                    $tests[] = $t2;

                    $t3 = new boolean_test('session_save_path_writable', @is_writable($tmp));
                    $t3->required = 1;
                    $t3->fail_key = 'fail_session_save_path_writable';
                    $tests[] = $t3;
                }
            }
        }

        // recommended test ... E_STRICT disabled
        $orig_error_level = $app->get_orig_error_level();
        $obj = new boolean_test('errorlevel_estrict', !($orig_error_level & E_STRICT));
        $obj->warn_key = 'estrict_enabled';
        $tests[] = $obj;

        // recommended test ... E_DEPRECATED disabled
        $obj = new boolean_test('errorlevel_edeprecated', !($orig_error_level & E_DEPRECATED));
        $obj->warn_key = 'edeprecated_enabled';
        $tests[] = $obj;
        if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 80000) {
            // default error_reporting level is now E_ALL. Previously it excluded E_DEPRECATED and E_NOTICE
            // recommended test ... E_NOTICE disabled
            $obj = new boolean_test('errorlevel_enotice', !($orig_error_level & E_NOTICE));
            $obj->warn_key = 'enotice_enabled';
            $tests[] = $obj;
        }

        // required test ... safe mode
        $obj = new boolean_test('safe_mode', test_is_false(ini_get('safe_mode')));
        $obj->required = 1;
        $obj->fail_key = 'fail_safe_mode';
        $tests[] = $obj;

        // required test ... file upload
        $obj = new boolean_test('file_uploads', test_is_true(ini_get('file_uploads')));
        $obj->required = 1;
        $obj->fail_key = 'fail_file_uploads';
        $tests[] = $obj;

        // upload max filesize
        $obj = new range_test('upload_max_filesize', ini_get('upload_max_filesize'));
        $obj->minimum = '1M';
        $obj->recommended = '10M';
        $obj->required = 1;
        $obj->warn_msg = lang('warn_upload_max_filesize', ini_get('upload_max_filesize'), $obj->recommended);
        $tests[] = $obj;

        // required test ... MEMORY LIMIT
        $obj = new range_test('memory_limit', ini_get('memory_limit'));
        $obj->minimum = '16M';
        $obj->recommended = '32M';
        $obj->pass_msg = ini_get('memory_limit');
        $obj->fail_msg = lang('fail_memory_limit', ini_get('memory_limit'), $obj->minimum, $obj->recommended);
        $obj->warn_msg = lang('warn_memory_limit', ini_get('memory_limit'), $obj->minimum, $obj->recommended);
        $obj->required = 1;
        $tests[] = $obj;

        // recommended test ... max_execution_time
        $v = (int) ini_get('max_execution_time');
        if ($v !== 0) {
            $obj = new range_test('max_execution_time', $v);
            $obj->minimum = 30;
            $obj->recommended = 60;
            $obj->required = 1;
            $obj->warn_msg = lang('warn_max_execution_time', ini_get('max_execution_time'), $obj->minimum, $obj->recommended);
            $obj->fail_msg = lang('fail_max_execution_time', ini_get('max_execution_time'), $obj->minimum, $obj->recommended);
            $tests[] = $obj;
        }

        // recommended test ... post_max_size
        $obj = new range_test('post_max_size', ini_get('post_max_size'));
        $obj->minimum = '2M';
        $obj->recommended = '10M';
        $obj->warn_msg = lang('warn_post_max_size', ini_get('post_max_size'), $obj->minimum, $obj->recommended);
        $obj->fail_key = 'fail_post_max_size';
        $tests[] = $obj;

        // recommended test ... register globals
        $obj = new boolean_test('register_globals', !ini_get('register_globals'));
        $obj->required = 1;
        $obj->fail_key = 'fail_register_globals';
        $tests[] = $obj;

        // recommended test ... output buffering
        $obj = new boolean_test('output_buffering', ini_get('output_buffering'));
        $obj->fail_key = 'fail_output_buffering';
        $tests[] = $obj;

        // recommended test ... disable functions
        $obj = new boolean_test('disable_functions', ini_get('disable_functions') == '');
        $obj->warn_msg = lang('warn_disable_functions', str_replace(',', ', ', ini_get('disable_functions')));
        $tests[] = $obj;

        // recommended test ... url fopen
        $obj = new boolean_test('allow_url_fopen', ini_get('allow_url_fopen'));
        $obj->warn_msg = lang('warn_url_fopen', ini_get('allow_url_fopen'));
        $tests[] = $obj;

        // recommended test ... default charset/encoding
        $default_charset = ini_get('default_charset');
        $obj = new boolean_test('default_charset', (strtolower($default_charset) == 'utf-8'));
        $obj->warn_msg = lang('warn_default_charset', $default_charset);
        $tests[] = $obj;

        // test ini set
        $v = ini_get('log_errors_max_len');
        if ($v) {
            $v2 = (string)max(512, (int)$v - 10);
        } elseif ($v !== false) {
            $v2 = '512';
        } else {
            $v2 = false;
        }
        if ($v2 !== false) {
            ini_set('log_errors_max_len', $v2);
            $r = (ini_get('log_errors_max_len') == $v2);
            ini_set('log_errors_max_len', $v);
            $obj = new boolean_test('ini_set', $r);
            $obj->fail_key = 'fail_ini_set';
            $tests[] = $obj;
        } else {
            $v = ini_get('max_execution_time');
            if ($v) {
                $v2 = (string)max(93,(int)$v + 2);
            } elseif ($v !== false) {
                $v2 = '93';
            } else {
                $v2 = false;
            }
            if ($v2 !== false) {
                ini_set('max_execution_time', $v2);
                $r = (ini_get('max_execution_time') == $v2);
                ini_set('max_execution_time', $v);
                $obj = new boolean_test('ini_set', $r);
                $obj->fail_key = 'fail_ini_set';
                $tests[] = $obj;
            } else {
                $obj = new informational_test('ini_set', 'Undetermined');
                $tests[] = $obj;
            }
        }

        // required test... check if most files are writable.
        $dirs = ['lib', 'admin', 'uploads', 'doc', 'tmp', 'assets'];
        if ($version_info) {
            // it's an upgrade or freshen
            if (!empty($version_info['config']['admin_path'])) {
                //TODO handle relative 'admin_path', possibly > 1 segment
                $dirs[1] = $version_info['config']['admin_path'];
            }
            if (!empty($version_info['config']['assets_path'])) {
                //TODO handle relative 'assets_path', possibly > 1 segment
                $dirs[5] = $version_info['config']['assets_path'];
            }
        }

        $failed = [];
        $list = glob($app->get_destdir().DIRECTORY_SEPARATOR.'*'); // filesystem path
        foreach ($list as $one) {
            $basename = basename($one);
            if (is_file($one)) {
                $relative = substr($one, strlen($app->get_destdir()) + 1);
                if (!is_writable($one)) {
                    $failed[] = $relative;
                }
            } elseif (in_array($basename, $dirs)) {
                $b = is_directory_writable($one, true);
                if (!$b) {
                    $tmp = get_writable_error();
                    $failed = array_merge($failed, get_writable_error());
                }
            }
        }

        if ($version_info) { // upgrade or freshen session
            // during an upgrade or freshen, the existing config file will or
            // might be backed up and replaced, which needs write-permission
            $obj = new boolean_test('config_writable', is_writable($version_info['config_file']));
            $obj->required = 1;
            $obj->fail_key = 'fail_config_writable';
            $tests[] = $obj;
            if ($action == 'upgrade' || $action == 'freshen') { //TODO why is version overwitten during freshen? just one of the files?
                $version_file = $app->get_destdir().DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'version.php';
                $obj = new boolean_test('version_writable', is_writable($version_file));
                $obj->required = 1;
                $obj->fail_key = 'fail_version_writable';
                $tests[] = $obj;
            }

            if (version_compare($version_info['version'], '2.2') < 0) {
                // assets folder must exist
                if (!empty($version_info['config']['assets_path'])) {
                    $aname = $version_info['config']['assets_path'];
                    //TODO if > 1 segment in name
                    $dir = $app->get_destdir().DIRECTORY_SEPARATOR.$aname;
                    //TODO if absolute
//                    $dir = $app->get_destdir().$aname;
                } else {
                    $dir = $app->get_destdir().DIRECTORY_SEPARATOR.'assets';
                }
                if (is_dir($dir)) {
                    $obj = new boolean_test('assets_dir_exists', false);
                    $obj->fail_key = 'fail_assets_dir';
                    $obj->warn_key = 'fail_assets_dir';
                    $tests[] = $obj;
                }
            }
        } else { // install
            // $dir is a filesystem path descendant from the site-root-path
            $is_dir_empty = function(string $dir): bool {
                $dir = trim($dir);
                if (!$dir) {
                    return false;  // fail on invalid dir
                }
                if (!is_dir($dir)) {
                    return true; // pass on dir not existing yet
                }
                $files = glob($dir.DIRECTORY_SEPARATOR.'*'); // filesystem path
                if (!$files) {
                    return true; // no files yet
                }
                if (count($files) > 1) {
                    return false; // more than one file
                }
                // trivial check for index.htm[l]
                $bn = strtolower(basename($files[0]));
                return fnmatch('index.htm?', $bn);
            };

            $dest = $app->get_destdir();
            $res = true;
            if ($res && !$is_dir_empty($dest.DIRECTORY_SEPARATOR.'tmp/cache')) {
                $res = false;
            }
            if ($res && !$is_dir_empty($dest.DIRECTORY_SEPARATOR.'tmp/templates_c')) {
                $res = false;
            }

            $obj = new boolean_test('tmp_dirs_empty', $res);
            $obj->required = 1;
            $obj->fail_key = 'fail_tmp_dirs_empty';
            $tests[] = $obj;
        }

        // recommended test ... remote_url
        $obj = new boolean_test('remote_url', test_remote_file('https://www.cmsmadesimple.org/latest_version.php', 10, 'cmsmadesimple'));
        $obj->fail_key = 'fail_remote_url';
        $obj->warn_key = 'fail_remote_url';
        $tests[] = $obj;
/*
        now run the tests
        if all of them pass
           display warm fuzzy message
           user can continue
        else if a required test fails
           display failed tests (or all tests for verbose mode)
           user can't continue
        otherwise
           display failed tests (or all tests for verbose mode)
           user can continue
*/
        $can_continue = true;
        $tests_failed = false;
        $fails = [];
        for ($i = 0, $n = count($tests); $i < $n; ++$i) {
            $res = $tests[$i]->run();
            if ($res == test_base::TEST_FAIL) {
                $tests_failed = true;
                $fails[] = $tests[$i];
                if ($tests[$i]->required) {
                    $can_continue = false;
                } else {
                    $tests[$i]->status = test_base::TEST_WARN;
                }
            }
        }

        $cachable = (!in_array($tests[$ctest], $fails)) ? 'auto' : 'file';
        $wiz->set_data('cachemode', $cachable);

        if (!$verbose) {
            $tests = $fails;
        }
        return [$tests_failed, $can_continue];
    }

    protected function process()
    {
        $url = $this->get_wizard()->next_url();
        redirect($url);
    }

    protected function display()
    {
        parent::display();
        $config = get_app()->get_config();
        $verbose = $config['verbose'] ?? 0;
        $informational = [];
        $tests = [];
        [$tests_failed, $can_continue] = $this->perform_tests($verbose, $informational, $tests);

        $smarty = smarty();
        $smarty->assign('tests', $tests)
         ->assign('tests_failed', $tests_failed)
         ->assign('can_continue', $can_continue)
         ->assign('verbose', $verbose);
        if ($tests_failed) {
            $smarty->assign('retry_url', $_SERVER['REQUEST_URI']);
        }
        if ($verbose) {
            $smarty->assign('information', $informational); //assume specialize() not needed
        }
        // TODO button(s) and processing for enable verbose mode etc.

        $smarty->display('wizard_step3.tpl');
        $this->finish();
    }

    private function _get_session_save_path()
    {
        $path = ini_get('session.save_path');
        if (($pos = strpos($path, ';')) !== false) {
            $path = substr($path, $pos + 1);
        }

        if ($path) {
            return $path;
        }
    }

    private function _GDVersion()
    {
        static $gd_version_number = null;

        if (is_null($gd_version_number)) {
            if (extension_loaded('gd')) {
                if (defined('GD_MAJOR_VERSION')) {
                    $gd_version_number = GD_MAJOR_VERSION;
                    return $gd_version_number;
                }
                $gdinfo = @gd_info();
                if (preg_match('/\d+/', $gdinfo['GD Version'], $gdinfo)) {
                    $gd_version_number = (int) $gdinfo[0];
                } else {
                    $gd_version_number = 1;
                }
                return $gd_version_number;
            }
            $gd_version_number = 0;
        }

        return $gd_version_number;
    }
} // class
