<?php
#Class to manage secure mact's and translations between those and plaintext mact's
#Copyright (C) 2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
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

/*
 * TODO check this. Deals with module- and action-name, but not action-parameter values, so not much of a boon ...
 */
namespace CMSMS\internal;

use cms_cache_handler;
use cms_config;
use cms_utils;
use CMSModule;
use function cms_error;
use function cms_htmlentities;
use function cms_to_bool;
use function startswith;

/**
 * Class to manage obfuscated+signed mact's, and translations between those and plaintext mact's
 * @since 2.3
 */
class MactEncoder
{
    /**
     * $_GET|$_POST|$_REQUEST key for secure mact's
     */
    const KEY = '_R';

    protected function get_pw() : string
    {
        $key = cms_utils::hash_string(self::KEY.self::class);
        return cms_cache_handler::get_instance()->get($key,'general');
    }

    protected function set_pw() : string
    {
        $key = cms_utils::hash_string(self::KEY.self::class);
        $val = cms_utils::random_string(16);
        cms_cache_handler::get_instance()->set($key,$val,'general');
        return $val;
    }

    /**
     * Create a MactInfo object from a secure request-URL
     *
     * @param bool $strict_request_type Optional flag Default true.
     * @return mixed MactInfo | null
     */
    protected function decode_secure_mact(bool $strict_request_type = true)
    {
        if( $strict_request_type ) {
            if( $_SERVER['REQUEST_METHOD'] == 'GET' ) {
                $val = $_GET[self::KEY] ?? null;
            }
            else if( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
                $val = $_POST[self::KEY] ?? null;
            }
            else {
                $val = null;
            }
        } else {
            $val = $_REQUEST[self::KEY] ?? null;
        }
        if( !$val ) return;

        $key = $this->get_pw();
        $data = cms_utils::decrypt_string(rawurldecode($val),$key);
        if( $data ) {
            $arr = json_decode($data,true);
            if( is_array($arr) && isset($arr['action']) && isset($arr['module']) && isset($arr['id']) ) {
                return MactInfo::from_array($arr);
            }
        }
    }

    /**
     * Create a MactInfo object from a plaintext request-URL
     *
     * @param bool $strict_request_type Optional flag Default true
     * @return mixed MactInfo | null
     */
    public function decode_plain_mact(bool $strict_request_type = true)
    {
        if( $strict_request_type ) {
            if( $_SERVER['REQUEST_METHOD'] == 'GET' ) {
                $val = $_GET['mact'] ?? null;
            }
            else if( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
                $val = $_POST['mact'] ?? null;
            }
            else {
                $val = null;
            }
        } else {
            $val = $_REQUEST['mact'] ?? null;
        }
        if( !$val ) return;

        list($module,$id,$action,$inline) = explode(',',$val,4);
        $arr = [
         'module' => trim($module),
         'id' => trim($id),
         'action' => trim($action),
         'inline' => cms_to_bool($inline),
        ];

        $input = $_REQUEST;
        if( $strict_request_type ) {
            if( $_SERVER['REQUEST_METHOD'] == 'GET' ) $input = $_GET;
            else if( $_SERVER['REQUEST_METHOD'] == 'POST' ) $input = $_POST;
        }
        foreach( $input as $key => $val ) {
            if( startswith($key,$arr['id']) ) {
                $key = substr($key,strlen($arr['id']));
                $arr['params'][$key] = $val;
            }
        }
        return MactInfo::from_array($arr);
    }

    /**
     * Populate a MactInfo object reflecting request parameters
     * @param bool $strict_request_type optional flag Default true
     * @return mixed MactInfo | null
     */
    public function decode(bool $strict_request_type = true)
    {
        if( $this->secure_mact_exists($strict_request_type) ) {
            return $this->decode_secure_mact($strict_request_type);
        }
        return $this->decode_plain_mact($strict_request_type);
    }

