<?php
namespace cms_installer;

use ArrayAccess;
use RuntimeException;

final class session implements ArrayAccess
{
    const SSLCIPHER = 'AES-256-CTR';

    private static $_instance;

    private $_crypter = '';
    private $_key = false;
    private $_data = null;

    #[\ReturnTypeWillChange]
    private function __construct()
    {
        if (PHP_VERSION_ID >= 70200 && function_exists('sodium_crypto_secretbox')) {
            $this->_crypter = 'sodium';
        } elseif (function_exists('openssl_encrypt')) {
            $this->_crypter = 'openssl';
        }
    }

    #[\ReturnTypeWillChange]
    private function __clone()
    {
    }

    public static function get_instance() : self
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Scrub all our data from $_SESSION, but don't touch the local copy in self::$_data
     */
    public function clear()
    {
        $this->_start();
        unset($_SESSION[$this->_key]);
    }

    /**
     * Scrub all our data from $_SESSION and from the local self::$_data too
     */
    public function reset()
    {
        $this->clear();
        $this->_data = null;
    }

    //ArrayAccess methods

    #[\ReturnTypeWillChange]
    public function offsetExists($key)// : bool
    {
        $this->_expand();
        return isset($this->_data[$key]);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($key)// : mixed
    {
        $this->_expand();
        return $this->_data[$key] ?? null;
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($key, $value)// : void
    {
        $this->_expand();
        $this->_data[$key] = $value;
        $this->_save();
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($key)// : void
    {
        $this->_expand();
        if (isset($this->_data[$key])) {
            unset($this->_data[$key]);
            $this->_save();
        }
    }

    /**
     * @ignore
     * @throws RuntimeException
     */
    private function _start()
    {
        if (!$this->_key) {
            $session_key = substr(md5(__DIR__), 0, 10);
            session_name('CMSIC'.$session_key);
            session_cache_limiter('nocache');
            if (@session_id()) {
                $res = null;
            } else {
                $res = @session_start();
            }
            if (!$res) {
                throw new RuntimeException('Problem starting the session (system configuration problem?)');
            }
            $this->_key = 'k'.md5(session_id());
        }
    }

    private function _collapse()
    {
        if ($this->_data) {
            $this->_start();
            $this->_save();
        }
        $this->_data = null;
    }

    private function _sodium_extend(string $passwd, string $seed)
    {
        if (PHP_VERSION_ID >= 70200 && function_exists('sodium_crypto_secretbox')) {
            $lr = strlen($passwd); //TODO handle mb_ override
            $j = max(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
            while ($lr < $j) {
                $passwd .= $passwd;
                $lr += $lr;
            }
            $c = $passwd[(int)$j / 2];
            if ($c == '\0') {
                $c = '\7e';
            }
            $t = substr(($passwd ^ $seed), 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $nonce = strtr($t, '\0', $c);
            $t = substr($passwd, 0, SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
            $key = strtr($t, '\0', $c);
            return [$nonce, $key];
        }
        return [];
    }

    private function _save()
    {
        $raw = serialize($this->_data);
        // TODO something random(ish), session-constant, not sourced directly or indirectly from $_SESSION: a cookie?
        $seed = __DIR__;
        switch ($this->_crypter) {
            case 'sodium':
                $pw = session_id();
                list($nonce, $key) = $this->_sodium_extend($pw, $seed);
                $str = sodium_crypto_secretbox($raw, $nonce, $key);
                break;
            case 'openssl':
                $pw = session_id().$seed;
                $l1 = openssl_cipher_iv_length(self::SSLCIPHER);
                $str = openssl_encrypt($raw, self::SSLCIPHER, $pw, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, substr($pw, 0, $l1));
                break;
            default: //CRAP obfuscation only!
                $pw = session_id().$seed;
                $l1 = strlen($pw);
                $l2 = strlen($raw);
                while ($l1 < $l2) {
                    $pw .= $pw;
                    $l1 += $l1;
                }
                $l2 = min($l1, $l2);
                $str = $raw;
                for ($i = 0; $i < $l2; ++$i) {
                    $str[$i] = $str[$i] ^ $pw[$i];
                }
                break;
        }

        $_SESSION[$this->_key] = $str;
    }

    private function _expand()
    {
        if (!is_array($this->_data)) {
            $this->_start();
            if (isset($_SESSION[$this->_key])) {
                $this->_load();
            } else {
                $this->_data = [];
            }
        }
    }

    private function _load()
    {
        $raw = $_SESSION[$this->_key] ?? null;
        if ($raw) {
            // TODO conform to _save() seed
            $seed = __DIR__;
            switch ($this->_crypter) {
                case 'sodium':
                    $pw = session_id();
                    list($nonce, $key) = $this->_sodium_extend($pw, $seed);
                    $str = sodium_crypto_secretbox_open($raw, $nonce, $key);
                    break;
                case 'openssl':
                    $pw = session_id().$seed;
                    $l1 = openssl_cipher_iv_length(self::SSLCIPHER);
                    $str = openssl_decrypt($raw, self::SSLCIPHER, $pw, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, substr($pw, 0, $l1));
                    break;
                default: //CRAP obfuscation only!
                    $pw = session_id().$seed;
                    $l1 = strlen($pw);
                    $l2 = strlen($raw); //TODO check embedded null's ok
                    while ($l1 < $l2) {
                        $pw .= $pw;
                        $l1 += $l1;
                    }
                    $l2 = min($l1, $l2);
                    $str = $raw;
                    for ($i = 0; $i < $l2; ++$i) {
                        $str[$i] = $str[$i] ^ $pw[$i];
                    }
                    break;
            }
            $this->_data = unserialize($str); // may except-out
            return;
        }

        $this->_data = null;
    }
} // class
