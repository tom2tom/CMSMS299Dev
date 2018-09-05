<?php
# Interface: defines constants needed for a cron job.
# Copyright (C) 2016-2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace CMSMS\Async;

/**
 * A simple interface to define constants needed for a cron job.
 *
 * @package CMS
 * @author Robert Campbell
 *
 * @since 2.2
 */
interface CronJobInterface
{
    /**
     * Constant indicating that this job does not recur (empty string is also used).
     */
    const RECUR_NONE  = '_none';

    /**
     * Constant indicating that ths job should recur every 15 minutes.
     */
    const RECUR_15M = '_15m';

    /**
     * Constant indicating that ths job should recur every 30 minutes.
     */
    const RECUR_30M = '_30m';

    /**
     * Constant indicating that ths job should recur every hour.
     */
    const RECUR_HOURLY  = '_hourly';

    /**
     * Constant indicating that ths job should recur every 2 hours.
     */
    const RECUR_120M = '_120m';

    /**
     * Constant indicating that ths job should recur every 3 hours.
     */
    const RECUR_180M = '_180m';

    /**
     * Constant indicating that ths job should recur daily..
     */
    const RECUR_DAILY   = '_daily';

    /**
     * Constant indicating that ths job should recur weekly..
     */
    const RECUR_WEEKLY  = '_weekly';

    /**
     * Constant indicating that ths job should recur monthly..
     */
    const RECUR_MONTHLY = '_monthly';
}

