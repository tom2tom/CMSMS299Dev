<?php

namespace cms_installer\wizard;

use cms_installer\tests\boolean_test;
use cms_installer\tests\informational_test;
use cms_installer\tests\matchany_test;
use cms_installer\tests\range_test;
use cms_installer\tests\test_base;
use cms_installer\tests\version_range_test;
use cms_installer\utils;
use cms_installer\wizard\wizard_step;
use function cms_installer\lang;
use function cms_installer\smarty;
use function cms_installer\get_app;
use function cms_installer\tests\test_is_false;
use function cms_installer\tests\test_is_true;
use function cms_installer\tests\test_remote_file;

class wizard_step3 extends wizard_step
{
    private function _get_session_save_path()
    {
        $path = ini_get('session.save_path');
        if( ($pos = strpos($path,';')) !== FALSE) $path = substr($path,$pos+1);

        if( $path ) return $path;
    }

    private function _GDVersion()
    {
        static $gd_version_number = null;

        if( is_null($gd_version_number) ) {
            if( extension_loaded('gd') ) {
                if( defined('GD_MAJOR_VERSION') ) {
                    $gd_version_number = GD_MAJOR_VERSION;
                    return $gd_version_number;
                }
                $gdinfo = @gd_info();
                if( preg_match('/\d+/', $gdinfo['GD Version'], $gdinfo) ) {
                    $gd_version_number = (int) $gdinfo[0];
                }
                else {
                    $gd_version_number = 1;
                }
                return $gd_version_number;
            }
            $gd_version_number = 0;
        }

        return $gd_version_number;
    }

