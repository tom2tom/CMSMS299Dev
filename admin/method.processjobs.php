<?php
/*
Support code for processing async / background jobs
Copyright (C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use CMSMS\AppParams;
use CMSMS\Lone;
use function CMSMS\log_error;
use function CMSMS\log_notice;

try {
    $ops->process_errors();
    $n = $ops->refresh_jobs();
    if ($log) {
        if ($n == 0) {
            error_log('no processed jobs'."\n", 3, $log);
        } else {
            error_log("upserted $n jobs\n", 3, $log);
        }
    }
    $ops->clear_bad_jobs();

    $jobs = $ops->get_jobs();
    if (!$jobs) {
        if ($log) {
            error_log('no due jobs'."\n", 3, $log);
        }
        return; // nothing to do
    } elseif ($log) {
        error_log(count($jobs).' saved jobs'."\n", 3, $log);
    }

    if ($ops->is_locked()) {
        $nm = 'process-jobs';
        if ($ops->lock_expired()) {
            debug_to_log($nm.': removing an expired lock (probably an error occurred)');
            log_notice($nm, 'Removing an expired lock. An error probably occurred with a previous job.');
            $ops->unlock();
        } else {
            debug_to_log($nm.': still locked (probably because of an error)... wait for a bit');
            log_notice($nm, 'Processing is already occurring');
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

    $ops->lock(); // get a new lock
    if ($log) {
        error_log('jobs processor locked'."\n", 3, $log);
        error_log('process - '.count($jobs).' job(s)'."\n", 3, $log);
    }

    $config = Lone::get('Config');
    $dev = !empty($config['develop_mode']);
    foreach ($jobs as $job) {
        // check we have not timed-out
        if ($now - $time_limit >= $started_at) {
            if ($log) {
                error_log('jobs processor timed out'."\n", 3, $log);
            }
            break;
        }
        try {
            $ops->set_current_job($job);
            $res = $job->execute();
            if ($log) {
                switch ($res) {
                    case 2:
                    case null: // for deprecated 2.2-API jobs
                        error_log('completed job '.$job->name."\n", 3, $log);
/* TMI                  break;
                    case 1;
                        error_log('job '.$job->name." skipped\n", 3, $log);
                        break;
                    case 0:
                        error_log('job '.$job->name." failed\n", 3, $log);
*/
                    // no break here
                    default:
                        break;
                }
            }
            if ($ops->job_recurs($job)) {
                $job->start = $ops->calculate_next_start_time($job);
                if ($job->start) {
                    $ops->clear_errors();
                    $ops->load_job($job); //update the next start
                } else {
                    $ops->unload_job($job);
                }
            } else {
                $ops->unload_job($job);
            }
/* TMI      if ($dev && ($res == 2 || $res === null)) {
                log_notice('Completed job',$job->name);
            }
*/
        } catch (Throwable $t) {
            $job = $ops->get_current_job();
            if ($job) {
                if ($log) {
                    error_log($job->name.' Throwable: '. $t->GetMessage()."\n", 3, $log);
                    error_log($t->getTraceAsString()."\n", 3, $log);
                }
                log_error('Error while processing job', $job->name);
                $ops->joberrorhandler($job, $t->GetMessage(), $t->GetFile(), $t->GetLine());
            } else {
                //TODO
                log_error('An error occurred while processing some job');
            }
        }
        $now = time();
    }
    $ops->set_current_job(null);
    $ops->unlock();
    if ($log) {
        error_log('jobs processor UNlocked'."\n", 3, $log);
    }
} catch (Throwable $t) {
    $ops->unlock();
    // some other error occurred, not processing jobs
    AppParams::set('joblastbadrun', $started_at);
    if ($log) {
        error_log('Throwable '.$t->GetMessage()."\n", 3, $log);
        error_log($t->GetTraceAsString()."\n", 3, $log);
    }
    debug_to_log('--Major async processing Throwable--');
    debug_to_log('Throwable '.$t->GetMessage());
    debug_to_log($t->GetTraceAsString());
}
