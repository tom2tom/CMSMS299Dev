<?php
#Miscellaneous CMSMS-dependent support functions (not only 'page'-related).
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

use CMSMS\AppSingle;
use CMSMS\AppState;
use CMSMS\CoreCapabilities;
use CMSMS\DeprecationNotice;
use CMSMS\FormUtils;
use CMSMS\internal\ModulePluginOperations;
use CMSMS\MultiEditor;
use CMSMS\NlsOperations;
use CMSMS\RichEditor;
use CMSMS\RouteOperations;

/**
 * Miscellaneous support functions which are dependent on this CMSMS
 * instance i.e. its settings, defines, classes etc
 * When preparing to process a request, this file must not be included
 * until all pre-requisites are present.
 *
 * @package CMS
 * @license GPL
 */

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
    if (headers_sent($_f, $_l)) throw new LogicException("Attempt to set headers, but headers were already sent at: $_f::$_l");

    if ($cachable) {
        if ($_SERVER['REQUEST_METHOD'] != 'GET' ||
        AppState::test_any_state(AppState::STATE_ADMIN_PAGE | AppState::STATE_INSTALL)) {
            $cachable = false;
        }
    }
    if ($cachable) {
        $cachable = (int) cms_siteprefs::get('allow_browser_cache',0);
    }
    if (!$cachable) {
        // admin pages can't be cached... period, at all.. never.
        @session_cache_limiter('nocache');
    }
    else {
        // frontend request
        $expiry = (int)max(0,cms_siteprefs::get('browser_cache_expiry',60));
        session_cache_expire($expiry);
        session_cache_limiter('public');
        @header_remove('Last-Modified');
    }

    // setup session with different (constant) id and start it
    $session_name = 'CMSSESSID'.cms_utils::hash_string(CMS_ROOT_PATH.CMS_VERSION);
    if (!AppState::test_state(AppState::STATE_INSTALL)) {
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
    if (!@session_id()) session_start();

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
    if( AppState::test_state(AppState::STATE_INSTALL) ) return true;

    if( !cms_siteprefs::get('site_downnow') ) return false;

    $userid = get_userid(false);
    if( $userid && cms_siteprefs::get('sitedownexcludeadmins') ) return false;

    if( !isset($_SERVER['REMOTE_ADDR']) ) return true;
    $excludes = cms_siteprefs::get('sitedownexcludes');
    if( !$excludes ) return true;
    return !cms_ipmatches($_SERVER['REMOTE_ADDR'],$excludes);
}

/**
 * Gets the username of the current CLI-user
 * NOT cached/static (to support concurrent-use)
 * @since 2.3
 * @return mixed string|null
 */
function get_cliuser()
{
    $uname = exec('whoami');
    if( !$uname ) {
        $file = tempnam(PUBLIC_CACHE_LOCATION, 'WHOMADE_');
        file_put_contents($file, 'test');
        $userid = fileowner($file);
        unlink($file);
        if( $userid ) { //might be false
            if( function_exists('posix_getpwuid') ) {
                $uname = posix_getpwuid($userid)['name']; //approximate, hack
            }
            else {
                $uname = getenv('USERNAME');
            }
        }
    }
    return $uname;
}

/**
 * Get the numeric id of the current user (logged-in or otherwise).
 *
 * If an effective userid has been recorded in the session, AND the primary user
 * is a member of the admin group, then allow emulating that effective userid.
 *
 * @since 0.1
 * @param  boolean $redirect Optional flag, default true. Whether to redirect to
 *  the admin login page if the user is not logged in (and operating in 'normal' mode).
 * @return mixed integer userid of the user | null
 */
function get_userid(bool $redirect = true)
{
    $config = AppSingle::Config();
    if( !$config['app_mode'] ) {
        if( cmsms()->is_cli() ) {
            $uname = get_cliuser();
            if( $uname ) {
                $user = AppSingle::UserOperations()->LoadUserByUsername($uname);
                if( $user ) {
                    return $user->id;
                }
            }
            return null;
        }
        //  TODO alias etc during other 'remote admin'
        $userid = AppSingle::LoginOperations()->get_effective_uid();
        if( !$userid && $redirect ) {
            redirect($config['admin_url'].'/login.php');
        }
        return $userid;
    }
    return 1; //CHECKME is the super-admin the only possible user in app mode ? if doing remote admin?
}

/**
 * Gets the username of the current user (logged-in or otherwise).
 *
 * If an effective username has been set in the session, AND the primary user is
 * a member of the admin group, then return the effective username.
 *
 * @since 2.0
 * @param  boolean $redirect Optional flag, default true. Whether to redirect to
 *  the admin login page if the user is not logged in.
 * @return string the username of the user, or '', or no return at all.
 */
