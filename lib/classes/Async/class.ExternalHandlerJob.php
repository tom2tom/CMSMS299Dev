<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Module: \CMSMS\Database\Connection (c) 2016 by Robert Campbell
#         (calguy1000@cmsmadesimple.org)
#  A class to define interaction with a database.
#
#-------------------------------------------------------------------------
# CMS - CMS Made Simple is (c) 2005 by Ted Kulp (wishy@cmsmadesimple.org)
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#-------------------------------------------------------------------------
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
#
#-------------------------------------------------------------------------
#END_LICENSE

/**
 * This file provides a utility for creating jobs with external handlers (UDT's or static functions)
 *
 * @package CMS
 */

namespace CMSMS\Async;

/**
 * A type of job that calls an external function for processing.  i.e: a UDT or a static function.
 *
 * If a module is specified for this object, then the module will be loaded before calling the handler.
 *
 * @package CMS
 * @author Robert Campbell
 * @copyright Copyright (c) 2015, Robert Campbell <calguy1000@cmsmadesimple.org>
 * @since 2.2
 * @property string $function The callback function name.
 * @property bool $is_udt Indicates that the function is the name of a simple plugin.
 */
class ExternalHandlerJob extends Job
{
    /**
     * @ignore
     */
    const HANDLER_UDT   = '_UDT_';

    /**
     * @ignore
     */
    private $_data = ['function'=>null,'is_udt'=>FALSE];

    /**
     * @ignore
     */
    public function __get($key)
    {
        switch( $key ) {
        case 'function':
            return trim($this->_data[$key]);

        case 'is_udt':
            return (bool) $this->_data[$key];

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
            $mgr = \CmsApp::get_instance()->GetSimplePluginOperations();
            $mgr->call_plugin( $this->function );
        }
        else {
            // call the function, pass in this.
            $module_name = $this->module;
            if( $module_name ) {
                $mod_obj = \CmsApp::get_instance()->GetModule($module_name);
                if( !is_object($mod_obj) ) throw new \RuntimeException('Job requires '.$module_name.' but the module could not be loaded');
            }
            call_user_func($this->function);
        }
    }
}
