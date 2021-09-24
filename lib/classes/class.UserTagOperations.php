<?php
/*
Class to process user-plugins (a.k.a. user-defined-tags)
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Culp and all other contributors from the CMSMS Development Team.

This file is part of CMS Made Simple <http://cmsmadesimple.org>

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

use CMSMS\DeprecationNotice;
use CMSMS\SingleItem;
use Throwable;
use const CMS_DB_PREFIX;
use const CMS_DEPREC;
use const CMS_FILETAGS_PATH;
use const CMSSAN_FILE;
use const CMSSAN_NONPRINT;
use function CMSMS\de_specialize;
use function CMSMS\log_error;
use function CMSMS\log_warning;
use function CMSMS\sanitizeVal;
use function CMSMS\specialize;
use function file_put_contents;

/**
 * User-plugin related functions
 * @since 1.5 (or before)
 *
 * This class supports file-stored as well as database-stored 'user' plugins.
 * Such plugins are intended to be limited to 'safe' functionality, because
 * the content has probably been added by a not-necessarily-trustworthy admin
 * user (or even some malefactor). Though such limit is not enforced here.
 *
 * @package CMS
 * @license GPL
 */
final class UserTagOperations
{
	/**
	 * Filename extension of user-plugin files, something that the web server won't execute
	 */
	const PLUGEXT = '.phphp'; //c.f. vanilla 2.3BETA used .cmsplugin, but those probably have different content

	/* *
	 * @ignore
	 */
//	private static $_instance;

	/**
	 * @var map for keys in $cache, $misses, each member like
	 *  lowercase tagname => actual tagname
	 * @ignore
	 */
	private $map = [];

	/**
	 * @var array Intra-request plugins cache, each member like
	 *  tagname => [ tagid, contentfile, callable|null ]
	 * Populated when, and to the extent, needed.
	 * Null is recorded for the callable until that's initialized.
	 * @ignore
	 */
	private $cache = [];

	/**
	 * @var bool Whether $cache[] has been fully-populated (by ListUserTags())
	 * which is unlikely since checks for individual tags will mostly be
	 * from a frontend request, but sometimes involve a selection from the list.
	 * @ignore
	 */
	private $cache_full = false;

	/**
	 * @var array Intra-request failed-UDT-checks cache, each member like
	 *  tagname => 0
	 * Populated when any check for a possible user-plugin turns out to be invalid.
	 * @ignore
	 */
	private $misses = [];

	/* *
	 * @ignore
	 * Needed when using local $_instance
	 */
//	private function __construct() {} TODO public iff wanted by SingleItem ?

	/**
	 * @ignore
	 */
	private function __clone() {}

