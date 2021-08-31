<?php
/*
Abstract base class for a Cron job.
Copyright (C) 2016-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that License, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS\Async;

use CMSMS\Async\Job;
use CMSMS\Async\RecurType;
use LogicException;
use UnexpectedValueException;

/**
 * An abstract base class for a CronJob.
 *
 * A CronJob is a CMSMS Job in that recurs at a specified interval
 * and can have an end/until timestamp.
 *
 * @package CMS
 *
 * @since 2.2
 */
abstract class CronJob extends Job
{
    /**
     * Constructor
     * @param array $params Optional assoc array of valid class properties
     *  each member like propname => propval
     */
    public function __construct($params = [])
    {
        parent::__construct();
        $this->_data = ['frequency' => RecurType::RECUR_NONE, 'interval' => 60, 'until' => 0] + $this->_data;
        if ($params) {
            foreach ($params as $key => $val) {
                $this->__set($key, $val);
            }
        }
    }

    /**
     * @ignore
     */
    public function __get($key)
    {
        switch ($key) {
        case 'frequency':
            return $this->_data[$key];
        case 'frequencyname':
            return RecurType::getName($this->_data[$key]);
        case 'interval':
        case 'until':
            return (int) $this->_data[$key];
        default:
            return parent::__get($key);
        }
    }

    /**
     * @ignore
     */
    public function __set($key, $val)
    {
        switch ($key) {
        case 'frequency':
            if (!RecurType::isValidValue($val)) {
                throw new UnexpectedValueException("$val is an invalid value for $key in ".static::class);
            }
            $this->_data[$key] = (int)$val;
            break;

        case 'interval':
            $val = min((int)$val, 60);
            $this->_data[$key] = $val;
            break;

        case 'force_start':
            // internal use only.
            $this->_data['start'] = (int) $val;
            break;

        case 'start':
            // this start overrides the one in the base class.
            $val = (int) $val;
            if ($val > 0 && $val < time() - 60) {
                throw new LogicException('Cannot set a start time before now in '.static::class);
            }
            $this->_data[$key] = $val;
            break;

        case 'until':
            $val = max((int)$val, 0);
            $this->_data[$key] = $val;
            break;

        default:
            parent::__set($key, $val);
        }
    }
}
