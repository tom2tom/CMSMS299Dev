<?php
#Class: smarty resource handler for content pages
#Copyright (C) 2012-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
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
use Smarty_Resource_Custom;
use const __CMS_PREVIEW_PAGE__;

/**
 * A simple class for handling the content smarty resource.
 *
 * @package CMS
 * @author Robert Campbell
 * @internal
 * @ignore
 * @copyright Copyright (c) 2012, Robert Campbell <calguy1000@cmsmadesimple.org>
 * @since 1.11
 */
class content_template_resource extends Smarty_Resource_Custom //fixed_smarty_custom_resource
{
	/**
     * @param string  $name    template name
     * @param string  &$source template source
     * @param int     &$mtime  template modification timestamp
	 */
    protected function fetch($name,&$source,&$mtime)
    {
        $contentobj = CmsApp::get_instance()->get_content_object();

        if (!is_object($contentobj)) {
            $mtime = time();
            // We've a custom error message...  return it here
            header('HTTP/1.0 404 Not Found');
            header('Status: 404 Not Found');
            $source = null;
            if ($name == 'content_en') $source = cms_siteprefs::get('custom404');
            $source = trim($source);
            return;
        }
        else if( isset($_SESSION['__cms_preview_']) && $contentobj->Id() == __CMS_PREVIEW_PAGE__ ) {
            $mtime = time();
            $contentobj =& $_SESSION['__cms_preview__'];
            $source = $contentobj->Show($name);
            $source = preg_replace("/\{\/?php\}/", '', $source);
            $source = trim($source);
            return;
        }
        else if (isset($contentobj) && $contentobj !== FALSE) {
            if( !$contentobj->Cachable() ) $mtime = time();
			else $mtime = $contentobj->GetModifiedDate();
            $source = $contentobj->Show($name);
            $source = preg_replace('/\{\/?php\}/', '', $source);
            $source = trim($source);
            return;
        }
        $source = '';
        $mtime = 0;
    }
} // class
