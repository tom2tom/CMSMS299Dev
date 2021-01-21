<?php
/*
Class: ExternalHandlerJob for jobs having an 'external' handler (plugins etc)
Copyright (C) 2016-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\UserTagOperations;
use CMSMS\Utils;
use RuntimeException;
use function cms_to_bool;

/**
 * A type of job that calls a function (user-plugin or module-action or
 *  static callable) for processing.
 *
 * If a module is specified for this object, then that module will be
 *  loaded before calling the handler.
 *
 * @package CMS
 * @author Robert Campbell
 *
 * @since 2.2
 * @property mixed $function string | callable
 *  The user-plugin | action file | function name, or an actual callable.
 * @property bool $is_udt Whether $function is the name of a user-plugin.
 */
class ExternalHandlerJob extends Job
{
    /**
     * @ignore
     */
    const HANDLER_UDT = '_UDT_'; //unused here

    /**
     * Constructor
     * @param array $params Optional assoc array of valid class properties
     *  each member like propname => propval
     */
    public function __construct($params = [])
    {
        parent::__construct();
        $this->_data = ['function' => '', 'is_udt' => false] + $this->_data;
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
    public function __set($key, $val)
    {
        switch ($key) {
        case 'function':
            if (is_string($val)) {
                $this->_data[$key] = trim($val);
            } else {
                $this->_data[$key] = $val;
                $this->_data['is_udt'] = false;
            }
            break;

        case 'is_udt':
            $val = cms_to_bool($val);
            $this->_data[$key] = $val;
            if ($val) {
                $val = $this->_data['function'];
                if (!is_string($val) || is_callable($val)) {
                    $this->_data['function'] = '';
                }
            }
            break;

        default:
            return parent::__set($key, $val);
        }
    }

    /**
     * @ignore
     * @return int 0|1|2 indicating execution status
     */
    public function execute()
    {
        if ($this->is_udt) {
            UserTagOperations::get_instance()->CallUserTag($this->function /*, $params = [], $smarty_ob = null*/);  //TODO plugin parameters missing
//TODO also support regular plugins
        } elseif ($this->module && preg_match('/^action\.(.+)\.php$/', $this->function, $matches)) {
            $mod_obj = Utils::get_module($this->module);
            //TODO exceptions useless in async context
            if (!is_object($mod_obj)) {
                throw new RuntimeException('Job requires '.$this->module.' module but it could not be loaded');
            }
            $mod_obj->DoAction($matches[1], '', []);
        } elseif (is_callable($this->function)) {
            if ($this->module) {
                $mod_obj = Utils::get_module($this->module);
                //TODO exceptions useless in async context
                if (!is_object($mod_obj)) {
                    throw new RuntimeException('Job requires '.$this->module.' module but it could not be loaded');
                }
                // call the function, pass in $this
                $fn = $this->function->bindTo($mod_obj);
                call_user_func($fn);
            } else {
                call_user_func($this->function);
            }
        }
        return 2; // TODO
    }
}
