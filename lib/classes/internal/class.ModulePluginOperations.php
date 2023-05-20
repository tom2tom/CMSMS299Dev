<?php
/*
Singleton class of functions to manage modules' smarty plugins
Copyright (C) 2010-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify it
under the terms of the GNU General Public License as published by the
Free Software Foundation; either version 3 of that license, or (at your option)
any later version.

CMS Made Simple is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS\internal;

use CMSModule;
use CMSMS\AppParams;
use CMSMS\AppState;
use CMSMS\CapabilityType;
use CMSMS\Crypto;
use CMSMS\DeprecationNotice;
use CMSMS\LoadedDataType;
use CMSMS\Lone;
use CMSMS\RequestParameters;
use Throwable;
use const CLEAN_STRING;
use const CMS_DB_PREFIX;
use const CMS_DEPREC;
use function cmsms;
use function CMSMS\log_error;
use function CMSMS\log_notice;
use function startswith;
//use function CMSMS\sanitizeVal;

/**
 * A singleton class to manage smarty plugins registered by modules.
 * NOTE the method-names' '_' prefix.
 * Methods may be called statically as ModulePluginOperations::function()
 * or (since 3.0) as non-static (new ModulePluginOperations())->_function()
 *
 * @package CMS
 * @license GPL
 * @internal
 * @final
 * @since  1.11
 */
final class ModulePluginOperations
{
	/**
	 * A flag indicating that the plugin can be used in frontend pages/templates
	 */
	const AVAIL_FRONTEND = 1;

	/**
	 * A flag indicating that the plugin can be used in admin templates
	 */
	const AVAIL_ADMIN = 2;

	/**
	 * Union of all valid flags
	 */
	const AVAIL_ALL = 3;

	/**
	 * Singleton instance of this class.
	 * No class properties to 'protect', not worth caching as
	 *  Lone::get('ModulePluginOperations')
	 */
	private static $_instance = null;

//	private function __construct() {} TODO public iff wanted by Lone ?
	#[\ReturnTypeWillChange]
	private function __clone() {}// : void {}

