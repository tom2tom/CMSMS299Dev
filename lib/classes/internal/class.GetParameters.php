<?php
#Class to create and interpret get-parameters used in an action-URL
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

use cms_config;
use cms_utils;
use CmsApp;
use const CMS_ROOT_PATH;
use function cleanArray;
use function cms_build_query;

/**
 * Class to create and interpret get-parameters used in an action-URL.
 * Supports plaintext and obscured parameters. The latter, if used, provide
 * a jot of protection against injection, mainly for sites without https support.
 * @since 2.3
 */
class GetParameters
{
    /**
     * Salt for keys related to obscured parameters
     */
    private const SECURESALT = '_SE_';

    /**
     * @var string rawurlencode()'d parameter key used for obscure params
     */
    private $_parmkey;

    // Reference: https://www.php.net/manual/en/function.base64-encode.php
    public function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public function base64url_decode($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

    /**
     * Check whether obscured get-parameters are present in $_REQUEST[]
     * @return bool
     */
    public function obscured_params_exist() : bool
    {
        if (empty($this->_parmkey)) {
            $key = cms_utils::hash_string(self::SECURESALT.CMS_ROOT_PATH);
            $this->_parmkey = rawurlencode($key);
        }
        return (!empty($_REQUEST[$this->_parmkey]) && !empty($_REQUEST['_'.$this->_parmkey]));
    }

    /**
     * Generate obscured get-parameters for use in an URL
     *
     * @param array $parms URL get-parameters. Should include mact-components
     *  and action-parameters (if any), and generic-parameters (if any)
     * @param int $format Optional format enumerator. Default 0.
     * @return string (no leading '?')
     */
    public function create_obscured_params(array $parms, int $format = 0) : string
    {
        $this->obscured_params_exist(); // create parameter key
        $data = json_encode($parms, JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $raw = cms_utils::random_string(mt_rand(10, 20));
        $privkey = hash_hmac('sha256', CmsApp::get_instance()->GetSiteUUID(), cms_config::get_instance()['db_password']);

        $val = cms_utils::encrypt_string($data, $raw.$privkey, 'internal');
        $sep = ($format == 2) ? '&' : '&amp;';
        $pubkey = cms_utils::encrypt_string($raw, $privkey);
        return $this->_parmkey.'='.$this->base64url_encode($val).$sep.'_'.$this->_parmkey.'='.$this->base64url_encode($pubkey);
    }

   /**
     * Generate plaintext get-parameters for use in an URL
     *
     * @param array $parms URL get-parameters. Should include mact-components
     *  and action-parameters (if any), and generic-parameters (if any)
     * @param int $format Optional format enumerator. Default 0.
     * @return string (no leading '?')
     */
    public function create_plain_params(array $parms, int $format = 0) : string
    {
        switch ($format) {
            case 2:
                $sep = '&';
                $enc = true;
                break;
            case 3:
                $sep = '&';
                $enc = false;
                break;
            default:
                $sep = '&amp;';
                $enc = true;
                break;
        }

		if (isset($parms['module']) && isset($parms['id']) && isset($parms['action'])) {
			$module = trim($parms['module']);
			$id = trim($parms['id']);
			$action = trim($parms['action']);
			$inline = !empty($parms['inline']) ? 1 : 0;
			unset($parms['module'], $parms['id'], $parms['action'], $parms['inline']);
			$parms = ['mact' => "$module,$id,$action,$inline"] + $parms;
		}

        $text = '';
        $first = true;
        foreach ($parms as $key => $val) {
            if (is_scalar($val)) {
                if ($enc) {
                    $key = rawurlencode($key);
                }
                if ($format != 0 || $key != 'mact') {
                    $val = rawurlencode($val);
                }
                if ($first) {
                    $text .= $key.'='.$val;
                    $first = false;
                } else {
                    $text .= $sep.$key.'='.$val;
                }
            } else {
                if ($first) {
                    $first = false;
                } else {
                    $text .= $sep;
                }
                $text .= cms_build_query($key, $val, $sep, $enc);
            }
        }
        return $text;
    }

    /**
     * Generate get-parameters for use in an URL
     * @param array $parms URL get-parameters. Should include mact-components
     *  and action-parameters (if any), and generic-parameters (if any)
     * @param int $format Optional format enumerator
     *  0 = (default, back-compatible) rawurlencoded parameter keys and values
     *      other than the value for key 'mact', '&amp;' for parameter separators
     *  1 = proper: as for 0, but also encode the 'mact' value
     *  2 = raw: as for 1, except '&' for parameter separators - e.g. for use in js
     *  3 = displayable: no encoding, all html_entitized, probably not usable as-is
     *   BUT the output must be entitized upstream, not done here
     * @return string
     */
    public function create_action_params(array $parms, int $format = 0) : string
    {
        if ($format < 3) {
//            $secure = true; //DEBUG
            $secure = cms_config::get_instance()['secure_action_url'];
            if ($secure) {
                return $this->create_obscured_params($parms, $format);
            }
        }
        return $this->create_plain_params($parms, $format);
    }

    /**
     * Return action-parameters interpreted from obscured parameters in the current request
     * Anything in the obscured data that is not a specific action-parameter is
     * migrated to $_REQUEST[]. Otherwise, request parameters are ignored.
     *
     * @param bool $clear Optional flag whether to clear the cached password
     *  and processed $_REQUEST[] members. Default false.
     * @return mixed array | null
     */
    public function decode_obscured_params(bool $clear = false)
    {
        if (!$this->obscured_params_exist()) return;

        $val = filter_var($_REQUEST['_'.$this->_parmkey], FILTER_SANITIZE_STRING);
        $pubkey = $this->base64url_decode($val);
        $privkey = hash_hmac('sha256', CmsApp::get_instance()->GetSiteUUID(), cms_config::get_instance()['db_password']);
        $raw = cms_utils::decrypt_string($pubkey, $privkey);

        $val = filter_var($_REQUEST[$this->_parmkey], FILTER_SANITIZE_STRING);
        $raw2 = $this->base64url_decode($val);
        $data = cms_utils::decrypt_string($raw2, $raw.$privkey, 'internal');

		if ($data) {
            $parms = json_decode($data, true);
            if (is_array($parms)) {
                $module = $action = false;
                $id = isset($parms['id']);
                if ($id) {
                    $len = strlen($parms['id']);
                    foreach ($parms as $key => &$val) {
                        switch ($key) {
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
                                //also park in $_REQUEST if relevant
                                if ($len > 0 && strncmp($key, $parms['id'], $len) != 0) {
                                    $_REQUEST[$key] = $val;
                                }
                        }
                    }
                    unset($val);
                }
                if ($clear) unset($_REQUEST[$this->_parmkey], $_REQUEST['_'.$this->_parmkey]);
                if ($module && $action && $id) {
                    return $parms;
                }
            }
        }
    }

    /**
     * Return action-parameters interpreted from plaintext parameters in the current request
     * Non-action parameters are ignored.
     *
     * @param bool $clear Optional flag whether to clear processed $_REQUEST[]
     *  members. Default false.
     * @return mixed array | null
     */
    public function decode_plain_params(bool $clear = false)
    {
        cleanArray($_REQUEST);
        $parms = [];
        $parts = explode(',', $_REQUEST['mact'], 4);
        if (isset($parts[0])) $parms['module'] = trim($parts[0]);
        if (isset($parts[1])) $parms['id'] = trim($parts[1]);
        if (isset($parts[2])) $parms['action'] = trim($parts[2]);
        if (isset($parts[3])) $parms['inline'] = ($parts[3]) ? 1 : 0;

        if ($clear) unset($_REQUEST['mact']);
        if ($parms['id'] !== '') {
            $id = $parms['id'];
            $len = strlen($id);
            foreach ($_REQUEST as $key => $val) {
                if (strncmp($key, $id, $len) == 0) {
                    $key2 = substr($key,$len);
                    if (is_numeric($val)) {
                        $parms[$key2] = $val + 0;
                    }
                    elseif (is_scalar($val)) {
                        $parms[$key2] = $val; //TODO interpret flattened non-scalars
                    }
                    else {
                        $parms[$key2] = $val;
                    }
                    if ($clear) unset($_REQUEST[$key]);
                }
            }
        }

        return $parms;
    }

    /**
     * Return action-parameters interpreted from parameters in the current request.
     * Non-action parameters are ignored.
     *
     * @param bool $clear Optional flag whether to clear processed $_REQUEST[]
     *  members. Default false.
     * @return mixed array | null
     */
    public function decode_action_params(bool $clear = false)
    {
        if (isset($_REQUEST['mact'])) {
            return $this->decode_plain_params($clear);
        }
        if ($this->obscured_params_exist()) {
            return $this->decode_obscured_params($clear);
        }
    }

    /**
     * Return the non-action parameters in the current request.
     * Assumes $_REQUEST is already sanitized e.g. via a previous method in this class
     *
     * @param mixed $id string | null
     * @return array
     */
    public function retrieve_general_params($id) : array
    {
        $l = strlen(''.$id);
        if ($l > 0) {
            $parms = [];
            foreach ($_REQUEST as $key => $val) {
                if (strncmp($key, $id, $l) != 0) {
                    $parms[$key] = $val;
                }
            }
        } else {
            $parms = $_REQUEST;
        }

        $this->obscured_params_exist(); // create parameter key
        return array_diff_key($parms, [
         'module' => 1,
         'id' => 1,
         'action' => 1,
         'inline' => 1,
         'mact' => 1,
         $this->_parmkey => 1,
         '_'.$this->_parmkey => 1,
        ]);
    }

    /**
     * Return specified action-parameter value(s) (if they exist) in the current request
     * Null is returned for each parameter which doesn't exist.
     *
     * @param array $keys Wanted parameter name(s)
     * @return array
     */
    public function get_plain_values(array $keys) : array
    {
        cleanArray($_REQUEST);
        $parts = explode(',', $_REQUEST['mact'], 4);
        $id = (isset($parts[1])) ? trim($parts[1]) : '';
        $len = strlen($id);
        $parms = array_fill_keys($keys, null);

        foreach ($keys as $key) {
            switch ($key) {
            case 'module': if (isset($parts[0])) { $val = trim($parts[0]); break; } else { break 2; }
            case 'id': if (isset($parts[1])) { $val = $id; break; } else { break 2; }
            case 'action': if (isset($parts[2])) { $val = trim($parts[2]); break; } else { break 2; }
            case 'inline': if (isset($parts[3])) { $val = ($parts[3]) ? 1 : 0; break; } else { break 2; }
            default:
                if ($id && isset($_REQUEST[$id.$key])) {
                    $val = $_REQUEST[$id.$key];
                    if (is_numeric($val)) {
                        $val += 0;
                    }
                    elseif (is_scalar($val)) {
                        //TODO interpret flattened non-scalars
                    }
                }
                else {
                    break 2;
                }
            }
            $parms[$key] = $val;
        }
        return $parms;
    }

    /**
     * Return specified action-parameter value(s) (if they exist) in the current request
     * Null is returned for each parameter which doesn't exist.
     *
     * @param array $keys Wanted parameter name(s)
     * @return array
     */
    public function get_obscured_values(array $keys) : array
    {
        cleanArray($_REQUEST);
        $parms = array_fill_keys($keys, null);

        $val = filter_var($_REQUEST['_'.$this->_parmkey], FILTER_SANITIZE_STRING);
        $pubkey = $this->base64url_decode($val);
        $privkey = hash_hmac('sha256', CmsApp::get_instance()->GetSiteUUID(), cms_config::get_instance()['db_password']);
        $raw = cms_utils::decrypt_string($pubkey, $privkey);

		$val = filter_var($_REQUEST[$this->_parmkey], FILTER_SANITIZE_STRING);
        $raw2 = $this->base64url_decode($val);
        $data = cms_utils::decrypt_string($raw2, $raw.$privkey, 'internal');

		if ($data) {
            $rparms = json_decode($data, true);
            if (is_array($rparms)) {
                $parms = array_merge($parms, $rparms);
                foreach ($parms as $key => &$val) {
                    if (is_numeric($val)) {
                        $val += 0;
                    }
                    elseif (is_scalar($val)) {
                        //TODO interpret flattened non-scalars
                    }
                }
                unset($val);
            }
        }
        return $parms;
    }

    /**
     * Return specified action-parameter value(s) (if they exist) in the current request
     * Null is returned for each parameter which doesn't exist.
     *
     * @param mixed $keys string | string[]
     * @return mixed array | null
     */
    public function get_action_values($keys)
    {
        if ($keys) {
            if (!is_array($keys)) $keys = [$keys];
            if (isset($_REQUEST['mact'])) {
                return $this->get_plain_values($keys);
            }
            if ($this->obscured_params_exist()) {
                return $this->get_obscured_values($keys);
            }
        }
    }
} // class
