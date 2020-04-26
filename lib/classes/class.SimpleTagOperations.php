<?php
#Class to process simple-plugins (a.k.a. user-defined-tags)
#Copyright (C) 2004-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Ted Culp and all other contributors from the CMSMS Development Team.
#This file is part of CMS Made Simple <http://cmsmadesimple.org>
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

namespace CMSMS;

use CMSMS\AppSingle;
use DeprecationNotice;
use Throwable;
use const CMS_DB_PREFIX;
use const CMS_DEPREC;
use const CMS_FILETAGS_PATH;
//use function file_put_contents;
//use CMSMS\SysDataCache;

/**
 * Simple-plugin related functions
 * @since 2.9
 * @since 1.5 (or before) as UserTagOperations
 *
 * This class supports file-stored as well as dB-stored 'simple' plugins.
 * Such plugins are simple in the sense that their content is limited to
 * 'safe' functionality, because the content has probably been added by a
 * not-necessarily-trustworthy admin user (or even some malefactor).
 *
 * @package CMS
 * @license GPL
 */
final class SimpleTagOperations
{
	/**
	 * Filename extension of simple-plugin files, something that the web server won't execute
	 */
	const PLUGEXT = '.phphp'; //c.f. vanilla 2.3BETA uses .cmsplugin, but those probably have different content

	/**
	 * Maximum fake-id used for identifying simple-plugin files
	 * Any integer < -1 will do
	 */
	const MAXFID = -10;

	/* *
	 * @ignore
	 */
//	private static $_instance;

	/**
	 * @var array Intra-request plugins cache, each member like
	 *  tagname => [ tagid, callable|null ]
	 * Populated when, and to the extent, needed. A fake id (self::MAXFID)
	 * is recorded for file-stored plugins. Null is recorded for the
	 * callable until that's initialized.
	 * @ignore
	 */
	//TODO consider making this a session-cache (for admin) but for frontend ?
	private $_cache = [];
	/**
	 * @var bool whether $_cache[] has been fully-populated (by ListSimpleTags())
	 * which is unlikely since checks for individual tags will mostly be from a
	 * frontend request, but sometimes involve a selection from the list.
	 * @ignore
	 */
	private $_loaded = false;
	/**
	 * @var array Intra-request failed-UDT-checks cache, each member like
	 *  name => 0
	 * Populated when any check for a possible usr-plugin turns out to be invalid.
	 * @ignore
	 */
	private $_misses = [];

	/* *
	 * @ignore
	 * Needed when using local $_instance
	 */
//	private function __construct() {}

	/**
	 * @ignore
	 */
	private function __clone() {}

	/**
	 * Support for pre-2.3 method-names, and simplified explicit tag-running
	 * @ignore
	 * @param string $name simple-plugin name
	 * @param array $args plugin-API variable(s) in some form.
	 *  $args OR $args[0] should include: [0]=$params[], [1]=$template/smarty object
	 */
	public function __call($name, $args)
	{
		if (strpos($name, 'User') !== false) {
			$sn = str_replace('User', 'Simple', $name);
			if (method_exists($this, $sn)) {
				try {
					return $this->$sn(...$args);
				} catch (Throwable $t) {
					return;
				}
			}
			//fall into running a tag whose name includes 'User'
		}
		try {
			return $this->CallSimpleTag($name, ...$args);
		} catch (Throwable $t) {
			return '<span style="font-weight:bold;color:red;">Plugin error!</span>'; //lang() normally N/A for frontend requests
		}
	}

	/**
	 * Process a smarty-call to get the output from a simple-plugin named $name
	 * @since 2.9
	 *
	 * @param string $name plugin identifier (as used in tags)
	 * @param array $args plugin-API variable(s)
	 *  [0] if present = array of $params for the plugin
	 *  [1] if present = template object (Smarty_Internal_Template or wrapper)
	 */
	public static function __callStatic($name, $args)
	{
		$handler = AppSingle::SimpleTagOperations()->GetHandler($name);
		try {
			return $handler(...$args);
		} catch (Throwable $t) {
			return '<span style="font-weight:bold;color:red;">Plugin error!</span>'; //lang() normally N/A for frontend requests
		}
	}

