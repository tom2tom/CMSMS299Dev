<?php
/*
System utilities class.
Copyright (C) 2010-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS;

//use CMSMS\DeprecationNotice;

//use CMSMS\NlsOperations;
use CMSMS\AdminTheme;
use CMSMS\AppConfig;
use CMSMS\CoreCapabilities;
use CMSMS\Database\Connection;
use CMSMS\internal\Smarty;
use CMSMS\Lone;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use IntlDateFormatter;
//use Mailchimp_User_MissingEmail;
use Throwable;
//use const CMS_DEPREC;
use function cmsms;

/**
 * A class of static utility/convenience methods.
 *
 * @package CMS
 * @license GPL
 * @author  Robert Campbell
 *
 * @final
 * @since 3.0
 * @since 1.9 as global-namespace cms_utils
 */
final class Utils
{
	/**
	 * Cache an intra-request value
	 * This method is typically used to store data for later use by
	 * another part of the system.
	 * @see also: set_session_value()
	 * @since 1.9
	 *
	 * @param string $key The name/identifier of the value
	 * @param mixed  $value The value to store
	 */
	public static function set_app_data(string $key, $value)
	{
		Lone::set($key, $value);
	}

	/**
	 * Return an intra-request cached value (if it exists)
	 * @see also: get_session_value()
	 * @since 1.9
	 *
	 * @param string $key The name/identifier of the value
	 * @return mixed The stored value | null
	 */
	public static function get_app_data(string $key)
	{
		return Lone::fastget($key);
	}

	/**
	 * Return the database connection singleton.
	 * @see Lone::get('Db')
	 * @since 1.9
	 *
	 * @return mixed CMSMS\Database\Connection object | null
	 */
	public static function get_db() : Connection
	{
		return Lone::get('Db');
	}

	/**
	 * Return the application config singleton.
	 * @see Lone::get('Config')
	 * @since 1.9
	 *
	 * @return AppConfig The global configuration object.
	 */
	public static function get_config() : AppConfig
	{
		return Lone::get('Config');
	}

	/**
	 * Return the application Smarty singleton.
	 * @see Lone::get('Smarty')
	 * @since 1.9
	 *
	 * @return Smarty handle to the Smarty object
	 */
	public static function get_smarty() : Smarty
	{
		return Lone::get('Smarty');
	}

	/**
	 * Return the current content object
	 * @since 1.9
	 *
	 * @return mixed Content The current content object | null
	 *  Always null if called from an admin script/module-action
	 */
	public static function get_current_content()
	{
		return cmsms()->get_content_object();
	}

	/**
	 * Return the alias of the current page
	 * @since 1.9
	 *
	 * @return mixed string | null  Always null if called from
	 *  an admin script/module-action
	 */
	public static function get_current_alias()
	{
		$obj = cmsms()->get_content_object();
		if( $obj ) return $obj->Alias();
	}

	/**
	 * Return the id of the current page
	 * @since 1.9
	 *
	 * @return mixed int | null Always null if called from
	 *  an admin script/module-action
	 */
	public static function get_current_pageid()
	{
		return cmsms()->get_content_id();
	}

	/**
	 * Report whether a module is available.
	 * @see get_module(), Lone::get('LoadedData')->get('modules')
	 * @since 1.11
	 *
	 * @param string $name The module name
	 * @return bool
	 */
	public static function module_available(string $name) : bool
	{
		return Lone::get('ModuleOperations')->IsModuleActive($name);
	}

	/**
	 * Return an installed module object.
	 * If a version string is passed, a matching object will only be
	 * returned if the installed version is greater than or equal to
	 * the specified version.
	 * @see version_compare()
	 * @see ModuleOperations::get_module_instance()
	 * @since 1.9
	 *
	 * @param string $name The module name
	 * @param string $version An optional version string
	 * @return mixed CMSModule The matching module object | null
	 */
	public static function get_module(string $name, string $version = '')
	{
		return Lone::get('ModuleOperations')->get_module_instance($name, $version);
	}

