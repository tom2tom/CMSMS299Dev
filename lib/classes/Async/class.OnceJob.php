<?php
/*
Class OnceJob: for processing a job once (each time it is enabled).
Copyright (C) 2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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
use ModuleOperations;
use LogicException;

/**
 * This class enables running an asynchronous background job once after
 * it has been enabled (which may happen as often as appropriate).
 *
 * @package CMS
 * @since 2.99
 */
abstract class OnceJob extends Job
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->_data = ['frequency' => RecurType::NONE,
        'start' => 0 // disabled until something triggers a change
        ] + $this->_data;
    }

    /**
     * Executor called by parent
     * @return int 0|1|2 indicating execution status
     */
    public function execute()
    {
        $this->run();
        $module = ModuleOperations::get_instance()->get_module_instance($this->manager_module);
        if (!$module) {
            throw new LogicException('Cannot save a job... the job-manger module is not available');
        }
        $module->suspend_job($this->name, $this->module);
        return 2; // TODO
    }

    /**
     * Abstract function to actually execute the job
     *
     * <strong>Note:</strong> all jobs should be able to execute properly within one HTTP request.
     * Jobs cannot count on administrator or data stored in session variables.  Any data that is needed for the job to process
     * should either be stored with the job object, or stored in the database in a user-independant format.
     */
    abstract public function run();
}
