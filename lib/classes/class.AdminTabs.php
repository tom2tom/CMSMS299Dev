<?php
/*
Class to aid creating a tabbed interface in the CMSMS admin console
Copyright (C) 2016-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS;

/**
 * A convenience class for creating a tabbed interface in the CMSMS admin console
 * May include some automation, which can be helpful, or a problem if the order
 * of method-calls is non-standard.
 * Typical in-template use (via corresponding plugins, with respective
 *  $autoflow argument true):
 * 1. set_tab_header() - n times, then
 * 2. start_tab() - n times each where appropriate, then
 * 3. end_tab_content() where appropriate
 * OR
 * Typical in-code use (for smarty assignment):
 * 1. optional start_tab_headers(), then
 * 2. set_tab_header() - n times, then
 * 3. optional end_tab_headers() + start_tab_content(), then
 * 4. start_tab() - n times
 * 5. optional end_tab() AFTER ANY start_tab() IF $autoflow is TRUE
 * 6. end_tab_content()
 *
 * @package CMS
 * @license GPL
 * @since 2.0
 * @author Robert Campbell
 */
final class AdminTabs
{
    // static properties here >> StaticProperties class ?
    /**
     * @ignore
     */
    private static $_current_tab;

    /**
     * @ignore
     */
    private static $_start_headers_sent = 0;

    /**
     * @ignore
     */
    private static $_end_headers_sent = 0;

    /**
     * @ignore
     */
    private static $_start_content_sent = 0;

    /**
     * @ignore
     * Whether start_tab() has been called, without a subsequent end_tab()
     */
    private static $_in_tab = 0;

    /**
     * @ignore
     * Whether end_tab() has been called (at least once)
     */
    private static $_ended_tab = 0;
    /**
     * @ignore
     */
    private static $_tab_idx = 0;

    /**
     * @ignore
     * Treat as singleton
     */
    private function __construct()
    {
    }

    private function __clone()
    {
    }

    /**
     * Revert class properties ready for a fresh sequence
     * @since 2.99
     *
     * @param string $tab The param key
     */
    public static function reset()
    {
        self::$_current_tab = null;
        self::$_start_headers_sent = 0;
        self::$_end_headers_sent = 0;
        self::$_start_content_sent = 0;
        self::$_in_tab = 0;
        self::$_ended_tab = 0;
        self::$_tab_idx = 0;
    }

    /**
     * Set the current active tab
     *
     * @param string $tab The param key
     */
    public static function set_current_tab($tab)
    {
        self::$_current_tab = $tab;
    }

    /**
     * Begin output of tab headers
     *
     * @param bool   $autoflow Since 2.99 Whether to process 'glue' element(s) to determine the returned value. Default true.
     * @return string
     */
    public static function start_tab_headers($autoflow = true)
    {
        if ($autoflow) {
            self::$_start_headers_sent = 1;
        }
        return "\n".'<div id="page_tabs">';
    }

    /**
     * Create a tab header
     *
     * @param string $tabid The tab key
     * @param string $title The title to display in the tab
     * @param bool   $active Whether the tab is active or not.  Default false.
     *  If the current active tag matches the $tabid then the tab will be marked as active.
     * @param bool   $autoflow Since 2.99 Whether to process 'glue' element(s) to determine the returned value. Default true.
     * @return string
     */
    public static function set_tab_header($tabid, $title, $active = false, $autoflow = true)
    {
        if (!$active) {
            if ((self::$_tab_idx == 0 && self::$_current_tab == '') || $tabid == self::$_current_tab) {
                $active = true;
            }
            self::$_tab_idx++;
        }

        if ($active) {
            $a = ' class="active"';
            self::$_current_tab = $tabid;
        } else {
            $a = '';
        }

        $tabid = strtolower(str_replace(' ', '_', $tabid));

        $out = '';
        if ($autoflow) {
            if (!self::$_start_headers_sent) {
                $out .= self::start_tab_headers();
            }
        }
        $out .= '<div id="'.$tabid.'"'.$a.'>'.$title.'</div>';
        return $out;
    }

    /**
     * Finish outputting tab headers
     *
     * @param bool   $autoflow Since 2.99 Whether to process 'glue' element(s) to determine the returned value. Default true.
     * @return string
     */
    public static function end_tab_headers($autoflow = true)
    {
        if ($autoflow) {
            self::$_end_headers_sent = 1;
        }
        return '</div> <!-- EndTabHeaders -->';
    }

    /**
     * Start the content portion of the tabbed layout
     *
     * @param bool   $autoflow Since 2.99 Whether to process 'glue' element(s) to determine the returned value. Default true.
     * @return string
     */
    public static function start_tab_content($autoflow = true)
    {
        $out = '';
        if ($autoflow) {
            if (!self::$_end_headers_sent) {
                $out .= self::end_tab_headers();
            }
            self::$_start_content_sent = 1;
        }
        $out .= "\n".'<div class="clearb"></div><div id="page_content">';
        return $out;
    }

    /**
     * Finish the content portion of the tabbed layout
     *
     * @return string
     */
    public static function end_tab_content($autoflow = true)
    {
        $out = '';
        if ($autoflow) {
            if (self::$_in_tab) {
                $out .= self::end_tab();
            } elseif (!self::$_ended_tab) {
                self::$_in_tab = 1;
                $out .= self::end_tab();
            }
        }
        $out .= "\n".'</div> <!-- EndTabContent -->';
        return $out;
    }

    /**
     * Start the content portion of a specific tab
     *
     * @param string $tabid The tab key
     * @param bool   $autoflow Since 2.99 Whether to process 'glue' element(s) to determine the returned value. Default true.
     * @return string
     */
    public static function start_tab($tabid, $autoflow = true)
    {
        $out = '';
        if ($autoflow) {
            if (!self::$_start_content_sent) {
                $out .= self::start_tab_content();
            }
            if (self::$_in_tab) {
                $out .= self::end_tab();
            }
            self::$_in_tab = 1;
        }
        $out .= "\n".'<div id="' . strtolower(str_replace(' ', '_', $tabid)) . '_c">';
        return $out;
    }

    /**
     * End the content portion of a single tab
     *
     * @param bool   $autoflow Since 2.99 Whether to process 'glue' element(s) to determine the returned value. Default true.
     * @return string
     */
    public static function end_tab($autoflow = true)
    {
        if ($autoflow) {
            self::$_in_tab = 0;
            self::$_ended_tab = 1;
        }
        return "\n".'</div> <!-- EndTab -->';
    }
} // class
