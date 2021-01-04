<?php
/*
Process-jobs action for for CMS Made Simple module: CmsJobManager
Copyright (C) 2016-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
See license details at the top of file CmsJobManager.module.php
*/

use CmsJobManager\JobStore;
use CmsJobManager\Utils;
use CMSMS\App;
use CMSMS\AppParams;

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

if (!isset($gCms) || !($gCms instanceof App)) {
    return;
}

$log = defined('ASYNCLOG');
if (empty($params['cms_jobman'])) {
    if ($log) {
        error_log('async action: process - exit no "cms_jobman" param'."\n", 3, ASYNCLOG);
    }
    return;
}

register_shutdown_function('\\CmsJobManager\\Utils::errorhandler');

try {
    Utils::process_errors();
    $n = $this->refresh_jobs();
    if ($log) {
        if ($n == 0) {
            error_log('async action: no processed jobs'."\n", 3, ASYNCLOG);
        } else {
            error_log("async action: upserted $n jobs\n", 3, ASYNCLOG);
        }
    }
    JobStore::clear_bad_jobs();

    $jobs = JobStore::get_jobs();
    if (!$jobs) {
        if ($log) {
            error_log('async action: process - no due jobs'."\n", 3, ASYNCLOG);
        }
        return; // nothing to do
    } elseif ($log) {
        error_log('async action: '.count($jobs).' saved jobs'."\n", 3, ASYNCLOG);
    }

    if ($this->is_locked()) {
        $nm = $this->GetName();
        if ($this->lock_expired()) {
            debug_to_log($nm.': Removing an expired lock (probably an error occurred)');
            audit('', $nm, 'Removing an expired lock. An error probably occurred with a previous job.');
            $this->unlock();
        } else {
            debug_to_log($nm.': Processing still locked (probably because of an error)... wait for a bit');
            audit('', $nm, 'Processing is already occurring.');
            return;
        }
    }

    $time_limit = (int)AppParams::get('jobtimeout', 0);
    if (!$time_limit) {
        $time_limit = (int)ini_get('max_execution_time');
    }
    $time_limit = max(2, min(120, $time_limit)); // no stupid time limit values
    set_time_limit($time_limit);

    $started_at = $now = time();
    AppParams::set('joblastrun', $started_at); //for use with AppParams::jobinterval checking

    $this->lock(); // get a new lock
    if ($log) {
        error_log('async action: process - '.count($jobs).' job(s)'."\n", 3, ASYNCLOG);
    }

    foreach ($jobs as $job) {
        // make sure we are not out of time.
        if ($now - $time_limit >= $started_at) {
            if ($log) {
                error_log('async action: process - timed out'."\n", 3, ASYNCLOG);
            }
            break;
        }
        try {
            $this->set_current_job($job);
            if ($log) {
                error_log('async action: execute job '.$job->name."\n", 3, ASYNCLOG);
            }
            $job->execute();
            if (Utils::job_recurs($job)) {
                $job->start = Utils::calculate_next_start_time($job);
                if ($job->start) {
                    $this->errors = 0;
                    Utils::load_job($job); //update the next start
                } else {
                    Utils::unload_job($job);
                }
            } else {
                Utils::unload_job($job);
            }
            if ($config['develop_mode']) {
                audit('', 'CmsJobManager', 'Processed job '.$job->name);
            }
            $this->set_current_job(null);
        } catch (Throwable $t) {
            $job = $this->get_current_job();
            if ($log) {
                error_log($job->name.' Throwable: '. $t->GetMessage()."\n", 3, ASYNCLOG);
                error_log($t->getTraceAsString()."\n", 3, ASYNCLOG);
            }
            audit('', 'CmsJobManager', 'An error occurred while processing: '.$job->name);
            Utils::joberrorhandler($job, $t->GetMessage(), $t->GetFile(), $t->GetLine());
        }
        $now = time();
    }
    $this->unlock();
    if ($log) {
        error_log('async action: process - now UNlocked'."\n", 3, ASYNCLOG);
    }
} catch (Throwable $t) {
    $this->unlock();
    // some other error occurred, not processing jobs.
    $this->SetPreference('last_badjob_run', $started_at);
    if ($log) {
        error_log('Throwable '.$t->GetMessage()."\n", 3, ASYNCLOG);
        error_log($t->GetTraceAsString());
    }
    debug_to_log('--Major async processing Throwable--');
    debug_to_log('Throwable '.$t->GetMessage());
    debug_to_log($t->GetTraceAsString());
}