function get_username(bool $redirect = true)
{
    $config = AppSingle::Config();
    if( !$config['app_mode'] ) {
        if( cmsms()->is_cli() ) {
            return get_cliuser();
        }
        //TODO alias etc during 'remote admin'
        $uname = AppSingle::LoginOperations()->get_effective_username();
        if( !$uname && $redirect ) {
            redirect($config['admin_url'].'/login.php');
        }
        return $uname;
    }
    return ''; //no username in app mode
}

/**
 * Checks to see if the user is logged in and the request has the proper key.
 * If not, normally redirects the browser to the admin login.
 *
 * Note: this method should only be called from admin operations.
 *
 * @since 0.1
 * @param boolean $no_redirect Optional flag, default false. If true, then do NOT redirect if not logged in
 * @return boolean or no return at all
 */
function check_login(bool $no_redirect = false)
{
    $redirect = !$no_redirect;
    $userid = get_userid($redirect);
    $ops = AppSingle::LoginOperations();
    if( $userid > 0 ) {
        if( $ops->validate_requestkey() ) {
            return true;
        }
        // still here if logged in, but no/invalid secure-key in the request
    }
    if( $redirect ) {
        // redirect to the admin login page
        // use SCRIPT_FILENAME and make sure it validates with the root_path
        if( startswith($_SERVER['SCRIPT_FILENAME'],CMS_ROOT_PATH) ) {
            $_SESSION['login_redirect_to'] = $_SERVER['REQUEST_URI'];
        }
        $ops->deauthenticate();
        $config = AppSingle::Config();
        redirect($config['admin_url'].'/login.php');
    }
    return false;
}

/**
 * Return the permissions (names) which always require explicit authorization
 *  i.e. even for super-admins (user 1 | group 1)
 * @since 2.3
 * @return array
 */
function restricted_cms_permissions() : array
{
    $val = cms_siteprefs::get('ultraroles');
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
 *
 * @since 0.1
 * @param int $userid The user id
 * @param varargs $perms Since 2.8 This may be a single permission-name string,
 *  or an array of such string(s), all members of which are to be 'OR'd,
 *  unless there's a following true-valued parameter, in which case those
 *  members are to be 'AND'd
 *  Formerly the second argument was limited to one permission-name string
 * @return boolean
 */
function check_permission(int $userid, ...$perms)
{
    return AppSingle::UserOperations()->CheckPermission($userid, ...$perms);
}

/**
 * Checks that the given userid has access to modify the given
 * pageid.  This would mean that they were set as additional
 * authors/editors by the owner.
 *
 * @internal
 * @since 0.2
 * @param  integer The admin user identifier
 * @param  mixed   Optional (valid) integer content id | null. Default null.
 * @return boolean
 */
function check_authorship(int $userid, $contentid = null)
{
    return AppSingle::ContentOperations()->CheckPageAuthorship($userid,$contentid);
}

/**
 * Gets an array of pages whose author is $userid.
 *
 * @internal
 * @since 0.11
 * @param  integer The user id.
 * @return array   An array of pages this user is an author of.
 */
function author_pages(int $userid)
{
    return AppSingle::ContentOperations()->GetPageAccessForUser($userid);
}

/**
 * Redirect to a relative URL on the current site.
 *
 * If headers have not been sent this method will use header based redirection.
 * Otherwise javascript redirection will be used.
 *
 * @author http://www.edoceo.com/
 * @since 0.1
 * @package CMS
 * @param string $to The url to redirect to
 */
function redirect(string $to)
{
    $app = AppSingle::App();
    if ($app->is_cli()) die("ERROR: no redirect on cli based scripts ---\n");

    $_SERVER['PHP_SELF'] = null;
    //TODO generally support the websocket protocol
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
            if ($l > 0 && substr_count($components['path'],'.', 1, $l-1) > 0) {
                $components['path'] = strtr(substr($components['path'], 0, $l), '.', '/') . substr($components['path'], $l);
            }
            if (in_array($components['path'][0],['\\','/'])) {
                //path is absolute, just append
                $to .= $components['path'];
            }
            //path is relative, append current directory first
            elseif (isset($_SERVER['PHP_SELF']) && !is_null($_SERVER['PHP_SELF'])) { //Apache
                $to .= (strlen(dirname($_SERVER['PHP_SELF'])) > 1 ?  dirname($_SERVER['PHP_SELF']).'/' : '/') . $components['path'];
            }
            elseif (isset($_SERVER['REQUEST_URI']) && !is_null($_SERVER['REQUEST_URI'])) { //Lighttpd
                if (endswith($_SERVER['REQUEST_URI'], '/')) {
                    $to .= (strlen($_SERVER['REQUEST_URI']) > 1 ? $_SERVER['REQUEST_URI'] : '/') . $components['path'];
                }
                else {
                    $dn = dirname($_SERVER['REQUEST_URI']);
                    if (!endswith($dn,'/')) $dn .= '/';
                    $to .= $dn . $components['path'];
                }
            }
        }
        $to .= isset($components['query']) ? '?' . $components['query'] : '';
        $to .= isset($components['fragment']) ? '#' . $components['fragment'] : '';
    }
    else {
        $to = $schema.'://'.$host.'/'.$to;
    }

    session_write_close();


    if (!AppState::test_state(AppState::STATE_INSTALL)) {
        $debug = constant('CMS_DEBUG');
    }
    else {
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
    }
    elseif ($debug) {
        echo 'Automatic redirection is disabled while debugging. Please click this link to continue.<br />
<a accesskey="r" href="'.$to.'">'.$to.'</a><br />
<div id="DebugFooter">';
        foreach ($app->get_errors() as $error) {
            echo $error;
        }
        echo '</div> <!-- end DebugFooter -->';
    }
    else {
        header("Location: $to");
    }
    exit;
}

