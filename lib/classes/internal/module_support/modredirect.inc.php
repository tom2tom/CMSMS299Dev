<?php
#Redirection-related functions for modules
#Copyright (C) 2004-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.
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
 * Methods for modules to do redirection
 *
 * @since   1.0
 * @package CMS
 * @license GPL
 */

/**
 * @access private
 */
function cms_module_RedirectToAdmin(&$modinstance, $page, $params=[])
{
    $url = $page.get_secure_param();
    if ($params) {
        foreach ($params as $key=>$value) {
            if (is_scalar($value)) {
                $url .= '&'.$key.'='.rawurlencode($value);
            } else {
                $url .= '&'.cms_build_query($key, $value, '&');
            }
        }
    }
    redirect($url);
}

/**
 * @access private
 */
function cms_module_Redirect(&$modinstance, $id, $action, $returnid='', $params=[], $inline=false)
{
    $name = $modinstance->GetName();

    // Suggestion by Calguy to make sure 2 actions don't get sent
    if (isset($params['action'])) {
        unset($params['action']);
    }
    if (isset($params['id'])) {
        unset($params['id']);
    }
    if (isset($params['module'])) {
        unset($params['module']);
    }
    if (!$inline && $returnid != '') {
        $id = 'cntnt01';
    }

    $text = '';
    if ($returnid != '') {
        $contentops = ContentOperations::get_instance();
        $content = $contentops->LoadContentFromId($returnid); //both types of Content class support GetURL()
        if (!is_object($content)) {
            // no destination content object
            return;
        }
        $text .= $content->GetURL();

        $parts = parse_url($text);
        if (isset($parts['query']) && $parts['query'] != '?') {
            $text .= '&';
        } else {
            $text .= '?';
        }
    }
    else {
        $text .= 'moduleinterface.php?';
    }

    $text .= 'mact='.$name.','.$id.','.$action.','.($inline ? 1 : 0);
    if ($returnid != '') {
        $text .= '&'.$id.'returnid='.$returnid;
    }
    else {
        $text .= '&'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
    }

    foreach ($params as $key=>$value) {
        if ($key && $value !== '') {
            if (is_scalar($value)) {
                $text .= '&'.$id.$key.'='.rawurlencode($value);
            } else {
                $text .= '&'.cms_build_query($id.$key, $value, '&');
            }
        }
    }
    redirect($text);
}
