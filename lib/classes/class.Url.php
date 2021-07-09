<?php
/*
Url class
Copyright (C) 2010-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS;

use function startswith;

/**
 * A class for interacting with an URL.
 *
 * @package CMS
 * @author  Robert Campbell
 *
 * @since 2.99
 * @since 1.9 as global-namespace cms_url
 */
class Url
{
    /**
     * @ignore
     */
    private $_orig = '';

    /**
     * @ignore
     */
    private $_parts = [];

    /**
     * @ignore
     */
    private $_query = [];

    /**
     * Constructor
     *
     * @param string $url the URL to work with
     */
    public function __construct($url = '')
    {
        $url = trim((string) $url);
        if( $url ) {
            $this->_orig = $url;
            $this->_parts = parse_url($url);
            if( !empty($this->_parts['query']) ) {
                parse_str($this->_parts['query'],$this->_query);
            }
        }
    }

    /**
     * @ignore
     */
    protected function _get_part($key)
    {
        $key = trim((string)$key);
        if( isset($this->_parts[$key]) ) return $this->_parts[$key];
    }

    /**
     * @ignore
     */
    protected function _set_part($key,$value)
    {
        $key = trim((string)$key);
        if( !strlen($value) && isset($this->_parts[$key]) ) {
            unset($this->_parts[$key]);
        }
        else {
            $this->_parts[$key] = $value;
        }
    }

    /**
     * @ignore
     * @see also CMSMS\urlencode()
     */
    protected function _clean1($str,$patn)
    {
        return preg_replace_callback_array([
            '/\x00-\x1f\x7f/' => function() { return ''; },
            $patn => function($matches) { return rawurlencode($matches[0]); }
        ], $str);
    }

    /**
     * @ignore
     */
    protected function _clean_parts()
    {
        if( !empty($this->_parts['scheme']) ) {
            // see https://en.wikipedia.org/wiki/List_of_URI_schemes
            $av = strtolower($this->_parts['scheme']);
            $this->_parts['scheme'] = preg_replace('/[^a-z0-9\-+.]/', '', $av);
        }

        if( !empty($this->_parts['user']) ) {
            //userinfo = unreserved | pct-encoded | sub-delims | ":"
            //unreserved = ALPHA | DIGIT | "-" | "." | "_" | "~"
            //sub-delims = "!" | "$" | "&" | "'" | "(" | ")" | "*" | "+" | "," | ";" | "="
            $this->_parts['user'] = $this->_clean1($this->_parts['user'], '/[^\w.~!$&\'()*\-+,:;=%]/');
        }
/*      if( !empty($this->_parts['pass']) ) {
            $this->_parts['pass'] = ; //passinfo = enctypted anything or empty
        }
*/
        if( !empty($this->_parts['host']) ) {
            //host = IP-literal | IPv4address | reg-name
            //IP-literal = "[" ( IPv6address | IPvFuture  ) "]"
            //IPvFuture  = "v" 1*HEXDIG "." 1*( unreserved | sub-delims | ":" )
            //reg-name    = *( unreserved / pct-encoded / sub-delims )
            $this->_parts['host'] = $this->_clean1($this->_parts['host'], '/[^\w.~!$&\'()*\-+,:;=\[\]%]/');
        }
        if( !empty($this->_parts['path']) ) {
            //pathchar = unreserved | pct-encoded | sub-delims | ":" | "@" PLUS "/"
            $this->_parts['path'] = $this->_clean1($this->_parts['path'], '/[^\w.~!@$&\'()*\-+,\/:;=%]/');
        }
        if( isset($this->_parts['port']) ) {
            $this->_parts['port'] = (int) filter_var($this->_parts['port'], FILTER_SANITIZE_NUMBER_INT);
        }
        if( $this->_query ) {
            //pchar = unreserved | pct-encoded | sub-delims | ":" | "@" PLUS "/" | "?"
            $arr = [];
            foreach( $this->_query as $key => $value ) {
                $ak = $this->_clean1($key, '/[^\w.~!@$&\'()*\-+,?\/:;=%]/');
                $av = $this->_clean1($value, '/[^\w.~!@$&\'()*\-+,?\/:;=%]/');
                $arr[$ak] = $av;
            }
            $this->_query = $arr;
        }
        if( !empty($this->_parts['fragment']) ) {
            //pchar = unreserved | pct-encoded | sub-delims | ":" | "@" PLUS "/" | "?"
            $this->_parts['fragment'] = $this->_clean1($this->_parts['fragment'], '/[^\w.~!@$&\'()*\-+,?\/:;=%]/');
        }
    }

    /**
     * A convenience function equivalent to (string) new Url(trim($url))
     * @since 2.99
     * @param mixed $url string | null
     * @return string
     */
    public function sanitize($url)
    {
        $this->_orig = '';
        $this->_parts = [];
        $this->_query = [];
        $this->__construct(trim($url));
        return $this->__toString();
    }

    /**
     * Return the original URL
     *
     * @return string
     */
    public function get_orig()
    {
        return $this->_orig;
    }

    /**
     * Return the URL scheme.  i.e: HTTP, HTTPS, ftp etc.
     *
     * @return string (might be empty)
     */
    public function get_scheme()
    {
        return $this->_get_part('scheme');
    }

