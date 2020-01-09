<?php
# Utilities class.
# Copyright (C) 2010-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

use CMSMS\Database\Connection;
use CMSMS\internal\Smarty;
use CMSMS\ModuleOperations;
use CMSMS\ThemeBase;

/**
 * A class of static utility/convenience methods.
 *
 * @package CMS
 * @license GPL
 * @author  Robert Campbell
 *
 * @final
 * @since 1.9
 */
final class cms_utils
{
	/**
	 * @ignore
	 */
	private static $_vars;

	/**
	 * @ignore
	 */
	private function __construct() {}
	/**
	 * @ignore
	 */
	private function __clone() {}

	/**
	 * Get intra-request stored data.
	 *
	 * @since 1.9
	 * @param string $key The key to get.
	 * @return mixed The stored data, or null
	 */
	public static function get_app_data(string $key)
	{
		if( is_array( self::$_vars ) && isset(self::$_vars[$key]) ) return self::$_vars[$key];
	}

	/**
	 * Set data for later use.
	 *
	 * This method is typically used to store data for later use by another part of the application.
	 * This data is not stored in the session, so it only exists during the current request.
	 *
	 * @since 1.9
	 * @param string $key The name of this data.
	 * @param mixed  $value The data to store.
	 */
	public static function set_app_data(string $key,$value)
	{
		if( $key == '' ) return;
		if( !is_array(self::$_vars) ) self::$_vars = [];
		self::$_vars[$key] = $value;
	}

	/**
	 * Return an installed module object.
	 *
	 * If a version string is passed, a matching object will only be returned IF
	 * the installed version is greater than or equal to the supplied version.
	 *
	 * @see version_compare()
	 * @see ModuleOperations::get_module_instance()
	 * @since 1.9
	 * @param string $name The module name
	 * @param string $version An optional version string
	 * @return mixed CmsModule The matching module object or null
	 */
	public static function get_module(string $name,string $version = '')
	{
		return ModuleOperations::get_instance()->get_module_instance($name,$version);
	}

	/**
	 * Report whether a module is available.
	 *
	 * @see get_module(), global_cache::get('modules')
	 * @author calguy1000
	 * @since 1.11
	 * @param string $name The module name
	 * @return bool
	 */
	public static function module_available(string $name)
	{
		return ModuleOperations::get_instance()->IsModuleActive($name);
	}

	/**
	 * Return the current database instance.
	 *
	 * @since 1.9
	 * @return mixed \CMSMS\Database\Connection object or null
	 */
	public static function get_db() : Connection
	{
		return CmsApp::get_instance()->GetDb();
	}

	/**
	 * Return the global CMSMS config.
	 *
	 * @since 1.9
	 * @return cms_config The global configuration object.
	 */
	public static function get_config() : cms_config
	{
		return cms_config::get_instance();
	}

	/**
	 * Return the CMSMS Smarty object.
	 *
	 * @see CmsApp::GetSmarty()
	 * @since 1.9
	 * @return Smarty handle to the Smarty object
	 */
	public static function get_smarty() : Smarty
	{
		return CmsApp::get_instance()->GetSmarty();
	}

	/**
	 * Return the current content object.
	 *
	 * This function will return NULL if called from an admin action
	 *
	 * @since 1.9
	 * @return mixed Content The current content object, or null
	 */
	public static function get_current_content()
	{
		return CmsApp::get_instance()->get_content_object();
	}

	/**
	 * Return the alias of the current page.
	 *
	 * This function will return NULL if called from an admin action
	 *
	 * @since 1.9
	 * @return mixed string|null
	 */
	public static function get_current_alias()
	{
		$obj = CmsApp::get_instance()->get_content_object();
		if( $obj ) return $obj->Alias();
	}

	/**
	 * Return the id of the current page
	 *
	 * This function will return NULL if called from an admin action
	 *
	 * @since 1.9
	 * @return mixed int|null
	 */
	public static function get_current_pageid()
	{
		return CmsApp::get_instance()->get_content_id();
	}

	/**
	 * Return the appropriate WYSIWYG module.
	 *
	 * For frontend requests this method will return the currently selected
	 * frontend WYSIWYG or null if none is selected.
	 * For admin requests this method will return the user's selected
	 * WYSIWYG module, or null.
	 *
	 * @since 1.10
	 * @param mixed $module_name Optional module name | null Default null
	 * @return mixed CMSModule | null
	 */
	public static function get_wysiwyg_module($module_name = null)
	{
		return ModuleOperations::get_instance()->GetWYSIWYGModule($module_name);
	}

