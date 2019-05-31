<?php
#hook-related classes: HookHandler, HookManager
#Copyright (C) 2016-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
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
                throw new InvalidArgumentException('Invalid callable passed to '. __CLASS__.'::'.__METHOD__);
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
     * This class is capable of managing a flexible list of hooks,
     * [un]registering handlers for those hooks, and calling the handlers
     *
     * @package CMS
     * @license GPL
     * @since 2.2
     * @author Robert Campbell <calguy1000@cmsmadesimple.org>
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
         * Sort hook handlers by priority, if not already done
         * @ignore
         * @param string $name The hook name.
         */
        protected static function sort_handlers($name)
        {
            if( !self::$_hooks[$name]->sorted ) {
                if( count(self::$_hooks[$name]->handlers) > 1 ) {
                    usort(self::$_hooks[$name]->handlers, function($a,$b)
                    {
                       return $a->priority <=> $b->priority;
                    });
                }
                self::$_hooks[$name]->sorted = true;
            }
        }

        /* *
         * Check whether $in is not a 'pure' non-associative array
         * @ignore
         */
/*
        private static function is_assoc($in)
        {
            if( !is_array($in) ) return false;
            return array_keys($in) !== range(0, count($in) - 1);
OR
            $keys = array_keys($in);
            $c = count($keys);
            $n = 0;
            for( $n = 0; $n < $c; $n++ ) {
                if( $keys[$n] != $n ) return false;
            }
            return true;
        }
*/
        /**
         * Add a handler to a hook
         *
         * @param string $name The hook name.  If the hook does not already exist, it is added.
         * @param callable $callable A PHP callable: function name | array | closure
         * @param int $priority The priority of the handler.
         * @return bool indicating success since 2.3
         */
        public static function add_hook($name,$callable,$priority = self::PRIORITY_NORMAL)
        {
            if( !is_callable($callable) ) return false; //TODO warn the user about failure
            $name = trim($name);
            $hash = self::calc_hash($callable);
            try {
                self::$_hooks[$name]->handlers[$hash] = new HookHandler($callable,$priority);
            } catch (InvalidArgumentException $e) {
                return false; //TODO warn the user about failure
            }
            if( !isset(self::$_hooks[$name]) ) self::$_hooks[$name] = new HookDefn($name);
            self::$_hooks[$name]->sorted = false;
            return true;
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

        /* *
         * Enable or disable a handler
         * @since 2.3
         * @param string $name The hook name.
         * @param callable $callable A callable function, or a string representing a callable function.  Closures are also supported.
         * @param bool $state Optiopnal flag whether the handler is to be disabled, default true
         */
/*        public static function block_hook($name, $callable, $state=true)
        {
            $name = trim($name);
            $hash = self::calc_hash($callable);
            if( !isset(self::$_hooks[$name]->handlers[$hash]) ) {
              //TODO
            }
        }
*/
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
         * Run a hook, progressively altering the value passed to handlers i.e. a filter.
         *
 		 * This method is akin to Events::SendEvent(), but with that method's
		 * originator and name parameters merged like 'originator::name', and
		 * the ability to prioritize the handlers.
		 *
         * @param args This method accepts variable arguments.
         * The first of them (required) is the name of the hook to execute.
         * Any further argument(s) will be passed to the first-sorted registered
         * handler, and, as progressively modified, to other such handlers.
         *
         * The handlers must each return either null (signalling ignore the result),
         * or else variable(s) that can be passed verbatim (keys ignored) to
         * the next handler. In most cases. the same number, order and types
         * of parameter(s) as were provided as argument(s) to the handler.
         * Returned parameter(s)' values may be different, of course.
		 *
         * @return mixed Depends on the hook handlers. Null if nothing to do.
         */
        public static function do_hook(...$args)
        {
            $name = trim(array_shift($args));

            if( $name === '' || !isset(self::$_hooks[$name]) || !count(self::$_hooks[$name]->handlers) ) return; // nothing to do.

            // note: $args is an array, maybe empty
            $value = $args;
            self::$_in_process[] = $name;

            self::sort_handlers($name);

            foreach( self::$_hooks[$name]->handlers as $obj ) {
                //TODO if blocking is supported, is not blocked
                $cb = $obj->callable;
                if( is_array($value) ) {
                    $out = $cb(...array_values($value));
                } else {
                    $out = $cb($value);
                }
                if( !is_null($out) ) {
                    $value = $out;
                }
            }

            $out = (is_array($value) && count($value) == 1 && key($value) == 0) ? $value[0] : $value;
            array_pop(self::$_in_process);
            return $out;
        }

       /**
        * Run a hook.
	    *
	    * This is a variant of do_hook(), with the same parameter(s) supplied to
	    * all handlers.
        *
        * @param args This method accepts variable arguments.
        * The first of them (required) is the name of the hook to execute.
        * Any further argument(s) will be passed to all registered handler(s).
        *
        * @since 2.3
        */
        public static function do_hook_simple(...$args)
        {
            $name = trim(array_shift($args));

            if( $name === '' || !isset(self::$_hooks[$name]) || !count(self::$_hooks[$name]->handlers) ) return; // nothing to do.

            // note: $args is an array, or empty
            self::$_in_process[] = $name;

            foreach( self::$_hooks[$name]->handlers as $obj ) {
                //TODO if blocking is supported, is not blocked
                $cb = $obj->callable;
                if( $args ) {
                    $cb(...$args);
                } else {
                    $cb();
                }
            }
            array_pop(self::$_in_process);
        }

        /**
         * Run a hook, to retrieve a single result.
		 *
		 * This is a variant of do_hook_accumulate(), which returns the value
		 * from the first handler which itself returns a non-empty value.
		 * Tip: it may be convenient to register a PRIORITY_LOW handler to
		 * return a default value.
         *
         * @param args This method accepts variable arguments.
         * The first of them (required) is the name of the hook to execute.
         * Any further argument(s) will be passed to the sorted registered
         * handlers in turn, until one such returns a non-empty value.
         *
         * @return mixed Depends on the hook handlers.
         */
        public static function do_hook_first_result(...$args)
        {
            $name = trim(array_shift($args));

            if( $name === '' || !isset(self::$_hooks[$name]) || !count(self::$_hooks[$name]->handlers)  ) return; // nothing to do.

            // note if present, $args is an array or empty
            self::$_in_process[] = $name;

            self::sort_handlers($name);

            foreach( self::$_hooks[$name]->handlers as $obj ) {
                //TODO if blocking is supported, is not blocked
                $cb = $obj->callable;
                if( $args ) {
                    $out = $cb(...$args);
                } else {
                    $out = $cb();
                }
                if( !empty( $out ) ) break;
            }

            if( is_array($out) && count($out) == 1 && key($out) == 0) $out = $out[0];
            array_pop(self::$_in_process);
            return $out;
        }

        /**
         * Run a hook, to retrieve the results from all handlers.
         *
         * @param args  This method accepts variable arguments.
         * The first of them (required) is the name of the hook to execute.
         * Any further argument(s) will be passed to the sorted registered handlers
         * in turn. Each handler's non-null return is 'pushed' into an array,
         * which is eventually returned to the caller.
         *
         * @return mixed null or array, each member of which is a non-null value returned by a handler.
         */
        public static function do_hook_accumulate(...$args)
        {
            $name = trim(array_shift($args));

            if( $name === '' || !isset(self::$_hooks[$name]) || !count(self::$_hooks[$name]->handlers) ) return; // nothing to do.

            self::sort_handlers($name);

            $out = [];
            self::$_in_process[] = $name;

            foreach( self::$_hooks[$name]->handlers as $obj ) {
                //TODO if blocking is supported, is not blocked
                $cb = $obj->callable;
                if( $args ) {
                    $ret = $cb(...$args);
                } else {
                    $ret = $cb();
                }
                if( !is_null($ret) ) {
                    $out[] = (is_array($ret) && count($ret) == 1 && key($ret) == 0) ? $ret[0] : $ret;
                }
            }
            array_pop(self::$_in_process);
            return $out;
        }
    } // class

} // namespace CMSMS
