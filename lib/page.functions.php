<?php
/*
Miscellaneous CMSMS-dependent support functions (not only 'page'-related).
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify it
under the terms of the GNU General Public License as published by the
Free Software Foundation; either version 2 of that license, or (at your option)
any later version.

CMS Made Simple is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace {

use CMSMS\App;
use CMSMS\AppParams;
use CMSMS\AppState;
use CMSMS\LogOperations;
use CMSMS\CoreCapabilities;
use CMSMS\Crypto;
use CMSMS\DeprecationNotice;
use CMSMS\FileTypeHelper;
use CMSMS\FormUtils;
use CMSMS\IMultiEditor;
use CMSMS\internal\ModulePluginOperations;
use CMSMS\NlsOperations;
use CMSMS\RequestParameters;
use CMSMS\RouteOperations;
use CMSMS\SingleItem;
use CMSMS\UserParams;
use CMSMS\Utils;
use Exception;
use LogicException;
use function CMSMS\de_entitize;
use function CMSMS\entitize;
use function CMSMS\execSpecialize;
use function CMSMS\add_debug_message;
use function CMSMS\get_debug_messages;
use function CMSMS\get_site_UUID;
use function CMSMS\log_error;
use function CMSMS\specialize;
use function CMSMS\urlencode;
use function endswith;
use function redirect;
use function startswith;

/**
 * Miscellaneous support functions which are dependent on this CMSMS
 * instance i.e. its settings, defines, classes etc
 * When preparing to process a request, this file must not be included
 * until all its prerequisites are present.
 *
 * @package CMS
 * @license GPL
 */

/**
 * Return the App singleton object
 * @see SingleItem::App()
 * @since 1.7
 *
 * @return App
 */
function cmsms() : App
{
	return SingleItem::App();
}

/**
 * Check whether the supplied identifier matches the site UUID
 * This is a security function e.g. in module actions: <pre>if (!checkuuid($uuid)) exit;</pre>
 * @since 2.99
 *
 * @param mixed $uuid identifier to be checked
 * @return bool indicating success
 */
function checkuuid($uuid) : bool
{
	return hash_equals(get_site_UUID(), $uuid.'');
}

/**
 * @ignore
 * @since 2.0.2
 */
function setup_session(bool $cachable = false)
{
	static $_setup_already = false;

	if ($_setup_already) {
		//TODO maybe session_regenerate_id(), if so, rename cache-group accordingly
		return;
	}

	$_f = $_l = null;
	if (headers_sent($_f, $_l)) {
		throw new LogicException("Attempt to set headers, but headers were already sent at: $_f::$_l");
	}
	if ($cachable) {
		if ($_SERVER['REQUEST_METHOD'] != 'GET' ||
		AppState::test_any(AppState::ADMIN_PAGE | AppState::INSTALL)) {
			$cachable = false;
		}
	}
	if ($cachable) {
		$cachable = (int) AppParams::get('allow_browser_cache', 0);
	}
	if (!$cachable) {
		// admin pages can't be cached... period, at all.. never.
		@session_cache_limiter('nocache');
	} else {
		// frontend request
		$expiry = (int)max(0, AppParams::get('browser_cache_expiry', 60));
		session_cache_expire($expiry);
		session_cache_limiter('public');
		@header_remove('Last-Modified');
	}

	// setup session with different (constant) id and start it
	$session_name = 'CMSSESSID'.Crypto::hash_string(CMS_ROOT_PATH.CMS_VERSION);
	if (!AppState::test(AppState::INSTALL)) {
		@session_name($session_name);
		@ini_set('url_rewriter.tags', '');
		@ini_set('session.use_trans_sid', 0);
	}

	if (isset($_COOKIE[$session_name])) {
		// validate the content of the cookie
		if (!preg_match('/^[a-zA-Z0-9,\-]{22,40}$/', $_COOKIE[$session_name])) {
			session_id(uniqid());
			session_start();
			session_regenerate_id(); //TODO rename cache-group accordingly
		}
	}
	if (!@session_id()) {
		session_start();
	}

	/* TODO session-shutdown function(s) processing, from handler(s) recorded in
		session_set_save_handler(
			callable1, ... callableN
		);
		session_register_shutdown();
	*/
	$_setup_already = true;
}

/**
 * A convenience function to test if the site is marked as down according to the config panel.
 * This method recognizes the site-preference relating to disabling
 * site-down status for certain IP address ranges.
 *
 * @return boolean
 */
function is_sitedown() : bool
{
	if (AppState::test(AppState::INSTALL)) {
		return true;
	}

	if (!AppParams::get('site_downnow')) {
		return false;
	}

	$userid = get_userid(false);
	if ($userid && AppParams::get('sitedownexcludeadmins')) {
		return false;
	}

	if (!isset($_SERVER['REMOTE_ADDR'])) {
		return true;
	}
	$excludes = AppParams::get('sitedownexcludes');
	if (!$excludes) {
		return true;
	}
	return !cms_ipmatches($_SERVER['REMOTE_ADDR'], $excludes);
}

/* * MAYBE IN FUTURE
 * Gets the username of the current CLI-user
 * NOT cached/static (to support concurrent-use)
 * @since 2.99
 *
 * @return mixed string|null
 */
/*function get_cliuser()
{
	$uname = exec('whoami');
	if (!$uname) {
		$file = tempnam(PUBLIC_CACHE_LOCATION, 'WHOMADE_');
		file_put_contents($file, 'test');
		$userid = fileowner($file);
		unlink($file);
		if ($userid) { //might be false
			if (function_exists('posix_getpwuid')) {
				$uname = posix_getpwuid($userid)['name']; //approximate, hack
			} else {
				$uname = getenv('USERNAME');
			}
		}
	}
	return $uname;
}
*/
/**
 * Gets the numeric id of the current user (primary/logged-in or 'effective').
 *
 * @since 0.1
 * @param  boolean $redirect Optional flag, default true. Whether to redirect to
 *  the admin login page if the user is not logged in (and operating in 'normal' mode).
 * @return mixed integer id of the current user | 0 | null
 */
function get_userid(bool $redirect = true)
{
//	$config = SingleItem::Config();
//	if (!$config['app_mode']) { MAYBE IN FUTURE
/* MAYBE IN FUTURE		if (cmsms()->is_cli()) {
		$uname = get_cliuser();
		if ($uname) {
			$user = SingleItem::UserOperations()->LoadUserByUsername($uname);
			if ($user) {
				return $user->id;
			}
		}
		return null;
	}
*/
	// TODO alias etc during 'remote admin'
	$userid = SingleItem::LoginOperations()->get_effective_uid();
	if (!$userid && $redirect) {
		redirect(SingleItem::Config()['admin_url'].'/login.php');
	}
	return $userid;
//	}
//	return 1; //CHECKME is the super-admin the only possible user in app mode ? if doing remote admin?
}

/**
 * Gets the username of the current user (primary/logged-in or 'effective').
 * @since 2.0
 *
 * @param  boolean $redirect Optional flag, default true. Whether to redirect to
 *  the admin login page if the user is not logged in.
 * @return string the username of the user, or '', or no return at all.
 */
function get_username(bool $redirect = true)
{
//	$config = SingleItem::Config();
//	if (!$config['app_mode']) { MAYBE IN FUTURE
/* MAYBE IN FUTURE		if (cmsms()->is_cli()) {
			return get_cliuser();
		}
*/
	//TODO alias etc during 'remote admin'
	$uname = SingleItem::LoginOperations()->get_effective_username();
	if (!$uname && $redirect) {
		redirect(SingleItem::Config()['admin_url'].'/login.php');
	}
	return $uname;
//	}
//	return ''; //no username in app mode
}

/**
 * Checks whether the user is logged in and the request has the proper key.
 * If not, normally redirect the browser to the admin login.
 * Note: this method should only be called from admin operations.
 * @since 0.1
 *
 * @param boolean $no_redirect Optional flag, default false. If true, then do NOT redirect if not logged in
 * @return boolean or no return at all
 */
function check_login(bool $no_redirect = false)
{
	$redirect = !$no_redirect;
	$userid = get_userid($redirect);
	$ops = SingleItem::LoginOperations();
	if ($userid > 0) {
		if ($ops->validate_requestkey()) {
			return true;
		}
		// still here if logged in, but no/invalid secure-key in the request
	}
	if ($redirect) {
		// redirect to the admin login page
		// use SCRIPT_FILENAME and make sure it validates with the root_path
		if (startswith($_SERVER['SCRIPT_FILENAME'], CMS_ROOT_PATH)) {
			$_SESSION['login_redirect_to'] = $_SERVER['REQUEST_URI'];
		}
		$ops->deauthenticate();
		redirect(SingleItem::Config()['admin_url'].'/login.php');
	}
	return false;
}

/**
 * Gets the permissions (names) which always require explicit authorization
 *  i.e. even for super-admins (user 1 | group 1)
 * @since 2.99
 *
 * @return array
 */
function restricted_cms_permissions() : array
{
	$val = AppParams::get('ultraroles');
	if ($val) {
		$out = json_decode($val);
		if ($out) {
			if (is_array($out)) {
				return $out;
			}
			if (is_scalar($out)) {
				return [$out];
			}
			return (array)$out;
		}
		//return TODO [defaults] upon error
	}
	return [];
}

/**
 * Checks to see that the given userid has access to the given permission.
 * Members of the admin group have all permissions.
 * @since 0.1
 *
 * @param int $userid The user id
 * @param varargs $perms Since 2.99 This may be a single permission-name string,
 *  or an array of such string(s), all members of which are to be 'OR'd,
 *  unless there's a following true-valued parameter, in which case those
 *  members are to be 'AND'd
 *  Formerly the second argument was limited to one permission-name string
 * @return boolean
 */
