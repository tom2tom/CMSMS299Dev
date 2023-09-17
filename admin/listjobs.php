<?php
/*
Procedure to display recorded data about async jobs
Copyright (C) 2022-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

//use CMSMS\Url;
//use CMSMS\AppParams;
//use CMSMS\FormUtils;
use CMSMS\Async\RecurType;
use CMSMS\Error403Exception;
use CMSMS\internal\JobOperations;
use CMSMS\Lone;

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

check_login();

$urlext = get_secure_param();
$userid = get_userid();

//$themeObject = Lone::get('Theme');

$pmod = check_permission($userid, 'Manage Jobs'); //?? View Jobs?
if (!$pmod) {
//TODO some pushed popup    $themeObject->RecordNotice('error', _ld('jobs', 'needpermissionto', '"Manage Jobs"'));
    throw new Error403Exception(_ld('jobs', 'permissiondenied')); // OR display error.tpl ?
}

/* DEBUG
if (0) {
    $u1 = FormUtils::create_action_link($TODOmod, [//create_action_url($id, 'test1');
        'getid' => $id,
        'action' => 'test1',
        '' => '',
        '' => '',
        'onlyhref' => true,
    ]);
    $u2 = FormUtils::create_action_link($TODOmod, [ //create_action_url($id, 'test2');
        'getid' => $id,
        'action' => 'test2',
        '' => '',
        '' => '',
        'onlyhref' => true,
    ]);
    $js = <<<EOS
<script>
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
        RecurType::RECUR_15M => _ld('jobs', 'recur_15m'),
        RecurType::RECUR_30M => _ld('jobs', 'recur_30m'),
        RecurType::RECUR_HOURLY => _ld('jobs', 'recur_hourly'),
        RecurType::RECUR_120M => _ld('jobs', 'recur_120m'),
        RecurType::RECUR_180M => _ld('jobs', 'recur_180m'),
        RecurType::RECUR_DAILY => _ld('jobs', 'recur_daily'),
        RecurType::RECUR_WEEKLY => _ld('jobs', 'recur_weekly'),
        RecurType::RECUR_MONTHLY => _ld('jobs', 'recur_monthly'),
        RecurType::RECUR_NONE => '',
    ];
    $custom = _ld('jobs', 'pollgap', '%s');

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
            $obj->until = $job->until;
        } else {
            $obj->frequency = '';
            $obj->until = 0;
        }
        $obj->created = $job->created;
        $obj->start = ($obj->frequency || $obj->until) ? $job->start : 0;
        $obj->errors = $job->errors;
        $job_objs[] = $obj;
    }
}

$smarty = Lone::get('Smarty');
$smarty->assign('jobs', $job_objs);

$content = $smarty->fetch('listjobs.tpl');
require ".{$dsep}header.php";
echo $content;
require ".{$dsep}footer.php";
