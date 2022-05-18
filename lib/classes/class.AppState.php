<?php
/*
Singleton class for accessing system state
Copyright (C) 2019-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use RuntimeException;
use UnexpectedValueException;

/**
 * Singleton class that records properties representing the current
 * state/status of CMSMS, and methods for interacting with them.
 *
 * @final
 * @package CMS
 * @license GPL
 * @since 3.0
 */
final class AppState
{
    /**
     * A bitflag constant indicating that the request is for a frontend page
     */
    const FRONT_PAGE = 1;

    /**
     * A bitflag constant indicating that the request is for a page in the CMSMS admin console
     */
    const ADMIN_PAGE = 2;

    /**
     * A bitflag constant indicating that the request is for an admin login
     */
    const LOGIN_PAGE = 4;

    /**
     * A bitflag constant indicating that the request is for an async job
     */
    const ASYNC_JOB = 0x40;

    /**
     * A bitflag constant indicating that the request is taking place during the installation process
     */
    const INSTALL = 0x80;

    /**
     * A bitflag constant indicating that a stylesheet is being processed during the request
     */
    const STYLESHEET = 0x100;

    /**
     * A bitflag constant indicating that we are currently parsing page templates
     * UNUSED
     */
    const PARSE_TEMPLATE = 0x200;

    /**
     * A bitflag constant indicating that request-type should be ignored
     */
    const NO_PAGE = 0x400;

    /**
     * @ignore
     */
    private const STATELIST = [
        self::ADMIN_PAGE,
        self::ASYNC_JOB,
        self::FRONT_PAGE,
        self::STYLESHEET,
        self::INSTALL,
        self::PARSE_TEMPLATE,
        self::LOGIN_PAGE,
        self::NO_PAGE
    ];

    /**
     * Interpretations of pre-3.0 identifiers of various states
     * @ignore
     * @deprecated since 3.0
     */
    private const STATESTRINGS = [
        'admin_request' => self::ADMIN_PAGE,
        'any_request' => self::NO_PAGE,
        'async_request' => self::ASYNC_JOB,
        'install_request' => self::INSTALL,
        'login_request' => self::LOGIN_PAGE,
        'parse_page_template' => self::PARSE_TEMPLATE,
        'stylesheet_request' => self::STYLESHEET,
   ];

    // static properties here >> Lone property|ies ?
    /**
     * Array of current states. Each member like stateflag => stateflag.
     * @ignore
     */
    private static $_states = [];

    /**
     * @ignore
     */
    #[\ReturnTypeWillChange]
    private function __construct() {}

    /**
     * @ignore
     */
    #[\ReturnTypeWillChange]
    private function __clone() {}

    /**
     * Accumulate all known states from global variables.
     * @todo transition to self::set_states()
     * @ignore
     */
    private static function _capture_states()
    {
        global $CMS_ADMIN_PAGE, $CMS_INSTALL_PAGE, $CMS_LOGIN_PAGE, $CMS_STYLESHEET;

        $tmp = [];
        if( isset($CMS_ADMIN_PAGE) ) $tmp[self::ADMIN_PAGE] = self::ADMIN_PAGE;
        if( isset($CMS_LOGIN_PAGE) ) $tmp[self::LOGIN_PAGE] = self::LOGIN_PAGE; // files also set ADMIN_PAGE
        if( isset($CMS_INSTALL_PAGE) ) $tmp[self::INSTALL] = self::INSTALL;
//        if( !$tmp ) $tmp[self::FRONT_PAGE] = self::FRONT_PAGE;

        if( isset($CMS_STYLESHEET) ) $tmp[self::STYLESHEET] = self::STYLESHEET; // the cms_stylesheet plugin is running
//      if (?) $tmp[self::PARSE_TEMPLATE] = self::PARSE_TEMPLATE;
        self::$_states += $tmp;
    }

    /**
     * [Un]set a global variable reflecting $flag and $value.
     * Effectively the inverse of _capture_states()
     * @deprecated since 3.0
     * @ignore
     */
    private static function _set_state_var(int $flag, bool $value = true)
    {
        global $CMS_ADMIN_PAGE, $CMS_INSTALL_PAGE, $CMS_LOGIN_PAGE, $CMS_STYLESHEET;

        switch( $flag ) {
            case self::ADMIN_PAGE:
                $name = 'CMS_ADMIN_PAGE';
                break;
            case self::STYLESHEET:
                $name = 'CMS_STYLESHEET';
                break;
            case self::INSTALL:
                $name = 'CMS_INSTALL_PAGE';
                break;
            case self::LOGIN_PAGE:
                $name = 'CMS_LOGIN_PAGE';
                break;
//          case self::PARSE_TEMPLATE: $name = ??; break;
            case self::FRONT_PAGE:
                unset($CMS_ADMIN_PAGE, $CMS_INSTALL_PAGE, $CMS_LOGIN_PAGE, $CMS_STYLESHEET);
            // no break here
            default:
                return;
        }

        if( $value ) {
            $$name = $flag;
        }
        else {
            unset($$name);
        }
    }