function check_permission(int $userid, ...$perms)
{
	return SingleItem::UserOperations()->CheckPermission($userid, ...$perms);
}

/**
 * Checks that the given userid has access to modify the given
 * pageid.  This would mean that they were set as additional
 * authors/editors by the owner.
 * @internal
 * @since 0.2
 *
 * @param  integer The admin user identifier
 * @param  mixed   Optional (valid) integer content id | null. Default null.
 * @return boolean
 */
function check_authorship(int $userid, $contentid = null)
{
	return SingleItem::ContentOperations()->CheckPageAuthorship($userid, $contentid);
}

/**
 * Gets an array of pages whose author is $userid
 * @internal
 * @since 0.11
 *
 * @param  integer The user id
 * @return array   The page-id's, or maybe empty
 */
function author_pages(int $userid)
{
	return SingleItem::ContentOperations()->GetPageAccessForUser($userid);
}

/**
 * Redirects to a current-site URL
 *
 * If headers have not been sent this method will use header-based redirection.
 * Otherwise javascript redirection will be used.
 *
 * @author http://www.edoceo.com/
 * @since 0.1
 * @package CMS
 * @param string $to The URL to redirect to
 */
function redirect(string $to)
{
	$app = SingleItem::App();
/* MAYBE IN FUTURE  if ($app->is_cli()) {
		die("ERROR: no redirect on cli-based scripts ---\n");
	}
*/
	$_SERVER['PHP_SELF'] = null;
	//TODO generally support the websocket protocol 'wss' : 'ws'
	$schema = ($app->is_https_request()) ? 'https' : 'http';

	$host = $_SERVER['HTTP_HOST'];
	$components = parse_url($to);
	if (count($components) > 0) {
		$to = (isset($components['scheme']) && startswith($components['scheme'], 'http') ? $components['scheme'] : $schema) . '://';
		$to .= $components['host'] ?? $host;
		$to .= isset($components['port']) ? ':' . $components['port'] : '';
		if (isset($components['path'])) {
			//support admin sub-domains
			$l = strpos($components['path'], '.php', 1);
			if ($l > 0 && substr_count($components['path'], '.', 1, $l - 1) > 0) {
				$components['path'] = strtr(substr($components['path'], 0, $l), '.', '/') . substr($components['path'], $l);
			}
			if (in_array($components['path'][0], ['\\', '/'])) {
				//path is absolute, just append // TODO double-'/' ok ?
				$to .= $components['path'];
			} else //path is relative, append current directory first
			if (!empty($_SERVER['PHP_SELF'])) { //Apache, sometimes
				$to .= (strlen(dirname($_SERVER['PHP_SELF'])) > 1 ?
				dirname($_SERVER['PHP_SELF']).'/' : '/') . // Windows also supports '/' as filepath separator
				$components['path'];
			} elseif (!empty($_SERVER['REQUEST_URI'])) { //Lighttpd, Apache sometimes
				if (endswith($_SERVER['REQUEST_URI'], '/')) {
					$to .= (strlen($_SERVER['REQUEST_URI']) > 1 ? $_SERVER['REQUEST_URI'] : '/') . $components['path'];
				} else {
					$dn = dirname($_SERVER['REQUEST_URI']); // Windoze also supports path-separator '/'
					if (!endswith($dn, '/')) {
						$dn .= '/';
					}
					$to .= $dn . $components['path'];
				}
			}
		}
		$to .= isset($components['query']) ? '?' . $components['query'] : '';
		$to .= isset($components['fragment']) ? '#' . $components['fragment'] : '';
	} else {
		$to = $schema.'://'.$host.'/'.$to;
	}

	session_write_close();

	if (!AppState::test(AppState::INSTALL)) {
		$debug = constant('CMS_DEBUG');
	} else {
		$debug = false;
	}

	if (!$debug && headers_sent()) {
		// use javascript instead
		echo '<script type="text/javascript">
<!-- location.replace("'.$to.'"); // -->
</script>
<noscript>
<meta http-equiv="Refresh" content="0;URL='.$to.'">
</noscript>';
	} elseif ($debug) {
		try {
			throw new Exception('');
		} catch (Throwable $t) {
			$arr = debug_backtrace();
			$from = 'file: '.basename($arr[0]['file']) . ' (line '.$arr[0]['line'].')';
		}
		if (startswith($to, CMS_ROOT_URL)) {
			$to2 = 'url: '.substr($to, strlen(CMS_ROOT_URL) + 1);
		} else {
			$to2 = $to;
		}
		echo 'Please click <a accesskey="r" href="'.$to.'">this link</a> to complete redirection<br />from<br />'.
$from.
'<br />to<br />'.
$to2.
'<br />
<br />
<div id="DebugFooter">';
		foreach (get_debug_messages() as $msg) {
			echo $msg;
		}
		echo '</div> <!-- end DebugFooter -->';
	} else {
		header("Location: $to");
	}
	exit;
}

/**
 * Given a page ID or an alias, redirect to it.
 * Retrieves the URL of the specified page, and performs a redirect
 *
 * @param string $alias A page alias
 */
function redirect_to_alias(string $alias)
{
	$hm = SingleItem::App()->GetHierarchyManager();
	$node = $hm->find_by_tag('alias', $alias);
	if (!$node) {
		// put mention into the admin log
		log_error('Attempt to redirect to invalid page',$alias);
		return;
	}
	$contentobj = $node->getContent();
	if (!is_object($contentobj)) {
		log_error('Attempt to redirect to invalid page',$alias);
		return;
	}
	$url = $contentobj->GetURL();
	if ($url) {
		redirect($url);
	}
}

/**
 * Returns a page id or alias, determined from current request data.
 * This method also handles matching routes and specifying which module
 * should be called with what parameters.
 * This is the main routine to do route-dispatching
 * @internal
 * @ignore
 *
 * @return mixed int | string ( | null? )
 */
function get_pageid_or_alias_from_url()
{
	$config = SingleItem::Config();
	$page = RequestParameters::get_request_values($config['query_var']);
	if ($page !== null) {
		// using non-friendly urls... get the page alias/id from the query var.
		$page = trim($page);
	} else {
		// either we're using internal pretty urls or this is the default page.
		$page = '';
		if (isset($_SERVER['REQUEST_URI']) && !endswith($_SERVER['REQUEST_URI'], 'index.php')) {
			$matches = [];
			if (preg_match('/.*index\.php\/(.*?)$/', $_SERVER['REQUEST_URI'], $matches)) {
				// pretty urls... grab all the stuff after the index.php
				$page = trim($matches[1]);
			}
		}
	}
	if (!$page) {
		// use the default page id
		return SingleItem::ContentOperations()->GetDefaultContent();
	}

	// by here, if we're not assuming pretty urls of any sort and we
	// have a value... we're done.
	if ($config['url_rewriting'] == 'none') {
		return $page;
	}

	// some kind of pretty-url
	// strip GET params
	if (($tmp = strpos($page, '?')) !== false) {
		$page = substr($page, 0, $tmp);
	}

	// strip page extension
	if ($config['page_extension'] != '' && endswith($page, $config['page_extension'])) {
		$page = substr($page, 0, strlen($page) - strlen($config['page_extension']));
	}

	// some servers leave in the first / of a request sometimes, which will stop route-matching
	// so strip trailing and leading /
	$page = trim($page, '/');

	// check for a route that matches
	// NOTE: we handle content routing separately at this time.
	// it'd be cool if contents were just another mact.
	$route = RouteOperations::find_match($page);
	if ($route) {
		// is it a route to a page ?
		$to = (int)$route->get_content();
		if ($to == 0) {
			// nope, is it route to a module ?
			$to = $route->get_dest();
			if (!$to) {
				return $page; // error, route unusable
			}
			// setup some default parameters.
			$arr = ['module' => $to, 'id' => 'cntnt01', 'action' => 'defaulturl', 'inline' => 0];
			$tmp = $route->get_defaults();
			if ($tmp) {
				$arr = array_merge($tmp, $arr);
			}
			$arr = array_merge($arr, $route->get_results());

			// put a constructed mact into $_REQUEST for later processing.
			// this is essentially our translation from pretty URLs to non-pretty URLS.
			$_REQUEST['mact'] = $arr['module'] . ',' . $arr['id'] . ',' . $arr['action'] . ',' . $arr['inline'];

			// put other parameters (except numeric matches) into $_REQUEST.
			foreach ($arr as $key => $val) {
				switch ($key) {
					case 'module':
					case 'id':
					case 'action':
					case 'inline':
						break; //no need to repeat mact parameters
					default:
						if (!is_int($key)) {
							$_REQUEST[$arr['id'] . $key] = $val;
						}
				}
			}
			// get a decent returnid
			if ($arr['returnid']) {
				$page = (int) $arr['returnid'];
//				unset($arr['returnid']);
			} else {
				$page = SingleItem::ContentOperations()->GetDefaultContent();
			}
		} else {
			$page = $to;
		}
	} elseif // no route matched... assume it is an alias which begins after the last /
	  (($pos = strrpos($page, '/')) !== false) {
		$page = substr($page, $pos + 1);
	}

	return $page;
}

/**
 * Gets the given site preference
 * @since 0.6
 * @deprecated since 1.10 NOPE
 * @see AppParams::get()
 *
 * @param string $prefname The preference name
 * @param mixed  $defaultvalue Optional return-value if the preference does not exist. Default null
 * @return mixed
 */
function get_site_preference(string $prefname, $defaultvalue = null)
{
	return AppParams::get($prefname, $defaultvalue);
}

/**
 * Removes the given site preference.
 * @since 0.6
 *
 * @deprecated since 1.10 NOPE
 * @see AppParams::remove()
 *
 * @param string $prefname Preference name to remove
 * @param boolean $uselike  Optional flag, default false. Whether to remove all preferences that are LIKE the supplied name.
 */
