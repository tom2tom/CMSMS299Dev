<?php
/*
Abstract class defining a CMSMS asynchronous job.
Copyright (C) 2017-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\internal\JobOperations;
use LogicException;
use UnexpectedValueException;

/**
 * A base class defining a job, and mechanisms for saving and retrieving that job.
 *
 * @package CMS
 * @author Robert Campbell
 *
 * @abstract
 * @since 2.2
 * @property-read int $id A unique id for this job (generated on save).
 * @property-read int $created The *NIX timestamp that this job was first created.
 * @property int $start The *NIX timestamp that this job should next start.
 * @property-read int $errors The number of errors encountered while trying to process this job.
 * @property string $name The name of this job.  If not specified, the class-name will be used.
 * @property string $module The related-module name, if needed.
 */
abstract class Job
{
    /**
     * Class properties
     * @ignore
     */
    protected $_data = [
     'id' => 0,
     'created' => 0,
     'start' => 0, //next-start timestamp, or 0 for never
     'errors' => 0,
     'name' => '',
     'module' => null,
    ];

    /**
     * Constructor
     * @param array $params Optional assoc array of valid class properties
     *  each member like propname => propval
     */
    public function __construct($params = [])
    {
        $now = time();
        $this->_data['created'] = $now;
        $this->_data['start'] = $now;
        $this->_data['name'] = static::class;
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
        case 'id':
        case 'created':
        case 'start':
        case 'errors':
            return (int) $this->_data[$key];

        case 'name':
        case 'module':
            return trim($this->_data[$key]);

        case 'manager_module':
            return ''; // not used now AppSingle::App()->GetJobManager();

        default:
            return (isset($this->_data[$key])) ? $this->_data[$key] : null;
        }
    }

    /**
     * @ignore
     */
    public function __set($key, $val)
    {
        switch ($key) {
        case 'id':
            $this->set_id($val);
            break;

        case 'created':
            $this->_data[$key] = min(time(), (int)$val);
            break;

        case 'start':
           if ($val != 0) {
               $val = min(time(), $val);
           }
          // no break here
        case 'errors':
            $this->_data[$key] = (int) $val;
            break;

        case 'name':
        case 'module':
            $this->_data[$key] = trim($val);
            break;

        default:
            $this->_data[$key] = $val;
        }
    }

    /**
     * @final
     * @param int $id >= 1 (e.g. the id-field value from a database table
     *  which records jobs-data)
     * @throws UnexpectedValueException
     * @throws LogicException
     */
    final public function set_id($id)
    {
        $id = (int) $id;
        //TODO exceptions useless in async context
        if ($id < 1) {
            throw new UnexpectedValueException('Invalid id passed to '.static::class.'::'.__FUNCTION__);
        }
        if ($this->_data['id']) {
            throw new LogicException('Cannot replace a job id');
        }
        $this->_data['id'] = $id;
    }

    /**
     * Delete this job from the database.
     *
     * @throws LogicException if a job manager module is not available,
     * or if for some reason the job could not be removed.
     */
    public function delete()
    {
/*        // get the asyncmanager module
        $module = AppSingle::App()->GetJobManager();
        if ($module) {
            $module->delete_job($this);
            $this->_data['id'] = 0;
            return;
        }
        throw new LogicException('Cannot delete a job... no job-manager module is available');
*/
        (new JobOperations())->unload_job($this);
        $this->_data['id'] = 0;
    }

    /**
     * Save this job.
     *
     * @throws LogicException if a job manager module is not available,
     * or if for some reason the job could not be saved.
     */
    public function save()
    {
/*        // get the asyncmanager module
        $module = AppSingle::App()->GetJobManager();
        if ($module) {
            $this->_data['id'] = (int)$module->save_job($this);
            return;
        }
        throw new LogicException('Cannot save a job... no job-manager module is available');
*/
        $this->_data['id'] = (new JobOperations())->load_job($this);
    }

    /**
     * Execute this job.
     *
     * @abstract
     * <strong>Note:</strong> all jobs should be able to execute properly within one HTTP request.
     * Jobs cannot count on any user, or data stored in session variables.
     * Any data that is needed for the job should either be stored with
     * the job object, or stored in the database in a context-independent format.
     * @return mixed void | since 2.99 int indicating execution-status
     *   0 = failed, 1 = no need to do anything, 2 = success
     */
    abstract public function execute();

    /**
     * Get the 'base' name of the class.
     *
     * @since 2.99
     * @return string
     */
    protected function shortname() : string
    {
        $val = static::class;
        $p = strrpos($val, '\\');
        return ($p !== false) ? substr($val, $p + 1) : $val;
    }
}
