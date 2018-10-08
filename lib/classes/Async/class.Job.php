<?php
# Abstract class defining an asynchronous job.
# Copyright (C) 2017-2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\ModuleOperations;
use LogicException;
use const CMS_VERSION;

/**
 * A class defining an asynchronous job, and mechanisms for saving and retrieving that job.
 *
 * @package CMS
 * @author Robert Campbell
 *
 * @since 2.2
 * @property-read int $id A unique integer id for this job (generated on save).
 * @property string $name The name of this job.  If not specified a unique random name will be generated.
 * @property-read int $created The unix timestamp that this job was first created.
 * @property string $module The module that created this job.  Useful if the job ever needs to be deleted.
 * @property int $start The minimum time that this job should start at.
 * @property-read int $errors The number of errors encountered while trying to pricess this job.
 */
abstract class Job
{
    /**
     * @ignore
     */
    const MODULE_NAME = 'CmsJobManager';

    /**
     * @ignore
     */
    private $_id;

    /**
     * @ignore
     */
    private $_name;

    /**
     * @ignore
     */
    private $_created;

    /**
     * @ignore
     */
    private $_module;

    /**
     * @ignore
     */
    private $_start;

    /**
     * @ignore
     */
    private $_errors;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $now = time();
        $this->_created = $this->_start = $now;
        $this->_name = md5(__FILE__.CMS_VERSION.get_class($this).rand(0,999)); // a pretty random name to this job
    }

    /**
     * @ignore
     */
    public function __get($key)
    {
        $tkey = '_'.$key;
        switch( $key ) {
        case 'id':
        case 'created':
        case 'start':
        case 'errors':
            return (int) $this->$tkey;

        case 'name':
        case 'module':
            return trim($this->$tkey);

        default:
            throw new LogicException("$key is not a gettable member of ".get_class($this));
        }
    }

    /**
     * @ignore
     */
    public function __set($key,$val)
    {
        $tkey = '_'.$key;
        switch( $key ) {
        case 'name':
        case 'module':
            $this->$tkey = trim($val);
            break;

        case 'force_start':
            // internal use only.
            $this->_start = (int) $val;
            break;

        case 'start':
        case 'errors':
            $this->$tkey = (int) $val;
            break;

        default:
            throw new LogicException("$key is not a settable member of ".get_class($this));
        }
    }

    /**
     * @ignore
     * @internal
     */
    final public function set_id($id)
    {
        $id = (int) $id;
        if( $id < 1 ) throw new LogicException('Invalid id passed to '.__METHOD__);
        if( $this->_id ) throw new LogicException('Cannot overwrite an id in a job that has one');
        $this->_id = $id;
    }

    /**
     * Delete this job from the database.
     *
     * This method will throw exceptions if the job manager module is not available, or if for some reason the job could not be removed.
     */
    public function delete()
    {
        // get the asyncmanager module
        $module = ModuleOperations::get_instance()->get_module_instance(self::MODULE_NAME);
        if( !$module ) throw new LogicException('Cannot delete a job... the CmsJobMgr module is not available');
        $module->delete_job($this);
        $this->_id = null;
    }

    /**
     * Save this job to the database.
     *
     * This method will throw exceptions if the job manager module is not available,
	 * or if for some reason the job could not be saved.
     */
    public function save()
    {
        // get the AsyncManager module
        // call it's save method with this.
        $module = ModuleOperations::get_instance()->get_module_instance(self::MODULE_NAME);
        if( !$module ) throw new LogicException('Cannot save a job... the CmsJobMgr module is not available');
        $this->_id = (int) $module->save_job($this);
    }

    /**
     * Abstract function to execute the job.
     *
     * <strong>Note:</strong> all jobs should be able to execute properly within one HTTP request.
     * Jobs cannot count on administrator or data stored in session variables.
	 * Any data that is needed for the job to process should either be stored with
	 * the job object, or stored in the database in a user-independent format.
     */
    abstract public function execute();
}

