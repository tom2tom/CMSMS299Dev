<?php
# action for CmsJobManager, a core module for CMS Made Simple to
# manage asynchronous jobs and cron jobs.
# Copyright (C) 2016-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
# See license details at the top of file CmsJobManager.module.php

use CmsJobManager\JobQueue;
use CmsJobManager\utils;

if (!isset($gCms)) {
    exit;
}

//TODO more-robust security
if (!isset($params['cms_jobman'])) {
    if (defined('ASYNCLOG')) {
        error_log('async action: process - exit no "cms_jobman" param'."\n", 3, ASYNCLOG);
    }
    exit;
}

while (ob_get_level()) {
    @ob_end_clean();
}
ignore_user_abort(true);
header('Connection: Close');
$out = 'X-CMSMS: Processing';
$size = strlen($out);
header("Content-Length: $size");
header($out);
flush();

register_shutdown_function('\\CmsJobManager\\utils::errorhandler');

try {
    $now = time();
/* interval checked during the trigger process
    $last_run = (int) $this->GetPreference('last_processing');
    if ($last_run >= $now - utils::get_async_freq()) {
        if (defined('ASYNCLOG')) {
            error_log('async action: process - too soon'."\n", 3, ASYNCLOG);
        }
        return '';
    }
*/
    utils::process_errors();
    JobQueue::clear_bad_jobs();

    $jobs = JobQueue::get_jobs();
    if (!$jobs) {
        if (defined('ASYNCLOG')) {
            error_log('async action: process - no jobs'."\n", 3, ASYNCLOG);
        }
        return ''; // nothing to do
    }

    if ($this->is_locked()) {
        if ($this->lock_expired()) {
            debug_to_log($this->GetName().': Removing an expired lock (probably an error occurred)');
            audit('', $this->GetName(), 'Removing an expired lock. An error probably occurred with a previous job.');
            $this->unlock();
        } else {
            debug_to_log($this->GetName().': Processing still locked (probably because of an error)... wait for a bit');
            audit('', $this->GetName(), 'Processing is already occurring.');
            exit;
        }
    }

    $time_limit = (int)$this->GetPreference('jobtimeout');
    if (!$time_limit) {
        $time_limit = (int) ini_get('max_execution_time');
    }
    $time_limit = max(30, min(1800, $time_limit)); // no stupid time limit values
    set_time_limit($time_limit);
    $started_at = $now;

    $this->lock(); // get a new lock
    if (defined('ASYNCLOG')) {
		error_log('async action: process - '.count($jobs).' job(s)'."\n", 3, ASYNCLOG);
    }

    foreach ($jobs as $job) {
        // make sure we are not out of time.
        if ($now - $time_limit >= $started_at) {
			if (defined('ASYNCLOG')) {
				error_log('async action: process - timed out'."\n", 3, ASYNCLOG);
			}
            break;
        }
        try {
            $this->set_current_job($job);
			if (defined('ASYNCLOG')) {
				error_log('async action: execute job '.$job->name."\n", 3, ASYNCLOG);
			}
            $job->execute();
            if (utils::job_recurs($job)) {
                $job->start = utils::calculate_next_start_time($job);
                if ($job->start) {
                    $this->errors = 0;
                    $this->save_job($job);
                } else {
                    $this->delete_job($job);
                }
            } else {
                $this->delete_job($job);
            }
            if ($config['develop_mode']) {
                audit('', 'CmsJobManager', 'Processed job '.$job->name);
            }
            $this->set_current_job(null);
        } catch (Exception $e) {
            $job = $this->get_current_job();
            audit('', 'CmsJobManager', 'An error occurred while processing: '.$job->name);
            utils::joberrorhandler($job, $e->GetMessage(), $e->GetFile(), $e->GetLine());
			if (defined('ASYNCLOG')) {
				error_log($job->name.' exception: '. $e->GetMessage()."\n", 3, ASYNCLOG);
			}
        }
    }
//    $this->SetPreference('last_processing', $now);
    $this->unlock();
    if (defined('ASYNCLOG')) {
        error_log('async action: process - now UNlocked'."\n", 3, ASYNCLOG);
    }
} catch (Exception $e) {
    // some other error occurred, not processing jobs.
    if (defined('ASYNCLOG')) {
        error_log('exception '.$e->GetMessage()."\n", 3, ASYNCLOG);
    }
    debug_to_log('--Major async processing exception--');
    debug_to_log('exception '.$e->GetMessage());
    debug_to_log($e->GetTraceAsString());
}

exit;
