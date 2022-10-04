<?php
namespace cms_installer\wizard;

//use function cms_installer\decrypt_creds;
//use function cms_installer\get_app;
use cms_installer\wizard\wizard_step;
use Error;
use Exception;
use mysqli;
use RuntimeException;
use StupidPass\StupidPass;
use Throwable;
use const cms_installer\ICMSSAN_PATH;
use const cms_installer\ICMSSAN_PUNCT;
use const cms_installer\ICMSSAN_PURE;
use function cms_installer\de_specialize;
use function cms_installer\lang;
use function cms_installer\redirect;
use function cms_installer\sanitizeVal;
use function cms_installer\smarty;
use function cms_installer\specialize_array;

class wizard_step4 extends wizard_step
{
    private $_params;
    private $_dbms_options;

    public function __construct()
    {
        if (!extension_loaded('mysqli')) {
            throw new Exception(lang('error_nodatabases'));
        }
        parent::__construct();

        $tz = date_default_timezone_get();
        if (!$tz) {
            $tz = 'UTC';
            @date_default_timezone_set('UTC');
        }
        $this->_params = [
            'db_type' => 'mysqli',
            'db_credentials' => '',
            'db_hostname' => 'localhost',
            'db_name' => '',
            'db_username' => '',
            'db_password' => '',
            'db_port' => '',
            'db_prefix' => 'cms_',
            'query_var' => '',
            'timezone' => $tz,
//            'samplecontent'=>FALSE,
            'admin_path' => '',
            'assets_path' => '',
            'userplugins_path' => '',
        ];

        $wiz = $this->get_wizard();
        // get saved data
        $tmp = $wiz->get_data('config');
        if ($tmp) {
            $this->_params = array_merge($this->_params, $tmp);
        }

        $action = $wiz->get_data('action');
        if ($action == 'upgrade') {  //install|freshen skips this step
            // read config data from config.php for these actions
//            $destdir = get_app()->get_destdir();
            $config = [];
            $config_file = $wiz->get_data('version_info')['config_file'];
            require_once $config_file;
//          $this->_params['db_type'] = /*$config['db_type'] ?? $config['dbms'] ??*/ 'mysqli';
/* too hard to robustly handle encrypted credentials here in the installer
            if( isset($config['db_credentials']) ) {
                $props = decrypt_creds($config['db_credentials'], 'f6e771d0s6r771q0'); // MUST conform with Crypto::pwolish()
                $this->_params = array_merge($this->_params,$props);
            } else {
*/
            $this->_params['db_hostname'] = $config['db_hostname'];
            $this->_params['db_username'] = $config['db_username'];
            $this->_params['db_password'] = $config['db_password'];
            $this->_params['db_name'] = $config['db_name'];
            if (!empty($config['db_port']) || is_numeric($config['db_port'])) {
                $this->_params['db_port'] = (int)$config['db_port'];
            }
//            }
            $this->_params['db_prefix'] = $config['db_prefix'];
            if (!empty($config['timezone'])) {
                $this->_params['timezone'] = $config['timezone'];
            }
            if (!empty($config['query_var'])) {
                $this->_params['query_var'] = $config['query_var'];
            }
        }
    }

    protected function process()
    {
        $this->_params['db_type'] = 'mysqli';
//      if( isset($_POST['db_type']) ) $this->_params['db_type'] = sanitizeVal($_POST['db_type'], ICMSSAN_PURE);
        // These db properties are set externally, so CMSMS data policies are irrelevant.
        // Stored in file and never displayed other than during this intaller,
        // so risk-mitigation limited to [de]specialize()
        // Minimal cleanup  i.e. no sanitizeVal() for externally-prescribed values
        //N/A   \cms_installer\de_specialize_array($_POST);
        if (isset($_POST['db_hostname'])) {
            $this->_params['db_hostname'] = trim(de_specialize($_POST['db_hostname']));
        }
        if (isset($_POST['db_name'])) {
            $this->_params['db_name'] = trim(de_specialize($_POST['db_name']));
        }
        if (isset($_POST['db_username'])) {
            $this->_params['db_username'] = trim(de_specialize($_POST['db_username']));
        }
        if (isset($_POST['db_password'])) {
            $this->_params['db_password'] = de_specialize($_POST['db_password']);
        }
        if (isset($_POST['db_port'])) {
            $this->_params['db_port'] = filter_input(INPUT_POST, 'db_port', FILTER_SANITIZE_NUMBER_INT);
        }
        // and the rest are self-managed data ...
        if (isset($_POST['db_prefix'])) {
            $this->_params['db_prefix'] = sanitizeVal(de_specialize($_POST['db_prefix']));
        }
        $this->_params['timezone'] = sanitizeVal($_POST['timezone'], ICMSSAN_PUNCT);
        if (isset($_POST['query_var'])) {
            $this->_params['query_var'] = sanitizeVal(de_specialize($_POST['query_var']), ICMSSAN_PURE);
        } // ?? ICMSSAN_NONPRINT

        foreach (['admin_path', 'assets_path', 'userplugins_path'] as $key) {
            if (isset($_POST[$key])) {
                $s = trim($_POST[$key], ' \/\'"');
                if ($s) {
                    $this->_params[$key] = sanitizeVal($s, ICMSSAN_PATH);
                } else {
                    $this->_params[$key] = '';
                }
            }
        }

        try {
            $this->validate($this->_params);
            $this->get_wizard()->merge_data('config', $this->_params);
            $url = $this->get_wizard()->next_url();
            redirect($url);
        } catch (RuntimeException $t) {
            //redisplay with credentials etc warning
            $smarty = smarty();
            $s = $t->GetMessage();
            if ($s) {
                $smarty->assign('message', $s);
            }
            $this->get_wizard()->merge_data('config', $this->_params);
            $tmp = $this->_params;
            specialize_array($tmp);
            $smarty->assign('config', $tmp);
        } catch (Error $t) {
            // non-fatal warning to be displayed
            $smarty = smarty();
            $s = $t->GetMessage();
            if ($s) {
                $smarty->assign('message', $s);
            }
            $this->get_wizard()->merge_data('config', $this->_params);
            $tmp = $this->_params;
            specialize_array($tmp);
            $smarty->assign('config', $tmp);
        } catch (Throwable $t) {
            $s = $this->forge_url;
            if ($s) {
                $s = '<br>'.lang('error_notify', $s);
            }
            $smarty = smarty();
            $smarty->assign('error', $t->GetMessage().$s);
        }
    }

