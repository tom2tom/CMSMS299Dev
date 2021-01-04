<?php
/*
Recorded-jobs methods for CMS Made Simple module CmsJobManager
Copyright (C) 2016-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
See license details at the top of file CmsJobManager.module.php
*/
namespace CmsJobManager;

use CmsJobManager;
use CMSMS\AppSingle;
use CMSMS\Events;
use CMSMS\Utils;
//use RuntimeException;
use Throwable;
use function audit;
use function debug_to_log;

/**
 * Recorded-jobs methods
 *
 * @since 2.99
 * @since 2.2 as JobQueue
 */
final class JobStore
{
    /**
     * Maximum no. of jobs per batch
     * Should never be > 100 pending jobs for a site
     */
    const MAXJOBS = 50;
    /**
     * Interval (seconds) between bad-job cleanups
     */
    const MINGAP = 3600;
    /**
     * Minimum no. of errors which signals a 'bad' job
     */
    const MINERRORS = 10;

    public static function have_jobs()
    {
        return self::get_jobs(true);
    }

    /**
     * @return array up to 50 members, mebbe empty
     */
    public static function get_all_jobs() : array
    {
        $now = time();
        $sql = 'SELECT * FROM '.CmsJobManager::TABLE_NAME." WHERE created < $now ORDER BY created";
        $db = AppSingle::Db();
        $rs = $db->SelectLimit($sql, self::MAXJOBS);
        if (!$rs) {
            return [];
        }

        $out = [];
        while (!$rs->EOF()) {
            $row = $rs->fields();
            if (!empty($row['module'])) {
                $mod = Utils::get_module($row['module']);
                if (!is_object($mod)) {
                    debug_to_log(sprintf('Could not load module %s required by job %s', $row['module'], $row['name']));
                    audit('', 'CmsJobManager', sprintf('Could not load module %s required by job %s', $row['module'], $row['name']));
//                    throw new RuntimeException('Job '.$row['name'].' requires module '.$row['module'].' That could not be loaded');
                }
            }
            try {
                $obj = unserialize($row['data']/*, ['allowed_classes' => ['allowed_classes' => Job-descentants, interface*-implmentors]]*/);
            } catch (Throwable $t) {
                $obj = null;
            }
            if (is_object($obj)) {
                $obj->set_id($row['id']);
                $obj->force_start = $row['start']; // in case this job was modified
                $out[] = $obj;
            } else {
                debug_to_log(__METHOD__);
                debug_to_log('Problem deserializing row');
                debug_to_log($row);
            }
            if (!$rs->MoveNext()) {
                break;
            }
        }
        $rs->Close();
        return $out;
    }

    /**
     * Get pending jobs, up to the prescribed batch-size limit, or just one if checking
     * @param bool $check_only Optional flag whether to merely check for existence
     *  of relevant job(s). Default false.
     * @return mixed array | bool
     */
    public static function get_jobs(bool $check_only = false)
    {
        $db = AppSingle::Db();
        $now = time();

        if ($check_only) {
            $sql = 'SELECT id FROM '.CmsJobManager::TABLE_NAME." WHERE start > 0 AND start <= $now AND (until = 0 OR until >= $now)";
            $rs = $db->SelectLimit($sql, 1);
            if ($rs) {
                $res = !$rs->EOF();
                $rs->Close();
                return $res;
            }
            return false;
        }

        $sql = 'SELECT * FROM '.CmsJobManager::TABLE_NAME." WHERE start > 0 AND start <= $now AND (until = 0 OR until >= $now) ORDER BY errors,created";
        $rs = $db->SelectLimit($sql, self::MAXJOBS);

        if (!$rs) {
            return false;
        }

        $out = [];
        while (!$rs->EOF()) {
            $row = $rs->fields();
            if (!empty($row['module'])) {
                $mod = Utils::get_module($row['module']);
                if (!is_object($mod)) {
                    debug_to_log(sprintf('Could not load module %s required by job %s', $row['module'], $row['name']));
                    audit('', 'CmsJobManager', sprintf('Could not load module %s required by job %s', $row['module'], $row['name']));
                }
            }
            try {
                $obj = unserialize($row['data']/*, ['allowed_classes' => Job-descentants, interface*-implmentors]*/);
            } catch (Throwable $t) {
                $obj = null;
            }
            if (is_object($obj)) {
                $obj->set_id($row['id']);
                $obj->force_start = $row['start']; // in case this job was modified
                $out[] = $obj;
            } else {
                debug_to_log(__METHOD__);
                debug_to_log('Problem deserializing row');
                debug_to_log($row);
            }
            if (!$rs->MoveNext()) {
                break;
            }
        }
        $rs->Close();
        return $out;
    }

    /**
     * At defined intervals, remove from the jobs table those which have recorded
     * more errors than the defined threshold
     */
    public static function clear_bad_jobs()
    {
        $mod = Utils::get_module('CmsJobManager');
        $now = time();
        $lastrun = (int) $mod->GetPreference('last_badjob_run');
        if ($lastrun + self::MINGAP >= $now) {
            return;
        }

        $db = AppSingle::Db();
        $sql = 'SELECT * FROM '.CmsJobManager::TABLE_NAME.' WHERE errors >= ?';
        $list = $db->GetArray($sql, [self::MINERRORS]);
        if ($list) {
            $idlist = [];
            foreach ($list as &$row) {
                try {
                    $obj = unserialize($row['data']/*, ['allowed_classes' => ['allowed_classes' => Job-descentants, interface*-implmentors]]*/);
                } catch (Throwable $t) {
                    $obj = null;
                }
                if (is_object($obj)) {
                    $obj->set_id($row['id']);
                    $idlist[] = (int) $row['id'];
                    Events::SendEvent('CmsJobManager', CmsJobManager::EVT_ONFAILEDJOB, ['job' => $obj]); //since 2.3
                } else {
                    debug_to_log(__METHOD__);
                    debug_to_log('Problem deserializing row');
                    debug_to_log($row);
                }
            }
            unset($row);
            $sql = 'DELETE FROM '.CmsJobManager::TABLE_NAME.' WHERE id IN ('.implode(',', $idlist).')';
            $db->Execute($sql);
            audit('', $mod->GetName(), 'Cleared '.count($idlist).' bad jobs');
        }
        $mod->SetPreference('last_badjob_run', $now);
    }
}
