<?php
# utility-methods for CmsJobManager, a core module for CMS Made Simple
# to manage asynchronous jobs and cron jobs.
# Copyright (C) 2016-2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
# See license details at the top of file CmsJobManager.module.php

namespace CmsJobManager;

use cms_utils;
use CmsJobManager;
use CMSMS\Async\CronJobInterface;
use CMSMS\Async\Job;
use CMSMS\ModuleOperations;
use const TMP_CACHE_LOCATION;
use function debug_to_log;

final class utils
{
//    public function __construct() {}

    public static function get_async_freq() : int
    {
        $mod = ModuleOperations::get_instance()->get_module_instance('CmsJobManager');
        $minutes = (int) $mod->GetPreference('jobinterval');
        $minutes = max(1, $minutes);
        $minutes = min(10, $minutes);
        return $minutes * 60; // seconds
    }
	/**
	 * @param Job $job
	 * @return bool
	 */
    public static function job_recurs(Job $job) : bool
    {
        if ($job instanceof CronJobInterface) {
            return $job->frequency != $job::RECUR_NONE;
        }
        return false;
    }

	/**
	 * @param Job $job
	 * @return mixed int|null
	 */
    public static function calculate_next_start_time(Job $job)
    {
        if (!self::job_recurs($job)) {
            return null;
        }
        $now = time();
        switch ($job->frequency) {
        case $job::RECUR_NONE:
            return null;
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
        $fn = TMP_CACHE_LOCATION.DIRECTORY_SEPARATOR.md5(__FILE__).'.err';
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
        $db = cms_utils::get_db();
        $sql = 'UPDATE '.CmsJobManager::table_name().' SET errors = errors + 1 WHERE id IN ('.implode(',', $job_ids).')';
        $db->Execute($sql);
        debug_to_log('Increased error count on '.count($job_ids).' jobs ');
    }

	/**
	 * @param type $job_id
	 */
    public static function put_error($job_id)
    {
        $fn = TMP_CACHE_LOCATION.DIRECTORY_SEPARATOR.md5(__FILE__).'.err';
        $fh = fopen($fn, 'a');
        fwrite($fh, $job_id."\n");
        fclose($fh);
    }

	/**
	 * @param mixed $job
	 * @param string $errmsg
	 * @param string $errfile
	 * @param string $errline
	 */
    public static function joberrorhandler($job, string $errmsg, string $errfile, string $errline)
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
        $mod = ModuleOperations::get_instance()->get_module_instance('CmsJobManager');
        $job = $mod->get_current_job();
        if ($job) {
            self::joberrorhandler($job, $err['message'], $err['file'], $err['line']);
        }
    }
}
