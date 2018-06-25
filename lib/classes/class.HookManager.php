<?php
#hook-related classes: HookHandler, HookManager
#Copyright (C) 2016-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

/**
 * Contains classes and utilities for working with CMSMS hooks.
 * @package CMS
 * @license GPL
 * @since 2.2
 */

/**
 * @ignore
 */
namespace CMSMS\Hooks {

	use CMSMS\HookManager;

    /**
     * An internal class to represent a hook handler.
     *
     * @internal
     * @ignore
     */
    class HookHandler
    {
        /**
         * @ignore
         */
        public $callable;

        /**
         * @ignore
         */
        public $priority;

        /**
         * @ignore
         */
        public function __construct($callable,$priority)
        {
            if (is_callable($callable, true)) {
                $this->callable = $callable;
                $this->priority = max(HookManager::PRIORITY_HIGH,min(HookManager::PRIORITY_LOW,(int)$priority));
            } else {
                throw new \InvalidArgumentException('Invalid callable passed to '. __CLASS__.'::'.__METHOD__);
            }
        }
    }

    /**
     * An internal class to represent a hook.
     *
     * @internal
     * @ignore
     */
    class HookDefn
    {
        /**
         * @ignore
         */
        public $name;
        /**
         * @ignore
         */
        public $handlers = [];
        /**
         * @ignore
         */
        public $sorted;

        /**
         * @ignore
         */
        public function __construct($name)
        {
            $this->name = $name;
        }
    }
} // namespace

namespace CMSMS {

	use CMSMS\Hooks\HookDefn;
	use CMSMS\Hooks\HookHandler;

    /**
     * A class to manage hooks, and to call hook handlers.
     *
     * This class is capable of managing a flexible list of hooks, registering handlers for those hooks, and calling the handlers
     * and/or related events.
     *
     * @package CMS
     * @license GPL
     * @since 2.2
     * @author Robert Campbell <calguy1000@gmail.com>
     */
    class HookManager
    {
        /**
         * High priority handler
         */
        const PRIORITY_HIGH = 1;

        /**
         * Indicates a normal priority handler
         */
        const PRIORITY_NORMAL = 2;

        /**
         * Indicates a low priority handler
         */
        const PRIORITY_LOW = 3;

        /**
         * @ignore
         */
        private static $_hooks;

        /**
         * @ignore
         */
        private static $_in_process = [];

        /**
         * @ignore
         */
        private function __construct() {}

        /**
         * @ignore
         */
        private static function calc_hash($in)
        {
            if( is_object($in) ) {
                return spl_object_hash($in);
            } elseif( is_callable($in, true) ) {
                return spl_object_hash((object)$in);
            }
        }

        /**
         * Add a handler to a hook
         *
         * @param string $name The hook name.  If the hook does not already exist, it is added.
         * @param callable $callable A callable function, or a string representing a callable function.  Closures are also supported.
         * @param int $priority The priority of the handler.
         */
        public static function add_hook($name,$callable,$priority = self::PRIORITY_NORMAL)
        {
            $name = trim($name);
            $hash = self::calc_hash($callable);
            try {
                self::$_hooks[$name]->handlers[$hash] = new HookHandler($callable,$priority);
            } catch (\InvalidArgumentException $e) {
                //TODO warn the user about failure
                return;
            }
            if( !isset(self::$_hooks[$name]) ) self::$_hooks[$name] = new HookDefn($name);
            self::$_hooks[$name]->sorted = false;
        }

        /**
         * Remove a handler from a hook
         * @since 2.3
         * @param string $name The hook name.
         * @param callable $callable A callable function, or a string representing a callable function.  Closures are also supported.
         */
        public static function remove_hook($name,$callable)
        {
            $name = trim($name);
            $hash = self::calc_hash($callable);
            unset(self::$_hooks[$name]->handlers[$hash]);
        }

        /**
         * Enable or disable a handler
         * @since 2.3
         * @param string $name The hook name.
         * @param callable $callable A callable function, or a string representing a callable function.  Closures are also supported.
         * @param bool $state Optiopnal flag whether the handler is to be disabled, default true
         */
        public static function block_hook($name, $callable, $state=true)
        {
            $name = trim($name);
            $hash = self::calc_hash($callable);
            if( !isset(self::$_hooks[$name]->handlers[$hash]) ) {
              //TODO
            }
        }

