<?php
/*
URL-creation methods for CMS Made Simple <http://cmsmadesimple.org>
(c) 2004-2010 Ted Kulp <ted@cmsmadesimple.org>
(c) 2011-2018 The CMSMS Dev Team

This file is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This file is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
Or read it online at:
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
 * @param mixed $returnid The integer page-id to return to after the action
 *   is done, or '' for admin
 * @param array $params	Parameters to include in the URL.
 *   These will be ignored if the prettyurl parameter is present
 * @param string $prettyurl URL segment(s) relative to the root-URL of the
 *   action, to generate a pretty-formatted URL
 * @param bool	$inline	Whether the target of the output link is the same
 *   tag on the same page
 * @param bool	$targetcontentonly Whether the target of the output link
 *   targets the content area of the destination page
 * @param bool 	$for_display Whether the generated URL is for public
 *   (on page) display, hence is probably not usable verbatim. Defaults to true
 *   (for compatibility reasons). Since 2.3
 *
 * @return string Ready-to-use or corresponding displayable URL.
 */
function cms_module_create_actionurl(
	&$modinstance,
	string $id,
	string $action,
	$returnid = '',
	array $params = [],
	bool $inline = false,
	bool $targetcontentonly = false,
	string $prettyurl = '',
	bool $for_display = true
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
		if ($targetcontentonly || ($returnid !== '' && !$inline)) {
			$id = 'cntnt01';
		}
		if ($returnid === '') {
			$text = $config['admin_url'];
			$goto = 'moduleinterface.php';
			$secureparam = '&'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
		} else {
			$text = $base_url;
			$goto = 'index.php';
			$secureparam = '';
		}

		$text .= '/'.$goto.'?mact='.$modinstance->GetName().','.$id.','.$action.','.($inline ? 1 : 0).$secureparam;
		if (isset($params['returnid']) && $returnid !== '') {
			unset($params['returnid']);
		}

		$ignores = ['assign', 'id', 'returnid', 'action', 'module'];
		foreach ($params as $key => $value) {
			if (!in_array($key, $ignores)) {
				$text .= '&'.$id.$key.'='.$value;
			}
		}
		if ($returnid !== '') {
			$text .= '&'.$id.'returnid='.$returnid;
			if ($inline) {
				$text .= '&'.$config['query_var'].'='.$returnid;
			}
		}
	}
	if ($for_display) {
		$text = cms_htmlentities($text);
	} else {
		$text = rawurlencode($text);
	}
	return $text;
}

/**
 * @internal
 *
 * Get an URL representing a site page
 *
 * @param string $id		  The module action-id (e.g. 'cntnt01'indicates that the
 *   default content block of the destination frontend-page is to be targeted)
 * @param mixed $returnid The integer page-id to return to after the action
 *   is done, or '' for admin
 * @param array $params	  Optional array of parameters to include in the URL.
 * @param bool 	$for_display Whether the generated URL is for public
 *  (on page) display, hence is probably not usable verbatim. Defaults
 *  to false.
 * @return string
 */
function cms_module_create_pageurl($id, $returnid, array $params = [], bool $for_display = false) : string
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
				$text .= $pageurl;
				$count = 0;
				foreach ($params as $key => $value) {
					if ($count == 0) {
						$config = $gCms->GetConfig();
						if ($config['url_rewriting'] != 'none') {
							$text .= '?';
						} else {
							$text .= '&';
						}
					} else {
						$text .= '&';
					}
					$text .= $id.$key.'='.$value;
					++$count;
				}
			}
			if ($for_display) {
				$text = cms_htmlentities($text);
			} else {
				$text = rawurlencode($text);
			}
		}
	}
	return $text;
}

/**
 * @internal
 */
function cms_module_create_joburl(&$modinstance, string $action, array $params = [], bool $secure = true) : string
{
	$modname = $modinstance->GetName();
	$id = 'aj_';

	$text = CMS_ROOT_URL.'/jobinterface.php?mact='.$modname.','.$id.','.$action.',0';

	if ($secure) {
		$text .= cms_module_create_secure_params($modinstance);
	}

	if ($params) {
		$ignores = ['assign', 'id', 'returnid', 'action', 'module', CMS_SECURE_PARAM_NAME];
		foreach ($params as $key => $value) {
			if (!in_array($key, $ignores)) {
				$text .= '&'.$id.rawurlencode($key).'='.rawurlencode($value);
			}
		}
	}

	return $text;
}

/**
 * @access private
 */
function cms_module_create_secure_params(&$modinstance)
{
	$modname = $modinstance->GetName();
	$id = 'aj_';

	$key = uniqid($modname, true);
	// djb2a hash
	$bytes = array_values(unpack('C*', $key)); //actual byte-length (i.e. no mb interference);
	$bc = count($bytes);
	$h = 5381;
	for ($i = 0; $i < $bc; ++$i) {
		$h = ($h + ($h << 5)) ^ $bytes[$i];
	}
	$token = dechex($h);
	$def = '/V^^V\\'.microtime();
	while (1) {
		$key = str_shuffle($key);
		$subkey = substr($key, 0, 6);
		if ($modinstance->GetPreference($subkey, $def) == $def) {
			$modinstance->SetPreference($subkey, $token);
			break;
		}
	}
	$text = '&'.$id.CMS_SECURE_PARAM_NAME.'='.$subkey.'&'.$id.$subkey.'='.$token;
	return $text;
}

/**
 * @access private
 */
function cms_module_check_secure_params(&$modinstance, $params)
{
	if (empty($params[CMS_SECURE_PARAM_NAME])) {
		return false;
	}
	$key = $params[CMS_SECURE_PARAM_NAME];
	$def = '/V^^V\\'.microtime();
	$log = $modinstance->GetPreference($key, $def);
	if ($log == $def) {
		return false;
	}
	$modinstance->RemovePreference($key);
	if (empty($params[$key])) {
		return false;
	}
	return $log == $params[$key];
}
