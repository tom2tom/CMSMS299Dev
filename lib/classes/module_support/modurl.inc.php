<?php
/*
URL-creation methods for CMS Made Simple <http://cmsmadesimple.org>
Copyright (C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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
use function cmsms;
use function CMSMS\entitize;
use function startswith;

/**
 * Methods for modules to construct URL's.
 *
 * @internal
 * @since 3.0
 * @package CMS
 * @license GPL
 */
/**
 * Get an URL which when accessed will run a module action
 *
 * @param object $mod The module-object
 * @param mixed $id	string|null GET|POST-submitted-parameters name-prefix
 *   (e.g. 'cntnt01' indicates that the default content block of the destination
 *   frontend-page is to be targeted), or a falsy value for an admin action
 *   will be translated to 'm1_'.
 * @param string $action	 The module action name
 * Optional parameters
 * @param mixed $returnid Optional page-id to return to after the action
 *   is done, numeric(int) or ''|null for admin. Default null.
 * @param array $params	Optional parameters to include in the URL. Default []
 *   These will be ignored if the prettyurl parameter is present.
 *   Since 3.0, parameter value(s) may be non-scalar: 1-D arrays processed directly,
 *   other things json-encoded if possible.
 * @param string $prettyurl URL segment(s) relative to the root-URL of the
 *   action, to generate a pretty-formatted URL
 * @param bool	$inline	Optional flag whether the target of the output link
 *   is the same tag on the same page Default false
 * @param bool	$targetcontentonly Optional flag whether the target of the
 *   output link targets the content area of the destination page Default false
 * @param string $prettyurl Optional url part(s), or ':NOPRETTY:' Default ''
 * @param bool $relative since 3.0 Optional flag whether to omit the site-root
 *   from the created url. Default false
 * @param int    $format since 3.0 Optional indicator for how to format the URL
 *  0 = entitized ' &<>"\'!$' chars in parameter keys and values,
 *   '&amp;' for parameter separators except 'mact' (clunky, back-compatible)
 *  1 = proper: rawurlencoded keys and values, '&amp;' for parameter separators
 *  2 = best for most contexts: as for 1, except '&' for parameter separators (default)
 *  3 = displayable: no encoding, all html_entitized, probably not usable as-is
 * @return string Ready-to-use or corresponding displayable URL.
 */
function CreateActionUrl(
	$mod,
	$id,
	string $action,
	$returnid = null,
	array $params = [],
	bool $inline = false,
	bool $targetcontentonly = false,
	string $prettyurl = '',
    bool $relative = false,
	int $format = 2
	) : string
{
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

	$base_url = ($relative) ? '' : CMS_ROOT_URL.'/';
	$config = Lone::get('Config');

	if (!$prettyurl && $config['url_rewriting'] != 'none') {
		// attempt to get a pretty url from the module
		$prettyurl = $mod->get_pretty_url($id, $action, $returnid, $params, $inline);
	}
	if ($prettyurl && $prettyurl != ':NOPRETTY:' && $config['url_rewriting'] == 'mod_rewrite') {
		$text = $base_url.$prettyurl.$config['page_extension'];
	} elseif ($prettyurl && $prettyurl != ':NOPRETTY:' && $config['url_rewriting'] == 'internal') {
		$text = $base_url.'index.php/'.$prettyurl.$config['page_extension'];
	} else {
		$frontend = is_numeric($returnid);

		if ($targetcontentonly || ($frontend && !$inline)) {
			$id = 'cntnt01';
		}
		$parms = [
			'module' => $mod->GetName(),
			'id' => $id,
			'action' => $action,
			'inline' => ($inline ? 1 : 0)
		];

		if ($frontend) {
			$text = $base_url.'index.php';
		} else {
			$text = $base_url.'lib/moduleinterface.php';
			if (isset($_SESSION[CMS_USER_KEY])) {
				$parms[CMS_SECURE_PARAM_NAME] = $_SESSION[CMS_USER_KEY];
			}
			//TODO ELSE redirect? or return error e.g.
			// '<!-- '.__METHOD__.' error : "session has expired -->';
		}

		$ignores = ['assign', 'id', 'returnid', 'action', 'module']; //CHECKME assign?
		foreach ($params as $key => $value) {
			if (startswith($key, CMS_SECURE_PARAM_NAME)) {
				$parms[$key] = $value;
			} elseif (!in_array($key, $ignores)) {
				$parms[$id.$key] = $value;
			}
		}
		if ($frontend) {
			$parms[$id.'returnid'] = $returnid;
			if ($inline) {
				$parms[$id.$config['query_var']] = $returnid;
			}
		}
		$text .= '?'.RequestParameters::create_action_params($parms, $format);
		if ($format == 3) {
			$text = entitize($text);
		}
	}
	return $text;
}

