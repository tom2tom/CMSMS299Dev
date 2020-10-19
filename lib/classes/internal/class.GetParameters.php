<?php
/*
Class to create get-parameters for use in an URL, and retreive request parameters
Copyright (C) 2019-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 2 of that License, or (at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of that along with CMS Made Simple. If not, see
<https://www.gnu.org/licenses/>.
*/

namespace CMSMS\internal;

use CMSMS\AppParams;
use CMSMS\AppSingle;
use CMSMS\Crypto;
use const CMS_JOB_KEY;
use const CMS_ROOT_PATH;
use function cms_build_query;

/**
 * Class to create get-parameters for use in an URL, and retrieve request
 * parameters.
 * Supports plaintext and obscured parameters. The latter, if used, provide
 * a jot of protection against injection, mainly for sites without https support.
 * @since 2.9
 */
class GetParameters
{
    /**
     * Salt for keys related to obscured parameters
     */
    private const SECURESALT = '_SE_';

    private const KEYPREF = '\\V^^V/'; //something extremely unlikely to be the prefix of any site-parameter key

    private const JOBID = 'aj_'; //something distinctive for job-URL's

    private const JOBKEY = '_jr_'; // parameter name for a secure repeatable job

    private const JOBONCEKEY = '_jo_'; // parameter name for a secure one-time job

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
    protected function obscured_params_exist() : bool
    {
        if (empty($this->_parmkey)) {
            $key = Crypto::hash_string(self::SECURESALT.CMS_ROOT_PATH);
            $this->_parmkey = rawurlencode($key);
        }
        return (!empty($_REQUEST[$this->_parmkey]) && !empty($_REQUEST['_'.$this->_parmkey]));
    }

