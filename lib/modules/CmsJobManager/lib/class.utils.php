<?php
# utility-methods for CmsJobManager, a core module for CMS Made Simple
# to manage asynchronous jobs and cron jobs.
# Copyright (C) 2016-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
# See license details at the top of file CmsJobManager.module.php

namespace CmsJobManager;

final class utils
{
//    public function __construct() {}

    public static function get_async_freq()
    {
        $config = \cms_config::get_instance();
        $minutes = (int) $config['cmsjobmgr_asyncfreq'];
        $minutes = max(3, $minutes);
        $minutes = min(60, $minutes);
        $freq = (int) $minutes * 60; // config entry is in minutes.
        return $freq;
    }

    public static function job_recurs(\CMSMS\Async\Job $job)
    {
        if (!$job instanceof \CMSMS\Async\CronJobInterface) {
            return false;
        }
        return $job->frequency != $job::RECUR_NONE;
    }

    public static function calculate_next_start_time(\CMSMS\Async\CronJob $job)
    {
        $out = null;
        $now = time();
        if (!self::job_recurs($job)) {
            return $out;
        }
        switch ($job->frequency) {
        case $job::RECUR_NONE:
            return $out;
        case $job::RECUR_15M:
            $out = $now + 15 * 60;
            break;
        case $job::RECUR_30M:
            $out = $now + 30 * 60;
            break;
        case $job::RECUR_HOURLY:
            $out = $now + 3600;
            break;
        case $job::RECUR_2H:
            $out = $now + 2 * 3600;
            break;
        case $job::RECUR_3H:
            $out = $now + 3 * 3600;
            break;
        case $job::RECUR_DAILY:
            $out = $now + 3600 * 24;
            break;
        case $job::RECUR_WEEKLY:
            $out = strtotime('+1 week', $now);
            break;
        case $job::RECUR_MONTHLY:
            $out = strtotime('+1 month', $now);
            break;
        }
        debug_to_log("adjusted to {$out} -- {$now} // {$job->until}");
        if (!$job->until || $out <= $job->until) {
            return $out;
        }
    }

    public static function process_errors()
    {
        $fn = md5(__FILE__).'.err';
        $fn = TMP_CACHE_LOCATION.DIRECTORY_SEPARATOR.$fn;
        if (!is_file($fn)) {
            return;
        }

        $data = file_get_contents($fn);
        @unlink($fn);
        if (!$data) {
            return;
        }

        $tmp = explode("\n", $data);
        if (!is_array($tmp) || !count($tmp)) {
            return;
        }

        $job_ids = [];
        foreach ($tmp as $one) {
            $one = (int) $one;
            if ($one < 1) {
                continue;
            }
            if (!in_array($one, $job_ids)) {
                $job_ids[] = $one;
            }
        }

        // have jobs to increase error count on.
        $db = \cms_utils::get_db();
        $sql = 'UPDATE '.CmsJobManager::table_name().' SET errors = errors + 1 WHERE id IN ('.implode(',', $job_ids).')';
        $db->Execute($sql);
        debug_to_log('Increased error count on '.count($job_ids).' jobs ');
    }

    public static function put_error($job_id)
    {
        $fn = md5(__FILE__).'.err';
        $fn = TMP_CACHE_LOCATION.DIRECTORY_SEPARATOR.$fn;
        $fh = fopen($fn, 'a');
        fwrite($fh, $job_id."\n");
        fclose($fh);
    }

    // no access to the database here.
    public static function joberrorhandler($job, $errmsg, $errfile, $errline)
    {
        debug_to_log('Fatal error occurred processing async jobs at: '.$errfile.':'.$errline);
        debug_to_log('Msg: '.$errmsg);

        if (!is_object($job)) {
            return;
        }
        self::put_error($job->id);
    }

    public static function errorhandler()
    {
        $err = error_get_last();
        if (is_null($err)) {
            return;
        }
        if ($err['type'] != E_ERROR) {
            return;
        }
        $mod = \ModuleOperations::get_instance()->get_module_instance('CmsJobManager');
        $job = $mod->get_current_job();
        if ($job) {
            self::joberrorhandler($job, $err['message'], $err['file'], $err['line']);
        }
    }
}
