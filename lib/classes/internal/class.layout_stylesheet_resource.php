<?php
#Class for handling css code as a resource
#Copyright (C) 2012-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

namespace CMSMS\internal;

use CmsLayoutStylesheet;
use Smarty_Resource_Custom;
use function endswith;

/**
 * A class for handling css code as a resource.
 *
 * @package CMS
 * @author Robert Campbell
 * @internal
 * @ignore
 *
 * @since 1.12
 */
class layout_stylesheet_resource extends Smarty_Resource_Custom //fixed_smarty_custom_resource
{
    /**
     * @param string  $name    template name
     * @param string  &$source template source
     * @param int     &$mtime  template modification timestamp
     */
    protected function fetch($name,&$source,&$mtime)
    {
        // clean up the input
        $name = trim($name);
        if( !$name ) {
            $mtime = 0;
            $source = '';
            return;
        }

        // if called via function.cms_stylesheet, then this stylesheet should be loaded.
        $obj = CmsLayoutStylesheet::load($name);

        // by now everything should be in memory in the CmsLayoutStylesheet internal cache's
        // put it all together in the order specified.
        $text = '/* cmsms stylesheet: '.$obj->get_name().' modified: '.strftime('%x %X',$obj->get_modified()).' */'."\n";
        $text .= $obj->get_content();
        if( !endswith($text,"\n") ) $text .= "\n";

        $mtime = $obj->get_modified();
        $source = $text;
    }
} // class