    protected function create_jobtype(int $type, bool $first = false, int $format = 0) : string
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
        $text = ($first) ? '' : $sep;
        if ($enc) {
            $text .= rawurlencode(CMS_JOB_KEY).'='.$type;
        } else {
            $text .= CMS_JOB_KEY.'='.$type;
        }
        return $text;
    }

    /**
     * Generate get-parameters for use in a job-URL
     * @param array $parms URL get-parameters. See GetParameters::create_action_params()
     * @param bool $onetime Optional flag whether the URL is for one-time use. Default false.
     * @param int $format Optional format enumerator. Default 0
     *  See GetParameters::create_action_params()
     * @return string
     */
    public function create_job_params(array $parms, bool $onetime = false, int $format = 0) : string
    {
        $parms['id'] = self::JOBID;
        $str = $parms['action'] ?? 'job';
        $str .= AppSingle::App()->GetUUID();
        if ($onetime) {
            $chars = Crypto::random_string(12, true);
            while (1) {
                $key = str_shuffle($chars);
                $subkey = substr($key, 0, 6);
                $val = hash('tiger128,3', $subkey.$str); // 32-hexits
                $savekey = self::KEYPREF.$subkey;
                if (!AppParams::exists($savekey)) {
                    AppParams::set($savekey, $val); // a bit racy!
                    $parms[self::JOBONCEKEY] = $subkey;
                    break;
                }
            }
        } else {
            $parms[self::JOBKEY] = hash('tiger128,3', $str);
        }
        $parms[CMS_JOB_KEY] = 2;

        return $this->create_action_params($parms, $format);
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

        if (isset($parms[CMS_JOB_KEY])) {
            $type = $parms[CMS_JOB_KEY];
            unset($parms[CMS_JOB_KEY]);
        } else {
            $type = -1;
        }
        ksort($parms); //security key(s) lead

        if (isset($parms['module']) && isset($parms['id']) && isset($parms['action'])) {
            $module = trim($parms['module']);
            $id = trim($parms['id']);
            $action = trim($parms['action']);
            $inline = !empty($parms['inline']) ? 1 : 0;
            unset($parms['module'], $parms['id'], $parms['action'], $parms['inline']);
            $parms = ['mact' => "$module,$id,$action,$inline"] + $parms;
        }

        $data = json_encode($parms, JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $raw = Crypto::random_string(mt_rand(10, 20));
        $privkey = hash_hmac('tiger128,3', AppSingle::App()->GetSiteUUID(), AppSingle::Config()['db_password']);

        $val = Crypto::encrypt_string($data, $raw.$privkey, 'internal');
        $sep = ($format == 2) ? '&' : '&amp;';
        $pubkey = Crypto::encrypt_string($raw, $privkey);
        $text = $this->_parmkey.'='.$this->base64url_encode($val).$sep.'_'.$this->_parmkey.'='.$this->base64url_encode($pubkey);
        if ($type != -1) {
            $text .= $this->create_jobtype($type, false, $format);
        }
        return $text;
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

        if (isset($parms[CMS_JOB_KEY])) {
            $type = $parms[CMS_JOB_KEY];
            unset($parms[CMS_JOB_KEY]);
        } else {
            $type = -1;
        }
        ksort($parms); //security key(s) lead

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
                if ($enc && ($format != 0 || $key != 'mact')) {
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

        if ($type != -1) {
            $text .= $this->create_jobtype($type, false, $format);
        }
        return $text;
    }

    /**
     * Generate get-parameters for use in an URL (not necessarily one which runs a module-action)
     * @param array $parms URL get-parameters. Should include mact-components
     *  and action-parameters (if any), and generic-parameters (if any)
     * @param int $format Optional format enumerator
     *  0 = (default, back-compatible) rawurlencoded parameter keys and values
     *      other than the value for key 'mact', '&amp;' for parameter separators
     *  1 = proper: as for 0, but also encode the 'mact' value
     *  2 = raw: as for 1, except '&' for parameter separators - e.g. for use in js
     *  3 = displayable: no encoding, all html_entitized, probably not usable as-is
     *   BUT the output must be entitized upstream, it's not done here
     * @return string
     */
    public function create_action_params(array $parms, int $format = 0) : string
    {
        if ($format < 3) {
//            $secure = true; //DEBUG
            $secure = AppSingle::Config()['obscure_urls'];
            if ($secure) {
                return $this->create_obscured_params($parms, $format);
            }
        }
        return $this->create_plain_params($parms, $format);
    }

    /**
     * Validate security get-parameters
     * @param array $parms Some/all current-request parameters
     * @return boolean indicating validity
     */
    protected function check_secure_params(array $parms)
    {
        if (isset($parms[self::JOBKEY])) {
            $str = $parms['action'] ?? 'job';
            $str .= AppSingle::App()->GetUUID();
            return $parms[self::JOBKEY] == hash('tiger128,3', $str);
        }
        if (isset($parms[self::JOBONCEKEY])) {
            $key = $parms[self::JOBONCEKEY];
            $savekey = self::KEYPREF.$key;
            if (AppParams::exists($savekey)) {
                $val = AppParams::get($savekey);
                AppParams::remove($savekey);
                $str = $parms['action'] ?? 'job';
                $hash = hash('tiger128,3', $key.$str.AppSingle::App()->GetUUID());
                return $hash == $val;
            }
            return false;
        }
        return (!isset($parms[CMS_JOB_KEY]) || $parms[CMS_JOB_KEY] != 2);
    }

    /**
     * Return action-parameters interpreted from obscured parameters in $_REQUEST[]
     * Anything in the obscured data that is not a specific action-parameter is
     * migrated as-is to $_REQUEST[]. Otherwise, request parameters are ignored.
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
        $privkey = hash_hmac('tiger128,3', AppSingle::App()->GetSiteUUID(), AppSingle::Config()['db_password']);
        $raw = Crypto::decrypt_string($pubkey, $privkey);

        $val = filter_var($_REQUEST[$this->_parmkey], FILTER_SANITIZE_STRING);
        $raw2 = $this->base64url_decode($val);
        $data = Crypto::decrypt_string($raw2, $raw.$privkey, 'internal');

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
                if (isset($_REQUEST[CMS_JOB_KEY])) {
                    $parms[CMS_JOB_KEY] = filter_var($_REQUEST[CMS_JOB_KEY], FILTER_SANITIZE_INT);
                    if ($parms[CMS_JOB_KEY] == 2) {
                        //TODO maybe a job-URL, check/process that
                    }
                    if ($clear) unset($_REQUEST[CMS_JOB_KEY]);
                }
                return $parms;
            }
        }
    }

    /**
     * Return parameters interpreted from plaintext parameters in $_REQUEST[]
     * Non-action parameters are ignored.
     *
     * @param bool $clear Optional flag whether to clear processed $_REQUEST[]
     *  members. Default false.
     * @return mixed array | null
     */
    public function decode_plain_params(bool $clear = false)
    {
        $parms = [];
        if (!empty($_REQUEST['mact'])) {
            $parts = explode(',', $_REQUEST['mact'], 4);
            $parms['module'] = trim($parts[0]);
            $parms['id'] = (isset($parts[1])) ? trim($parts[1]) : '';
            $parms['action'] = (isset($parts[2])) ? trim($parts[2]) : 'defaultadmin';
            $parms['inline'] = (!empty($parts[3])) ? 1 : 0;
        }

        if (isset($parms['id']) && $parms['id'] !== '') {
            if (!$clear) {
                $tmp = $_REQUEST['mact'] ?? null;
            }
            unset($_REQUEST['mact']);

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
            if (!$clear && $tmp) $_REQUEST['mact'] = $tmp;
        }

        if (isset($_REQUEST[CMS_JOB_KEY])) {
            $parms[CMS_JOB_KEY] = filter_var($_REQUEST[CMS_JOB_KEY], FILTER_SANITIZE_NUMBER_INT); //OR (int)
            if ($parms[CMS_JOB_KEY] == 2) {
                //TODO maybe a job-URL, check/process that
            }
            if ($clear) unset($_REQUEST[CMS_JOB_KEY]);
        }

        return $parms;
    }

    /**
     * Return parameters interpreted from parameters in the current request.
     * Non-action parameters are ignored.
     *
     * @param bool $clear Optional flag whether to clear processed $_REQUEST[]
     *  members. Default false.
     * @return mixed array | null
     */
    public function decode_action_params(bool $clear = false)
    {
        if ($this->obscured_params_exist()) {
            return $this->decode_obscured_params($clear);
        }
        return $this->decode_plain_params($clear);
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
     * @param array $keys Wanted parameter name(s), empty if all are wanted
     * @return array, empty if some problem occurred
     */
    public function get_plain_values(array $keys) : array
    {
        if (!empty($_REQUEST['mact'])) {
            $parts = explode(',', $_REQUEST['mact'], 4);
            $_REQUEST['module'] = trim($parts[0]);
            $_REQUEST['id'] = $id = trim($parts[1]);
            $_REQUEST['action'] = trim($parts[2]);
            $_REQUEST['inline'] = (!empty($parts[3])) ? 1 : 0;
            $len = strlen($id);
            $strip = $len > 0;
        }
        else {
            $strip = false;
        }

        if (!$keys) {
            $keys = array_keys($_REQUEST);
            $key = array_search('mact', $keys);
            if ($key !== false) {
                unset($keys[$key]);
            }
        }

        if (!$this->check_secure_params($_REQUEST)) {
            foreach ($_REQUEST as $key=>$val) {
                unset($_REQUEST[$key]);
            }
            return [];
        }

        $parms = array_fill_keys($keys, null);
        foreach ($keys as $key) {
            switch ($key) {
            case 'module':
            case 'id':
            case 'action':
                if (isset($_REQUEST[$key])) {
                    $val = trim($_REQUEST[$key]); break;
                } else {
                    continue 2;
                }
            case 'inline':
                if (isset($_REQUEST[$key])) {
                    $val = (int)$_REQUEST[$key]; break;
                } else {
                    continue 2;
                }
            default:
                if ($strip && isset($_REQUEST[$id.$key])) {
                    $val = $_REQUEST[$id.$key];
                    if (is_numeric($val)) {
                        $val += 0;
                    }
                    elseif (is_scalar($val)) {
                        //TODO if (flattened non-scalar) {interpet & store}
                    }
                } elseif (isset($_REQUEST[$key])) {
                    $val = $_REQUEST[$key];
                    if (is_numeric($val)) {
                        $val += 0;
                    }
                    elseif (is_scalar($val)) {
                        //TODO if (flattened non-scalar) {interpet & store}
                    }
                } else {
                    continue 2;
                }
            }
            $parms[$key] = $val;
        }
        return $parms;
    }

    /**
     * Return specified parameter value(s) (if they exist) in the current request
     * Null is returned for each parameter which doesn't exist.
     *
     * @param array $keys Wanted parameter name(s), empty if all are wanted
     * @return array, empty if some problem occurred
     */
    public function get_obscured_values(array $keys) : array
    {
        $val = filter_var($_REQUEST['_'.$this->_parmkey], FILTER_SANITIZE_STRING);
        $pubkey = $this->base64url_decode($val);
        $privkey = hash_hmac('tiger128,3', AppSingle::App()->GetSiteUUID(), AppSingle::Config()['db_password']);
        $raw = Crypto::decrypt_string($pubkey, $privkey);

        $val = filter_var($_REQUEST[$this->_parmkey], FILTER_SANITIZE_STRING);
        $raw2 = $this->base64url_decode($val);
        $data = Crypto::decrypt_string($raw2, $raw.$privkey, 'internal');

        if ($data) {
            $rparms = json_decode($data, true);
            if (is_array($rparms)) {

                if (!$this->check_secure_params($_REQUEST + $rparms)) {
                    foreach ($_REQUEST as $key=>$val) {
                        unset($_REQUEST[$key]);
                    }
                    return [];
                }

                if (!$keys) {
                    $keys = array_keys($rparms);
                }
                $parms = array_fill_keys($keys, null);

                $wanted = array_intersect_key($parms, $rparms);
                $parms = $wanted + $parms;
                foreach ($parms as $key => &$val) {
                    if (is_numeric($val)) {
                        $val += 0;
                    }
                    elseif (is_scalar($val)) {
                        //TODO if (flattened non-scalar) {interpet}
                    }
                }
                unset($val);
                return $parms;
            }
        }

        foreach ($_REQUEST as $key=>$val) {
            unset($_REQUEST[$key]);
        }
        return [];
    }

    /**
     * Return specified parameter value(s) (if they exist) in the current request
     * Null is returned for each parameter which doesn't exist.
     *
     * @param mixed $keys Optional parameter-name(s) string | string[]
     *  String may be '*', or array may be []. Default null, hence all parameters
     * @return array
     */
    public function get_request_values($keys = null) : array
    {
        if ($keys) {
            if (!is_array($keys)) {
                if ($keys != '*') {
                    $keys = [$keys];
                } else {
                    $keys = [];
                }
            }
        } else {
            $keys = [];
        }
        if ($this->obscured_params_exist()) {
            return $this->get_obscured_values($keys);
        }
        return $this->get_plain_values($keys);
    }
} // class
