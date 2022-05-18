<?php
/*
Class ClearLocksJob: for periodic cleanup of expired item-locks
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
use CMSMS\LockOperations;
use Throwable;

class ClearLocksJob extends CronJob
{
    #[\ReturnTypeWillChange]
    public function __construct()
    {
        parent::__construct();
        $this->name = 'Core\ClearLocks';
        $this->frequency = RecurType::RECUR_DAILY;
    }

    /**
     * @ignore
     * @return int 0|1|2 indicating execution status
      */
    public function execute()
    {
        try {
            LockOperations::delete_expired();
            return 2;
        } catch (Throwable $t) {
            return 0;
        }
    }
}
\class_alias('CMSMS\jobs\ClearLocksJob', 'ClearLocksTask', false);
