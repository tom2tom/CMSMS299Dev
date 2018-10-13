<?php
#Class to aid creating a tabbed interface in the CMSMS admin console
#Copyright (C) 2016-2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#BUT WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace CMSMS;

/**
 * A convenience class for creating a tabbed interface in the CMSMS admin console
 * Includes some automation, which can be a problem if the order of method-calls
 * is non-standard.
 * Typical in-template use (via corresponding plugins):
 * 1. set_tab_header() - n times, then
 * 2. start_tab() - n times each where appropriate, then
 * 3. end_tab_content() where appropriate
 * OR
 * Typical in-code use (for smarty assignment):
 * 1. optional start_tab_headers(), then
 * 2. set_tab_header() - n times, then
 * 3. optional end_tab_headers() + start_tab_content(), then
 * 4. optional end_tab() BEFORE ANY start_tab()
 * 5. start_tab() - n times
 * 6. end_tab_content()
 *
 * @package CMS
 * @license GPL
 * @since 2.0
 * @author Robert Campbell
 */
final class AdminTabs
{

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
     */
    private static $_in_tab = 0;

    /**
     * @ignore
     */
    private static $_tab_idx = 0;

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
     * @return string
     */
    public static function start_tab_headers()
    {
        self::$_start_headers_sent = 1;
        return '<div id="page_tabs">';
    }

    /**
     * Create a tab header
     *
     * @param string $tabid The tab key
     * @param string $title The title to display in the tab
     * @param bool   $active Whether the tab is active or not.  If the current active tag matches the $tabid then the tab will be marked as active.
     * @return string
     */
    public static function set_tab_header($tabid, $title, $active = false)
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
        if (!self::$_start_headers_sent) {
            $out .= self::start_tab_headers();
        }
        $out .= '<div id="'.$tabid.'"'.$a.'>'.$title.'</div>';
        return $out;
    }

    /**
     * Finish outputting tab headers
     *
     * @return string
     */
    public static function end_tab_headers()
    {
        self::$_end_headers_sent = 1;
        return '</div> <!-- EndTabHeaders -->';
    }

    /**
     * Start the content portion of the tabbed layout
     *
     * @return string
     */
    public static function start_tab_content()
    {
        $out = '';
        if (!self::$_end_headers_sent) {
            $out .= self::end_tab_headers();
        }
        $out .= '<div class="clearb"></div><div id="page_content">';
        self::$_start_content_sent = 1;
        return $out;
    }

    /**
     * Finish the content portion of the tabbed layout
     *
     * @return string
     */
    public static function end_tab_content()
    {
        $out = '';
        if (self::$_in_tab) {
            $out .= self::end_tab();
	        self::$_in_tab = 0;
        }
        $out .= '</div> <!-- EndTabContent -->';
        return $out;
    }

    /**
     * Start the content portion of a specific tab
     *
     * @param string $tabid The tab key
     * @param array  $params Unused, deprecated
     * @return string
     */
    public static function start_tab($tabid)
    {
        $out = '';
        if (!self::$_start_content_sent) {
            $out .= self::start_tab_content();
        }
        if (self::$_in_tab) {
            $out .= self::end_tab();
        }
        self::$_in_tab = 1;
        $out .= '<div id="' . strtolower(str_replace(' ', '_', $tabid)) . '_c">';
        return $out;
    }

    /**
     * End the content portion of a single tab
     *
     * @return string
     */
    public static function end_tab()
    {
        return '</div> <!-- EndTab -->';
    }
} // class
