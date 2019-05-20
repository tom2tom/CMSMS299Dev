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

use cms_siteprefs;
use cms_utils;
use CmsApp;
use CMSMS\internal\global_cachable;
use CMSMS\internal\global_cache;
use const CMS_DB_PREFIX;
use function audit;

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
	 * A flag indicating that the plugin can be used in frontend pages/templates
	 */
	const AVAIL_FRONTEND = 1;

	/**
	 * A flag indicating that the plugin can be used in admin templates
	 */
	const AVAIL_ADMIN = 2;

	/**
	 * Get an instance of this class.
	 * @deprecated since 2.3 use new ModulePluginOperations()
	 */
	public static function get_instance() : self
	{
		return new self();
	}

	/**
	 * Initialize 'module_plugins' global cache
	 * @since 2.3
	 */
	public static function setup()
	{
		$obj = new global_cachable('module_plugins', function()
			{
				$data = [];
				$tmp = (new module_meta())->module_list_by_method('IsPluginModule');
				if( $tmp ) {
					$val = (int)cms_siteprefs::get('smarty_cachelife',-1);
					if( $val != 0 ) $val = 1;
					foreach( $tmp as $module ) {
						$callback = $module.'::function_plugin';
						$sig = md5($module.$module.$callback);
						$data[$sig] = [
							'name'=>$module,
							'module'=>$module,
							'type'=>'function',
							'callback'=>$callback,
							'cachable'=>$val, //maybe changed later
							'available'=>self::AVAIL_FRONTEND + self::AVAIL_ADMIN, //ditto
						];
					}
				}
				// adjust module-plugins recorded in the database
				$db = CmsApp::get_instance()->GetDb();
				$query = 'SELECT DISTINCT * FROM '.CMS_DB_PREFIX.'module_smarty_plugins';
				$list = $db->GetArray($query);
				foreach ($list as &$row) {
					$sig = md5($row['name'].$row['module'].$row['callback']);
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
		global_cache::add_cachable($obj);
	}

	/**
	 * Return parameters for a named module-plugin, if the supplied name is recognized.
	 * This might be called by Smarty when looking for something to process a plugin.
	 *
	 * @param string $name name of the tag being sought
	 * @param string $type tag type (commonly Smarty::PLUGIN_FUNCTION, maybe Smarty::PLUGIN_BLOCK,
	 *  Smarty::PLUGIN_COMPILER, Smarty::PLUGIN_MODIFIER, Smarty::PLUGIN_MODIFIERCOMPILER)
	 * @return mixed array | null Array members 'callback','cachable'
	 */
	public static function load_plugin($name,$type)
	{
		$row = self::get_instance()->find($name,$type);
		if( is_array($row) ) {
//TODO 		if( $row['available'] IS NOT consistent with current request ) return;
			// load the module
			$module = cms_utils::get_module($row['module']);
			if( $module ) {
				return [
					'callback' => $row['callback'],
					'cachable' => (bool)$row['cachable']
				];
			}
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
	public function find($name,$type)
	{
		$data = global_cache::get('module_plugins');
		if( $data ) {
			foreach( $data as $row ) {
				if( $row['type'] == $type && strcasecmp($row['name'],$name) == 0 ) return $row;
			}
		}
	}

	/**
	 * @since 2.3
	 * @ignore
	 * @param mixed $callback string|array
	 * @return string|null
	 */
	private function validate_callback($callback)
	{
		if( is_array($callback) ) {
			if( count($callback) == 2 ) {
				// ensure first member refers to the correct module
				if( !is_string($callback[0]) || strtolower($callback[0]) == 'this') {
					$callback[0] = $module_name;
				}
				$callback = $callback[0].'::'.$callback[1];
			}
			else {
				// an array with only one member !?
				audit('',__CLASS__,'Cannot register plugin '.$name.' for module '.$module_name.' - invalid callback');
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
			audit('',__CLASS__,'Substitute the default handler for plugin '.$name);
			$callback = $module_name.'::function_plugin';
		}
		return $callback;
	}

	/**
	 * Add information about a plugin to the 'module_plugins' global cache
	 * @since 2.3
	 * @param string $module_name The module name
	 * @param string $name  The plugin name
	 * @param string $type  The plugin type (normally 'function')
	 * @param callable $callback A static function to call e.g. 'function_plugin' or 'module_name::function_plugin' or [$module_name,'function_plugin']
	 * @param bool $cachable Deprecated since 2.3 Whether the plugin is cachable. Default true
	 * @param int  $available Flag(s) indicating the intended use(s) of the plugin. Default 0, hence AVAIL_FRONTEND.
	 *   See AVAIL_ADMIN and AVAIL_FRONTEND
	 * @return bool indicating success
	 */
	public function add_dynamic(string $module_name, string $name, string $type, callable $callback, bool $cachable = TRUE, int $available = 0) : bool
	{
		$callback = $this->validate_callback($callback);
		if( !$callback ) return FALSE;

		$dirty = FALSE;
		$data = global_cache::get('module_plugins');
		$sig = md5($name.$module_name.$callback);
		if( !isset($data[$sig]) ) {
			if( $available == 0 ) $available = self::AVAIL_FRONTEND + self::AVAIL_ADMIN;
			$data[$sig] = [
				'name'=>$name,
				'module'=>$module_name,
				'type'=>$type,
				'callback'=>$callback,
				'cachable'=>$cachable,
				'available'=>$available
			];
			$dirty = TRUE;
		}
		else {
			if( $data[$sig]['callback'] != $callback ) { $data[$sig]['callback'] = $callback; $dirty = TRUE; }
			if( $data[$sig]['cachable'] != $cachable ) { $data[$sig]['cachable'] = $cachable; $dirty = TRUE; }
			if( $data[$sig]['available'] != $available ) { $data[$sig]['available'] = $available; $dirty = TRUE; }
		}
		if( $dirty ) {
			global_cache::update('module_plugins', $data);
		}
		return TRUE;
	}

	/**
	 * Add information about a plugin to the database
	 * and clear, NOT add to, the 'module_plugins' global cache.
	 * This method is normally called during a module's installation/upgrade.
	 *
	 * @param string $module_name The module name
	 * @param string $name  The plugin name
	 * @param string $type  The plugin type (function,block,modifier)
	 * @param callable $callback A static function to call e.g. 'function_plugin' or 'module_name::function_plugin' or [$module_name,'function_plugin']
	 * @param bool $cachable Deprecated since 2.3 Whether the plugin is cachable. Default true
	 * @param int  $available Flag(s) indicating the intended use(s) of the plugin. Default 0, hence AVAIL_FRONTEND.
	 *   See AVAIL_ADMIN and AVAIL_FRONTEND
	 * @return mixed boolean | null
	 */
	public function add(string $module_name,string $name,string $type,callable $callback,bool $cachable = TRUE,int $available = 0)
	{
		$callback = $this->validate_callback($callback);
		if( !$callback ) return FALSE;

		$all = self::AVAIL_FRONTEND + self::AVAIL_ADMIN;
		if( $available == 0 ) {
			$available = $all;
		}
		else {
			$available &= $all;
		}

		$db = CmsApp::get_instance()->GetDb();
		$query = 'INSERT INTO '.CMS_DB_PREFIX.'module_smarty_plugins (name,module,type,callback,available,cachable) VALUES(?,?,?,?,?,?)';
		$dbr = $db->Execute($query,[
			$name,
			$module_name,
			$type,
			$callback,
			$available,
			$cachable,
		]);
		if( $dbr ) {
			global_cache::clear('module_plugins');
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Add information about a plugin to the database
	 * @deprecated since 2.3 Instead use (new ModulePluginOperations())->add()
	 * @see add()
	 */
	public static function addStatic($module_name,$name,$type,$callback,$cachable = TRUE,$available = 0)
	{
		return self::get_instance()->add($module_name,$name,$type,$callback,$cachable,$available);
	}

	/**
	 * Remove all plugins for a module from the database
	 *
	 * @param string $module_name
	 */
	public static function remove_by_module(string $module_name)
	{
		self::get_instance()->_remove_by_module($module_name);
	}

	/**
	 * Remove all plugins for a module from the database, clear the
	 * 'module_plugins' global cache
	 *
	 * @param string $module_name
	 */
	public function _remove_by_module(string $module_name)
	{
		$db = CmsApp::get_instance()->GetDb();
		$query = 'DELETE FROM '.CMS_DB_PREFIX.'module_smarty_plugins WHERE module=?';
		$dbr = $db->Execute($query,[$module_name]);
		if( $dbr ) {
			global_cache::clear('module_plugins');
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
	 * Remove a named plugin from the database, clear the
	 * 'module_plugins' global cache
	 *
	 * @param string $name
	 */
	public function _remove_by_name(string $name)
	{
		$db = CmsApp::get_instance()->GetDb();
		$query = 'DELETE FROM '.CMS_DB_PREFIX.'module_smarty_plugins WHERE name=?';
		$dbr = $db->Execute($query,[$name]);
		if( $dbr ) {
			global_cache::clear('module_plugins');
		}
	}
} // class