	/**
	 * Support for pre-2.99 method-names, and simplified explicit tag-running
	 * @ignore
	 * @param string $name user-plugin name
	 * @param array $args plugin-API variable(s) in some form.
	 *  $args OR $args[0] should include: [0]=$params[], [1]=$template/smarty object
	 */
	public function __call(string $name, array $args)
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
			return $this->CallUserTag($name, ...$args);
		} catch (Throwable $t) {
			return '<span style="font-weight:bold;color:red;">Plugin error!</span>'; //lang() normally N/A for frontend requests
		}
	}

	/**
	 * Process a smarty-call to get the output from a user-plugin named $name
	 * @since 2.99
	 *
	 * @param string $name plugin identifier (as used in tags)
	 * @param array $args plugin-API variable(s)
	 *  [0] if present = array of $params for the plugin
	 *  [1] if present = template object (Smarty_Internal_Template or wrapper)
	 */
	public static function __callStatic(string $name, array $args)
	{
		$handler = SingleItem::UserTagOperations()->GetHandler($name); // what is self:: here
		try {
			return $handler(...$args);
		} catch (Throwable $t) {
			return '<span style="font-weight:bold;color:red;">Plugin error!</span>'; //lang() normally N/A for frontend requests
		}
	}

	/**
	 * Get the singleton instance of this class
	 * @deprecated since 2.99 instead use CMSMS\SingleItem::UserTagOperations()
	 * @return self i.e. UserTagOperations
	 */
	public static function get_instance() : self
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'CMSMS\SingleItem::UserTagOperations()'));
		return SingleItem::UserTagOperations();
	}

	/**
	 * Return filesystem path which would apply to a user-plugin named $name
	 * @since 2.99
	 * @param string $name plugin name or name(s)-pattern, suitable for file-system usage verbatim
	 * @return string absolute path
	 */
	public function FilePath(string $name) : string
	{
		return CMS_FILETAGS_PATH.DIRECTORY_SEPARATOR.$name.self::PLUGEXT;
	}

	/**
	 * Determine whether $name is acceptable for a user-plugin.
	 * Specifically whether it's capable of being the main part of a
	 * valid filename, in case the plugin is or will become file-stored.
	 * And its length is between 8 and 48 bytes inclusive.
	 * And it's not a duplicate of some other tag's name.
	 * @since 2.99
	 * @internal
	 *
	 * @param string $name plugin identifier (as used in tags). A reference, so it can be trim()'d
	 * @return bool indicating success
	 */
	private function IsValidName(string &$name) : bool
	{
		$name = trim($name);
		if ($name) {
			if (sanitizeVal($name, CMSSAN_FILE) !== $name) {
				return false;
			}
/*			//see https://www.php.net/manual/en/functions.user-defined.php
			//starts with a letter (ASCII|UTF8) or _, followed by any number of such letters or digits or _'s.
			if (!preg_match('~^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$~', $name)) {
				return false;
			}
*/
			$l = strlen($name);
			if ($l < 8 || $l > 48) { // max == table-column-width 2.99 breaker
				return false;
			}
			if (0) { // TODO $name is not unique and this is a rename or addition
				//CMS_DB_PREFIX.'userplugins' (case-sensitive?) ::name is not used for another recorded id
				return false;
			}
			return true;
		}
		return false;
	}

	/**
	 * Report whether (any-case) $key is a key in $haystack
	 * @internal
	 * @ignore
	 *
	 * @param string $key
	 * @param array $haystack
	 * @return bool indicating presence
	 */
	private function ArrayHas(string $key, array $haystack) : bool
	{
		$lkey = strtolower($key); // TODO support non-ASCII
		return isset($this->map[$lkey]) && isset($haystack[$this->map[$lkey]]);
	}

	/**
	 * Get the member of $haystack (if any), whose key matches (any-case) $key
	 * @internal
	 * @ignore
	 *
	 * @param string $key
	 * @param array $haystack
	 * @return mixed recorded value | null
	 */
	private function ArrayGet(string $key, array $haystack)
	{
		$lkey = strtolower($key); // TODO support non-ASCII
		$akey = $this->map[$lkey] ?? '';
		return ($akey) ? $haystack[$akey] : null;
	}

	/**
	 * @internal
	 * @ignore
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param array $haystack
	 */
	private function ArraySet(string $key, $value, array &$haystack)
	{
		$lkey = strtolower($key); // TODO support non-ASCII
		$this->map[$lkey] = $key;
		$haystack[$key] = $value;
	}

	/**
	 * Establish local data cache for all user-plugins
	 * @deprecated since 2.99 does nothing. There are no LoadedData
	 * for user-plugins
	 * @internal
	 */
	public static function load_setup() {}

	/**
	 * Cache all information about all user-plugins (onetime only)
	 * @deprecated since 2.99 does nothing. Local cache is populated
	 * on demand, and to the extent needed, by class methods
	 * e.g. ListUserTags()
	 * @internal
	 */
	public function LoadUserTags() {}

	/* *
	 * Migrate plugin from database-storage to file-storage.
	 * Note: there is no operational advantage from such change.
	 * @since 2.99
	 *
	 * @param string $name Plugin name
	 * @return bool indicating success
	 */
//	public function ExportFile(string $name) : bool {}
// see e.g. Template 'contentfile' property change

	/* *
	 * Migrate plugin from file-storage to dB-storage
	 * Note: there is no operational advantage from such change.
	 * @since 2.99
	 *
	 * @param string $name Plugin name
	 * @return bool indicating success
	 */
