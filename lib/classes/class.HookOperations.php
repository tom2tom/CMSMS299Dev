<?php
/*
HookOperations class
Copyright (C) 2016-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS;

use CMSMS\internal\HookDefn;
use CMSMS\internal\HookHandler;
use InvalidArgumentException;

/*
TODO POLICY REVIEW
Should anything be entitled to interfere with a recorded hook-function ?
ATM not so.
Maybe so if hooks were to become non-static ?

TODO POLICY REVIEW
Should there be a hook-method essentially for sending notices ?
Same parameter(s) supplied to each (possibly-prioritized) handler, nothing returned
ATM not so.
*/

/**
 * A class to manage the members of and the running of hooks
 * (a.k.a. hooklists in other contexts).
 *
 * @package CMS
 * @license GPL
 * @since 2.99
 * @since 2.2 as HookManager
 */
class HookOperations
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

    // static properties here >> SingleItem property|ies ?
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
     * @return bool indicating success since 2.99
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
     * Test whether we are currently handling a hook.
     *
     * @param null|string $name The hook name to test for | null.
     *  If null, test for any hook at all.
     * @return bool
     */
    public static function in_hook($name = null)
    {
        if( !$name ) return (count(self::$_in_process) > 0);
        return in_array($name,self::$_in_process);
    }

    /*
     * This method is akin to Events::SendEvent(), but with that method's
     * originator and name parameters merged like 'originator::name', and
     * the ability to prioritize the handlers, and handler-results returned,
     * instead of supplied-parameter-references altered when relevant.
     */
    /**
     * Run a hook, perhaps progressively altering the argument(s) passed
     * to handlers.
     *
     * @param args This method accepts variable arguments.
     * The first of them (required) is the name of the hook to execute.
     * Any further argument(s) will be passed in turn to registered
     * handler(s) (as sorted). Any one or more of the handlers may
     * modify those parameters.
     *
     * The handlers must each return either null (signalling ignore the
     * result), or else variable(s) that can be passed verbatim as
     * arguments to the next handler. That is, the same number, order and
     * types of parameter(s) as were provided as argument(s) to the handler.
     * Returned parameter(s)' values may be different, of course.
     *
     * @return mixed Depends on the hook handlers. Null if nothing to do.
     */
    public static function do_hook(...$args)
    {
        $name = trim(array_shift($args));

        if( $name === '' || !isset(self::$_hooks[$name]) || !self::$_hooks[$name]->handlers ) return; // nothing to do

        // note: $args is an array, maybe empty, or maybe with array-members
        $value = $args;
        self::$_in_process[] = $name;

        self::sort_handlers($name);

        foreach( self::$_hooks[$name]->handlers as $obj ) {
            if( is_array($value) ) {
                $out = ($obj->callable)(...array_values($value));
            } else {
                $out = ($obj->callable)($value);
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
     * handlers in turn, until one of them returns a non-empty value.
     *
     * @return mixed Depends on the hook handlers.
     */
    public static function do_hook_first_result(...$args)
    {
        $name = trim(array_shift($args));

        if( $name === '' || !isset(self::$_hooks[$name]) || !self::$_hooks[$name]->handlers  ) return; // nothing to do

        // note if present, $args is an array or empty
        self::$_in_process[] = $name;

        self::sort_handlers($name);

        foreach( self::$_hooks[$name]->handlers as $obj ) {
            if( $args ) {
                $out = ($obj->callable)(...$args);
            } else {
                $out = ($obj->callable)();
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
     * Any further argument(s) will be passed to the sorted registered
     * handlers in turn. Each handler's non-null return is 'pushed' into
     * an array, which is ultimately returned to the caller.
     *
     * @return array non-null value(s) returned by handler(s) | empty
     */
    public static function do_hook_accumulate(...$args)
    {
        $name = trim(array_shift($args));

        if( $name === '' || !isset(self::$_hooks[$name]) || !self::$_hooks[$name]->handlers ) return []; // nothing to do

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
