<?php
/*
Class of methods to populate and retrieve request parameters
Copyright (C) 2019-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify it
under the terms of the GNU General Public License as published by the
Free Software Foundation, either version 3 of that license, or (at your option)
any later version.

CMS Made Simple is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS;

use CMSMS\Crypto;
use CMSMS\SingleItem;
use Throwable;
use const CMS_JOB_KEY;
use const CMS_SECURE_PARAM_NAME;
use function CMSMS\get_site_UUID;
use function startswith;
//use function debug_bt_to_log;

/**
 * Class of static methods to populate get-parameters for use in an URL,
 * and retrieve parameter-values from $_REQUEST | $_GET | $_POST.
 * @see also deprecated get_parameter_value() which can revert to a $_SESSION value,
 * for any requested parameter that is not found.
 * @since 2.99
 */
class RequestParameters
{
//    private const KEYPREF = '\V^^V/'; //something extremely unlikely to be the prefix of any site-parameter key

    private const JOBID = 'aj_'; //something distinctive for job-URL's

    private const JOBKEY = '_jr_'; // parameter name for a secure repeatable job

    private const JOBONCEKEY = '_jo_'; // parameter name for a secure one-time job

    // rfc3986 allowed = unreserved | pct-encoded | sub-delims | ":" | "@" PLUS "/" | "?" PLUS "%" for already-encoded
    private const ENC_PATTERN = '/[^\w.~!@$&\'()*\-+,?\/:;=%]/'; // regex pattern for selecting unaccepable URL-chars to be encoded

    /**
     * @param int $format 0 .. 3
     * $return 2-member array
     *  [0] = separator string
     *  [1] = encoding-enum: 0 none 1 entitize 2 urlencode
     */
    protected static function modes(int $format) : array
    {
        switch ($format) {
            case 0:
                return ['&amp;', 1];
            case 1:
                return ['&amp;', 2];
            case 3:
                return ['&', 0];
            default:
                return ['&', 2];
        }
    }

    /**
     * Cleanup part of an URL
     *
     * @param string $str
     * @param string $patn Optional regex for selecting chars in $str to be rawurlencoded
     * @return string
     */
    protected static function clean1(string $str, string $patn = self::ENC_PATTERN) : string
    {
        if (!$patn) { $patn = self::ENC_PATTERN; }
        return preg_replace_callback_array([
            '/[\x00-\x1f\x7f]/' => function() { return ''; },
            $patn => function($matches) { return rawurlencode($matches[0]); }
        ], $str);
    }

    /**
     * Replacement for pre-2.99 cms_htmlentities(), as was used for
     * encoding during URL construction. Affects only ' &<>"\'!$' chars.
     * @since 2.99
     * @deprecated since 2.99
     * $param string $str
     * @return string
     */
    protected static function urlentities(string $str)
    {
        $ret = preg_replace('/[\x00-\x1f\x7f]/', '', $str);
        return str_replace(
            ['&',    ' ',    '>',   '<',   '"',     '!',    "'",    '$'],
            ['&amp;','&#32;','&gt;','&lt;','&quot;','&#33;','&#39;','&#36;'],
        $ret);
    }

    /**
     * Generate get-parameter for a job-type
     *
     * @param int $type job-type value (0..2)
     * @param bool $first Optional flag whether this is the first get-parameter. Default false
     * @param int $format Optional format enumerator 0..3. Default 2.
     *  See RequestParameters::create_action_params()
     * @return string
     */
    protected static function create_jobtype(int $type, bool $first = false, int $format = 2) : string
    {
        [$sep, $enc] = self::modes($format);
        $text = ($first) ? '' : $sep;
        switch ($enc) {
            case 0:
                return $text . CMS_JOB_KEY.'='.$type;
            case 1:
                return $text . self::urlentities(CMS_JOB_KEY).'='.$type; // prob. does nothing
            case 2:
                return $text . rawurlencode(CMS_JOB_KEY).'='.$type; // nothing clean1-worthy in here
        }
        return '';
    }