    /**
     * Set the URL scheme
     *
     * @param string $val The URL scheme.
     */
    public function set_scheme($val)
    {
        $val = trim((string) $val);
        return $this->_set_part('scheme',$val);
    }

    /**
     * Return the host part of the URL
     * Might return an empty string if the input URL does not have a host part.
     *
     * @return string (might be empty)
     */
    public function get_host()
    {
        return $this->_get_part('host');
    }

    /**
     * Set the URL host
     *
     * @param string $val The URL hostname.
     */
    public function set_host($val)
    {
        $val = trim((string) $val);
        $this->_set_part('host',$val);
    }

    /**
     * Return the port part of the URL
     * Might return an empty string if the input URL does not have a port portion.
     *
     * @return int (might be empty)
     */
    public function get_port()
    {
        return $this->_get_part('port');
    }

    /**
     * Set the URL port
     *
     * @param int $val the URL port number.
     */
    public function set_port($val)
    {
        $val = (int) $val;
        return $this->_set_part('port',$val);
    }

    /**
     * Return the user part of the URL, if any.
     *
     * @return string (might be empty)
     */
    public function get_user()
    {
        return $this->_get_part('user');
    }

    /**
     * Set the user portion of the URL.
     * Note: usually a password must also be set if setting the username.
     *
     * @param string $val The username (may be empty)
     */
    public function set_user($val)
    {
        $val = trim((string) $val);
        return $this->_set_part('user',$val);
    }

    /**
     * Retrieve the password portion of the URL, if any.
     *
     * @return string (might be empty)
     */
    public function get_pass()
    {
        return $this->_get_part('pass');
    }

    /**
     * Set the password portion of the URL.
     * Usually when setting the password, the username portion is also required in an URL.
     *
     * @param string $val The password (may be empty)
     */
    public function set_pass($val)
    {
        $val = trim((string) $val);
        return $this->_set_part('pass',$val);
    }

    /**
     * Return the path portion of the URL.
     *
     * @return string (might be empty)
     */
    public function get_path()
    {
        return $this->_get_part('path');
    }

    /**
     * Set the path portion of the URL.
     *
     * @param string $val (may be empty)
     */
    public function set_path($val)
    {
        return $this->_set_part('path',$val);
    }

    /**
     * Return the the query portion of the URL, if any is set.
     *
     * @return string (might be empty)
     */
    public function get_query()
    {
        if( $this->_query ) return http_build_query($this->_query);
    }

    /**
     * Set the query portion of the URL.
     *
     * @param string $val (may be empty)
     */
    public function set_query($val)
    {
        $val = (string) $val;
        if( $val ) parse_str($val,$this->_query);
        return $this->_set_part('query',$val);
    }

    /**
     * Return the fragment portion of the URL
     *
     * @return string
     */
    public function get_fragment()
    {
        return $this->_get_part('fragment');
    }

    /**
     * Set the fragment portion of the URL.
     *
     * @param string $val
     */
    public function set_fragment($val)
    {
        $val = (string) $val;
        return $this->_set_part('fragment',$val);
    }

    /**
     * Test whether the named query variable exists in the URL
     *
     * @param string $key
     */
    public function queryvar_exists($key)
    {
        return ($key && isset($this->_query[$key]));
    }

    /**
     * Erase a query var if it exists.
     *
     * @since 2.0.1
     * @param string $key
     */
    public function erase_queryvar($key)
    {
        $key = trim((string)$key);
        if( $this->queryvar_exists($key) ) {
            unset($this->_parts['query']);
            unset($this->_query[$key]);
        }
    }

    /**
     * Retrieve a query var from the URL.
     *
     * @param string $key
     */
    public function get_queryvar($key)
    {
        $key = trim((string)$key);
        if( $this->queryvar_exists($key) ) return $this->_query[$key];
    }

    /**
     * Set a query var into the URL
     *
     * @param string $key
     * @param string $value
     */
    public function set_queryvar($key,$value)
    {
        $key = trim((string)$key);
        if( $key ) {
            unset($this->_parts['query']);
            //TODO sanitize
            $this->_query[$key] = (string) $value;
        }
    }

    /**
     * @ignore
     */
    public function __toString()
    {
        $this->_clean_parts();
        // build the query array back into a string.
        if( $this->_query ) $this->_parts['query'] = http_build_query($this->_query);

        $parts = $this->_parts;

        $url = (!empty($parts['scheme'])) ? $parts['scheme'] . ':' : '';
        if( !empty($parts['user']) ) {
            $url .= '//'.$parts['user'];
            if( !empty($parts['pass']) ) $url .= ':'.$parts['pass'];
        }
        if( !empty($parts['host']) ) {
            if( empty($parts['user']) ) {
                $url .= '//';
            }
            else {
                $url .= '@';
            }
            $url .= $parts['host'];
            if( !empty($params['port']) && $parts['port'] > 0 ) $url .= ':'.$parts['port'];
        }
        if( !empty($parts['path']) ) {
            //$path = $parts['path'] ?? '';
            //if( $path && $path[0] != '/' ) $path = '/'.$path; // TODO
            if( (!empty($parts['user']) || !empty($parts['host'])) && !startswith($parts['path'],'/') ) $url .= '/';
            $url .= $parts['path'];
        }
        if( !empty($parts['query']) ) $url .= '?'.$parts['query'];
        if( !empty($parts['fragment']) ) $url .= '#'.$parts['fragment'];
        return $url;
    }
} // class
