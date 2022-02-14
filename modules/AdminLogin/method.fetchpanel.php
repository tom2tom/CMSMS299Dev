<?php
/*
AdminLogin module method to generate login-panel content
Copyright (C) 2018-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
BUT WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\Crypto;
use CMSMS\SingleItem;

//variables for included method
$config = SingleItem::Config();
$login_url = $config['admin_url'].'/login.php';

require_once __DIR__ . DIRECTORY_SEPARATOR . 'method.process.php';

$csrf = Crypto::random_string(16, true); //encryption-grade hash not needed
$_SESSION[$csrf_key] = $csrf;

$smarty = SingleItem::Smarty();

$tpl = $this->GetTemplateObject('login-form.tpl');
$tpl->assign([
	'mod' => $this,
	'actionid' => '',
	'loginurl' => 'login.php',
	'forgoturl' => 'login.php?forgotpw=1',
	'csrf' => $csrf,
	'changepwhash' => $changepwhash ?? '',
	'iserr' => !empty($errmessage),
]);
if (!empty($tplvars)) { $tpl->assign($tplvars); }
$data = ['form' => $tpl->fetch(),'csrf' => $csrf];
//some results from the included method also for upstream
if (!empty($tplvars)) { $data += $tplvars; }
if (!empty($infomessage)) { $data['infomessage'] = $infomessage; }
if (!empty($warnmessage)) { $data['warnmessage'] = $warnmessage; }
if (!empty($errmessage)) { $data['errmessage'] = $errmessage; }
if (!empty($changepwhash)) { $data['changepwhash'] = $changepwhash; }
if (!empty($changepwtoken)) { $data['changepwtoken'] = $changepwtoken; }
