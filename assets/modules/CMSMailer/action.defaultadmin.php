<?php
/*
CMSMailer module defaultadmin action
Copyright (C) 2004-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple module CMSMailer.

This CMSMailer module is free software; you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published
by the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

This CMSMailer module is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <http://www.gnu.org/licenses/licenses.html#AGPL>.
*/

use CMSMailer\Mailer;
use CMSMS\App;
use CMSMS\AppParams;
use CMSMS\Crypto;
use CMSMS\Utils;

if (!isset($gCms) || !($gCms instanceof App)) exit;
if (!($this->CheckPermission('Modify Mail Preferences') ||
      $this->CheckPermission('Modify Site Preferences'))) exit;
// names and types of CMSMailer preferences
$mailprefs = [
 'mailer' => 1,
 'from' => 1,
 'fromuser'  => 1,
 'host' => 1,
 'charset' => 1,
 'password' => 4,
 'port' => 2,
 'secure' => 1,
 'sendmail' => 1,
 'smtpauth' => 3,
 'timeout' => 2,
 'username' => 1,
];

if (isset($params['submit'])/* || isset($params['sendtest'])*/) {
    // TODO sanitize, validate
    // first update core mail prefs, if any
    $val = AppParams::get('mailprefs');
    if ($val) {
        $core = unserialize($val, ['allowed_classes' => false]);
        foreach ($core as $key => &$val) {
            if (isset($params[$key])) {
                if (isset($mailprefs[$key])) {
                    switch ($mailprefs[$key]) {
                    case 2:
                        $val = (int)$params[$key];
                        break;
                    case 3:
                        $val = (bool)$params[$key];
                        break;
                    case 4:
                        $val = base64_encode(Crypto::encrypt_string(trim($val)));
                        break;
                    default:
                        $val = trim($params[$key]);
                        break;
                    }
                } else {
                    $val = $params[$key];
                }
                unset($params[$key]); //don't record locally
            }
        }
        unset($val);
        if ($core) { AppParams::set('mailprefs', serialize($core)); }
    }

    foreach ($mailprefs as $key => &$val) {
        if (isset($params[$key])) {
            switch ($val) {
            case 2:
                $val = (int)$params[$key];
                break;
            case 3:
                $val = (bool)$params[$key];
                break;
            case 4:
                $val = base64_encode(Crypto::encrypt_string(trim($val)));
                break;
            default:
                $val = trim($params[$key]);
                break;
            }
            $this->SetPreference($key, $val);
        }
    }
    unset($val);
}

if (isset($params['sendtest'])) {
    $messages = []; $errors = [];
    if ($params['testaddress'] == '') {
        $errors[] = $this->Lang('error_notestaddress');
    } else {
        $addr = filter_var($params['testaddress'], FILTER_SANITIZE_EMAIL);
        if (!is_email($addr)) {
            $errors[] = $this->Lang('error_badtestaddress');
        } else {
            try {
                $mailer = new Mailer();
                $mailer->AddAddress($addr);
                $mailer->IsHTML(true);
                $mailer->SetBody($this->Lang('mailtest_body'));
                $mailer->SetSubject($this->Lang('mailtest_subject'));
                $mailer->Send();
                if ($mailer->IsError()) {
                    $errors[] = $mailer->GetErrorInfo();
                } else {
                    $messages[] = $this->Lang('mailtest_success');
                }
            } catch (Throwable $t) {
                $errors[] = $t->GetMessage();
            }
        }
    }

    $themeObject = Utils::get_theme_object();
    foreach ($messages as $str) {
        $themeObject->RecordNotice('info', $str);
    }
    foreach ($errors as $str) {
       $themeObject->RecordNotice('error', $str);
    }
    $activetab = 'test';
}

$baseurl = $this->GetModuleURLPath();
$js = <<<EOS
 <script type="text/javascript" src="{$baseurl}/lib/js/jquery-inputCloak.min.js"></script>
EOS;
add_page_headtext($js);

