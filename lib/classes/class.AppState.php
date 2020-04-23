<?php
/*
Singleton class for accessing system state
Copyright (C) 2019-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

namespace CMSMS;

use CmsInvalidDataException;
use RuntimeException;

/**
 * Singleton class that contains various functions and properties representing
 * the state of the application.
 *
 * @final
 * @package CMS
 * @license GPL
 * @since 2.3
 */
final class AppState
{
    /**
     * A bitflag constant indicating that the request is for a frontend page
     */
    const STATE_FRONT_PAGE = 1;

    /**
     * A bitflag constant indicating that the request is for a page in the CMSMS admin console
     */
    const STATE_ADMIN_PAGE = 2;

    /**
     * A bitflag constant indicating that the request is for an admin login
     */
    const STATE_LOGIN_PAGE = 4;

    /**
     * A bitflag constant indicating that the request is taking place during the installation process
     */
    const STATE_INSTALL = 0x80;

    /**
     * A bitflag constant indicating that a stylesheet is being processed during the request
     */
    const STATE_STYLESHEET = 0x100;

    /**
     * A bitflag constant indicating that we are currently parsing page templates
     * UNUSED
     */
    const STATE_PARSE_TEMPLATE = 0x200;

    /**
     * @ignore
     */
    private const STATELIST = [
        self::STATE_ADMIN_PAGE,
        self::STATE_FRONT_PAGE,
        self::STATE_STYLESHEET,
        self::STATE_INSTALL,
        self::STATE_PARSE_TEMPLATE,
        self::STATE_LOGIN_PAGE
    ];

    /**
     * Interpretations of pre-2.9 identifiers of various states
     * @ignore
     * @deprecated since 2.9
     */
    private const STATESTRINGS = [
        'admin_request' => self::STATE_ADMIN_PAGE,
        'install_request' => self::STATE_INSTALL,
        'login_request' => self::STATE_LOGIN_PAGE,
        'parse_page_template' => self::STATE_PARSE_TEMPLATE,
        'stylesheet_request' => self::STATE_STYLESHEET,
    ];

    // static properties here >> StaticProperties class ?
    /**
     * Array of current states.
     * @ignore
     */
    private static $_states = [];

    /**
     * @ignore
     */
    private function __construct() {}

    /**
     * @ignore
     */
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
        if( isset($CMS_ADMIN_PAGE) ) $tmp[self::STATE_ADMIN_PAGE] = self::STATE_ADMIN_PAGE;
        if( isset($CMS_LOGIN_PAGE) ) $tmp[self::STATE_LOGIN_PAGE] = self::STATE_LOGIN_PAGE; // files also set STATE_ADMIN_PAGE
        if( isset($CMS_INSTALL_PAGE) ) $tmp[self::STATE_INSTALL] = self::STATE_INSTALL;
//        if( !$tmp ) $tmp[self::STATE_FRONT_PAGE] = self::STATE_FRONT_PAGE;

        if( isset($CMS_STYLESHEET) ) $tmp[self::STATE_STYLESHEET] = self::STATE_STYLESHEET; // the cms_stylesheet plugin is running
//      if (?) $tmp[self::STATE_PARSE_TEMPLATE] = self::STATE_PARSE_TEMPLATE;
        self::$_states += $tmp;
    }

    /**
     * [Un]set a global variable reflecting $flag and $value.
     * Effectively the inverse of _capture_states()
     * @deprecated since 2.9
     * @ignore
     */
    private static function _set_state_var(int $flag, bool $value = true)
    {
        global $CMS_ADMIN_PAGE, $CMS_INSTALL_PAGE, $CMS_LOGIN_PAGE, $CMS_STYLESHEET;

        switch( $flag ) {
            case self::STATE_ADMIN_PAGE:
                $name = 'CMS_ADMIN_PAGE';
                break;
            case self::STATE_STYLESHEET:
                $name = 'CMS_STYLESHEET';
                break;
            case self::STATE_INSTALL:
                $name = 'CMS_INSTALL_PAGE';
                break;
            case self::STATE_LOGIN_PAGE:
                $name = 'CMS_LOGIN_PAGE';
                break;
//          case self::STATE_PARSE_TEMPLATE: $name = ??; break;
            case self::STATE_FRONT_PAGE:
                unset($CMS_ADMIN_PAGE, $CMS_INSTALL_PAGE, $CMS_LOGIN_PAGE, $CMS_STYLESHEET);
            // no break here
            default:
                return;
        }

        if( $value ) {
            $$name = 1;
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
     * @throws CmsInvalidDataException
     * @throws RuntimeException
     */
    private static function _validate_state_var(int $flag, $value) : bool
    {
        if( !in_array($flag, self::STATELIST) ) {
            throw new CmsInvalidDataException($flag.' is not a recognised CMSMS state');
        }
        if( $flag == self::STATE_INSTALL && $value !== null ) {
            if (!property_exists('cms_installer\\installer_base', 'signature')) { // TODO not hardcoded names
                throw new RuntimeException('Invalid state-change');
            }
        }
        return TRUE;
    }

    /**
     * Set the list of current states.
     * Any invalid state is ignored, if if does not throw an exception.
     *
     * @param int $states State bit-flag(s), OR'd class constant(s).
     */
    public static function set_states(int $states)
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
     * Get a list of all current states.
     *
     * @return array  State constants (int's)
     */
    public static function get_states() : array
    {
        self::_capture_states();
        return array_keys(self::$_states);
    }

    /**
     * Report whether the specified state matches the current application state.
     * Returns false or throws an exception if the state is invalid.
     *
     * @param mixed $state int | deprecated string State identifier, a class constant
     * @return bool
     */
    public static function test_state($state) : bool
    {
        if( is_string($state) ) {
            $state = self::STATESTRINGS[$state] ?? (int)$state; //deprecated since 2.3
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
     * @param int $states State bit-flag(s), OR'd class constant(s)
     * @return bool
     */
    public static function test_any_state(int $states) : bool
    {
        self::_capture_states();
        $tmp = array_sum(self::$_states);
        return ($tmp & $states) > 0;
    }

    /**
     * Report whether all the specified state(s) are current.
     *
     * @param int $states State bit-flag(s), OR'd class constant(s)
     * @return bool
     */
    public static function test_all_states(int $states) : bool
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
    public static function add_state($state)
    {
        if( is_string($state) ) {
            $state = self::STATESTRINGS[$state] ?? (int)$state; //deprecated since 2.9
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
    public static function remove_state($state) : bool
    {
        if( is_string($state) ) {
            $state = self::STATESTRINGS[$state] ?? (int)$state; //deprecated since 2.9
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
}
