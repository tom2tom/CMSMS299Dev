<?php
# job-related utility-methods for CmsJobManager module
# Copyright (C) 2016-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
# See license details at the top of file CmsJobManager.module.php

namespace CmsJobManager;

use cms_utils;
use CmsApp;
use CmsJobManager;
use CMSMS\Events;
use CMSMS\HookManager;
use RuntimeException;
use function audit;
use function debug_to_log;

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

	/**
	 * @return mixed array | null
	 * @throws RuntimeException
	 */
    public static function get_all_jobs()
    {
        $db = CmsApp::get_instance()->GetDb();
        $sql = 'SELECT * FROM '.CmsJobManager::TABLE_NAME.' WHERE created < UNIX_TIMESTAMP() ORDER BY created ASC LIMIT '.self::MAXJOBS;
        $list = $db->GetArray($sql);
        if (!$list) {
            return;
        }

        $out = [];
        foreach ($list as $row) {
            if (!empty($row['module'])) {
                $mod = cms_utils::get_module($row['module']);
                if (!is_object($mod)) {
                    throw new RuntimeException('Job '.$row['name'].' requires module '.$row['module'].' That could not be loaded');
                }
            }
            $obj = unserialize($row['data']); //, ['allowed_classes'=>['CMSMS\\Async\\Job']]);
            $obj->set_id($row['id']);
            $obj->force_start = $row['start']; // in case this job was modified
            $out[] = $obj;
        }

        return $out;
    }

	/**
	 * @param bool $check_only Optional flag whether to merely check for existence of relevant job(s). Default false.
	 * @return mixed array | true | null
	 */
    public static function get_jobs($check_only = false)
    {
        $db = CmsApp::get_instance()->GetDb();

        $limit = ($check_only) ? 1 : self::MAXJOBS;

        $sql = 'SELECT * FROM '.CmsJobManager::TABLE_NAME.' WHERE start < UNIX_TIMESTAMP() AND created < UNIX_TIMESTAMP() ORDER BY errors ASC,created ASC LIMIT '.$limit;
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
                $mod = cms_utils::get_module($row['module']);
                if (!is_object($mod)) {
                    audit('', 'CmsJobManager', sprintf('Could not load module %s required by job %s', $row['module'], $row['name']));
                    continue;
                }
            }
            $obj = unserialize($row['data']); //, ['allowed_classes'=>['CMSMS\\Async\\Job']]);
            $obj->set_id($row['id']);
            $obj->force_start = $row['start']; // in case this job was modified
            $out[] = $obj;
        }

        return $out;
    }

    public static function clear_bad_jobs()
    {
        $mod = cms_utils::get_module('CmsJobManager');
        $now = time();
        $lastrun = (int) $mod->GetPreference('last_badjob_run');
        if ($lastrun + self::MINGAP >= $now) {
            return;
        }

        $db = $mod->GetDb();
        $sql = 'SELECT * FROM '.CmsJobManager::TABLE_NAME.' WHERE errors >= ?';
        $list = $db->GetArray($sql, [self::MINERRORS]);
        if ($list) {
            $idlist = [];
            foreach ($list as $row) {
                $obj = unserialize($row['data']); //, ['allowed_classes'=>['CMSMS\\Async\\Job']]);
                if (!is_object($obj)) {
                    debug_to_log(__METHOD__);
                    debug_to_log('Problem deserializing row');
                    debug_to_log($row);
                    continue;
                }
                $obj->set_id($row['id']);
                $idlist[] = (int) $row['id'];
                HookManager::do_hook(CmsJobManager::EVT_ONFAILEDJOB, [ 'job' => $obj ]); //TODO BAD no namespace, some miscreant handler can change the parameter ... 
                Events::SendEvent('CmsJobManager',CmsJobManager::EVT_ONFAILEDJOB, [ 'job' => $obj ]); //since 2.3
            }
            $sql = 'DELETE FROM '.CmsJobManager::TABLE_NAME.' WHERE id IN ('.implode(',', $idlist).')';
            $db->Execute($sql);
            audit('', $mod->GetName(), 'Cleared '.count($idlist).' bad jobs');
        }
        $mod->SetPreference('last_badjob_run', $now);
    }
}