function remove_site_preference(string $prefname, bool $uselike = false)
{
	AppParams::remove($prefname, $uselike);
}

/**
 * Sets the given site preference with the given value.
 * @since 0.6
 *
 * @deprecated since 1.10 NOPE
 * @see AppParams::set()
 *
 * @param string $prefname The preference name
 * @param mixed  $value The preference value (will be stored as a string)
 */
function set_site_preference(string $prefname, $value)
{
	AppParams::set($prefname, $value);
}

/**
 * Gets a specified module-parameter/preference value, without the module
 * being loaded, and without involving cached data.
 * Intended mainly for classes which interact async with modules.
 * @since 2.99
 *
 * @param string $modname The module name
 * @param string $parmname The property name
 * @param mixed  $defaultvalue The default value if the parameter is not recorded
 * @return mixed
 */
function get_module_param($modname, $parmname, $defaultvalue = '')
{
	$key = $modname.AppParams::NAMESPACER.$parmname;
	return AppParams::getraw($key, $defaultvalue);
}

/**
 * Sets a specified module-parameter value, without the module being loaded.
 * Intended mainly for classes which interact async with modules.
 *
 * @since 2.99
 *
 * @param string $modname The module name
 * @param string $parmname The property name
 * @param mixed  $value The datum to be recorded
 */
function set_module_param($modname, $parmname, $value)
{
	$key = $modname.AppParams::NAMESPACER.$parmname;
	AppParams::set($key, $value);
}

/**
 * Gets the secure parameter(s) query-string used in admin links.
 * @internal
 *
 * @param mixed $first since 2.99 Optional bool flag whether this is
 *  to be the first-used URL-parameter (default true) OR
 *  string URL to which the returned string will be appended
 * @return string
 */
function get_secure_param($first = true) : string
{
	if (isset($_SESSION[CMS_USER_KEY])) {
		if (is_string($first) && $first) {
			$pre = (strpos($first, '?') === false) ? '?' : '&';
		} else {
			$pre = ($first) ? '?' : '&';
		}
		$out = $pre.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
		if (!ini_get_boolean('session.use_cookies')) {
			//PHP constant SID is unreliable, we recreate it
			//encoding prob. not needed, but the session-vars used might change in future?
			$out .= '&'.rawurlencode(session_name()).'='.rawurlencode(session_id());
		}
		return $out;
	}
	return '';
}

/**
 * Gets the secure params in a form-friendly format i.e. content mimics
 *  application/x-www-form-urlencoded (not rawurlencode()'d)

 * @internal
 *
 * @return array
 */
function get_secure_param_array() : array
{
	$out = [CMS_SECURE_PARAM_NAME => $_SESSION[CMS_USER_KEY]];
	if (!ini_get_boolean('session.use_cookies')) {
		$out[urlencode(session_name())] = urlencode(session_id());
	}
	return $out;
}

/**
 * Return $value if it's set and the same basic type as $default.
 * Otherwise return $default. Note: this trim()'s $value if it's not numeric.
 * @deprecated since 2.99
 * @internal
 * @ignore
 *
 * @param mixed $value
 * @param mixed $default Optional default value to return. Default ''.
 * @param string $session_key Optional key for retrieving the default value from $_SESSION[]. Default ''
 * @return mixed
 */
function _get_value_with_default($value, $default = '', $session_key = '')
{
	if ($session_key != '') {
		if (isset($_SESSION['default_values'][$session_key])) {
			$default = $_SESSION['default_values'][$session_key];
		}
	}

	// set our return value to the default initially and overwrite with $value if we like it.
	$return_value = $default;

	if (isset($value)) {
		if (is_array($value)) {
			// $value is an array - validate each element.
			$return_value = [];
			foreach ($value as $element) {
				$return_value[] = _get_value_with_default($element, $default); // recurse
			}
		} else {
			if (is_numeric($default)) {
				if (is_numeric($value)) {
					$return_value = $value;
				}
			} else {
				$return_value = trim($value);
			}
		}
	}

	if ($session_key != '') {
		$_SESSION['default_values'][$session_key] = $return_value;
	}
	return $return_value;
}

/**
 * Retrieve a (scalar or array) value from the supplied $parameters array.
 * Returns $_SESSION['parameter_values'][$session_key] if $key is not in
 *  $parameters and $_SESSION['parameter_values'][$session_key] exists.
 * Returns $default if $key is not in $parameters and $session_key is not
 *  specified or $_SESSION['parameter_values'][$session_key] does not exist.
 * There is little point in using this func without a $session_key
 * Note: This function trim()'s string values.
 * @deprecated since 2.99 Do not rely on fallback to $_SESSION values.
 *
 * @param array $parameters
 * @param string $key The wanted member of $parameters
 * @param mixed $default Optional default value to return. Default ''.
 * @param string $session_key Optional key for retrieving the default value from $_SESSION[]. Default ''
 * @return mixed recorded value | $default
 */
function get_parameter_value(array $parameters, string $key, $default = '', string $session_key = '')
{
	assert(!CMS_DEPREC, new DeprecationNotice('php', 'like $parameters[$key] ?? $default'));

	if ($session_key) {
		if (isset($_SESSION['parameter_values'][$session_key])) {
			$default = $_SESSION['parameter_values'][$session_key];
		}
	}

	// set our return value to the default initially and overwrite with $parameters value if we like it.
	$return_value = $default;
	if (isset($parameters[$key])) {
		if (is_bool($default)) {
			// want a bool return_value
			if (isset($parameters[$key])) {
				$return_value = cms_to_bool((string)$parameters[$key]);
			}
		} elseif (is_numeric($default)) {
			// default value is a number, we only like $parameters[$key] if it's a number too.
			if (is_numeric($parameters[$key])) {
				$return_value = $parameters[$key] + 0;
			}
		} elseif (is_string($default)) {
			$return_value = trim($parameters[$key]);
		} elseif (is_array($parameters[$key])) {
			// $parameters[$key] is an array - validate each element.
			$return_value = [];
			foreach ($parameters[$key] as $element) {
				$return_value[] = _get_value_with_default($element, $default);
			}
		} else {
			$return_value = $parameters[$key];
		}
	}

	if ($session_key) {
		$_SESSION['parameter_values'][$session_key] = $return_value;
	}
	return $return_value;
}

/* * NOT YET
 * Adds a content-security-policy header for the current page, and returns a
 * security-policy nonce for use in on-page javascripts.
 * @since 2.99
 * @internal
 *
 * @return string
 */
/*function get_csp_token() : string
{
	static $nonce = null; // limit to once-per-request

	if ($nonce === null) {
		$nonce = Crypto::random_string(20, false, true);
		$js = <<<EOS
<meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'nonce-{$nonce}'">
EOS;
		add_page_headtext($js, false);
	}
	return $nonce;
}
*/
/**
 * Process a module-tag
 * This method is used by the {cms_module} plugin and to process {ModuleName} tags
 * @internal
 * @since 2.99 ModulePluginOperations::call_plugin_module() may be used instead
 *
 * @param array $params A hash of action-parameters
 * @param object $template A Smarty_Internal_Template object
 * @return string The module output string or an error message string or ''
 */
function cms_module_plugin(array $params, $template) : string
{
	return ModulePluginOperations::call_plugin_module($params, $template);
}

/**
 * Gets the url corresponding to a provided site-path
 * @since 2.99
 *
 * @param string $in The input path, absolute or relative
 * @param string $relative_to Optional absolute path which (relative) $in is relative to
 * @return string
 */
function cms_path_to_url(string $in, string $relative_to = '') : string
{
	$in = trim($in);
	if ($relative_to) {
		$in = realpath(cms_join_path($relative_to, $in));
		return str_replace([CMS_ROOT_PATH, DIRECTORY_SEPARATOR], [CMS_ROOT_URL, '/'], $in);
	} elseif (preg_match('~^ *(?:\/|\\\\|\w:\\\\|\w:\/)~', $in)) {
		// $in is absolute
		$in = realpath($in);
		return str_replace([CMS_ROOT_PATH, DIRECTORY_SEPARATOR], [CMS_ROOT_URL, '/'], $in);
	} else {
		return strtr($in, DIRECTORY_SEPARATOR, '/');
	}
}

/**
 * Gets the relative portion of a path
 * @since 2.2
 *
 * @param string $in The input path or file specification
 * @param string $relative_to The optional path to compute relative to.  If not supplied the cmsms root path will be used.
 * @return string The relative portion of the input string.
 */
function cms_relative_path(string $in, string $relative_to = null) : string
{
	$in = realpath(trim($in));
	if (!$relative_to) {
		$relative_to = CMS_ROOT_PATH;
	}
	$to = realpath(trim($relative_to));

	if ($in && $to && startswith($in, $to)) {
		return substr($in, strlen($to));
	}
	return '';
}

/**
 * Performs HTML entity conversion on the supplied value
 * Normally this is for mitigating risk (XSS) from a string to be displayed
 * in the browser
 * @deprecated since 2.99 Instead use CMSMS\entitize()
 *
 * @see CMSMS\entitize (which handles over 10000 values)
 * @see CMSMS\execSpecialize(), CMSMS\Database\Connection::escStr() (which handle execution risks)
 *
 * @param mixed  $val     The input variable string | null
 * @param int    $flags   Optional bit-flag(s) indicating how entitize() should handle quotes etc.
 *  Default 0, hence ENT_QUOTES | ENT_ENT_SUBSTITUTE | ENT_EXEC (custom) | preferred_lang().
 * @param string $charset Optional character set of $val. Default 'UTF-8'.
 *  If empty the system setting will be used.
 * @param bool   $convert_single_quotes Optional flag indicating whether
 *  single quotes should be converted to entities. Default false.
 * @return the converted string
 */
