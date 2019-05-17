<?php
#populate runtime variables for admin js to use
#Copyright (C) 2018-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

$urlext = get_secure_param();

$data = [];
$data['admin_url'] = $config['admin_url'];
$data['ajax_alerts_url'] = $config['admin_url'].'/ajax_alerts.php'.$urlext;
$data['ajax_help_url'] = $config['admin_url'].'/ajax_help.php'.$urlext;
$data['job_key'] = CMS_JOB_KEY;
$data['lang_alert'] = lang('alert');
$data['lang_cancel'] = lang('cancel');
$data['lang_choose'] = lang('choose'); //filepicker-specific
$data['lang_close'] = lang('close');
$data['lang_confirm'] = lang('confirm');
$data['lang_confirm_leave'] = lang('confirm_leave');
$data['lang_disabled'] = lang('disabled');
$data['lang_error'] = lang('error');
$data['lang_gotit'] = lang('gotit');
$data['lang_largeupload'] = lang('upload_largeupload');
$data['lang_no'] = lang('no');
$data['lang_none'] = lang('none');
$data['lang_ok'] = lang('ok');
$data['lang_select_file'] = lang('select_file'); //filepicker-specific
$data['lang_title_help'] = lang('help');
$data['lang_yes'] = lang('yes');
$data['max_upload_size'] = $config['max_upload_size'];
$data['root_url'] = CMS_ROOT_URL;
$data['secure_param_name'] = CMS_SECURE_PARAM_NAME;
$data['uploads_url'] = $config['uploads_url'];
$data['user_key'] = $_SESSION[CMS_USER_KEY];

$c = count($data) - 1; // special-case the last member

$_out_ = <<<EOS
var ex = {

EOS;
for ($i=0; $i<$c; ++$i) {
    $key = key($data);
    $value = json_encode(current($data));
    $_out_ .= " $key: $value,\n";
    next($data);
}
$key = key($data);
$value = json_encode(current($data));
$_out_ .= <<<EOS
 $key: $value
};
if(typeof cms_data === 'undefined') {
 var cms_data = {};
}
Object.keys(ex).forEach(function(key) { cms_data[key] = ex[key]; });
EOS;

