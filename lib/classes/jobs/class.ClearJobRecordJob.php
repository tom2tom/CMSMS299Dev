<?php
/*
Class ClearJobParamsJob: for periodic cleanup of redundant async-job signatures
Copyright (C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS\jobs;

use CMSMS\Async\CronJob;
use CMSMS\Async\RecurType;
use CMSMS\SingleItem;
use const CMS_DB_PREFIX;

class ClearJobRecordJob extends CronJob
{
    public function __construct()
    {
        parent::__construct();
        $this->name = 'Core\ClearJobRecord';
        $this->frequency = RecurType::RECUR_WEEKLY;
    }

    /**
     * @ignore
     * @return int 0|1|2 indicating execution status
      */
    public function execute()
    {
        $db = SingleItem::Db();
        $limit = $db->dbTimeStamp(time() - 86400);
        $sql = 'DELETE FROM '.CMS_DB_PREFIX.'job_records WHERE create_date < '.$limit;
        $db->execute($sql);
        return 2;
    }
}

\class_alias('CMSMS\jobs\ClearJobRecordJob', 'ClearJobRecordTask', false);
