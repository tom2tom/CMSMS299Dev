<?php
#Redirection-related functions for modules
#Copyright (C) 2004-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

namespace CMSMS\module_support;

use CMSMS\AppSingle;
use const CMS_ROOT_URL;
use const CMS_SECURE_PARAM_NAME;
use const CMS_USER_KEY;
use function cms_build_query;
use function get_secure_param;
use function redirect as doredirect;

/**
 * Methods for modules to do redirection.
 *
 * @internal
 * @since   1.0
 * @package CMS
 * @license GPL
 */
/**
 *
 * @param type $page PHP script to redirect to
 * @param array $params URL parameters
 */
function RedirectToAdmin($modinst, $page, array $params = [])
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
	doredirect($url);
};

/**
 *
 * @param string $id
 * @param type $action
 * @param mixed $returnid
 * @param array $params
 * @param bool $inline
 */
function Redirect($modinst, $id, $action, $returnid = '', array $params = [], bool $inline = false)
{
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

	if ($returnid != '') {
		$contentops = AppSingle::ContentOperations();
		$content = $contentops->LoadContentFromId($returnid); //both types of Content class support GetURL()
		if (!is_object($content)) {
			// no destination content object
			return;
		}
		$text = $content->GetURL();

		$parts = parse_url($text);
		if (isset($parts['query']) && $parts['query'] != '?') {
			$text .= '&';
		} else {
			$text .= '?';
		}
	}
	else {
		$text = CMS_ROOT_URL.'/lib/moduleinterface.php?';
	}

	$name = $modinst->GetName();
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
	doredirect($text);
};