    protected function display()
    {
        parent::display();
        $smarty = smarty();

        $tmp = timezone_identifiers_list();
        if (!is_array($tmp)) {
            throw new Exception(lang('error_tzlist'));
        }
        $tmp2 = array_combine(array_values($tmp), array_values($tmp));
        $smarty->assign('timezones', array_merge(['' => lang('none')], $tmp2));
//        $smarty->assign('db_types',$this->_dbms_options);
        $smarty->assign('action', $this->get_wizard()->get_data('action'));
        $raw = $this->_params['verbose'] ?? 0;
//        $v = ($raw === null) ? $this->get_wizard()->get_data('verbose',0) : (int)$raw;
        $smarty->assign('verbose', (int)$raw);
        $tmp = $this->_params;
        specialize_array($tmp);
        $smarty->assign('config', $tmp);
        $smarty->display('wizard_step4.tpl');

        $this->finish();
    }

    /**
     * Lite password check and possible warning
     * @param array $config
     * @return array of strings, empty or each member one line of an error message
     */
    private function check_pass(array $config) : array
    {
        $data = $this->get_wizard()->get_data('sessionchoices');
        // obvious references to the environment (company, hostname, username, etc)
        $avoids = ['CMSMS', 'cmsms', $config['db_name'], $config['db_prefix']];
        $tmp = $data['sitename'] ?? '';
        if ($tmp) {
            $avoids[] = str_replace([' ', '_'], ['', ''], strtolower($tmp));
            $avoids[] = $tmp;
        }
        // overridden error messages
        $messages = [
          'common' => lang('warn_pwcommon'),
          'environ' => lang('warn_pwenviron'),
          'strength' => lang('warn_pwweak'),
        ];
        // custom evaluation options
        $options = [
         'maxlen-guessable-test' => 16,
         'strength' => 'Strong',
        ];
        $checker = new StupidPass(0, $avoids, '', $messages, $options);
        if (!$checker->validate($config['db_password'])) {
            $warn = [lang('warn_pwdiscuss')];
            $errs = $checker->getErrors();
            foreach ($errs as $msg) {
                $warn[] = $msg;
            }
            return $warn;
        }
        return [];
    }

    /**
     * Authority check and possible warning
     * @param array $config
     * @param mysqli $mysqli database interface object
     * @return array of strings, empty or each member one line of an error message
     */
    private function check_auth(array $config, mysqli $mysqli) : array
    {
        $nm = $mysqli->real_escape_string($config['db_username']);
        $res = $mysqli->query("SHOW GRANTS FOR `$nm`");
        if ($res) {
            $warn = [];
            $places = ['*.*', '`'.$config['db_name'].'`.*', '`'.$config['db_username'].'`@`'.$config['db_hostname'].'`', "''@''"];
            $matches = [];
            $auth = [];
            foreach ($res as $row) {
                $auth[] = reset($row);
            }
            $res->close();
            foreach ($auth as $row) {
                $show = '';
                preg_match('/.+ON\s+(\S+)/i', $row, $matches);
                if ($matches[1] && in_array(trim($matches[1]), $places)) {
                    foreach ([
                      ['GRANT', 'ALL', 'FILE', 'SUPER', 'PROXY', 'GRANT OPTION'], //various TODO
                      ['CREATE', 'USER', 'ROLE', 'TABLESPACE'], //various TODO
                      ['DROP', 'USER', 'ROLE'], //various
                      ['SHOW', 'DATABASES'], //various
                    ] as $chk) {
                        $p = strpos($row, $chk[0]);
                        if ($p === 0) {
                            $p += strlen($chk[0]);
                            foreach ($chk as $i => $nm) {
                                if ($i > 0 && strpos($row, $nm, $p) !== false) {
                                    $show .= $chk[0].' '.$nm.', ';
                                }
                            }
                        }
                    }
                }
                if ($show) {
                    if (!$warn) {
                        $warn[] = lang('warn_pwperms');
                    }
                    $warn[] = rtrim($show, ', ');
                }
            }
            return $warn;
        }
        return [];
    }

