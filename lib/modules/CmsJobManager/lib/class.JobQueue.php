<?php
# jobqueue-methods for CmsJobManager, a core module for CMS Made Simple
# to manage asynchronous jobs and cron jobs.
# Copyright (C) 2016-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
# See license details at the top of file CmsJobManager.module.php

namespace CmsJobManager;

final class JobQueue
{
    const MAXJOBS = 50; // should never be more than 100 jobs in the queue for a site
    const MINGAP = 3600; // interval (seconds) between bad-job cleanups
    const MINERRORS = 10;

//    public function __construct() {}

    public static function have_jobs()
    {
        return self::get_jobs(true);
    }

    public static function get_all_jobs()
    {
        $db = \CmsApp::get_instance()->GetDb();
        $sql = 'SELECT * FROM '.\CmsJobManager::table_name().' WHERE created < UNIX_TIMESTAMP() ORDER BY created ASC LIMIT '.self::MAXJOBS;
        $list = $db->GetArray($sql);
        if (!$list) {
            return;
        }

        $out = [];
        foreach ($list as $row) {
            if (!empty($row['module'])) {
                $mod = \cms_utils::get_module($row['module']);
                if (!is_object($mod)) {
                    throw new \RuntimeException('Job '.$row['name'].' requires module '.$row['module'].' That could not be loaded');
                }
            }
            $obj = unserialize($row['data']);
            $obj->set_id($row['id']);
            $obj->force_start = $row['start']; // in case this job was modified.
            $out[] = $obj;
        }

        return $out;
    }

    public static function get_jobs($check_only = false)
    {
        $db = \CmsApp::get_instance()->GetDb();

        $limit = ($check_only) ? 1 : self::MAXJOBS;

        $sql = 'SELECT * FROM '.\CmsJobManager::table_name().' WHERE start < UNIX_TIMESTAMP() AND created < UNIX_TIMESTAMP() ORDER BY errors ASC,created ASC LIMIT '.$limit;
        $list = $db->GetArray($sql);
        if (!$list) {
            return;
        }
        if ($check_only) {
            return true;
        }

        $out = [];
        foreach ($list as $row) {
            if (!empty($row['module'])) {
                $mod = \cms_utils::get_module($row['module']);
                if (!is_object($mod)) {
                    audit('', 'CmsJobManager', sprintf('Could not load module %s required by job %s', $row['module'], $row['name']));
                    continue;
                }
            }
            $obj = unserialize($row['data']);
            $obj->set_id($row['id']);
            $obj->force_start = $row['start']; // in case this job was modified.
            $out[] = $obj;
        }

        return $out;
    }

    public static function clear_bad_jobs()
    {
        $mod = \cms_utils::get_module('CmsJobManager');
        $now = time();
        $lastrun = (int) $mod->GetPreference('last_badjob_run');
        if ($lastrun + self::MINGAP >= $now) {
            return;
        }

        $db = $mod->GetDb();
        $sql = 'SELECT * FROM '.\CmsJobManager::table_name().' WHERE errors >= ?';
        $list = $db->GetArray($sql, [self::MINERRORS]);
        if (is_array($list) && count($list)) {
            $idlist = [];
            foreach ($list as $row) {
                $obj = unserialize($row['data']);
                if (!is_object($obj)) {
                    debug_to_log(__METHOD__);
                    debug_to_log('Problem deserializing row');
                    debug_to_log($row);
                    continue;
                }
                $obj->set_id($row['id']);
                $idlist[] = (int) $row['id'];
                \CMSMS\HookManager::do_hook(\CmsJobManager::EVT_ONFAILEDJOB, [ 'job' => $obj ]);
            }
            $sql = 'DELETE FROM '.\CmsJobManager::table_name().' WHERE id IN ('.implode(',', $idlist).')';
            $db->Execute($sql);
            audit('', $mod->GetName(), 'Cleared '.count($idlist).' bad jobs');
        }
        $mod->SetPreference('last_badjob_run', $now);
    }
}