	/**
	 * Get the singleton instance of this class
	 * @deprecated since 2.9 instead use CMSMS\AppSingle::SimpleTagOperations()
	 * @return self i.e. SimpleTagOperations
	 */
	public static function get_instance() : self
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method','CMSMS\AppSingle::SimpleTagOperations()'));
		return AppSingle::SimpleTagOperations();
	}

	/**
	 * Return filesystem path which would apply to a simple-plugin named $name
	 * @since 2.9
	 * @param string $name plugin name or name(s)-pattern
	 * @return string absolute path
	 */
	public function FilePath(string $name) : string
	{
		return CMS_FILETAGS_PATH.DIRECTORY_SEPARATOR.$name.self::PLUGEXT;
	}

	/**
	 * Determine whether $id represents a file-stored plugin
	 * @since 2.9
	 * @param mixed $id int|string identifier
	 * @return bool
	 */
	public function IsFileID($id) : bool
	{
		return (int)$id <= self::MAXFID;
	}

	/**
	 * Check whether $name is acceptable for a simple-plugin.
	 * Specifically, it matches PHP naming rules i.e. starts with a letter
	 * (ASCII|UTF8) or _, followed by any number of such letters or digits or _'s.
	 * No actual-file name-duplication check here.
	 * @since 2.9
	 *
	 * @param string $name plugin identifier (as used in tags). A reference, so it can be trim()'d
	 * @return bool indicating success
	 */
	public function IsValidName(string &$name) : bool
	{
		$name = trim($name);
		if ($name) {
			//see https://www.php.net/manual/en/functions.user-defined.php
			return preg_match('~^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$~', $name) != 0;
		}
		return false;
	}

	/**
	 * Determine whether $key is a member of $arr, whatever the case of $key is
	 * @internal
	 *
	 * @param string $key
	 * @param array $arr
	 * @return bool
	 */
	protected function CacheHas(string $key, array &$arr) : bool
	{
		return isset($arr[$key]) ||
			in_array(strtolower($key), array_map('strtolower', array_keys($arr)));
	}

	protected function CacheSet(string $key, $val, array &$arr)
	{
		$ik = null; //func($key, $arr);
		if ($ik) { $arr[$ik] = $val; } else { $arr[$key] = $val; }
	}

	protected function CacheGet(string $key, array &$arr)
	{
		$ik = null; //func($key,$arr)
		return ($ik) ? $arr[$ik] : null;
	}

	/**
	 * Establish local data cache for all simple-plugins
	 *
	 * @ignore
	 * @deprecated since 2.9 does nothing. There is no SysDataCache for simple-plugins
	 * @internal
	 */
	public static function setup()
	{
	}

	/**
	 * Cache all information about all simple plugins (onetime only)
	 * @deprecated since 2.9 does nothing. Local cache is populated on demand
	 * and to the extent needed, by class methods e.g. ListSimpleTags()
	 */
	public function LoadSimpleTags()
	{
	}

	/* *
	 * Migrate plugin from dB-storage to file-storage.
	 * Note: there is no operational advantage from such change.
	 * @since 2.9
	 *
	 * @param string $name Plugin name
	 * @return bool indicating success
	 */