	/**
	 * Return the currently selected syntax highlighter.
	 *
	 * @since 1.10
	 * @author calguy1000
	 * @return mixed CMSModule | null
	 */
	public static function get_syntax_highlighter_module()
	{
		return ModuleOperations::get_instance()->GetSyntaxHighlighter();
	}

	/**
	 * Return the currently selected search module.
	 *
	 * @since 1.10
	 * @author calguy1000
	 * @return mixed CMSModule | null
	 */
	public static function get_search_module()
	{
		return ModuleOperations::get_instance()->GetSearchModule();
	}

	/**
	 * Return the currently selected filepicker module.
	 *
	 * @since 2.2
	 * @author calguy1000
	 * @return mixed CMSModule | null
	 */
	public static function get_filepicker_module()
	{
		return ModuleOperations::get_instance()->GetFilePickerModule();
	}

	/**
	 * Attempt to retrieve the IP address of the connected user.
	 * This function attempts to compensate for proxy servers.
	 *
	 * @author calguy1000
	 * @since 1.10
	 * @return string IP address in dotted notation, or null
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

		if( filter_var($ip,FILTER_VALIDATE_IP) ) return $ip;

		return null;
	}

	/**
	 * Return the current theme object or a named theme object.
	 * Only valid during an admin request.
	 *
	 * @author calguy1000
	 * @since 1.11
	 * @param mixed $name Since 2.3 Optional theme name. Default ''
	 * @return mixed ThemeBase derived object, or null
	 */
	public static function get_theme_object($name = '')
	{
		return ThemeBase::get_instance($name);
	}

	/**
	 * Return the url corresponding to the provided site-path
	 *
	 * @since 2.3
	 * @param string $in The input path, absolute or relative
	 * @param string $relative_to Optional absolute path which (relative) $in is relative to
	 * @return string
	 */
	public static function path_to_url(string $in, string $relative_to = '') : string
	{
		return cms_path_to_url($in, $relative_to);
	}

	const SSLCIPHER = 'AES-256-CTR';

	private static function check_crypter(string $crypter)
	{
		switch ($crypter) {
		case 'sodium':
			if (PHP_VERSION_ID >= 70200 && function_exists('sodium_crypto_secretbox')) {
				break;
			} else {
				throw new Exception('Libsodium-based decryption is not available');
			}
		case 'openssl':
			if (function_exists('openssl_encrypt')) {
				break;
			} else {
				throw new Exception('OpenSSL-based decryption is not available');
			}
		case 'best':
			if (PHP_VERSION_ID >= 70200 && function_exists('sodium_crypto_secretbox')) {
				$crypter = 'sodium';
			} elseif (function_exists('openssl_encrypt')) {
				$crypter = 'openssl';
			}
		}
		return $crypter;
	}