$s1 = json_encode($this->Lang('confirm_sendtestmail'));
$s2 = json_encode($this->Lang('confirm_settings'));
$js = <<<EOS
<script type="text/javascript">
//<![CDATA[
function on_mailer() {
 switch ($('#mailer').val()) {
  case 'mail':
   $('.set_smtp').find('input,select').prop('disabled',true);
   $('.set_sendmail').find('input,select').prop('disabled',true);
   break;
  case 'smtp':
   $('.set_sendmail').find('input,select').prop('disabled',true);
   $('.set_smtp').find('input,select').prop('disabled',false);
   break;
  case 'sendmail':
   $('.set_smtp').find('input,select').prop('disabled',true);
   $('.set_sendmail').find('input,select').prop('disabled',false);
   break;
 }
}
$(function() {
 $('#password').inputCloak({
   type:'see4',
   symbol:'\u25CF'
 });
 on_mailer();
 $('#mailer').on('change', on_mailer);
 $('[name="{$id}sendtest"]').on('click activate', function(ev) {
  ev.preventDefault();
  cms_confirm_btnclick(this, $s1);
  return false;
 });
 $('[name="{$id}submit"]').on('click activate', function(ev) {
  ev.preventDefault();
  cms_confirm_btnclick(this, $s2);
  return false;
 });
});
//]]>
</script>

EOS;
add_page_foottext($js);

foreach ($mailprefs as $key => &$val) {
    $val = $this->GetPreference($key);
}
unset($val);
//any core properties prevail
$val = AppParams::get('mailprefs');
if ($val) {
    $mailprefs = array_merge($mailprefs, unserialize($val, ['allowed_classes' => false]));
}
$mailprefs['password'] = Crypto::decrypt_string(base64_decode($mailprefs['password']));

if (empty($activetab)) { $activetab = 'settings'; }

$extras = get_secure_param_array(); //TODO all hidden items in form
if (0) { //TODO if light-module
    $tpl = $this->GetTemplateObject('defaultadmin.tpl');
} else {
    $tpl = $smarty->createTemplate($this->GetTemplateResource('defaultadmin.tpl')); //,null,null,$smarty);
}

$tpl->assign([
 'startform' => $this->CreateFormStart($id, 'defaultadmin', $returnid), //TODO FormUtils::whatever
 'extraparms' => $extras,
 'tab' => $activetab,
 'title_charset' => $this->Lang('charset'),
 'help_charset' => 'info_charset',
 'value_charset' => $mailprefs['charset'],
 'title_mailer' => $this->Lang('mailer'),
 'help_mailer' => 'info_mailer',
 'value_mailer' => $mailprefs['mailer'],
 'opts_mailer' => [
      'mail' => 'mail',
      'sendmail' => 'sendmail',
      'smtp' => 'smtp',
  ],
 'title_host' => $this->Lang('host'),
 'help_host' => 'info_host',
 'value_host' => $mailprefs['host'],
 'title_port' => $this->Lang('port'),
 'help_port' => 'info_port',
 'value_port' => $mailprefs['port'],
 'title_from' => $this->Lang('from'),
 'help_from' => 'info_from',
 'value_from' => $mailprefs['from'],
 'title_fromuser' =>$this->Lang('fromuser'),
 'help_fromuser' => 'info_fromuser',
 'value_fromuser' => $mailprefs['fromuser'],
 'title_sendmail' =>$this->Lang('sendmail'),
 'help_sendmail' => 'info_sendmail',
 'value_sendmail' => $mailprefs['sendmail'],
 'title_timeout' => $this->Lang('timeout'),
 'help_timeout' => 'info_timeout',
 'value_timeout' => $mailprefs['timeout'],
 'title_smtpauth' => $this->Lang('smtpauth'),
 'help_smtpauth' => 'info_smtpauth',
 'value_smtpauth' => $mailprefs['smtpauth'],
 'title_secure' => $this->Lang('secure'),
 'help_secure' => 'info_secure',
 'value_secure' => $mailprefs['secure'],
 'opts_secure' => [
     '' => $this->Lang('none'),
     'ssl' => $this->Lang('ssl'),
     'tls' => $this->Lang('tls')
  ],
 'title_username' => $this->Lang('username'),
 'help_username' => 'info_username',
 'value_username' => $mailprefs['username'],
 'title_password' => $this->Lang('password'),
 'help_password' => 'info_password',
 'value_password' => $mailprefs['password'],
 'title_testaddress' => $this->Lang('testaddress'),
 'help_testaddress' => 'info_testaddress',
]);

$tpl->display();
return '';
