<?php
# class: ExternalHandlerJob for jobs with external handlers (plugins or static functions)
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

use CmsApp;
use CMSMS\UserTagOperations;
use RuntimeException;
use function cms_to_bool;

/**
 * A type of job that calls a function (user-plugin or static function) for processing.
 *
 * If a module is specified for this object, then the module will be loaded before calling the handler.
 *
 * @package CMS
 * @author Robert Campbell
 *
 * @since 2.2
 * @property string $function The callable function name.
 * @property bool $is_udt Whether $function is the name of a user-plugin.
 */
class ExternalHandlerJob extends Job
{
    /**
     * @ignore
     */
    const HANDLER_UDT   = '_UDT_'; //unused ?

    /**
     * @ignore
     */
    protected $_data = [
        'function'=>null,
        'is_udt'=>FALSE,
    ];

    /**
     * @ignore
     */
    public function __get($key)
    {
        switch( $key ) {
        case 'function':
        case 'is_udt':
            return $this->_data[$key];

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
        case 'function':
            $this->_data[$key] = trim($val);
            break;

        case 'is_udt':
            $this->_data[$key] = cms_to_bool($val);
            break;

        default:
            return parent::__set($key,$val);
        }
    }

    /**
     * @ignore
     */
    public function execute()
    {
        if( $this->is_udt ) {
            UserTagOperations::get_instance()->CallUserTag($this->function /*, $params = [], $smarty_ob = null*/);  //TODO UDTfiles args
        }
        else {
            // call the function, pass in $this
            $module_name = $this->module;
            if( $module_name ) {
                $mod_obj = CmsApp::get_instance()->GetModule($module_name);
                if( !is_object($mod_obj) ) throw new RuntimeException('Job requires '.$module_name.' but the module could not be loaded');
            }
            call_user_func($this->function);
//TODO also support regular plugins
//TODO also support callables in general
        }
    }
}