    /**
     * Generate get-parameters for use in a job-URL
     *
     * @param array $parms URL get-parameters. See RequestParameters::create_action_params()
     * @param bool $onetime Optional flag whether the URL is for one-time use. Default false.
     * @param int $format Optional format enumerator 0..3. Default 2
     *  See RequestParameters::create_action_params()
     * @return string
     */
    public static function create_job_params(array $parms, bool $onetime = false, int $format = 2) : string
    {
        if (isset($parms['action'])) {
            $str = $parms['action'];
            $parms['id'] = self::JOBID;
        } else {
            $str = 'job';
        }
        $str .= get_site_UUID();
        if ($onetime) {
            $db = SingleItem::Db();
            $tmpl = 'INSERT INTO '.CMS_DB_PREFIX."job_records (token,hash) VALUES ('%s','%s')";
            $chars = Crypto::random_string(20, true);
//          $chars = preg_replace('/[^\w.~!@$()*#\-+]/', '', $chars); // pre-empt behaviour of create_action_params()
            $swaps = str_shuffle('eFgHkLmN24680'); // >= 7 chars
            while (1) {
                $key = str_shuffle($chars);
                $subkey = substr($key, 0, 10);
                $subkey = strtr($subkey, '%+"?&;', $swaps); // replace chars which crap on url-decoding or SQL
                $subkey = strtr($subkey, "'", $swaps[7]); // must do this separately ? (escaping ' fails for strtr?)
                // NOTE $subkey might be encoded in create_action_params() >> clean1()
                $val = hash('tiger128,3', $subkey.$str); // 32-hexits
                $sql = sprintf($tmpl, $subkey, $val);
                $dbr = $db->execute($sql);
                if ($dbr) {
                    $parms[self::JOBONCEKEY] = $subkey;
                    break;
/* DEBUG        } else {
                    debug_bt_to_log();
                    throw new Exception("Failed to record job token: $subkey");
                    break;
*/
                }
            }
        } else {
            $parms[self::JOBKEY] = hash('tiger128,3', $str);
        }
        $parms[CMS_JOB_KEY] = 2;

        return self::create_action_params($parms, $format);
    }

