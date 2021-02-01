<?php
/*
Populate runtime variables for admin js to use
Copyright (C) 2018-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\AppParams;
use CMSMS\Crypto;

$urlext = get_secure_param();

if (!isset($vars)) {
    $vars = [];
}

//NOTE nothing of any security-risk should ever be in these data
$vars['root_url'] = CMS_ROOT_URL;
$vars['admin_url'] = $config['admin_url'];
$vars['ajax_alerts_url'] = $config['admin_url'].'/ajax_alerts.php'.$urlext;
$vars['ajax_help_url'] = $config['admin_url'].'/ajax_help.php'.$urlext;
$vars['job_key'] = CMS_JOB_KEY;
$vars['secure_param_name'] = CMS_SECURE_PARAM_NAME;
$vars['user_key'] = $_SESSION[CMS_USER_KEY]; // for some ajax operations at least
$vars['lang_alert'] = lang('alert');
$vars['lang_cancel'] = lang('cancel');
$vars['lang_close'] = lang('close');
$vars['lang_confirm'] = lang('confirm');
$vars['lang_confirm_leave'] = lang('confirm_leave');
$vars['lang_disabled'] = lang('disabled');
$vars['lang_error'] = lang('error');
$vars['lang_gotit'] = lang('gotit');
$vars['lang_no'] = lang('no');
$vars['lang_none'] = lang('none');
$vars['lang_ok'] = lang('ok');
$vars['lang_title_help'] = lang('help');
$vars['lang_yes'] = lang('yes');

// is the website down for maintenance?
if (AppParams::get('site_downnow')) {
    $vars['lang_maintenance_warning'] = lang('maintenance_warning');
    $vars['sitedown'] = true;
} else {
    $vars['sitedown'] = false;
}

//$nonce = get_csp_token(); N/A here cuz all module-actions use this setup

// convert any " to ' suitable for json() then in js
foreach ($vars as &$val) {
    if (is_string($val)) {
        $val = strtr($val, '"', "'");
    }
}
unset($val);
$enc = json_encode($vars);
//for privacy (not security) a simple munge
$enc2 = rawurlencode(Crypto::scramble_string($enc));
//we also define cms_data, in case something wants to use that object prematurely
$js = <<<EOS
<script type="text/javascript">
//<![CDATA[
 var cms_data = {},
  cms_runtime = '$enc2';
//]]>
</script>

EOS;