    /**
     * @param bool $verbose
     * @param array $informational
     * @param array $tests
     * @return array
     */
    protected function perform_tests(bool $verbose, array &$informational, array &$tests) : array
    {
        $app = get_app();
        $wiz = $this->get_wizard();
        $action = $wiz->get_data('action');
        $version_info = $wiz->get_data('version_info');
        $informational = [];
        $tests = [];

        if ($verbose) {
            // messages...
            $informational[] = new informational_test('server_api',PHP_SAPI,'info_server_api');
            $informational[] = new informational_test('server_os',implode(', ',[PHP_OS,php_uname('r'),php_uname('m')]));
            $informational[] = new informational_test('server_software',$_SERVER['SERVER_SOFTWARE']); //,'info_server_software');
        }

        // required test ... php version
        $v = PHP_VERSION;
        $obj = new version_range_test('php_version',$v);
        $obj->minimum = '7.1';
        $obj->recommended = '7.2';
        $obj->fail_msg = lang('fail_php_version',$v,$obj->minimum);
        if (version_compare($obj->minimum, $obj->recommended) < 0) {
            $obj->warn_msg = lang('fail_php_version2',$v,$obj->recommended);
        } else {
            $obj->warn_msg = lang('msg_yourvalue',$v);
        }
        $obj->pass_msg = lang('msg_yourvalue',$v);
        $obj->required = 1;
        $tests[] = $obj;

        // required test ... mysqli extension
        $obj = new boolean_test('database_support',extension_loaded('mysqli'));
        $obj->required = 1;
        $obj->fail_key = 'fail_database_support';
        $tests[] = $obj;

        // required test ... gd extension V.2
        $obj = new version_range_test('gd_version',$this->_GDVersion());
        $obj->minimum = 2;
        $obj->required = 1;
        $obj->fail_msg = lang('msg_yourvalue',$this->_GDVersion());
        $tests[] = $obj;

        // required test ... multibyte extension
        $obj = new boolean_test('multibyte_support',extension_loaded('mbstring') && function_exists('mb_get_info'));
        $obj->required = 1;
        $obj->fail_key = 'fail_multibyte_support';
        $tests[] = $obj;

        // required test ... xml extension
        $obj = new boolean_test('xml_functions',extension_loaded('xml'));
        $obj->required = 1;
        $obj->fail_key = 'fail_xml_functions';
        $tests[] = $obj;

        // recommended test ... curl extension
        $obj = new boolean_test('curl_extension',extension_loaded('curl'));
        $obj->fail_key = 'fail_curl_extension';
        $tests[] = $obj;

        // recommended test ... supported cache extension
        // preference order: [php]redis,apcu,yac,memcached(slowest)
        $obj = new matchany_test('cache_extension');
        $t1 = new boolean_test('PHPredis',class_exists('Redis'),'cache_predis'); //too bad if server not running!
        $obj->add_child($t1);
        $t1 = new boolean_test('APCu',extension_loaded('apcu') && ini_get('apc.enabled'),'cache_apcu');
        $obj->add_child($t1);
        $t1 = new boolean_test('YAC',extension_loaded('yac'),'cache_yac');
        $obj->add_child($t1);
        $t1 = new boolean_test('Memcached',class_exists('Memcached'),'cache_memcached'); //too bad if server not running!
        $obj->add_child($t1);
        $obj->fail_key = 'fail_cache_extension';
        $obj->pass_key = 'pass_cache_extension';
        $tests[] = $obj;
        $ctest = count($tests) - 1; //process this one specially

        // recommended test ... ziparchive class (zip extension)
        $obj = new boolean_test('func_ziparchive',class_exists('ZipArchive'));
        $obj->fail_key = 'fail_func_ziparchive';
        $tests[] = $obj;

        // required test ... tmpfile
        $fh = tmpfile();
        $b = $fh !== FALSE;
        $obj = new boolean_test('tmpfile',$b);
        $obj->required = 1;
        if( $b ) fclose($fh);
        else $obj->fail_msg = lang('fail_tmpfile');
        $tests[] = $obj;

        // required test ... tempnam function
        $obj = new boolean_test('func_tempnam',function_exists('tempnam'));
        $obj->required = 1;
        $obj->fail_key = 'fail_func_tempnam';
        $tests[] = $obj;

        // required test ... some sort of gzopen/gzopen64 combo
        $obj = new boolean_test('func_gzopen',function_exists('gzopen') || function_exists('gzopen64'));
        $obj->required = 1;
        $obj->fail_key = 'fail_func_gzopen';
        $tests[] = $obj;

        // required test ... md5 function
        $obj = new boolean_test('func_md5',function_exists('md5'));
        $obj->fail_key = 'fail_func_md5';
        $obj->required = 1;
        $tests[] = $obj;

        // required test ... json function
        $obj = new boolean_test('func_json',function_exists('json_decode'));
        $obj->fail_key = 'pass_func_json';
        $obj->required = 1;
        $tests[] = $obj;

        // file get contents
        $obj = new boolean_test('file_get_contents',function_exists('file_get_contents'));
        $obj->required = 1;
        $obj->fail_key = 'fail_file_get_contents';
        $tests[] = $obj;

        // required test ... magic_quotes_runtime
        $obj = new boolean_test('magic_quotes_runtime',!get_magic_quotes_runtime());
        $obj->required = 1;
        $obj->fail_key = 'fail_magic_quotes_runtime';
        $tests[] = $obj;

        // recommended test ... open basedir
        $obj = new boolean_test('open_basedir',ini_get('open_basedir') == '');
        $obj->warn_key = 'warn_open_basedir';
        $obj->fail_key = 'fail_open_basedir';
        $tests[] = $obj;

        // required test ... sessions must use cookies
        $t0 = new boolean_test('session_use_cookies',ini_get('session.use_cookies'));
        $t0->required = 1;
        $t0->fail_key = 'fail_session_use_cookies';
        $tests[] = $t0;

        if( ini_get('session.save_handler') == 'files' ) {
            $open_basedir = ini_get('open_basedir');
            if( $open_basedir ) {
                // open basedir restrictions are in effect, can't test if the session save path is writable
                // so just talk about it.
                // note: if we got here, sessions are probably working just fine.
                $t2 = new boolean_test('open_basedir_session_save_path',0);
                $t2->warn_key = 'warn_open_basedir_session_savepath';
                $t2->msg = lang('info_open_basedir_session_save_path');
                $tests[] = $t2;
            }
            else {
                // test if the session save path is writable.
                $tmp = $this->_get_session_save_path();
                if( $tmp ) {
                    // session save path can be empty which should use the system temporary directory
                    $t2 = new boolean_test('session_save_path_exists',@is_dir($tmp));
                    $t2->required = 1;
                    $t2->fail_key = 'fail_session_save_path_exists';
                    $tests[] = $t2;

                    $t3 = new boolean_test('session_save_path_writable',@is_writable($tmp));
                    $t3->required = 1;
                    $t3->fail_key = 'fail_session_save_path_writable';
                    $tests[] = $t3;
                }
            }
        }

        // recommended test ... E_STRICT disabled
        $orig_error_level = $app->get_orig_error_level();
        $obj = new boolean_test('errorlevel_estrict',!($orig_error_level & E_STRICT));
        $obj->warn_key = 'estrict_enabled';
        $tests[] = $obj;

        // recommended test ... E_DEPRECATED disabled
        $obj = new boolean_test('errorlevel_edeprecated',!($orig_error_level & E_DEPRECATED));
        $obj->warn_key = 'edeprecated_enabled';
        $tests[] = $obj;

        // required test ... safe mode
        $obj = new boolean_test('safe_mode',test_is_false(ini_get('safe_mode')));
        $obj->required = 1;
        $obj->fail_key = 'fail_safe_mode';
        $tests[] = $obj;

        // required test ... file upload
        $obj = new boolean_test('file_uploads',test_is_true(ini_get('file_uploads')));
        $obj->required = 1;
        $obj->fail_key = 'fail_file_uploads';
        $tests[] = $obj;

        // upload max filesize
        $obj = new range_test('upload_max_filesize',ini_get('upload_max_filesize'));
        $obj->minimum = '1M';
        $obj->recommended = '10M';
        $obj->required = 1;
        $obj->warn_msg = lang('warn_upload_max_filesize',ini_get('upload_max_filesize'),$obj->recommended);
        $tests[] = $obj;

        // required test ... MEMORY LIMIT
        $obj = new range_test('memory_limit',ini_get('memory_limit'));
        $obj->minimum = '16M';
        $obj->recommended = '32M';
        $obj->pass_msg = ini_get('memory_limit');
        $obj->fail_msg = lang('fail_memory_limit',ini_get('memory_limit'),$obj->minimum,$obj->recommended);
        $obj->warn_msg = lang('warn_memory_limit',ini_get('memory_limit'),$obj->minimum,$obj->recommended);
        $obj->required = 1;
        $tests[] = $obj;

        // recommended test ... max_execution_time
        $v = (int) ini_get('max_execution_time');
        if( $v !== 0 ) {
            $obj = new range_test('max_execution_time',$v);
            $obj->minimum = 30;
            $obj->recommended = 60;
            $obj->required = 1;
            $obj->warn_msg = lang('warn_max_execution_time',ini_get('max_execution_time'),$obj->minimum,$obj->recommended);;
            $obj->fail_msg = lang('fail_max_execution_time',ini_get('max_execution_time'),$obj->minimum,$obj->recommended);;
            $tests[] = $obj;
        }

        // recommended test ... post_max_size
        $obj = new range_test('post_max_size',ini_get('post_max_size'));
        $obj->minimum = '2M';
        $obj->recommended = '10M';
        $obj->warn_msg = lang('warn_post_max_size',ini_get('post_max_size'),$obj->minimum,$obj->recommended);
        $obj->fail_key = 'fail_post_max_size';
        $tests[] = $obj;

        // recommended test ... register globals
        $obj = new boolean_test('register_globals',!ini_get('register_globals'));
        $obj->required = 1;
        $obj->fail_key = 'fail_register_globals';
        $tests[] = $obj;

        // recommended test ... output buffering
        $obj = new boolean_test('output_buffering',ini_get('output_buffering'));
        $obj->fail_key = 'fail_output_buffering';
        $tests[] = $obj;

        // recommended test ... disable functions
        $obj = new boolean_test('disable_functions',ini_get('disable_functions') == '');
        $obj->warn_msg = lang('warn_disable_functions',str_replace(',',', ',ini_get('disable_functions')));
        $tests[] = $obj;

        // recommended test ... default charset/encoding
        $default_charset = ini_get('default_charset');
        $obj = new boolean_test('default_charset',(strtolower($default_charset) == 'utf-8'));
        $obj->warn_msg = lang('warn_default_charset',$default_charset);
        $tests[] = $obj;

        // test ini set
        $val = (ini_get('log_errors_max_len')) ? ini_get('log_errors_max_len').'0':'99';
        ini_set('log_errors_max_len',$val);
        $obj = new boolean_test('ini_set',ini_get('log_errors_max_len') == $val);
        $obj->fail_key = 'fail_ini_set';
        $tests[] = $obj;

        // required test... check if most files are writable.
            $dirs = ['lib','admin','uploads','doc','tmp','assets'];
            if( $version_info ) {
                // it's an upgrade
                if( !empty($version_info['config']['admin_dir']) ) {
                    $dirs[1] = $version_info['config']['admin_dir'];
                }
                if( !empty($version_info['config']['assets_dir']) ) {
                    $dirs[5] = $version_info['config']['assets_dir'];
                }
            }

            $failed = [];
            $list = glob($app->get_destdir().DIRECTORY_SEPARATOR.'*');
            foreach( $list as $one ) {
                $basename = basename($one);
                if( is_file($one) ) {
                    $relative = substr($one,strlen($app->get_destdir())+1);
                    if( !is_writable($one) ) $failed[] = $relative;
                }
                else if( in_array($basename,$dirs) ) {
                    $b = utils::is_directory_writable($one,TRUE);
                    if( !$b ) {
                        $tmp = utils::get_writable_error();
                        $failed = array_merge($failed,utils::get_writable_error());
                    }
                }
            }

        if( $version_info ) {
            // during an upgrade (not a freshen), config file must be writable
            if( $action == 'upgrade' ) {
                $obj = new boolean_test('config_writable',is_writable($version_info['config_file']));
                $obj->required = 1;
                $obj->fail_key = 'fail_config_writable';
                $tests[] = $obj;

                if( version_compare($version_info['version'],'2.2') < 0 ) {
                    $dir = $app->get_destdir().DIRECTORY_SEPARATOR.'assets';
                    if( is_dir($dir) ) {
                        $obj = new boolean_test('assets_dir_exists',FALSE);
                        $obj->fail_key = 'fail_assets_dir';
                        $obj->warn_key = 'fail_assets_dir';
                        $tests[] = $obj;
                    }
                }
            }
        } else {
            $dest = $app->get_destdir();
            $config_file = $dest.DIRECTORY_SEPARATOR.'config.php';
            $obj = new boolean_test('config_writable',!is_file($config_file) || is_writable($config_file));
            $obj->required = 1;
            $obj->fail_key = 'fail_config_writable';
            $tests[] = $obj;

            $is_dir_empty = function(string $dir) : bool
            {
                $dir = trim($dir);
                if( !$dir ) return FALSE;  // fail on invalid dir
                if( !is_dir($dir) ) return TRUE; // pass on dir not existing yet
                $files = glob($dir.DIRECTORY_SEPARATOR.'*' );
                if( !count($files) ) return TRUE; // no files yet.
                if( count($files) > 1 ) return FALSE; // morre than one file
                // trivial check for index.htm[l]
                $bn = strtolower(basename($files[0]));
                return fnmatch('index.htm?',$bn);
            };
            $res = true;
            if( $res && !$is_dir_empty($dest.DIRECTORY_SEPARATOR.'tmp/cache') ) $res = false;
            if( $res && !$is_dir_empty($dest.DIRECTORY_SEPARATOR.'tmp/templates_c') ) $res = false;

            $obj = new boolean_test('tmp_dirs_empty',$res);
            $obj->required = 1;
            $obj->fail_key = 'fail_tmp_dirs_empty';
            $tests[] = $obj;
        }

        // recommended test ... remote_url
        $obj = new boolean_test('remote_url',test_remote_file('https://www.cmsmadesimple.org/latest_version.php',10,'cmsmadesimple'));
        $obj->fail_key = 'fail_remote_url';
        $obj->warn_key = 'fail_remote_url';
        $tests[] = $obj;

/*      now run the tests
        if all tests pass
           display warm fuzzy message
           user can continue
        else if a required test fails
           display failed tests (or all tests for verbose mode)
           user can't continue
        otherwise
           display failed tests (or all tests for verbose mode)
           user can continue
*/
        $can_continue = TRUE;
        $tests_failed = FALSE;
        $fails = [];
        for( $i = 0, $n = count($tests); $i < $n; $i++ ) {
            $res = $tests[$i]->run();
            if( $res == test_base::TEST_FAIL ) {
                $tests_failed = TRUE;
                $fails[] = $tests[$i];
                if( $tests[$i]->required ) {
                    $can_continue = FALSE;
                }
                else {
                    $tests[$i]->status = test_base::TEST_WARN;
                }
            }
        }

        $cachable = ( !in_array($tests[$ctest],$fails) ) ? 'auto' : 'file';
        $wiz->set_data('cachemode',$cachable);

        if( !$verbose ) $tests = $fails;
        return [$tests_failed,$can_continue];
    }

    protected function process()
    {
        $action = $this->get_wizard()->get_data('action');
        if( $action == 'freshen' ) {
            $url = $this->get_wizard()->step_url(5);
        }
        else {
            $url = $this->get_wizard()->next_url();
        }
        utils::redirect($url);
    }

    protected function display()
    {
        parent::display();
        $config = get_app()->get_config();
        $verbose = $config['verbose'] ?? 0;
        $informational = [];
        $tests = [];
        list($tests_failed,$can_continue) = $this->perform_tests($verbose,$informational,$tests);

        $smarty = smarty();
        $smarty->assign('tests',$tests)
         ->assign('tests_failed',$tests_failed)
         ->assign('can_continue',$can_continue)
         ->assign('verbose',$verbose)
         ->assign('retry_url',$_SERVER['REQUEST_URI']);
        if( $verbose ) $smarty->assign('information',$informational);
        // TODO button(s) and processing for enable verbose mode etc.

        $smarty->display('wizard_step3.tpl');
        $this->finish();
    }
} // class
