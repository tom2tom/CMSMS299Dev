<?php

namespace cms_installer;

use ArrayAccess;
//use cms_installer\Crypter;
use RuntimeException;

final class session implements ArrayAccess
{
    private static $_instance;

//    private $_cryptinstance; //for self-managed cryption
    private $_crypter = '';
    private $_key = false;
    private $_data = null;

    private function __construct()
    {
        if( PHP_VERSION_ID >= 70200 && function_exists('sodium_crypto_secretbox') ) {
            $this->_crypter = 'sodium';
        }
/*        elseif( function_exists('openssl_encrypt') ) {
            $this->_crypter = 'openssl';
            $this->_cryptinstance = new Crypter();
        }
*/
    }

    private function __clone() {}

    public static function get_instance()
    {
        if( !self::$_instance ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * @ignore
     * @throws RuntimeException
     */
    private function _start()
    {
        if( !$this->_key ) {
            $session_key = substr(md5(__DIR__),0,10);
            session_name('CMSIC'.$session_key);
            session_cache_limiter('nocache');
            if( @session_id() ) $res = null;
            else $res = @session_start();
            if( !$res ) throw new RuntimeException('Problem starting the session (system configuration problem?)');
            $this->_key = 'k'.md5(session_id());
        }
    }

    private function _collapse()
    {
        if( $this->_data ) {
            $this->_start();
            $this->_save();
        }
        $this->_data = null;
    }

    private function _save()
    {
        $raw = serialize($this->_data);
		// TODO something random(ish), session-constant, not sourced directly or indirectly from $_SESSION: a cookie?
        $seed = __DIR__;
        switch( $this->_crypter ) {
            case 'sodium':
                $pw = session_id();
                $nonce = substr(($pw ^ $seed),0,16);
                $str = sodium_crypto_secretbox($raw,$nonce,$pw);
                break;
/*            case 'openssl':
                $pw = session_id() ^ $seed;
                $str = $this->_cryptinstance->encrypt_value($raw,$pw);
                break;
*/
            default: //CRAP obfuscation only!
				$s1 = session_id().$seed;
				$l1 = strlen($s1);
				$l2 = strlen($raw);
				while( $l1 < $l2 ) {
					$s1 .= $s1;
					$l1 += $l1;
				}
				$l3 = min($l1,$l2);
				$str = $raw;
				for( $i = 0; $i < $l3; ++$i ) {
					$str[$i] = $str[$i] ^ $s1[$i];
				}
                break;
        }

        $_SESSION[$this->_key] = $str;
    }

    private function _expand()
    {
        if( !is_array($this->_data) ) {
            $this->_start();
            if( isset($_SESSION[$this->_key]) ) {
                $this->_load();
            }
            else {
                $this->_data = [];
            }
        }
    }

    private function _load()
    {
        $raw = $_SESSION[$this->_key] ?? null;
        if( $raw ) {
			// TODO conform to _save() seed
			$seed = __DIR__;
            switch( $this->_crypter ) {
                case 'sodium':
                    $pw = session_id();
                    $nonce = substr(($pw ^ $seed),0,16);
                    $str = sodium_crypto_box_open($raw,$nonce,$pw);
                    break;
/*                case 'openssl':
                    $pw = session_id() ^ $seed;
                    $str = $this->_cryptinstance->decrypt_value($raw,$pw);
                    break;
*/
                default: //CRAP obfuscation only!
					$s1 = session_id().$seed;
					$l1 = strlen($s1);
					$l2 = strlen($raw); //TODO check embedded null's ok
					while( $l1 < $l2 ) {
						$s1 .= $s1;
						$l1 += $l1;
					}
					$l3 = min($l1,$l2);
					$str = $raw;
					for( $i = 0; $i < $l3; ++$i ) {
						$str[$i] = $str[$i] ^ $s1[$i];
					}
                    break;
            }
            $this->_data = unserialize($str); // may except-out
			return;
        }

        $this->_data = null;
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

    public function offsetExists($key)
    {
        $this->_expand();
        return isset($this->_data[$key]);
    }

    public function offsetGet($key)
    {
        $this->_expand();
        return $this->_data[$key] ?? null;
    }

    public function offsetSet($key,$value)
    {
        $this->_expand();
        $this->_data[$key] = $value;
        $this->_save();
    }

    public function offsetUnset($key)
    {
        $this->_expand();
        if( isset($this->_data[$key]) ) {
            unset($this->_data[$key]);
            $this->_save();
        }
    }
} // class