	/**
	 * Return the appropriate WYSIWYG module
	 *
	 * For frontend requests this method will return the currently selected
	 * frontend WYSIWYG or null if none is selected.
	 * For admin requests this method will return the user's selected
	 * WYSIWYG module, or null.
	 * @since 1.10
	 *
	 * @param mixed $module_name Optional module name | null Default null
	 * @return mixed CMSModule | null
	 */
	public static function get_wysiwyg_module($module_name = null)
	{
		return Lone::get('ModuleOperations')->GetWYSIWYGModule($module_name);
	}

	/**
	 * Return the currently-selected syntax highlight module
	 * @since 1.10
	 *
	 * @return mixed CMSModule | null
	 */
	public static function get_syntax_highlighter_module()
	{
		return Lone::get('ModuleOperations')->GetSyntaxHighlighter();
	}

	/**
	 * Return the currently selected search module
	 * @since 1.10
	 *
	 * @return mixed CMSModule | null
	 */
	public static function get_search_module()
	{
		return Lone::get('ModuleOperations')->GetSearchModule();
	}

	/**
	 * Return the currently-selected filepicker module.
	 * @since 2.2
	 *
	 * @return mixed CMSModule | null
	 */
	public static function get_filepicker_module()
	{
		return Lone::get('ModuleOperations')->GetFilePickerModule();
	}

	/**
	 * Return the first-detected email-sender module (if any)
	 * @since 3.0
	 *
	 * @return mixed CMSModule | null
	 */
	public static function get_email_module()
	{
		$modnames = Lone::get('LoadedMetadata')->get('capable_modules', false, CoreCapabilities::EMAIL_MODULE);
		if ($modnames) {
			return Lone::get('ModuleOperations')->get_module_instance($modnames[0]);
		}
	}

	/**
	 * Send email message(s) using the selected system email-sender,
	 *  if any, or else by PHP mail()
	 * @see https://www.php.net/manual/en/function.mail
	 * @since 3.0
	 *
	 * @param string $to one or more (in which case comma-separated)
	 *  destination(s), each like emailaddress or Name <emailaddress>
	 * @param string $subject plaintext subject
	 * @param string $message plaintext or html message body-content
	 * @param mixed array|string $additional_headers optional other header(s)
	 *  for the message e.g. From, Cc, ...
	 * @param string $additional_params optional extra params for sendmail
	 *  if that's the currently-selected backend processor
	 * @return bool indicating message was accepted for delivery
	 */
	public static function send_email(string $to, string $subject, string $message,
	   $additional_headers = [], string $additional_params = '') : bool
	{
		$mod = self::get_email_module();
		if ($mod) {
			$classname = $mod->GetName().'\Mailer';
			try {
				$mailer = new $classname();
				return $mailer->send_simple($to, $subject, $message, $additional_headers, $additional_params);
			} catch (Throwable $t) {
				return false;
			}
		} else {
			//TODO cleanup params e.g. subject per RFC 2047, trim'd $message with \r\n separators
			return mail($to, $subject, $message, $additional_headers, $additional_params);
		}
	}

	/**
	 * Attempt to retrieve the IP address of the connected user
	 * This method attempts to compensate for proxy servers.
	 * @since 1.10
	 *
	 * @return mixed IP address in dotted notation | null
	 */
	public static function get_real_ip()
	{
		$ip = $_SERVER['REMOTE_ADDR'];
		if (empty($ip) && !empty($_SERVER['HTTP_CLIENT_IP'])) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		}
		elseif (empty($ip) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		if (filter_var($ip, FILTER_VALIDATE_IP)) {
			return $ip;
		}
	}

	/**
	 * Return the current admin theme object or a named theme object
	 * @see also AdminTheme::get_instance()
	 * @since 1.11
	 *
	 * @param mixed $name Since 3.0 Optional theme name. Default ''
	 * @return mixed AdminTheme-derived object | null
	 *  Always null if not called from an admin script/module-action
	 */
	public static function get_theme_object($name = '')
	{
		return AdminTheme::get_instance($name);
	}

