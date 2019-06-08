<?php
# Base class for a Cron job.
# Copyright (C) 2016-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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
 * An abstract base class for a CronJob.
 *
 * A Cron job is a CMSMS Job in that recurs at a specified interval
 * and can have an end/until datetime.
 *
 * @package CMS
 * @author Robert Campbell
 *
 * @since 2.2
 */
abstract class CronJob extends Job
{
    use CronJobTrait;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->_data['start'] = time();
    }
}