        /**
         * Test if we are currently handling a hook or not.
         *
         * @param null|string $name The hook name to test for.  If null is provided, the system will return true if any hook is being processed.
         * @return bool
         */
        public static function in_hook($name = null)
        {
            if( !$name ) return (count(self::$_in_process) > 0);
            return in_array($name,self::$_in_process);
        }

        /**
         * Run a hook, progressively altering the value of the input i.e. a filter.
         *
         * This method accepts variable arguments.  The first argument (required)
         * is the name of the hook to execute. Further arguments will be passed
         * to registered handlers.
         *
         * If an event with the specified name exists, it will be 'sent' first.
         *
         * The event/hook handlers must each return the same number and type of arguments
         * as were provided to it, so that they can be passed to the next handler.
         *
		 * @param  array $args 1 or more members, 1st is hook-name
         * @return mixed The output of this method depends on the hook. Null if nothing to do.
         */
        public static function do_hook(...$args)
        {
            $name = trim(array_shift($args));

            $module = $eventname = null;
            if( strpos($name,':') !== false ) list($module,$eventname) = explode('::',$name);
            $is_event = $module && $eventname;

            if( !$is_event && ( !isset(self::$_hooks[$name]) || !count(self::$_hooks[$name]->handlers) )  ) return; // nothing to do.

            // note: if present, $args is an array
            $value = $args;
            self::$_in_process[] = $name;
            if( $is_event && is_array($value) && count($value) == 1 && isset($value[0]) && is_array($value[0]) ) {
                // attempt to call an event with this data.
                $data = $value[0];
                \Events::SendEvent($module,$eventname,$data);
                $value[0] = $data; // transitive.
            }
            if( isset(self::$_hooks[$name]->handlers) && count(self::$_hooks[$name]->handlers) ) {
                // sort the handlers.
                if( !self::$_hooks[$name]->sorted ) {
                    if( count(self::$_hooks[$name]->handlers) > 1 ) {
                        usort(self::$_hooks[$name]->handlers, function($a,$b)
						{
                           return $a->priority <=> $b->priority;
                        });
                    }
                    self::$_hooks[$name]->sorted = TRUE;
                }

                foreach( self::$_hooks[$name]->handlers as $obj ) {
                    //TODO if not blocked
                    // input is passed to each handler in turn
                    // NOTE each handler must return the same number and type
                    // of parameters as were provided, but their values may be different
                    $cb = $obj->callable;
                    if( empty($value) || !is_array($value) ) {
                        $value = $cb($value);
                    } else {
                        $value = $cb(...$value);
                    }
                }
            }
            $out = (is_array($value) && count($value) == 1) ? $value[0] : $value;
            array_pop(self::$_in_process);
            return $out;
        }

        /**
         * Run a hook, returning the first non-empty value.
         * This method does not call event handlers with similar name.
         *
         * This method accepts variable arguments.  The first argument (required)
         * is the name of the hook to execute. Further arguments will be passed
         * to the various handlers.
         *
         * This method passes the same input parameter(s) to each hook handler.
         *
		 * @param  array $args 1 or more members, 1st is hook-name
         * @return mixed The output of this method depends on the hook.
         */
        public static function do_hook_first_result(...$args)
        {
            $name = trim(array_shift($args));

            if( !isset(self::$_hooks[$name]) || !count(self::$_hooks[$name]->handlers)  ) return; // nothing to do.

            // note if present, $args is an array
            $value = $args;
            self::$_in_process[] = $name;

            if( isset(self::$_hooks[$name]->handlers) && count(self::$_hooks[$name]->handlers) ) {
                // sort the handlers.
                if( !self::$_hooks[$name]->sorted ) {
                    if( count(self::$_hooks[$name]->handlers) > 1 ) {
                        usort(self::$_hooks[$name]->handlers,function($a,$b){
                                if( $a->priority < $b->priority ) return -1;
                                if( $a->priority > $b->priority ) return 1;
                                return 0;
                            });
                    }
                    self::$_hooks[$name]->sorted = TRUE;
                }

                foreach( self::$_hooks[$name]->handlers as $obj ) {
                    //TODO if not blocked
                    $cb = $obj->callable;
                    if( empty($value) || !is_array($value) ) {
                        $out = $cb($value);
                    } else {
                        $out = $cb(...$value);
                    }
                    if( !empty( $out ) ) break;
                }
            }
            $out = (is_array($out) && count($out) == 1) ? $out[0] : $out;
            array_pop(self::$_in_process);
            return $out;
        }