    /**
     * Get an URL query-string corresponding to the supplied value,
     * which is probably non-scalar.
     * This allows (among other things) generation of URL content that
     * replicates parameter arrays like $_POST'd parameter values,
     * for passing around and re-use without [de]serialization.
     * It behaves better than PHP http_build_query(), but only interprets 1-D arrays.
     *
     * @param string $key parameter name/key
     * @param mixed  $val Generally an array, but may be some other non-scalar or a scalar
     * @param int $format Optional format enumerator 0..3. Default 2.
     * @see create_action_params()
     * @return string (No leading $sep for arrays)
     */
    public static function build_query(string $key, $val, int $format = 0) : string
    {
        [$sep, $enc] = self::modes($format);
        switch ($enc) {
            case 0:
            case 1: // apply self::urlentities(), for $format 0
                $eq = '=';
                $sp = $sep;
                break;
            case 2:
                $eq = '~~~';
                $sp = '___';
                break;
        }
        $multi = false;

        if (is_array($val)) {
            $out = '';
            $first = true;
            foreach ($val as $k => $v) {
                if ($first) {
                    $out .= ($enc != 1) ? $key.'['.$k.']' : self::urlentities($key.'['.$k.']');
                    $first = false;
                } else {
                    $out .= $sp;
                    $out .= ($enc != 1) ? $key.'['.$k.']' : self::urlentities($key.'['.$k.']');
                    $multi = true;
                }
                if (!is_scalar($v)) {
                    try {
                        $v = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        if (json_last_error() != JSON_ERROR_NONE) {
                            $v = rawurlencode(json_last_error_msg());
                        }
                    } catch (Throwable $t) {
                        $v = rawurlencode($t->GetMessage());
                    }
                    $out .= ($enc != 1) ? $eq.$v : $eq.self::urlentities($v);
                } elseif ($v !== '') {
                    $out .= ($enc != 1) ? $eq.$v : $eq.self::urlentities($v);
                }
            }
        } elseif (!is_scalar($val)) {
            try {
                $val = json_encode($val, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if (json_last_error() != JSON_ERROR_NONE) {
                    $val = rawurlencode(json_last_error_msg());
                }
            } catch (Throwable $t) {
                $val = rawurlencode($t->GetMessage());
            }
            $out .= ($enc != 1) ? $key.$eq.$val : self::urlentities($key).$eq.self::urlentities($val);
        } elseif ($val !== '') { //just in case, also handle scalars
            $out .= ($enc != 1) ? $key.$eq.$val : self::urlentities($key).$eq.self::urlentities($val);
        } else {
            $out .= ($enc != 1) ? $key : self::urlentities($key);
        }

        if ($enc == 2) {
            $out = str_replace($eq, '=', self::clean1($out));
            if ($multi) {
                $out = str_replace($sp, $sep, $out);
            }
        }
        return $out;
    }

    /**
     * Generate get-parameters for use in an URL (not necessarily one
     * which initiates a module-action)
     *
     * @param array $parms URL get-parameters. Should include mact-components
     *  and action-parameters (if any), and generic-parameters (if any)
     *  Any non-trailing value which is empty (like &key=) will be cleaned (to &key)
     *  Trailing empties are retained, to support 'URL-prefix' creation
     * @param int $format Optional format enumerator
     *  0 = entitized ' &<>"\'!$' chars in parameter keys and values,
     *   '&amp;' for parameter separators except 'mact' (default, back-compatible)
     *  1 = proper: rawurlencoded keys and values, '&amp;' for parameter separators
     *  2 = best for most contexts: as for 1, except '&' for parameter separators
     *  3 = displayable: no encoding, all html_entitized, probably not usable as-is
     *   BUT the output must be entitized upstream, it's not done here
     * @return string
     */
    public static function create_action_params(array $parms, int $format = 0) : string
    {
        [$sep, $enc] = self::modes($format); // TODO just entitizing for format 0
/*        if (isset($parms[CMS_JOB_KEY])) {
            $type = $parms[CMS_JOB_KEY];
            unset($parms[CMS_JOB_KEY]);
        } else {
            $type = -1;
        }
        ksort($parms); //security key(s) lead TODO OR follow if $enc == 0
*/
        if (isset($parms['module']) && isset($parms['id']) && isset($parms['action'])) {
            $module = trim($parms['module']);
            $id = trim($parms['id']);
            $action = trim($parms['action']);
            $inline = !empty($parms['inline']) ? 1 : 0;
            unset($parms['module'], $parms['id'], $parms['action'], $parms['inline']);
            $parms = ['mact' => "$module,$id,$action,$inline"] + $parms;
        }
        //security key(s) lead, or trail in legacy-mode
        $pre = [];
        foreach ($parms as $key => $val) {
            if (startswith($key, CMS_SECURE_PARAM_NAME)) {
                $pre[$key] = $val;
                unset($parms[$key]);
            }
        }
        if ($pre) {
            if ($format > 0) {
                $parms = $pre + $parms;
            } else {
                $parms += $pre;
            }
        }

        $text = '';
        $first = true;
        foreach ($parms as $key => $val) {
            if (is_scalar($val)) {
                switch ($enc) {
                    case 0:
                        $key = self::clean1($key, '/\x00/'); // minimally sanitize it
                        $val = self::clean1($val, '/\x00/');
                        break;
                    case 1:
                        $key = self::urlentities($key);
                        $val = self::urlentities($val);
                        break;
                    case 2:
                        $key = self::clean1($key);
                        $val = self::clean1($val);
                        break;
                }
                if ($first) {
                    $text .= $key;
                    $first = false;
                } else {
                    $text .= $sep.$key;
                }
                $text .= '='.$val; // embedded empty $vals later removed
            } else {
                if ($first) {
                    $first = false;
                } else {
                    $text .= $sep;
                }
                $text .= self::build_query($key, $val, $format);
            }
        }
/*     if ($type != -1) {
            $text .= self::create_jobtype($type, false, $format);
        }
*/
        if ($format == 0) {
            $text = str_replace('&amp;mact', '&mact', $text);  // legacy-compliance
        }
        // strip embedded (not trailing) empty values
        $text = str_replace('='.$sep, $sep, $text);
        return $text;
    }

    /**
     * Validate security parameters in $parms
     *
     * @param array $parms Some/all current-request parameters
     * @return boolean indicating validity
     */
    public static function check_secure_params(array $parms)
    {
        if (isset($parms[self::JOBKEY])) {
            $str = $parms['action'] ?? 'job';
            $str .= get_site_UUID();
            return $parms[self::JOBKEY] == hash('tiger128,3', $str);
        }
        if (isset($parms[self::JOBONCEKEY])) {
            $key = $parms[self::JOBONCEKEY];
            $key2 = rawurldecode($key);
            $db = SingleItem::Db();
            $row = $db->getRow('SELECT token,hash FROM '.CMS_DB_PREFIX.'job_records WHERE token=? OR token=?', [$key, $key2]);
            if (!$row) {
                return false;
            }
            $db->execute('DELETE FROM '.CMS_DB_PREFIX.'job_records WHERE token = '.$db->qStr($row['token']));
            if ($key != $key2 && $row['token'] == $key2) { $key = $key2; }

            $str = $parms['action'] ?? 'job';
            $hash = hash('tiger128,3', $key.$str.get_site_UUID());
            return $hash == $row['hash'];
        }
        return (!isset($parms[CMS_JOB_KEY]) || $parms[CMS_JOB_KEY] != 2);
    }

    /**
     * Return array of request parameters, $_REQUEST or else merged $_POST, $_GET
     *
     * @return array
     */
    protected static function get_request_params() : array
    {
        if (!empty($_REQUEST)) {
            return $_REQUEST;
        }
        return array_merge($_POST, $_GET);
    }

	/**
	 * Return parameters in the current request whose key begins with $id
	 * $id is stripped from the start of returned keys.
	 * This was formerly ModuleOperations::GetModuleParameters()
	 *
	 * @param string $id module-action identifier
	 * @param bool   $clean since 2.99 optional flag whether to pass
	 *  non-numeric string-values via CMSMS\de_specialize() Default false.
	 * @param mixed $names since 2.99 optional strings array, or single,
	 *  or comma-separated series of, wanted parameter key(s)
	 * @return array, maybe empty
	 */
	public static function get_identified_params(string $id, bool $clean = false, $names = '') : array
	{
		$params = [];

		$len = strlen($id);
		if( $len ) {
//			$raw = self::TODO();
			if( $names ) {
				if( is_array($names) ) {
					$matches = $names;
				}
				else {
					$matches = explode(',',$names);
				}
				$matches = array_map(function($val) { return trim($val); },$matches);
			}
			else {
				$matches = FALSE;
			}
//			foreach( $raw as $key=>$value ) {
			foreach( $_REQUEST as $key => $value ) {
				if( strncmp($key,$id,$len) == 0 ) {
					$key = substr($key,$len);
					if( !$matches || in_array($key,$matches) ) {
						if( $clean && is_string($value) && !is_numeric($value) ) {
							$value = de_specialize($value);
						}
						$params[$key] = $value;
					}
				}
			}
		}
		return $params;
	}

    /**
     * Return parameters interpreted from parameters in the current request.
     * Non-action parameters are ignored.
     *
     * @return array maybe empty
     */
    public static function get_action_params()
    {
        $parms = [];
        $source = self::get_request_params();
        if (!empty($source['mact'])) {
            $parts = explode(',', $source['mact'], 4);
            $parms['module'] = trim($parts[0]);
            $parms['id'] = (isset($parts[1])) ? trim($parts[1]) : '';
            $parms['action'] = (isset($parts[2])) ? trim($parts[2]) : 'defaultadmin';
            $parms['inline'] = (!empty($parts[3])) ? 1 : 0;
        }

        if (isset($parms['id']) && $parms['id'] !== '') {
            $tmp = $source['mact'] ?? null;
            unset($source['mact']);

            $id = $parms['id'];
            $len = strlen($id);
            foreach ($source as $key => $val) {
                if (strncmp($key, $id, $len) == 0) {
                    $key2 = substr($key,$len);
                    if (is_scalar($val)) {
                        if (($dec = self::get_json($val))) {
                            $parms[$key2] = $dec;
                        } else {
                            $parms[$key2] = $val;
                        }
                    } else {
                        $parms[$key2] = $val;
                    }
                }
            }
            if ($tmp) $source['mact'] = $tmp;
        }

        if (isset($source[CMS_JOB_KEY])) {
            $val = filter_var($source[CMS_JOB_KEY], FILTER_SANITIZE_NUMBER_INT); //OR (int)
            if ($val == 2) {
                //TODO maybe a job-URL, check/process that
            }
        }
        return $parms;
    }

    /**
     * Return the non-action parameters in the current request.
     *
     * @param string $pref Optional, if non-empty, also ignore parameters whose
     *  key begins with this
     * @return array
     */
    public static function get_general_params($pref = '') : array
    {
        $source = self::get_request_params();
        $l = strlen(''.$pref);
        if ($l > 0) {
            $parms = [];
            foreach ($source as $key => $val) {
                if (strncmp($key, $pref, $l) != 0) {
                    $parms[$key] = $val;
                }
            }
        } else {
            $parms = $source;
        }

        if (isset($source['id'])) {
            $pref = $source['id'];
            $l = strlen($pref);
            if ($l > 0) {
                $tmp = [];
                foreach ($parms as $key => $val) {
                    if (strncmp($key, $pref, $l) != 0) {
                        $tmp[$key] = $val;
                    }
                }
                $parms = $tmp;
            }
        }

        return array_diff_key($parms, [
         'mact' => 1,
         'module' => 1,
         'id' => 1,
         'action' => 1,
         'inline' => 1,
        ]);
    }

    /**
     * Return values of specified parameter(s) (if they exist) in the current request
     * Null is returned for each parameter which doesn't exist.
     *
     * @param mixed $keys Optional wanted parameter-name(s) string | string[]
     *  String may be '' or '*', or array may be []. Default [], hence all parameters
     * @return mixed associative array | single value
     *   Values are verbatim i.e. not cleaned at all.
     *   Value is null for any parameter which doesn't exist.
     */
    public static function get_request_values($keys = [])
    {
        $multi = true;
        if ($keys) {
            if (!is_array($keys)) {
                if ($keys != '*') {
                    $multi = false;
                    $keys = [$keys];
                } else {
                    $keys = [];
                }
            }
        } else {
            $keys = [];
        }

        $source = self::get_request_params();
        if (!empty($source['mact'])) {
            $parts = explode(',', $source['mact'], 4);
            $source['module'] = trim($parts[0]);
            $source['id'] = $id = trim($parts[1]);
            $source['action'] = trim($parts[2]);
            $source['inline'] = (!empty($parts[3])) ? 1 : 0;
            $len = strlen($id);
            $strip = $len > 0;
        } else {
            $id = '';
            $strip = false;
        }

        if (!$keys) {
            $keys = array_keys($source);
        }

        $parms = array_fill_keys($keys, null);
        foreach ($keys as $key) {
            switch ($key) {
            case 'module':
            case 'id':
            case 'action':
                if (isset($source[$key])) {
                    $val = trim($source[$key]); break;
                } else {
                    continue 2;
                }
            case 'inline':
                if (isset($source[$key])) {
                    $val = (int)$source[$key]; break;
                } else {
                    continue 2;
                }
            default:
                if ($strip && isset($source[$id.$key])) {
                    $val = $source[$id.$key];
                    if (($dec = self::get_json($val))) {
                        $val = $dec;
                    }
                } elseif (isset($source[$key])) {
                    $val = $source[$key];
                    if (($dec = self::get_json($val))) {
                        $val = $dec;
                    }
                } else {
                    continue 2;
                }
            }
            $parms[$key] = $val;
        }
        return ($multi) ? $parms : reset($parms);
    }

    /**
     * Return json-decode()'d version of $val, if possible
     * @param mixed $val normally string
     * @return mixed decoded parameter | false
     */
    protected static function get_json($val)
    {
        if (!$val || !is_string($val) || is_numeric($val)) {
            return false;
        }

        $cleaned = ltrim($val);
        if (!$cleaned || !in_array($cleaned[0], ['{', '['])) {
            return false;
        }

        $dec = json_decode($cleaned, true);
        if ($dec && $dec != $cleaned && (json_last_error() == JSON_ERROR_NONE)) {
            return $dec;
        }
        return false;
    }
} // class
