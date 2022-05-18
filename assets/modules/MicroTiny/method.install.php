<?php
/*
MicroTiny module installation procedure
Copyright (C) 2009-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of the Microtiny module for CMS Made Simple
<http://dev.cmsmadesimple.org/projects/microtiny>

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
use CMSMS\Lone;
use CMSMS\UserParams;
use MicroTiny\Profile;

if (empty($this) || !($this instanceof MicroTiny)) exit;
//$installing = AppState::test(AppState::INSTALL);
//if (!($installing || $this->CheckPermission('Modify Modules'))) exit;

$tp = cms_join_path($config['uploads_path'], 'images');
if (!is_dir($tp)) {
	mkdir($tp, 0771, true);
}
$fp = cms_join_path(__DIR__, 'images', 'uTiny-demo.png');
copy($fp, $tp.DIRECTORY_SEPARATOR.'uTiny-demo.png');

$fp = cms_join_path(__DIR__, 'lib', 'tinymce', 'tinymce.min.js');
if (is_file($fp)) {
	$val = $this->GetModuleURLPath().'/lib/tinymce';
	$hash = '';
} else {
	$alert = new TranslatableAlert('Modify Site Preferences');
	$alert->name = 'MicroTiny Setup Needed';
	$alert->module = 'MicroTiny';
	$alert->titlekey = 'postinstall_title';
	$alert->msgkey = 'postinstall_notice';
	$alert->save();

	$val = 'https://cdnjs.cloudflare.com/ajax/libs/tinymce/4.9.11';//TODO support preconnection, SRI hash
	$hash = 'sha512-3tlegnpoIDTv9JHc9yJO8wnkrIkq7WO7QJLi5YfaeTmZHvfrb1twMwqT4C0K8BLBbaiR6MOo77pLXO1/PztcLg==';
}
$this->SetPreference('source_url', $val);
$this->SetPreference('source_sri', $hash);
$this->SetPreference('skin_url', ''); // use default
$this->SetPreference('disable_cache', false);

//best to avoid module-specific class autoloading during installation
$fp = cms_join_path(__DIR__,'lib','class.Profile.php');
require_once $fp;

$obj = new Profile([
	'name'=>MicroTiny::PROFILE_FRONTEND,
	'label'=>$this->Lang('profile_frontend'),
	'menubar'=>false,
	'allowimages'=>false,
	'showstatusbar'=>false,
	'allowresize'=>false,
	'system'=>true,
]);
$obj->save();

$obj = new Profile([
	'name'=>MicroTiny::PROFILE_ADMIN,
	'label'=>$this->Lang('profile_admin'),
	'menubar'=>true,
	'allowimages'=>true,
	'showstatusbar'=>true,
	'allowresize'=>true,
	'system'=>true,
]);
$obj->save();

$me = $this->GetName();
$val = AppParams::get('wysiwyg');
if (!$val) {
	AppParams::set('wysiwyg', $me);
}
$val = AppParams::get('frontendwysiwyg');
if (!$val) {
	AppParams::set('frontendwysiwyg', $me);
}

$users = Lone::get('UserOperations')->GetList();
foreach ($users as $uid => $uname) {
	$val = UserParams::get_for_user($uid, 'wysiwyg');
	if (!$val) {
		UserParams::set_for_user($uid, 'wysiwyg', $me);
		UserParams::set_for_user($uid, 'wysiwyg_type', '');
		UserParams::set_for_user($uid, 'wysiwyg_theme', ''); // TODO
	}
}
