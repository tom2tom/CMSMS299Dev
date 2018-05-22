<?php
#ajax processor to generate runtime variables for admin js to use
#Copyright (C) 2004-2017 Ted Kulp <ted@cmsmadesimple.org>
#Copyright (C) 2018 The CMSMS Dev Team <coreteam@cmsmadesimple.org>
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

$CMS_ADMIN_PAGE=1;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$urlext='?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
$config = cms_config::get_instance();

// get some urls and language strings
// TODO some of these should be action-spacific
$data = [];
$data['lang_title_help'] = lang('help');
$data['lang_alert'] = lang('alert');
$data['lang_error'] = lang('error');
$data['lang_ok'] = lang('ok');
$data['lang_gotit'] = lang('gotit');
$data['lang_cancel'] = lang('cancel');
$data['lang_confirm'] = lang('confirm');
$data['lang_close'] = lang('close');
$data['lang_yes'] = lang('yes');
$data['lang_no'] = lang('no');
$data['lang_none'] = lang('none');
$data['lang_disabled'] = lang('disabled');
$data['lang_hierselect_title'] = lang('title_hierselect_select'); // jquery.cmsms_hierselector.js
$data['lang_select_file'] = lang('select_file'); // jquery.cmsms_filepicker.js
$data['lang_choose'] = lang('choose');
//$data['lang_filetobig'] = lang('upload_filetobig'); //unused
$data['lang_largeupload'] = lang('upload_largeupload');
$data['max_upload_size'] = $config['max_upload_size'];
$data['admin_url'] = $config['admin_url'];
$data['root_url'] = $config['root_url'];
$data['uploads_url'] = $config['uploads_url'];
$data['ajax_help_url'] = $config['admin_url'].'/ajax_help.php'.$urlext;
$data['ajax_alerts_url'] = $config['admin_url'].'/ajax_alerts.php'.$urlext;
$data['secure_param_name'] = CMS_SECURE_PARAM_NAME;
$data['user_key'] = $_SESSION[CMS_USER_KEY];
$c = count($data) - 1; // special-case the last member

// output some javascript
$out = <<<EOS
var ex = {

EOS;
for ($i=0; $i<$c; ++$i) {
    $key = key($data);
    $value = json_encode(current($data));
    $out .= " $key: $value,\n";
	next($data);
}
$key = key($data);
$value = json_encode(current($data));
$out .= <<<EOS
 $key: $value
};
if(typeof cms_data === 'undefined') {
 var cms_data = {};
}
Object.keys(ex).forEach(function(key) { cms_data[key] = ex[key]; });
EOS;

header('Pragma: public');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Cache-Control: private',false);
header('Content-type: text/javascript');
echo $out;
exit;