	private static function sodium_extend(string $passwd, string $local) : array
	{
		if (PHP_VERSION_ID >= 70200 && function_exists('sodium_crypto_secretbox')) {
			$lr = strlen($passwd); //TODO handle php.ini setting mbstring.func_overload & 2 i.e. overloaded
			$j = max(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES,SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
			while ($lr < $j) {
				$passwd .= $passwd;
				$lr += $lr;
			}
			$c = $passwd[(int)$j/2];
			if ($c == '\0') { $c = '\7e'; }
			$t = substr(($passwd ^ $local),0,SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
			$nonce = strtr($t,'\0',$c);
			$t = substr($passwd,0,SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
			$key = strtr($t,'\0',$c);
			return [$nonce,$key];
		}
		return [];
	}

	/**
	 * Encrypt the the provided string
	 * @since 2.3
	 * @see also cms_utils::decrypt_string()
	 *
	 * @param string $raw the string to be processed
	 * @param string $passwd Optional password
	 * @param string $crypter optional crypt-mode 'sodium' | 'openssl' | 'best' | anything else
	 * @return string
	 */
	public static function encrypt_string(string $raw, string $passwd = '', string $crypter = 'best')
	{
		if ($passwd === '') {
			$str = str_replace(['/','\\'],['',''],__DIR__);
			$passwd = substr($str,strlen(CMS_ROOT_PATH)); //not site- or filesystem-specific
		}

		$crypter = self::check_crypter($crypter);
		switch ($crypter) {
		case 'sodium':
			$localpw = CMS_ROOT_URL.self::class;
			list($nonce, $key) = self::sodium_extend($passwd,$localpw);
			return sodium_crypto_secretbox($raw,$nonce,$key);
		case 'openssl':
			$localpw = CMS_ROOT_URL.self::class;
			$l1 = openssl_cipher_iv_length(self::SSLCIPHER);
			return openssl_encrypt($raw,self::SSLCIPHER,$passwd,OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING,substr($localpw,0,$l1));
		default:
			$p = $lp = strlen($passwd); //TODO handle php.ini setting mbstring.func_overload & 2 i.e. overloaded
			$lr = strlen($raw);
			$j = -1;
			for ($i = 0; $i < $lr; ++$i) {
				$r = ord($raw[$i]);
				if (++$j == $lp) { $j = 0; }
				$k = ($r ^ ord($passwd[$j])) + $p;
				$raw[$i] = chr($k);
				$p = $r;
			}
			return $raw;
		}
	}

	/**
	 * Decrypt the the provided string
	 * @since 2.3
	 * @see also cms_utils::encrypt_string()
	 *
	 * @param string $raw the string to be processed
	 * @param string $passwd optional
	 * @param string $crypter optional crypt-mode identifier 'sodium' | 'openssl' | 'best' | anything else
	 * @return string
	 */
	public static function decrypt_string(string $raw, string $passwd = '', string $crypter = 'best')
	{
		if ($passwd === '') {
			$str = str_replace(['/','\\'],['',''],__DIR__);
			$passwd = substr($str,strlen(CMS_ROOT_PATH)); //not site- or filesystem-specific
		}

		$crypter = self::check_crypter($crypter);
		switch ($crypter) {
		case 'sodium':
			$localpw = CMS_ROOT_URL.self::class;
			list($nonce, $key) = self::sodium_extend($passwd,$localpw);
			$val = sodium_crypto_secretbox_open($raw,$nonce,$key);
			if ($val !== false) {
				return $val;
			}
			sleep(2); // inhibit brute-forcing
			return null;
		case 'openssl':
			$localpw = CMS_ROOT_URL.self::class;
			$l1 = openssl_cipher_iv_length(self::SSLCIPHER);
			$val = openssl_decrypt($raw,self::SSLCIPHER,$passwd,OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING,substr($localpw,0,$l1));
			if ($val !== false) {
				return $val;
			}
			sleep(2);
			return null;
		default:
			$p = $lp = strlen($passwd); //TODO handle php.ini setting mbstring.func_overload & 2 i.e. overloaded
			$lr = strlen($raw);
			$j = -1;

			for ($i = 0; $i < $lr; ++$i) {
				$k = ord($raw[$i]) - $p;
				if ($k < 0) {
					$k += 256;
				}
				if (++$j == $lp) { $j = 0; }
				$p = $k ^ ord($passwd[$j]);
				$raw[$i] = chr($p);
			}
			return $raw;
		}
	}

	/**
	 * Hash the the provided string
	 * @since 2.3
	 *
	 * @param string $raw the string to be processed, may be empty
	 * @param bool $seeded optional flag whether to seed the hash. Default false (unless $raw is empty)
	 * @return string (12 alphanum bytes)
	 */
	public static function hash_string(string $raw, $seeded = false)
	{
		if ($raw === '' || $seeded) {
			$seed = '1234';
			for ($i = 0; $i < 4; ++$i) {
				$n = mt_rand(48, 122); // 0 .. z
				$seed[$i] = chr($n);
			}
			$raw .= $seed;
		}
		$str = hash('fnv132', $raw);
		return base_convert($str.$str, 16, 36);
	}

	/**
	 * Generate a random string.
	 * This is intended for seeds, ID's etc. Not encryption-grade.
	 * @since 2.3
	 *
	 * @param int $length No. of bytes in the returned string
	 * @param bool $alnum Optional flag whether to limit the contents to ASCII alphanumeric chars. Default false.
	 * @return string
	 */
	public static function random_string(int $length, bool $alnum = false) : string
	{
		$str = str_repeat(' ', $length);
		for ($i = 0; $i < $length; ++$i) {
			if ($alnum) {
				$n = mt_rand(48, 122);
				if (($n >= 58 && $n <= 64) || ($n >= 91 && $n <= 96)) {
					--$i;
					continue;
				}
			} else {
				$n = mt_rand(33, 254);
				switch ($n) {
					case 34:
					case 38:
					case 39:
					case 44:
					case 63:
					case 96:
					case 127:
						--$i;
						continue 2;
				}
			}
			$str[$i] = chr($n);
		}
		return $str;
	}
} // class
