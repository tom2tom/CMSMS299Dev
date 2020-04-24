<?php
/*
URL-creation methods for CMS Made Simple <http://cmsmadesimple.org>
Copyright (C) 2011-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\internal\GetParameters;

/**
 * Methods for modules to construct URL's.
 *
 * @since 2.3
 *
 * @license GPL
 */

/**
 * @internal
 *
 * Get an URL representing a module action
 *
 * @param module-object $modinstance The module to which the action belongs
 * @param mixed $id		string|null The module action-id (e.g. 'cntnt01' indicates
 *   that the default content block of the destination frontend-page is to be targeted),
 *   a falsy value for an admin action will be translated to 'm1_'.
 * @param string $action	The module action name
 * Optional parameters
 * @param mixed $returnid Optional page-id to return to after the action
 *   is done, numeric(int) or ''|null for admin. Default null.
 * @param array $params	Optional parameters to include in the URL. Default []
 *   These will be ignored if the prettyurl parameter is present.
 *   Since 2.3, parameter value(s) may be non-scalar: 1-D arrays processed directly,
 *   other things json-encoded if possible.
 * @param string $prettyurl URL segment(s) relative to the root-URL of the
 *   action, to generate a pretty-formatted URL
 * @param bool	$inline	Optional flag whether the target of the output link
 *   is the same tag on the same page Default false
 * @param bool	$targetcontentonly Optional flag whether the target of the
 *   output link targets the content area of the destination page Default false
 * @param string $prettyurl Optional url part(s), or ':NOPRETTY:' Default ''
 * @param int    $format since 2.3 Optional indicator for how to format the URL
 *  0 = (default, back-compatible) rawurlencoded parameter keys and values
 *      other than the value for key 'mact', '&amp;' for parameter separators
 *  1 = proper: as for 0, but also encode the 'mact' value
 *  2 = raw: as for 1, except '&' for parameter separators - e.g. for use in js
 *  3 = displayable: no encoding, all html_entitized, probably not usable as-is
 * @return string Ready-to-use or corresponding displayable URL.
 */
function cms_module_create_actionurl(
	&$modinstance,
	$id,
	string $action,
	$returnid = null,
	array $params = [],
	bool $inline = false,
	bool $targetcontentonly = false,
	string $prettyurl = '',
	int $format = 0
	) : string {

	if ($id) {
		$id = trim($id); //sanitize not needed, breaks back-compatibility
		assert($id != false, __METHOD__.' error : $id parameter is invalid');
		if (!$id) {
			return '<!-- '.__METHOD__.' error : "id" parameter is invalid -->';
		}
	} else {
		$id = 'm1_';
	}

	$action = trim($action); //sanitize not needed, breaks back-compatibility
	assert($action != false, __METHOD__.' error : $action parameter is missing');
	if (!$action) {
		return '<!-- '.__METHOD__.' error : "action" parameter is missing -->';
	}

	$base_url = CMS_ROOT_URL;
	$config = cms_config::get_instance();

	if (!$prettyurl && $config['url_rewriting'] != 'none') {
		// attempt to get a pretty url from the module
		$prettyurl = $modinstance->get_pretty_url($id, $action, $returnid, $params, $inline);
	}
	if ($prettyurl && $prettyurl != ':NOPRETTY:' && $config['url_rewriting'] == 'mod_rewrite') {
		$text = $base_url.'/'.$prettyurl.$config['page_extension'];
	} elseif ($prettyurl && $prettyurl != ':NOPRETTY:' && $config['url_rewriting'] == 'internal') {
		$text = $base_url.'/index.php/'.$prettyurl.$config['page_extension'];
	} else {
		$frontend = is_numeric($returnid);
		if ($frontend) {
			$text = $base_url . '/index.php';
		} else {
			$text = $config['admin_url'] . '/moduleinterface.php';
		}

		if ($targetcontentonly || ($frontend && !$inline)) {
			$id = 'cntnt01';
		}
		$parms = [
			'module' => $modinstance->GetName(),
			'id' => $id,
			'action' => $action,
			'inline' => ($inline ? 1 : 0)
		];
		if (!$frontend) {
			if (isset($_SESSION[CMS_USER_KEY])) {
				$parms[CMS_SECURE_PARAM_NAME] = $_SESSION[CMS_USER_KEY];
			}
			//TODO ELSE redirect? or return error e.g.
			// '<!-- '.__METHOD__.' error : "session has expired -->';
		}

		$ignores = ['assign', 'id', 'returnid', 'action', 'module']; //CHECKME assign?
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
		$text .= '?'.(new GetParameters())->create_action_params($parms, $format);
	}
    if ($format == 3) {
		$text = cms_htmlentities($text);
    }
	return $text;
}

/**
 * @internal
 *
 * Get an URL representing a site page
 *
 * @param mixed $id	string|null The module action-id (e.g. 'cntnt01' indicates that
 *   the default content block of the destination frontend-page is to be targeted),
 *   a falsy value for an admin page will be translated to 'm1_'.
 * @param mixed $returnid The integer page-id to return to after the action
 *   is done, or ''|null for admin
 * @param array $params	  Optional array of parameters to include in the URL.
 *   Since 2.3, parameter value(s) may be non-scalar: 1-D arrays processed directly,
 *   other things json-encoded if possible.
 * @param int   $format since 2.3 Optional indicator for how to format the url
 *  0 = default: rawurlencoded parameter keys and values, '&amp;' for parameter separators
 *  1 = raw: as for 0, except '&' for parameter separators - e.g. for use in js
 *  2 = page-displayable: all html_entitized, probably not usable as-is
 * @return string
 */
function cms_module_create_pageurl($id, $returnid, array $params = [], int $format = 0) : string
{
	$text = '';
	$gCms = CmsApp::get_instance();
	$hm = $gCms->GetHierarchyManager();
	$node = $hm->find_by_tag('id',$returnid);
	if ($node) {
		$contentobj = $node->getContent();
		if ($contentobj) {
			$pageurl = $contentobj->GetURL();
			if ($pageurl) {
				switch ($format) {
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
				$first = true;

				if ($id) {
					$id = trim($id);  //sanitize not needed, breaks back-compatibility
					assert($id != false, __METHOD__.' error : $id parameter is invalid');
					if (!$id) {
						return '<!-- '.__METHOD__.' error : "id" parameter is invalid -->';
					}
				} else {
					$id = 'm1_';
				}

				//TODO use http_build_query($params)
				foreach ($params as $key => $value) {
					if ($first) {
						$config = $gCms->GetConfig();
						if ($config['url_rewriting'] != 'none') {
							$text .= '?';
						} else {
							$text .= $sep;
						}
						$first = false;
					} else {
						$text .= $sep;
					}

					$key = $id.$key;
					if (is_scalar($value)) {
						if ($enc) {
							$key = rawurlencode($key);
							$value = rawurlencode($value);
						}
						$text .= $key.'='.$value;
					} else {
						$text .= cms_build_query($key, $value, $sep, $enc);
					}
				}
				if ($format == 2) {
					$text = cms_htmlentities($text);
				}
			}
		}
	}
	return $text;
}
