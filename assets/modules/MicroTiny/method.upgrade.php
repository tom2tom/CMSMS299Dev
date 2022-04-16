<?php
/*
MicroTiny module upgrade process
Copyright (C) 2009-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

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
use CMSMS\SingleItem;
use CMSMS\UserParams;

if (empty($this) || !($this instanceof MicroTiny)) exit;
//$installing = AppState::test(AppState::INSTALL);
//if (!($installing || $this->CheckPermission('Modify Modules'))) exit;

if( version_compare($oldversion,'2.0') < 0 ) {
	$this->RemovePreference();
	$this->DeleteTemplate();
	return include_once(__DIR__.'/method.install.php');
}

if( version_compare($oldversion,'2.3') < 0 ) {
	$tp = cms_join_path($config['uploads_path'], 'images');
	if (!is_dir($tp)) {
		mkdir($tp, 0771, true);
	}
	$fp = cms_join_path(__DIR__, 'images', 'uTiny-demo.png');
	copy($fp, $tp.DIRECTORY_SEPARATOR.'uTiny-demo.png');

	$fp = cms_join_path(__DIR__, 'lib', 'js', 'tinymce');
	if (is_dir($fp)) {
		$tp = cms_join_path(__DIR__, 'lib', 'tinymce');
		if (!is_dir($tp)) {
			@rename($fp, $tp); // silence folder-not-empty warning
		}
	}
	recursive_delete(dirname($fp));

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
	$this->SetPreference('disable_cache', !empty($config['mt_disable_cache'])); // migrate from config.php to module setting

	$profiles = $this->ListPreferencesByPrefix('profile_');
	foreach ($profiles as $name) {
		$full = 'profile_'.$name;
		$val = $this->GetPreference($full);
		if( $val ) {
			$data = unserialize($val, ['allowed_classes' => false]);
			$val = json_encode($data, JSON_NUMERIC_CHECK|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
			$this->SetPreference($full, $val);
		}
	}

	$me = $this->GetName();
	$val = AppParams::get('wysiwyg');
	if (!$val) {
		AppParams::set('wysiwyg', $me);
	}
	$val = AppParams::get('frontendwysiwyg');
	if (!$val) {
		AppParams::set('frontendwysiwyg', $me);
	}

	$users = SingleItem::UserOperations()->GetList();
	foreach ($users as $uid => $uname) {
		$val = UserParams::get_for_user($uid, 'wysiwyg');
		if (!$val || $val == 'TinyMCE') { // old TinyMCE module had non-conforming API >> crash!
			UserParams::set_for_user($uid, 'wysiwyg', $me);
			UserParams::set_for_user($uid, 'wysiwyg_type', '');
			UserParams::set_for_user($uid, 'wysiwyg_theme', '');
		}
	}

	$arr = $db->getCol('SELECT id FROM '.CMS_DB_PREFIX."layout_tpl_types WHERE originator='$me'");
	if ($arr) {
		$query = 'DELETE FROM '.CMS_DB_PREFIX.'layout_templates WHERE type_id IN ('.implode(',',$arr).')';
		$db->execute($query);
		$query = 'DELETE FROM '.CMS_DB_PREFIX.'layout_tpl_types WHERE id IN ('.implode(',',$arr).')';
		$db->execute($query);
	}
}
