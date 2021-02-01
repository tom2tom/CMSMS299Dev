<?php
/*
Class: smarty resource handler for frontend pages
Copyright (C) 2012-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\AppParams;
use CMSMS\AppSingle;

/**
 * A simple class for handling the content smarty resource.
 *
 * @package CMS
 * @author Robert Campbell
 * @internal
 * @ignore
 *
 * @since 2.99
 * @since 1.11 as content_template_resource
 */
class Smarty_Resource_content extends Smarty_Resource_Custom
{
    /**
     * @param string  $name    template identifier
     * @param string  &$source store for retrieved template content, if any
     * @param int     &$mtime  store for retrieved template modification timestamp
     */
    protected function fetch($name, &$source, &$mtime)
    {
        $contentobj = AppSingle::App()->get_content_object();

        if ($contentobj) {
            if( !$contentobj->Cachable() ) { $mtime = time(); }
            else { $mtime = $contentobj->GetModifiedDate(); }
            $source = $contentobj->Show($name);  //TODO maybe disable SmartyBC-supported {php}{/php}
            return;
        }

        header('HTTP/1.0 404 Not Found');
        header('Status: 404 Not Found');

        $mtime = time();
        // TODO relevance of AppParams::get('enablecustom404') and AppParams::get('custom404template')
        $source = ($name == 'content_en') ? trim(AppParams::get('custom404')) : '';
    }
} // class
