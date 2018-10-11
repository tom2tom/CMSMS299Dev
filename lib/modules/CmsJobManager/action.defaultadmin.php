<?php

use CmsJobManager\JobQueue;
use CmsJobManager\utils;
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
$(document).ready(function() {
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
$this->AdminHeaderContent($js);
//DEBUG - END

$jobs = [];
$job_objs = JobQueue::get_all_jobs();
if( $job_objs ) {
    foreach( $job_objs as $job ) {
        $obj = new StdClass;
		$name = $job->name;
		if( ($t = strrpos($name, '\\')) !== false ) {
			$name = substr($name, $t+1);
		}
        $obj->name = $name;
        $obj->module = $job->module;
        $obj->frequency = (utils::job_recurs($job)) ? $job->frequency : null;
        $obj->created = $job->created;
        $obj->start = $job->start;
        $obj->until = (utils::job_recurs($job)) ? $job->until : null;
        $obj->errors = $job->errors;
        $jobs[] = $obj;
    }
}

$list = ['' => ''];
$list[RecurType::RECUR_NONE] = '';
$list[RecurType::RECUR_15M] = $this->Lang('recur_15m');
$list[RecurType::RECUR_30M] = $this->Lang('recur_30m');
$list[RecurType::RECUR_HOURLY] = $this->Lang('recur_hourly');
$list[RecurType::RECUR_120M] = $this->Lang('recur_120m');
$list[RecurType::RECUR_180M] = $this->Lang('recur_180m');
$list[RecurType::RECUR_DAILY] = $this->Lang('recur_daily');
$list[RecurType::RECUR_WEEKLY] = $this->Lang('recur_weekly');
$list[RecurType::RECUR_MONTHLY] = $this->Lang('recur_monthly');

$tpl = $this->create_new_template('defaultadmin.tpl');
$tpl->assign('jobs',$jobs);
$tpl->assign('recur_list',$list);
$tpl->assign('jobinterval',(int)$this->GetPreference('jobinterval'));
$tpl->assign('last_processing',(int)$this->GetPreference('last_processing'));

if( $this->CheckPermission('Modify Site Preferences') ) {
    $tpl->assign('tabbed',1);
    $tpl->assign('enabled',(int)$this->GetPreference('enabled'));
    $tpl->assign('jobtimeout',(int)$this->GetPreference('jobtimeout'));
    $tpl->assign('joburl',$this->GetPreference('joburl'));
}

$tpl->display();
