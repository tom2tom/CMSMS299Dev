<?php

use CmsJobManager\JobQueue;
use CmsJobManager\Utils;
use CMSMS\Async\RecurType;

if( !isset($gCms) ) exit;
if( !$this->VisibleToAdminUser() ) exit;

if( isset($params['apply']) && $this->CheckPermission('Modify Site Preferences') ) {
    $this->SetPreference('enabled', !empty($params['enabled']));
    $t = max(30, min(1800, (int)$params['jobtimeout']));
    $this->SetPreference('jobtimeout', $t);
    $t = max(1, min(10, (int)$params['jobinterval']));
    $this->SetPreference('jobinterval', $t);
    $t = trim($params['joburl']);
    if( $t ) {
        $t2 = filter_var($t, FILTER_SANITIZE_URL);
        if( filter_var($t2, FILTER_VALIDATE_URL) ) {
            $this->SetPreference('joburl', $t2);
        } else {
            $this->ShowErrors($this-Lang('err_url'));
        }
    } else {
        $this->SetPreference('joburl', '');
    }
}

//DEBUG - DISABLE FOR PRODUCTION
$u1 = $this->create_url($id,'test1',$returnid,[],false,false,'',1);
$u2 = $this->create_url($id,'test2',$returnid,[],false,false,'',1);
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
//DEBUG - END

$jobs = [];
$job_objs = JobQueue::get_all_jobs();
if( $job_objs ) {
    $list = [
        RecurType::RECUR_15M => $this->Lang('recur_15m'),
        RecurType::RECUR_30M => $this->Lang('recur_30m'),
        RecurType::RECUR_HOURLY => $this->Lang('recur_hourly'),
        RecurType::RECUR_120M => $this->Lang('recur_120m'),
        RecurType::RECUR_180M => $this->Lang('recur_180m'),
        RecurType::RECUR_DAILY => $this->Lang('recur_daily'),
        RecurType::RECUR_WEEKLY => $this->Lang('recur_weekly'),
        RecurType::RECUR_MONTHLY => $this->Lang('recur_monthly'),
        RecurType::RECUR_NONE => '',
    ];
    $custom = $this->Lang('pollgap', '%s');

    foreach( $job_objs as $job ) {
        $obj = new stdClass();
        $name = $job->name;
        if( ($t = strrpos($name, '\\')) !== false ) {
            $name = substr($name, $t+1);
        }
        $obj->name = $name;
        $obj->module = $job->module;
        if (Utils::job_recurs($job)) {
            if (isset($list[$job->frequency])) {
                $obj->frequency = $list[$job->frequency];
            } elseif ($job->frequency == RecurType::RECUR_SELF) {
                $t = floor($job->interval / 3600) . gmdate(":i", $job->interval % 3600);
                $obj->frequency = sprintf($custom, $t);
            } else {
                $obj->frequency = ''; //unknown parameter
            }
            $obj->until = $job->until;
        } else {
            $obj->frequency = null;
            $obj->until = null;
        }
        $obj->created = $job->created;
        $obj->start = $job->start;
        $obj->errors = $job->errors;
        $jobs[] = $obj;
    }
}

$tpl = $this->create_new_template('defaultadmin.tpl');
$tpl->assign('jobs',$jobs);
$tpl->assign('jobinterval',(int)$this->GetPreference('jobinterval'));
$tpl->assign('last_processing',(int)$this->GetPreference('last_processing'));

if( $this->CheckPermission('Modify Site Preferences') ) {
    $tpl->assign('tabbed',1);
    $tpl->assign('enabled',(int)$this->GetPreference('enabled'));
    $tpl->assign('jobtimeout',(int)$this->GetPreference('jobtimeout'));
    $tpl->assign('joburl',$this->GetPreference('joburl'));
}

$tpl->display();
return '';
