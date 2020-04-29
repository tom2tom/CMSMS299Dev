<?php
# Url class
# Copyright (C) 2010-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

namespace CMSMS;

use function startswith;

/**
 * A class for interacting with an URL.
 *
 * @package CMS
 * @author  Robert Campbell
 *
 * @since 2.9
 * @since 1.9 as cms_url
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
            $this->_orig = filter_var($url,FILTER_SANITIZE_URL);
            $this->_parts = parse_url($this->_orig);
            if( isset($this->_parts['query']) ) {
                parse_str($this->_parts['query'],$this->_query);
                if( $this->_query ) {
                    foreach( $this->_query as $key => $value ) {
                        //TODO sanitize array members e.g. remove / encode # < > * % & : \ ?
                    }
                }
            }
        }
    }

    /**
     * @ignore
     */
    private function _get_part($key)
    {
        $key = trim((string)$key);
        if( isset($this->_parts[$key]) ) return $this->_parts[$key];
    }

    /**
     * @ignore
     */
    private function _set_part($key,$value)
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
     * @return string (may be empty)
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
     * may return an empty string if the input url does not have a host part.
     *
     * @return string (may be empty)
     */
    public function get_host()
    {
        return $this->_get_part('host');
    }


    /**
     * Set the URL host
     *
     * @param string $val The url hostname.
     */
    public function set_host($val)
    {
        $val = trim((string) $val);
        $this->_set_part('host',$val);
    }


    /**
     * Return the port part of the URL
     * may return an empty string if the input url does not have a port portion.
     *
     * @return int (may be empty)
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
     * may return an empty string if the input url does not have a username portion.
     *
     * @return string (may be empty)
     */
    public function get_user()
    {
        return $this->_get_part('user');
    }

    /**
     * Set the user portion of the URL. An empty string is accepted
     * Note: usually one must set the password if setting the username.
     *
     * @param string $val The username
     */
    public function set_user($val)
    {
        $val = trim((string) $val);
        return $this->_set_part('user',$val);
    }


    /**
     * Retrieve the password portion of the URL, if any.
     *
     * @return string (may be empty)
     */
    public function get_pass()
    {
        return $this->_get_part('pass');
    }

    /**
     * Set the password portion of the URL.  Empty string is accepted
     * Usually when setting the password, the username portion is also required on a URL.
     *
     * @param string $val The password
     */
    public function set_pass($val)
    {
        $val = trim((string) $val);
        return $this->_set_part('pass',$val);
    }

    /**
     * Return the path portion of the URL.  This may be empty
     *
     * @return string
     */
    public function get_path()
    {
        return $this->_get_part('path');
    }

    /**
     * Set the path portion of the URL.  An empty string is accepted.
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
     *
     * @return string (may be empty)
     */
    public function get_query()
    {
        if( $this->_query ) return http_build_query($this->_query);
    }

    /**
     * Set the query portion of the URL.  An empty string is accepted
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
     * Test if the named query variable exists in the URL
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
     * Retrieve a query var from the url.
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
        // build the query array back into a string.
        if( $this->_query ) $this->_parts['query'] = http_build_query($this->_query);

        $parts = $this->_parts;

        $path = $parts['path'] ?? '';
        if( $path && $path[0] != '/' ) $path = '/'.$path;

        $parts = $this->_parts;
        $url = ((!empty($parts['scheme'])) ? $parts['scheme'] . '://' : '');
        if( !empty($parts['user']) ) {
            $url .= $parts['user'];
            if( !empty($parts['pass']) ) $url .= ':'.$parts['pass'];
            $url .= '@';
        }
        if( !empty($parts['host']) ) $url .= $parts['host'];
        if( !empty($params['port']) && $parts['port'] > 0 ) $url .= ':'.$parts['post'];
        if( !empty($parts['path']) ) {
            if( !startswith($parts['path'],'/') ) $url .= '/';
            $url .= $parts['path'];
        }
        if( !empty($parts['query']) ) $url .= '?'.$parts['query'];
        if( !empty($parts['fragment']) ) $url .= '#'.$parts['fragment'];
        return $url;
    }
} // class

\class_alias(Url::class, 'cms_url', false);