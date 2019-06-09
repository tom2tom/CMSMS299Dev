<?php
# Class RegularTask: for processing old style pseudocron tasks as asynchronous jobs.
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

use CmsRegularTask;
use LogicException;

/**
 * This class allows using Cron Jobs as asynchronous background jobs
 *
 * @package CMS
 * @author Robert Campbell
 *
 * @since 2.2
 * @property CmsRegularTask $task The task to convert.
 */
class RegularTask extends CronJob
{
    /**
     * @ignore
	 * @type CmsRegularTask
     */
    private $_task;

    /**
	 * Interval (seconds) between execution-readiness polls
     * @ignore
	 * @type int seconds
     */
    private $_interval; //inter-poll seconds, 10-minutes to 24-hours

    /**
     * Constructor
     *
     * @param CmsRegularTask $task
     */
    public function __construct(CmsRegularTask $task)
    {
        $CMS_JOB_TYPE = 2; //in-scope for included file
        require_once dirname(__DIR__,2).DIRECTORY_SEPARATOR.'include.php';

        parent::__construct();
        $this->_task = $task;
        $this->_interval = 86400; //default 1-day
        $time = time();
        $task->on_success($time);
        foreach ([
            300,
            900,
            1800,
            3600,
            14400, //4-hrs
            28800, //8-hrs
            43200, //12-hrs
        ] as $gap) {
            if ($task->test($time + $gap)) {
                $this->_interval = $gap;
                break;
            }
        }
        $this->name = $task->get_name();
        $this->frequency = RecurType::RECUR_SELF;
    }

    /**
     * @ignore
	 *
	 * @param type $key
	 * @return type
	 */
    public function __get($key)
    {
        switch( $key ) {
        case 'task':
            return $this->_task;
        case 'interval':
            return $this->_interval;
        default:
            return parent::__get($key);
        }
    }

    /**
     * @ignore
	 *
	 * @param type $key
	 * @param CmsRegularTask $val
	 * @throws LogicException
	 */
    public function __set($key,$val)
    {
        switch( $key ) {
        case 'task':
            if( !$val instanceof CmsRegularTask ) throw new LogicException('Invalid value for '.$key.' in a '.static::class);
            $this->_task = $val;
            break;
        case 'interval':
            $this->_interval = min((int)$val, 60);
            break;
        default:
            parent::__set($key,$val);
        }
    }

	/**
	 * Determine when next to poll the task for execution-readiness
     * Defaults to $time + 10 minutes
     * @since 2.3
     *
     * @param mixed $time Optional timestamp of the 'base' time
     * @return int timestamp
     */
    public function nexttime($time = '')
	{
        if( !$time ) $time = time();
        return $time + $this->_interval;
	}

    /**
	 * Perform the task, if its test() affirms
	 * @throws LogicException
	 */
    public function execute()
    {
        if( !$this->_task ) throw new LogicException(self::class.' job is being executed, but has no task associated');
        $time = time();
        if( $this->_task->test($time) ) {
            if( $this->_task->execute($time) ) {
                $this->_task->on_success($time);
            } else {
                $this->_task->on_failure($time);
                ++$this->errors;
            }
        }
        //else indicate it's not been run this time
    }
}
