<?php
namespace cms_installer\wizard;

use cms_installer\wizard\wizard_step;
use Exception;
use Throwable;
use const cms_installer\ICMSSAN_FILE;
use const cms_installer\ICMSSAN_NONPRINT;
use function cms_installer\de_specialize;
use function cms_installer\get_app;
use function cms_installer\joinpath;
use function cms_installer\lang;
use function cms_installer\redirect;
use function cms_installer\sanitizeVal;
use function cms_installer\smarty;
use function cms_installer\specialize;
use function cms_installer\translator;

class wizard_step5 extends wizard_step
{
    private $_params;

    public function run()
    {
        $wiz = $this->get_wizard();

        $tz = date_default_timezone_get();
        if (!$tz) {
            @date_default_timezone_set('UTC');
        }

        $this->_params = ['languages' => []];

        $action = $wiz->get_data('action');
        if ($action == 'install') {
            $this->_params += ['sitename' => '', 'supporturl' => ''];
        }
        // get saved data
        $tmp = $wiz->get_data('config');
        if ($tmp) {
            $this->_params = array_merge($this->_params, $tmp);
        }
        $lang = translator()->get_selected_language();
        if ($lang != 'en_US') {
            $this->_params['languages'] = [$lang];
        }

        $tmp = $wiz->get_data('sessionchoices');
        if ($tmp) {
            $this->_params = array_merge($this->_params, $tmp);
        }
        return parent::run();
    }

