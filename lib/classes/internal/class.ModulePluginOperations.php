<?php
# Class of functions to manage modules' smarty plugins
# Copyright (C) 2010-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use cms_utils;
use CmsApp;
//use CMSMS\internal\global_cachable;
//use CMSMS\internal\global_cache;
use const CMS_DB_PREFIX;
use function cms_error;
use function endswith;
use function startswith;

/**
 * A class to manage smarty plugins registered by modules.
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
	 * A flag indicating that the plugin is intended to be available in the frontend
	 */
	const AVAIL_FRONTEND = 1;

	/**
	 * A flag indicating that the plugin is intended to be available for admin templates
	 */
	const AVAIL_ADMIN = 2;

	/* *
	 * @ignore
	 */
//	private static $_instance = null;

	//TODO namespaced global variables here
	/**
	 * @ignore
	 */
	private static $_data;

	/**
	 * @ignore
	 */
	private static $_loaded;

	/**
	 * @ignore
	 */
	private static $_modified;

	/* *
	 * @ignore
	 */
/*	public function __construct() {
/ *
		$obj = new global_cachable('session_plugin_modules', function()
				{
					$names = [];
					$tmp = (new module_meta())->module_list_by_method('IsPluginModule');
					if( $tmp ) {
						// module-names cached in the database, maybe without per-request RegisterModulePlugin()
						$query = 'SELECT DISTINCT module FROM '.CMS_DB_PREFIX.'module_smarty_plugins';
						$db = CmsApp::get_instance()->GetDb();
						$list = $db->GetCol($query);
						for( $i = 0, $n = count($tmp); $i < $n; ++$i ) {
							$module = $tmp[$i];
							if( !$list || !in_array($module, $list) ) {
								$names[] = $module;
							}
						}
					}
					return $names;
				});
		global_cache::add_cachable($obj);
* /
	}
*/

	/**
	 * @ignore
	 */
//    private function __clone() {}

	/**
	 * Get an instance of this class.
	 * @deprecated since 2.3 use new ModulePluginOperations()
	 */
	public static function get_instance() : self
	{
//		if( !self::$_instance ) { self::$_instance = new self(); } return self::$_instance;
		return new self();
	}

	/* *
	 * Inform smarty about all module-plugins which are not recorded in the
	 * module_smarty_plugins database table.
	 * In effect, this is insurance against malformed module lazy-loading
	 * and/or plugin registration outside a module's constructor.
	 * @since 2.3
	 */
