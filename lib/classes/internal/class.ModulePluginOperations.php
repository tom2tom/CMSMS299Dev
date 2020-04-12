<?php
# Class of functions to manage modules' smarty plugins
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

namespace CMSMS\internal;

use cms_siteprefs;
use cms_utils;
use CmsApp;
use CmsCoreCapabilities;
use CMSModule;
use CMSMS\AppSingle;
use CMSMS\AppState;
use CMSMS\internal\GetParameters;
use CMSMS\ModuleOperations;
use CMSMS\SysDataCache;
use CMSMS\SysDataCacheDriver;
use DeprecationNotice;
use Throwable;
use const CLEAN_STRING;
use const CMS_DB_PREFIX;
use const CMS_DEPREC;
use function audit;

/**
 * A class to manage smarty plugins registered by modules. Methods may be called
 * statically as ModulePluginOperations::function()
 * or (since 2.9) non-static (new ModulePluginOperations())->_function() NOTE the '_' prefix
 *
 * @package CMS
 * @license GPL
 * @author Robert Campbell <calguy1000@cmsmadesimple.org>
 * @internal
 * @final
 * @access private
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

	private static $_instance = null;

	/**
	 * Call a class-method from a static context
	 * @param string $name Method name (no aliasing done here)
	 * @param array $args Method argument(s)
	 * @return mixed
	 */
	public static function __callStatic($name, $args)
	{
		$obj = self::get_instance();
		if( $name == 'addStatic' ) {
			assert(empty(CMS_DEPREC), new DeprecationNotice('method','(ModulePluginOperations::add()'));
			return $obj->_add(...$args);
		}
		$pname = '_'.$name;
		if( method_exists($obj, $pname) ) {
			return $obj->$pname(...$args);
		}
	}

	/**
	 * Get an instance of this class, for use by class static-methods.
	 * No properties to 'protect', not worth caching as AppSingle::ModulePluginOperations()
	 * @return self
	 */
	public static function get_instance() : self
	{
		if( !self::$_instance ) { self::$_instance = new self(); }
		return self::$_instance;
	}

	/**
	 * Initialize 'module_plugins' system-data cache
	 * @since 2.3
	 */
	public function _setup()
	{
		$obj = new SysDataCacheDriver('module_plugins', function()
			{
				$data = [];
				$modops = ModuleOperations::get_instance();
				$tmp = $modops->GetCapableModules(CmsCoreCapabilities::PLUGIN_MODULE);
				$tmp2 = $modops->GetMethodicModules('IsPluginModule',TRUE); //deprecated since 2.3
				if( $tmp || $tmp2 ) {
					$val = (int)cms_siteprefs::get('smarty_cachelife',-1);
					if( $val != 0 ) $val = 1;
					foreach( array_unique( array_merge($tmp, $tmp2)) as $module ) {
						$callback = $module.'::function_plugin';
						$sig = cms_utils::hash_string($module.$module.$callback);
						$data[$sig] = [
							'name'=>$module,
							'module'=>$module,
							'type'=>'function',
							'callback'=>$callback,
							'cachable'=>$val, //maybe changed later
							'available'=>self::AVAIL_ALL, //ditto
						];
					}
				}
				// add, or replace by, module-plugins recorded in the database
				$db = CmsApp::get_instance()->GetDb();
				$query = 'SELECT DISTINCT * FROM '.CMS_DB_PREFIX.'module_smarty_plugins';
				$list = $db->GetArray($query);
				foreach ($list as &$row) {
					$sig = cms_utils::hash_string($row['name'].$row['module'].$row['callback']);
					$data[$sig] = [
						'name'=>$row['name'],
						'module'=>$row['module'],
						'type'=>$row['type'],
						'callback'=>$row['callback'],
						'cachable'=>(bool)$row['cachable'],
						'available'=>(int)$row['available'],
					];
				}
				return $data;
			});
		SysDataCache::get_instance()->add_cachable($obj);
	}

	/**
	 * Process a module-tag
	 * This method (or its static equivalent) is used by the {cms_module} plugin
	 * and to process {ModuleName} tags
	 * @since 2.9 this does the work for global method cms_module_plugin()
	 *
	 * @param array $params A hash of action-parameters
	 * @param object $template A Smarty_Internal_Template object
	 * @return string The module output string or an error message string or ''
	 */
	public function _call_plugin_module(array $params, $template) : string
	{
		if( !empty($params['module']) ) {
			$module = $params['module'];
		}
		else {
			return '<!-- ERROR: module name not specified -->';
		}

		if( !($modinst = $this->_get_plugin_module($module)) ) {
			return "<!-- ERROR: $module is not available, in this context at least -->\n";
		}

		unset($params['module']);
		if( !empty($params['action']) ) {
			// action was set in the module tag
			$action = $params['action'];
	//	   unset($params['action']);  unfortunate 2.3 deprecation
		}
		else {
			$params['action'] = $action = 'default'; //2.3 deprecation
		}

		if( !empty($params['idprefix']) ) {
			// idprefix was set in the module tag
			$id = $params['idprefix'];
			$setid = true;
		}
		else {
			// multiple modules might be used in a page|template
			// just in case they get confused ...
			static $modnum = 1;
			++$modnum;
			$id = "m{$modnum}_";
			$setid = false;
		}

		$rparams = (new GetParameters())->decode_action_params();
		if( $rparams ) {
			$mactmodulename = $rparams['module'] ?? '';
			if( strcasecmp($mactmodulename, $module) == 0 ) {
				$checkid = $rparams['id'] ?? '';
				$inline = !empty($rparams['inline']);
				if( $inline && $checkid == $id ) {
					$action = $rparams['action'] ?? 'default';
					$params['action'] = $action; // deprecated since 2.3
					unset($rparams['module'], $rparams['id'], $rparams['action'], $rparams['inline']);
					$params = array_merge($params, $rparams, AppSingle::ModuleOperations()->GetModuleParameters($id));
				}
			}
		}
	/*  if( isset($_REQUEST['mact']) ) {
			// We're handling an action.  Check if it is for this call.
			// We may be calling module plugins multiple times in the template,
			// but a POST or GET mact can only be for one of them.
			$mact = filter_var($_REQUEST['mact'], FILTER_SANITIZE_STRING);
			$ary = explode(',', $mact, 4);
			$mactmodulename = $ary[0] ?? '';
			if( strcasecmp($mactmodulename, $module) == 0 ) {
				$checkid = $ary[1] ?? '';
				$inline = isset($ary[3]) && $ary[3] === 1;
				if( $inline && $checkid == $id ) { // presumbly $setid true i.e. not a random id
					// the action is for this instance of the module and we're inline
					// i.e. the results are supposed to replace the tag, not {content}
					$action = $ary[2] ?? 'default';
					$params['action'] = $action; // deprecated since 2.3
					$params = array_merge($params, AppSingle::ModuleOperations()->GetModuleParameters($id));
				}
			}
		}
	*/
		$params['id'] = $id; // deprecated since 2.3
		if( $setid ) {
			$params['idprefix'] = $id; // might be needed per se, probably not
			$modinst->SetParameterType('idprefix', CLEAN_STRING); // in case it's a frontend request
		}
		$returnid = AppSingle::App()->get_content_id();
		$params['returnid'] = $returnid;

		// collect action output (echoed and/or returned, but ignoring literal 1,
		// probably from inclusion of action code without explict return value
		// too bad if the action actually returned 1!
		ob_start();
		$ret = $modinst->DoActionBase($action, $id, $params, $returnid, $template);
		if( $ret !== 1 && ($ret || is_numeric($ret)) ) {
			echo $ret;
		}
		$out = ob_get_clean();

		if( isset($params['assign']) ) {
			$template->assign(trim($params['assign']), $out);
			return '';
		}
		return $out;
	}

	/**
	 * Return the module (if any) to use for processing the specified tag.
	 * @since 2.3
	 * @param string $name Name of the tag whose processor-module is wanted
	 * @param string $type Optional tag type (commonly Smarty::PLUGIN_FUNCTION, maybe Smarty::PLUGIN_BLOCK,
	 *  Smarty::PLUGIN_COMPILER, Smarty::PLUGIN_MODIFIER, Smarty::PLUGIN_MODIFIERCOMPILER)
	 *  Default 'function'
	 * @return mixed CMSModule | null
	 */
	public function _get_plugin_module(string $name,string $type = 'function')
	{
		$row = $this->_find($name,$type);
		if( is_array($row) ) {
	 		if( $row['available'] != self::AVAIL_ALL ) {
				$states = AppState::get_states();
				if( in_array(AppState::STATE_ADMIN_PAGE,$states) ) {
					if( !($row['available'] & self::AVAIL_ADMIN) ) return;
				}
				elseif( in_array(AppState::STATE_FRONT_PAGE,$states) ) {
					if( !($row['available'] & self::AVAIL_FRONTEND) ) return;
				}
				else {
					return;
				}
			}
			return ModuleOperations::get_instance()->get_module_instance($row['module']);
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
	 * @return mixed array | null Array members 'callback','cachable'
	 */
	public function _load_plugin(string $name,string $type = 'function')
	{
		$module = $this->_get_plugin_module($name,$type);
		if( $module ) {
			$row = $this->_find($name,$type);
			$cb = $row['callback'];
			if( strncmp($cb,'s:',3) === 0 || strncmp($cb ,'a:2:{',5) === 0) {
				try {
					$cb = unserialize($row['callback']);
				}
				catch (Throwable $t) {
					$cb = false;
				}
			}
			if( is_string($cb) && strpos($cb, '::') === false ) {
				$cb = $row['module'].'::'.$cb;
			}
			return [
				'callback' => $cb,
				'cachable' => (bool)$row['cachable']
			];
		}
	}

	/**
	 * Try to find a match for a named & typed module-plugin
	 * Since 2.3, the name-comparison is case-insensitive
	 *
	 * @param string $name
	 * @param string $type
	 * @return mixed array | null
	 */
	public function _find(string $name,string $type)
	{
		$data = SysDataCache::get_instance()->get('module_plugins');
		if( $data ) {
			foreach( $data as $row ) {
				if( $row['type'] == $type && strcasecmp($row['name'],$name) == 0 ) return $row;
			}
		}
	}

	/**
	 * @since 2.3
	 * @ignore
	 * @param string $module_name The module name
	 * @param mixed $callback string|array
	 * @return string|null
	 */
	private function validate_callback(string $module_name,$callback)
	{
		if( is_array($callback) ) {
			if( count($callback) == 2 ) {
				// ensure first member refers to a module (not necessarily the one doing the registration)
				if( $callback[0] instanceof CMSModule ) {
					$callback[0] = $callback[0]->GetName();
				}
				elseif( !is_string($callback[0]) || strtolower($callback[0]) == 'this') {
					$callback[0] = $module_name;
				}
				$callback = $callback[0].'::'.$callback[1];
			}
			else {
				// an array with only one member !?
				audit('',self::class,'Cannot register plugin '.$name.' for module '.$module_name.' - invalid callback');
				return;
			}
		}
		elseif( ($p = strpos($callback,'::')) !== FALSE ) {
			if( $p === 0 ) {
				// ::method syntax (implies module name)
				$callback = $module_name.$callback;
			}
		}
		else {
			// assume it's just a method name
			$callback = $module_name.'::'.$callback;
		}

		if( !is_callable($callback) ) {
			audit('',self::class,'Substitute the default handler for plugin '.$name);
			$callback = $module_name.'::function_plugin';
		}
		return $callback;
	}

	/**
	 * Add information about a plugin to the 'module_plugins' system-data cache
	 * @since 2.3
	 * @param string $module_name The module name
	 * @param string $name  The plugin name
	 * @param string $type  The plugin type (normally 'function')
	 * @param callable $callback A static function to call e.g. 'function_plugin' or 'module_name::function_plugin' or [$module_name,'function_plugin']
	 * @param bool $cachable Deprecated since 2.3 Whether the plugin is cachable. Default true
	 * @param int  $available Flag(s) indicating the intended use(s) of the plugin. Default AVAIL_FRONTEND.
	 *   See AVAIL_ADMIN and AVAIL_FRONTEND
	 * @return bool indicating success
	 */
	public function _add_dynamic(string $module_name,string $name,string $type,callable $callback,bool $cachable = TRUE,int $available = 1) : bool
	{
		$callback = $this->validate_callback($module_name,$callback);
		if( !$callback ) return FALSE;

		$dirty = FALSE;
		$cache = SysDataCache::get_instance();
		$data = $cache->get('module_plugins');
		$sig = cms_utils::hash_string($name.$module_name.$callback);
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
				'callback'=>$callback,
				'cachable'=>$cachable,
				'available'=>$available,
			];
			$dirty = TRUE;
		}
		else {
			if( $data[$sig]['callback'] != $callback ) { $data[$sig]['callback'] = $callback; $dirty = TRUE; }
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
	 * @param string $type  The plugin type (function,block,modifier)
	 * @param callable $callback A static function to call e.g. 'function_plugin' or 'module_name::function_plugin' or [$module_name,'function_plugin']
	 * @param bool $cachable Deprecated since 2.3 Whether the plugin is cachable. Default true
	 * @param int  $available Flag(s) indicating the intended use(s) of the plugin. Default AVAIL_FRONTEND.
	 *   See AVAIL_ADMIN and AVAIL_FRONTEND
	 * @return mixed boolean | null
	 */
	public function _add(string $module_name,string $name,string $type,callable $callback,bool $cachable = TRUE,int $available = 1)
	{
		$callback = $this->validate_callback($module_name,$callback);
		if( !$callback ) return FALSE;

		if( $available == 0 ) {
			$available = self::AVAIL_FRONTEND;
		}
		else {
			$available &= self::AVAIL_ALL;
		}
		$cachable = ($cachable) ? 1 : 0;

		$db = CmsApp::get_instance()->GetDb();
		$pref = CMS_DB_PREFIX;
		$query = <<<EOS
UPDATE {$pref}module_smarty_plugins SET type=?,callback=?,available=?,cachable=? WHERE name=? AND module=?
EOS;
		$db->Execute($query, [$type,$callback,$available,$cachable,$name,$module_name]);
		$query = <<<EOS
INSERT INTO {$pref}module_smarty_plugins (name,module,type,callback,available,cachable)
SELECT ?,?,?,?,?,? FROM (SELECT 1 AS dmy) Z
WHERE NOT EXISTS (SELECT 1 FROM {$pref}module_smarty_plugins T WHERE T.name=? AND T.module=?)
EOS;
		$dbr = $db->Execute($query,[
			$name,
			$module_name,
			$type,
			$callback,
			$available,
			$cachable,
			$name,
			$module_name,
		]);

		if( $dbr ) {
			SysDataCache::get_instance()->release('module_plugins');
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
		$db = CmsApp::get_instance()->GetDb();
		$query = 'DELETE FROM '.CMS_DB_PREFIX.'module_smarty_plugins WHERE module=?';
		$dbr = $db->Execute($query,[$module_name]);
		if( $dbr ) {
			SysDataCache::get_instance()->release('module_plugins');
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
		$db = CmsApp::get_instance()->GetDb();
		$query = 'DELETE FROM '.CMS_DB_PREFIX.'module_smarty_plugins WHERE name=?';
		$dbr = $db->Execute($query,[$name]);
		if( $dbr ) {
			SysDataCache::get_instance()->release('module_plugins');
		}
	}
} // class
