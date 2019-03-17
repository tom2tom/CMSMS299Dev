<?php

namespace cms_installer;

use ArrayAccess;
use Exception;

final class request implements ArrayAccess
{
    const METHOD_GET  = 'GET';
    const METHOD_POST = 'POST';

    private static $_instance;
    private $_data;

    private function __construct() {}
    private function __clone () {}

    public function __call($fn,$args)
    {
        $key = strtoupper($fn);
        if( isset($_SERVER[$key]) ) return $this->raw_server($key);
        throw new Exception('Call to unknown method '.$fn.' in request object');
    }

    public static function get_instance() : self
    {
        if( !self::$_instance ) self::$_instance = new self();
        return self::$_instance;
    }

    public function raw_server(string $key)
    {
        if( isset($_SERVER[$key]) ) return $_SERVER[$key];
    }

    public function method()
    {
        if( $this->raw_server('REQUEST_METHOD') == 'POST' ) {
            return self::METHOD_POST;
        }
        elseif( $this->raw_server('REQUEST_METHOD') == 'GET' ) {
            return self::METHOD_GET;
        }
        throw new Exception('Unhandled request method '.$_SERVER['REQUEST_METHOD']);
    }

    public function is_post() : bool
    {
        return $this->method() == self::METHOD_POST;
    }

    public function is_get() : bool
    {
        return $this->method() == self::METHOD_GET;
    }

    public function https() : bool
    {
        return isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off';
    }

    /**
     * @since 2.3
     * This replaces function request::self(), cuz that's a reserved name
     * @return mixed string | null
     */
    public function script()
    {
        return $this->raw_server('PHP_SELF');
    }

    public function accept()
    {
        return $this->raw_server('HTTP_ACCEPT');
    }

    public function accept_charset()
    {
        return $this->raw_server('HTTP_ACCEPT_CHARSET');
    }

    public function accept_encoding()
    {
        return $this->raw_server('HTTP_ACCEPT_ENCODING');
    }

    public function accept_language()
    {
        return $this->raw_server('HTTP_ACCEPT_LANGUAGE');
    }

    public function host()
    {
        return $this->raw_server('HTTP_HOST');
    }

    public function referer()
    {
        return $this->raw_server('HTTP_REFERER');
    }

    public function user_agent()
    {
        return $this->raw_server('HTTP_USER_AGENT');
    }

    //ArrayAccess methods

    public function offsetExists($key)
    {
        return isset($_REQUEST[$key]);
    }

    public function offsetGet($key)
    {
        if( isset($_REQUEST[$key]) ) return $_REQUEST[$key];
    }

    public function offsetSet($key,$value)
    {
        throw new Exception('Attempt to directly set a request variable');
    }

    public function offsetUnset($key)
    {
        throw new Exception('Attempt to unset a request variable');
    }
} // class
