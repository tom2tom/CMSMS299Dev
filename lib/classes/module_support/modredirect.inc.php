<?php
/*
Redirection-related functions for modules
Copyright (C) 2004-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify it
under the terms of the GNU General Public License as published by the
Free Software Foundation; either version 3 of that license, or (at your option)
any later version.

CMS Made Simple is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS\module_support;

use CMSMS\Lone;
use CMSMS\RequestParameters;
use const CMS_ROOT_URL;
use const CMS_SECURE_PARAM_NAME;
use const CMS_USER_KEY;
use function CMSMS\urlencode;
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
 * @param CMSModule $mod UNUSED
 * @param string $pageurl PHP script to redirect to
 * @param array $params URL parameters Default []
 */
function RedirectToAdmin($mod, $pageurl, array $params = [])
{
	$url = $pageurl.get_secure_param($pageurl);
	if ($params) {
		foreach ($params as $key=>$value) {
			if (is_scalar($value)) {
				$url .= '&'.urlencode($key.'='.$value);
			} else {
				$url .= '&'.RequestParameters::build_query($key, $value);
			}
		}
	}
	doredirect($url);
}

/**
 *
 * @param CMSModule $mod
 * @param string $id
 * @param string $action
 * @param mixed $returnid Default ''
 * @param array $params Default []
 * @param bool $inline Default false
 */
function Redirect($mod, $id, $action, $returnid = '', array $params = [], bool $inline = false)
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
		$contentops = Lone::get('ContentOperations');
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

	$name = $mod->GetName();
	$text .= 'mact='.$name.','.$id.','.$action.','.($inline ? 1 : 0);
	if ($returnid) {
		$text .= '&'.$id.'returnid='.$returnid; // nothing encodish here
	}
	else {
		$text .= '&'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY]; // nor here
	}

	foreach ($params as $key=>$value) {
		if ($key && $value !== '') {
			if (is_scalar($value)) {
				$text .= '&'.urlencode($id.$key.'='.$value);
			} else {
				$text .= '&'.RequestParameters::build_query($id.$key, $value);
			}
		}
	}
	doredirect($text);
}