function cms_htmlentities($val, int $flags = 0, string $charset = 'UTF-8', bool $convert_single_quotes = false) : string
{
	assert(!CMS_DEPREC, new DeprecationNotice('function', 'CMSMS\entitize'));
	return entitize($val, $flags, $charset, $convert_single_quotes);
}

/**
 * Performs HTML entity reversion on the supplied value
 * Normally this is for reversing changes applied by CMSMS\entitize() or htmlentities(),
 * prior to displaying the value. Reversion to its 'real' content is then
 * needed before processing (for validation, interpretation, storage etc)
 * @deprecated since 2.99 Instead use CMSMS\de_entitize()
 *
 * @see CMSMS\de_entitize
 *
 * @param mixed $val     The input variable string | null
 * @param int   $flags   @since 2.99 Optional bit-flag(s) indicating how
 *  html_entity_decode() should handle quotes etc. Default 0, hence
 *  ENT_QUOTES | ENT_ENT_SUBSTITUTE | preferred_lang().
 * @param string $charset @since 2.99 Optional character set of $val.
 *  Default 'UTF-8'. If empty, the system setting will be used.
 * @return the converted string
 */
function cms_html_entity_decode($val, int $flags = 0, string $charset = 'UTF-8') : string
{
	assert(!CMS_DEPREC, new DeprecationNotice('function', 'CMSMS\de_entitize'));
	return de_entitize($val, $flags, $charset);
}

/**
 * A wrapper around move_uploaded_file() that attempts to ensure that
 * uploaded files have correct permissions, and don't contain malicious content.
 *
 * @param string $tmpfile The temporary file specification
 * @param string $destination The destination file specification
 * @return bool
 */
function cms_move_uploaded_file(string $tmpfile, string $destination) : bool
{
	// check e.g. image files for malicious content
	if ((new FileTypeHelper())->is_image($tmpfile)) {
		$p = file_get_contents($tmpfile); // TODO if compressed image?
		$s = execSpecialize($p);
		if ($s != $p) {
			//TODO report error or throw new Exception(lang(''))
			return false;
		}
	}
	if (@move_uploaded_file($tmpfile, $destination)) {
		return @chmod($destination, octdec(SingleItem::Config()['default_upload_permission']));
	} else {
		//TODO report error or throw new Exception(lang(''))
	}
	return false;
}

/**
 * Gets a UNIX UTC timestamp corresponding to the supplied (typically
 * database datetime formatted and timezoned) date/time string.
 * The supplied parameter is not validated, apart from ignoring a falsy value.
 * @since 2.99
 *
 * @param mixed $datetime normally a string reported by a query on a database datetime field
 * @param bool  $is_utc Optional flag whether $datetime is for the UTC timezone. Default false.
 * @return int Default 1 (not false)
 */
function cms_to_stamp($datetime, bool $is_utc = false) : int
{
	static $dt = null;
	static $offs = null;

	if ($datetime) {
		if ($dt === null) {
			$dt = new DateTime('@0', null);
		}
		if (!$is_utc) {
			if ($offs === null) {
				$dtz = new DateTimeZone(SingleItem::Config()['timezone']);
				$offs = timezone_offset_get($dtz, $dt);
			}
		}
		try {
			$dt->modify($datetime);
			if (!$is_utc) {
				return $dt->getTimestamp() - $offs;
			}
			return $dt->getTimestamp();
		} catch (Throwable $t) {
			// nothing here
		}
	}
	return 1; // anything not falsy
}

/**
 * Gets the, or the highest-versioned, installed jquery scripts and/or associated css
 * @since 2.99
 *
 * @return array of filepaths, keys per params: 'jqcore','jqmigrate','jqui','jquicss'
 */
function cms_installed_jquery(bool $core = true, bool $migrate = false, bool $ui = true, bool $uicss = true) : array
{
	$found = [];
	$allfiles = false;

	if ($core) {
		$fp = CMS_SCRIPTS_PATH.DIRECTORY_SEPARATOR.'jquery';
		$allfiles = scandir($fp);
		//the 'core' jquery files are named like jquery-*min.js
		$m = preg_grep('~^jquery\-\d[\d\.]+\d(\.min)?\.js$~', $allfiles);
		//find highest version
		$best = '0';
		$use = reset($m);
		foreach ($m as $file) {
			preg_match('~(\d[\d\.]+\d)~', $file, $matches);
			if (version_compare($best, $matches[1]) < 0) {
				$best = $matches[1];
				$use = $file;
			}
		}
		$found['jqcore'] = $fp.DIRECTORY_SEPARATOR.$use;
	}

	if ($migrate) {
		if (!$allfiles) {
			$fp = CMS_SCRIPTS_PATH.DIRECTORY_SEPARATOR.'jquery';
			$allfiles = scandir($fp);
		}
		$debug = constant('CMS_DEBUG');
		$m = preg_grep('~^jquery\-migrate\-\d[\d\.]+\d(\.min)?\.js$~', $allfiles);
		$best = '0';
		$use = reset($m);
		foreach ($m as $file) {
			preg_match('~(\d[\d\.]+\d)(\.min)?~', $file, $matches);
			if ($debug) {
				//prefer a non-min version, so that problems are logged
				if ($best === '0') {
					$best = $matches[1];
					$use = $file;
					$min = !empty($matches[2]); //$use is .min
				} elseif (version_compare($best, $matches[1]) < 0 || ($min && empty($matches[2]))) {
					$best = $matches[1];
					$use = $file;
					$min = !empty($matches[2]); //$use is .min
				}
			} elseif (version_compare($best, $matches[1]) < 0) {
				$best = $matches[1];
				$use = $file;
			}
		}
		$found['jqmigrate'] = $fp.DIRECTORY_SEPARATOR.$use;
	}

	$allfiles = false;

	if ($ui) {
		$fp = CMS_SCRIPTS_PATH.DIRECTORY_SEPARATOR.'jquery-ui';
		$allfiles = scandir($fp);
		$m = preg_grep('~^jquery\-ui\-\d[\d\.]+\d([\.\-]custom)?(\.min)?\.js$~', $allfiles);
		$best = '0';
		$use = reset($m);
		foreach ($m as $file) {
			preg_match('~(\d[\d\.]+\d)~', $file, $matches);
			if (version_compare($best, $matches[1]) < 0) {
				$best = $matches[1];
				$use = $file;
			}
		}
		$found['jqui'] = $fp.DIRECTORY_SEPARATOR.$use;
	}

	if ($uicss) {
		if (!$allfiles) {
			$fp = CMS_SCRIPTS_PATH.DIRECTORY_SEPARATOR.'jquery-ui';
			$allfiles = scandir($fp);
		}
		$m = preg_grep('~^jquery\-ui\-\d[\d\.]+\d([\.\-]custom)?(\.min)?\.css$~', $allfiles);
		$best = '0';
		$use = reset($m);
		foreach ($m as $file) {
			preg_match('~(\d[\d\.]+\d)~', $file, $matches);
			if (version_compare($best, $matches[1]) < 0) {
				$best = $matches[1];
				$use = $file;
			}
		}
		$found['jquicss'] = $fp.DIRECTORY_SEPARATOR.$use;
	}

	return $found;
}

/**
 * Gets content which will include wanted js (jQuery etc) and css in a
 * displayed page.
 * @since 1.10
 * @deprecated since 2.99
 * Instead, relevant content can be gathered via functions added to hook
 * 'AdminHeaderSetup' and/or 'AdminBottomSetup', or a corresponding tag
 *  e.g. {gather_content list='AdminHeaderSetup'}.
 * See also the ScriptsMerger class, for consolidating scripts into a single
 * download.
 */
function cms_get_jquery(string $exclude = '', bool $ssl = false, bool $cdn = false, string $append = '', string $custom_root = '', bool $include_css = true)
{
	assert(!CMS_DEPREC, new DeprecationNotice('Gather page content via hook function or smarty tag', ''));

	$incs = cms_installed_jquery(true, false, true, $include_css);
	if ($include_css) {
		$url1 = cms_path_to_url($incs['jquicss']);
		$s1 = <<<EOS
<link rel="stylesheet" type="text/css" href="{$url1}" />

EOS;
	} else {
		$s1 = '';
	}
	$url2 = cms_path_to_url($incs['jqcore']);
	$url3 = cms_path_to_url($incs['jqui']);
	$out = <<<EOS
<!-- default page inclusions -->{$s1}
<script type="text/javascript" src="{$url2}"></script>
<script type="text/javascript" src="{$url3}"></script>

EOS;
	return $out;
}

/**
 * @since 2.99
 * @internal
 * @ignore
 */
function get_best_file($places, $target, $ext, $as_url)
{
	$i = $p = 0;
	while (($p = stripos($target, 'min', $p)) !== false) {
		$i = $p;
		++$p;
	}
	if ($i > 0) {
		if (stripos($target, '.'.$ext, $i) && $target[$i + 3] == '.' && (($c = $target[$i - 1]) == '.' || $c == '-' || $c == '_')) {
			$base = substr($target, 0, $i - 1); //strip .min.type-suffix
		} else {
			$i = 0;
		}
	}
	if ($i == 0 && ($p = stripos($target, '.'.$ext)) !== false) {
		$base = substr($target, 0, $p); //strip type-suffix
	}
	$patn = '~^'.addcslashes($base, '.-').'([.-](\d[\d\.]*))?([.-]min)?\.'.$ext.'$~i';
	foreach ($places as $base_path) {
		$allfiles = scandir($base_path);
		if ($allfiles) {
			$files = preg_grep($patn, $allfiles);
			if ($files) {
				if (count($files) > 1) {
//					$best = ''
					foreach ($files as $target) {
						preg_match($patn, $target, $matches);
						if (!empty($matches[2])) {
							break; //use the min TODO check versions too
						} elseif (!empty($matches[1])) {
							//TODO a candidate, but try for later-version/min
						} else {
							//TODO a candidate, but try for min
						}
					}
//					$target = $best;
				} else {
					$target = reset($files);
				}
				$out = $base_path.DIRECTORY_SEPARATOR.$target;
				if ($as_url) {
					return cms_path_to_url($out);
				}
				return $out;
			}
		}
	}
	return '';
}

