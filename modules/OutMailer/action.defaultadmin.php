<?php
/*
OutMailer module defaultadmin action
Copyright (C) 2004-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple module OutMailer.

The OutMailer module is free software; you may redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published
by the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

The OutMailer module is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <http://www.gnu.org/licenses/licenses.html#AGPL>.
*/

use OutMailer\Mailer;
use OutMailer\PrefCrypter;
use OutMailer\Utils;
use CMSMS\AppParams;
use CMSMS\Crypto;
use CMSMS\FormUtils;
//use CMSMS\ResourceMethods;
use CMSMS\Utils as AppUtils;
use function CMSMS\de_specialize;
use function CMSMS\specialize;

if (empty($this) || !($this instanceof OutMailer)) exit;
/*
$Apw = PrefCrypter::decrypt_preference(PrefCrypter::MKEY);
$val0 = bin2hex(random_bytes(32));
$val2 = base64_encode(Crypto::encrypt_string($val0, $vpw));
$X = $CRASH;
*/
$pmod = $this->CheckPermission('Modify Site Preferences') ||
    $this->CheckPermission('Modify Mail Preferences');
$pgates = false; // NOT YET IMPLEMENTED $pmod || $this->CheckPermission('Modify Email Gateways');
$psee = false; // NOT YET IMPLEMENTED $this->CheckPermission('View Email Gateways');
$ptpl = $this->CheckPermission('Modify Email Templates');
if (!($pmod || $pgates || $psee || $ptpl)) exit;

if (!empty($params['activetab'])) {
    $activetab = $params['activetab'];
} else {
    $activetab = 'internal';
}

$pw = '';
// names and types of OutMailer preferences
$mailprefs = [
 'mailer' => 1,
 'from' => 1,
 'fromuser'  => 1,
 'host' => 1,
 'charset' => 1,
 'password' => 5,
 'port' => 2,
 'secure' => 1,
 'sendmail' => 1,
 'smtpauth' => 3,
 'timeout' => 2,
 'username' => 1,
//OAuth-related extras for this mailer
 'oauthProvider' => 1,
 'oauthClientId' => 1,
 'oauthClientSecret' => 5,
 'oauthUserEmail' => 1, //same as from?
/*/spool-related extras for this mailer
 'batchgap' => 2,
 'batchsize' => 2,
*/
 'single' => 2,
];

if (isset($params['apply'])/* || isset($params['sendtest'])*/) {
    // TODO sanitize & validate inputs
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
                        $val = base64_encode(Crypto::encrypt_string(trim($params[$key]))); // default P/W
                        break;
                    case 5:
                        if (empty($pw)) {
                            $pw = PrefCrypter::decrypt_preference(PrefCrypter::MKEY);
                        }
                        $val = base64_encode(Crypto::encrypt_string(trim($params[$key]), $pw)); // custom P/W
                        break;
                    default:
                        $val = trim($params[$key]);
                        break;
                    }
                } else {
                    $val = $params[$key];
                }
//                unset($params[$key]); //don't record locally
            }
        }
        unset($val);
        if ($core) {
            AppParams::set('mailprefs', serialize($core));
        }
    }

    foreach ($mailprefs as $key => &$val) {
        if (isset($params[$key])) {
            switch ($val) {
            case 2:
                $tmp = (int)$params[$key];
                break;
            case 3:
                $tmp = (bool)$params[$key];
                break;
            case 4:
                $tmp = base64_encode(Crypto::encrypt_string(trim($params[$key]))); //default P/W
                break;
            case 5:
                if (empty($pw)) {
                    $pw = PrefCrypter::decrypt_preference(PrefCrypter::MKEY);
                }
                $tmp = base64_encode(Crypto::encrypt_string(trim($params[$key]), $pw));
                break;
            default:
                $tmp = trim($params[$key]);
                break;
            }
            $this->SetPreference($key, $tmp);
        }
    }
    unset($val, $pw);
    $pw = null; // assist garbage collector
}

