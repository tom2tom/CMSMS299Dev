<?php
/*
Security-related methods.
Copyright (C) 2022-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
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
 * @since 3.0
 * @see https://www.zend.com/blog/libsodium-and-php-encrypt
 */
class Crypto
{
	private const SSLCIPHER = 'chacha20-poly1305';

	/**
	 * @ignore
	 */
	protected static function check_crypter(string $crypter): string
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
			} else {
				$crypter = 'internal';
			}
			break;
		default:
			$crypter = 'internal';
		}
		return $crypter;
	}

	/**
	 * @ignore
	 */
	protected static function sodium_extend(string $passwd): array
	{
		if (PHP_VERSION_ID >= 70200 && function_exists('sodium_crypto_secretbox')) {
			$lr = strlen($passwd); //TODO handle php.ini setting mbstring.func_overload & 2 i.e. overloaded
			$j = max(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, SODIUM_CRYPTO_SECRETBOX_KEYBYTES); // 32,24
			while ($lr < $j) {
				$passwd .= $passwd;
				$lr += $lr;
			}
			$c = $passwd[(int)($j/2)];
			if ($c == "\0") { $c = '~'; }
			// TODO something not site-specific & > SODIUM_CRYPTO_SECRETBOX_NONCEBYTES (24) long
			$local = strtr(CMS_ROOT_URL ^ __CLASS__, ['/'=>'', '\\'=>'']); //not filesystem-specific
			$lr = strlen($local);
			while ($lr < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
				$local .= $local;
				$lr += $lr;
			}
			$t = substr(($passwd ^ $local), 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
			$nonce = strtr($t, "\0", $c);
			$t = substr($passwd, 0, SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
			$key = strtr($t, "\0", $c);
			return [$nonce, $key];
		}
		return [];
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
	public static function encrypt_string(string $raw, string $passwd = '', string $crypter = 'best'): string
	{
		$use = self::pwolish($passwd);
		try {
			$crypter = self::check_crypter($crypter);
		} catch (Throwable $t) {
			return ''; //TODO
		}
		switch ($crypter) {
		case 'sodium':
			[$nonce, $key] = self::sodium_extend($use);
			// idenfifier: prefix 2y\22
			return "2y\x022".sodium_crypto_secretbox($raw, $nonce, $key);
		case 'openssl':
			$l1 = openssl_cipher_iv_length(self::SSLCIPHER);
			$nonce = random_bytes($l1);
			$r = openssl_encrypt($raw, self::SSLCIPHER, $use, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $nonce);
			// idenfifier: prefix 2y\12
			return "2y\x012".$nonce.$r;
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
			// idenfifier: prefix 2y\02
			return "2y\x002".$raw;
		}
	}

	/**
	 * Decrypt the the provided string
	 * @see also Crypto::encrypt_string()
	 *
	 * @param string $raw the string to be processed
	 * @param string $passwd optional
	 * @return mixed string | false
	 */
	public static function decrypt_string(string $raw, string $passwd = '')//: mixed
	{
		if ($raw === '') return '';
		if (strlen($raw) < 5 || $raw[3] != '2' || strncmp($raw, '2y', 2) != 0) {
			return false;
		}
		switch ($raw[2]) {
			case "\2":
				$crypter = 'sodium';
				break;
			case "\1":
				$crypter = 'openssl';
				break;
			case "\0":
				$crypter = 'internal';
				break;
			default:
				return false;
		}
		try {
			$crypter = self::check_crypter($crypter);
		} catch (Throwable $t) {
			return false;
		}
		$raw = substr($raw, 4);
		$use = self::pwolish($passwd);
		switch ($crypter) {
		case 'sodium':
			[$nonce, $key] = self::sodium_extend($use);
			$val = sodium_crypto_secretbox_open($raw, $nonce, $key);
			if ($val === false) {
				sleep(2); // inhibit brute-forcing
			}
			return $val;
		case 'openssl':
			$l1 = openssl_cipher_iv_length(self::SSLCIPHER);
			$nonce = substr($raw, 0, $l1);
			$raw = substr($raw, $l1);
			$val = openssl_decrypt($raw, self::SSLCIPHER, $use, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $nonce);
			if ($val === false) {
				sleep(2);
			}
			return $val;
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
	public static function hash_string(string $raw, bool $seeded = false): string
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
	public static function random_string(int $length, bool $ascii = false, bool $alpha = false): string
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
	public static function scramble_string(string $raw): string
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
	public static function unscramble_string(string $raw): string
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
	 * Duration-safe password-manipulator
	 * @ignore
	 * NOTE CMSMS installer might? need to use an equivalent to this
	 * @param string $raw
	 * @return string 32 bytes
	 */
	private static function pwolish(string $raw): string
	{
		$use = self::pwextend($raw);
		//TODO consider other default e.g.
		//func(trim(file_get_contents(CMS_ASSETS_DIR.DIRECTORY_SEPARATOR.'configs'.DIRECTORY_SEPARATOR.'master.dat')))
		$str = substr(__DIR__, strlen(CMS_ROOT_PATH));
		$str = strtr($str, ['/'=>'', '\\'=>'']); //not site- or filesystem-specific
		$str = hash('tiger128,3', $str); //fast 32-hexit hasher (broken, but who cares, here)
		return (hash_equals($use.'AA', ''.'AA')) ? $str : $use;
	}

	/**
	 * Adjust the length of the provided string
	 * Surrounding whitespace is removed
	 * @ignore
	 *
	 * @param string $raw
	 * @return string 0 or 32 bytes
	 */
	private static function pwextend(string $raw): string
	{
		$raw = trim($raw);
		switch ($lr = strlen($raw)) {
			case 0:
			//waste some time
			for ($i = 1; $i < 3; ++$i) {
				$raw .= 'AA' ^ ''.strlen('AA');
			}
			return '';
			case 1:
			$i = ord($raw);
			$raw .= chr(($i + 3) % 256) . chr(($i + 63) % 256);
			$lr = 3;
			break;
			case 2:
			case 4:
			case 8:
			$i = ord($raw[1]);
			$raw .= chr(($i + $lr) % 256);
			$lr++;
			break;
			default:
			$raw .= ''; // waste some time
		}
		while ($lr < 32) {
			$raw .= strrev($raw);
			$lr += $lr;
		}
		if ($lr > 32) {
			$parts = str_split($raw, 32);
			$lr = count($parts);
			if (strlen($parts[$lr-1]) != 32) {
				$parts[$lr-1] .= $parts[$lr-2];
			}
			$raw = $parts[0];
			for ($i = 1; $i < $lr; ++$i) {
				if ($i & 1) {
					$raw ^= strrev($parts[$i]);
				} else {
					$raw ^= $parts[$i];
				}
			}
		}
		return $raw;
	}
} // class