	/**
	 * Call a class-method from a static context
	 * @param string $name Method name | static proxy | module name
	 * @param array $args Method argument(s)
	 * @return mixed
	 */
    #[\ReturnTypeWillChange]
	public static function __callStatic(string $name, array $args)
	{
		$obj = self::get_instance();
		if( $name == 'addStatic' ) {
			assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'ModulePluginOperations::add()'));
			return $obj->_add(...$args);
		}
		$pname = '_'.$name;
		if( method_exists($obj, $pname) ) {
			return $obj->$pname(...$args);
		}
		if( count($args) == 2 && is_array($args[0]) ) {
			$args[0]['module'] = $name;
			return $obj->_call_plugin_module(...$args);
		}
        return null;
	}

	/**
	 * Get an instance of this class, for use by class static-methods
	 * @return self
	 */
	public static function get_instance() : self
	{
		if( !self::$_instance ) { self::$_instance = new self(); }
		return self::$_instance;
	}

	/**
	 * Initialize 'module_plugins' loaded-data cache
	 * @since 3.0
	 */
	public static function load_setup()
	{
		$obj = new LoadedDataType('module_plugins', function(bool $force) {
			static $sema4 = 0; // recursion blocker
			if( ++$sema4 !== 1 ) {
				--$sema4;
				return;
			}
			$data = [];
			$tmp = Lone::get('LoadedMetadata')->get('capable_modules', $force, CapabilityType::PLUGIN_MODULE); //TODO might need forced if this loader is forced
			$tmp2 = Lone::get('LoadedMetadata')->get('methodic_modules', $force, 'IsPluginModule'); //deprecated since 3.0
			if( $tmp || $tmp2 ) {
				$val = AppParams::get('smarty_cachemodules', 0);
				if( $val ) {
					$val = (int)AppParams::get('smarty_cachelife', -1);
					//$val -1 for CACHING_LIFETIME_CURRENT (3600 secs)
					//$val 0 for CACHING_OFF
					//$val >0 for explicit cache ttl (secs)
				}

				foreach( array_unique( array_merge($tmp, $tmp2)) as $module_name ) {
//					$callable = $module.'::function_plugin';
//					$sig = Crypto::hash_string($module.$module.$callable);
					$callable = __CLASS__.'::'.$module_name; // hence handle via our __callStatic()
					$sig = Crypto::hash_string($module_name.$module_name.$callable);
					$data[$sig] = [
						'name'=>$module_name,
						'module'=>$module_name,
						'type'=>'function',
						'callable'=>$callable,
						'cachable'=>($val != 0), //maybe changed later
						'available'=>self::AVAIL_ALL, //ditto
					];
				}
			}
			// add, or replace by, module-plugins recorded in the database
			$db = Lone::get('Db');
			$query = 'SELECT DISTINCT * FROM '.CMS_DB_PREFIX.'module_smarty_plugins';
			$list = $db->getArray($query);
			foreach ($list as &$row) {
				$sig = Crypto::hash_string($row['name'].$row['module'].$row['callable']);
				$data[$sig] = [
					'name'=>$row['name'],
					'module'=>$row['module'],
					'type'=>$row['type'],
					'callable'=>$row['callable'],
					'cachable'=>(bool)$row['cachable'],
					'available'=>(int)$row['available'],
				];
			}
			--$sema4; // back to 0 for next time
			return $data;
		});
		Lone::get('LoadedData')->add_type($obj);
	}

	/**
	 * Process a module-tag
	 * This method (or its static equivalent) is used by the {cms_module}
     * plugin and to process {ModuleName} tags
	 * @since 3.0 this does the work for global method cms_module_plugin()
	 *
	 * @param array $params A hash of action-parameters
	 * @param object $template A Smarty_Internal_Template object
	 * @return string The module output string or an error message string or ''
	 */
	public function _call_plugin_module($params, $template) : string
	{
		if( !empty($params['module']) ) {
			$modname = $params['module'];
		}
		else {
			return '<!-- ERROR: module name not specified -->';
		}

		if( !($mod = $this->_get_plugin_module($modname)) ) {
			return "<!-- ERROR: module '$modname' is not available, in this context at least -->";
		}

		unset($params['module']);
		if( !empty($params['action']) ) {
			// action was set in the module tag
			$action = $params['action'];
//			unset($params['action']);  unfortunate 3.0 deprecation
		}
		else {
			$params['action'] = $action = 'default'; //3.0 deprecation
		}

		if( !empty($params['idprefix']) ) {
			// idprefix was set in the module tag
			$id = $params['idprefix'];
			$setid = TRUE;
		}
		else {
			// multiple modules might be used in a page|template
			// just in case they get confused ...
			static $modnum = 1;
			++$modnum;
			$id = "m{$modnum}_";
			$setid = FALSE;
		}

		$rparams = RequestParameters::get_action_params();
		if( $rparams ) {
			$mactmodulename = $rparams['module'] ?? '';
			if( strcasecmp($mactmodulename, $modname) == 0 ) {
				$checkid = $rparams['id'] ?? '';
				$inline = !empty($rparams['inline']);
				if( $inline && $checkid == $id ) {
					$action = $rparams['action'] ?? 'default';
					$params['action'] = $action; // deprecated since 3.0
					unset($rparams['module'], $rparams['id'], $rparams['action'], $rparams['inline']);
					$params = array_merge($params, $rparams, RequestParameters::get_identified_params($id));
				}
			}
		}
/*		if( isset($_REQUEST['mact']) ) {
			// We're handling an action.  Check if it is for this call.
			// We may be calling module plugins multiple times in the template,
			// but a POST or GET mact can only be for one of them.
			$mact = sanitizeVal($mact, CMSSAN_PHPSTRING);
			$ary = explode(', ', $mact, 4);
			$mactmodulename = $ary[0] ?? '';
			if( strcasecmp($mactmodulename, $modname) == 0 ) {
				$checkid = $ary[1] ?? '';
				$inline = isset($ary[3]) && $ary[3] === 1;
				if( $inline && $checkid == $id ) { // presumbly $setid true i.e. not a random id
					// the action is for this instance of the module and we're inline
					// i.e. the results are supposed to replace the tag, not {content}
					$action = $ary[2] ?? 'default';
					$params['action'] = $action; // deprecated since 3.0
					$params = array_merge($params, RequestParameters::get_identified_params($id));
				}
			}
		}
*/
		$params['id'] = $id; // deprecated since 3.0
		if( $setid ) {
			$params['idprefix'] = $id; // might be needed per se, probably not
			$mod->SetParameterType('idprefix', CLEAN_STRING); // in case it's a frontend request
		}
		$returnid = cmsms()->get_content_id();
		$params['returnid'] = $returnid;

		$out = $mod->DoActionBase($action, $id, $params, $returnid, $template);

		if( !empty($params['assign']) ) {
			$template->assign(trim($params['assign']), $out);
			return '';
		}
		return $out;
	}

	/**
	 * Return the module (if any) to use for processing the specified tag.
	 * Any dependent module(s) will also be loaded.
	 * @since 3.0
	 * @param string $name Name of the tag whose processor-module is wanted
	 * @param string $type Optional tag type (commonly Smarty::PLUGIN_FUNCTION, maybe Smarty::PLUGIN_BLOCK,
	 *  Smarty::PLUGIN_COMPILER, Smarty::PLUGIN_MODIFIER, Smarty::PLUGIN_MODIFIERCOMPILER)
	 *  Default 'function'
	 * @return mixed CMSModule | null
	 */
	public function _get_plugin_module(string $name, string $type = 'function')
	{
		$row = $this->_find($name, $type);
		if( $row ) {
			if( $row['available'] != self::AVAIL_ALL ) {
				$states = AppState::get();
				if( in_array(AppState::FRONT_PAGE, $states) ) {
					if( !($row['available'] & self::AVAIL_FRONTEND) ) return;
				}
				elseif( in_array(AppState::ADMIN_PAGE, $states) ) {
					if( !($row['available'] & self::AVAIL_ADMIN) ) return;
				}
				else {
					return;
				}
			}
			return Lone::get('ModuleOperations')->get_module_instance($row['module']);
		}
	}

	/**
	 * Return parameters for a named module-plugin, if the supplied name is recognized.
	 * This might be called by Smarty when looking for something to process a plugin.
	 * The module is loaded, if found.
	 *
	 * @param string $name Name of the tag whose processor-module is wanted
	 * @param string $type Optional tag type (commonly Smarty::PLUGIN_FUNCTION, maybe Smarty::PLUGIN_BLOCK,
	 *  Smarty::PLUGIN_COMPILER, Smarty::PLUGIN_MODIFIER, Smarty::PLUGIN_MODIFIERCOMPILER)
	 *  Default 'function'
	 * @return array 2 members: 'callable'=>string, 'cachable'=>bool | empty
	 */
	public function _load_plugin(string $name, string $type = 'function')
	{
		$mod = $this->_get_plugin_module($name, $type);
		if( $mod ) {
			$row = $this->_find($name, $type);
			$callable = $row['callable'];
			if( !$callable || $callable == $row['module'].'::function_plugin' ) {
				$callable = __CLASS__.'::'.$row['module']; //substitute our handler
			}
			elseif( strncmp($callable, 's:', 2) == 0 || strncmp($callable , 'a:2:{', 5) == 0) {
				try {
					$callable = @unserialize($row['callable']);
				}
				catch (Throwable $t) {
					$callable = FALSE;
				}
			}
			if( is_string($callable) && strpos($callable, '::') === FALSE ) {
				$callable = $row['module'].'::'.$callable;
			}

			return [
				'callable' => $callable,
				'cachable' => (bool)$row['cachable']
			];
		}
		return [];
	}

	/**
	 * Try to find a match for a named & typed module-plugin
	 * Since 3.0, the name-comparison is case-insensitive
	 *
	 * @param string $name tag identifier, usually a module-name (any case)
	 * @param string $type tag-type 'function' etc
	 * @return array with members
	 *  'name','module','type','callable','cachable','available'. Or empty
	 */
	public function _find(string $name, string $type)
	{
		$data = Lone::get('LoadedData')->get('module_plugins');
		if( $data ) {
			foreach( $data as $row ) {
				if( $row['type'] == $type && strcasecmp($row['name'], $name) == 0 ) {
					return $row;
				}
			}
		}
		return [];
	}

	/**
	 * @since 3.0
	 * @ignore
	 * @param string $module_name The module name
	 * @param mixed $callable string|array
	 * @return string|null
	 */
	private function validate_callable(string $module_name, $callable)
	{
		if( !$callable || $callable == $module_name.'::function_plugin' ) {
			$callable = __CLASS__.'::'.$module_name;
		}
		elseif( is_array($callable) ) {
			if( count($callable) == 2 ) {
				// ensure first member refers to a module (not necessarily the one doing the registration)
				if( $callable[0] instanceof CMSModule ) {
					$callable[0] = $callable[0]->GetName();
				}
				elseif( !is_string($callable[0]) || strtolower($callable[0]) == 'this') {
					$callable[0] = $module_name;
				}
				$callable = $callable[0].'::'.$callable[1];
			}
			else {
				// an array with only one member !?
				log_error(__CLASS__, 'Cannot register plugin for module '.$module_name.' - invalid callable');
				return;
			}
		}
		elseif( ($p = strpos($callable, '::')) !== FALSE ) {
			if( $p === 0 ) {
				// '::method' syntax (implies module name)
				$callable = $module_name.$callable;
			}
		}
		else {
			// assume it's just a method name
			$callable = $module_name.'::'.$callable;
		}

		if( !is_callable($callable) ) {
			log_notice(__CLASS__, 'Substitute the default handler for plugin '.$module_name);
//			$callable = $module_name.'::function_plugin';
			$callable = __CLASS__.'::'.$module_name;
		}
		return $callable;
	}

	/**
	 * Add information about a plugin to the 'module_plugins' system-data cache
	 * @since 3.0
	 * @param string $module_name The module name
	 * @param string $name  The plugin name
	 * @param string $type  The plugin type (normally 'function')
	 * @param mixed $callable callable | falsy Static callable e.g.
	 *  'module_name::plugin_handler'
	 *  [$module_name, 'plugin_handler']
	 *  'plugin_handler' (in which case the module name will be added)
	 *  or a falsy value results in the default handler being used
	 * @param bool $cachable Deprecated since 3.0 Whether the plugin is cachable. Default true
	 * @param int  $available Flag(s) indicating the intended use(s) of the plugin. Default AVAIL_FRONTEND.
	 *   See AVAIL_ADMIN and AVAIL_FRONTEND
	 * @return bool indicating success
	 */
	public function _add_dynamic(string $module_name, string $name, string $type, callable $callable, bool $cachable = TRUE, int $available = 1) : bool
	{
		$callable = $this->validate_callable($module_name, $callable);
		if( !$callable ) return FALSE;

		$dirty = FALSE;
		$cache = Lone::get('LoadedData');
		$data = $cache->get('module_plugins');
		$sig = Crypto::hash_string($name.$module_name.$callable);
		if( !isset($data[$sig]) ) {
			if( $available == 0 ) {
				$available = self::AVAIL_FRONTEND;
			} else {
				$available &= self::AVAIL_ALL;
			}
			$data[$sig] = [
				'name'=>$name,
				'module'=>$module_name,
				'type'=>$type,
				'callable'=>$callable,
				'cachable'=>$cachable,
				'available'=>$available,
			];
			$dirty = TRUE;
		}
		else {
			if( $data[$sig]['callable'] != $callable ) { $data[$sig]['callable'] = $callable; $dirty = TRUE; }
			if( $data[$sig]['cachable'] != $cachable ) { $data[$sig]['cachable'] = $cachable; $dirty = TRUE; }
			if( $data[$sig]['available'] != $available ) { $data[$sig]['available'] = $available; $dirty = TRUE; }
		}
		if( $dirty ) {
			$cache->set('module_plugins', $data);
		}
		return TRUE;
	}

	/**
	 * Add information to, or update, information about a plugin in the database
	 * and clear, NOT add to, the 'module_plugins' system-data cache.
	 * This method is normally called during a module's installation/upgrade
	 * or after settings change i.e. not in every request.
	 *
	 * @param string $module_name The module name
	 * @param string $name  The plugin name
	 * @param string $type  The plugin type (function, block, modifier)
	 * @param mixed $callable callable | falsy Static callable e.g.
	 *  'module_name::plugin_handler'
	 *  [$module_name, 'plugin_handler']
	 *  'plugin_handler' (in which case the module name will be added)
	 *  or a falsy value results in the default handler being used
	 * @param bool $cachable Deprecated since 3.0 Whether the plugin is cachable. Default true
	 * @param int  $available Flag(s) indicating the intended use(s) of the plugin. Default AVAIL_FRONTEND.
	 *   See AVAIL_ADMIN and AVAIL_FRONTEND
	 * @return mixed boolean | null
	 */
	public function _add(string $module_name, string $name, string $type, callable $callable, bool $cachable = TRUE, int $available = 1)
	{
		$callable = $this->validate_callable($module_name, $callable);
		if( !$callable ) return FALSE;

		if( startswith($callable, __CLASS__.'::') ) $callable = NULL; // no need to store the default
		if( $available == 0 ) {
			$available = self::AVAIL_FRONTEND;
		}
		else {
			$available &= self::AVAIL_ALL;
		}
		$cachable = ($cachable) ? 1 : 0;

		$db = Lone::get('Db');
		$pref = CMS_DB_PREFIX;
		$query = <<<EOS
UPDATE {$pref}module_smarty_plugins SET type=?,callable=?,available=?,cachable=? WHERE `name`=? AND module=?
EOS;
		$db->execute($query, [$type, $callable, $available, $cachable, $name, $module_name]);
		//just in case (module,name) is not unique-indexed by the db
		$query = <<<EOS
INSERT INTO {$pref}module_smarty_plugins (`name`,module,type,callable,available,cachable)
SELECT ?,?,?,?,?,? FROM (SELECT 1 AS dmy) Z
WHERE NOT EXISTS (SELECT 1 FROM {$pref}module_smarty_plugins T WHERE T.`name`=? AND T.module=?)
EOS;
		$dbr = $db->execute($query, [
			$name,
			$module_name,
			$type,
			$callable,
			$available,
			$cachable,
			$name,
			$module_name,
		]);

		if( $dbr ) {
			Lone::get('LoadedData')->refresh('module_plugins');
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Remove all plugins for a module from the database, clear the
	 * 'module_plugins' system-data cache
	 *
	 * @param string $module_name
	 */
	public function _remove_by_module(string $module_name)
	{
		$db = Lone::get('Db');
		$query = 'DELETE FROM '.CMS_DB_PREFIX.'module_smarty_plugins WHERE module=?';
		$dbr = $db->execute($query, [$module_name]);
		if( $dbr ) {
			Lone::get('LoadedData')->refresh('module_plugins');
		}
	}

	/**
	 * Remove a named plugin from the database, clear the 'module_plugins'
	 * system-data cache
	 *
	 * @param string $name
	 */
	public function _remove_by_name(string $name)
	{
		$db = Lone::get('Db');
		$query = 'DELETE FROM '.CMS_DB_PREFIX.'module_smarty_plugins WHERE `name`=?';
		$dbr = $db->execute($query, [$name]);
		if( $dbr ) {
			Lone::get('LoadedData')->refresh('module_plugins');
		}
	}
} // class
