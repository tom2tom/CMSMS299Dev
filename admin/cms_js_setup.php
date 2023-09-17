<?php
/*
Ajax processor to generate runtime variables for admin js to use
Copyright (C) 2004-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

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

/*
Deprecated since 3.0
Use corresponding immediate setup via a hooklist, then include|require
jsruntime.php at a suitable place in a script.
*/

use CMSMS\AppState;
use CMSMS\Lone;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
AppState::set(AppState::ADMIN_PAGE);
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$urlext = get_secure_param();
$config = Lone::get('Config');

// get some urls and language strings
$data = [];
$data['admin_url'] = $config['admin_url'];
$data['ajax_alerts_url'] = $config['admin_url'].'/ajax_alerts.php'.$urlext;
$data['ajax_help_url'] = $config['admin_url'].'/ajax_help.php'.$urlext;
$data['job_key'] = CMS_JOB_KEY;
$data['lang_alert'] = _la('alert');
$data['lang_cancel'] = _la('cancel');
$data['lang_choose'] = _la('choose'); //filepicker-specific
$data['lang_close'] = _la('close');
$data['lang_confirm'] = _la('confirm');
$data['lang_confirm_leave'] = _la('confirm_leave');
$data['lang_disabled'] = _la('disabled');
$data['lang_error'] = _la('error');
$data['lang_gotit'] = _la('gotit');
$data['lang_largeupload'] = _la('upload_largeupload');
$data['lang_no'] = _la('no');
$data['lang_none'] = _la('none');
$data['lang_ok'] = _la('ok');
$data['lang_select_file'] = _la('select_file'); //filepicker-specific
$data['lang_title_help'] = _la('help');
$data['lang_yes'] = _la('yes');
$data['max_upload_size'] = $config['max_upload_size'];
$data['root_url'] = CMS_ROOT_URL;
$data['secure_param_name'] = CMS_SECURE_PARAM_NAME;
$data['uploads_url'] = $config['uploads_url'];
$data['user_key'] = $_SESSION[CMS_USER_KEY];
$c = count($data) - 1; // special-case the last member

// output some javascript
$out = <<<EOS
var ex = {

EOS;
for ($i=0; $i<$c; ++$i) {
    $key = key($data);
    $value = json_encode(current($data), JSON_UNESCAPED_UNICODE);
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

//header('Pragma: public'); // deprecated 1.1
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Cache-Control: private', false);
header('Content-Type: text/javascript');
echo $out;
exit;