/**
 * Get an URL which when accessed will run a (non-displayed) module-job
 *
 * @param object $mod The module-object UNUSED
 * @param string $action The module action name
 * Optional parameters:
 * @param array $params Parameters to include in the URL. Default [].
 *   Since 3.0, parameter value(s) may be non-scalar: 1-D arrays processed directly,
 *   other things json-encoded if possible.
 * @param bool $onetime Whether the URL (specifically, its security-parameters) is for one-time use. Default false.
 * @param bool $relative since 3.0 Optional flag whether to omit the site-root
 *   from the created url. Default false
 * @param int  $format Indicator for how to format the URL
 *  0 = entitized ' &<>"\'!$' chars in parameter keys and values, '&amp;' for
 *   parameter separators other than 'mact' (clunky, back-compatible)
 *  1 = proper: rawurlencoded keys and values, '&amp;' for parameter separators
 *  2 = best for most contexts: as for 1, except '&' for parameter separators (default)
 *  3 = displayable: no encoding, all html_entitized, probably not usable as-is
 * @return string Ready-to-use or corresponding displayable URL.
 */
function CreateJobUrl(
	$mod,
	string $action,
	array $params = [],
	bool $onetime = false,
    bool $relative = false,
	int $format = 2
	) : string
{
	$action = trim($action); //sanitize not needed, breaks back-compatibility
	assert($action != false, __METHOD__.' error : $action parameter is missing');
	if (!$action) {
		return '<!-- '.__METHOD__.' error : "action" parameter is missing -->';
	}

	$base_url = ($relative) ? '' : CMS_ROOT_URL.'/';
	$text = $base_url.'lib/moduleinterface.php?';
	$text .= RequestParameters::create_job_params($params, $onetime, $format);

	if ($format == 3) {
		$text = entitize($text);
	}
	return $text;
}

/**
 * Get an URL which when accessed will display a site page
 *
 * @param object $mod The module-object
 * @param mixed $id	string|null GET|POST-submitted-parameters name-prefix
 *   (e.g. 'cntnt01' indicates that the default content block of the destination
 *   frontend-page is to be targeted), or a falsy value for an admin action
 *   will be translated to 'm1_'.
 * @param mixed $returnid The integer page-id to return to after the action
 *   is done, or ''|null for admin
 * @param array $params	  Optional array of parameters to include in the URL.
 *   Since 3.0, parameter value(s) may be non-scalar: 1-D arrays processed directly,
 *   other things json-encoded if possible.
 * @param bool $relative since 3.0 Optional flag whether to omit the site-root
 *   from the created url. Default false
 * @param int   $format since 3.0 Optional indicator for how to format the url
 *  0 = entitized ' &<>"\'!$' chars in parameter keys and values, '&amp;' for
 *    parameter separators except 'mact' (clunky, back-compatible)
 *  1 = rawurlencoded keys and values, '&amp;' for parameter separators
 *  2 = best for most contexts: as for 1, except '&' for parameter separators (default)
 *  3 = displayable: no encoding, all html_entitized, probably not usable as-is
 * @return string
 */
function CreatePageUrl(
	$mod,
 	$id,
	$returnid,
	array $params = [],
    bool $relative = false,
	int $format = 2
	) : string
{
	$text = '';
	$hm = cmsms()->GetHierarchyManager();
	$node = $hm->find_by_tag('id',$returnid);
	if ($node) {
		$contentobj = $node->getContent();
		if ($contentobj) {
			$pageurl = $contentobj->GetURL();
			if ($pageurl) {
				if ($id) {
					$id = trim($id);  //sanitize not needed, breaks back-compatibility
					assert($id != false, __METHOD__.' error : $id parameter is invalid');
					if (!$id) {
						return '<!-- '.__METHOD__.' error : "id" parameter is invalid -->';
					}
				} else {
					$id = 'm1_';
				}
				$params['id'] = $id;

				$text = ($relative) ? str_replace(CMS_ROOT_URL.'/', '', $pageurl) : $pageurl;
				$config = Lone::get('Config');
				if ($config['url_rewriting'] != 'none') {
					// attempt to get a pretty url from the module TODO support empty action-name
					$prettyurl = $mod->get_pretty_url($id, '', $returnid, $params, false);
					if ($prettyurl && $prettyurl != ':NOPRETTY:') {
						$text .= '/'.$prettyurl;
						return $text;
					}
				}
				$text .= '?'.RequestParameters::create_action_params($params, $format);
				if ($format == 3) {
					$text = entitize($text);
				}
			}
		}
	}
	return $text;
}
