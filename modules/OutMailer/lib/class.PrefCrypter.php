<?php
/*
Class for processing crypted preferences related to the OutMailer module
Copyright (C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple module OutMailer.

This OutMailer module is free software; you may redistribute it and/or
modify it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation; either version 3 of that license,
or (at your option) any later version.

This OutMailer module is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

See the GNU Affero General Public License
<http://www.gnu.org/licenses/licenses.html#AGPL> for more details.
*/
namespace OutMailer;

use CMSMS\Crypto;
use function get_module_param;
use function set_module_param;

class PrefCrypter
{
    public const MKEY = 'masterpass';
    protected const SKEY = 'prefsalt';
    protected const HASHALGO = 'tiger192,4'; //48 hexits, quite fast
    protected const MODNAME = __NAMESPACE__;

    /**
     * Get a unique module-specific constant string
     * This must be site- and database-independent, and not accessible
	 * externally or displayed as a class-constant in debugger dumps etc
     * @ignore
     *
     * @return string 20+ bytes
     */
    final protected static function get_muid() : string
    {
        return 'Z6gMrWV7psrRGqUgUhkA'.self::MODNAME;
    }

    // The remainer of this class is generic, and arguably should be shared code

    /**
     * @ignore
     * @return string
     */
    final protected static function get_seed() : string
    {
        $s = self::get_muid().self::SKEY;
        $sk = hash('fnv1a64', $s);
        $t = get_module_param(self::MODNAME, $sk);
        $raw = base64_decode($t.'==');
        $value = Crypto::decrypt_string($raw, hash(self::HASHALGO, $s));
        return $value;
    }

    /**
     * @ignore
     * @param mixed $value
     * @return string
     */
    final protected static function flatten($value) : string
    {
        $s = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (json_last_error() != JSON_ERROR_NONE) {
            $s = json_last_error_msg();
        }
        return $s;
    }

    /**
     * @ignore
     * @param string $value
     * @return mixed
     */
    final protected static function unflatten(string $value)
    {
        return json_decode($value, true); //no relevant flag until PHP 7.3+
    }

    /**
     * Encrypt and save a preference value
     *
     * @param string $key module-preferences key
     * @param mixed $value scalar value to be stored, normally a string
     */
    final public static function encrypt_preference(string $key, $value)
    {
        if (is_scalar($value)) {
            $value = '' . $value;
        } else {
            $value = self::flatten($value); // TODO handle error
        }
        $s = self::get_muid();
        $p = self::get_seed();
        $t = Crypto::encrypt_string($value, hash(self::HASHALGO, $s.$p.$key));
        $p = base64_encode($t);
        set_module_param(self::MODNAME, hash('fnv1a64', $s.$key), rtrim($p, '='));
    }

    /**
     * Retrieve encrypted preference value
     *
     * @param string $key module-preferences key
     * @return plaintext string, or false
     */
    final public static function decrypt_preference(string $key)
    {
        $s = self::get_muid();
        $t = get_module_param(self::MODNAME, hash('fnv1a64', $s.$key));
        $raw = base64_decode($t.'==');
        $p = self::get_seed();
        $value = Crypto::decrypt_string($raw, hash(self::HASHALGO, $s.$p.$key));
        if (is_scalar($value)) { // TODO if definitely a scalar value, not just pretending
            return $value;
        } else {
            return self::unflatten($value); // TODO handle error
        }
    }

    /**
     * Remove an encrypted preference
     *
     * @param object $mod OutMailer module object
     * @param string $key module-preferences key
     */
    final public static function remove_preference($mod, string $key)
    {
        $s = self::get_muid();
        $mod->RemovePreference(hash('fnv1a64', $s.$key));
    }
}