    /**
     * Determine whether the request parameters include a secured mact
     * @param bool $strict_request_type optional flag Default true
     * @return boolean
     */
    protected function secure_mact_exists(bool $strict_request_type = true) : bool
    {
        if( !$strict_request_type ) return isset($_REQUEST[self::KEY]);
        if( !isset($_SERVER['REQUEST_METHOD']) ) return false;
        if( $_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET[self::KEY]) ) return true;
        if( $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST[self::KEY]) ) return true;
        return false;
    }

    /**
     * Determine whether the request parameters include a plaintext mact
     * @param bool $strict_request_type optional flag Default true
     * @return boolean
     */
    public function plain_mact_exists(bool $strict_request_type = true) : bool
    {
        if( !$strict_request_type ) return isset($_REQUEST['mact']);
        if( !isset($_SERVER['REQUEST_METHOD']) ) return false;
        if( $_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['mact']) ) return true;
        if( $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mact']) ) return true;
        return false;
    }

    /**
     * Remove $_REQUEST members corresponding to a plaintext mact
     */
    public function remove_plain_mact_params()
    {
        $input = &$_REQUEST;
        if( isset($input['mact']) ) {
            $parts = explode(',',$input['mact'],4);
            $id = trim($parts[1]);
            foreach( $input as $key => &$val ) {
                if( startswith($key,$id) ) $val = null;
            }
            $input['mact'] = null;
        }
    }

    /**
     * Populate $_REQUEST members corresponding to a secure mact
     * @param bool $strict_request_type optional flag Default true
     */
    public function expand_secure_mact(bool $strict_request_type = true)
    {
        // if we have a secure mact request, convert it into an old style mact request
        // only happens if the secure mact key issset and valid.
        if( $this->secure_mact_exists($strict_request_type) ) {
            $mact = $this->decode_secure_mact($strict_request_type);
            if( $mact ) {
                // repopulate mact.
                $mact_str = "{$mact->module},{$mact->id},{$mact->action},{$mact->inline}";
                $_REQUEST['mact'] = $mact_str;
                foreach( $mact->params as $key => $val ) {
                    $key = $mact->id.$key;
                    $_REQUEST[$key] = $val;
                }
            }
        }
    }

    /**
     * Generate secure URL-slug representing $mact and related action-parameters
     *
     * @param MactInfo $mact
     * @param array $extraparms Optional additional request parameters which do not need mact-id in their key
     * @return string
     */
    protected function encode_to_secure_url(MactInfo $mact, array $extraparms = [])
    {
        $json = json_encode($mact);
        $key = $this->set_pw();
        $val = cms_utils::encrypt_string($json, $key);
        $str = self::KEY.'='.rawurlencode($val);
        if( !empty($extraparms) ) {
            foreach( $extraparms as $key => $val ) {
                $str .= '&amp;'.$key.'='.rawurlencode(cms_htmlentities($val));
            }
        }
        return $str;
    }

    /**
     * Generate plaintext URL-slug representing $mact and related action-parameters
     *
     * @param MactInfo $mact
     * @param array $extraparms Optional additional request parameters which do not need mact-id in their key
     * @return string
     */
    protected function encode_to_plain_url(MactInfo $mact, array $extraparms = [])
    {
        // encodes to old style mact url.
        $arr = null;
        $arr['mact'] = "{$mact->module},{$mact->id},{$mact->action},{$mact->inline}";
        $params = $mact->params;
        if( !empty($params) ) {
            foreach( $params as $key => $val ) {
                $key = "{$mact->id}{$key}";
                $arr[$key] = $val;
            }
        }
        if( !empty($extraparms) ) {
            foreach( $extraparms as $key => $val ) {
                $arr[$key] = $val;
            }
        }

        $out = '';
        $keys = array_keys($arr);
        for( $i = 0, $n = count($keys); $i < $n; $i++ ) {
            $key = $keys[$i];
            $val = $arr[$key];
            $out .= cms_htmlentities($key).'='.rawurlencode($val);
            /*
            if( $key == 'mact' ) {
                // special case for the mact param?
                $out .= $key.'='.$val;
            }
            else {
                $out .= cms_htmlentities($key).'='.rawurlencode($val);
            }
            */
            if( $i < $n - 1 ) $out .= '&amp;';
        }
        return $out;
    }

    /**
     *
     * @param MactInfo $mact
     * @return array
     */
    public function encode_mact(MactInfo $mact)
    {
        $json = json_encode($mact);
        $key = $this->set_pw();
        $val = cms_utils::encrypt_string($json, $key);
        return [self::KEY => rawurlencode($val)];
    }

    /**
     * Generate URL-slug representing mact and related action-parameters
     * @param MactInfo $mact
     * @param array $extraparms Optional additional request parameters which do not need mact-id in their key
     * @return string
     */
    public function encode_to_url(MactInfo $mact, array $extraparms = [])
    {
        $secure = cms_config::get_instance()['secure_request_url'];
        if ( $secure ) {
            return $this->encode_to_secure_url($mact, $extraparms);
        }
        return $this->encode_to_plain_url($mact, $extraparms);
    }

    /**
     * Create a MactInfo object representing supplied parameters
     *
     * @param mixed CMSModule|string $module
     * @param string $id
     * @param string $action
     * @param bool $inline
     * @param array $params
     * @return MactInfo
     */
    public function create_mactinfo($module, string $id, string $action, bool $inline = false, array $params = []) : MactInfo
    {
        $arr = [];
        if( is_object($module) && $module instanceof CMSModule ) {
            $arr['module'] = $module->GetName();
        } else {
            $arr['module'] = trim($module);
        }
        $arr['action'] = trim($action);
        $arr['id'] = trim($id);
        if( !$arr['id'] ) $arr['id'] = MactInfo::CNTNT01;
        $arr['inline'] = $inline;
        if( $params ) {
            $excluded = ['assign','id','returnid','action','module']; //CHECKME returnid assign breakage?
            $tmp = [];
            foreach( $params as $key => $val ) {
                if( startswith($key,'__') ) continue;
                if( in_array($key,$excluded) ) continue;
                $tmp[$key] = $val;
            }
            if( $tmp ) $arr['params'] = $tmp;
        }

        return MactInfo::from_array($arr);
    }
} // class
