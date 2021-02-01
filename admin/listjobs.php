<?php
/*
Procedure to display recorded data about async jobs
Copyright (C) 2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

//use CMSMS\Url;
//use CMSMS\AppParams;
//use CMSMS\FormUtils;
use CMSMS\AppSingle;
use CMSMS\AppState;
use CMSMS\Async\RecurType;
use CMSMS\Error403Exception;
use CMSMS\internal\JobOperations;
use CMSMS\Utils;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$urlext = get_secure_param();
$userid = get_userid();

$themeObject = Utils::get_theme_object();

$pmod = check_permission($userid, 'Manage Jobs'); //?? View Jobs?
if (!$pmod) {
//TODO some pushed popup    $themeObject->RecordNotice('error', lang_by_realm('jobs', 'needpermissionto', '"Manage Jobs"'));
    throw new Error403Exception(lang_by_realm('jobs', 'permissiondenied')); // OR display error.tpl ?
}

/* DEBUG
if (0) {
    $u1 = FormUtils::create_action_link($TODOmod, [//create_url($id, 'test1', $returnid, [], false, false, '', 1);
        'modid' => $id,
        'action' => 'test1',
        '' => '',
        '' => '',
        'onlyhref' => true,
    ]);
    $u2 = FormUtils::create_action_link($TODOmod, [ //create_url($id, 'test2', $returnid, [], false, false, '', 1);
        'modid' => $id,
        'action' => 'test2',
        '' => '',
        '' => '',
        'onlyhref' => true,
    ]);
    $js = <<<EOS
<script type="text/javascript">
//<![CDATA[
$(function() {
 $('body').append(
'<a id="simple1" href="$u1" class="link_button icon do">Simple Derived Class Test</a>' +
'<a href="$u2" class="link_button icon do">Simple Derived Cron Test</a>'
);
 $('#simple1').on('click', function(ev) {
  ev.preventDefault();
  cms_confirm('woot it works'); //TODO linkclick ...
  return false;
 });
});
//]]>
</script>
EOS;
    add_page_headtext($js);
} //DEBUG - END
*/

$log = (defined('ASYNCLOG')) ?
    TMP_CACHE_LOCATION.DIRECTORY_SEPARATOR.'debug.log' :
    false;
$ops = new JobOperations();
// get up-to-date status (and populate $jobs[]) - the jobinterval will prevent immediate postrequest repeat
require_once __DIR__.DIRECTORY_SEPARATOR.'method.processjobs.php';
$job_objs = [];
if ($jobs) {
    $list = [
        RecurType::RECUR_15M => lang_by_realm('jobs', 'recur_15m'),
        RecurType::RECUR_30M => lang_by_realm('jobs', 'recur_30m'),
        RecurType::RECUR_HOURLY => lang_by_realm('jobs', 'recur_hourly'),
        RecurType::RECUR_120M => lang_by_realm('jobs', 'recur_120m'),
        RecurType::RECUR_180M => lang_by_realm('jobs', 'recur_180m'),
        RecurType::RECUR_DAILY => lang_by_realm('jobs', 'recur_daily'),
        RecurType::RECUR_WEEKLY => lang_by_realm('jobs', 'recur_weekly'),
        RecurType::RECUR_MONTHLY => lang_by_realm('jobs', 'recur_monthly'),
        RecurType::RECUR_NONE => '',
    ];
    $custom = lang_by_realm('jobs', 'pollgap', '%s');

    foreach ($jobs as $job) {
        $obj = new stdClass();
        $name = $job->name;
        if (($t = strrpos($name, '\\')) !== false) {
            $name = substr($name, $t + 1);
        }
        $obj->name = $name;
        $obj->module = $job->module;
        if ($ops->job_recurs($job)) {
            if (isset($list[$job->frequency])) {
                $obj->frequency = $list[$job->frequency];
            } elseif ($job->frequency == RecurType::RECUR_SELF) {
                $t = floor($job->interval / 3600) . gmdate(':i', $job->interval % 3600);
                $obj->frequency = sprintf($custom, $t);
            } else {
                $obj->frequency = ''; //unknown parameter
            }
            $obj->until = $job->until; // TODO format
        } else {
            $obj->frequency = null;
            $obj->until = null;
        }
        $obj->created = $job->created;
        $obj->start = ($obj->frequency || $obj->until) ? $job->start : 0;
        $obj->errors = $job->errors;
        $job_objs[] = $obj;
    }
}

$smarty = AppSingle::Smarty();
$smarty->assign('jobs', $job_objs);

$content = $smarty->fetch('listjobs.tpl');
$sep = DIRECTORY_SEPARATOR;
require ".{$sep}header.php";
echo $content;
require ".{$sep}footer.php";
