<?php
/*
CMSMailer module defaultadmin action
Copyright (C) 2004-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\Crypto;
use CMSMS\Utils;
use CMSMailer\Mailer;

if(!isset($gCms)) exit;

if( !$this->CheckPermission('Modify Mail Preferences') ||
     $this->CheckPermission('Modify Site Preferences')) exit;

/*
if (isset($params['cancel'])) {
    $this->Redirect($id, 'defaultadmin', $returnid);
}
*/

if (isset($params['submit']) || isset($params['sendtest'])) {
    // TODO validate
    $this->SetPreference('mailer', $params['mailer']);
    $this->SetPreference('charset', $params['charset']);
    $this->SetPreference('host', $params['host']);
    $this->SetPreference('port', (int)$params['port']);
    $this->SetPreference('from', $params['from']);
    $this->SetPreference('fromuser', $params['fromuser']);
    $this->SetPreference('sendmail', $params['sendmail']);
    $this->SetPreference('timeout', (int)$params['timeout']);
    $this->SetPreference('smtpauth', (bool)$params['smtpauth']);
    $this->SetPreference('secure', $params['secure']);
    $this->SetPreference('username', $params['username']);
    $this->SetPreference('password', base64_encode(Crypto::encrypt_string($params['password']));
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
}

$s1 = json_encode($this->Lang('confirm_sendtestmail'));
$s2 = json_encode($this->Lang('confirm_settings'));
$js = <<<EOS
<script type="text/javascript">
//<![CDATA[
$(function() {
 $('[name="{$id}sendtest"]').on('click activate', function(ev) {
  ev.preventDefault();
  cms_confirm_btnclick(this, $s1);
  return false;
 }
 $('[name="{$id}submit"]').on('click activate', function(ev) {
  ev.preventDefault();
  cms_confirm_btnclick(this, $s2);
  return false;
 }
});
//]]>
</script>

EOS;
add_page_foottext($js);

//TODO $mailprefs = $this->GetPreference();
$extras = get_secure_param_array(); //TODO all hidden items in form
$tpl = $this->GetTemplateObject('defaultadmin.tpl');

$tpl->assign([
 'startform' => $this->CreateFormStart($id, 'defaultadmin', $returnid), //TODO FormUtils::whatever
 'extraparms' => $extras,
 'title_charset' => $this->Lang('charset'),
 'help_charset' => 'info_charset',
 'value_charset' => $this->GetPreference('charset','utf-8'),
 'title_mailer' => $this->Lang('mailer'),
 'help_mailer' => 'info_mailer',
 'value_mailer' => $this->GetPreference('mailer', 'mail'),
 'opts_mailer' => [
      'mail' => 'mail',
      'sendmail' => 'sendmail',
      'smtp' => 'smtp',
  ],
 'title_host' => $this->Lang('host'),
 'help_host' => 'info_host',
 'value_host' => $this->GetPreference('host'),
 'title_port' => $this->Lang('port'),
 'help_port' => 'info_port',
 'value_port' => $this->GetPreference('port'),
 'title_from' => $this->Lang('from'),
 'help_from' => 'info_from',
 'value_from' => $this->GetPreference('from'),
 'title_fromuser' =>$this->Lang('fromuser'),
 'help_fromuser' => 'info_fromuser',
 'value_fromuser' => $this->GetPreference('fromuser'),
 'title_sendmail' =>$this->Lang('sendmail'),
 'help_sendmail' => 'info_sendmail',
 'value_sendmail' => $this->GetPreference('sendmail'),
 'title_timeout' => $this->Lang('timeout'),
 'help_timeout' => 'info_timeout',
 'value_timeout' => $this->GetPreference('timeout'),
 'title_smtpauth' => $this->Lang('smtpauth'),
 'help_smtpauth' => 'info_smtpauth',
 'value_smtpauth' => $this->GetPreference('smtpauth', 0),
 'title_secure' => $this->Lang('secure'),
 'help_secure' => 'info_secure',
 'value_secure' => $this->GetPreference('secure', ''),
 'opts_secure' => [
      $this->Lang('none') => '',
      $this->Lang('ssl') => 'ssl',
      $this->Lang('tls') => 'tls',
  ],
 'title_username' => $this->Lang('username'),
 'help_username' => 'info_username',
 'value_username' => $this->GetPreference('username'),
 'title_password' => $this->Lang('password'),
 'help_password' => 'info_password',
 'value_password' => Crypto::decrypt_string(base64_decode($this->GetPreference('password'))), 
 'title_testaddress' => $this->Lang('testaddress'),
 'help_testaddress' => 'info_testaddress',
]);

$tpl->display();
return '';