	/**
	 *@ignore
	 */
	private static function swap($fmt) : string
	{
		if (!$fmt) {
			return ''.$fmt;
		}
		$from = [
		'%a', // \1
		'%A', // \2
		'%d',
		'%e',
		'%j',
		'%u',
		'%w',
		'%W', // \10
		'%b', // \3
		'%h', // \3
		'%B', // \4
		'%m',
		'%y',
		'%Y',
		'%D',
		'%F',
		'%x', // \6
		'%H',
		'%k',
		'%I',
		'%l',
		'%M',
		'%p', // \0e
		'%P', // \0f
		'%r',
		'%R',
		'%S',
		'%T',
		'%X', // \7
		'%z',
		'%Z',
		'%c', // \8
		'%s',
		'%n',
		'%t',
		'%%',
		'%C', // \11
		'%g', // \12
		'%G',
		'%U', // \13
		'%V',
		];

		$to = [
		"\1",
		"\2",
		'd',
		'j', // interim
		'z',
		'N',
		'w',
		"\x10",
		"\3",
		"\3",
		"\4",
		'm',
		'y',
		'Y',
		'm/d/y',
		'Y-m-d',
		"\6",
		'H',
		'G',
		'h',
		'g',
		'i',
		"\x0e",
		"\x0f",
		'h:i:s A',
		'H:i',
		's',
		'H:i:s',
		"\7",
		'O',
		'T',
		"\x8",
		'U',
		"\n",
		"\t",
		'&#37;', // '%' chars are valid but may confuse e.g. Smarty date-munger
		"\x11",
		"\x12",
		'o',
		"\x13",
		'W',
		];
		if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
	// TODO robustly derive values for Windows OS
	/* see
	https://docs.microsoft.com/en-us/cpp/c-runtime-library/reference/strftime-wcsftime-strftime-l-wcsftime-l?redirectedfrom=MSDN&view=msvc-170
	re other uses of '#' modifier
	*/
			$to[3] = '#d'; // per php.net: correctly relace %e on Windows
		}
		return str_replace($from, $to, $fmt);
	}

	/**
	 *@ignore
	 */
	private static function custom(int $st, string $mode) : string
	{
		if (extension_loaded('Intl')) {
			$zone = Lone::get('Config')['timezone'];
			$dt = new DateTime(null, new DateTimeZone($zone));
			$dt->setTimestamp($st);
			$locale = NlsOperations::get_current_language();
			switch ($mode) {
			case "\1": // short day name
				return datefmt_format_object($dt, 'EEE', $locale);
			case "\2": // normal day name
				return datefmt_format_object($dt, 'EEEE', $locale);
			case "\3": // short month name
				return datefmt_format_object($dt, 'MMM', $locale);
			case "\4": // normal month name
				return datefmt_format_object($dt, 'MMMM', $locale);
			case "\6": // date only
				return datefmt_format_object($dt,
					[IntlDateFormatter::FULL, IntlDateFormatter::NONE], $locale);
			case "\7": // time only
				return datefmt_format_object($dt,
					[IntlDateFormatter::NONE, IntlDateFormatter::MEDIUM], $locale);
			case "\x8": // date and time
				return datefmt_format_object($dt,
					[IntlDateFormatter::FULL, IntlDateFormatter::MEDIUM], $locale);
			case "\x0e": // am/pm, upper-case
			case "\x0f": // am/pm, lower-case
				$s = datefmt_format_object($dt, 'a', $locale);
				if ($mode == "\x0e") {
					// force upper-case, any charset
					if (!preg_match('/[\x80-\xff]/',$s)) { return strtoupper($s); }
					elseif (function_exists('mb_strtoupper')) { return mb_strtoupper($s); }
				}
				else {
					// force lower-case, any charset
					if (!preg_match('/[\x80-\xff]/',$s)) { return strtolower($s); }
					elseif (function_exists('mb_strtolower')) { return mb_strtolower($s); }
				}
				return $s;
			default:
				return 'Unknown Format';
			}
		} elseif (function_exists('nl_langinfo')) { // not Windows OS
			switch ($mode) {
			case "\1": // short day name
				$n = date('w', $st) + 1;
				$fmt = constant('ABDAY_'.$n);
				return nl_langinfo($fmt);
			case "\2": // normal day name
				$n = date('w', $st) + 1;
				$fmt = constant('DAY_'.$n);
				return nl_langinfo($fmt);
			case "\3": // short month name
				$n = date('n', $st);
				$fmt = constant('ABMON_'.$n);
				return nl_langinfo($fmt);
			case "\4": // normal month name
				$n = date('n', $st);
				$fmt = constant('MON_'.$n);
				return nl_langinfo($fmt);
			case "\6": // date without time
				$fmt = nl_langinfo(D_FMT);
				$fmt = self::swap($fmt);
				return date($fmt);
			case "\7": // time without date
				$fmt = nl_langinfo(T_FMT);
				$fmt = self::swap($fmt);
				return date($fmt);
			case "\x8": // date and time
				$fmt = nl_langinfo(D_T_FMT);
				$fmt = self::swap($fmt);
				return date($fmt);
			case "\x0e": // am/pm, upper-case
			case "\x0f": // am/pm, lower-case
				$s = date('A', $st);
				$fmt = ($s == 'AM') ? AM_STR : PM_STR;
				$s = nl_langinfo($fmt);
				if ($mode == "\x0e") {
					// force upper-case, any charset
					if (!preg_match('/[\x80-\xff]/',$s)) { return strtoupper($s); }
					elseif (function_exists('mb_strtoupper')) { return mb_strtoupper($s); }
				} else {
					// force lower-case, any charset
					if (!preg_match('/[\x80-\xff]/',$s)) { return strtolower($s); }
					elseif (function_exists('mb_strtolower')) { return mb_strtolower($s); }
				}
				return $s;
			default:
				return 'Unknown Format';
			}
		} else {
	// TODO robustly derive values for Windows OS
			switch ($mode) {
			case "\1": // short day name
				return date('D', $st);
			case "\2": // normal day name
				return date('l', $st);
			case "\3": // short month name
				return date('M', $st);
			case "\4": // normal month name
				return date('F', $st);
			case "\6": // date only
				return date('j F Y', $st);
			case "\7": // time only
				return date('H:i:s', $st);
			case "\x8": // date and time
				return date('j F Y h:i a', $st);
			case "\x0e": // am/pm, upper-case
				return date('A', $st);
			case "\x0f": // am/pm, lower-case
				return date('a', $st);
			default:
				return 'Unknown Format';
			}
		}
	}

	/**
	 * Return a formatted date/time representation
	 * @since 3.0
	 *
	 * @param mixed $datevar timestamp | DateTime object | datetime string parsable by strtotime()
	 * @param string $format strftime()- and/or date()-compatible format definition
	 * @param mixed $default_date fallback to use if $datevar is empty. Same types as $datevar
	 * @return string
	 */
	public static function dt_format($datevar, string $format = '%b %e, %Y', $default_date = '') : string
	{
		if (empty($datevar) && $default_date) {
			$datevar = $default_date;
		}
		if (empty($datevar)) {
			$st = time();
		} elseif (is_numeric($datevar)) {
			$st = (int)$datevar;
		} elseif ($datevar instanceof DateTime
		  || (interface_exists('DateTimeInterface', false) && $datevar instanceof DateTimeInterface)
		) {
			$st = $datevar->format('U');
		} else {
			$st = strtotime($datevar);
			if ($st === -1 || $st === false) {
				$st = time();
			}
		}

		$outfmt = self::swap($format);
		$tmp = date($outfmt, $st);
		$text = preg_replace_callback_array([
			'~[\x01-\x08\x0e\x0f]~' => function($m) use($st) {
				return self::custom($st, $m[0]);
			},
			'~\x11~' => function($m) use($st) { // two-digit century
				return floor(date('Y', $st) / 100);
			},
			'~\x12~' => function($m) use($st) { // week of year, per ISO8601
				return substr(date('o', $st), -2);
			},
			'~\x10~' => function($m) use($st) { // week of year, assuming the first Monday is day 0
				 $n1 = date('Y', $st);
				 $n2 = date('z', strtotime('first monday of january '.$n1));
				 $n1 = date('z', $st);
				 return floor(($n2-$n1) / 7) + 1;
			 },
			'~\x13~' => function($m) use($st) { // week of year, assuming the first Sunday is day 0
				$n1 = date('Y', $st);
				$n2 = date('z', strtotime('first sunday of january '.$n1));
				$n1 = date('z', $st);
				return floor(($n2-$n1) / 7) + 1;
			}
		], $tmp);

		return $text;
	}
} // class
//if (!\class_exists('cms_utils', false)) \class_alias(Utils::class, 'cms_utils', false);
