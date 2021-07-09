<?php
/*
Class: job to consolidate equivalent admin-log-table entries
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
namespace CMSMS\jobs;

use CMSMS\AppParams;
use CMSMS\AppSingle;
use CMSMS\Async\CronJob;
use CMSMS\Async\RecurType;
use CMSMS\Log\dbstorage;

class ReduceLogJob extends CronJob
{
    const LASTRUN_SITEPREF = 'ReduceLogJob'.AppParams::NAMESPACER.'lastexecute';

    private $_queue;

    public function __construct()
    {
        parent::__construct();
        $this->name = 'Core\\LogReduce';
        $this->frequency = RecurType::RECUR_DAILY;
        $this->_queue = [];
    }

    /**
     * Perform the job
     * @return int 0|1|2 indicating execution status
     */
    public function execute()
    {
        $time = time();
        $last_execute = (int)AppParams::get(self::LASTRUN_SITEPREF, 0);
        $mintime = max($last_execute - 60,$time - 24 * 3600);
        $table = dbstorage::TABLENAME;
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
            $rst->MoveNext();
        }
        if ($rst) { $rst->Close(); }
        if ($this->have_queued()) {
            $this->adjust_last();
            $this->clear_queued();
        }
        return 2; // TODO
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
        $table = dbstorage::TABLENAME;
        $lastrec['message'] .= sprintf(' (repeated %d times)',$n);
        $sql = "UPDATE $table SET message = ? WHERE timestamp = ? AND user_id = ? AND username = ? AND item_id = ? AND subject = ? AND ip_addr = ?";
        $db->Execute($sql,[$lastrec['message'],$lastrec['timestamp'],$lastrec['user_id'],$lastrec['username'],
                           $lastrec['item_id'],$lastrec['subject'],$lastrec['ip_addr']]);
    }

    protected function clear_queued()
    {
        $n = count($this->_queue);
        if ($n < 1) { return; }

        $table = dbstorage::TABLENAME;
        $db = AppSingle::Db();
        $sql = "DELETE FROM $table WHERE timestamp = ? AND user_id = ? AND username = ? AND item_id = ? AND subject = ? AND message = ? AND ip_addr = ?";
        for ($i = 0; $i < $n; $i++) {
            $rec = $this->_queue[$i];
            $db->Execute($sql,[$rec['timestamp'],$rec['user_id'],$rec['username'],
                               $rec['item_id'],$rec['subject'],$rec['message'],$rec['ip_addr']]);
        }
        $this->_queue = [];
    }
}
