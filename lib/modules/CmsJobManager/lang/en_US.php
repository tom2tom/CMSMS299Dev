<?php

$lang['apply'] = 'Apply';
$lang['cancel'] = 'Cancel';
$lang['created'] = 'Created';
$lang['errors'] = 'Errors';
$lang['evtdesc_OnJobFailed'] = 'Sent after a job is removed from the job queue after failing too many times';
$lang['evthelp_OnJobFailed'] = '<h4>Parameters:</h4>
<ul>
  <li>"job" - A reference to the \CMSMS\Async\Job job object that has failed</li>
</ul';
$lang['frequency'] = 'Frequency';
$lang['friendlyname'] = 'Background Jobs';
$lang['info_background_jobs'] = 'This shows all currently known background jobs. Such jobs normally appear and disappear frequently. If a job has a high error count or was never started, then some investigation is needed.';
$lang['info_no_jobs'] = 'There is no job to be performed.';
$lang['jobs'] = 'Jobs';
$lang['moddescription'] = 'A module for managing asynchronous processing jobs.';
$lang['module'] = 'Module';
$lang['name'] = 'Name';
$lang['perm_Manage_Jobs'] = 'Manage Asynchronous Jobs';
$lang['pollgap'] = 'Polled every %s';
$lang['processing_freq'] = 'Maximum processing frequency (seconds)';
$lang['recur_120m'] = 'Every 2 hours';
$lang['recur_15m'] = 'Every 15 minutes';
$lang['recur_180m'] = 'Every 3 hours';
$lang['recur_30m'] = 'Every 30 minutes';
$lang['recur_daily'] = 'Daily';
$lang['recur_hourly'] = 'Hourly';
$lang['recur_monthly'] = 'Monthly';
$lang['recur_weekly'] = 'Weekly';
$lang['settings'] = 'Settings';
$lang['settings_title'] = 'Background Job Settings'; // for admin site-settings info
$lang['start'] = 'Start';
$lang['until'] = 'Until';

$lang['prompt_enabled'] = 'Enable job processing by this module';
$lang['help_enabled'] = 'Background job-processing is an essential element of this website. Only disable this if another compatible job processor is present, and <strong>you understand what you\'re doing</strong>.';
$lang['prompt_frequency'] = 'Minimum interval between processing of jobs (<em>minutes</em>)';
$lang['help_frequency'] = 'Enter a value from 1 to 10. Lower is better, but not so low that performance of the website is noticeably degraded.';
$lang['prompt_timelimit'] = 'Jobs timeout (<em>seconds</em>)';
$lang['help_timelimit'] = 'Enter a value from 30 to 1800. This is akin to PHP’s maximum execution time setting.';
$lang['prompt_joburl'] = 'Custom URL for job processing';
$lang['help_joburl'] = 'Enter a suitable URL to replace the default internal URL, if that cannot be used. Leave blank to use the default.';

$lang['help'] = <<<EOT
<h3>What does this do?</h3>
<p>This is a CMSMS core module that provides functionality for processing jobs asynchronously (in the background) as the website is handling requests.</p>
<p>Any module which is suitably constructed can create jobs to perform tasks that do not need direct user intervention or that can take some time to process.  This module provides the processing capability for those jobs.</p>
<h3>How is it used?</h3>
<p>This module has no interaction of its own.  It does provide a simple job report that lists jobs that the manager currently has in its queue.  Jobs might regularly pop on and off this queue, so refreshing the page from time to time might give you an indication as to what is happening in the background of your site.</p>
<p>This module will process jobs at most every minute, and at least every ten minutes. The default interval is 3 minutes. This infrequent processing is to ensure reasonable performance on most websites.</p>
<p>You can adjust the frequency in the module settings.</p>
<p><strong>Note:</strong> It is an error to disable asynchronous processing completely, because some functioning of the CMSMS core relies on this functionality. This module's operation may be disabled, but only do so if another suitable alternative is present.</p>
<h3>What about problem jobs?</h3>
<p>From time to time some applications might create jobs that fail, exiting with some sort of error.  CmsJobManager will remove the job after the job has failed a number of times.  At which time the originating code can re-create the job.  If you encounter a problematic job that continues to fail this is a bug that should be diagnosed, and reported in detail to the appropriate developers.</p>
EOT;
