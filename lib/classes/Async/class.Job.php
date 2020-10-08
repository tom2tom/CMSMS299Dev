<?php
# Abstract class defining a CMSMS job.
# Copyright (C) 2017-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\App;
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
 * @property string $name The name of this job.  If not specified, the class-name will be used.
 * @property string $module The related-module name, if needed.
 * @property-read int $created The *NIX timestamp that this job was first created.
 * @property int $start The minimum time that this job should start at.
 * @property-read int $errors The number of errors encountered while trying to process this job.
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
     'start' => 1, //next start time, or 0
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
        $this->_data['created'] = time();
        $this->_data['name'] = static::class;
        if( $params ) {
            foreach( $params as $key => $val ) {
                $this->__set($key,$val);
            }
        }
    }

    /**
     * @ignore
     */
    public function __get($key)
    {
        switch( $key ) {
        case 'id':
        case 'created':
        case 'start':
        case 'errors':
            return (int) $this->_data[$key];

        case 'name':
        case 'module':
            return trim($this->_data[$key]);

        case 'manager_module':
            return App::get_instance()->GetJobManager();

        default:
            return (isset($this->_data[$key])) ? $this->_data[$key] : null;
        }
    }

    /**
     * @ignore
     */
    public function __set($key,$val)
    {
        switch( $key ) {
        case 'id':
            $this->set_id($val);
            break;

        case 'name':
        case 'module':
            $this->_data[$key] = trim($val);
            break;

        case 'start':
        case 'errors':
            $this->_data[$key] = (int) $val;
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
        if( $id < 1 ) throw new UnexpectedValueException('Invalid id passed to '.static::class.'::'.__FUNCTION__);
        if( $this->_data['id'] ) throw new LogicException('Cannot replace a job id');
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
        // get the asyncmanager module
        $module = App::get_instance()->GetJobManager();
        if( $module ) {
            $module->delete_job($this);
            $this->_data['id'] = 0;
            return;
        }
        throw new LogicException('Cannot delete a job... no job-manager module is available');
    }

    /**
     * Save this job.
     *
     * @throws LogicException if a job manager module is not available,
     * or if for some reason the job could not be saved.
     */
    public function save()
    {
        // get the asyncmanager module
        $module = App::get_instance()->GetJobManager();
        if( $module ) {
            $this->_data['id'] = (int)$module->save_job($this);
            return;
        }
        throw new LogicException('Cannot save a job... no job-manager module is available');
    }

    /**
     * Execute this job.
     *
     * @abstract
     * <strong>Note:</strong> all jobs should be able to execute properly within one HTTP request.
     * Jobs cannot count on any user, or data stored in session variables.
     * Any data that is needed for the job should either be stored with
     * the job object, or stored in the database in a context-independent format.
     */
    abstract public function execute();
}