if (isset($params['sendtest'])) {
    $messages = [];
    $errors = [];
    if ($params['testaddress'] == '') {
        $errors[] = $this->Lang('error_notestaddress');
    } else {
        //ignore invalid chars in the email
        //BUT PHP's FILTER_VALIDATE_EMAIL mechanism is not entirely reliable - see notes at https://www.php.net/manual/en/function.filter-var.php
//      $addr = filter_var($params['testaddress'], FILTER_SANITIZE_EMAIL);
        $addr = de_specialize(trim($params['testaddress']));
        if ($addr && !is_email($addr)) {
            $errors[] = $this->Lang('error_badtestaddress');
        } elseif ($addr) {
            try {
                $mailer = new Mailer();
                $mailer->AddAddress($addr);
                $mailer->IsHTML(true);
                $mailer->SetBody($this->Lang('mailtest_body', CMS_ROOT_URL));
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

    $themeObject = AppUtils::get_theme_object();
    foreach ($messages as $str) {
        $themeObject->RecordNotice('info', $str);
    }
    foreach ($errors as $str) {
       $themeObject->RecordNotice('error', $str);
    }
    $activetab = 'test';
}

if ($pmod) {
    if (isset($params['masterpass'])) {
        require_once __DIR__.DIRECTORY_SEPARATOR.'method.savesettings.php';
        $activetab = 'settings';
    }
}

if ($pgates) {
    if (isset($params['platform'])) {
        require_once __DIR__.DIRECTORY_SEPARATOR.'method.savegates.php';
        $activetab = 'gates';
    }

    Utils::refresh_platforms($this);
    $gatesdata = Utils::get_platforms_full($this);
    if ($gatesdata) {
        $gatesnames = [];
        foreach ($gatesdata as $key => &$one) {
            $gatesnames[$key] = $one['obj']->get_name();
            $one = $one['obj']->get_setup_form();
        }
        unset($one);
        asort($gatesnames, SORT_STRING); // no unicode in names?
        $current = $this->GetPreference('platform');
        if (!$current) {
            $current = $params['platform'] ?? reset($gatesnames); //TODO
        }
    } else {
        $gatesnames = [];
        $current = '';
    }
//    $addurl1 = $this->CreateLink($id, 'opengate', '', '', ['gate_id' => -1], '', true);
    $addurl = FormUtils::create_action_link($this, [
     'getid' => $id,
     'action' => 'opengate',
     'params' => ['gate_id' => -1],
     'onlyhref' => true,
     'format' => 2,
    ]);
    $urlext = get_secure_param();
}

//TODO deploy a ScriptsMerger, for easier CSP compliance
//$jsm = new CMSMS\ScriptsMerger();
$baseurl = CMS_ASSETS_URL.'/js';
$baseurl2 = $this->GetModuleURLPath();
$js = <<<EOS
 <script type="text/javascript" src="{$baseurl}/jquery-inputCloak.min.js"></script>
EOS;
add_page_headtext($js);

$s1 = addcslashes($this->Lang('confirm_sendtestmail'), "'\n\r");
$s2 = addcslashes($this->Lang('confirm_settings'), "'\n\r");
$s3 = addcslashes($this->Lang('confirm_property'), "'\n\r");
//TODO some js is permission-specific
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
 on_mailer();
 var dbg = $('.cloaked');
 $('.cloaked').inputCloak({
   type:'see1',
   symbol:'\u25CF'
 });
 $('.platform_panel').hide();
 var sel = $('#platform'),
   cg = sel.val();
 $('#'+cg).show();
 sel.on('change', function() {
   $('.platform_panel').hide();
   cg = $(this).val();
   $('#'+cg).show();
 });
 $('#mailer').on('change', on_mailer);
 $('[name="{$id}sendtest"]').on('click activate', function(ev) {
  ev.preventDefault();
  cms_confirm_btnclick(this, '$s1');
  return false;
 });
 $('[name="{$id}submit"]').on('click activate', function(ev) {
  ev.preventDefault();
  cms_confirm_btnclick(this, '$s2');
  return false;
 });
 $('[name$="~delete"]').on('click activate', function(ev) {
  ev.preventDefault();
  var cb = $(this).closest('fieldset').find('input[name$="~sel"]:checked');
  if (cb.length > 0) {
   cms_confirm_btnclick(this, '$s3');
  }
  return false;
 });
 $('.gatedata').find('tbody').sortable().disableSelection()
  .find('tr').removeAttr('onmouseover').removeAttr('onmouseout')
  .on('mouseover', function() {
    var now = $(this).attr('class');
    $(this).attr('class', now+'hover');
  })
  .on('mouseout', function() {
    var now = $(this).attr('class');
    var to = now.indexOf('hover');
    $(this).attr('class', now.substring(0,to));
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

if (empty($activetab)) { $activetab = 'internal'; }

$mailers = [
 'mail' => 'PHP',
 'sendmail' => 'Sendmail',
 'smtp' => 'SMTP',
];
if ($config['develop_mode']) {
    $mailers += [
     'file' => $this->Lang('file'),
     'dummy' => $this->Lang('testonly'),
    ];
}

$singl_opts = [
 ['value'=>0,'label'=>$this->Lang('never')],
 ['value'=>1,'label'=>$this->Lang('always')],
 ['value'=>2,'label'=>$this->Lang('nocopies')],
];
$val = (int)$mailprefs['single'];
$singl_opts[$val] += ['checked'=> true];

if (empty($pw)) {
    $pw = PrefCrypter::decrypt_preference(PrefCrypter::MKEY);
}

$val = (string)$mailprefs['password'];
$smptpass = Crypto::decrypt_string(base64_decode($val), $pw);
//$useit = specialize($smptpass); ?
//ditto for other displayed strings?

//$extras = []; //TODO all 'other' hidden items in each form

$tpl = $smarty->createTemplate($this->GetTemplateResource('defaultadmin.tpl')); //,null,null,$smarty);

$tpl->assign([
 'startform' => FormUtils::create_form_start($this, ['id' => $id, 'action' => 'defaultadmin']),
 'extraparms' => null, //$extras,
 'tab' => $activetab,
 'pmod' => $pmod,
 'pgates' => $pgates,
 'psee' => $psee,
 'ptpl' => $ptpl,
 'title_charset' => $this->Lang('charset'),
 'value_charset' => $mailprefs['charset'],
 'title_mailer' => $this->Lang('mailer'),
 'value_mailer' => $mailprefs['mailer'],
 'opts_mailer' => $mailers,
 'title_host' => $this->Lang('host'),
 'value_host' => $mailprefs['host'],
 'title_port' => $this->Lang('port'),
 'value_port' => $mailprefs['port'],
 'title_from' => $this->Lang('from'),
 'value_from' => $mailprefs['from'],
 'title_fromuser' =>$this->Lang('fromuser'),
 'value_fromuser' => $mailprefs['fromuser'],
 'title_sendmail' =>$this->Lang('sendmail'),
 'value_sendmail' => $mailprefs['sendmail'],
 'title_timeout' => $this->Lang('timeout'),
 'value_timeout' => $mailprefs['timeout'],
 'title_smtpauth' => $this->Lang('smtpauth'),
 'value_smtpauth' => $mailprefs['smtpauth'],
 'title_secure' => $this->Lang('secure'),
 'value_secure' => $mailprefs['secure'],
 // see SmtpTransport const's which match tbese values
 'opts_secure' => [
     '' => $this->Lang('none'),
     'stls' => $this->Lang('starttls'),
     'tls' => $this->Lang('tls'),
     'ssl' => $this->Lang('ssl'),
  ],
 'title_username' => $this->Lang('username'),
 'value_username' => $mailprefs['username'],
 'title_password' => $this->Lang('password'),
 'value_password' => $smptpass,
/* if batching is supported ...
 'title_batchgap' => $this->Lang('batchgap'),
 'opts_batchgap' => [
     0 => $this->Lang('none'),
     3600 => $this->Lang('hours_1'),
     14400 => $this->Lang('hours_counted', '4'),
     43200 => $this->Lang('hours_counted', '12'),
     86400 + 3600 => $this->Lang('days_1'), // +1hr in case there's DST in place
 ],
 'value_batchgap' => $mailprefs['batchgap'],
 'title_batchsize' => $this->Lang('batchsize'),
 'value_batchsize' => $mailprefs['batchsize'],
*/
 'title_single' => $this->Lang('single'),
 'opts_single' => $singl_opts,
 'title_testaddress' => $this->Lang('testaddress'),
]);

//TODO perm(s) for changing oauth settings
//TODO relevance of email setting for oauth
$provider1 = (string)$mailprefs['oauthProvider'];
//TODO get & assign actual supported identity providers
if (in_array($provider1, [
    '',
    'fb',
    'ghub',
    'ggl',
    'insta',
    'in'
])) {
    $provider2 = '';
} else {
    $provider2 = $provider1;
    $provider1 = 'custom';
}
$val = (string)$mailprefs['oauthClientSecret'];
$secret = Crypto::decrypt_string(base64_decode($val), $pw);

$tpl->assign('opts_provider', [
    '' => $this->Lang('na'),
    'fb' => 'Facebook',
    'ghub' => 'Github',
    'ggl' => 'Google',
    'insta' => 'Instagram',
    'in' => 'LinkedIn',
    'custom' => $this->Lang('other'),
])
 ->assign('title_provider', $this->Lang('lbl_provider'))
 ->assign('value_provider', $provider1)
 ->assign('title_otherprovider', $this->Lang('lbl_otherprovider'))
 ->assign('value_otherprovider', $provider2)
 ->assign('title_oclient', $this->Lang('lbl_client'))
 ->assign('value_oclient', (string)$mailprefs['oauthClientId'])
 ->assign('title_osecret', $this->Lang('lbl_secret'))
 ->assign('value_osecret', $secret)
 ->assign('title_oemail', $this->Lang('lbl_email'))
 ->assign('value_oemail', (string)$mailprefs['oauthUserEmail']);

if ($pgates) {
    $tpl->assign([
     'gatesnames' => $gatesnames,
     'platform' => $current,
     'gatesdata' => $gatesdata,
     'addurl' => $addurl,
    ]);
}

if ($pmod) {
    $tpl->assign([
     'title_modpassword' => $this->Lang('modpassword'),
     'value_modpassword' => $pw, // >> textarea, no need for specialize()
    ]);
}

$tpl->display();
