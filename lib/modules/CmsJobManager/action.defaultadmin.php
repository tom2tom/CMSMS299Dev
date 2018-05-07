<?php
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

$jobs = [];
$job_objs = \CmsJobManager\JobQueue::get_all_jobs();
if( $job_objs ) {
    foreach( $job_objs as $job ) {
        $obj = new StdClass;
        $obj->name = $job->name;
        $obj->module = $job->module;
        $obj->frequency = (\CmsJobManager\utils::job_recurs($job)) ? $job->frequency : null;
        $obj->created = $job->created;
        $obj->start = $job->start;
        $obj->until = (\CmsJobManager\utils::job_recurs($job)) ? $job->until : null;
        $obj->errors = $job->errors;
        $jobs[] = $obj;
    }
}

$list = ['' => ''];
$list[\CMSMS\Async\CronJob::RECUR_NONE] = '';
$list[\CMSMS\Async\CronJob::RECUR_15M] = $this->Lang('recur_15m');
$list[\CMSMS\Async\CronJob::RECUR_30M] = $this->Lang('recur_30m');
$list[\CMSMS\Async\CronJob::RECUR_HOURLY] = $this->Lang('recur_hourly');
$list[\CMSMS\Async\CronJob::RECUR_120M] = $this->Lang('recur_120m');
$list[\CMSMS\Async\CronJob::RECUR_180M] = $this->Lang('recur_180m');
$list[\CMSMS\Async\CronJob::RECUR_DAILY] = $this->Lang('recur_daily');
$list[\CMSMS\Async\CronJob::RECUR_WEEKLY] = $this->Lang('recur_weekly');
$list[\CMSMS\Async\CronJob::RECUR_MONTHLY] = $this->Lang('recur_monthly');

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

/* TESTING
extra template content
<a id="simple1" href="{cms_action_url action=test1}" class="link_button icon do">Simple Derived Class Test</a>
<a href="{cms_action_url action=test2}" class="link_button icon check">Simple Derived Cron Test</a>
$js = <<<EOS
<script type="text/javascript">
//<![CDATA[
$(document).ready(function() {
  $('#simple1').on('click', function(ev) {
    ev.preventDefault();
    cms_confirm('woot it works');
    return false;
  });
});
//]]>
</script>
EOS;
$this->AdminFooterContent($js);
*/

$tpl->display();
