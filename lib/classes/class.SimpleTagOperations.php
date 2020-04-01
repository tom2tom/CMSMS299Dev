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

use CmsApp;
use DeprecationNotice;
use BadMethodCallException;
use RuntimeException;
use Smarty;
use Throwable;
use UnexpectedValueException;
use const CMS_DB_PREFIX;
use const CMS_DEPREC;
use const CMS_UDT_PATH;
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
	const PLUGEXT = '.phphp'; //c.f. vanilla 2.3DEV uses .cmsplugin

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
		}
		catch (Throwable $t) {
			// nothing here
		}
	}

	/**
	 * Get the appropriate simple-plugin file for $name, and run a function with that file included.
	 * @since 2.9
	 *
	 * @param string $name plugin identifier (as used in tags)
	 * @param array $args plugin-API variable(s)
	 *  [0] if present = array of $params for the plugin
	 *  [1] if present = template object (Smarty_Internal_Template or wrapper)
	 * @throws RuntimeException if file is not found (in spite of prior confirmation)
	 */
	public static function __callStatic($name, $args)
	{
		try {
			return self::CallFileTag($name, ...$args);
		}
		catch (Throwable $t) {
			// nothing here
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
		return CMS_UDT_PATH.DIRECTORY_SEPARATOR.$name.self::PLUGEXT;
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
	 * Check whether the content of $name is acceptable for a simple-plugin.
	 * Specifically, it starts with letter (ASCII|UTF8) or _, plus at least 1
	 * such letter or digit or _.
	 * No actual-file name-duplication check here.
	 * @since 2.9
	 *
	 * @param string $name plugin identifier (as used in tags). A reference, so it can be trim()'d
	 * @return bool
	 */
	public function IsValidName(string &$name) : bool
	{
		$name = trim($name);
		if ($name) {
			return preg_match('~^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]+$~', $name) != 0;
		}
		return false;
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
					$props = ['description','parameters','license','code'];
				} else {
					$props = explode(',',$props);
				}
			}
			$res = array_combine($props, array_fill(0, count($props), ''));
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
	 * Retrieve property|ies of the plugin named $name (however stored).
	 *
	 * @param string $name Plugin name
	 * @param mixed $props @since 2.9 string|strings[] Optional dB
	 *  simpleplugins-table field name(s) (comma-separated ok), or '*', or falsy
	 *  for an existence-check. Default 'code'.
	 *
	 * @return mixed array | field-value | true (exists) | null
	 */
	public function GetSimpleTag(string $name, $props = 'code')
	{
		if (isset($this->_cache[$name])) {
			$filetag = $this->_cache[$name][0] < 0;
		} else {
			$filetag = null; //i.e. unknown
		}
		if ($filetag) {
			return $this->GetFileTag($name, $props); //UDTfiles
		} else {
			//definite dB-store, or unknown
			if (is_array($props)) {
				//always get id value
				if (!in_array('id', $props)) {
					array_shift($props, 'id');
				}
				$fields = implode(',', $props);
				$multi = count($props) > 1;
			} elseif ($props) {
				//always get id
				if ($props != '*' && strpos($props, 'id') === false) {
					$fields = 'id,'.$props;
					$multi = true;
				} else {
					$fields = $props;
					$multi = ($props == '*' || strpos(',', $props) !== false);
				}
			} else {
				$fields = 'id';
				$multi = false;
			}
			$db = CmsApp::get_instance()->GetDb();
			$query = 'SELECT '.$fields.' FROM '.CMS_DB_PREFIX.'simpleplugins WHERE name=?';
			$dbr = $db->GetRow($query, [$name]);
			if ($dbr) {
				if ($filetag === null) {
					$this->_cache[$name] = [(int)$dbr['id'],  null]; //remember it
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
	 * @param $name plugin identifier (as used in tags)
	 * @return bool since 2.9, formerly $name|false
	*/
	public function SimpleTagExists(string $name) : bool
	{
		if (!$this->IsValidName($name)) {
			$this->_misses[$name] = 0;
			return false;
		}
		if (isset($this->_cache[$name])) { return true; }
		if (isset($this->_misses[$name])) { return false; }
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
	 * Save simple-plugin file named $name. The file will be created or overwitten
	 * as appropriate. Renaming a plugin fails if the new name already exists.
	 *
	 * @param string $name Tag name
	 * @param array  $meta Assoc array of sanitized tag properties with any/all of
	 *  'id','oldname','description','parameters','license'
	 * @return bool indicating success
	 */
	public function SetFileTag(string $name, array $params) : bool
	{
		if (!$this->IsValidName($name)) {
			$this->_misses[$name] = 0;
			return false;
		}

		$code = trim($code, " \t\n\r");
		if ($code) {
			$code = preg_replace_callback_array([
				'/^\s*<\?php[\s\r\n]*/i' => function() { return ''; },
				'/[\s\r\n]*\?>[\s\r\n]*$/' => function() { return ''; },
				], $code);
			try {
				eval('if(0){ '.$code.' }'); // no code execution
			} catch (Throwable $t) {
				return false; //TODO log'simple-plugin '.$name.' code error: '.$t->GetMessage()];
			}
			// More-complex code-sanitization cannot reasonably be performed
			// out-of-context ($params etc etc).
			// We'll trust the provided code as-is. But run it in a sandbox, if we can ...
		} else {
			return false;
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
		return @file_put_contents($fp, $out, LOCK_EX);
	}

	/**
	 * Insert/store or update a simple-plugin in the database or in file
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
	 * @return bool indicating success TODO API for returning error message
	 */
	public function SetSimpleTag(string $name, ...$args) : bool
	{
		if (!$this->IsValidName($name)) {
			return false; // TODO log 'simple-plugin name error: ',$name
		}

		if (count($args) == 1 && is_array($args[0])) {
			$params = $args[0];
		} else { // pre-2.3 API
			$params = ['id'=>-1, 'code'=>$args[0], 'description'=>$args[1] ?? ''];
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
			return false; //TODO log'simple-plugin '.$name.' code error: '.$t->GetMessage()];
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
			//pass sanitized $params[]
			$keys = array_keys($params);
			$params = [];
			foreach ($keys as $one) {
				$params[$one] = $$one;
			}
			// process file-storage UDTfiles
			return $this->SetFileTag($name, $params);
		} elseif ($id == -1 || $id > 0) {
			//upsert dB
			$db = CmsApp::get_instance()->GetDb();
			$tbl = CMS_DB_PREFIX.'simpleplugins';
			if ($id == -1) {
				$query = "INSERT INTO $tbl (name,code,description,parameters) VALUES (?,?,?,?)";
				$dbr = $db->Execute($query,[$name,$code,$description,$parameters]);
				if ($dbr) {
					$id = (int)$dbr;
					$this->_cache[$name] = [$id, null];
				}
				return (bool)$dbr;
			} else {
				//prevent duplicate names
				$query = <<<EOS
UPDATE $tbl SET name=?,code=?,description=?,parameters=?
WHERE id=?
AND NOT id IN (SELECT id FROM $tbl WHERE name=? AND id!=?)
EOS;
				$dbr = $db->Execute($query,[$name,$code,$description,$parameters,$id,$name,$id]);
				if ($dbr) {
					//update cache if renamed
					if ($oldname && $name != $oldname) {
						unset($this->_cache[$oldname]);
						$this->_cache[$name] = [$id, null];
					}
				}
				return (bool)$dbr;
			}
		}
		return false;
	}

	/**
	 * Delete simple-plugin named $name.
	 *
	 * @param string $name plugin name
	 * @return bool indicating success
	 */
	public function RemoveSimpleTag(string $name) : bool
	{
		if (isset($this->_misses[$name])) return false;
		if (!isset($this->_cache[$name])) {
			$this->SimpleTagExists($name); //populate the relevant cache
		}
		if (isset($this->_cache[$name])) {
			//$this->_cache[$name] => dB|fake id, callable|null
			if ($this->_cache[$name][0] > 0) {
				//process dB-stored plugin
				$db = CmsApp::get_instance()->GetDb();
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
			$db = CmsApp::get_instance()->GetDb();
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
				if (!isset($this->_cache[$name])) {
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
	 * If a dB-stored simple-plugin corresponding to $name exists, run it
	 * This supports explicit tag-running. In most instances, the relevant handler
	 * would instead be called directly by smarty.
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
		$processor = $this->CreateTagFunction($name);
		if ($processor) {
			if (!$smarty_ob) {
				$smarty_ob = Smarty::get_instance(); //default to global smarty
			}
			//TODO sandbox this
			return $processor($params, $smarty_ob);
		}
		if (0) { //TODO debug etc
			throw new RuntimeException('Could not find plugin named '.$name);
		}
		return false;
	}

	/**
	 * If a file-stored simple-plugin corresponding to $name exists, run it
	 *
	 * @param string $name plugin identifier (as used in tags)
	 * @param varargs $args parameters provided by the caller. Must include
	 *  relevant $params and $smarty/$template for use by the plugin.
	 * @return mixed Whatever is returned by the included file, or false
	 * @throws RuntimeException if file is not found (in spite of prior confirmation)
	 */
	protected static function CallFileTag(string $name, ...$args)
	{
		$fp = AppSingle::SimpleTagOperations()->FilePath($name);
		if (!is_file($fp)) {
			if (0) { //TODO debug etc
				throw new RuntimeException('Could not find plugin file named '.$name);
			}
			return false;
		}
		// handle $args[0] if it is the only member, as [$params[], $template]
		if (count($args) == 1) {
			if (is_array($args[0]) && count($args[0]) == 2) {
				if (1) { //TODO not $params[ a=>, b=> ] without related template
					$args = $args[0];
				}
			}
		}

		$processor = function($params = [], $template = null) use($fp)
		{
			if ($params) extract($params); // included code might expect individual variables
			$smarty = $template; // included code might use this variable instead
			// other in-scope variables c.f. module-actions
			$gCms = CmsApp::get_instance();
			$db = $gCms->GetDb();
			$config = $gCms->GetConfig();
			include_once $fp;
		};
		//TODO sandbox this
		return $processor(...$args);
	}

	/**
	 * Return the callable (if any) which smarty can use to process the named plugin.
	 *
	 * @param $name plugin identifier
	 * @return mixed:
	 *   for a dB-stored plugin, the name of a 'created' function, or
	 *   for a file-stored plugin, an array, or
	 *   null upon error
	 */
	public function CreateTagFunction(string $name)
	{
		$name = trim($name);
		if (!isset($this->_cache[$name])) {
			if (isset($this->_misses[$name])) return null;
			try {
				$this->SimpleTagExists($name); //populate relevant cache
			} catch (Throwable $t) {
				return null;
			}
		}
		if (isset($this->_cache[$name])) {
			if (!$this->_cache[$name][1]) {
				if ($this->_cache[$name][0] > 0) {
					$processor = 'cms_simple_tag_'.$name;
					if (!function_exists($processor)) {
						$code = $this->GetSimpleTag($name, 'code');
						try {
							if (!$code) {
								throw new UnexpectedValueException();
							}
							$strfunc = <<<EOS
function $processor(\$params,\$template) {
if(\$params) extract(\$params);
\$smarty=\$template;
\$gCms=CmsApp::get_instance();
\$db=\$gCms->GetDb();
\$config=\$gCms->GetConfig();
$code
}
EOS;
							// no content validation or eval() protection here,
							// we assume no content-change between latest save and now
							// BUT the created function should be run in a sandbox, if possible TODO
							eval($strfunc);
						} catch (Throwable $t) {
							unset ($this->_cache[$name]);
							$this->_misses[$name] = 1;
							return null;
						}
					}
					$this->_cache[$name][1] = $processor;
				} else {
					$this->_cache[$name][1] = [self::class, $name]; //fake callable to trigger __callStatic()
				}
			}
			return $this->_cache[$name][1];
		}
		return null;
	}

	/**
	 * If a simple-plugin corresponding to $name exists, arrange for it to
	 * process an event identified by its originator and name.
	 * Variables $gCms, $db, $config and (global) $smarty are in-scope for the
	 * plugin code.
	 * @since 2.9
	 *
	 * @param string $name plugin identifier (as used in tags)
	 * @param string $originator The name of the event originator, a module-name or 'Core'
	 * @param string $eventname The name of the event
	 * @param array  $params Reference to event parameter(s) provided by the originator.
	 *  They may be altered by the handler.
	 * @return bool
	 */
	public function DoEvent(string $name, string $originator, string $eventname, array &$params)
	{
		if ($originator && $eventname) {
			$fp = $this->FilePath($name);
			if (is_file($fp)) {
				$params['sender'] = $originator;
				$params['event'] = $eventname;

				$processor = function(&$params) use ($fp)
				{
					if ($params) extract($params); // included code might use individual variables, but that defeats feedback via referencing
					$gCms = CmsApp::get_instance();
					$db = $gCms->GetDb();
					$config = $gCms->GetConfig();
					$smarty = $gCms->GetSmarty();
					include_once $fp;
				};
				//TODO run in sandbx
				return $processor($params);
			} else {
				//handle as regular UDT
				return $handler($originator, $eventname, $params); //TODO check
			}
		}
		return false;
	}
} // class

//backward-compatibility shiv
\class_alias(SimpleTagOperations::class, 'UserTagOperations', false);