    /**
     * Check whether [un]setting a state value is currently valid
     * @param int $flag enumerator of the state to be processed
     * @param mixed $value bool value to be set | null for either value (i.e. we're checking/testing)
     * @return bool
     * @throws UnexpectedValueException or RuntimeException
     */
    private static function _validate_state_var(int $flag, $value) : bool
    {
        if( !in_array($flag, self::STATELIST) ) {
            throw new UnexpectedValueException($flag.' is not a recognised CMSMS state');
        }
        if( $flag == self::INSTALL && $value !== null ) {
            if (!property_exists('cms_installer\installer_base', 'signature')) { // TODO not hardcoded names
                throw new RuntimeException('Invalid state-change');
            }
        }
        return TRUE;
    }

    /**
     * Set the list of current state(s).
     * Any invalid state is ignored, if it does not throw an exception.
     *
     * @param int $states State bit-flag(s), OR'd AppState constant(s).
     */
    public static function set(int $states)
    {
        $tmp = [];
        foreach( self::STATELIST as $flag ) {
            if( $states & $flag ) {
                if( self::_validate_state_var($flag, TRUE) ) {
                    $tmp[$flag] = $flag;
                    self::_set_state_var($flag); //compatibility
                }
            }
        }
        self::$_states = $tmp;
    }

    /**
     * Get a list or bitflag-combination of all current states.
     *
     * @param bool $flat optional flag whether to report a single int Default false.
     * @return mixed array State constants (int's) | int
     */
    public static function get(bool $flat = false)
    {
        self::_capture_states();
        return ($flat) ? array_sum(self::$_states) : array_keys(self::$_states);
    }

    /**
     * Report whether the specified state matches the current application state.
     * Returns false or throws an exception if the state is invalid.
     *
     * @param mixed $state int | deprecated string State identifier, a class constant
     * @return bool
     */
    public static function test($state) : bool
    {
        if( is_string($state) ) {
            $state = self::STATESTRINGS[$state] ?? (int)$state; //deprecated since 3.0
        }
        if( self::_validate_state_var($state, null) ) {
            self::_capture_states();
            return isset(self::$_states[$state]);
        }
        return FALSE;
    }

    /**
     * Report whether one or more of the specified state(s) is current.
     *
     * @param int $states State bit-flag(s), OR'd AppState constant(s)
     * @return bool
     */
    public static function test_any(int $states) : bool
    {
        self::_capture_states();
        $tmp = array_sum(self::$_states);
        return ($tmp & $states) > 0;
    }

    /**
     * Report whether all the specified state(s) are current.
     *
     * @param int $states State bit-flag(s), OR'd AppState constant(s)
     * @return bool
     */
    public static function test_all(int $states) : bool
    {
        self::_capture_states();
        $tmp = array_sum(self::$_states);
        return ($tmp & $states) == $states;
    }

    /**
     * Add a state to the list of current states.
     * Does nothing or throws an exception if the state-change is invalid.
     *
     * @param mixed $state int | deprecated string The state, a class constant
     */
    public static function add($state)
    {
        if( is_string($state) ) {
            $state = self::STATESTRINGS[$state] ?? (int)$state; //deprecated since 3.0
        }
        if( self::_validate_state_var($state, TRUE) ) {
            self::$_states[$state] = $state;
            self::_capture_states();
            self::_set_state_var($state); //compatibility
        }
    }

    /**
     * Remove a state from the list of current states.
     * Returns false or throws an exception if the state-change is invalid.
     *
     * @param mixed $state int | deprecated string The state, a class constant
     * @return bool indicating success
     */
    public static function remove($state) : bool
    {
        if( is_string($state) ) {
            $state = self::STATESTRINGS[$state] ?? (int)$state; //deprecated since 3.0
        }
        if( self::_validate_state_var($state, FALSE) ) {
            self::_capture_states();
            if( isset(self::$_states[$state]) ) {
                unset(self::$_states[$state]);
                self::_set_state_var($state, 0); //compatibility
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
     * Replace the whole list of current states by the specified one(s).
     *
     * @param mixed $state int | deprecated string Replacement state(s),
     *  class constant(s)
     * @return int prior state(s) as OR'd AppState constant(s)
     * @throws UnexpectedValueException if the state-change is invalid.
     */
    public static function exchange($state) : int
    {
        if( is_string($state) ) {
            $state = self::STATESTRINGS[$state] ?? (int)$state; //deprecated since 3.0
        }
        if (0) { // TODO validate all flag(s) in $state
            throw new UnexpectedValueException($state.' includes un-recognised CMSMS state(s)');
        }
        $current = array_sum(self::$_states);
        list($current, $state) = [$state, $current]; // atomic swap
        self::set_states($state);
        return $current;
    }
}