/**
 * Gets the filepath or URL of a wanted script file, if found in any of the
 * standard locations for such files (or any other provided location).
 * Intended mainly for non-jQuery scripts, but it will try to find those those too.
 * @since 2.99
 *
 * @param string $filename absolute or relative filepath or (base)name of the
 *  wanted file, optionally including [.-]min before the .js extension
 *  If the name includes a version, that will be taken into account.
 *  Otherwise, the first-found version will be used. Min-format preferred over non-min.
 * @param bool $as_url optional flag, whether to return URL or filepath. Default true.
 * @param mixed $custompaths string | string[] optional 'non-standard' directory-path(s) to include (first) in the search
 * @return mixed string absolute filepath|URL|null
 */
function cms_get_script(string $filename, bool $as_url = true, $custompaths = '')
{
	$target = basename($filename);
	if ($target == $filename) {
		$places = [
		 CMS_SCRIPTS_PATH,
		 CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'js',
		 CMS_ADMIN_PATH.DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'js',
		 CMS_SCRIPTS_PATH.DIRECTORY_SEPARATOR.'jquery',
		 CMS_SCRIPTS_PATH.DIRECTORY_SEPARATOR.'jquery-ui',
		];
	} elseif (preg_match('~^ *(?:\/|\\\\|\w:\\\\|\w:\/)~', $filename)) {
		// $filename is absolute
		$places = [dirname($filename)];
	} else {
		// $filename is relative, try to find it
		//TODO if relevant, support somewhere module-relative
		//TODO partial path-intersection too, any separators
		$base_path = ltrim(dirname($filename), ' \/');
		$places = [
		 $base_path,
		 CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'js',
		 CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'assets',
		 SingleItem::Config()['uploads_path'],
		 CMS_ADMIN_PATH.DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'js',
		 CMS_ROOT_PATH,
		];
	}

	if ($custompaths) {
		if (is_array($custompaths)) {
			$places = array_merge($custompaths, $places);
		} else {
			array_unshift($places, $custompaths);
		}
		$places = array_unique($places);
	}

	return get_best_file($places, $target, 'js', $as_url);
}

/**
 * Sends 'Content-Type' header.
 * Intended mainly for admin pages, but not restricted to such.
 * @since 2.99 (migrated from include.php)
 *
 * @param string $media_type optional page-content MIME-type. Default 'text/html'.
 *  Use 'application/xml' to enable XML’s strict parsing rules, <![CDATA[…]]> sections,
 *  or elements that aren't from the HTML|SVG|MathML namespaces.
 * @param string $charset optional default ''. Hence system default value.
 */
function cms_admin_sendheaders($media_type = 'text/html', $charset = '')
{
	if (!$charset) {
		$charset = NlsOperations::get_encoding();
	}
	header("Content-Type: $media_type; charset=$charset");
}

/**
 * Gets the filepath or URL of a wanted css file, if found in any of the
 * standard locations for such files (or any other provided location).
 * Intended mainly for non-jQuery styles, but it will try to find those those too.
 * @since 2.99
 *
 * @param string $filename absolute or relative filepath or (base)name of the
 *  wanted file, optionally including [.-]min before the .css extension
 *  If the name includes a version, that will be taken into account.
 *  Otherwise, the first-found version will be used. Min-format preferred over non-min.
 * @param bool $as_url optional flag, whether to return URL or filepath. Default true.
 * @param mixed $custompaths string | string[] optional 'non-standard' directory-path(s) to include (first) in the search
 * @return mixed string absolute filepath|URL|null
 */
function cms_get_css(string $filename, bool $as_url = true, $custompaths = '')
{
	$target = basename($filename);
	if ($target == $filename) {
		$places = [
		 CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'styles',
		 CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'styles',
		 CMS_ADMIN_PATH.DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'styles',
		 CMS_SCRIPTS_PATH.DIRECTORY_SEPARATOR.'jquery',
		 CMS_SCRIPTS_PATH.DIRECTORY_SEPARATOR.'jquery-ui',
		];
	} elseif (preg_match('~^ *(?:\/|\\\\|\w:\\\\|\w:\/)~', $filename)) {
		// $filename is absolute
		$places = [dirname($filename)];
	} else {
		// $filename is relative, try to find it
		//TODO if relevant, support somewhere module-relative
		//TODO partial path-intersection too, any separators
		$base_path = ltrim(dirname($filename), ' \/');
		$places = [
		 $base_path,
		 CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'styles',
		 CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'assets',
		 SingleItem::Config()['uploads_path'],
		 CMS_ADMIN_PATH.DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'styles',
		 CMS_ROOT_PATH,
		];
	}

	if ($custompaths) {
		if (is_array($custompaths)) {
			$places = array_merge($custompaths, $places);
		} else {
			array_unshift($places, $custompaths);
		}
		$places = array_unique($places);
	}

	return get_best_file($places, $target, 'css', $as_url);
}

/**
 * Creates a text area element
 *
 * @internal
 * @deprecated since 2.99 instead use CMSMS\FormUtils::create_textarea()
 *
 * @param boolean $enablewysiwyg Whether to apply a richtext-editor.
 *   If false, and forcewysiwyg is not empty, then a syntax-highlight editor is applied.
 * @param string  $value The contents of the text area
 * @param string  $name The name of the text area
 * @param string  $class An optional class name
 * @param string  $id An optional ID (HTML ID) value
 * @param string  $encoding The optional encoding
 * @param string  $stylesheet Optional style information
 * @param integer $width Width (the number of columns) (CSS can and will override this)
 * @param integer $height Height (the number of rows) (CSS can and will override this)
 * @param string  $forcewysiwyg Optional name of the richtext- or syntax-highligh-editor to use.
 *   If empty, preferences indicate which editor to use.
 * @param string  $wantedsyntax Optional name of the language/syntax used.
 *   If non-empty it indicates that a syntax highlighter will be used.
 * @param string  $addtext Optional additional text to include in the textarea tag
 * @return string
 */
function create_textarea(
	bool $enablewysiwyg,
	string $value,
	string $name,
	string $class = '',
	string $id = '',
	string $encoding = '',
	string $stylesheet = '',
	int $width = 80,
	int $height = 15,
	string $forcewysiwyg = '',
	string $wantedsyntax = '',
	string $addtext = ''
) {
	assert(!CMS_DEPREC, new DeprecationNotice('method', 'FormUtils::create_textarea'));
	$parms = func_get_args() + [
		'height' => 15,
		'width' => 80,
	];
	return FormUtils::create_textarea($parms);
}

/**
 * Creates a dropdown/select html element containing a list of files that
 * match certain conditions
 *
 * @internal
 * @param string The name (and id) for the select element.
 * @param string The directory name to search.
 * @param string The name of the file to be selected
 * @param string A comma separated series of file-extensions that should be displayed in the list
 * @param string An optional string with which to prefix each value in the output. Default ''
 * @param boolean n An optional flag indicating whether 'none' should be an allowed option. Default false.
 * @param string An optional string containing additional parameters for the dropdown element. Default ''
 * @param string An optional string to use as name-prefix when filtering files. Default ''
 * @param boolean An optional flag indicating whether the files matching
 *  the extension and the prefix should be included or excluded from the result set. Default true.
 * @param boolean An optional flag indicating whether the output should be sorted. Default false.
 * @return string maybe empty
 */
function create_file_dropdown(
	string $name,
	string $dir,
	string $value,
	string $allowed_extensions,
	string $optprefix = '',
	bool $allownone = false,
	string $extratext = '',
	string $fileprefix = '',
	bool $excludefiles = true,
	bool $sortresults = false) : string
{
	$files = get_matching_files($dir, $allowed_extensions, true, true, $fileprefix, $excludefiles);
	if ($files === false) {
		return '';
	}
	if ($sortresults) {
		natcasesort($files);
	}
/*	$out = "<select name=\"{$name}\" id=\"{$name}\" {$extratext}>\n";
	if ($allownone) {
		$txt = '';
		if (empty($value)) {
			$txt = 'selected="selected"';
		}
		$out .= "<option value=\"-1\" $txt>--- ".lang('none')." ---</option>\n";
	}
	foreach ($files as $file) {
		$txt = '';
		$opt = $file;
		if (!empty($optprefix)) {
			$opt = $optprefix.'/'.$file;
		}
		if ($opt == $value) {
			$txt = 'selected="selected"';
		}
		$out .= "<option value=\"{$opt}\" {$txt}>{$file}</option>\n";
	}
	$out .= '</select>';
*/
	$opts = [];
	if ($allownone) {
		$opts['--- '.lang('none').' ---'] = -1;
	}
	foreach ($files as $file) {
		if ($optprefix) {
			$opt = $optprefix.'/'.$file;
		} else {
			$opt = $file;
		}
		$opts[$file] = $opt;
	}
	$out = FormUtils::create_select([ // DEBUG
		'type' => 'drop',
		'name' => $name,
		'htmlid' => $name,
		'getid' => '',
		'multiple' => false,
		'options' => $opts,
		'selectedvalue' => $value,
		'addtext' => $extratext,
	]);
	return $out;
}