/* EXPERIMENTAL ALTERNATIVE
	public function RegisterSessionPlugins()
	{
		$tmp = global_cache::get('session_plugin_modules'); //module-names NOT cached in the database
		if( $tmp ) {
			$smarty = CmsApp::get_instance()->GetSmarty();
			foreach( $tmp as $module_name ) {
				//c.f. some-module-object->RegisterModulePlugin();
				try {
					$smarty->registerPlugin('function', $module_name, [$module_name,'function_plugin'], false);
				} catch (Exception $e) {
					//ignore duplicate registrations
				}
			}
		}
	}
*/
	/**
	 * Get data recorded in the module_smarty_plugins database table
	 * @ignore
	 */
	private function _load()
	{
		if( self::$_loaded ) return;
		// todo: cache this stuff.  does not need to be run on each request
		// global_cache 'session_plugin_modules' has only module names for plugin-modules not cached in this table
		self::$_loaded = TRUE;
		self::$_data = [];
		$db = CmsApp::get_instance()->GetDb();
		$query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_smarty_plugins ORDER BY module';
		$tmp = $db->GetArray($query);
		if( is_array($tmp) ) {
			for( $i = 0, $n = count($tmp); $i < $n; $i++ ) {
				$row = $tmp[$i];
				// verify signature
				$sig = md5($row['name'].$row['module'].$row['callback']);
				if( $sig == $row['sig'] ) {
					$row['callback'] = unserialize($row['callback']);
					self::$_data[$row['sig']] = $row;
				}
			}
		}
	}

	/**
	 * Record cached data (self::$_data) in the module_smarty_plugins table
	 * @ignore
	 * @return mixed true | null
	 */
	private function _save()
	{
		if( !self::$_data || !self::$_modified )
			return;

		$db = CmsApp::get_instance()->GetDb();
		$query = 'TRUNCATE TABLE '.CMS_DB_PREFIX.'module_smarty_plugins';
		$db->Execute($query);
		// TODO use prepared statement
		$query = 'INSERT INTO '.CMS_DB_PREFIX.'module_smarty_plugins (sig,name,module,type,callback,available) VALUES';
		$fmt = " ('%s','%s','%s','%s','%s',%d),";
		foreach( self::$_data as $row ) {
			$query .= sprintf($fmt,$row['sig'],$row['name'],$row['module'],$row['type'],serialize($row['callback']),$row['available']);
		}
		if( endswith($query,',') ) $query = substr($query,0,-1);
		$dbr = $db->Execute($query);
		if( !$dbr ) return FALSE;
//DEBUG		global_cache::clear('session_plugin_modules');
		self::$_modified = FALSE;
		return TRUE;
	}

	/**
	 * Attempt to load a named module-plugin.
	 * This might be called by Smarty when looking for an unknown plugin.
	 * @internal
	 *
	 * @param string $name name of the undefined tag
	 * @param string $type tag type (commonly Smarty::PLUGIN_FUNCTION, maybe Smarty::PLUGIN_BLOCK,
	 *  Smarty::PLUGIN_COMPILER, Smarty::PLUGIN_MODIFIER, Smarty::PLUGIN_MODIFIERCOMPILER)
	 * @return mixed array | null Array members per database record:
	 *  sig,name,module,type,callback,cachable,available
	 */
	public static function load_plugin($name,$type)
	{
		$row = self::get_instance()->find($name,$type);
		if( !is_array($row) ) return;

		// load the module
		$module = cms_utils::get_module($row['module']);
		if( $module ) {
			// fix the callback, in case somebody used 'this' in the string.
			if( is_array($row['callback']) ) {
				// it's an array
				if( count($row['callback']) == 2 ) {
					// first element is some kind of string... do some magic to point to the module object
					if( !is_string($row['callback'][0]) || strtolower($row['callback'][0]) == 'this') $row['callback'][0] = $row['module'];
				}
				else {
					// an array with only one item?
					cms_error('Cannot load plugin '.$row['name'].' from module '.$row['module'].' because of errors in the callback');
					return;
				}
			}
			else if( startswith($row['callback'],'::') ) {
				// ::method syntax (implies module name)
				$row['callback'] = [$row['module'],substr($row['callback'],2)];
			}
			else {
				// assume it's just a method name
				$row['callback'] = [$row['module'],$row['callback']];
			}
		}
		if( !is_callable($row['callback']) ) {
			// it's in the db... but not callable.
			cms_error('Cannot load plugin '.$row['name'].' from module '.$row['module'].' because callback not callable (module disabled?)');
			$row['callback'] = [$row['module'],'function_plugin'];
		}
		return $row;
	}

	/**
	 * Find the details for a specific plugin
	 *
	 * @param string $name
	 * @param string $type
	 * @return mixed array | null
	 */
	public function find($name,$type)
	{
		$this->_load();
		if( self::$_data ) {
			foreach( self::$_data as $row ) {
				if( $row['name'] == $name && $row['type'] == $type ) return $row;
			}
		}
	}

	/**
	 * Add information about a plugin to the local data cache and to the database
	 * This method is normally called during a module's installation/upgrade.
	 *
	 * @deprecated since 2.3 Instead use (new ModulePluginOperations())->add()
	 * @param string $module_name The module name
	 * @param string $name  The plugin name
	 * @param string $type  The plugin type (function,block,modifier)
	 * @param callable $callback  The callable (static function) which runs the plugin.
	 * @param bool $cachable UNUSED since 2.3 (always cachable) Optional flag whether the plugin is cachable, default true
	 * @param int  $available Optional bit-flag(s) indicating the availability of the plugin. default 0.  See AVAIL_ADMIN AND AVAIL_FRONTEND
	 */
	public static function addStatic($module_name,$name,$type,$callback,$cachable = TRUE,$available = 0)
	{
		return self::get_instance()->add($module_name,$name,$type,$callback,$cachable,$available);
	}

	/**
	 * Add information about a plugin to the local data cache and to the database.
	 * This method is normally called during a module's installation/upgrade.
	 *
	 * @param string $module_name The module name
	 * @param string $name  The plugin name
	 * @param string $type  The plugin type (function,block,modifier)
	 * @param callable $callback A static function to call e.g. 'function_plugin' or [$module_name,'function_plugin']
	 * @param bool $cachable UNUSED since 2.3 (always cachable) Whether the plugin is cachable. Default true
	 * @param int  $available Flag(s) indicating the availability of the plugin. Default 0, hence AVAIL_FRONTEND.
	 *   See AVAIL_ADMIN and AVAIL_FRONTEND
	 * @return mixed boolean | null
	 */
	public function add(string $module_name,string $name,string $type,callable $callback,bool $cachable = TRUE,int $available = 0)
	{
		$this->_load();
		if( !is_array(self::$_data) ) self::$_data = [];

		// todo... validate params

		$sig = md5($name.$module_name.serialize($callback));
		if( !isset(self::$_data[$sig]) ) {
			if( $available == 0 ) $available = self::AVAIL_FRONTEND;
			self::$_data[$name] = [
				'sig'=>$sig,
				'module'=>$module_name,
				'name'=>$name,
				'type'=>$type,
				'callback'=>$callback,
				'available'=>$available
			];
			self::$_modified = TRUE;
			return $this->_save();
		}
		return TRUE;
	}

	/**
	 * Remove all plugins for a module from the local datacache and the database
	 *
	 * @param string $module_name
	 */
	public static function remove_by_module(string $module_name)
	{
		self::get_instance()->_remove_by_module($module_name);
	}

	/**
	 * Remove all plugins for a module from the local data cache and the database
	 *
	 * @param string $module_name
	 */
	public function _remove_by_module(string $module_name)
	{
		$this->_load();
		if( self::$_data ) {
			foreach( self::$_data as $key => $row ) {
				if( $module_name == $row['module'] ) {
					self::$_data[$key] = null;
					self::$_modified = true;
				}
			}
			if( self::$_modified ) {
				self::$_data = array_filter(self::$_data);
				$this->_save();
			}
		}
	}

	/**
	 * Remove a named plugin from the local datacache and the database
	 *
	 * @param string $name
	 */
	public static function remove_by_name(string $name)
	{
		self::get_instance()->_remove_by_name($name);
	}

	/**
	 * Remove a named plugin from the local datacache and the database
	 *
	 * @param string $name
	 */
	public function _remove_by_name(string $name)
	{
		$this->_load();
		if( self::$_data ) {
			foreach( self::$_data as $key => $row ) {
				if( $name == $row['name'] ) {
					self::$_data[$key] = null;
					self::$_modified = true;
				}
			}
			if( self::$_modified ) {
				self::$_data = array_filter(self::$_data);
				$this->_save();
			}
		}
	}
} // class
