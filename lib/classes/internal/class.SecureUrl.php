<?php
#Class to create and interpret encrypted action-URL slugs
#Copyright (C) 2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace CMSMS\internal;

use cms_cache_handler;
use cms_utils;
use const CMS_ROOT_PATH;

/**
 * Class to create and interpret encrypted action-URL slugs
 * Provides security against injection, mainly for sites without https support
 * @since 2.3
 */
class SecureUrl
{
    /**
     * Salt for keys related to secure parameters
     */
    const SECURESALT = '_SE_';

    /**
     * @var string rawurlencode()'d parameter key used for bundled secure params
     */
    private $_urlkey;

    /**
     * @ignore
     */
    protected function set_pw() : string
    {
        $key = cms_utils::hash_string(self::SECURESALT.self::class);
        $val = strtr(random_bytes(16), '\0', chr(128));
        cms_cache_handler::get_instance()->set($key, $val, 'SecureUrl');
        return $val;
    }

    /**
     * @ignore
     */
    protected function get_pw() : string
    {
        $key = cms_utils::hash_string(self::SECURESALT.self::class);
        $cache = cms_cache_handler::get_instance();
        $val = $cache->get($key, 'SecureUrl');
        $cache->erase($key, 'SecureUrl');
        return $val;
    }

    /**
     * Check whether a secure URL-slug exists
     * @return bool
     */
    public function secure_slug_exists() : bool
    {
        if( empty($this->_urlkey) ) {
	        $key = cms_utls::hash_string(self::SECURESALT.CMS_ROOT_PATH);
            $this->_urlkey = rawurlencode($key);
        }
        return !empty($_REQUEST[$this->_urlkey]);
    }

    /**
     * Generate secure URL-slug representing $parms
     *
     * @param array $parms URL-parameters - Mact elements etc
     * @return string
     */
    public function create_secure_slug(array $parms)
    {
        $this->secure_slug_exists(); // create key
        $data = json_encode($parms, JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $pw = $this->set_pw();
        $val = cms_utils::encrypt_string($data, $pw, 'internal');
        return $this->_urlkey.'='.rawurlencode($val);
    }

    /**
     * Return action-parameters derived from a secure request-URL
     *
     * @param bool $strict_type Optional flag Default true.
     * @return mixed array | null
     */
    public function decode_secure_slug()
    {
        if( !$this->secure_slug_exists() ) return;

        $pw = $this->get_pw();
        $data = cms_utils::decrypt_string(rawurldecode($_REQUEST[$this->_urlkey]), $pw, 'internal');
        if( $data ) {
            $parms = json_decode($data, true);
            if( is_array($parms) && isset($parms['id']) ) {
                $id = trim($parms['id']);
                if( !$id ) return;

                $len = strlen($id);
                $module = $action = false;
                $tmp = [];
                foreach( $parms as $key => &$val ) {
                    switch( $key ) {
                    case 'module':
                        $module = trim($val) != '';
                        break;
                    case 'action':
                        $action = trim($val) != '';
                        break;
					case 'id': // already checked
					case 'inline': // don't care
                        break;
                    default:
                        if( strncmp($key, $id, $len) ) {
                            $key2 = substr($key, $len);
                            $tmp[$key2] = $val;
                            $val = null;
                        }
                        break;
                    }
                }
                unset($val);
                unset($_REQUEST[$this->_urlkey]);
                if( $module && $action && $id ) {
                    return array_filter($parms) + $tmp;
                }
            }
        }
    }
} // class
