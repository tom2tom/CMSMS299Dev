<?php
/*
Class: content_assistant
Copyright (C) 2010-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.    See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS\internal;

use CMSMS\AppParams;
use CMSMS\RouteOperations;
use function munge_string_to_url;

/**
 * @package CMS
 */

/**
 * A simple class for assisting with content management
 *
 * @package CMS
 * @internal
 *
 * @since 1.9
 */
class content_assistant
{
    /**
     * Test whether we are allowed to auto-create url paths
     *
     * @return bool
     */
    public static function auto_create_url()
    {
        return (bool) AppParams::get('content_autocreate_urls',0);
    }


    /**
     * Test whether the supplied URL is valid for a pretty-url etc
     * representing the supplied content id.
     * Only called with a freshly-sanitized URL
     *
     * @param string $url The partial URL to test
     * @param mixed  $content_id Optional content identifier
     * @return bool
     */
    public static function is_valid_url($url,$content_id = '')
    {
        // check for start- or end-slash
        if( $url[0] == '/' || substr_compare($url,'/',-1,1) == 0 ) return false;

        // check for chars not suitable for a pretty-url/route
	    if( munge_string_to_url($url,true,true) != $url ) return false;

        // check for duplicates
        RouteOperations::load_routes();
        $route = RouteOperations::find_match($url,true);
        if( !$route ) return true;
        if( $route->is_content() ) {
            if( $content_id == '' || ($route->get_content() == $content_id) ) return true;
        }
        return false;
    }
} // class
