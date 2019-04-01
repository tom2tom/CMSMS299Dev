<?php
#Class: smarty resource handler for content pages
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

use cms_siteprefs;
use CmsApp;
use CMSContentManager\ContentEditor;
use CmsException;
use Smarty_Resource_Custom;
use const CMS_PREVIEW_PAGEID;
use const CMS_PREVIEW;

/**
 * A simple class for handling the content smarty resource.
 *
 * @package CMS
 * @author Robert Campbell
 * @internal
 * @ignore
 *
 * @since 2.3
 * @since 1.11 as content_template_resource
 */
class content_resource extends Smarty_Resource_Custom //fixed_smarty_custom_resource
{
    /**
     * @param string  $name    template identifier
     * @param string  &$source store for retrieved template content, if any
     * @param int     &$mtime  store for retrieved template modification timestamp
     */
    protected function fetch($name,&$source,&$mtime)
    {
        $contentobj = CmsApp::get_instance()->get_content_object();

        if ($contentobj) {
            if( !$contentobj->Cachable() ) { $mtime = time(); }
            else { $mtime = $contentobj->GetModifiedDate(); }
            $out = $contentobj->Show($name);
            $out = trim(preg_replace('/\{\/?php\}/', '', $out));
            $source = $out;
            return;
        }

        // We've a custom error message...  return it here
        header('HTTP/1.0 404 Not Found');
        header('Status: 404 Not Found');

        $mtime = time();
        if ($name == 'content_en') { $source = trim(cms_siteprefs::get('custom404')); }
        else { $source = ''; }
    }
} // class
