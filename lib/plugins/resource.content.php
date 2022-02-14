<?php
/*
Class: smarty resource handler for frontend pages
Copyright (C) 2012-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\Error404Exception;
use CMSMS\SingleItem;

/**
 * A simple class for handling the content smarty resource.
 *
 * @package CMS
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
     * @param int     &$mtime  store for retrieved template modification timestamp, if $source is set
     */
    protected function fetch($name, &$source, &$mtime)
    {
        $contentobj = SingleItem::App()->get_content_object();

        if ($contentobj) {
            $source = $contentobj->Show($name);  //TODO maybe disable SmartyBC-supported {php}{/php}
            if( $contentobj->Cachable() ) {
                $st = $contentobj->GetModifiedDate();
                if( !$st ) $st = time() - 86400;
                $mtime = $st;
            }
            else {
                $mtime = time();
            }
            return;
        }
        throw new Error404Exception('Page content-object not found');
    }
}
