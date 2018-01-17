<?php
# action for CmsJobManager, a core module for CMS Made Simple to
# manage asynchronous jobs and cron jobs.
# Copyright (C) 2016-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
# See license details at the top of file CmsJobManager.module.php

if (!isset($gCms)) {
    exit;
}
if (!isset($_REQUEST['cms_jobman'])) {
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

register_shutdown_function('\CmsJobManager\utils::errorhandler');

try {
    $now = time();
    $last_run = (int) $this->GetPreference('last_processing');
    if ($last_run >= $now - \CmsJobManager\utils::get_async_freq()) {
        return;
    }

    \CmsJobManager\utils::process_errors();
    \CmsJobManager\JobQueue::clear_bad_jobs();

    $jobs = \CmsJobManager\JobQueue::get_jobs();
    if (!$jobs) {
        return; // nothing to do.
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

    $time_limit = (int) $config['cmsjobmanager_timelimit'];
    if (!$time_limit) {
        $time_limit = (int) ini_get('max_execution_time');
    }
    $time_limit = max(30, min(1800, $time_limit)); // no stupid time limit values
    set_time_limit($time_limit);
    $started_at = $now;

    $this->lock(); // get a new lock.
    foreach ($jobs as $job) {
        // make sure we are not out of time.
        if ($now - $time_limit >= $started_at) {
            break;
        }
        try {
            $this->set_current_job($job);
            $job->execute();
            if (\CmsJobManager\utils::job_recurs($job)) {
                $job->start = \CmsJobManager\utils::calculate_next_start_time($job);
                if ($job->start) {
                    $this->errors = 0;
                    $this->save_job($job);
                } else {
                    $this->delete_job($job);
                }
            } else {
                $this->delete_job($job);
            }
            $this->set_current_job(null);
            if ($config['developer_mode']) {
                audit('', 'CmsJobManager', 'Processed job '.$job->name);
            }
        } catch (\Exception $e) {
            $job = $this->get_current_job();
            audit('', 'CmsJobManager', 'An error occurred while processing: '.$job->name);
            \CmsJobManager\utils::joberrorhandler($job, $e->GetMessage(), $e->GetFile(), $e->GetLine());
        }
    }
    $this->unlock();
    $this->GetPreference('last_processing', $now);
} catch (\Exception $e) {
    // some other error occurred, not processing jobs.
    debug_to_log('--Major async processing exception--');
    debug_to_log('exception '.$e->GetMessage());
    debug_to_log($e->GetTraceAsString());
}

exit;
