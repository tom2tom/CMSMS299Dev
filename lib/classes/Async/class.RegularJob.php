<?php
/*
Class RegularJob: for processing old style pseudocron tasks as asynchronous jobs.
Copyright (C) 2016-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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
namespace CMSMS\Async;

use CmsRegularTask;
use CMSMS\Async\RecurType;
use CMSMS\IRegularTask;
use LogicException;
use UnexpectedValueException;

/**
 * This class enables using old CmsRegularTask pseudocron tasks as asynchronous background jobs
 *
 * @package CMS
 *
 * @since 3.0
 * @since 2.2 as RegularTask
 * @property CmsRegularTask | IRegularTask $task The task to convert
 */
class RegularJob extends CronJob
{
    /**
     * Constructor
     *
     * @param CmsRegularTask | IRegularTask $task
     */
    #[\ReturnTypeWillChange]
    public function __construct($task)
    {
        parent::__construct();
        $this->_data = [
            'task' => $task,
            'type' => get_class($task),
            'name' => $task->get_name(),
            'frequency' => RecurType::RECUR_SELF
        ] + $this->_data;
    }

    /* *
     * @ignore
     *
     * @param string $key
     * @return mixed
     */
/*    #[\ReturnTypeWillChange]
    public function __get(string $key)
    {
        return parent::__get($key);
    }
*/

    /**
     * @ignore
     *
     * @param string $key
     * @param mixed $val
     * @throws UnexpectedValueException
     */
    #[\ReturnTypeWillChange]
    public function __set(string $key, $val)
    {
        switch ($key) {
        case 'task':
            if (!($val instanceof IRegularTask || $val instanceof CmsRegularTask)) {
                throw new UnexpectedValueException("Invalid value for '$key' property in ".static::class);
            }
            $this->_data['task'] = $val;
            $this->_data['type'] = get_class($val);
            break;
        default:
            parent::__set($key, $val);
        }
    }

    /**
     * Perform the task, if its test() affirms
     * @return int 0|1|2 indicating execution status
     * @throws LogicException
     */
    public function execute()
    {
        $task = $this->_data['task'];
        if (!$task || !is_object($task)) {
            $class = $this->_data['type'];
            if ($class) {
                $task = new $class();
                if ($task) {
                    $this->_data['task'] = $task;
                }
            }
            if (!$task || !is_object($task)) {
                throw new LogicException(__CLASS__.' job is being executed, but has no task associated');
            }
        }
        $now = time();
        if ($task->test($now)) {
            if ($task->execute($now)) {
                $task->on_success($now);
                return 2;
            } else {
                $task->on_failure($now);
                return 0;
            }
        }
        return 1;
    }
}
