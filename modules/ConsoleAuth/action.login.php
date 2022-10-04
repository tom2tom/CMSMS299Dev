<?php
/*
ConsoleAuth module action - login : generate a login page.
Copyright (C) 2018-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\Crypto;
use CMSMS\FormUtils;
use CMSMS\NlsOperations;
use CMSMS\ScriptsMerger;
use CMSMS\StylesMerger;

//if (some worthy test fails) exit;

//extra variables for included method
//$login_url = $this->create_action_url($id,'login'); // BAD back to here
$login_url = $config['admin_url'].'/login.php';

require_once __DIR__ . DIRECTORY_SEPARATOR . 'method.process.php';

if (!empty($csrf_key)) {
	$_SESSION[$csrf_key] = $csrf = Crypto::random_string(16, true); //encryption-grade hash not needed
}

$lang = NlsOperations::get_default_language();
if (($p = strpos($lang,'_')) !== false) {
	$lang = substr($lang,0,$p);
}
$enc = NlsOperations::get_encoding();

$fp = __DIR__.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR;
$fn = 'module';

if (NlsOperations::get_language_direction() == 'rtl') {
	$langdir = 'rtl';
	if (is_file($fp.$fn.'-rtl.min.css')) {
		$fn .= '-rtl.min';
	} elseif (is_file($fp.$fn.'-rtl.css')) {
		$fn .= '-rtl';
	}
} else {
	$langdir = 'ltr';
	if (is_file($fp.$fn.'.min.css')) {
		$fn .= '.min';
	}
}
$fn .= '.css';

$incs = cms_installed_jquery(true, true, true, true);

$csm = new StylesMerger();
$csm->queue_matchedfile('normalize.css', 1);
$csm->queue_file($incs['jquicss'], 2);
//$csm->queue_file($fp.$fn, 3); NOPE contains relative-URLS
$out = $csm->page_content();

$baseurl = $this->GetModuleURLPath();
$out .= <<<EOS
  <link rel="stylesheet" href="{$baseurl}/css/{$fn}">

EOS;

$jsm = new ScriptsMerger();
$jsm->queue_file($incs['jqcore'], 1);
//if (CMS_DEBUG) {
$jsm->queue_file($incs['jqmigrate'], 1); //in due course, omit this or keep if (CMS_DEBUG)
//}
$jsm->queue_file($incs['jqui'], 1);
$jsm->queue_matchedfile('login.js', 3, __DIR__.DIRECTORY_SEPARATOR.'lib');
$out .= $jsm->page_content();

// generate this here, not via {form_start}, to work around forced incorrect formaction-value
$extras = [
 'id' => $id,
 'action' => 'login',
 'extraparms' => [
  CMS_JOB_KEY => 1,
]];
if (isset($csrf)) {
	$extras['extraparms']['csrf'] = $csrf;
}

$start_form = FormUtils::create_form_start($this, $extras);

$tpl = $this->GetTemplateObject('login.tpl');
$tpl->assign([
 'mod' => $this,
 'actionid' => $id,
 'lang_code' => $lang,
 'lang_dir' => $langdir,
 'encoding' => $enc,
 'header_includes' => $out,
 'start_form' => $start_form,
 'admin_url' => $config['admin_url'],
 'module_url' => $baseurl,
 'forgot_url' => 'login.php?forgotpw=1',
 'csrf' => $csrf ?? null,
 'changepwhash' => $changepwhash ?? null,
 'username' => $username ?? null,
 'password' => $password ?? null,
 'message' => $infomessage ?? null,
 'warning' => $warnmessage ?? null,
 'error' => $errmessage ?? null,
]);
// 'forgot_url' => $login_url.'&'.$id.'forgotpw=1',
//'theme' => $theme_object,
//'theme_root' => $theme_object->root_url,

try {
	$tpl->display();
} catch (Throwable $t) {
	echo '<div class="error">'.$t->getMessage().'</div>';
}
