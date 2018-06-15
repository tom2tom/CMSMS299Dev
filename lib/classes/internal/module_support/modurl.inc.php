<?php
/*
URL-creation methods for CMS Made Simple <http://cmsmadesimple.org>
Copyright (C) 2004-2010 Ted Kulp <ted@cmsmadesimple.org>
Copyright (C) 2011-2018 The CMSMS Dev Team <coreteam@cmsmadesimple.org>

This file is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This file is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

/**
 * Methods for modules to construct URL's.
 *
 * @since	2.3
 *
 * @license GPL
 */

/**
 * @internal
 *
 * Get an URL representing a module action
 *
 * @param module-object $modinstance The module to which the action belongs
 * @param string $id		The module action-id (e.g. 'cntnt01' indicates that the
 *   default content block of the destination frontend-page is to be targeted)
 * @param string $action	The module action name
 * Optional parameters
 * @param mixed $returnid Optional integer page-id to return to after the action
 *   is done, or ''|null for admin. Default null.
 * @param array $params	Optional parameters to include in the URL. Default []
 *   These will be ignored if the prettyurl parameter is present
 * @param string $prettyurl URL segment(s) relative to the root-URL of the
 *   action, to generate a pretty-formatted URL
 * @param bool	$inline	Optional flag whether the target of the output link
 *   is the same tag on the same page Default false
 * @param bool	$targetcontentonly Optional flag whether the target of the
 *   output link targets the content area of the destination page Default false
 * @param string $prettyurl Optional url part(s) Default ''
 * @param int    $mode since 2.3 Optional indicator for how to format the url
 *  0 = (default) rawurlencoded parameter keys and values, '&amp;' for parameter separators
 *  1 = raw: no rawurlencoding, '&' for parameter separators - e.g. for use in js
 *  2 = page-displayable: all html_entitized, probably not usable as-is
 *
 * @return string Ready-to-use or corresponding displayable URL.
 */
function cms_module_create_actionurl(
	&$modinstance,
	string $id,
	string $action,
	$returnid = null,
	array $params = [],
	bool $inline = false,
	bool $targetcontentonly = false,
	string $prettyurl = '',
	int $mode = 0
	) : string {

	$id = sanitize($id);
	assert($id != false, __METHOD__.' error : $id parameter is missing');
	if (!$id) {
		return '<!-- '.__METHOD__.' error : "id" parameter is missing -->';
	}
	$action = sanitize($action);
	assert($action != false, __METHOD__.' error : $action parameter is missing');
	if (!$action) {
		return '<!-- '.__METHOD__.' error : "action" parameter is missing -->';
	}

	$base_url = CMS_ROOT_URL;

	$config = \cms_config::get_instance();
	if (empty($prettyurl) && $config['url_rewriting'] != 'none') {
		// attempt to get a pretty url from the module
		$prettyurl = $modinstance->get_pretty_url($id, $action, $returnid, $params, $inline);
	}
	if ($prettyurl && $config['url_rewriting'] == 'mod_rewrite') {
		$text = $base_url.'/'.$prettyurl.$config['page_extension'];
	} elseif ($prettyurl && $config['url_rewriting'] == 'internal') {
		$text = $base_url.'/index.php/'.$prettyurl.$config['page_extension'];
	} else {
        $frontend = is_int($returnid);
		if ($targetcontentonly || ($frontend && !$inline)) {
			$id = 'cntnt01';
		}

		$parms = [
		'mact' => $modinstance->GetName().','.$id.','.$action.','.($inline ? 1 : 0)
		];

		if (!$frontend) {
/*TODO			if (!isset($_SESSION[CMS_USER_KEY])) {
				return '<!-- '.__METHOD__.' error : "session has expired -->';
			}
*/
			$text = $config['admin_url'] . '/moduleinterface.php';
			$parms[CMS_SECURE_PARAM_NAME] = $_SESSION[CMS_USER_KEY];
		} else {
			$text = $base_url . 'index.php';
		}

		if (!empty($params['returnid'])) {
			unset($params['returnid']);
		}

		$ignores = ['assign', 'id', 'returnid', 'action', 'module'];
		foreach ($params as $key => $value) {
			if (!in_array($key, $ignores)) {
				$parms[$id.$key] = $value;
			}
		}
		if ($frontend) {
			$parms[$id.'returnid'] = $returnid;
			if ($inline) {
				$parms[$id.$config['query_var']] = $returnid;
			}
		}

		switch ($mode) {
			case 1:
			case 2:
				$sep = '&';
				$enc = false;
				break;
			default:
				$sep = '&amp;';
				$enc = true;
				break;
		}

		$count = 0;
		foreach ($parms as $key => $value) {
			if ($enc) {
				$key = rawurlencode($key);
				$value = rawurlencode($value);
			}
			if ($count == 0) {
				$text .= '?'.$key.'='.$value;
				++$count;
			} else {
				$text .= $sep.$key.'='.$value;
			}
		}
	}
	if ($mode == 2) {
		$text = cms_htmlentities($text);
	}
	return $text;
}

/**
 * @internal
 *
 * Get an URL representing a site page
 *
 * @param string $id		  The module action-id (e.g. 'cntnt01' indicates that
 *   the default content block of the destination frontend-page is to be targeted)
 * @param mixed $returnid The integer page-id to return to after the action
 *   is done, or ''|null for admin
 * @param array $params	  Optional array of parameters to include in the URL.
 * @param int   $mode since 2.3 Optional indicator for how to format the url
 *  0 = (default) rawurlencoded parameter keys and values, '&amp;' for parameter separators
 *  1 = raw: as for 0, except '&' for parameter separators - e.g. for use in js
 *  2 = page-displayable: all html_entitized, probably not usable as-is
 * @return string
 */
function cms_module_create_pageurl(string $id, $returnid, array $params = [], int $mode = 0) : string
{
	$text = '';
	$gCms = \CmsApp::get_instance();
	$manager = $gCms->GetHierarchyManager();
	$node = $manager->sureGetNodeById($returnid);
	if ($node) {
		$content = $node->GetContent();
		if ($content) { //CHECKME
			$pageurl = $content->GetURL();
			if ($pageurl) {
				switch ($mode) {
					case 1:
						$sep = '&';
						$enc = true;
						break;
					case 2:
						$sep = '&';
						$enc = false;
						break;
					default:
						$sep = '&amp;';
						$enc = true;
						break;
				}

				$text = $pageurl;
				$count = 0;
				foreach ($params as $key => $value) {
					if ($count == 0) {
						$config = $gCms->GetConfig();
						if ($config['url_rewriting'] != 'none') {
							$text .= '?';
						} else {
							$text .= $sep;
						}
						++$count;
					} else {
						$text .= $sep;
					}

					$key = $id.$key;
					if ($enc) {
						$key = rawurlencode($key);
						$value = rawurlencode($value);
					}
					$text .= $key.'='.$value;
				}
				if ($mode == 2) {
					$text = cms_htmlentities($text);
				}
			}
		}
	}
	return $text;
}
