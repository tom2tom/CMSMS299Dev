<?php
/*
CMSMailer module installation process
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple module CMSMailer.

This CMSMailer module is free software; you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published
by the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

This CMSMailer module is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

See the GNU Affero General Public License
<http://www.gnu.org/licenses/licenses.html#AGPL> for more details.
*/

use CMSMS\AppParams;
use CMSMS\Crypto;
use CMSMailer\PrefCrypter;

if (!function_exists('cmsms')) exit;

$dict = $db->NewDataDictionary(); //old NewDataDictionary($db);
$taboptarray = ['mysqli' => 'ENGINE MyISAM CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci'];

$flds = '
id I(2) UNSIGNED AUTO KEY,
alias C(48),
title C(128) NOTNULL,
description C(255),
enabled I(1) NOTNULL DEFAULT 1,
active I(1) NOTNULL DEFAULT 0
';
if ($this->mod->platformed) {
    $sqlarray = $dict->CreateTableSQL(CMS_DB_PREFIX.'module_cmsmailer_platforms', $flds, $taboptarray);
    $res = $dict->ExecuteSQLArray($sqlarray);

    $flds = '
id I(4) UNSIGNED AUTO KEY,
platform_id I(2) UNSIGNED NOTNULL,
title C(96),
value C(255),
encvalue B(1023),
apiname C(64),
signature C(64),
encrypt I(1) NOTNULL DEFAULT 0,
enabled I(1) NOTNULL DEFAULT 1,
apiorder I(1) NOTNULL DEFAULT -1
';
    $sqlarray = $dict->CreateTableSQL(CMS_DB_PREFIX.'module_cmsmailer_props', $flds, $taboptarray);
    $res = $dict->ExecuteSQLArray($sqlarray);
}
// Permissions
$this->CreatePermission('Modify Mail Preferences', 'Modify CMSMailer Module Settings');
if ($this->mod->platformed) {
    $this->CreatePermission('ModifyEmailGateways', 'Modify Email Gateway Settings');
    $this->CreatePermission('ViewEmailGateways', 'View Email Gateways');
    //$this->CreatePermission('ModifyEmailTemplates', 'Modify Email Gateway Templates');
}
// Preferences
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
    'fromuser'  =>  'Do Not Reply',
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
if ($this->mod->platformed) {
    $this->SetPreference('platform', null); //TODO alias
}
//$this->SetPreference('hourlimit', 100);
//$this->SetPreference('daylimit', 1000);
//$this->SetPreference('logsends', true);
//$this->SetPreference('logdays', 7);
//$this->SetPreference('logdeliveries', true);
//$this->SetPreference('lastcleared', time());

$this->CreateEvent('EmailDeliveryReported');
