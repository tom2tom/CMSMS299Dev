<?php
/*
A class of convenience functions for admin console requests
Copyright (C) 2010-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS;

use CMSMS\AppState;
use CMSMS\DeprecationNotice;
use CMSMS\Events;
use CMSMS\HookOperations;
use CMSMS\HttpRequest;
use CMSMS\Lone;
use ErrorException;
use FilesystemIterator;
use LogicException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use const CMS_DEFAULT_VERSIONCHECK_URL;
use const CMS_DEPREC;
use const CMS_ROOT_URL;
use const CMS_SECURE_PARAM_NAME;
use const CMS_USER_KEY;
use const CMS_VERSION;
use const PUBLIC_CACHE_LOCATION;
use const TMP_CACHE_LOCATION;
use const TMP_TEMPLATES_C_LOCATION;
use function add_page_foottext;
use function check_permission;
use function cms_get_script;
use function endswith;
use function get_userid;
use function lang;

/* this is also used during content installation i.e. state INSTALL, or nothing
if (!AppState::test(AppState::ADMIN_PAGE)) {
	$name = AdminUtils::class;
	throw new ErrorException("Attempt to use $name class from an invalid request");
}
*/
/**
 * A class of static utility methods for admin requests.
 *
 * @final
 * @package CMS
 * @license GPL
 * @author  Robert Campbell
 *
 * @since 2.0
 */
final class AdminUtils
{
	/**
	 * A regular expression to use when testing if an item has a valid name.
	 * Valid if the name has initial (regex) 'word' char, then any number of 'word' or '/+-., ' char(s)
	 * @see also CMSMS\sanitizeVal(..., CMSSAN_NAME) which is similar
	 */
	private const ITEMNAME_REGEX = '<^[a-zA-Z0-9_\x80-\xff][a-zA-Z0-9_/+\-., \x80-\xff]*$>';

	/**
	 * @ignore
	 */
	private function __construct() {}

	/**
	 * @ignore
	 */
	private function __clone(): void {}

	/**
	 * Test if a string is suitable for use as a name of an item in CMSMS.
	 * For use by various modules and the core.
	 * The name must begin with an alphanumeric character (but some
	 * extended characters are allowed) and that must be followed by
	 * additional alphanumeric characters
	 * Note the name is not necessarily guaranteed to be usable in
	 * smarty without backticks.
	 *
	 * @param string $str The string to test
	 * @return bool|string false on error or the validated string.
	 */
	public static function is_valid_itemname(string $str)
	{
		if (!is_string($str)) return false;
		$t_str = trim($str);
		if (!$t_str) return false;
		if (!preg_match(self::ITEMNAME_REGEX, $t_str)) return false;
		return $str;
	}

	/**
	 * Convert an admin request URL to a generic form that is suitable
	 * for saving to a database.
	 * This is useful for things like bookmarks and homepages.
	 * NOTE: generated URL's are relative to the site's topmost admin URL
	 *
	 * @param string $in_url The input URL that has the session key in it.
	 * @return string A relative URL that is converted to a generic form.
	 */
	public static function get_generic_url(string $in_url): string
	{
		static $rem1 = null; // avoid umpteen calc's of the same value

		if (!defined('CMS_USER_KEY') || !isset($_SESSION[CMS_USER_KEY]) || !$_SESSION[CMS_USER_KEY]) {
			throw new LogicException('This method can only be called for admin requests');
		}

		if ($rem1 == null) {
			$rem1 = CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
		}

		$out = str_replace([CMS_ROOT_URL, $rem1, '?&', '&&'], ['', '', '?', '&'], $in_url);
		$out = trim($out, '/&? ');
		if (endswith($out, '&amp;')) {
			$out = substr($out, 0, -5);
		}
		return $out;
	}

	/**
	 * Convert a generic URL into one that is suitable for this user's session.
	 *
	 * @param string $in_url The generic url. Usually retrieved from a preference or from the database
	 * @return string A URL that has a session key in it.
	 */
	public static function get_session_url(string $in_url): string
	{
		if (!defined('CMS_USER_KEY') || !isset($_SESSION[CMS_USER_KEY]) || !$_SESSION[CMS_USER_KEY]) {
			throw new LogicException('This method can only be called for admin requests');
		}
		return $in_url.'&'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
	}

	/**
	 * Get the latest available CMSMS version.
	 * This method does a remote request to the version check URL at most once per day.
	 *
	 * @return string
	 */
	public static function fetch_latest_cmsms_ver(): string
	{
		$last_fetch = (int) AppParams::get('last_remotever_check');
		$remote_ver = AppParams::get('last_remotever');
		if ($last_fetch < (time() - 24 * 3600)) {
			$req = new HttpRequest();
// use default	$req->setTimeout(10);
			$req->execute(CMS_DEFAULT_VERSIONCHECK_URL);
			if ($req->getStatus() == 200) {
				$remote_ver = trim($req->getResult());
				if (strpos($remote_ver, ':') !== false) {
					[$tmp, $remote_ver] = explode(':', $remote_ver, 2);
					$remote_ver = trim($remote_ver);
				}
				AppParams::set('last_remotever', $remote_ver);
				AppParams::set('last_remotever_check', time());
			}
		}
		return $remote_ver;
	}