/**
 * Gets page content (js, css) for initialization of and use by the configured
 * 'rich-text' (a.k.a. wysiwyg) text-editor.
 * @since 2.99
 *
 * @param array $params  Configuration details. Recognized members are:
 *  string 'editor' name of editor to use. Default '' hence recorded preference.
 *  bool   'edit'   whether the content is editable. Default false (i.e. just for display)
 *  string 'handle' name of the js variable to be used for the created editor. Default 'editor'
 *  string 'htmlid' id of the page-element whose content is to be edited. Default 'edit_area'.
 *  string 'theme'  override for the normal editor theme/style.  Default ''
 *  string 'workid' id of a div to be created (by some editors) to process
 *    the content of the htmlid-element. (As always, avoid conflict with tab-name divs). Default 'edit_work'
 * @return array up to 2 members, those being 'head' and/or 'foot', or perhaps [1] or []
 */
function get_richeditor_setup(array $params) : array
{
	if (SingleItem::App()->is_frontend_request()) {
		$val = AppParams::get('frontendwysiwyg'); //module name
	} else {
		$userid = get_userid();
		$val = UserParams::get_for_user($userid, 'wysiwyg');
		if (!$val) {
			$val = AppParams::get('wysiwyg');
		}
	}
	if ($val) {
		$parts = explode('::', $val, 2);
		$modname = $parts[0] ?? '';
		if ($modname) {
			$mod = Utils::get_module($modname);
			if ($mod) {
				//$params[] will be ignored by modules without relevant capability
				if (empty($params['editor'])) {
					$params['editor'] = $parts[1] ?? $modname;
				}
				if (empty($params['theme'])) {
					$val = UserParams::get_for_user($userid, 'wysiwyg_theme');
					if (!$val) {
						$val = AppParams::get('wysiwyg_theme');
					}
					if ($val) {
						$params['theme'] = $val;
					}
				}
				$out = $mod->WYSIWYGGenerateHeader('', '', $params);
				if ($out) {
					return ['head' => $out];
				}
				return [1]; //anything not falsy
			}
		}
	}
	return [];
}

/**
 * Gets page content (css, js) for initialization of and use by the configured
 * 'advanced' (a.k.a. syntax-highlight) text-editor. Assumes that is for admin usage only.
 * @since 2.99
 *
 * @param array $params  Configuration details. Recognized members are:
 *  string 'editor' name of editor to use. Default '' hence recorded preference.
 *  bool   'edit'   whether the content is editable. Default false (i.e. just for display)
 *  string 'handle' name of the js variable to be used for the created editor. Default 'editor'
 *  string 'htmlclass' class of the page-element(s) whose content is to be edited.
 *   An alternative to 'htmlid' Default ''.
 *  string 'htmlid' id of the page-element whose content is to be edited.
 *    (As always, avoid conflict with tab-name divs). Default 'edit_area'.
 *  string 'workid' id of a div to be created (by some editors) to process the
 *    content of the htmlid-element if the latter is a textarea. Default 'edit_work'
 *  string 'typer'  content-type identifier, an absolute filepath or filename or
 *    at least an extension or pseudo (like 'smarty'). Default ''
 *  string 'theme' name to override the recorded theme/style for the editor. Default ''
 * @return array up to 2 members, those being 'head' and/or 'foot', or perhaps [1] or []
 */
function get_syntaxeditor_setup(array $params) : array
{
	if (SingleItem::App()->is_frontend_request()) {
		return [];
	}

	$userid = get_userid();
	$val = UserParams::get_for_user($userid, 'syntaxhighlighter');
	if (!$val) {
		$val = AppParams::get('syntaxhighlighter');
	}
	if ($val) {
		$parts = explode('::', $val, 2);
		$modname = $parts[0] ?? '';
		if ($modname) {
			$mod = Utils::get_module($modname);
			if ($mod) {
				//$params[] will be handled only by modules with relevant capability
				if (empty($params['editor'])) {
					$params['editor'] = $parts[1] ?? $parts[0];
				}
				if (empty($params['theme'])) {
					$val = UserParams::get_for_user($userid, 'syntax_theme');
					if (!$val) {
						$val = AppParams::get('syntax_theme');
					}
					if ($val) {
						$params['theme'] = $val;
					}
				}
				$out = $mod->SyntaxGenerateHeader($params);
				if ($out) {
					return ['head' => $out];
				}
				return [1]; //anything not falsy
			}
		}
	}
	return [];
}

/**
 * Outputs a backtrace into the generated log file.
 *
 * @see debug_to_log, debug_bt
 */
function debug_bt_to_log()
{
	if (SingleItem::Config()['debug_to_log'] ||
		(function_exists('get_userid') && get_userid(false))) {
		$bt = debug_backtrace();
		$file = $bt[0]['file'];
		$line = $bt[0]['line'];

		$out = ["Backtrace in $file on line $line"];

		$bt = array_reverse($bt);
		foreach ($bt as $trace) {
			if ($trace['function'] == 'debug_bt_to_log') {
				continue;
			}

			$file = '';
			$line = '';
			if (isset($trace['file'])) {
				$file = $trace['file'];
				$line = $trace['line'];
			}
			$function = $trace['function'];
			$str = "$function";
			if ($file) {
				$str .= " at $file:$line";
			}
			$out[] = $str;
		}

		$filename = TMP_CACHE_LOCATION . DIRECTORY_SEPARATOR. 'debug.log';
		foreach ($out as $txt) {
			error_log($txt . "\n", 3, $filename);
		}
	}
}

/**
 * Displays (echo) stack trace as human-readable lines
 *
 * This method uses echo.
 * @param string $title since 2.99 Optional title for (verbatim) display
 */
function stack_trace(string $title = '')
{
	if ($title) {
		echo $title . "\n";
	}

	$bt = debug_backtrace();
	foreach ($bt as $elem) {
		if ($elem['function'] == 'stack_trace') {
			continue;
		}
		if (isset($elem['file'])) {
			echo $elem['file'].':'.$elem['line'].' - '.$elem['function'].'<br />';
		} else {
			echo ' - '.$elem['function'].'<br />';
		}
	}
}

/**
 * Generates a backtrace in a readable format.
 *
 * This method uses echo.
 */
function debug_bt()
{
	$bt = debug_backtrace();
	$file = $bt[0]['file'];
	$line = $bt[0]['line'];

	echo "\n\n<p><b>Backtrace in $file on line $line</b></p>\n";

	$bt = array_reverse($bt);
	echo "<pre><dl>\n";
	foreach ($bt as $trace) {
		$file = $trace['file'];
		$line = $trace['line'];
		$function = $trace['function'];
		$args = implode(',', $trace['args']);
		echo "
<dt><b>$function</b>($args) </dt>
<dd>$file on line $line</dd>
";
	}
	echo "</dl></pre>\n";
}

/**
* Debug function to display $var nicely in html.
*
* @param mixed $var The data to display
* @param string $title (optional) title for the output.  If null memory information is output.
* @param bool $echo_to_screen (optional) Flag indicating whether the output should be echoed to the screen or returned.
* @param bool $use_html (optional) flag indicating whether html or text should be used in the output.
* @param bool $showtitle (optional) flag indicating whether the title field should be displayed in the output.
* @return string
*/
function debug_display($var, string $title = '', bool $echo_to_screen = true, bool $use_html = true, bool $showtitle = true) : string
{
	global $starttime, $orig_memory;

	if (!$starttime) {
		$starttime = microtime();
	}

	ob_start();

	if ($showtitle) {
		$titleText = microtime_diff($starttime, microtime()) . ' S since request-start';
		if (function_exists('memory_get_usage')) {
			$net = memory_get_usage() - $orig_memory;
			$titleText .= ', memory usage: net '.$net;
		} else {
			$net = false;
		}

		$memory_peak = (function_exists('memory_get_peak_usage') ? memory_get_peak_usage() : '');
		if ($memory_peak) {
			if ($net === false) {
				$titleText .= ', memory usage: peak '.$memory_peak;
			} else {
				$titleText .= ', peak '.$memory_peak;
			}
		}

		if ($use_html) {
			echo "<div><b>$titleText</b>\n";
		} else {
			echo "$titleText\n";
		}
	}

	if ($title || $var || is_numeric($var)) {
		if ($use_html) {
			echo '<pre>';
		}
		if ($title) {
			echo $title . "\n";
		}
		if (is_array($var)) {
			echo 'Number of elements: ' . count($var) . "\n";
			print_r($var);
		} elseif (is_object($var)) {
			print_r($var);
		} elseif (is_string($var)) {
			if ($use_html) {
				print_r(htmlentities(str_replace("\t", '  ', $var))); // OR CMSMS\entitize ?
			} else {
				print_r($var);
			}
		} elseif (is_bool($var)) {
			echo ($var) ? 'true' : 'false';
		} elseif ($var || is_numeric($var)) {
			print_r($var);
		}
		if ($use_html) {
			echo '</pre>';
		}
	}
	if ($use_html) {
		echo "</div>\n";
	}

	$out = ob_get_clean();

	if ($echo_to_screen) {
		echo $out;
	}
	return $out;
}

/**
 * Displays $var nicely only if $config["debug"] is set.
 *
 * @param mixed $var
 * @param string $title
 */
function debug_output($var, string $title = '')
{
	if (SingleItem::Config()['debug']) {
		debug_display($var, $title, true);
	}
}

/**
 * Debug function to output formatted debug information about a variable
 * to a log file.
 *
 * @param mixed $var    data to display
 * @param string $title optional logfile title
 * @param string $filename optional filepath of file to record the message
 */
