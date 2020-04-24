<?php
# Trait: the primary functionality for recurring cron jobs.
# Copyright (C) 2016-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\Async\RecurType;
use LogicException;

/**
 * A trait providing functionality for recurring cron jobs.
 *
 * @package CMS
 * @author Robert Campbell
 *
 * @since 2.2
 * @property string $frequency The frequency of the cron job
 * @property int $start The minimum start time of the cron job.  This property is adjusted after each and every execution of the cron job.
 * @property int $until A unix timestamp representing when the cron job should stop recurring.
 */
trait CronJobTrait
{
    /**
     * @ignore
     */
    private $_data = [ 'start'=>null, 'frequency' => RecurType::RECUR_NONE, 'until'=>null ];

    /**
     * @ignore
     */
    public function __get($key)
    {
        switch( $key ) {
        case 'frequency':
            return $this->_data[$key];

        case 'start':
        case 'until':
            return (int) $this->_data[$key];

        default:
            return parent::__get($key);
        }
    }

    /**
     * @ignore
     */
    public function __set($key,$val)
    {
        switch( $key ) {
        case 'frequency':
			if (RecurType::isValidValue($val)) {
                $this->_data[$key] = (int)$val;
			} else {
                throw new LogicException("$val is an invalid value for $key");
			}
            break;

        case 'force_start':
            // internal use only.
            $this->_data['start'] = (int) $val;
            break;

        case 'start':
            // this start overrides the one in the base class.
            $val = (int) $val;
            if( $val < time() - 60 ) throw new LogicException('Cannot adjust a start value to the past');
            // fall through.

        case 'until':
            $this->_data[$key] = (int) $val;
            break;

        default:
            parent::__set($key,$val);
        }
    }
}