	/**
	 * Report whether a newer version of CMSMS than presently running is available
	 *
	 * @return bool
	 */
	public static function site_needs_updating(): bool
	{
		$remote_ver = self::fetch_latest_cmsms_ver();
		return version_compare(CMS_VERSION, $remote_ver) < 0;
	}

	/**
	 * Get a tag representing a themed icon or module icon
	 * @deprecated since 3.0 instead use CMSMS\AdminTheme::get_icon()
	 *
	 * @param string $icon the basename of the desired icon file, may include theme-dir-relative path,
	 *  may omit file type/suffix, ignored if smarty variable $actionmodule is currently set
	 * @param array $attrs Since 3.0 Optional assoc array of attributes for the created img tag
	 * @return string
	 */
	public static function get_icon(string $icon, array $attrs = []): string
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'CMSMS\AdminTheme::get_icon'));
		$themeObject = Lone::get('Theme');
		return $themeObject->get_icon($icon, $attrs);
	}

	/**
	 * Get a help tag for displaying inline popup help.
	 *
	 * This method accepts variable arguments. Recognized keys are
	 " 'key1'|'realm', 'key'|'key2', 'title'|'titlekey'
	 * If neither 'key1'|'realm' is provided, the fallback will be the
	 * current action-module name, or else 'help'
	 * If neither 'title'|'titlekey' is provided, the fallback will be 'key'|'key2'
	 *
	 * @param $args string(s) varargs
	 * @return string HTML content of the help tag, or empty
	 */
	public static function get_help_tag(...$args): string
	{
		if (!AppState::test(AppState::ADMIN_PAGE)) return '';

		$themeObject = Lone::get('Theme');
		if (!is_object($themeObject)) return '';

		$icon = $themeObject->get_icon('info', ['class'=>'cms_helpicon']);
		if (!$icon) return '';

		$params = [];
		if (count($args) >= 2 && is_string($args[0]) && is_string($args[1])) {
			$params['key1'] = $args[0];
			$params['key2'] = $args[1];
			if (isset($args[2])) $params['title'] = $args[2];
		} elseif (count($args) == 1 && is_string($args[0])) {
			$params['key2'] = $args[0];
		} else {
			$params = $args[0];
		}

		$key1 = '';
		$key2 = '';
		$title = '';
		foreach ($params as $key => $value) {
			switch($key) {
			case 'key1':
			case 'realm':
				$key1 = trim($value); // TODO handle any (unlikely) '"' in key
				break;
			case 'key':
			case 'key2':
				$key2 = trim($value); // ibid
				break;
			case 'title':
			case 'titlekey':
				$title = str_replace('"', '&quot;', trim($value));
			}
		}

		if (!$key1) {
			$smarty = Lone::get('Smarty');
			$module = $smarty->getTemplateVars('_module');
			if ($module) {
				$key1 = $module;
			} else {
				$key1 = 'help'; //default translation-domain for popup help
			}
		}

		if (!$key1) return '';

		if ($key2 !== '') { $key1 .= '__'.$key2; }
		if ($title === '') {
			$title = ($key2) ? $key2 : 'for this'; //TODO lang
		}

		return '<span class="cms_help" data-cmshelp-key="'.$key1.'" data-cmshelp-title="'.$title.'">'.$icon.'</span>';
	}

	/**
	 * Remove files from the website directories defined as
	 * TMP_CACHE_LOCATION, TMP_TEMPLATES_C_LOCATION, PUBLIC_CACHE_LOCATION
	 * @since 3.0
	 *
	 * @param $age_days Optional cache-item-modification threshold (days), 0 to whatever.
	 *  Default 0 hence 'now'.
	 */
	public static function clear_cached_files(int $age_days = 0)
	{
		if (!AppState::test_any(AppState::ADMIN_PAGE | AppState::ASYNC_JOB | AppState::INSTALL)
		 || !defined('TMP_CACHE_LOCATION')) { // relevant permission(s) check too ?
			$name = __METHOD__;
			throw new ErrorException("Method $name may not be used");
		}

		$age_days = max(0, $age_days);
		HookOperations::do_hook('clear_cached_files', ['older_than' => $age_days]); //TODO BAD no namespace, some miscreant handler can change the parameter ...  deprecate?
		Events::SendEvent('Core', 'ClearCachedFiles', ['older_than' => $age_days]); //since 3.0
		$ttl = $age_days * 24 * 3600;
		$the_time = time() - $ttl;
		$dirs = array_unique([TMP_CACHE_LOCATION, TMP_TEMPLATES_C_LOCATION, PUBLIC_CACHE_LOCATION]);
		foreach ($dirs as $start_dir) {
			$iter = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($start_dir,
					FilesystemIterator::KEY_AS_FILENAME |
					FilesystemIterator::SKIP_DOTS
				),
				RecursiveIteratorIterator::LEAVES_ONLY |
				RecursiveIteratorIterator::SELF_FIRST);
			foreach ($iter as $fn => $inf) {
				if ($inf->isFile() && $inf->getMTime() <= $the_time) {
					if (!fnmatch('index.htm?', $fn)) {
						unlink($inf->getPathname());
					} else {
						touch($inf->getPathname());
					}
				}
			}
		}
	}

	/**
	 * Generate a hierarchical ordered ajax-populated dropdown representing some
	 * or all site pages, with fallback to text input.
	 *
	 * If $current or $parent parameters are provided, care is taken to ensure
	 * that children which could cause a loop are hidden, when creating a dropdown
	 * for changing a page's parent.
	 *
	 * This method uses the CMSMS jQuery hierselector widget.
	 * @since 3.0 This method was migrated from the ContentOperations class
	 *
	 * @param mixed The int|string id of the content object we are working with. Default 0.
	 *   Used with $allow_current to ignore this object and its descendants.
	 * @param mixed $selected The int|string id of the currently selected content object. Or -1 if none. Default 0.
	 * @param string $name The html name of the created dropdown. Default 'parent_id'. ('m1_' will be prepended)
	 * @param bool $allow_current Ensures that the current value cannot be selected, or $current and its children.
	 *   Used to prevent circular deadlocks.
	 * @param bool $use_perms If true, check page-edit permission and show
	 *  only the pages that the current user may edit. Default false.
	 * @param bool $allow_all Whether to also show items which don't have a
	 *  valid link. Default false.
	 * @param bool $for_child since 2.2 Whether to obey the WantsChildren()
	 *  result reported by each content object. Default false.
	 * @return string js and html to display an ajax-populated dropdown, with fallback to text-input
	 */
	public static function CreateHierarchyDropdown(
		$current = 0,
		$selected = 0,
		string $name = 'parent_id',
		bool $allow_current = false,
		bool $use_perms = false,
		bool $allow_all = false,
		bool $for_child = false): string
	{
		// static properties here >> Lone property|ies ?
		static $count = 1;

		$userid = get_userid(false);
		$elemid = 'cms_hierdropdown'.$count++;
		$elemtitle = lang('title_hierselect');
		$first = ($count == 1) ? 'true' : 'false';
		$modify_all = check_permission($userid, 'Manage All Content') || check_permission($userid, 'Modify Any Page');
		$popuptitle = lang('title_hierselect_select');
		$script_url = cms_get_script('jquery.cmsms_hierselector.js');
		$selected = (int)$selected;

		$opts = [
			'allow_all' => ($allow_all) ? 'true' : 'false',
			'allow_current' => ($allow_current) ? 'true' : 'false',
			'current' => (int)$current,
			'for_child' => ($for_child) ? 'true' : 'false',
			'is_manager' => ($modify_all) ? 'true' : 'false',
			'selected' => $selected,
			'use_perms' => ($use_perms) ? 'true' : 'false',
			'use_simple' => ($modify_all) ? 'false' : 'true',
		];

		$str = '{';
		foreach ($opts as $key => $val) {
			$str .= "\n  ".$key.':'.$val.', ';
		}
		$str = rtrim($str, ' , ')."\n }";

		// scrappy layout here to make the page-sourceview prettier
		if ($first) {
			$out = <<<EOS

<script src="$script_url"></script>
EOS;
		} else {
			$out = <<<EOS

EOS;
		}
		$out .= <<<EOS

<script>
$(function() {
EOS;
		if ($first) {
			$out .= <<<EOS

 cms_data.ajax_hiersel_url = 'ajax_hier_content.php';
 cms_data.lang_hierselect_title = '$popuptitle';
EOS;
		}
		$out .= <<<EOS

 $('#{$elemid}').hierselector($str);
});
</script>
EOS;
add_page_foottext($out);

		return <<<EOS

<input type="text" id="$elemid" class="cms_hierdropdown" name="$name" title="$elemtitle" value="$selected" size="8" maxlength="8">

EOS;
	}
} // class
//if (!\class_exits('CmsAdminUtils')) \class_alias(AdminUtils::class, 'CmsAdminUtils', false);
//if (!\class_exits('cms_admin_utils') \class_alias(AdminUtils::class, 'cms_admin_utils', false);
