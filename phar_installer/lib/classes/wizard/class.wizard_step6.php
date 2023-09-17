<?php
namespace cms_installer\wizard;

use cms_installer\wizard\wizard_step;
use Exception;
use RuntimeException;
use StupidPass\StupidPass;
use Throwable;
use const cms_installer\ICMSSAN_ACCOUNT;
use const cms_installer\ICMSSAN_NONPRINT;
use function cms_installer\de_specialize;
use function cms_installer\get_app;
use function cms_installer\is_email;
use function cms_installer\lang;
use function cms_installer\redirect;
use function cms_installer\sanitizeVal;
use function cms_installer\smarty;
use function cms_installer\specialize_array;

class wizard_step6 extends wizard_step
{
    private $_adminacct;

    public function __construct()
    {
        parent::__construct();
        $suf = str_pad(mt_rand(0, 999), 3, '0', STR_PAD_LEFT);
        $this->_adminacct = [
          'username' => 'admin'.$suf,
          'password' => '',
          'repeatpw' => '',
          'emailaddr' => '',
          'emailsend' => false,
         ];
        $tmp = $this->get_wizard()->get_data('adminaccount');
        if ($tmp) {
            $this->_adminacct = array_merge($this->_adminacct, $tmp);
        }
    }

    protected function process()
    {
        //N/A   \cms_installer\de_specialize_array($_POST);
        if (isset($_POST['username'])) {
            $this->_adminacct['username'] = de_specialize($_POST['username']);
        }
        if (isset($_POST['emailaddr'])) {
            $this->_adminacct['emailaddr'] = de_specialize($_POST['emailaddr']);
        }
        if (isset($_POST['password'])) {
            $this->_adminacct['password'] = de_specialize($_POST['password']);
        }
        if (isset($_POST['repeatpw'])) {
            $this->_adminacct['repeatpw'] = de_specialize($_POST['repeatpw']);
        }
        if( !empty($_POST['emailsend']) ) {
            $this->_adminacct['emailsend'] = (int)$_POST['emailsend'];
        } else {
            $this->_adminacct['emailsend'] = 0;
        }
        try {
            $this->validate($this->_adminacct);
            $this->get_wizard()->set_data('adminaccount', $this->_adminacct);
            $url = $this->get_wizard()->next_url();
            redirect($url);
        } catch (RuntimeException $t) {
            //redisplay with credentials
            $s = $t->GetMessage();
            if ($s) {
                $smarty = smarty();
                $smarty->assign('message', $s);
            }
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

        $app = get_app();
        $config = $app->get_config();

        $raw = $config['verbose'] ?? 0;
        $smarty->assign('verbose', (int)$raw);

        $tmp = $this->_adminacct;
        $raw = $config['adminlogin'] ?? null;
        if ($raw !== null) {
            $tmp['username'] = trim($raw);
        }
        $raw = $config['adminemail'] ?? null;
        if ($raw !== null) {
            $tmp['emailaddr'] = trim($raw);
        }
        $raw = $config['adminpw'] ?? null;
        if ($raw !== null) {
            $tmp['password'] = trim($raw); // TODO trim() maybe bad
        }
        specialize_array($tmp);
        $smarty->assign('account', $tmp);
        $smarty->display('wizard_step6.tpl');

        $this->finish();
    }

    // lite 'n lazy username check
    // TODO c.f. UserOperations::UsernameCheck()
    private function valid_name($str)
    {
        $data = $this->get_wizard()->get_data('sessionchoices');
        // obvious references to the environment (company,hostname,username,etc)
        $avoids = ['CMSMS', 'cmsms'];
        $tmp = $data['sitename'] ?? '';
        if ($tmp) {
            $avoids[] = str_replace([' ', '_'], ['', ''], strtolower($tmp));
            $avoids[] = $tmp;
        }
        // don't bother with overridden (or translated) error messages
        $messages = [];
        // custom evaluation options
        $options = [
          'disable' => ['upper', 'lower', 'numeric', 'special', 'strength'],
          'maxlen-guessable-test' => 12,
        ];
        $warn = [];
        $checker = new StupidPass(48, $avoids, '', $messages, $options);
        if (!$checker->validate($str)) {
            $warn[] = lang('error_adminacct_username');
            $errs = $checker->getErrors();
            foreach ($errs as $msg) {
                $warn[] = str_replace('Password', 'Name', $msg);
            }
        }

        $patns = [
         '~[<>\[\]|{}\\/\^]~', //unacceptable printables
         '~[!?]{3,}|[^\w!?]{2,}|_{2,}~', //multiple non-word-chars
         '~[\x00-\x1F\x7F]~', //\xA0\u{1680}\u{180E}\u{2000}-\u{200B}\u{202F}\u{205F}\u{3000}\u{FEFF}]', //unprintables, unusual whitespace
//         '~[\x80-\x9F\u{2000}-\u{200F}\u{2028}-\u{202F}\u{3000}\u{E000}-\u{F8FF}]', //private use UTF-8
// '//u' valid UTF8
         '~[?&]+[^=]+=[^&]+~', //URI-like
         '~[\. ](?:com|org|net|info|gov|asn|biz|info|uk|kz|ru|ir|кз|pt|br)\b~i', //common domain-names
         '~^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$~', //[in]valid IPv4
         '~^(?:[0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}$~', //valid IPv6 sans zero-only words
        ];
        foreach ($patns as $regex) {
            if (!preg_match($regex, $str)) {
                continue;
            }
            if (!$warn) {
                $warn[] = lang('error_adminacct_username');
            }
            $warn[] = 'Name contains unacceptable character(s) or pattern(s)';
            break;
        }

        if ($warn) {
            return $warn;
        }
        return true;
    }

    // lite password check
    // returns true or array of messages
    private function valid_pass($str)
    {
        $data = $this->get_wizard()->get_data('sessionchoices');
        // obvious references to the environment (company,hostname,username,etc)
        $avoids = ['CMSMS', 'cmsms', $this->_adminacct['username']];
        $tmp = $data['sitename'] ?? '';
        if ($tmp) {
            $avoids[] = str_replace([' ', '_'], ['', ''], strtolower($tmp));
            $avoids[] = $tmp;
        }
        // don't bother with overridden (or translated) error messages
        $messages = [];
        // custom evaluation options
        $options = [
          'maxlen-guessable-test' => 16,
          'strength' => 'Strong',
        ];
        $checker = new StupidPass(64, $avoids, '', $messages, $options);
        if ($checker->validate($str)) {
            return true;
        }
        $msgs = [-1 => lang('error_adminacct_password')] +
            array_values($checker->getErrors());
        return $msgs;
    }

    private function validate($acct)
    {
        if ($acct['username'] !== null) {
            if (!$acct['username']) {
                throw new Exception(lang('error_adminacct_username'));
            }
            $tmp = sanitizeVal($acct['username'], ICMSSAN_ACCOUNT);
            if ($tmp !== trim($acct['username'])) {
                throw new Exception(lang('error_adminacct_username'));
            }
            if (($error1 = $this->valid_name($tmp)) !== true) {
                $smarty = smarty();
                $smarty->assign('tellname', $error1);
            }
        }

        if ($acct['emailaddr'] !== null) {
            if ($acct['emailaddr']) {
                $tmp = trim(filter_var($acct['emailaddr'], FILTER_SANITIZE_EMAIL)); // TODO too strict
                if ($tmp !== $acct['emailaddr'] || !is_email($acct['emailaddr'])) {
                    throw new Exception(lang('error_adminacct_emailaddr'));
                }
            }
        }

        if ($acct['password'] !== null) {
            if (!$acct['password']) {
                throw new Exception(lang('error_adminacct_password'));
            }
            $tmp = sanitizeVal($acct['password'], ICMSSAN_NONPRINT);
            if ($tmp !== $acct['password']) {
                throw new Exception(lang('error_adminacct_password'));
            }
            $tmp = sanitizeVal($acct['repeatpw'], ICMSSAN_NONPRINT);
            if ($tmp !== $acct['password']) {
                throw new Exception(lang('error_adminacct_repeatpw'));
            }
            if (($error2 = $this->valid_pass($tmp)) !== true) {
                if (!isset($smarty)) {
                    $smarty = smarty();
                }
                $smarty->assign('tellpass', $error2);
            }
        }
        if ((isset($error1) && is_array($error1)) || (isset($error2) && is_array($error2))) {
            $smarty->assign('doerr', 1);
            throw new RuntimeException('');
        }
    }
} // class