/**
 * Given a page ID or an alias, redirect to it.
 * Retrieves the URL of the specified page, and performs a redirect
 *
 * @param string $alias A page alias.
 */
function redirect_to_alias(string $alias)
{
    $hm = AppSingle::App()->GetHierarchyManager();
    $node = $hm->find_by_tag('alias',$alias);
    if (!$node) {
        // put mention into the admin log
        cms_warning('Core: Attempt to redirect to invalid alias: '.$alias);
        return;
    }
    $contentobj = $node->getContent();
    if (!is_object($contentobj)) {
        cms_warning('Core: Attempt to redirect to invalid alias: '.$alias);
        return;
    }
    $url = $contentobj->GetURL();
    if ($url) {
        redirect($url);
    }
}

/**
 * Return a page id or alias, determined from current request data.
 * This method also handles matching routes and specifying which module
 * should be called with what parameters.
 *
 * This is the main routine to do route-dispatching
 *
 * @internal
 * @ignore
 * @access private
 * @return string (or null?)
 */
function get_pageid_or_alias_from_url()
{
    $config = AppSingle::Config();

    $query_var = $config['query_var'];
    if( isset($_GET[$query_var]) ) {
        // using non friendly urls... get the page alias/id from the query var.
        $page = @trim((string) $_REQUEST[$query_var]);
    }
    else {
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
    unset($_GET['query_var']);
    if( !$page ) {
        // use the default page id
        return AppSingle::ContentOperations()->GetDefaultContent();
    }

    // by here, if we're not assuming pretty urls of any sort and we
    // have a value... we're done.
    if( $config['url_rewriting'] == 'none' ) {
        return $page;
    }

    // some kind of pretty-url
    // strip GET params
    if( ($tmp = strpos($page,'?')) !== false ) $page = substr($page,0,$tmp);

    // strip page extension
    if ($config['page_extension'] != '' && endswith($page, $config['page_extension'])) {
        $page = substr($page, 0, strlen($page) - strlen($config['page_extension']));
    }

    // some servers leave in the first / of a request sometimes, which will stop route-matching
    // so strip trailing and leading /
    $page = trim($page, '/');

    // check if there's a route that matches.
    // note: we handle content routing separately at this time.
    // it'd be cool if contents were just another mact.
    $route = RouteOperations::find_match($page);
    if( $route ) {
        $to = $route->get_dest();
        if( $to == '__CONTENT__' ) {
            // a route to a page
            $page = (int)$route['key2'];
        }
        else {
            // a module route
            // setup some default parameters.
            $arr = [ 'module'=>$to, 'id'=>'cntnt01', 'action'=>'defaulturl', 'inline'=>0 ];
            $tmp = $route->get_defaults();
            if( $tmp ) $arr = array_merge($tmp, $arr);
            $arr = array_merge($arr, $route->get_results());

            // put a constructed mact into $_REQUEST for later processing.
            // this is essentially our translation from pretty URLs to non-pretty URLS.
            // TODO also support secure params via GetParameters class
            $_REQUEST['mact'] = $arr['module'] . ',' . $arr['id'] . ',' . $arr['action'] . ',' . $arr['inline'];

            // put other parameters (except numeric matches) into $_REQUEST.
            foreach( $arr as $key=>$val ) {
                switch ($key) {
                    case 'module':
                    case 'id':
                    case 'action':
                    case 'inline':
                        break; //no need to repeat mact parameters
                    default:
                        if( !is_int($key) ) {
                            $_REQUEST[$arr['id'] . $key] = $val;
                        }
                }
            }
            // get a decent returnid
            if( $arr['returnid'] ) {
                $page = (int) $arr['returnid'];
//                unset($arr['returnid']);
            }
            else {
                $page = AppSingle::ContentOperations()->GetDefaultContent();
            }
        }
    }
    else // no route matched... assume it is an alias which begins after the last /
      if( ($pos = strrpos($page,'/')) !== false ) {
        $page = substr($page, $pos + 1);
    }

    return $page;
}

/**
 * Gets the given site preference
 * @since 0.6
 *
 * @deprecated since 1.10 NOPE
 * @see cms_siteprefs::get
 *
 * @param string $prefname The preference name
 * @param mixed  $defaultvalue Optional return-value if the preference does not exist. Default null
 * @return mixed
 */
function get_site_preference(string $prefname, $defaultvalue = null)
{
    return cms_siteprefs::get($prefname,$defaultvalue);
}

/**
 * Removes the given site preference.
 * @since 0.6
 *
 * @deprecated since 1.10 NOPE
 * @see cms_siteprefs::remove
 *
 * @param string $prefname Preference name to remove
 * @param boolean $uselike  Optional flag, default false. Whether to remove all preferences that are LIKE the supplied name.
 */
function remove_site_preference(string $prefname, bool $uselike = false)
{
    return cms_siteprefs::remove($prefname, $uselike);
}

/**
 * Sets the given site preference with the given value.
 * @since 0.6
 *
 * @deprecated since 1.10 NOPE
 * @see cms_siteprefs::set
 *
 * @param string $prefname The preference name
 * @param mixed  $value The preference value (will be stored as a string)
 */
function set_site_preference(string $prefname, $value)
{
    return cms_siteprefs::set($prefname, $value);
}

/**
 * Return the secure param query-string used in all admin links.
 *
 * @internal
 * @access private
 * @return string
 */
function get_secure_param() : string
{
    $out = '?';
    if (!ini_get_boolean('session.use_cookies')) {
        //PHP constant SID is unreliable, we recreate it
        $out .= rawurlencode(session_name()).'='.rawurlencode(session_id()).'&amp;';
    }
    $out .= CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
    return $out;
}

/**
 * Return the secure params in a form-friendly format.
 *
 * @internal
 * @access private
 * @return array
 */
function get_secure_param_array() : array
{
    $out = [CMS_SECURE_PARAM_NAME => $_SESSION[CMS_USER_KEY]];
    if (!ini_get_boolean('session.use_cookies')) {
        $out[session_name()] = session_id();
    }
    return $out;
}

/**
 * Process a module-tag
 * This method is used by the {cms_module} plugin and to process {ModuleName} tags
 * @internal
 * @since 2.9 ModulePluginOperations::call_plugin_module() may be used instead
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
 * Return the url corresponding to a provided site-path
 *
 * @since 2.3
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
    }
    elseif (preg_match('~^ *(?:\/|\\\\|\w:\\\\|\w:\/)~', $in)) {
        // $in is absolute
        $in = realpath($in);
        return str_replace([CMS_ROOT_PATH, DIRECTORY_SEPARATOR], [CMS_ROOT_URL, '/'], $in);
    }
    else {
        return strtr($in, DIRECTORY_SEPARATOR, '/');
    }
}

/**
 * Return the relative portion of a path
 *
 * @since 2.2
 * @author Robert Campbell
 * @param string $in The input path or file specification
 * @param string $relative_to The optional path to compute relative to.  If not supplied the cmsms root path will be used.
 * @return string The relative portion of the input string.
 */
function cms_relative_path(string $in, string $relative_to = null) : string
{
    $in = realpath(trim($in));
    if (!$relative_to) $relative_to = CMS_ROOT_PATH;
    $to = realpath(trim($relative_to));

    if ($in && $to && startswith($in, $to)) {
        return substr($in, strlen($to));
    }
    return '';
}

/**
 * Get PHP flag corresponding to the configured 'content_language' i.e. the
 * preferred language/syntax for page-content
 *
 * @since 2.3
 * @return PHP flag
 */
function cms_preferred_lang() : int
{
    $val = str_toupper(AppSingle::Config()['content_language']);
    switch ($val) {
        case 'HTML5';
            return ENT_HTML5;
        case 'HTML':
            return ENT_HTML401; //a.k.a. 0
        case 'NONE':
            return 0;
        default:
            return ENT_XHTML;
    }
}

static $deflang = 0;
static $defenc = '';

/**
 * Perform HTML entity conversion on a string.
 *
 * @see htmlentities
 *
 * @param mixed  $val     The input string, or maybe null
 * @param int    $flags   Optional bit-flag(s) indicating how htmlentities() should handle quotes etc. Default 0, hence ENT_QUOTES | cms_preferred_lang().
 * @param string $charset Optional character set of $val. Default 'UTF-8'. If empty the system setting will be used.
 * @param bool   $convert_single_quotes Optional flag indicating whether single quotes should be converted to entities. Default false.
 *
 * @return string the converted string
 */
function cms_htmlentities($val, int $flags = 0, string $charset = 'UTF-8', bool $convert_single_quotes = false) : string
{
    if ($val === '' || $val === null) {
        return '';
    }

    global $deflang, $defenc;

    if ($flags === 0) {
        $flags = ($convert_single_quotes) ? ENT_QUOTES : ENT_COMPAT;
    }
    if ($flags & (ENT_HTML5 | ENT_XHTML | ENT_HTML401) == 0) {
        if ($deflang === 0) {
            $deflang = cms_preferred_lang();
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
    return htmlentities($val, $flags, $charset, false);
}

/**
 * Perform HTML entity conversion on a string.
 *
 * @see html_entity_decode
 *
 * @param string $val     The input string
 * @param int    $param   Optional flag(s) indicating how html_entity_decode() should handle quotes etc. Default 0, hence ENT_QUOTES | cms_preferred_lang().
 * @param string $charset Optional character set of $val. Default 'UTF-8'. If empty the system setting will be used.
 *
 * @return string the converted string
 */
function cms_html_entity_decode(string $val, int $param = 0, string $charset = 'UTF-8') : string
{
    if ($val === '') {
        return '';
    }

    global $deflang, $defenc;

    if ($param === 0) {
        $param = ENT_QUOTES;
    }
    if ($param & (ENT_HTML5 | ENT_XHTML | ENT_HTML401) == 0) {
        if ($deflang === 0) {
            $deflang = cms_preferred_lang();
        }
        $param |= $deflang;
    }

    if (!$charset) {
        if ($defenc === '') {
            $defenc = NlsOperations::get_encoding();
        }
        $charset = $defenc;
    }

    return html_entity_decode($val, $param, $charset);
}

/**
 * A wrapper around move_uploaded_file that attempts to ensure permissions on uploaded
 * files are set correctly.
 *
 * @param string $tmpfile The temporary file specification
 * @param string $destination The destination file specification
 * @return bool
 */
function cms_move_uploaded_file(string $tmpfile, string $destination) : bool
{
    if (@move_uploaded_file($tmpfile, $destination)) {
        return @chmod($destination, octdec(AppSingle::Config()['default_upload_permission']));
    }
    return false;
}

/**
 * Return a UNIX UTC timestamp corresponding to the supplied (typically
 * database datetime formatted and timezoned) date/time string.
 * The supplied parameter is not validated, apart from ignoring a falsy value.
 * @since 2.3
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
                $dtz = new DateTimeZone(AppSingle::Config()['timezone']);
                $offs = timezone_offset_get($dtz, $dt);
            }
        }
        try {
            $dt->modify($datetime);
            if (!$is_utc) {
                return $dt->getTimestamp() - $offs;
            }
            return $dt->getTimestamp();
        }
        catch (Throwable $t) {
            // nothing here
        }
    }
    return 1; // anything not falsy
}

/**
 * Identify the, or the highest-versioned, installed jquery scripts and/or css
 * @since 2.3
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
                }
                elseif (version_compare($best, $matches[1]) < 0 || ($min && empty($matches[2]))) {
                    $best = $matches[1];
                    $use = $file;
                    $min = !empty($matches[2]); //$use is .min
                }
            }
            elseif (version_compare($best, $matches[1]) < 0) {
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
 * Return content which will include wanted js (jQuery etc) and css in a
 * displayed page.
 * @since 1.10
 * @deprecated since 2.3
 * Instead, relevant content can be gathered via functions added to hook
 * 'AdminHeaderSetup' and/or 'AdminBottomSetup', or a corresponding tag
 *  e.g. {gather_content list='AdminHeaderSetup'}.
 * See also the ScriptOperations class, for consolidating scripts into a single
 * download.
 */
function cms_get_jquery(string $exclude = '',bool $ssl = false,bool $cdn = false,string $append = '',string $custom_root = '',bool $include_css = true)
{
    $incs = cms_installed_jquery(true, false, true, $include_css);
    if ($include_css) {
        $url1 = cms_path_to_url($incs['jquicss']);
        $s1 = <<<EOS
<link rel="stylesheet" type="text/css" href="{$url1}" />

EOS;
    }
    else {
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
 * @since 2.3
 * @ignore
 */
function get_best_file($places, $target, $ext, $as_url)
{
    if (($p = stripos($target, 'min')) !== false) {
        $base = substr($target, 0, $p-1); //strip [.-]min & type-suffix
    }
    elseif (($p = stripos($target, '.'.$ext)) !== false) {
        $base = substr($target, 0, $p); //strip type-suffix
    }
    $base = strtr($base, ['.'=>'\\.', '-'=>'\\-']);

    $patn = '~^'.$base.'([.-](\d[\d\.]*))?([.-]min)?\.'.$ext.'$~i';
    foreach ($places as $base_path) {
        $allfiles = scandir($base_path);
        if ($allfiles) {
            $files = preg_grep($patn, $allfiles);
            if ($files) {
                if (count($files) > 1) {
//                    $best = ''
                    foreach ($files as $target) {
                        preg_match($patn, $target, $matches);
                        if (!empty($matches[2])) {
                            break; //use the min TODO check versions too
                        }
                        elseif (!empty($matches[1])) {
                            //TODO a candidate, but try for later-version/min
                        }
                        else {
                            //TODO a candidate, but try for min
                        }
                    }
//                    $target = $best;
                }
                else {
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
 * Return the filepath or URL of a wanted script file, if found in any of the
 * standard locations for such files (or any other provided location).
 * Intended mainly for non-jQuery scripts, but it will try to find those those too.
 * @since 2.3
 *
 * @param string $filename absolute or relative filepath or (base)name of the
 *  wanted file, optionally including [.-]min before the .js extension
 *  If the name includes a version, that will be taken into account.
 *  Otherwise, the first-found version will be used. Min-format preferred over non-min.
 * @param bool $as_url optional flag, whether to return URL or filepath. Default true.
 * @param mixed $custompaths string | string[] optional 'non-standard' directory-path(s) to include (first) in the search
 * @return mixed string absolute filepath | URL | null
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
    }
    elseif (preg_match('~^ *(?:\/|\\\\|\w:\\\\|\w:\/)~', $filename)) {
        // $filename is absolute
        $places = [dirname($filename)];
    }
    else {
        // $filename is relative, try to find it
        //TODO if relevant, support somewhere module-relative
        //TODO partial path-intersection too, any separators
        $base_path = ltrim(dirname($filename),' \\/');
        $places = [
         $base_path,
         CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'js',
         CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'assets',
         AppSingle::Config()['uploads_path'],
         CMS_ADMIN_PATH.DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'js',
         CMS_ROOT_PATH,
        ];
    }

    if ($custompaths) {
        if (is_array($custompaths)) {
            $places = array_merge($custompaths, $places);
        }
        else {
            array_unshift($places, $custompaths);
        }
        $places = array_unique($places);
    }

    return get_best_file($places, $target, 'js', $as_url);
}

/**
 * Return the filepath or URL of a wanted css file, if found in any of the
 * standard locations for such files (or any other provided location).
 * Intended mainly for non-jQuery styles, but it will try to find those those too.
 * @since 2.3
 *
 * @param string $filename absolute or relative filepath or (base)name of the
 *  wanted file, optionally including [.-]min before the .css extension
 *  If the name includes a version, that will be taken into account.
 *  Otherwise, the first-found version will be used. Min-format preferred over non-min.
 * @param bool $as_url optional flag, whether to return URL or filepath. Default true.
 * @param mixed $custompaths string | string[] optional 'non-standard' directory-path(s) to include (first) in the search
 * @return mixed string absolute filepath | URL | null
 */
function cms_get_css(string $filename, bool $as_url = true, $custompaths = '')
{
    $target = basename($filename);
    if ($target == $filename) {
        $places = [
         CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'css',
         CMS_ADMIN_PATH.DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'css',
         CMS_SCRIPTS_PATH.DIRECTORY_SEPARATOR.'jquery',
         CMS_SCRIPTS_PATH.DIRECTORY_SEPARATOR.'jquery-ui',
        ];
    }
    elseif (preg_match('~^ *(?:\/|\\\\|\w:\\\\|\w:\/)~', $filename)) {
        // $filename is absolute
        $places = [dirname($filename)];
    }
    else {
        // $filename is relative, try to find it
        //TODO if relevant, support somewhere module-relative
        //TODO partial path-intersection too, any separators
        $base_path = ltrim(dirname($filename),' \\/');
        $places = [
         $base_path,
         CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'css',
         CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'assets',
         AppSingle::Config()['uploads_path'],
         CMS_ADMIN_PATH.DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'css',
         CMS_ROOT_PATH,
        ];
    }

    if ($custompaths) {
        if (is_array($custompaths)) {
            $places = array_merge($custompaths, $places);
        }
        else {
            array_unshift($places, $custompaths);
        }
        $places = array_unique($places);
    }

    return get_best_file($places, $target, 'css', $as_url);
}

/**
 * A method to create a text area control
 *
 * @internal
 * @access private
 * @deprecated since 2.3 instead use CMSMS\FormUtils::create_textarea()
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
    assert(empty(CMS_DEPREC), new DeprecationNotice('method','FormUtils::create_textarea'));
    $parms = func_get_args() + [
        'height' => 15,
        'width' => 80,
    ];
    return FormUtils::create_textarea($parms);
}

/**
 * Create a dropdown/select html element containing a list of files that match certain conditions
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
 * @param boolean An optional flag indicating whether the files matching the extension and the prefix should be included or excluded from the result set. Default true.
 * @param boolean An optional flag indicating whether the output should be sorted. Default false.
 * @return string maybe empty
 */
function create_file_dropdown(
    string $name,
    string $dir,
    string $value,
    string $allowed_extensions,
    string $optprefix = '',
    bool   $allownone = false,
    string $extratext = '',
    string $fileprefix = '',
    bool   $excludefiles = true,
    bool   $sortresults = false) : string
{
    $files = [];
    $files = get_matching_files($dir,$allowed_extensions,true,true,$fileprefix,$excludefiles);
    if( $files === false ) return '';
    $out = "<select name=\"{$name}\" id=\"{$name}\" {$extratext}>\n";
    if( $allownone ) {
        $txt = '';
        if( empty($value) ) $txt = 'selected="selected"';
        $out .= "<option value=\"-1\" $txt>--- ".lang('none')." ---</option>\n";
    }

    if( $sortresults ) natcasesort($files);
    foreach( $files as $file ) {
        $txt = '';
        $opt = $file;
        if( !empty($optprefix) ) $opt = $optprefix.'/'.$file;
        if( $opt == $value ) $txt = 'selected="selected"';
        $out .= "<option value=\"{$opt}\" {$txt}>{$file}</option>\n";
    }
    $out .= '</select>';
    return $out;
}

/**
 * Get page content (js, css) for initialization of and use by the configured
 * 'rich-text' (a.k.a. wysiwyg) text-editor.
 * @since 2.3
 * @param array $params  Configuration details. Recognized members are:
 *  string 'editor' name of editor to use. Default '' hence recorded preference.
 *  bool   'edit'   whether the content is editable. Default false (i.e. just for display)
 *  string 'handle' name of the js variable to be used for the created editor. Default 'editor'
 *  string 'htmlid' id of the page-element whose content is to be edited. Default 'edit_area'.
 *  string 'theme'  override for the normal editor theme/style.  Default ''
 *  string 'workid' id of a div to be created (by some editors) to process
 *    the content of the htmlid-element. (As always, avoid conflict with tab-name divs). Default 'edit_work'
 *
 * @return array up to 2 members, those being 'head' and/or 'foot', or perhaps [1] or []
 */
function get_richeditor_setup(array $params) : array
{
    if( AppSingle::App()->is_frontend_request() ) {
        $val = cms_siteprefs::get('frontendwysiwyg'); //module name
    }
    else {
        $userid = get_userid();
        $val = cms_userprefs::get_for_user($userid, 'wysiwyg');
        if( !$val ) {
            $val = cms_siteprefs::get('wysiwyg');
        }
    }
    if( $val ) {
        $vars = explode ('::', $val);
        $modname = $vars[0] ?? '';
        if( $modname ) {
            $modinst = cms_utils::get_module($modname);
            if( $modinst ) {
                if( $modinst instanceof RichEditor ) {
                    $edname = $params['editor'] ?? $vars[1] ?? $modname;
                    if (empty($params['theme'])) {
                        $val = cms_userprefs::get_for_user($userid, 'richeditor_theme');
                        if( !$val ) {
                            $val = cms_siteprefs::get('richeditor_theme');
                        }
                        if( $val ) {
                            $params['theme'] = $val;
                        }
                    }
                    return $modinst->GetEditorSetup($edname, $params);
                }
                elseif( $modinst->HasCapability(CoreCapabilities::WYSIWYG_MODULE) ) {
                    if( empty($params['editor']) ) { $params['editor'] = $vars[1] ?? $modname; }
                    //$params[] will be ignored by modules without relevant capability
                    $out = $modinst->WYSIWYGGenerateHeader($params);
                    if( $out ) { return ['head'=>$out]; }
                    return [1]; //anything not falsy
                }
            }
        }
    }
    return [];
}

/**
 * Get page content (css, js) for initialization of and use by the configured
 * 'advanced' (a.k.a. syntax-highlight) text-editor. Assumes that is for admin usage only.
 * @since 2.3
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
 *
 * @return array up to 2 members, those being 'head' and/or 'foot', or perhaps [1] or []
 */
function get_syntaxeditor_setup(array $params) : array
{
    if( AppSingle::App()->is_frontend_request() ) {
        return [];
    }

    $userid = get_userid();
    $val = cms_userprefs::get_for_user($userid, 'syntax_editor');
    if( !$val ) {
        $val = cms_siteprefs::get('syntax_editor');
    }
    if( $val ) {
        $vars = explode ('::', $val);
        $modname = $vars[0] ?? '';
        if( $modname ) {
            $modinst = cms_utils::get_module($modname);
            if( $modinst ) {
                if( $modinst instanceof MultiEditor ) {
                    $edname = $params['editor'] ?? $vars[1] ?? $modname;
                    if (empty($params['theme'])) {
                        $val = cms_userprefs::get_for_user($userid, 'syntax_theme');
                        if( !$val ) {
                            $val = cms_siteprefs::get('syntax_theme');
                        }
                        if( $val ) {
                            $params['theme'] = $val;
                        }
                    }
                    return $modinst->GetEditorSetup($edname, $params);
                }
                elseif( $modinst->HasCapability(CoreCapabilities::SYNTAX_MODULE) ) {
                    if( empty($params['editor']) ) { $params['editor'] = $vars[1] ?? $modname; }
                    //$params[] will be ignored by modules without relevant capability
                    $out = $modinst->SyntaxGenerateHeader($params);
                    if( $out ) { return ['head'=>$out]; }
                    return [1]; //anything not falsy
                }
            }
        }
    }
    return [];
}

/**
 * Output a backtrace into the generated log file.
 *
 * @see debug_to_log, debug_bt
 * Rolf: Looks like not used
 */
function debug_bt_to_log()
{
    if (AppSingle::Config()['debug_to_log'] ||
        (function_exists('get_userid') && get_userid(false))) {
        $bt = debug_backtrace();
        $file = $bt[0]['file'];
        $line = $bt[0]['line'];

        $out = ["Backtrace in $file on line $line"];

        $bt = array_reverse($bt);
        foreach($bt as $trace) {
            if ($trace['function'] == 'debug_bt_to_log') continue;

            $file = '';
            $line = '';
            if (isset($trace['file'])) {
                $file = $trace['file'];
                $line = $trace['line'];
            }
            $function = $trace['function'];
            $str = "$function";
            if ($file) $str .= " at $file:$line";
            $out[] = $str;
        }

        $filename = TMP_CACHE_LOCATION . DIRECTORY_SEPARATOR. 'debug.log';
        foreach ($out as $txt) {
            error_log($txt . "\n", 3, $filename);
        }
    }
}

/**
 * Display (echo) stack trace as human-readable lines
 *
 * This method uses echo.
 * @param string $title since 2.3 Optional title for (verbatim) display
 */
function stack_trace(string $title = '')
{
    if ($title) echo $title . "\n";

    $bt = debug_backtrace();
    foreach ($bt as $elem) {
        if ($elem['function'] == 'stack_trace') continue;
        if (isset($elem['file'])) {
            echo $elem['file'].':'.$elem['line'].' - '.$elem['function'].'<br />';
        }
        else {
            echo ' - '.$elem['function'].'<br />';
        }
    }
}

/**
 * Generate a backtrace in a readable format.
 *
 * This function does not return but echoes output.
 */
function debug_bt()
{
    $bt = debug_backtrace();
    $file = $bt[0]['file'];
    $line = $bt[0]['line'];

    echo "\n\n<p><b>Backtrace in $file on line $line</b></p>\n";

    $bt = array_reverse($bt);
    echo "<pre><dl>\n";
    foreach($bt as $trace) {
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
    if (!$starttime) $starttime = microtime();

    ob_start();

    if ($showtitle) {
        $titleText = microtime_diff($starttime,microtime()) . ' S since request-start';
        if (function_exists('memory_get_usage')) {
            $net = memory_get_usage() - $orig_memory;
            $titleText .= ', memory usage: net '.$net;
        }
        else {
            $net = false;
        }

        $memory_peak = (function_exists('memory_get_peak_usage') ? memory_get_peak_usage() : '');
        if ($memory_peak) {
            if ($net === false) {
                $titleText .= ', memory usage: peak '.$memory_peak;
            }
            else {
                $titleText .= ', peak '.$memory_peak;
            }
        }

        if ($use_html) {
            echo "<div><b>$titleText</b>\n";
        }
        else {
            echo "$titleText\n";
        }
    }

    if ($title || $var || is_numeric($var)) {
        if ($use_html) echo '<pre>';
        if ($title) echo $title . "\n";
        if (is_array($var)) {
            echo 'Number of elements: ' . count($var) . "\n";
            print_r($var);
        }
        elseif (is_object($var)) {
            print_r($var);
        }
        elseif (is_string($var)) {
            if ($use_html) {
                print_r(htmlentities(str_replace("\t", '  ', $var)));
            }
            else {
                print_r($var);
            }
        }
        elseif (is_bool($var)) {
            echo ($var) ? 'true' : 'false';
        }
        elseif ($var || is_numeric($var)) {
            print_r($var);
        }
        if ($use_html) echo '</pre>';
    }
    if ($use_html) echo "</div>\n";

    $out = ob_get_clean();

    if ($echo_to_screen) echo $out;
    return $out;
}

/**
 * Display $var nicely only if $config["debug"] is set.
 *
 * @param mixed $var
 * @param string $title
 */
function debug_output($var, string $title='')
{
    if (AppSingle::Config()['debug']) { debug_display($var, $title, true); }
}

/**
 * Debug function to output debug information about a variable in a formatted matter
 * to a debug file.
 *
 * @param mixed $var    data to display
 * @param string $title optional title.
 * @param string $filename optional output filename
 */
function debug_to_log($var, string $title='',string $filename = '')
{
    if (AppSingle::Config()['debug_to_log'] ||
        (function_exists('get_userid') && get_userid(false))) {
        if ($filename == '') {
            $filename = TMP_CACHE_LOCATION . '/debug.log';
            $x = (is_file($filename)) ? @filemtime($filename) : time();
            if ($x !== false && $x < (time() - 24 * 3600)) unlink($filename);
        }
        $errlines = explode("\n",debug_display($var, $title, false, false, true));
        foreach ($errlines as $txt) {
            error_log($txt . "\n", 3, $filename);
        }
    }
}

/**
 * Add $var to the global errors array if $config['debug'] is in effect.
 *
 * @param mixed $var
 * @param string $title
 */
function debug_buffer($var, string $title='')
{
    if (constant('CMS_DEBUG')) {
        AppSingle::App()->add_error(debug_display($var, $title, false, true));
    }
}