/*disabled	public function ExportFile(string $name) : bool
	{
		if ($this->CacheHas($name, $this->_misses)) return false;
		if (!$this->CacheHas($name, $this->_cache)) {
			$this->SimpleTagExists($name); //populate the relevant cache
		}
		if ($this->CacheHas($name, $this->_cache)) {
			if (!$this->IsFileID($this->_cache[$name][0])) {
				$params = $this->GetSimpleTag($name, '*');
				$params['id'] = self::MAXFID;
				$res = $this->SetFileTag($name, $params);
				if ($res) {
					$db = AppSingle::db();
					$query = 'DELETE FROM '.CMS_DB_PREFIX.'simpleplugins WHERE name=?';
					$db->Execute($query, [$name]);
				}
				return $res;
			}
		}
		return false;
	}
*/
	/* *
	 * Migrate plugin from file-storage to dB-storage
	 * Note: there is no operational advantage from such change.
	 * @since 2.9
	 *
	 * @param string $name Plugin name
	 * @return bool indicating success
	 */
/*disabled	public function ImportFile(string $name) : bool
	{
		if ($this->CacheHas($name, $this->_misses)) return false;
		if (!$this->CacheHas($name, $this->_cache)) {
			$this->SimpleTagExists($name); //populate the relevant cache
		}
		if ($this->CacheHas($name, $this->_cache)) {
			if ($this->IsFileID($this->_cache[$name][0])) {
				$params = $this->GetFileTag($name, '*');
				$params['id'] = -1;
				$res = $this->SetSimpleTag($name, $params);
				if ($res) {
					$fp = $this->FilePath($name);
					@unlink($fp);
				}
				return $res;
			}
		}
		return false;
	}
*/
	/**
	 * Retrieve property|ies of the file-stored plugin named $name
	 *
	 * @param string $name Plugin name
	 * @param mixed $props string|strings[] Optional property name(s)
	 * (comma-separated ok), or '*', or falsy (hence  an existence-flag).
	 * '*' gets all available of 'description','parameters','license','code'.
	 * Default 'code'
	 * @return mixed assoc array of requested properties | single-prop value | true (exists) | null if N/A
	 */
	protected function GetFileTag(string $name, $props = 'code') //UDTfiles
	{
		$fp = $this->FilePath($name);
		if (is_file($fp)) {
			$cont = file_get_contents($fp);
			if (!$cont) {
				return null;
			}
			if (!$props) {
				return true; //it exists
			}
			if (!is_array($props)) {
				if ($props == '*') {
					$props = ['id','description','parameters','license','code'];
				} else {
					$props = explode(',',$props);
				}
			}
			$res = array_combine($props, array_fill(0, count($props), ''));
			if (isset($res['id'])) { $res['id'] = self::MAXFID; }
			$ps = strpos($cont, '<metadata>');
			$pe = strpos($cont, '</metadata>', $ps);
			if ($ps !== false && $pe !== false) {
				$xmlstr = substr($cont, $ps, $pe - $ps + 11);
				$xml = simplexml_load_string($xmlstr);
				if ($xml !== false) {
					if (in_array('description', $props)) {
						$val = (string)$xml->description;
						$res['description'] = ($val) ? htmlspecialchars_decode($val, ENT_XML1) : '';
					}
					if (in_array('parameters', $props)) {
						$val = (string)$xml->parameters;
						$res['parameters'] = ($val) ? htmlspecialchars_decode($val, ENT_XML1) : '';
					}
					if (in_array('license', $props)) {
						$val = (string)$xml->license;
						$res['license'] = ($val) ? htmlspecialchars_decode($val, ENT_XML1) : '';
					}
				}
				if (in_array('code', $props)) {
					$ps = strpos($cont, '*/', $pe);
					$res['code'] = ($ps !== false) ? trim(substr($cont, $ps + 2), " \t\n\r") : '';
				}
			} else {
				// malformed tag file !
				if (in_array('code', $props)) {
					// skip any introductory comment(s)
					$skips = '~^\s*(<\?php|#|//)~'; //ignore lines starting like this
					$patn2 = '~/\*~'; //start of multi-line comment
					$patn3 = '~\*/~'; //end of multi-line comment
					$d = 0;
					$lines = preg_split('/$\R?^/m', $cont);
					foreach ($lines as $r=>$l) {
						if (preg_match($skips, $l)) {
							continue;
						} elseif (preg_match($patn2, $l)) {
							++$d;
						} elseif (preg_match($patn3, $l)) {
							if (--$d == 0) {
								//too bad if code starts on the same line as the '*/' !
								break;
							} elseif ($d < 0) $d = 0; //format error
						} else {
							break;
						}
					}
					$res['code'] = implode("\n", array_slice($lines, $r, count($lines) - $r, true));
				}
			}
			return (count($res) > 1) ? $res : reset($res);
		}
		$this->_misses[$name] = 0;
		return null;
	}

	/**
	 * Retrieve property|ies of the named simple-plugin.
	 *
	 * @param string $name Plugin name
	 * @param mixed $props @since 2.9 string|strings[] Optional database
	 *  simpleplugins-table field name(s) (comma-separated ok), or '*',
	 *  or falsy for an existence-check. Default 'code'.
	 *
	 * @return mixed array | field-value | true (exists) | null upon error
	 */
	public function GetSimpleTag(string $name, $props = 'code')
	{
		if ($this->CacheHas($name, $this->_cache)) {
			$filetag = $this->IsFileID($this->_cache[$name][0]);
		} else {
			$filetag = null; //i.e. not yet known
		}
		if ($filetag) {
			return $this->GetFileTag($name, $props); //UDTfiles
		} else {
			//definite dB-storage, or unknown
			$scrub = false;
			if (is_array($props)) {
				$multi = count($props) > 1;
				//always get id value, for local cache data at least
				if (!in_array('id', $props)) {
					array_shift($props, 'id');
					$scrub = true;
				}
				$fields = implode(',', $props);
			} elseif ($props) {
				$multi = ($props == '*' || strpos(',', $props) !== false);
				//always get id
				if ($props != '*' && strpos($props, 'id') === false) {
					$fields = 'id,'.$props;
					$scrub = true;
				} else {
					$fields = $props;
				}
			} else {
				$multi = false;
				$fields = 'id';
			}
			$db = AppSingle::db();
			$query = 'SELECT '.$fields.' FROM '.CMS_DB_PREFIX.'simpleplugins WHERE name=?';
			//TODO caseless name-match: table|field definition is *_ci ?
			$dbr = $db->GetRow($query, [$name]);
			if ($dbr) {
				if ($filetag === null) {
					$this->_cache[$name] = [(int)$dbr['id'],  null]; //remember it
				}
				if ($scrub) {
					unset($dbr['id']);
				}
				return ($props) ? (($multi) ? $dbr : reset($dbr)) : true;
			}
			$res = $this->GetFileTag($name, $props); //UDTfiles
			if ($res) {
				if ($filetag === null) {
					$this->_cache[$name] = [self::MAXFID, null]; //remember it
				}
				return $res;
			}
			$this->_misses[$name] = 0;
			return null;
		}
	}

	/**
	 * Check whether $name is acceptable for a simple-plugin, and if so,
	 * whether the corresponding dB-stored or file-stored plugin exists.
	 * @since 1.10
	 *
	 * @param $name plugin identifier
	 * @return bool since 2.9, formerly $name|false
	*/
	public function SimpleTagExists(string $name) : bool
	{
		if (!$this->IsValidName($name)) {
			$this->_misses[$name] = 0;
			return false;
		}
		//TODO support caseless matches
		if ($this->CacheHas($name, $this->_cache)) { return true; }
		if ($this->CacheHas($name, $this->_misses)) { return false; }
		return ($this->GetSimpleTag($name, '') != false);
	}

	/**
	 * Test whether a simple-plugin with the specified name exists, after (if
	 * check_functions is true) testing whether ANY plugin with that name has been registered.
	 * @since 2.9 this does not also check for a matching system-plugin - all
	 *  system-plugins are automatically handled by smarty
	 *
	 * @param string $name			The name to test
	 * @param bool   $check_functions Optional flag. Default true. First, test if a plugin with such name is
	 *  already registered with smarty
	 */
	public function SmartyTagExists(string $name, bool $check_functions = true) : bool
	{
		if ($check_functions) {
			// might be registered by something else... a module perhaps
			$smarty = Smarty::get_instance();
			if ($smarty->is_registered($name)) {
				return true;
			}
		}

		return $this->SimpleTagExists($name);
	}

	/**
	 * Save file for simple-plugin named $name. The file will be created or
	 * overwitten as appropriate, except that renaming a file-stored plugin
	 * fails if the new name already exists.
	 * The file's content will be like <?php/*XML*\/CODE.
	 * The xml facilitates structured interaction with the plugin content
	 * via the admin console.
	 *
	 * @param string $name Tag name
	 * @param array  $meta Assoc array of sanitized tag properties with any/all of
	 *  'id','oldname','description','parameters','license','detail'
	 * @return mixed bool indicating success | 2-member array,
	 *  [0] = bool indicating success
	 *  [1] = string lang-key (no spaces) or actual message, normally '' on success
	 */
	public function SetFileTag(string $name, array $params)
	{
		$bare = empty($params['detail']);
		if (!$this->IsValidName($name)) {
			$this->_misses[$name] = 0;
			return ($bare) ? false : [false, 'error_splg_name'];
		}

		$code = trim($params['code'], " \t\n\r");
		if ($code) {
			$code = preg_replace_callback_array([
				'/^\s*<\?php[\s\r\n]*/i' => function() { return ''; },
				'/[\s\r\n]*\?>[\s\r\n]*$/' => function() { return ''; },
				], $code);
			try {
				eval('if(0){ '.$code.' }'); // no code execution
			} catch (Throwable $t) {
				return ($bare) ? false : [false, 'Plugin '.$name.' code error: '.$t->GetMessage()];
			}
			// More-complex code-sanitization cannot reasonably be performed
			// out-of-context ($params etc etc).
			// We'll trust the provided code as-is. But run it in a sandbox, if we can ...
		} else {
			return ($bare) ? false : [false, 'error_splg_nocode'];
		}

		$d = (!empty($params['description'])) ?
			'<description>'."\n".htmlspecialchars(trim($params['description']), ENT_XML1)."\n".'</description>':
			'<description></description>';
		$p = (!empty($params['parameters'])) ?
			'<parameters>'."\n".htmlspecialchars(trim($params['parameters']), ENT_XML1)."\n".'</parameters>':
			'<parameters></parameters>';
		$l = (!empty($params['license'])) ?
			'<license>'."\n".htmlspecialchars(trim($params['license']), ENT_XML1)."\n".'</license>':
			'<license></license>';
		//no additional security-related code inserted, that's in the code which include's the file for use
		$out = <<<EOS
<?php
/*
<metadata>
$l
$d
$p
</metadata>
*/
$code

EOS;
		$oldname = $params['oldname'] ?? '';
		if ($oldname && $name != $oldname) {
			//update cache if renamed
			unset($this->_cache[$oldname]);
			$this->_cache[$name] = [self::MAXFID, null]; //remember it
			//remove old tagfile
			$fp = $this->FilePath($oldname);
			@unlink($fp);
		}
		$fp = $this->FilePath($name);
		$res = @file_put_contents($fp, $out, LOCK_EX);
		return ($bare) ? $res : [$res, (($res) ? '' : 'error_internal')];
	}

	/**
	 * Insert/store or update a simple-plugin in the database or in file.
	 *
	 * @param string $name   plugin name now, perhaps different from $params[oldname]
	 * @param varargs $args  since 2.9 normally just a single assoc array of
	 *  additional properties, some/all of
	 *  'id' -1 for new plugin, > 0 for an existing dB-stored plugin,
	 *    <= self::MAXFID for an existing file-stored plugin
	 *  'oldname' string simple-plugin recorded name, or '' for new plugin
	 *  'code'
	 *  'description'
	 *  'parameters'
	 *  'license' ignored for a dB-stored plugin
	 *  'detail' bool indicating caller wants a status message
	 * @return mixed bool indicating success | 2-member array,
	 *  [0] = bool indicating success
	 *  [1] = string lang-key (no spaces) or actual message, normally '' on success
	 */
	public function SetSimpleTag(string $name, ...$args)
	{
		if (count($args) == 1 && is_array($args[0])) {
			$params = $args[0];
		} else { // pre-2.3 API
			$params = ['id'=>-1, 'code'=>$args[0], 'description'=>$args[1] ?? ''];
		}
		$bare = empty($params['detail']);

		if (!$this->IsValidName($name)) {
			$this->_misses[$name] = 0;
			return ($bare) ? false : [false, 'error_splg_name'];
		}

		$val = $params['code'] ?? '';
		if ($val) {
			$code = preg_replace_callback_array([
				'/^[\s\r\n]*<\?php[\s\r\n]*/i' => function() { return ''; },
				'/[\s\r\n]*\?>[\s\r\n]*$/' => function() { return ''; },
				'/    /' => function() { return "\t"; }
			], $val);
		} else {
			$code = $val;
		}

		// More-complex code-validation runs afoul of inherent $params[] usage,
		// namespaces etc etc, so cannot reasonably be performed out-of-context.
		// We'll have to trust it! or better, always run in a sandbox.
		try {
			eval('if(0){ '.$code.' }'); // no code execution
		} catch (Throwable $t) {
			return ($bare) ? false : [false, 'Plugin '.$name.' code error: '.$t->GetMessage()];
		}

		$val = $params['oldname'] ?? '';
		if ($val && !is_numeric($val)) {
			$oldname = filter_var($val,FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_BACKTICK|FILTER_FLAG_STRIP_LOW);
		} else {
			$oldname = $val;
		}

		$val = $params['description'] ?? '';
		if ($val && !is_numeric($val)) {
			$description = filter_var($val,FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_BACKTICK);
		} else {
			$description = null;
		}
		$val = $params['parameters'] ?? '';
		if ($val && !is_numeric($val)) {
			$parameters = filter_var($val,FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_BACKTICK);
		} else {
			$parameters = null;
		}

		$id = (int)$params['id'];
		if ($this->IsFileID($id)) {
			$val = $params['license'] ?? '';
			if ($val && !is_numeric($val)) {
				$license = filter_var($val,FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_BACKTICK);
			} else {
				$license = null;
			}
			//pass the sanitized $params[]
			foreach ($params as $key => &$val) {
				if (isset($$key)) { $val = $$key; }
			}
			unset($val);
			// process file-storage UDTfiles
			return $this->SetFileTag($name, $params);
		} elseif ($id == -1 || $id > 0) {
			//upsert dB
			$db = AppSingle::db();
			$tbl = CMS_DB_PREFIX.'simpleplugins';
			if ($id == -1) {
				$query = "INSERT INTO $tbl (name,code,description,parameters) VALUES (?,?,?,?)";
				$dbr = $db->Execute($query,[$name,$code,$description,$parameters]);
				if ($dbr) {
					$id = (int)$dbr;
					$this->_cache[$name] = [$id, null];
				}
				$res = (bool)$dbr;
				return ($bare) ? $res : [$res, (($res) ? '' : 'error_internal')];
			} else {
				//prevent duplicate names
				$query = <<<EOS
UPDATE $tbl SET name=?,code=?,description=?,parameters=?
WHERE id=?
AND NOT id IN (SELECT id FROM $tbl WHERE name=? AND id!=?)
EOS;
				$dbr = $db->Execute($query,[$name,$code,$description,$parameters,$id,$name,$id]);
				$res = (bool)$dbr;
				if ($res) {
					//update cache if renamed
					if ($oldname && $name != $oldname) {
						unset($this->_cache[$oldname]);
						$this->_cache[$name] = [$id, null];
					}
				}
				return ($bare) ? $res : [$res, (($res) ? '' : 'error_internal')];
			}
		}
		return ($bare) ? false : [false, 'missingparams'];
	}

	/**
	 * Delete simple-plugin named $name.
	 *
	 * @param string $name plugin name
	 * @return bool indicating success
	 */
	public function RemoveSimpleTag(string $name) : bool
	{
		if ($this->CacheHas($name, $this->_misses)) return false;
		if (!$this->CacheHas($name, $this->_cache)) {
			$this->SimpleTagExists($name); //populate the relevant cache
		}
		if ($this->CacheHas($name, $this->_cache)) {
			//$this->_cache[$name] => dB|fake id, callable|null
			if (!$this->IsFileID($this->_cache[$name][0])) {
				//process dB-stored plugin
				// TODO caseless name
				$db = AppSingle::db();
				$query = 'DELETE FROM '.CMS_DB_PREFIX.'simpleplugins WHERE name=?';
				$dbr = $db->Execute($query, [$name]);
				$res = ($dbr != false);
				if ($res) {
					unset($this->_cache[$name]);
				}
				return $res;
			}
			if ($this->IsFileID($this->_cache[$name][0])) {
				//process file-stored plugin
				$fp = $this->FilePath($name);
				if (is_file($fp)) {
					$res = @unlink($fp);
					if ($res) {
						unset($this->_cache[$name]);
					}
					return $res;
				}
			}
		}
		return false;
	}

	/**
	 * List all dB-stored or file-stored simple-plugins
	 *
	 * @return array each member like id => tagname, where id <= self::MAXFID
	 *  to indicate a file-stored plugin
	 */
	public function ListSimpleTags() : array
	{
		if (!$this->_loaded) {
			$db = AppSingle::db();
			$query = 'SELECT name,id FROM '.CMS_DB_PREFIX.'simpleplugins ORDER BY name';
			$out = $db->GetAssoc($query);

			$patn = $this->FilePath('*');
			$files = glob($patn, GLOB_NOESCAPE);
			if ($files) {
				$n = self::MAXFID;
				foreach ($files as $fp) {
					$name = basename($fp, self::PLUGEXT);
					if ($this->IsValidName($name)) $out[$name] = $n--;
				}
			}

			foreach ($out as $name=>$id) {
				if (!$this->CacheHas($name, $this->_cache)) {
					$this->_cache[$name] = [$id, null]; //dB|fake id, no callable yet
				}
			}
			$this->_loaded = true;
		}

		$out = [];
		foreach ($this->_cache as $name => $row) {
			$out[$row[0]] = $name;
		}
		return $out;
	}

	/**
	 * @internal
	 * @param string $name   The name of the user defined tag
	 * @return anonymous function
	 */
	protected function GetHandler(string $name)
	{
		// TODO support caseless $name
		if ($this->CreateTagFunction($name)) {
			if (!empty($this->_cache[$name][1])) {
				return $this->_cache[$name][1];
			}

			if ($this->IsFileID($this->_cache[$name][0])) {
				$strfunc = $this->GetFileTag($name);
				// TODO sanitize $strfunc, in case was externally-changed since last check
			} else {
				$strfunc = $this->GetSimpleTag($name);
			}
			// subject to TODO above, we (naively?) assume no code-change between latest save and now
			$handler = function (&$params = [], $template = null) use ($strfunc)
			{
				// API doc says plugin code can also expect individual variables
				if ($params) {
					extract($params);
				}
				$gCms = AppSingle::App();
				$config = AppSingle::Config();
				$db = AppSingle::db(); // TODO enforce read-only here
				$smarty = $gCms->GetSmarty(); // TODO restrict methods : assign[byref]* or define { } replacements
//				TODO sandbox this :: protect caches, global vars, class properties etc
//				any security-enhancements instigated here could be reversed by malicious eval'd code
//				$fakesmarty = new trapperclass();
				if (!$template) {
					$template = $smarty;
//				} else {
//					$faketemplate = new trapperclass();
				}
//				$db->multi_execute('FLUSH TABLES WITH READ LOCK;SET GLOBAL read_only = 1');

				ob_start();
				try {
					$out = eval($strfunc);
				} catch (Throwable $t) {
//				$db->multi_execute('SET GLOBAL read_only = 0;UNLOCK TABLES');
					ob_end_clean();
					return '';
				}
				if ($out && !is_scalar($out)) {
//				$db->multi_execute('SET GLOBAL read_only = 0;UNLOCK TABLES');
					ob_end_clean();
					return '';
				}
//				$db->multi_execute('SET GLOBAL read_only = 0;UNLOCK TABLES');
				$ret = ob_get_clean().$out;
				$ret = strtr($ret, '`' , '');
//				TODO further sanitize $ret :: text | Element(s), non-dodgy etc
				return $ret;
			};
		} else { //bad call
			$handler = function()
			{
				return '<span style="font-weight:bold;color:red;">Missing plugin: '.$name.'</span>';
			};
		}
		$this->_cache[$name][1] = $handler;

		return $handler;
	}

	/**
	 * If a dB-stored simple-plugin corresponding to $name exists, run it
	 * This supports explicit tag-running. In most instances, the relevant
	 * callable would instead be called directly by smarty.
	 *
	 * @param string $name   The name of the user defined tag
	 * @param array  $params Optional tag parameters. Default []
	 * @param mixed object|null $smarty_ob Optional Smarty_Internal_Template
	 *  (or descendant) for the caller's context
	 * Compiled smarty provides a Smarty_Internal_Template-object as the
	 * 2nd argument to tags. CMSMS\internal\Smarty Is not a sub-class of that.
	 *
	 * @return mixed value returned by tag|false
	 */
	public function CallSimpleTag(string $name, array &$params = [], $smarty_ob = null)
	{
		if (!empty($this->_cache[$name][1])) {
			$func = $this->_cache[$name][1];
		} else {
			$func = $this->GetHandler($name);
		}
		if ($func) {
			return $func($params, $smarty_ob);
		}
		return false;
	}

	/**
	 * If a simple-plugin corresponding to $name exists, arrange for it
	 * to process an event identified by its originator and name.
	 * @since 2.9
	 *
	 * @param string $name plugin identifier (as used in tags)
	 * @param string $originator The name of the event originator, a module-name or 'Core'
	 * @param string $eventname The name of the event
	 * @param array  $params Reference to event parameter(s) provided by
	 *  the originator. They may be altered by the handler.
	 * @return bool
	 */
	public function DoEvent(string $name, string $originator, string $eventname, array &$params)
	{
		if ($originator && $eventname) {
			if (empty($this->_cache[$name][1])) {
				$obj = $this->CreateTagFunction($name);
				if (!$obj) {
					return false;
				}
			}
			$params['sender'] = $originator;
			$params['event'] = $eventname;
			$res = (bool)$this-> CallSimpleTag($name, $params);
			unset($params['sender'], $params['event']);
			return $res;
		}
		return false;
	}

	/**
	 * Return the callable (if any) which smarty can use to process the named plugin.
	 *
	 * @param $name plugin identifier (any case)
	 * @return mixed callable | null
	 */
	public function CreateTagFunction(string $name)
	{
		$name = trim($name);
		if (!$this->CacheHas($name, $this->_cache)) {
			if ($this->CacheHas($name, $this->_misses)) { return null; }
			try {
				$this->SimpleTagExists($name); //populate relevant cache
			} catch (Throwable $t) {
				return null;
			}
		}
		return ($this->CacheHas($name, $this->_cache)) ?
			self::class.'::'.$name : //fake callable triggers self::__callStatic()
			null;
	}
} // class

\class_alias(SimpleTagOperations::class, 'UserTagOperations', false);
