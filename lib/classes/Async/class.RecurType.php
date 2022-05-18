<?php
/*
Enum class: identifies types/frequencies of cron-job recurrence.
Copyright (C) 2018-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that lcense along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS\Async;

use CMSMS\BasicEnum;

final class RecurType extends BasicEnum
{
    /**
     * Constant indicating that the job should recur every 15 minutes.
     */
    const RECUR_15M = 2;

    /**
     * Constant indicating that the job should recur every 30 minutes.
     */
    const RECUR_30M = 3;

    /**
     * Constant indicating that the job should recur every hour.
     */
    const RECUR_HOURLY = 4;

    /**
     * Constant indicating that the job should recur every 2 hours.
     */
    const RECUR_120M = 5;
    const RECUR_2H = 5; //alias

    /**
     * Constant indicating that the job should recur every 3 hours.
     */
    const RECUR_180M = 6;
    const RECUR_3H = 6; //alias

    /**
     * Constant indicating that the job should recur twice per day.
     */
    const RECUR_HALFDAILY = 9;
    const RECUR_12H = 9; //alias

    /**
     * Constant indicating that the job should recur daily.
     */
    const RECUR_DAILY = 10;

    /**
     * Constant indicating that the job should recur weekly.
     */
    const RECUR_WEEKLY = 20;

    /**
     * Constant indicating that the job should recur monthly.
     */
    const RECUR_MONTHLY = 30;

    /**
     * Constant indicating that the job determines its own recurrence interval.
     */
    const RECUR_SELF = 200;
    const RECUR_ALWAYS = 200; //alias

    /**
     * Constant indicating that the job does not recur.
     */
    const RECUR_NONE = 1000;

    #[\ReturnTypeWillChange]
    private function __construct() {}
    #[\ReturnTypeWillChange]
    private function __clone() {}
} // class