function debug_to_log($var, string $title = '', string $filename = '')
{
	if (SingleItem::Config()['debug_to_log'] ||
		(function_exists('get_userid') && get_userid(false))) {
		if ($filename == '') {
			$filename = TMP_CACHE_LOCATION . DIRECTORY_SEPARATOR. 'debug.log';
		}
		if (is_file($filename)) {
			$ttl = AppParams::get('auto_clear_cache_age', 0);
			if ($ttl > 0) {
				$secs = $ttl * 86400;
				$mt = filemtime($filename);
				if ($mt < (time() - $secs)) {
					file_put_contents($filename, '');
				}
			}
		}
		$errlines = explode("\n", debug_display($var, $title, false, false, true));
		foreach ($errlines as $txt) {
			error_log($txt . "\n", 3, $filename);
		}
	}
}

/**
 * Adds $var to the global errors array if $config['debug'] is in effect.
 *
 * @param mixed $var
 * @param string $title
 */
function debug_buffer($var, string $title = '')
{
	if (constant('CMS_DEBUG')) {
		add_debug_message(debug_display($var, $title, false, true));
	}
}

/**
 * @ignore
 * @since 0.3
 * @deprecated since 2.99 instead use CMSMS\log_info()
 * @see LogOperations::info()
 */
function audit($itemid, string $subject, string $msg = '')
{
	assert(!CMS_DEPREC, new DeprecationNotice('function', 'CMSMS\log_info()'));
	SingleItem::LogOperations()->info($msg, $subject, $itemid);
}

/**
 * Chmod $path, and if it's a directory, all files and folders in it and descendants.
 * @deprecated since 2.99 instead use recursive_chmod()
 * @see recursive_chmod()
 */
function chmod_r(string $path, int $mode) : bool
{
	assert(!CMS_DEPREC, new DeprecationNotice('function', 'recursive_chmod()'));
	return recursive_chmod($path, $mode, 0);
}

/**
 * Munge a value to support verbatim inclusion of that value inside [x]html
 * elements, and also to minimally hinder XSS and other nasty stuff when displayed.
 * This has been applied in CMSMS to some received data prior to its storage
 * and subsequent display without escaping. NOT a hugely attractive practice.
 * This function does nothing for SQL-injection mitigation.
 *
 * @internal
 * @deprecated since 2.99 Instead use CMSMS\sanitizeVal() for inputs,
 *  CMSMS\specialize() or CMSMS\entitize() for outputs,
 *   maybe CMSMS\Database\Connection::escStr()
 * @param mixed $val input value
 * @return string
 */
function cleanValue($val)
{
	assert(!CMS_DEPREC, new DeprecationNotice('function', 'CMSMS\specialize'));
	return specialize((string)$val, 0, '', true);
}

} // global namespace

