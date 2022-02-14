<?php
/*
OutMailer module installation process
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple module OutMailer.

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\AdminAlerts\TranslatableAlert;
use CMSMS\AppParams;
use CMSMS\AppState;
use CMSMS\Crypto;
use CMSMS\SingleItem;
use OutMailer\PrefCrypter;

if (empty($this) || !($this instanceof OutMailer)) exit;
$installer_working = AppState::test(AppState::INSTALL);
if (!($installer_working || $this->CheckPermission('Modify Modules'))) exit;

$dict = $db->NewDataDictionary(); //old NewDataDictionary($db);
$taboptarray = ['mysqli' => 'ENGINE MyISAM CHARACTER SET utf8mb4'];

$flds = '
id I UNSIGNED AUTO KEY,
alias C(50),
title C(40) NOTNULL,
description X(1500),
enabled I1 UNSIGNED NOTNULL DEFAULT 1,
active I1 UNSIGNED NOTNULL DEFAULT 0
';
$sqlarray = $dict->CreateTableSQL(CMS_DB_PREFIX.'module_outmailer_platforms', $flds, $taboptarray);
$res = $dict->ExecuteSQLArray($sqlarray);

$flds = '
id I UNSIGNED AUTO KEY,
platform_id I UNSIGNED NOTNULL UKEY,
title C(40) NOTNULL,
plainvalue C(500),
encvalue B(1000),
apiname C(100) UKEY,
signature C(100),
encrypt I1 UNSIGNED NOTNULL DEFAULT 0,
enabled I1 UNSIGNED NOTNULL DEFAULT 1,
apiorder I2 NOTNULL DEFAULT -1
';
$sqlarray = $dict->CreateTableSQL(CMS_DB_PREFIX.'module_outmailer_props', $flds, $taboptarray);
$res = $dict->ExecuteSQLArray($sqlarray);

// Permissions
$this->CreatePermission('Modify Mail Preferences', 'Modify OutMailer Module Settings');
$this->CreatePermission('Modify Email Gateways', 'Modify Email Gateway Settings');
$this->CreatePermission('View Email Gateways', 'View Email Gateways');
//$this->CreatePermission('Modify Email Templates', 'Modify Email Gateway Templates');

// Preferences
$init = new class extends PrefCrypter
{
    public function set_seed()
    {
        $s = parent::get_muid().parent::SKEY;
        $sk = hash('fnv1a64', $s);
        $t = base_convert(random_bytes(256), 16, 36); // 16-or-so random alphanums
        $raw = Crypto::encrypt_string($t, hash(parent::HASHALGO, $s));
        $value = rtrim(base64_encode($raw), '=');
        set_module_param(parent::MODNAME, $sk, $value);
    }
};
$init->set_seed();
$pw = base64_decode('U29tZSB3b29ob28gdGhpbmd5IGdvZXMgaGVyZSE=').Crypto::random_string(8, true, true);
PrefCrypter::encrypt_preference(PrefCrypter::MKEY, $pw);

$host = $_SERVER['SERVER_NAME'] ?? gethostname() ?? php_uname('n') ?? 'localhost.localdomain';
$path = trim(ini_get('sendmail_path'));
if (!$path || stripos($path, 'sendmail') === false) {
    $path = '/usr/sbin/sendmail';
}
$mailprefs = [
    'mailer' => 'mail',
    'host' => 'mail.'.$host,
    'port' => 25,
    'from' => 'donotreply@'.$host,
    'fromuser'  => 'Do Not Reply',
    'sendmail' => $path,
    'smtpauth' => false,
    'username' => '',
    'password' => '',
    'secure' => '',
    'timeout' => 60,
    'charset' => 'utf-8',
    'single'=> 0,
];
$val = AppParams::get('mailprefs');
if ($val) {
    $parms = unserialize($val, ['allowed_classes' => false]);
    if ($parms['password'] !== '') {
        $parms['password'] = Crypto::decrypt_string(base64_decode($parms['password']));
    }
    $mailprefs = array_merge($mailprefs, $parms);
}
$mailprefs['password'] = base64_encode(Crypto::encrypt_string($mailprefs['password'], $pw));
unset($pw); $pw = null;
foreach ($mailprefs as $key => $val) {
    $this->SetPreference($key, $val);
}

$this->SetPreference('platform', null); //TODO alias
//$this->SetPreference('hourlimit', 100);
//$this->SetPreference('daylimit', 1000);
//$this->SetPreference('logsends', true);
//$this->SetPreference('logdays', 7);
//$this->SetPreference('logdeliveries', true);
//$this->SetPreference('lastcleared', time());

$this->CreateEvent('EmailDeliveryReported');

// semi-permanent alias for back-compatibility
$ops = SingleItem::ModuleOperations();
$ops->set_module_classname('CMSMailer', get_class($this));

if( $installer_working && 1 ) { // TODO && is new site
    $alert = new TranslatableAlert('Modify Site Preferences');
    $alert->name = 'Email Setup Needed';
    $alert->module = 'OutMailer';
    $alert->titlekey = 'postinstall_title';
    $alert->msgkey = 'postinstall_notice';
    $alert->save();
}
