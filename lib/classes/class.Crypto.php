<?php
/*
Security-related methods.
Copyright (C) 2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS;

use Exception;
use const CMS_ROOT_PATH;
use const CMS_ROOT_URL;

/**
 * A class of static lightweight security-related methods.
 *
 * @package CMS
 * @license GPL
 *
 * @since 2.99
 */
class Crypto
{
	private const SSLCIPHER = 'AES-256-CTR';

	protected static function check_crypter(string $crypter)
	{
		switch ($crypter) {
		case 'sodium':
			if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 70200 && function_exists('sodium_crypto_secretbox')) {
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
			if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 70200 && function_exists('sodium_crypto_secretbox')) {
				$crypter = 'sodium';
			} elseif (function_exists('openssl_encrypt')) {
				$crypter = 'openssl';
			}
		}
		return $crypter;
	}

	protected static function sodium_extend(string $passwd, string $local) : array
	{
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

	/**
	 * Encrypt the the provided string
	 * @see also Crypto::decrypt_string()
	 *
	 * @param string $raw the string to be processed
	 * @param string $passwd Optional password
	 * @param string $crypter optional crypt-mode 'sodium' | 'openssl' | 'best' | anything else e.g. 'internal'
	 * @return string
	 */
	public static function encrypt_string(string $raw, string $passwd = '', string $crypter = 'best')
	{
		$use = self::pwolish($passwd);
		try {
			$crypter = self::check_crypter($crypter);
		} catch (Throwable $t) {
			return ''; //TODO
		}
		switch ($crypter) {
		case 'sodium':
			$localpw = CMS_ROOT_URL.__CLASS__;
			list($nonce, $key) = self::sodium_extend($use,$localpw);
			return sodium_crypto_secretbox($raw,$nonce,$key);
		case 'openssl':
			$localpw = CMS_ROOT_URL.__CLASS__;
			$l1 = openssl_cipher_iv_length(self::SSLCIPHER);
			return openssl_encrypt($raw,self::SSLCIPHER,$use,OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING,substr($localpw,0,$l1));
		default:
			$p = $lp = strlen($use); //TODO handle php.ini setting mbstring.func_overload & 2 i.e. overloaded
			$lr = strlen($raw);
			$j = -1;
			for ($i = 0; $i < $lr; ++$i) {
				$r = ord($raw[$i]);
				if (++$j == $lp) { $j = 0; }
				$k = ($r ^ ord($use[$j])) + $p;
				$raw[$i] = chr($k);
				$p = $r;
			}
			return $raw;
		}
	}

	/**
	 * Decrypt the the provided string
	 * @see also Crypto::encrypt_string()
	 *
	 * @param string $raw the string to be processed
	 * @param string $passwd optional
	 * @param string $crypter optional crypt-mode identifier 'sodium' | 'openssl' | 'best' | anything else
	 * @return string
	 */
	public static function decrypt_string(string $raw, string $passwd = '', string $crypter = 'best')
	{
		if ($raw === '') return $raw;
		$use = self::pwolish($passwd);
		$retry = ($crypter == 'best');
		try {
			$crypter = self::check_crypter($crypter);
		} catch (Throwable $t) {
			return ''; //TODO
		}
		switch ($crypter) {
		case 'sodium':
			$localpw = CMS_ROOT_URL.__CLASS__;
			list($nonce, $key) = self::sodium_extend($use,$localpw);
			$val = sodium_crypto_secretbox_open($raw,$nonce,$key);
			if ($val !== false) {
				return $val;
			}
			if (!$retry) {
				sleep(2); // inhibit brute-forcing
				return null;
			}
			// no break here
		case 'openssl':
			$localpw = CMS_ROOT_URL.__CLASS__;
			$l1 = openssl_cipher_iv_length(self::SSLCIPHER);
			$val = openssl_decrypt($raw,self::SSLCIPHER,$use,OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING,substr($localpw,0,$l1));
			if ($val !== false) {
				return $val;
			}
			if (!$retry) {
				sleep(2);
				return null;
			}
			// no break here
		default:
			$p = $lp = strlen($use); //TODO handle php.ini setting mbstring.func_overload & 2 i.e. overloaded
			$lr = strlen($raw);
			$j = -1;

			for ($i = 0; $i < $lr; ++$i) {
				$k = ord($raw[$i]) - $p;
				if ($k < 0) {
					$k += 256;
				}
				if (++$j == $lp) { $j = 0; }
				$p = $k ^ ord($use[$j]);
				$raw[$i] = chr($p);
			}
			return $raw;
		}
	}

	/**
	 * Hash the the provided string. Not encryption-grade.
	 *
	 * @param string $raw the string to be processed, may be empty
	 * @param bool $seeded optional flag whether to seed the hash. Default false (unless $raw is empty)
	 * @return string (12|13 alphanum bytes)
	 */
	public static function hash_string(string $raw, bool $seeded = false)
	{
		if ($raw === '' || $seeded) {
			$seed = '1234';
			for ($i = 0; $i < 4; ++$i) {
				$n = mt_rand(48, 122); // 0 .. z
				$seed[$i] = chr($n);
			}
			$raw = $seed.$raw;
		}
		$str = hash('fnv1a32', $raw); // 8 hexits
		return base_convert($str.strrev($str), 16, 36);
	}

	/**
	 * Generate a random string.
	 * This is intended for seeds, ID's etc. Not encryption-grade.
	 *
	 * @param int $length No. of bytes in the returned string
	 * @param bool $ascii Optional flag whether to limit the contents to 'printable' ASCII chars. Default false.
	 * @param bool $alpha Optional flag whether to limit the contents to alphanum ASCII chars. Default false.
	 * @return string
	 */
	public static function random_string(int $length, bool $ascii = false, bool $alpha = false) : string
	{
		$str = str_repeat(' ', $length);
		for ($i = 0; $i < $length; ++$i) {
			if ($ascii && !$alpha) {
				$n = mt_rand(33, 126);
			} else {
				$n = mt_rand(1, 254);
			}
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
			$str[$i] = chr($n);
		}
		if ($alpha) {
			$val = substr(base64_encode($str), 0, $length);
			$val = preg_replace_callback('~[=/+]~', function() {
				return chr(mt_rand(65, 90));
				}, $val);
			return $val;
		}
		return $str;
	}

	/**
	 * Munge a string. Not for security-purposes.
	 * Uses simple protocol, js-compatible i.e. no lookups, translations.
	 * Method derived from https://github.com/felixmc/cabd.js/blob/master/cabd.js
	 *
	 * @param string $raw the string to be processed, may be empty
	 * @return string
	 */
	public static function scramble_string(string $raw) : string
	{
		$str = $raw;
		$length = strlen($raw);
		$offset = ($length & 1) ? 1 : 2;
		$k = $length - $offset;
		$mid = $length / 2;
		for ($i = 0; $i < $mid; $i++) {
			$str[$i] = $raw[$k - $i - $i];
		}
		$k = $offset - 1 - $length;
		for (; $i < $length; $i++) {
			$str[$i] = $raw[$k + $i + $i];
		}
		return $str;
	}

	/**
	 * Un-munge a string.
	 * For reference, mainly. Probably never used server-side.
	 * @see Crypto::scramble_string();
	 * Method derived from https://github.com/felixmc/cabd.js/blob/master/cabd.js
	 *
	 * @param string $raw the string to be processed, may be empty
	 * @return string
	 */
	public static function unscramble_string(string $raw) : string
	{
		$str = $raw;
		$length = strlen($raw);
		$offset = ($length & 1) ? 0 : 1;
		$k = $length - $offset;
		for ($i = 0; $i < $length; ++$i) {
			$j = ($i & 1) ? ($k + $i) / 2 : ($k - $i - 1) / 2;
			$str[$i] = $raw[$j];
		}
		return $str;
	}

	/**
	 * Non-cryptographic but duration-safe obfuscator
	 * @ignore
	 * NOTE CMSMS installer needs to use an equivalent to this
	 * @param string $raw
	 * @return string
	 */
	private static function pwolish(string $raw) : string
	{
		$str = substr(__DIR__,strlen(CMS_ROOT_PATH));
		$str = strtr($str,['/'=>'','\\'=>'']); //not site- or filesystem-specific
//		$str = self::scramble_string($str.hash('adler32',$str)); // fast 8-hexit hasher
		$str = hash('fnv1a32',$str); // fast 8-hexit hasher
		$str .= str_rot13($str); // 16 hexits
		return (hash_equals($raw.'AA',''.'AA')) ? $str : $raw;
	}
} // class
