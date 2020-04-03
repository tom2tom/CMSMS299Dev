<?php
#page-content placement methods
#Copyright (C) 2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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
 * @var array
 * Accumulator for content to be included in the page header
 * TODO confirm request-specific
 */
$PAGE_HEAD_CONTENT = [];

/**
 * @var array
 * Accumulator for content to be included near page-end
 * (immediately before the </body> tag).
 * TODO confirm request-specific
 */
$PAGE_BOTTOM_CONTENT = [];

/**
 *
 * @param mixed $content string | string[] The content to add
 * @param array $holder content-array to be updated
 * @param bool  $after Optional flag whether to append (instead of prepend). Default true
 */
function add_page_content($content, &$holder, $after = true)
{
    if( is_array($content) ) {
        $clean = array_map('trim', $content);
        $more = array_diff($holder, $clean);
        if( $more ) {
            if( $after ) {
                $holder = array_merge($holder, $more);
            }
            else {
                $holder = array_merge($more, $holder);
            }
        }
    }
    else {
        $txt = trim($content);
        if( $txt && !in_array($txt, $holder) ) {
            if( $after ) {
                $holder[] = $txt;
            }
            else {
                array_unshift($holder, $txt);
            }
        }
    }
}

/**
 *
 * @param mixed $content string | string[] The content to add
 * @param array $holder content-array to be updated
 */
function remove_page_content($content, &$holder)
{
    if( is_array($content) ) {
        $clean = array_map('trim', $content);
        $holder = array_diff($holder, $clean);
    }
    else {
        $txt = trim($content);
        if( $txt && ($p = array_search($txt, $holder) !== false) ) {
            unset($holder[$p]);
        }
    }
}

/**
 * Add to the accumulated content to be inserted in the head section of the output page
 * @since 2.3
 *
 * @param mixed $content string | string[] The content to add
 * @param bool  $after Optional flag whether to append (instead of prepend). Default true
 */
function add_page_headtext($content, $after = true)
{
    global $PAGE_HEAD_CONTENT;
    add_page_content($content, $PAGE_HEAD_CONTENT, $after);
}

/**
 * Remove from the accumulated content to be inserted in the head section of the output page
 * @since 2.3
 *
 * @param mixed $content string | string[] The content to add
 */
function remove_page_headtext($content)
{
    global $PAGE_HEAD_CONTENT;
    remove_page_content($content, $PAGE_HEAD_CONTENT);
}

/**
 * Return the accumulated content to be inserted into the head section of the output page
 * @since 2.3
 *
 * @return string
 */
function get_page_headtext() : string
{
    global $PAGE_HEAD_CONTENT;
    if( $PAGE_HEAD_CONTENT ) return implode("\n", $PAGE_HEAD_CONTENT)."\n";
    return '';
}

/**
 * Add to the accumulated content to be inserted at the bottom of the output page
 * @since 2.3
 *
 * @param mixed $content string | string[] The content to add
 * @param bool  $after Optional flag whether to append (instead of prepend). Default true
 */
function add_page_foottext($content, $after = true)
{
    global $PAGE_BOTTOM_CONTENT;
    add_page_content($content, $PAGE_BOTTOM_CONTENT, $after);
}

/**
 * Remove from the accumulated content to be inserted at the bottom of the output page
 * @since 2.3
 *
 * @param mixed $content string | string[] The content to remove
 */
function remove_page_foottext($content)
{
    global $PAGE_BOTTOM_CONTENT;
    remove_page_content($content, $PAGE_BOTTOM_CONTENT);
}

/**
 * Return the accumulated content to be inserted at the bottom of the output page
 * @since 2.3
 *
 * @return string
 */
function get_page_foottext() : string
{
    global $PAGE_BOTTOM_CONTENT;
    if( $PAGE_BOTTOM_CONTENT ) return implode("\n", $PAGE_BOTTOM_CONTENT)."\n";
    return '';
}