namespace CMSMS {

use CMSMS\AppParams;
use CMSMS\AppState;
use CMSMS\AutoCookieOperations;
use CMSMS\Events;
use CMSMS\PageLoader;
use CMSMS\ScriptsMerger;
use CMSMS\SingleItem;
use CMSMS\StylesMerger;
use CMSMS\Url;
use Throwable;
use const CMS_SCHEMA_VERSION;
use function CMSMS\de_specialize;
use function CMSMS\de_specialize_array;
use function CMSMS\execSpecialize;
use function CMSMS\get_entparms;
use function CMSMS\preferred_lang;
use function CMSMS\specialize_array;

static $deflang = 0;
static $defenc = '';
// custom bitflag to trigger execSpecialize() during CMSMS\entitize() and CMSMS\specialize()
define('ENT_EXEC', 2 << 15); //something compatible with PHP's ENT_* enum values


/**
 * @ignore
 * @since 02.99
 * @see LogOperations::info()
 */
function log_info($itemid, string $subject, string $msg = '')
{
	SingleItem::LogOperations()->info($msg, $subject, $itemid);
}

/**
 * @ignore
 * @since 2.99
 * @see LogOperations::notice()
 */
function log_notice(string $msg, string $subject = '')
{
	SingleItem::LogOperations()->notice($msg, $subject);
}

/**
 * @ignore
 * @since 2.99
 * @see LogOperations::warning()
 */
function log_warning(string $msg, string $subject = '')
{
	SingleItem::LogOperations()->warning($msg, $subject);
}

/**
 * @ignore
 * @since 2.99
 * @see LogOperations::error()
 */
function log_error(string $msg, string $subject = '')
{
	SingleItem::LogOperations()->error($msg, $subject);
}

/**
 * Add a dump-message
 * @since 2.99
 * @internal
 *
 * @param string $str The error message
 */
function add_debug_message(string $str)
{
	SingleItem::add('App.DumpMessages', $str);
}

/**
 * Return the accumulated dump-messages
 * @since 2.99
 * @internal
 *
 * @return array, maybe empty
 */
function get_debug_messages() : array
{
	return SingleItem::get('App.DumpMessages') ?? [];
}

/**
 * A shutdown function: disconnect from the database
 * @since 2.99
 *
 * @internal
 */
function dbshutdown()
{
	SingleItem::Db()->Close();
}

/**
 * Gets PHP enum corresponding to the configured 'content_language' i.e. the
 * preferred language/syntax for page-content
 * @since 2.99
 *
 * @return PHP enum value
 */
function preferred_lang() : int
{
	$val = str_toupper(SingleItem::Config()['content_language']);
	switch ($val) {
		case 'HTML5':
			return ENT_HTML5;
		case 'HTML':
			return ENT_HTML401; //a.k.a. 0
		case 'NONE':
			return 0;
		default:
			return ENT_XHTML;
	}
}

/**
 * Tailors parameters for entity conversion
 * @internal
 * @since 2.99
 *
 * @param int    $flags   Bit-flag(s) indicating how htmlentities() etc should handle quotes etc.
 *  0 is treated as ENT_QUOTES | ENT_ENT_SUBSTITUTE | ENT_EXEC (custom) | preferred_lang().
 * @param string $charset Character set of processed string
 * @param bool   $convert_single_quotes Flag indicating whether single quotes
 *  should also be converted to/from entities
 * @return 2-member array [0] = ENT* bitflags, [1] = characters' encoding
 */
function get_entparms(int $flags, string $charset, bool $convert_single_quotes) : array
{
	global $deflang, $defenc;

	if ($flags === 0) {
		$flags = ($convert_single_quotes) ? ENT_QUOTES : ENT_COMPAT;
		$flags |= ENT_SUBSTITUTE;
	}
	if ($flags & (ENT_HTML5 | ENT_XHTML | ENT_HTML401) == 0) {
		if ($deflang === 0) {
			$deflang = preferred_lang();
		}
		$flags |= $deflang;
	}
	if ($convert_single_quotes) {
		$flags &= ~(ENT_COMPAT | ENT_NOQUOTES);
	}

	if (!$charset) {
		if ($defenc === '') {
			$defenc = NlsOperations::get_encoding();
		}
		$charset = $defenc;
	}
	return [$flags, $charset];
}

/**
 * Performs HTML entity conversion on the supplied value.
 * Normally this is for mitigating risk (XSS) from a string to be displayed
 * in the browser
 * @since 2.99
 * @see htmlentities (which handles over 10000 values)
 * @see CMSMS\execSpecialize() (which handles execution risks)
 *
 * @param mixed  $val     The input variable string | null
 * @param int    $flags   Optional bit-flag(s) indicating how htmlentities() should handle quotes etc.
 *  Default 0, hence ENT_QUOTES | ENT_ENT_SUBSTITUTE | ENT_EXEC (custom) | preferred_lang().
 * @param string $charset Optional character set of $val. Default 'UTF-8'.
 *  If empty the system setting will be used.
 * @param bool   $convert_single_quotes Optional flag indicating whether
 *  single quotes should be converted to entities. Default false.
 * @return the converted string
 */
function entitize($val, int $flags = 0, string $charset = 'UTF-8', bool $convert_single_quotes = false) : string
{
	if ($val === '' || $val === null) {
		return '';
	}

	if ($flags === 0 || $flags & ENT_EXEC) {
		// munge risky-bits
		$val = execSpecialize($val);
		$flags &= ~ENT_EXEC;
	}

	list($flags, $charset) = get_entparms($flags, $charset, $convert_single_quotes);
	return \htmlentities($val, $flags, $charset, false);
}

/**
 * Performs HTML entity reversion on the supplied value
 * Normally this is for reversing changes applied by CMSMS\entitize() or htmlentities(),
 * prior to displaying the value. Reversion to its 'real' content is then
 * needed before processing (for validation, interpretation, storage etc)
 * @since 2.99
 * @see html_entity_decode
 *
 * @param mixed $val     The input variable string|null
 * @param int   $flags   @since 2.99 Optional bit-flag(s) indicating how
 *  html_entity_decode() should handle quotes etc. Default 0, hence
 *  ENT_QUOTES | preferred_lang().
 * @param string $charset @since 2.99 Optional character set of $val. Default 'UTF-8'.
 *  If empty, the system setting will be used.
 * @return the converted string
 */
function de_entitize($val, int $flags = 0, string $charset = 'UTF-8') : string
{
	if ($val === '' || $val === null) {
		return '';
	}

	list($flags, $charset) = get_entparms($flags, $charset, true);
	return \html_entity_decode($val, $flags, $charset);
}

/**
 * Convert some chars (& " ' < >), if they exist in the supplied value,
 * to HTML entities. This preserves those characters' meaning without
 * disturbing page elements|layout displayed in the browser. It is also
 * for mitigating XSS risk.
 * @since 2.99
 * @see htmlspecialchars
 * @see CMSMS\execSpecialize() (which handles execution risks)
 *
 * @param mixed  $val     Value to be processed scalar|array|null
 * @param int    $flags   Optional bit-flag(s) indicating how htmlspecialchars()
 *  should handle quotes etc. Default 0, hence
 *  ENT_QUOTES | ENT_ENT_SUBSTITUTE | ENT_EXEC (custom) | preferred_lang().
 * @param string $charset Optional character set of $val. Default 'UTF-8'.
 *  If empty the system setting will be used.
 * @param bool   $convert_single_quotes Optional flag indicating whether
 *  single quotes should be converted to entities. Default false.
 * @return mixed string | array the converted $val
 */
function specialize($val, int $flags = 0, string $charset = 'UTF-8', bool $convert_single_quotes = false)
{
	if ($val === '' || $val === null) {
		return '';
	}
	if (!is_array($val)) {
		if ($flags === 0 || $flags & ENT_EXEC) {
			// munge risky-bits
			$val = execSpecialize($val);
			$flags &= ~ENT_EXEC;
		}

		list($flags, $charset) = get_entparms($flags, $charset, $convert_single_quotes);
		return \htmlspecialchars($val, $flags, $charset, false);
	}

	specialize_array($val, $flags, $charset, $convert_single_quotes);
	return $val;
}

/**
 * Performs in-place HTML special chars conversion on string-values and
 * sub-array-values in the specified array
 * @since 2.99
 * @see specialize
 *
 * @param array $arr   The inputs array, often $_POST etc
 * @param int    $flags   Optional bit-flag(s) indicating how htmlspecialchars()
 *  should handle quotes etc. Default 0, hence
 *  ENT_QUOTES | ENT_ENT_SUBSTITUTE | ENT_EXEC (custom) | preferred_lang().
 * @param string $charset Optional character set of $val. Default 'UTF-8'.
 *  If empty the system setting will be used.
 * @param bool   $convert_single_quotes Optional flag indicating whether
 *  single quotes should be converted to entities. Default false.
 */
function specialize_array(array &$arr, int $flags = 0, string $charset = 'UTF-8', bool $convert_single_quotes = false)
{
	foreach ($arr as &$val) {
		if (is_string($val && !is_numeric($val))) {
			$val = specialize($val, $flags, $charset, $convert_single_quotes);
		} elseif (is_array($val)) {
			specialize_array($val, $flags, $charset, $convert_single_quotes); // recurse
		}
	}
	unset($val);
}

/**
 * Performs HTML special chars reversion on the supplied value
 * Normally this is for reversing changes applied by CMSMS\specialize()
 * or htmlspecialchars() prior to displaying the value. Reversion to
 * its 'real' content is then needed before processing (for validation,
 * interpretation, storage etc)
 * @since 2.99
 * @see htmlspecialchars_decode
 *
 * @param mixed  $val  Value to be processed scalar|array|null
 * @param int   $flags Optional bit-flag(s) indicating how
 *  html_entity_decode() should handle quotes etc. Default 0, hence
 *  ENT_QUOTES | preferred_lang().
 * @return mixed string | array the converted $val
 */
function de_specialize($val, int $flags = 0)
{
	if ($val === '' || $val === null) {
		return '';
	}
	if (!is_array($val)) {
		if ($flags === 0 || $flags & ENT_EXEC) {
			// reverse execSpecialize() i.e. de-munge risky-bits
			$val = preg_replace_callback('/&#(\d+);/', function($matches) {
				return chr($matches[1]);
			}, $val);
			$flags &= ~ENT_EXEC;
		}

		global $defenc;

		list($flags,) = get_entparms($flags, $defenc, true);
		return \htmlspecialchars_decode($val, $flags);
	}

	de_specialize_array($val, $flags);
	return $val;
}

/**
 * Performs in-place HTML special chars reversion on string-values and
 * sub-array-values in the specified array
 * @since 2.99
 * @see de_specialize
 *
 * @param array $arr   The inputs array, often $_POST etc
 * @param int   $flags Optional bit-flag(s) indicating how html_entity_decode() should handle quotes etc.
 *  Default 0, hence ENT_QUOTES | preferred_lang().
 */
function de_specialize_array(array &$arr, int $flags = 0)
{
	foreach ($arr as &$val) {
		if (is_string($val && !is_numeric($val))) {
			$val = de_specialize($val, $flags);
		} elseif (is_array($val)) {
			de_specialize_array($val, $flags); // recurse
		}
	}
	unset($val);
}

/**
 * Cleanup, and if it's risky, munge, the 'path' component of the supplied URL.
 * Note: this will disable trusted as well as untrusted 'scriptish' URL's,
 * so apply with discretion!
 * @since 2.99
 *
 * @param string $url
 * @return string
 */
function urlSpecialize(string $url) : string
{
	$url_ob = new Url(de_entitize($url));
	$p = $url_ob->get_path();
	if (strpos($p, 'base64') !== false) {
		$parts = explode(',', $p);
		foreach ($parts as &$s) {
			if (strpos($s, 'base64') === false) {
				$t = base64_decode($s);
				if ($t) {
					$q = execSpecialize($t);
					if ($q != $t) {
						$s = $t;
					}
				}
			}
		}
		unset($s);
		$q = implode(',', $parts);
		if ($p != $q) {
			// TODO maybe these should handle embedded whitespace
			$url_ob->set_path(str_replace(['/html', 'image/', ';base64', 'base64'], ['/plain', 'text/plain;', '', ''], $q));
		}
	} elseif (strpos($p, '%') !== false) {
		$t = urldecode($p);
		$s = execSpecialize($t);
		if ($s != $t) {
			$url_ob->set_path(str_replace(['/html', 'image/'], ['/plain', 'text/plain;'], $t));
		}
	} else {
		$s = execSpecialize($p);
		if ($s != $p) {
			$url_ob->set_path(str_replace(['/html', 'image/'], ['/plain', 'text/plain;'], $p));
		}
	}
	return (string)$url_ob;
}

/**
 * Disable the processing of the page template.
 * This function controls whether the page template will be processed at all.
 * It must be called early enough in the content generation process.
 *
 * Ideally this method can be called from within a module action that is
 * called from within the default content block when content_processing is
 * set to 2 (the default) in the config.php file
 *
 * @since 2.99
 */
function disable_template_processing()
{
	SingleItem::set('app.showtemplate', false);
}

/**
 * [Un]set the flag indicating whether to process the (optional) template
 * currently pending.
 * This method can be called from anywhere, to temporarily toggle smarty processing
 *
 * @since 2.99
 * @param bool $state optional default true
 */
function do_template_processing(bool $state = true)
{
	SingleItem::set('app.showtemplate', $state);
}

/**
 * Get the flag indicating whether or not template processing is allowed.
 *
 * @return bool
 * @since 2.99
 */
function template_processing_allowed() : bool
{
	//TODO reconcile: ($gCms->JOBTYPE < 2) = no template-processing, no $smarty
	return (bool)SingleItem::get('app.showtemplate');
}

/**
 * Get the intra-request shared scripts-combiner object.
 * @since 2.99
 *
 * @return object ScriptsMerger
 */
function get_scripts_manager() : ScriptsMerger
{
	$sm = SingleItem::get('ScriptsMerger');
	if( !$sm ) {
		$sm = new ScriptsMerger();
		SingleItem::set('ScriptsMerger', $sm);
	}
	return $sm;
}

/**
 * Get the intra-request shared styles-combiner object.
 * @since 2.99
 *
 * @return object StylesMerger
 */
function get_styles_manager() : StylesMerger
{
	$sm = SingleItem::get('StylesMerger');
	if( !$sm ) {
		$sm = new StylesMerger();
		SingleItem::set('StylesMerger', $sm);
	}
	return $sm;
}

/**
 * Get a cookie-manager instance.
 * @since 2.99
 *
 * @return AutoCookieOperations
 */
function get_cookie_manager() : AutoCookieOperations
{
	return new AutoCookieOperations();
}

/**
 * Get this site's unique identifier
 * @since 2.99
 *
 * @return 32-byte english-alphanum string
 */
function get_site_UUID() : string
{
	return SingleItem::get('site_uuid');
}

/**
 * Retrieve the installed schema version.
 * @since 2.99
 * @since 2.0 as App::get_installed_schema_version()
 *
 * @return int, maybe 0
 */
function get_installed_schema_version() : int
{
	$val = AppParams::get('cms_schema_version');
	if( AppState::test(AppState::INSTALL) ) {
		return (int)$val; //most-recently cached value (if any)
	}
	if (!$val && defined('CMS_SCHEMA_VERSION')) { //undefined during installation
		$val = CMS_SCHEMA_VERSION;
	}
	if (!$val) {
		$val = $CMS_SCHEMA_VERSION ?? 0; // no force-load here, might not be installed
	}
	return (int)$val; // maybe 0
}

/**
 * Report whether the installed tables-schema is up-to-date.
 * @since 2.99
 *
 * @return bool
 */
function schema_is_current() : bool
{
	global $CMS_SCHEMA_VERSION; // what we're supposed to have
	if (!isset($CMS_SCHEMA_VERSION)) {
		require __DIR__.DIRECTORY_SEPARATOR.'version.php'; // get it [again?]
	}
	$current = get_installed_schema_version(); // what we think we do have
	return version_compare($current, $CMS_SCHEMA_VERSION) == 0;
}

/**
 * Intra-frontend-page-display event-sender
 * @since 2.99
 *
 * @param string $eventname
 * @param int $pageid Displayed-page identifier
 * @param mixed $content Some of the page's content string | null
 * &return mixed string | null
 */
function tailorpage(string $eventname, int $pageid, $content = null)
{
	static $pageobj = null; // this will be used many times in each request

	$ret = ($content != false);
	try {
		if (!$pageobj) {
			$pageobj = PageLoader::LoadContent($pageid);
		}
		$parms = ['content' => $pageobj];
		if ($content) {
			$parms['html'] = &$content;
		}
		Events::SendEvent('Core', $eventname, $parms);
	} catch (Throwable $t) {
		trigger_error('Page-setup problem: '.$t->getMessage());
	}
	if ($ret) {
		return $content;
	}
}

} // CMSMS namespace
