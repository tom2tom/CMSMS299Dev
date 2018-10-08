<?php
# Class RegularTask: for processing old style pseudocron tasks as asynchronous jobs.
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

use CmsRegularTask;
use LogicException;

/**
 * This class allows using CmsRegularTask pseudocron tasks as asynchronous background jobs
 *
 * @package CMS
 * @author Robert Campbell
 *
 * @since 2.2
 * @property CmsRegularTask $task The task to convert.
 */
class RegularTask extends Job
{
    /**
     * @ignore
     */
    private $_task;

    /**
     * Constructor
     *
     * @param CmsRegularTask $task
     */
    public function __construct(CmsRegularTask $task)
    {
        parent::__construct();
        $this->_task = $task;
        $this->name = $task->get_name();
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
        default:
            return parent::__get($key);
        }
    }

    /**
     * @ignore
	 *
	 * @param type $key
	 * @param CmsRegularTask $val
	 * @return type
	 * @throws LogicException
	 */
    public function __set($key,$val)
    {
        switch( $key ) {
        case 'task':
            if( !$val instanceof CmsRegularTask ) throw new LogicException('Invalid value for '.$key.' in a '.__CLASS__);
            $this->_task = $val;
            break;

        default:
            return parent::__set($key,$val);
        }
    }

    /**
	 * Perform the task
	 * @throws LogicException
	 */
    public function execute()
    {
        if( !$this->_task ) throw new LogicException(__CLASS__.' job is being executed, but has no task associated');
        $task = $this->_task;
        $now = time();
        if( $task->test($now) ) {
            if( $task->execute($now) ) {
                $task->on_success($now);
            } else {
                $task->on_failure($now);
                ++$this->errors;
            }
        }
    }
}