//	public function ImportFile(string $name) : bool {}
// see e.g. Template 'contentfile' property change

	/**
	 * Render tag code at least nominally suitable for use (whether as a
	 * content generator or event-notice handler).
	 * This is run each time a tag is to be used i.e. don't rely on any
	 * previous pre-save cleanup, in case the tag has been modified
	 * independently since last saved (especially possible if file-stored).
	 * @since 2.99
	 * @internal
	 *
	 * @param string $code the tag PHP code
	 * @param bool $fromfile optional flag whether the tag is file-stored. Default false.
	 * @return string
	 */
	private function FilterforUse(string $code) : string
	{
		// remove inappropriate php tags, if any
//		$val =
		return preg_replace(
			['/^[\s\r\n]*<\?(php|=)[\s\r\n]*/i', '/[\s\r\n]*\?>[\s\r\n]*$/'],
			['', ''],
			$code);
//		return $val;
	}

	/**
	 * Retrieve property|ies of the file-stored plugin named $name
	 *
	 * @param string $name Plugin name
	 * @param mixed $props string | strings[] Optional property name(s)
	 * (comma-separated ok), or '*', or falsy (hence an existence-flag).
	 * '*' gets all available of 'description','parameters','license','code'.
	 * Default 'code'
	 * @return mixed assoc array of requested properties | single-prop value | true (exists) | null if N/A
	 */
	private function GetFileTag(string $name, $props = 'code') //UDTfiles
	{
		$fp = $this->FilePath($name);
		if (is_readable($fp)) {
			$cont = file_get_contents($fp);
			if (!$cont) {
				return null;
			}
			if (!$props) {
				return true; //it exists
			}
			if (!is_array($props)) {
				if ($props == '*') {
					$props = ['id', 'description', 'parameters', 'license', 'code'];
				} else {
					$props = explode(',', $props);
				}
			}
			$res = array_combine($props, array_fill(0, count($props), ''));
			if (isset($res['id'])) {
				if (!$this->ArrayHas($name, $this->cache)) {
					$db = SingleItem::Db();
					//TODO case-insensitive name-match if table|field definition is not *_ci
					$query = 'SELECT id,contentfile FROM '.CMS_DB_PREFIX.'userplugins WHERE name=?';
					$dbr = $db->getRow($query, [$name]);
					if (!$dbr) {
						log_error("Userplugin doesn't exist", $name);
						// TODO warn user
						return null;
					}
					$this->ArraySet($name,
						[(int)$dbr['id'], (bool)$dbr['contentfile'], null],
						$this->cache);
				}
				$res['id'] = $this->ArrayGet($name, $this->cache)[0];
			}
			$ps = strpos($cont, '<metadata>');
			$pe = strpos($cont, '</metadata>', $ps);
			if ($ps !== false && $pe !== false) {
				$xmlstr = substr($cont, $ps, $pe - $ps + 11);
				$xml = simplexml_load_string($xmlstr);
				if ($xml !== false) {
					// the file might have been edited, perhaps maliciously!
					// so apply CMSMS\sanitizeVal(, CMSSAN_ VARIOUS)
					// AND for some nl2br() ? striptags() ?
					if (in_array('description', $props)) {
						$val = (string)$xml->description;
						$res['description'] = ($val) ? de_specialize($val, ENT_XML1 | ENT_NOQUOTES) : '';
					}
					if (in_array('parameters', $props)) {
						$val = (string)$xml->parameters;
						$res['parameters'] = ($val) ? de_specialize($val, ENT_XML1 | ENT_NOQUOTES) : '';
					}
					if (in_array('license', $props)) {
						$val = (string)$xml->license;
						$res['license'] = ($val) ? de_specialize($val, ENT_XML1 | ENT_NOQUOTES) : '';
					}
				}
				if (in_array('code', $props)) {
					$ps = strpos($cont, '*/', $pe);
					$res['code'] = ($ps !== false) ? trim(substr($cont, $ps + 2), " \t\n\r") : '';
					// TODO $this->FilterforUse() if relevant
				}
			} else {
				// malformed tag file !
				if (in_array('code', $props)) {
					// skip any introductory comment(s)
					$skips = '~^\s*(<\?php|#|//)~'; //ignore lines starting like this TODO short php tag, any case
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
					// TODO $this->FilterforUse() if relevant
				}
			}
			return (count($res) > 1) ? $res : reset($res);
		}
		$this->ArraySet($name, 0, $this->misses);
		return null;
	}

	/**
	 * Retrieve property|ies of the named user-plugin.
	 *
	 * @param string $name Plugin name
	 * @param mixed $props @since 2.99 string|strings[] Optional database
	 *  userplugins-table field name(s) (comma-separated ok), or '*',
	 *  or falsy for an existence-check. Default 'code'.
	 *
	 * @return mixed array | field-value | true (exists) | null upon error
	 */
	public function GetUserTag(string $name, $props = 'code')
	{
		if (!$this->ArrayHas($name, $this->cache)) {
			$db = SingleItem::Db();
			//TODO case-sensitive name-match if table|field definition is *_ci ?
			$query = 'SELECT id,contentfile FROM '.CMS_DB_PREFIX.'userplugins WHERE name=?';
			$dbr = $db->getRow($query, [$name]);
			if (!$dbr) {
				log_error("Userplugin doesn't exist", $name);
				// TODO warn user
				return null;
			}
			$this->ArraySet($name,
				[(int)$dbr['id'], (bool)$dbr['contentfile'], null],
				$this->cache);
		}
		if ($this->ArrayGet($name, $this->cache)[1]) {
			// a file-stored tag
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
				// TODO ditto for 'contentfile'
				$fields = implode(',', $props);
			} elseif ($props) {
				$multi = ($props == '*' || strpos(',', $props) !== false);
				//always get id, contentfile
				if ($props != '*' && strpos($props, 'id') === false) {
					$fields = 'id,'.$props;
					$scrub = true;
				} else {
					$fields = $props;
				}
				// TODO ditto for 'contentfile'
			} else {
				$multi = false;
				$fields = 'id';
			}
			// TODO FilterforUse if relevant
			$db = SingleItem::Db();
			$query = 'SELECT '.$fields.' FROM '.CMS_DB_PREFIX.'userplugins WHERE name=?';
			//TODO case-sensitive name-match if table|field definition is *_ci ?
			$dbr = $db->getRow($query, [$name]);
			if ($dbr) {
				if ($filetag === null) {
					$this->ArraySet($name,
						[(int)$dbr['id'], (bool)$dbr['contentfile'], null],
						$this->cache);
				}
				if ($scrub) {
					unset($dbr['id']);
				}
				return ($props) ? (($multi) ? $dbr : reset($dbr)) : true;
			}
			// maybe it's file-stored but unregistered
			$res = $this->GetFileTag($name, $props); //UDTfiles
			if ($res) {
				log_warning("Un-registered userplugin used", $name);
				//remember it TODO dB-data update, apply real id
				$this->ArraySet($name, [0, true, null], $this->cache);
				return $res;
			}
			$this->ArraySet($name, 0, $this->misses);
			return null;
		}
	}

	/**
	 * Check whether $name is acceptable for a user-plugin, and if so,
	 * whether the corresponding dB-stored or file-stored plugin exists.
	 * @since 1.10
	 *
	 * @param $name plugin identifier
	 * @return bool since 2.99, formerly $name|false
	*/
	public function UserTagExists(string $name) : bool
	{
		if (!$this->IsValidName($name)) {
			$this->ArraySet($name, 0, $this->misses);
			return false;
		}
		if ($this->ArrayHas($name, $this->cache)) { return true; }
		if ($this->ArrayHas($name, $this->misses)) { return false; }
		return ($this->GetUserTag($name, '') != false);
	}

	/**
	 * Test whether a user-plugin with the specified name exists, after (if
	 * check_functions is true) testing whether ANY plugin with that name has been registered.
	 * @since 2.99 this does not also check for a matching system-plugin - all
	 *  system-plugins are automatically handled by smarty
	 *
	 * @param string $name	The name to test
	 * @param bool   $check_functions Optional flag. Default true. First, test if a plugin with such name is
	 *  already registered with smarty
	 */
	public function SmartyTagExists(string $name, bool $check_functions = true) : bool
	{
		if ($check_functions) {
			// might be registered by something else... a module perhaps
			$smarty = SingleItem::Smarty();
			if ($smarty->is_registered($name)) {
				return true;
			}
		}
		return $this->UserTagExists($name);
	}

	/**
	 * Save file for user-plugin named $name. The file will be created or
	 * over-written as appropriate, except that renaming a file-stored
	 * plugin fails if the new name already exists.
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
	private function SetFileTag(string $name, array $params)
	{
		$bare = empty($params['detail']);
		if (!$this->IsValidName($name)) {
			$this->ArraySet($name, 0, $this->misses);
			return ($bare) ? false : [false, 'error_usrplg_name'];
		}

		$code = trim($params['code'], " \t\n\r"); // cleanup and other checks done upstream
		if (!$code) {
			return ($bare) ? false : [false, 'error_usrplg_nocode'];
		}

		if (!empty($params['license'])) {
			// TODO attend to stored newlines? e.g. strip <br/>
			$text = specialize(trim($params['license']), ENT_XML1 | ENT_NOQUOTES);
			$l = '<license>'."\n".$text."\n".'</license>';
		} else {
			$l = '<license></license>';
		}
		if (!empty($params['description'])) {
			// TODO attend to stored newlines? e.g. strip <br/>
			$text = specialize(trim($params['description']), ENT_XML1 | ENT_NOQUOTES);
			$d = '<description>'."\n".$text."\n".'</description>';
		} else {
			$d = '<description></description>';
		}
		if (!empty($params['parameters'])) {
			// TODO attend to stored newlines? e.g. strip <br/>
			$text = specialize(trim($params['parameters']), ENT_XML1 | ENT_NOQUOTES);
			$p = '<parameters>'."\n".$text."\n".'</parameters>';
		} else {
			$p = '<parameters></parameters>';
		}
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
			$row = $this->ArrayGet($oldname, $this->cache);
			unset($this->cache[$this->map[$oldname]]);
// no longer needed? if () { unset($this->map[$oldname]); }
			$this->ArraySet($name, $row, $this->cache);
			//remove old tagfile
			$fp = $this->FilePath($oldname);
			@unlink($fp);
		}
		$fp = $this->FilePath($name);
		$res = @file_put_contents($fp, $out, LOCK_EX);
		return ($bare) ? $res : [$res, (($res) ? '' : 'error_internal')];
	}

	/**
	 * Insert/store or update a user-plugin in the database or in file.
	 *
	 * @param string $name   plugin name now, perhaps different from $params[oldname]
	 * @param varargs $args  since 2.99 normally just a single assoc array of
	 *  additional properties, some/all of
	 *  'id' 0 for new plugin, > 0 for an existing plugin
	 *  'oldname' string user-plugin recorded name, or '' for new plugin
	 *  'description'
	 *  'parameters'
	 *  'contentfile'
	 *  'code'
	 *  'license' ignored for a dB-stored plugin
	 *  'detail' bool indicating caller wants a status message
	 * @return mixed bool indicating success | 2-member array,
	 *  [0] = bool indicating success
	 *  [1] = string lang-key (no spaces) or actual message, normally '' on success
	 */
	public function SetUserTag(string $name, ...$args)
	{
		if (count($args) == 1 && is_array($args[0])) {
			$params = $args[0];
		} else { // pre-2.99 API
			$params = ['id'=>0, 'description'=>$args[1] ?? '', 'code'=>$args[0], 'contentfile'=>false];
		}
		$bare = empty($params['detail']);

		if (!$this->IsValidName($name)) {
			$this->ArraySet($name, 0, $this->misses);
			return ($bare) ? false : [false, 'error_usrplg_name'];
		}

		$code = $params['code'] ?? '';
		if ($code) {
			$code = $this->FilterforUse($code);
			$code = str_replace('    ', "\t", $code);
			// More-complex code-validation runs afoul of inherent $params[] usage,
			// namespaces etc etc, so cannot reasonably be performed out-of-context.
			// We'll have to trust it! or better, always run in a sandbox.
			try {
				eval('if(0){ '.$code.' }'); // no code execution
			} catch (Throwable $t) {
				return ($bare) ? false : [false, 'Plugin '.$name.' code error: '.$t->GetMessage()];
			}
		}

		$val = $params['oldname'] ?? '';
		if ($val && !is_numeric($val)) {
			$oldname = sanitizeVal($val, CMSSAN_FILE);
		} else {
			$oldname = $val;
		}

		$val = $params['description'] ?? '';
		if ($val && !is_numeric($val)) {
			$description = sanitizeVal($val, CMSSAN_NONPRINT);
			// TODO attend to stored newlines? e.g. strip <br/>
			// OR nl2br( ,true) for newlines without a preceeding br '~(?<!(<br(\s*)?/?  >))[\n\r]{1,2}~i'
		} else {
			$description = null;
		}
		$val = $params['parameters'] ?? '';
		if ($val && !is_numeric($val)) {
			$parameters = sanitizeVal($val, CMSSAN_NONPRINT);
			// TODO attend to stored newlines? e.g. strip <br/>
			// OR nl2br( ,true) for newlines without a preceeding br '~(?<!(<br(\s*)?/?  >))[\n\r]{1,2}~i'
		} else {
			$parameters = null;
		}

		$id = (int)$params['id'];
		if (!empty($params['contentfile'])) {
			$val = $params['license'] ?? '';
			if ($val && !is_numeric($val)) {
				// TODO attend to stored newlines? e.g. strip <br/>
//				$license = sanitizeVal($val, CMSSAN_NONPRINT); // OR kill non- cr lf tab
				$license = preg_replace(
				['~/\*~', '~\*/~', '~[\r\n]+\s*#~', '~[\r\n]+\s*//~', '~<br(\s*)?/?>~i'],
				['', '', '', '', "\n"],
				$license);
				$license = trim(strip_tags($license, '<a>'));
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
		} else {
			//upsert dB
			$db = SingleItem::Db();
			$tbl = CMS_DB_PREFIX.'userplugins';
			if ($id === 0) {
				$query = "INSERT INTO $tbl (name,description,parameters,code,contentfile) VALUES (?,?,?,?,0)";
				$dbr = $db->execute($query, [$name, $description, $parameters, $code]);
				if ($dbr) {
					$id = (int)$dbr; // CHECKME last-insert works now?
					$this->ArraySet($name, [$id, false, null], $this->cache);
				}
				$res = (bool)$dbr;
				return ($bare) ? $res : [$res, (($res) ? '' : 'error_internal')];
			} else {
				//prevent duplicate names
				$query = <<<EOS
UPDATE $tbl SET name=?,description=?,parameters=?,contentfile=0,code=?,
WHERE id=?
AND NOT id IN (SELECT id FROM $tbl WHERE name=? AND id!=?)
EOS;
				$dbr = $db->execute($query, [$name, $description, $parameters, $code, $id, $name, $id]);
				$res = (bool)$dbr;
				if ($res) {
					//update cache if renamed
					if ($oldname && $name != $oldname) {
						unset($this->cache[$this->map[$oldname]]);
// no longer needed? if () { unset($this->map[$oldname]); }
						$this->ArraySet($name, [$id, false, null], $this->cache);
					}
				}
				return ($bare) ? $res : [$res, (($res) ? '' : 'error_internal')];
			}
		}
		return ($bare) ? false : [false, 'missingparams'];
	}

	/**
	 * Delete user-plugin named $name.
	 *
	 * @param string $name plugin name
	 * @return bool indicating success
	 */
	public function RemoveUserTag(string $name) : bool
	{
		if ($this->ArrayHas($name, $this->misses)) return false;
		if (!$this->ArrayHas($name, $this->cache)) {
			$this->UserTagExists($name); //populate the relevant cache
		}
		if ($this->ArrayHas($name, $this->cache)) {
			// TODO if case-sensitive name in _ci field
			$db = SingleItem::Db();
			$query = 'DELETE FROM '.CMS_DB_PREFIX.'userplugins WHERE name=?';
			$dbr = $db->execute($query, [$name]);
			$res = ($dbr != false);
			if ($res) {
				//$this->cache[$name] => int recorded id, bool is-file-stored, callable|null
				if ($this->ArrayGet($name, $this->cache)[1]) {
					$fp = $this->FilePath($name);
					if (is_file($fp)) {
						@unlink($fp);
					}
				}
				unset($this->cache[$this->map[$name]]);
				if (1) { unset($this->map[$name]); } // TODO if not otherwise needed
			}
			return $res;
		}
		return false;
	}

	/**
	 * List all user-plugins
	 *
	 * @return array each member like id => tagname
	 */
	public function ListUserTags() : array
	{
		if (!$this->cache_full) {
			$db = SingleItem::Db();
			$query = 'SELECT id,name,contentfile FROM '.CMS_DB_PREFIX.'userplugins ORDER BY name';
			$dbr = $db->getAssoc($query);
			foreach ($dbr as $id=>$row) {
				$name = $row['name'];
				if ($row['contentfile']) {
					$fp = $this->FilePath($name);
					if (!is_readable($fp)) {
						log_error("File-stored userplugin doesn't exist", $name);
						// TODO remove from dB and/or warn user
						continue;
					}
				}
				if (!$this->ArrayHas($name, $this->cache)) {
					// no callable yet
					$this->ArraySet($name, [$id, (bool)$row['contentfile'], null], $this->cache);
				}
			}
			$this->cache_full = true;
		}

		$out = [];
		foreach ($this->cache as $name => $row) {
			$out[$row[0]] = $name;
		}
		return $out;
	}

	/**
	 * @internal
	 * @param string $name   The name of the user defined tag
	 * @return anonymous function
	 */
	private function GetHandler(string $name)
	{
		// TODO if case-sensitive $name in dB *_ci field and strtolower()'d local cache
		if ($this->CreateTagFunction($name)) {
			$row = $this->ArrayGet($name, $this->cache);
			if ($row[2]) {
				return $row[2]; // handler already populated
			}
			if ($row[1]) {
				// $name represents a file-stored tag
				$strfunc = $this->GetFileTag($name);
			} else {
				$strfunc = $this->GetUserTag($name);
			}
			// subject to TODO above, we (naively?) assume no code-change between latest save and now
			$handler = function (&$params = [], $template = null) use ($strfunc)
			{
				// API doc says plugin code can also expect individual variables
				if ($params) {
					extract($params);
				}
				$gCms = SingleItem::App();
				$config = SingleItem::Config();
				$db = SingleItem::Db(); // TODO enforce read-only db here
				$smarty = SingleItem::Smarty(); // TODO restrict methods : assign[byref]* or define { } replacements
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
			$handler = function() {
				return '<span style="font-weight:bold;color:red;">Missing plugin: '.$name.'</span>';
			};
		}
		$this->cache[$name][2] = $handler;
		return $handler;
	}

	/**
	 * If a dB-stored user-plugin corresponding to $name exists, run it
	 * This supports explicit tag-running. In most instances, the relevant
	 * callable would instead be called directly by smarty.
	 *
	 * @param string $name   The name of the user defined tag
	 * @param array  $params Optional tag parameters. Default []
	 * @param mixed object|null $smarty_ob Optional Smarty_Internal_Template
	 *  (or descendant) for the caller's context
	 * Compiled smarty provides a Smarty_Internal_Template-object as the
	 * 2nd argument to tags. CMSMS\internal\Smarty is not a sub-class of that.
	 *
	 * @return mixed value returned by tag|false
	 */
	public function CallUserTag(string $name, array &$params = [], $smarty_ob = null)
	{
		$row = $this->ArrayGet($name, $this->cache);
		if ($row && $row[2]) {
			$fname = $row[2];
		} else {
			$fname = $this->GetHandler($name); // downstream updates stuff for next time
		}
		if ($fname) {
			return $fname($params, $smarty_ob);
		}
		return false;
	}

	/**
	 * If a user-plugin corresponding to $name exists, arrange for it
	 * to process an event identified by its originator and name.
	 * @since 2.99
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
			$row = $this->ArrayGet($name, $this->cache);
			if (!$row || !$row[2]) {
				$handler = $this->CreateTagFunction($name);
				if (!$handler) {
					return false;
				}
				// TODO $this->ArraySet($name, [,,], $this->cache);
			}
			$params['sender'] = $originator;
			$params['event'] = $eventname;
			$res = (bool)$this->CallUserTag($name, $params);
			unset($params['sender'], $params['event']);
			return $res;
		}
		return false;
	}

	/**
	 * Return the callable (if any) which smarty can use to process the
	 * named plugin.
	 *
	 * @param $name plugin identifier (any case)
	 * @return mixed callable | null
	 */
	public function CreateTagFunction(string $name)
	{
		$name = trim($name);
		if (!$this->ArrayHas($name, $this->cache)) {
			if ($this->ArrayHas($name, $this->misses)) {
				return null;
			}
			try {
				$this->UserTagExists($name); //populate relevant cache
			} catch (Throwable $t) {
				return null;
			}
		}
		return ($this->ArrayHas($name, $this->cache)) ?
			__CLASS__.'::'.$name : //fake callable triggers self::__callStatic()
			null;
	}
} // class