    protected function process()
    {
        $app = get_app();
        $config = $app->get_config();

        if (isset($_POST['samplecontent'])) {
            $this->_params['samplecontent'] = filter_input(INPUT_POST, 'samplecontent', FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($_POST['sitename'])) {
            $this->_params['sitename'] = de_specialize($_POST['sitename']); //no other content-strip
        }

        if (isset($_POST['supporturl'])) {
            $url = de_specialize($_POST['supporturl']);
            $pass = filter_var($url, FILTER_VALIDATE_URL); //TODO allow rawurlencode()'d invalid chars c.f. CMSMS\urlencode(), CMSMS\Url::sanitize()
            // the test above barfs for non-ASCII chars
            if (!$pass && preg_match('/[\x80-\xff]/', $url) &&
                // fallback to a rough check (ignores dodgy chars)
                parse_url($url, PHP_URL_SCHEME) && parse_url($url, PHP_URL_HOST)) {
                $pass = true;
            }
            if ($pass) {
                $this->_params['supporturl'] = $url;
            } else {
                unset($this->_params['supporturl']);
            }
        }

        if (isset($_POST['languages'])) {
            $tmp = [];
            foreach ($_POST['languages'] as $lang) {
                // see http://www.unicode.org/reports/tr35/#Identifiers
                $tmp[] = sanitizeVal($lang, ICMSSAN_NONPRINT);
            }
            $this->_params['languages'] = $tmp;
        }

        if (isset($_POST['wantedextras'])) {
            //record the selected members of $app_config['extramodules']
            $tmp = [];
            foreach ($_POST['wantedextras'] as $name) {
                $tmp[] = sanitizeVal($name, ICMSSAN_FILE); //module-identifier corresponds to a foldername BUT no space(s) ?
            }
            $this->_params['wantedextras'] = $tmp;
        }

        $wiz = $this->get_wizard();
        try {
            $this->validate($this->_params);
            $wiz->merge_data('sessionchoices', $this->_params);

            if ($config['nofiles']) {
                $url = $wiz->step_url(8);
            } elseif ($wiz->get_data('action') == 'install') {
                $url = $wiz->next_url();
            } else {  // upgrade or freshen
                $url = $wiz->step_url(7);
            }
            redirect($url);
        } catch (Throwable $t) {
            $s = $this->forge_url;
            if ($s) {
                $s = '<br />'.lang('error_notify', $s);
            }
            $smarty = smarty();
            $smarty->assign('error', $t->GetMessage().$s);
        }
    }

    protected function display()
    {
        parent::display();
        $action = $this->get_wizard()->get_data('action');

        $smarty = smarty();
        $smarty->assign('action', $action);

        $app = get_app();
        $config = $app->get_config();
        $raw = $config['verbose'] ?? 0;
//        $v = ($raw === null) ? $this->get_wizard()->get_data('verbose',0) : (int)$raw;
        $smarty->assign('verbose', (int)$raw);

        if ($action == 'install') {
            $raw = $config['sitename'] ?? null;
            $v = ($raw === null) ? $this->_params['sitename'] : trim($raw);
            $smarty->assign('sitename', specialize($v));

            $raw = $config['supporturl'] ?? null;
            $v = ($raw === null) ? '' : trim($raw);
            $smarty->assign('supporturl', specialize($v)); // should have no effect

            $smarty->assign('yesno', ['0' => lang('no'), '1' => lang('yes')]);
        } elseif ($action == 'upgrade') {
            // if pertinent upgrade
            $version_info = $this->get_wizard()->get_data('version_info');
            if (version_compare($version_info['version'], '3.0') < 0) {
                $raw = $app->get_dest_version();
                if (version_compare($raw, '3.0') >= 0) { //should always be true, here
                    $raw = $config['supporturl'] ?? null;
                    $v = ($raw === null) ? '' : trim($raw);
                    $smarty->assign('supporturl', specialize($v));
                }
            }
        }

        $languages = $app->get_language_list();
        unset($languages['en_US']);
        if ($languages && $action == 'upgrade') {
            // exclude installed languages
            $v = (!empty($config['admin_path'])) ? $config['admin_path'] : 'admin';
            $fp = joinpath($app->get_destdir(), $v, 'lang', 'ext', '');
            $raw = glob($fp.'*.php', GLOB_NOSORT); // filesystem path
            if ($raw) {
                foreach ($languages as $key => $v) {
                    $tmp = $fp.$key.'.php';
                    if (in_array($tmp, $raw)) {
                        unset($languages[$key]);
                    }
                }
            }
        }
        $smarty->assign('language_list', $languages);
        $raw = $config['selectlangs'] ?? null;
        if ($raw) {
            if (is_array($raw)) {
                array_walk($raw, function(&$v) {
                    $v = trim($v);
                });
                $v = $raw;
            } else {
                $v = [trim($raw)];
            }
        } else {
            $v = [];
        }
        $smarty->assign('languages', $v);

        if ($action != 'freshen') {
            $raw = $app->get_noncore_modules();
            if ($raw && $action == 'upgrade') {
                // exclude installed modules
                $fp = $app->get_destdir();
                //TODO if not using assets/modules for non-core modules
                $v = (!empty($config['assets_path'])) ? $config['assets_path'] : 'assets';
                $dirs = [
                    $fp.DIRECTORY_SEPARATOR.$v.DIRECTORY_SEPARATOR.'modules',
                    $fp.DIRECTORY_SEPARATOR.'modules',
                    ];
                foreach ($raw as $key => $v) {
                    foreach ($dirs as $dir) {
                        $fp = $dir.DIRECTORY_SEPARATOR.$v;
                        if (is_dir($fp) && is_file($fp.DIRECTORY_SEPARATOR.$v.'.module.php')) {
                            unset($raw[$key]);
                            break;
                        }
                    }
                }
            }
            if ($raw) {
                $modules = array_combine($raw, $raw);
            } else {
                $modules = null;
            }
            $smarty->assign('modules_list', $modules);
            $smarty->assign('modules_sel', (($modules) ? $config['selectmodules'] ?? null : null));
        }

        $smarty->display('wizard_step5.tpl');
        $this->finish();
    }

    private function validate($params)
    {
        $action = $this->get_wizard()->get_data('action');
        if ($action == 'install') {
            if (empty($params['sitename'])) {
                throw new Exception(lang('error_nositename'));
            }
        }
    }
} // class
