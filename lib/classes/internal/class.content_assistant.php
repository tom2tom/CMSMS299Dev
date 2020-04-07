<?php
#Module: content_assistant
#Copyright (C) 2010-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace CMSMS\internal;

use cms_siteprefs;
use CMSMS\RouteOperations;
use function endswith;
use function munge_string_to_url;
use function startswith;

/**
 * @package CMS
 */

/**
 * A simple class for assisting with content management
 *
 * @package CMS
 * @internal
 * @author Robert Campbell (calguy1000@cmsmadesimple.org)
 *
 * @since 1.9
 */
class content_assistant
{
  /**
   * A utility function to test if we are allowed to auto create url paths
   *
   * @return bool
   */
  public static function auto_create_url()
  {
    return cms_siteprefs::get('content_autocreate_urls',0);
  }


  /**
   * A utility function to test if the supplied url is valid for the supplied content id
   *
   * @param string The partial url path to test
   * @return bool
   */
  public static function is_valid_url($url,$content_id = '')
  {
    // check for starting or ending slashes
    if( startswith($url,'/') || endswith($url,'/') ) return FALSE;

    // first check for invalid chars.
	if( munge_string_to_url($url,TRUE,TRUE) != $url ) return FALSE;

     // now check for duplicates.
    RouteOperations::load_routes();
    $route = RouteOperations::find_match($url,TRUE);
    if( !$route ) return TRUE;
    if( $route->is_content() ) {
		if($content_id == '' || ($route->get_content() == $content_id)) return TRUE;
	}
    return FALSE;
  }
} // class