        /*
         * Send an event, or run a hook
         *
         * This method accepts variable arguments.  The first argument (required)
         * is the name of the hook to execute. Further arguments will be passed
         * to each hook handler or event handler.
         *
         * If an event with the specified name exists, it will be 'sent'. Otherwise,
         * a hook with that name (if any) will be processed.
		 *
		 * @param  array $args 1 or more members, 1st is hook-name
         * @since 2.3
         */
        public static function do_hook_all(...$args)
        {
            $name = trim(array_shift($args));

            $module = $eventname = null;
            if( strpos($name,':') !== false ) list($module,$eventname) = explode('::',$name);
            $is_event = $module && $eventname;

            if( !$is_event && ( !isset(self::$_hooks[$name]) || !count(self::$_hooks[$name]->handlers) )  ) return; // nothing to do.

            // note: $args is an array
            $value = $args;
            self::$_in_process[] = $name;
            if( $is_event && is_array($value) && count($value) == 1 && isset($value[0]) && is_array($value[0]) ) {
                // attempt to call an event with this data.
                \Events::SendEvent($module,$eventname,$value[0]);
            }
            elseif( isset(self::$_hooks[$name]->handlers) && count(self::$_hooks[$name]->handlers) ) {
                foreach( self::$_hooks[$name]->handlers as $obj ) {
                    //TODO if not blocked
                    $cb = $obj->callable;
                    if( empty($value) || !is_array($value) ) {
                        $cb($value);
                    } else {
                        $cb(...$value);
                    }
                }
            }
            array_pop(self::$_in_process);
        }

/*      private function is_assoc($in)
        {
             $keys = array_keys($in);
             $c = count($keys);
             $n = 0;
             for( $n = 0; $n < $c; $n++ ) {
                  if( $keys[$n] != $n ) return FALSE;
             }
             return TRUE;
        }
*/
        /**
         * Run a hook, accumulating the results of each handler into an array.
         *
         * This method accepts variable arguments.
         * The first argument (required) is the name of the hook to execute. If an
         * event with the same name exists, it will be called first.

         * Further arguments will be passed to the various handlers.
         *
         * Each handler's return probably should have the same number and type
         * of parameters, for sane accumulation in the results array.
         *
		 * @param  array $args 1 or more members, 1st is hook-name
         * @return array Mixed data, as it cannot be ascertained what data is passed back from event handlers.
         */
        public static function do_hook_accumulate(...$args)
        {
            $name = trim(array_shift($args));

//          if( is_array($args) && count($args) == 1 && is_array($args[0]) && !$this->is_assoc($args[0]) ) $args = $args[0];
            $is_event = false;
            $module = $eventname = null;
            if( strpos($name,':') !== FALSE ) list($module,$eventname) = explode('::',$name);
            if( $module && $eventname ) $is_event = true;

            if( !$is_event && ( !isset(self::$_hooks[$name]) || !count(self::$_hooks[$name]->handlers) )  ) return; // nothing to do.

            // sort the handlers.
            if( !self::$_hooks[$name]->sorted ) {
                if( count(self::$_hooks[$name]->handlers) > 1 ) {
                    usort(self::$_hooks[$name]->handlers, function($a,$b)
					{
                       return $a->priority <=> $b->priority;
                    });
                }
                self::$_hooks[$name]->sorted = TRUE;
            }

            $out = [];
            $value = $args;
            self::$_in_process[] = $name;
            if( $is_event && is_array($value) && count($value) == 1 && isset($value[0]) && is_array($value[0]) ) {
                $data = $value[0];
                \Events::SendEvent($module,$eventname,$data);
                $value[0] = $data; // this may not be the same as input data.
            }
            foreach( self::$_hooks[$name]->handlers as $obj ) {
                $cb = $obj->callable;
                if( empty($value) || !is_array($value) ) {
                    $ret = $cb($value);
                } else {
                    $ret = $cb(...$value);
                }
	            $out[] = (is_array($ret) && count($ret) == 1) ? $ret[0] : $ret;
            }
            array_pop(self::$_in_process);
            return $out;
        }
    } // class

} // namespace CMSMS
