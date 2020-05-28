<?php
/*
CMSMailer module installation process
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

use CMSMS\AppParams;
use CMSMS\Crypto;

if (!function_exists('cmsms')) exit;

// Permissions
$this->CreatePermission('Modify Mail Preferences', 'Modify CMSMailer Module Settings');

// Preferences
$host = $_SERVER['SERVER_NAME'] ?? gethostname() ?? php_uname('n') ?? 'localhost.localdomain';
$path = ini_get('sendmail_path');
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
];
$val = AppParams::get('mailprefs');
if ($val) {
    $mailprefs = array_merge($mailprefs, unserialize($val, ['allowed_classes' => false]));
}
$mailprefs['password'] = base64_encode(Crypto::encrypt_string($mailprefs['password']));

foreach ($mailprefs as $key => $val) {
    $this->SetPreference($key, $val);
}
