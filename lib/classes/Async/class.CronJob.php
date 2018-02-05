<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Module: CronJob (c) 2016 by Robert Campbell
#         (calguy1000@cmsmadesimple.org)
#
#-------------------------------------------------------------------------
# CMS - CMS Made Simple is (c) 2005 by Ted Kulp (wishy@cmsmadesimple.org)
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#-------------------------------------------------------------------------
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
#
#-------------------------------------------------------------------------
#END_LICENSE

/**
 * This file defines the primary base class for a cron job.
 *
 * @package CMS
 */

namespace CMSMS\Async;

/**
 * An abstract base class for a cronjob.
 *
 * A Cron job is different than a regular job in that it recurs at a specified frequency and can have an end/until date.
 *
 * @package CMS
 * @author Robert Campbell
 * @copyright Copyright (c) 2016, Robert Campbell <calguy1000@cmsmadesimple.org>
 * @since 2.2
 */
abstract class CronJob extends Job implements CronJobInterface {
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