    private function validate(&$config)
    {
        $action = $this->get_wizard()->get_data('action');
//      if( empty($config['db_type']) ) throw new Exception(lang('error_nodbtype'));
        if (empty($config['db_hostname'])) {
            throw new Exception(lang('error_nodbhost'));
        }
        if (empty($config['db_name'])) {
            throw new Exception(lang('error_nodbname'));
        }
        if (empty($config['db_username'])) {
            throw new Exception(lang('error_nodbuser'));
        }
        // password must exist, and may validly contain anything
        // (provided the encoding is ASCII, or else compatible with the db setting
        if (!isset($config['db_password']) || !($config['db_password'] || is_numeric($config['db_password']))) {
            throw new Exception(lang('error_nodbpass'));
        }
        if (empty($config['db_prefix']) && $action == 'install') {
            throw new Exception(lang('error_nodbprefix'));
        }
        $s = (!empty($config['query_var'])) ? trim($config['query_var']) : '';
        // query_var used in URL's, arguably we could accept anything
        // (un-encodable) for those i.e. alphanum or -._~:/?#[]@!$&'()*+,;%=
        if ($s && !preg_match('~^[A-Za-z0-9_.]*$~', $s)) {
            throw new Exception(lang('error_invalidqueryvar'));
        } elseif ($s) {
            $config['query_var'] = $s;
        }
        if (empty($config['timezone'])) {
            throw new Exception(lang('error_notimezone'));
        }
        $s = trim($config['timezone']);
        if (!(preg_match('~^[A-Za-z]+/[A-Za-z_\-/]+$~', $s) || strcasecmp($s, 'UTC') == 0)) {
            throw new Exception(lang('error_invalidtimezone'));
        }
        $all_timezones = timezone_identifiers_list();
        if (!in_array($s, $all_timezones)) {
            throw new Exception(lang('error_invalidtimezone'));
        } else {
            $config['timezone'] = $s;
        }

        // try a test connection
        if (empty($config['db_port'])) {
            $mysqli = new mysqli($config['db_hostname'], $config['db_username'],
                $config['db_password'], $config['db_name']);
        } else {
            $mysqli = new mysqli($config['db_hostname'], $config['db_username'],
                $config['db_password'], $config['db_name'], (int)$config['db_port']);
        }
        if (!$mysqli) {
            throw new Exception(lang('error_createtable'));
        }
        if ($mysqli->connect_errno) {
            throw new Exception($mysqli->connect_error.' : '.lang('error_createtable'));
        }
        $s = $mysqli->server_info; // e.g. '5.6.49', '10.3.27-MariaDB'
        $t = preg_replace('/[^\d\.]/', '', $s);
        if (stripos($s, 'Maria') === false) {
            if (version_compare($t, '5.5') < 0) { // released late 2010 BUT 5.6 would be nicer!
                throw new Exception(lang('error_dbversion', $s));
            }
        } elseif (version_compare($t, '5.5') < 0) { // BUT (MySQL-5.6-compatible) 10.0 would be nicer!
            throw new Exception(lang('error_dbversion', $s));
        }

        $sql = 'CREATE TABLE '.$config['db_prefix'].'_dummyinstall (i INT)';
        if (!$mysqli->query($sql)) {
            throw new Exception(lang('error_createtable'));
        }
        $sql = 'DROP TABLE '.$config['db_prefix'].'_dummyinstall';
        if (!$mysqli->query($sql)) {
            throw new Exception(lang('error_droptable'));
        }

        if ($action == 'install') {
            // check whether some typical core tables exist
            $sql = 'SELECT content_id FROM '.$config['db_prefix'].'content LIMIT 1';
            if (($res = $mysqli->query($sql)) && $res->num_rows > 0) {
                throw new Exception(lang('error_cmstablesexist'));
            }
            $sql = 'SELECT module_name FROM '.$config['db_prefix'].'modules LIMIT 1';
            if (($res = $mysqli->query($sql)) && $res->num_rows > 0) {
                throw new Exception(lang('error_cmstablesexist'));
            }
        }

        if (!isset($_POST['warndone'])) {
            // not previously warned (and maybe such is needed ?)
            $warn1 = $this->check_pass($config);
            $warn2 = $this->check_auth($config, $mysqli);
            if ($warn1 || $warn2) {
                $smarty = smarty();
                $smarty->assign('dowarn', 1) // block repetition
                    ->assign('loosepass', $warn1)
                    ->assign('looseperms', $warn2);
                throw new RuntimeException(''); //redisplay with warning
            }
        }
    }
} // class
