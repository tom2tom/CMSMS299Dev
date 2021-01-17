<?php
/*
AdminLog module task: consolidate equivalent log entries
Copyright (C) 2017-2021 CMS Made Simple Foundation <foundationcmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace AdminLog;

use AdminLog\storage;
use CMSMS\AppParams;
use CMSMS\AppSingle;
use CMSMS\Async\CronJob;
use CMSMS\Async\RecurType;
use const CMS_DB_PREFIX;

class ReduceLogJob extends CronJob
{
    const LASTRUN_SITEPREF = 'Adminlog'.AppParams::NAMESPACER.'Reduce_lastexecute';
    const TABLE = CMS_DB_PREFIX.storage::TABLENAME;

    private $_queue;

    public function __construct()
    {
        parent::__construct();
        $this->name = 'AdminLog\\Reduce';
        $this->frequency = RecurType::RECUR_DAILY;
        $this->module = 'AdminLog';
        $this->_queue = [];
    }

    /**
     * Perform the task
     */
    public function execute()
    {
        $time = time();
        $last_execute = (int)AppParams::get(self::LASTRUN_SITEPREF, 0);
        $mintime = max($last_execute - 60,$time - 24 * 3600);
        $table = self::TABLE;
        $sql = "SELECT * FROM $table WHERE timestamp >= ? ORDER BY timestamp";
        $db = AppSingle::Db();
        $rst = $db->Execute($sql,[$mintime]);

        $prev = [];
        while ($rst && !$rst->EOF()) {
            $row = $rst->fields;
            if ($prev && $this->is_same($prev,$row)) {
                $this->queue_for_deletion($prev);
            } elseif ($this->have_queued()) {
                $this->adjust_last();
                $this->clear_queued();
            }
            $prev = $row;
            if (!$rst->MoveNext()) {
                break;
            }
        }
        if ($rst) { $rst->Close(); }
        if ($this->have_queued()) {
            $this->adjust_last();
            $this->clear_queued();
        }
    }

    protected function is_same(array $a, array $b) : bool
    {
        if (!is_array($a) || !is_array($b)) return false;

        foreach ($a as $key => $val) {
            switch ($key) {
            case 'timestamp':
                // ignore similar timestamps
                if (abs($b['timestamp'] - $val) > 3600) return false;
                break;
            default:
                if ($b[$key] != $val) return false;
                break;
            }
        }
        return true;
    }

    protected function queue_for_deletion($row)
    {
        $this->_queue[] = $row;
    }

    protected function have_queued() : bool
    {
       return (count($this->_queue) > 1);
    }

    protected function adjust_last()
    {
        $n = count($this->_queue);
        if ($n < 1) { return; }

        $lastrec = $this->_queue[$n - 1];
        $this->_queue = array_slice($this->_queue,0,-1);

        $db = AppSingle::Db();
        $table = self::TABLE;
        $lastrec['action'] = $lastrec['action'] . sprintf(' (repeated %d times)',$n);
        $sql = "UPDATE $table SET action = ? WHERE timestamp = ? AND user_id = ? AND username = ? AND item_id = ? AND item_name = ? AND ip_addr = ?";
        $db->Execute($sql,[$lastrec['action'],$lastrec['timestamp'],$lastrec['user_id'],$lastrec['username'],
                           $lastrec['item_id'],$lastrec['item_name'],$lastrec['ip_addr']]);
    }

    protected function clear_queued()
    {
        $n = count($this->_queue);
        if ($n < 1) { return; }

        $table = self::TABLE;
        $db = AppSingle::Db();
        $sql = "DELETE FROM $table WHERE timestamp = ? AND user_id = ? AND username = ? AND item_id = ? AND item_name = ? AND action = ? AND ip_addr = ?";
        for ($i = 0; $i < $n; $i++) {
            $rec = $this->_queue[$i];
            $db->Execute($sql,[$rec['timestamp'],$rec['user_id'],$rec['username'],
                               $rec['item_id'],$rec['item_name'],$rec['action'],$rec['ip_addr']]);
        }
        $this->_queue = [];
    }
}
