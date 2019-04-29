<?php
#class of utility-methods for operating on and with modules
#Copyright (C) 2004-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.
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

namespace CMSMS;

use cms_config;
use cms_siteprefs;
use cms_userprefs;
use cms_utils;
use CmsApp;
use CmsCoreCapabilities;
use CmsFileSystemException;
use CmsLayoutTemplateType;
use CMSModule;
use CMSMS\AdminAlerts\Alert;
use CMSMS\internal\global_cache;
use CMSMS\internal\module_meta;
use CMSMS\TemplateOperations;
use LogicException;
use const CMS_DB_PREFIX;
use const CMS_ROOT_PATH;
use const CMS_SCHEMA_VERSION;
use const CMS_VERSION;
use function allow_admin_lang;
use function cms_error;
use function cms_join_path;
use function cms_module_path;
use function cms_module_places;
use function cms_notice;
use function cms_warning;
use function debug_buffer;
use function get_userid;
use function lang;

/**
 * A singleton utility class to allow for working with modules.
 *
 * @since       0.9
 * @package     CMS
 * @license GPL
 */
final class ModuleOperations
{
    //TODO namespaced global variables here
	/**
	 * @ignore
	 */
	const CLASSMAP_PREF = 'module_classmap';

	/**
	 * @ignore
	 */
	const STD_AUTH_MODULE = 'CoreAdminLogin';

	/**
	 * @ignore
	 */
//	private static $_instance = null;

	/**
	 * @ignore
	 */
	private static $_auth_module = null;

	/**
	 * @ignore
	 */
	private static $_classmap = null;

	/* *
	 * @ignore
	 */
//    private static $_module_class_map;

	/**
	 * @ignore
	 */
	private static $_modules = null;

	/**
	 * Currently-installed core/system modules list
	 * The population of core modules can change, so this is not hardcoded
	 * @ignore
	 */
	private static $_coremodules = null;

	/**
	 * @ignore
	 */
	private static $_moduleinfo;

	/**
	 * @ignore
	 */
//	private function __construct() {}

	/**
	 * @ignore
	 */
//	private function __clone() {}

	/**
	 * Get an instance of this class.
	 * @deprecated since 2.3 use new ModuleOperations()
	 * @return ModuleOperations
	 */
	public static function get_instance() : self
	{
//		if( !self::$_instance ) { self::$_instance = new self(); } return self::$_instance;
		return new self();
	}

	/**
	 * @ignore
	 */
	private function get_module_classmap() : array
	{
		if( !is_array(self::$_classmap) ) {
			self::$_classmap = [];
			$tmp = cms_siteprefs::get(self::CLASSMAP_PREF);
			if( $tmp ) self::$_classmap = unserialize($tmp);
		}
		return self::$_classmap;
	}

	/**
	 * @ignore
	 * @param string $module
	 * @return mixed string | null
	 */
	private function get_module_classname(string $module)
	{
		$module = trim($module);
		if( !$module ) return;
		$map = $this->get_module_classmap();
		if( isset($map[$module]) ) return $map[$module];
		return $module;
	}

	/**
	 * @param string $module
	 * @return mixed string | null
	 */
	public function get_module_filename(string $module)
	{
		$module = trim($module);
		if( $module ) {
			$fn = cms_module_path($module);
			if( is_file($fn) ) return $fn;
		}
	}

	/**
	 * @param string $module
	 * @return mixed string | null
	 */
	public function get_module_path(string $module)
	{
		$fn = $this->get_module_filename( $module );
		if( $fn ) return dirname( $fn );
	}

	/**
	 * Set the classname of a module.
	 * Useful when the module class is in a namespace.
	 * This caches to alias permanently, as distinct from class_alias()
	 *
	 * @param string $module The module name
	 * @param string $classname The class name
	 */
	public function set_module_classname(string $module, string $classname)
	{
		$module = trim($module);
		$classname = trim($classname);
		if( !$module || !$classname ) return;

		$this->get_module_classmap();
		self::$_classmap[$module] = $classname;
		cms_siteprefs::set(self::CLASSMAP_PREF, serialize(self::$_classmap));
	}

	/**
	 * Generate a moduleinfo.ini file for a module.
	 *
	 * @since 2.3
	 * @param CMSModule $obj a loaded-module object
	 */
	public function generate_moduleinfo(CMSModule $obj)
	{
		$dir = $this->get_module_path( $obj->GetName() );
		if( !is_writable( $dir ) ) throw new CmsFileSystemException(lang('errordirectorynotwritable'));

		$fh = @fopen($dir.'/moduleinfo.ini','w');
		if( $fh === false ) throw new CmsFileSystemException(lang('errorfilenotwritable', 'moduleinfo.ini'));

		fputs($fh,"[module]\n");
		fputs($fh,'name = "'.$obj->GetName()."\"\n");
		fputs($fh,'version = "'.$obj->GetVersion()."\"\n");
		fputs($fh,'description = "'.$obj->GetDescription()."\"\n");
		fputs($fh,'author = "'.$obj->GetAuthor()."\"\n");
		fputs($fh,'authoremail = "'.$obj->GetAuthorEmail()."\"\n");
		fputs($fh,'mincmsversion = "'.$obj->MinimumCMSVersion()."\"\n");
		fputs($fh,'lazyloadadmin = '.($obj->LazyLoadAdmin()?'1':'0')."\n");
		fputs($fh,'lazyloadfrontend = '.($obj->LazyLoadFrontend()?'1':'0')."\n");
		$depends = $obj->GetDependencies();
		if( $depends ) {
			fputs($fh,"[depends]\n");
			foreach( $depends as $key => $val ) {
				fputs($fh,"$key = \"$val\"\n");
			}
		}
		fputs($fh,"[meta]\n");
		fputs($fh,'generated = '.time()."\n");
		fputs($fh,'cms_ver = "'.CMS_VERSION."\"\n");
		fclose($fh);
	}

	/**
	 * @ignore
	 * @param CMSModule $module_obj a module object
	 */
	private function _install_module(CMSModule &$module_obj)
	{
		$module_name = $module_obj->GetName();
		debug_buffer('install_module '.$module_name);

		$gCms = CmsApp::get_instance(); // vars in scope for Install()
		$db = $gCms->GetDb();

		$result = $module_obj->Install();
		if( !isset($result) || $result === FALSE) {
			// install returned nothing, or FALSE, a successful installation
			$query = 'DELETE FROM '.CMS_DB_PREFIX.'modules WHERE module_name = ?';
//			$dbr = if result-check done
			$db->Execute($query,[$module_name]);
			$query = 'DELETE FROM '.CMS_DB_PREFIX.'module_deps WHERE child_module = ?';
//			$dbr =
			$db->Execute($query,[$module_name]);

			$lazyload_fe    = (method_exists($module_obj,'LazyLoadFrontend') && $module_obj->LazyLoadFrontend())?1:0;
			$lazyload_admin = (method_exists($module_obj,'LazyLoadAdmin') && $module_obj->LazyLoadAdmin())?1:0;
			$query = 'INSERT INTO '.CMS_DB_PREFIX.'modules
(module_name,version,status,admin_only,active,allow_fe_lazyload,allow_admin_lazyload)
VALUES (?,?,?,?,?,?,?)';
//			$dbr =
			$db->Execute($query,[
			$module_name,$module_obj->GetVersion(),'installed',
			($module_obj->IsAdminOnly())?1:0,1,$lazyload_fe,$lazyload_admin
			]);

			$deps = $module_obj->GetDependencies();
			if( $deps ) {
				$now = $db->DbTimeStamp(time());
				$stmt = $db->Prepare('INSERT INTO '.CMS_DB_PREFIX.'module_deps
(parent_module,child_module,minimum_version,create_date,modified_date)
VALUES (?,?,?,'.$now.',NULL)');
				foreach( $deps as $depname => $depversion ) {
					if( !$depname || !$depversion ) continue;
//					$dbr =
					$db->Execute($stmt,[$depname,$module_name,$depversion]);
				}
				$stmt->close();
			}
			$this->generate_moduleinfo( $module_obj );
			self::$_moduleinfo = [];
			global_cache::clear('modules');
			global_cache::clear('module_deps');
			global_cache::clear('module_plugins');
			global_cache::clear('module_menus');

			cms_notice('Installed module '.$module_name.' version '.$module_obj->GetVersion());
			Events::SendEvent( 'Core', 'ModuleInstalled', [ 'name' => $module_name, 'version' => $module_obj->GetVersion() ] );
			return [TRUE,$module_obj->InstallPostMessage()];
		}

		// install returned something.
		return [FALSE,$result];
	}

	/**
	 * Install a module into the database
	 *
	 * @param string $module_name The name of the module to install
	 * @return array Returns a tuple of whether the install was successful and a message if applicable
	 */
	public function InstallModule(string $module_name)
	{
		// get an instance of the object (force it).
		$obj = $this->get_module_instance($module_name,'',TRUE);
		if( !$obj ) return [FALSE,lang('errormodulenotloaded')];

		// check for dependencies
		$deps = $obj->GetDependencies();
		if( $deps ) {
			foreach( $deps as $mname => $mversion ) {
				if( $mname == '' || $mversion == '' ) continue; // invalid entry.
				$newmod = $this->get_module_instance($mname);
				if( !is_object($newmod) || version_compare($newmod->GetVersion(),$mversion) < 0 ) {
					return [FALSE,lang('missingdependency').': '.$mname];
				}
			}
		}

		// do the actual installation stuff.
		$res = $this->_install_module($obj);
		if( $res[0] == FALSE && $res[1] == '') {
			$res[1] = lang('errorinstallfailed');
			// put mention into the admin log
			cms_error('Installation of module '.$module_name.' failed');
		}
		return $res;
	}

	/**
	 * @ignore
	 */
	private function _get_module_info()
	{
		if( !self::$_moduleinfo ) {
			$tmp = global_cache::get('modules');
			if( is_array($tmp) ) {
				self::$_moduleinfo = [];
				for( $i = 0, $n = count($tmp); $i < $n; $i++ ) {
					$name = $tmp[$i]['module_name'];
					$filename = $this->get_module_filename($name);
					if( is_file($filename) ) {
						if( !isset(self::$_moduleinfo[$name]) ) self::$_moduleinfo[$name] = $tmp[$i];
					}
				}

				$all_deps = $this->_get_all_module_dependencies();
				if( $all_deps && count($all_deps) ) {
					foreach( $all_deps as $mname => $deps ) {
						if( is_array($deps) && count($deps) && isset(self::$_moduleinfo[$mname]) ) {
							$minfo =& self::$_moduleinfo[$mname];
							$minfo['dependants'] = array_keys($deps);
						}
					}
				}
			}
		}

		return self::$_moduleinfo;
	}

	/**
	 * @ignore
	 */
	private function _load_module(string $module_name, bool $force_load = FALSE, bool $dependents = TRUE)
	{
		$gCms = CmsApp::get_instance(); // backwards compatibility... set the global.

		$info = $this->_get_module_info();
		if( !isset($info[$module_name]) && !$force_load ) {
			cms_warning("Nothing is known about $module_name... can't load it");
			return FALSE;
		}

		// okay, lessee if we can load the dependants
		if( $dependents ) {
			$deps = $this->get_module_dependencies($module_name);
			if( $deps ) {
				foreach( $deps as $name => $ver ) {
					if( $name == $module_name ) continue; // a module cannot depend on itself.
					// this is the start of a recursive routine. get_module_instance() may call _load_module
					$obj2 = $this->get_module_instance($name,$ver);
					if( !is_object($obj2) ) {
						cms_warning("Cannot load module $module_name ... Problem loading dependent module $name version $ver");
						return FALSE;
					}
				}
			}
		}

		// now load the module itself... recurses into the autoloader if possible.
		$class_name = $this->get_module_classname($module_name);
		if( !class_exists($class_name,true) ) {
			$fname = $this->get_module_filename($module_name);
			if( !is_file($fname) ) {
				cms_warning("Cannot load $module_name because the module file does not exist");
				return FALSE;
			}

			debug_buffer('including source for module '.$module_name);
			require_once($fname);
		}

		$obj = new $class_name();
		if( !is_object($obj) || ! $obj instanceof CMSModule ) {
			// oops, some problem loading.
			cms_error("Cannot load module $module_name ... some problem instantiating the class");
			return FALSE;
		}

		if( version_compare($obj->MinimumCMSVersion(),CMS_VERSION) == 1 ) {
			// oops, not compatible.... can't load.
			cms_error('Cannot load module '.$module_name.' it is not compatible wth this version of CMSMS');
			unset($obj);
			return FALSE;
		}

		self::$_modules[$module_name] = $obj;

		global $CMS_INSTALL_PAGE;

		$tmp = $gCms->get_installed_schema_version();
		if( $tmp == CMS_SCHEMA_VERSION && isset($CMS_INSTALL_PAGE) && $this->IsSystemModule($module_name)) {
			// during the phar installer, we can use get_module_instance() to install or upgrade core modules
			if( !isset($info[$module_name]) || $info[$module_name]['status'] != 'installed' ) {
				$res = $this->_install_module($obj);
				if( $res[0] == FALSE ) {
					// nope, can't auto install...
					unset($obj,self::$_modules[$module_name]);
					return FALSE;
				}
			}

			// can't auto upgrade modules if cmsms schema versions don't match.
			// check to see if an upgrade is needed.
			if( isset($info[$module_name]) && $info[$module_name]['status'] == 'installed' ) {
				$dbversion = $info[$module_name]['version'];
				if( version_compare($dbversion, $obj->GetVersion()) == -1 ) {
					// looks like upgrade is needed
					$res = $this->_upgrade_module($obj);
					if( !$res ) {
						// upgrade failed
						allow_admin_lang(FALSE); // isn't this ugly.
						debug_buffer("Automatic upgrade of $module_name failed");
						unset($obj,self::$_modules[$module_name]);
						return FALSE;
					}
				}
			}
		}

		if( !$force_load && (!isset($info[$module_name]['status']) || $info[$module_name]['status'] != 'installed') ) {
			debug_buffer('Cannot load an uninstalled module');
			unset($obj,self::$_modules[$module_name]);
			return false;
		}

		global $CMS_STYLESHEET;

		if( !(isset($CMS_STYLESHEET) || isset($CMS_INSTALL_PAGE)) ) {
			global $CMS_ADMIN_PAGE;
			if( isset($CMS_ADMIN_PAGE) ) {
				$obj->InitializeAdmin();
			} else if( !$force_load ) {
				if( $gCms->is_frontend_request() ) {
					$obj->InitializeFrontend();
				}
			}
		}

		// we're all done.
		Events::SendEvent( 'Core', 'ModuleLoaded', [ 'name' => $module_name ] );
		return TRUE;
	}

	/**
	 * Return a list of all modules that appear to exist properly in the modules directories.
	 *
	 * @return array of module names for all modules
	 */
	public function FindAllModules()
	{
		$result = [];
		foreach( cms_module_places() as $dir ) {
			if( is_dir($dir) && $handle = @opendir($dir) ) {
				while( ($file = readdir($handle)) !== false ) { //not glob(), which recurses infinitely
					if( $file == '..' || $file == '.' ) continue;
					$fn = "$dir/$file/$file.module.php";
					if( @is_file($fn) && !in_array($file,$result) ) $result[] = $file;
				}
			}
		}

		sort($result);
		return $result;
	}

	/**
	 * Return the information stored in the database about all installed modules.
	 *
	 * @since 2.0
	 * @return array
	 */
	public function GetInstalledModuleInfo()
	{
		return $this->_get_module_info();
	}

	/**
	 * @ignore
	 */
	private function _upgrade_module( CMSModule &$module_obj, string $to_version = '' )
	{
		// upgrade only if the database schema is up-to-date.
		$gCms = CmsApp::get_instance();
		$tmp = $gCms->get_installed_schema_version();
		if( $tmp && $tmp < CMS_SCHEMA_VERSION ) {
			return [FALSE,lang('error_coreupgradeneeded')];
		}

		$info = $this->_get_module_info();
		$module_name = $module_obj->GetName();
		$dbversion = $info[$module_name]['version'];
		if( $to_version == '' ) $to_version = $module_obj->GetVersion();
		$dbversion = $info[$module_name]['version'];
		if( version_compare($dbversion, $to_version) != -1 ) {
			return [TRUE]; // nothing to do.
		}

		$db = $gCms->GetDb();
		$result = $module_obj->Upgrade($dbversion,$to_version);
		if( !isset($result) || $result === FALSE ) {
			//TODO handle module re-location, if any
			$lazyload_fe    = (method_exists($module_obj,'LazyLoadFrontend') && $module_obj->LazyLoadFrontend())?1:0;
			$lazyload_admin = (method_exists($module_obj,'LazyLoadAdmin') && $module_obj->LazyLoadAdmin())?1:0;
			$admin_only = ($module_obj->IsAdminOnly())?1:0;

			$query = 'UPDATE '.CMS_DB_PREFIX.'modules SET version = ?, active = 1, allow_fe_lazyload = ?, allow_admin_lazyload = ?, admin_only = ? WHERE module_name = ?';
//			$dbr =
			$db->Execute($query,[$to_version,$lazyload_fe,$lazyload_admin,$admin_only,$module_name]);

			// upgrade dependencies
			$query = 'DELETE FROM '.CMS_DB_PREFIX.'module_deps WHERE child_module = ?';
//			$dbr =
			$db->Execute($query,[$module_name]);

			$deps = $module_obj->GetDependencies();
			if( $deps ) {
				$now = $db->dbTimeStamp(time());
				$stmt = $db->Prepare('INSERT INTO '.CMS_DB_PREFIX."module_deps
(parent_module,child_module,minimum_version,create_date,modified_date)
VALUES (?,?,?,$now,$now)");
				foreach( $deps as $depname => $depversion ) {
					if( !$depname || !$depversion ) continue;
//					$dbr =
					$db->Execute($stmt,[$depname,$module_name,$depversion]);
				}
				$stmt->close();
			}
			$this->generate_moduleinfo( $module_obj );
			self::$_moduleinfo = [];
			global_cache::clear('modules');
			global_cache::clear('module_deps');
			global_cache::clear('module_plugins');
			global_cache::clear('module_menus');

			cms_notice('Upgraded module '.$module_name.' to version '.$module_obj->GetVersion());
			Events::SendEvent( 'Core', 'ModuleUpgraded', [ 'name' => $module_name, 'oldversion' => $dbversion, 'newversion' => $module_obj->GetVersion() ] );

			global_cache::clear('Events');
			return [TRUE];
		}

		cms_error('Upgrade failed for module '.$module_name);
		return [FALSE,$result];
	}

	/**
	 * Upgrade a module
	 *
	 * This is an internal method, subject to change in later releases.  It should never be called for upgrading arbitrary modules.
	 * Any use of this function by third party code will not be supported.  Use at your own risk and do not report bugs or issues
	 * related to your use of this module.
	 *
	 * @internal
	 * @param string $module_name The name of the module to upgrade
	 * @param string $to_version The destination version
	 * @return array, 1 or 2 members
	 *  [0] : bool whether or not the upgrade was successful
	 *  [1] if member[0] == false : string error message
	 */
	public function UpgradeModule( string $module_name, string $to_version = '')
	{
		$module_obj = $this->get_module_instance($module_name,'',TRUE);
		if( !is_object($module_obj) ) return [FALSE,lang('errormodulenotloaded')];
		return $this->_upgrade_module($module_obj,$to_version);
	}

	/**
	 * Uninstall a module
	 *
	 * @internal
	 * @param string $module_name The name of the module to remove
	 * @return array Returns a tuple of whether the uninstall was successful and a message upon error
	 */
	public function UninstallModule(string $module_name) : array
	{
		$obj = cms_utils::get_module($module_name);
		if( !$obj ) return [FALSE,lang('errormodulenotloaded')];

		$cleanup = $obj->AllowUninstallCleanup();
		$result = $obj->Uninstall(); // false | string | number != 1

		if( $result === FALSE ) { //success

			$db = CmsApp::get_instance()->GetDb();
			// now delete the record
			$db->Execute('DELETE FROM '.CMS_DB_PREFIX.'modules WHERE module_name=?',[$module_name]);

			// delete any dependencies
			$db->Execute('DELETE FROM '.CMS_DB_PREFIX.'module_deps WHERE child_module=?',[$module_name]);

			// clean up, if permitted
			if( $cleanup ) {
				$db->Execute('DELETE FROM '.CMS_DB_PREFIX.TemplateOperations::TABLENAME.' WHERE originator=?',[$module_name]);
				$db->Execute('DELETE FROM '.CMS_DB_PREFIX.'event_handlers WHERE class=? AND type="M"',[$module_name]);
				$db->Execute('DELETE FROM '.CMS_DB_PREFIX.'events WHERE originator=?',[$module_name]);

				$types = CmsLayoutTemplateType::load_all_by_originator($module_name);
				if( $types ) {
					foreach( $types as $type ) {
						$tpls = TemplateOperations::template_query(['t:'.$type->get_id()]);
						if( $tpls ) {
							foreach( $tpls as $tpl ) {
								$tpl->delete();
							}
						}
						$type->delete();
					}
				}

				$alerts = Alert::load_all();
				if( $alerts ) {
					foreach( $alerts as $alert ) {
						if( $alert->module == $module_name ) $alert->delete();
					}
				}

				$jobmgr = CmsApp::get_instance()->GetJobManager();
				if( $jobmgr ) $jobmgr->delete_jobs_by_module($module_name);

				$db->Execute('DELETE FROM '.CMS_DB_PREFIX.'module_smarty_plugins WHERE module=?',[$module_name]);
				$db->Execute('DELETE FROM '.CMS_DB_PREFIX."siteprefs WHERE sitepref_name LIKE '". str_replace("'",'',$db->qStr($module_name))."_mapi_pref%'");
				$db->Execute('DELETE FROM '.CMS_DB_PREFIX.'routes WHERE key1=?',[$module_name]);
			}

			// clear related caches
			global_cache::clear('modules');
			global_cache::clear('module_deps');
			global_cache::clear('module_plugins');
			global_cache::clear('module_menus');

			// Removing module from info
			self::$_moduleinfo = [];

			cms_notice('Uninstalled module '.$module_name);
			Events::SendEvent( 'Core', 'ModuleUninstalled', [ 'name' => $module_name ] );

			global_cache::clear('Events');
			return [TRUE,''];
		}

		cms_error('Uninstall failed: '.$module_name); //TODO lang
		if( is_numeric($result) ) $result = lang('failure');
		return [FALSE,$result];
	}

	/**
	 * Test if a module is active
	 *
	 * @param string $module_name
	 * @return bool
	 */
	public function IsModuleActive(string $module_name) : bool
	{
		if( !$module_name ) return FALSE;
		$info = $this->_get_module_info();
		if( !isset($info[$module_name]) ) return FALSE;

		return (bool)$info[$module_name]['active'];
	}

	/**
	 * Activate a module
	 *
	 * @param string $module_name
	 * @param bool $activate flag indicating whether to activate or deactivate the module
	 * @return bool
	 */
	public function ActivateModule(string $module_name,bool $activate = true)
	{
		if( !$module_name ) return FALSE;
		$info = $this->_get_module_info();
		if( !isset($info[$module_name]) ) return FALSE;

		$o_state = $info[$module_name]['active'];
		if( $activate ) {
			$info[$module_name]['active'] = 1;
		}
		else {
			$info[$module_name]['active'] = 0;
		}
		if( $info[$module_name]['active'] != $o_state ) {
			Events::SendEvent( 'Core', 'BeforeModuleActivated', [ 'name'=>$module_name, 'activated'=>$activate ] );
			$db = CmsApp::get_instance()->GetDb();
			$query = 'UPDATE '.CMS_DB_PREFIX.'modules SET active = ? WHERE module_name = ?';
//			$dbr =
			$db->Execute($query,[$info[$module_name]['active'],$module_name]);
			self::$_moduleinfo = [];
			global_cache::clear('modules'); //force refresh of the cached active property
			global_cache::clear('module_plugins');
			global_cache::clear('module_menus');
			Events::SendEvent( 'Core', 'AfterModuleActivated', [ 'name'=>$module_name, 'activated'=>$activate ] );
			if( $activate ) {
				cms_notice("Module $module_name activated"); //TODO lang
			}
			else {
				cms_notice("Module $module_name deactivated");
			}
		}
		return TRUE;
	}

	/**
	 * Returns a hash of all loaded modules.  This will include all
	 * modules loaded into memory at the current time
	 *
	 * @return mixed array | null The hash of all loaded modules
	 */
	public function GetLoadedModules()
	{
		return self::$_modules;
	}

	/**
	 * @internal
	 */
	public function is_module_loaded(string $module_name)
	{
		$module_name = trim( $module_name );
		return isset( self::$_modules[$module_name] );
	}

	/**
	 * Return an array of the names of all modules that we currently know about
	 *
	 * @return array
	 */
	public function GetAllModuleNames()
	{
		return array_keys($this->_get_module_info());
	}

	/**
	 * Return all the information we know about modules.
	 *
	 * @return array
	 */
	public function GetAllModuleInfo()
	{
		return $this->_get_module_info();
	}

	/**
	 * Return an array of the names of all installed modules.
	 *
	 * @param bool $include_all Include even inactive modules
	 * @return array
	 */
	public function GetInstalledModules(bool $include_all = FALSE)
	{
		$result = [];
		$info = $this->_get_module_info();
		if( is_array($info) ) {
			foreach( $info as $name => $rec ) {
				if( $rec['status'] != 'installed' ) continue;
				if( !$rec['active'] && !$include_all ) continue;
				$result[] = $name;
			}
		}
		return $result;
	}

	/**
	 * Return an array of installed modules that have the specified capability.
	 * This method will force the loading of all modules regardless of the module settings.
	 *
	 * @param string $capability The capability name
	 * @param mixed $args Capability arguments
	 * @return array List of all the module objects with that capability
	 */
	public static function get_modules_with_capability(string $capability, $args = null )
	{
		if( !is_array($args) ) {
			if( !empty($args) ) {
				$args = [ $args ];
			}
			else {
				$args = [];
			}
		}
		return (new module_meta())->module_list_by_capability($capability,$args);
	}

	/**
	 * @ignore
	 */
	private function _get_all_module_dependencies()
	{
		$out = global_cache::get('module_deps');
		if( $out === '-' ) return;
		return $out;
	}

	/**
	 * Return a list of dependencies from a module.
	 * This works by reading the dependencies from the database.
	 *
	 * @since 1.11.8
	 * @author Robert Campbell
	 * @param string $module_name The module name
	 * @return array Hash of module names and dependencies
	 */
	public function get_module_dependencies(string $module_name)
	{
		if( !$module_name ) return;

		$deps = $this->_get_all_module_dependencies();
		if( isset($deps[$module_name]) ) return $deps[$module_name];
	}

	/**
	 * Return a reference to a module object, if possible
	 * If the module is not already loaded, and $force is true, the module will be [re]loaded.
	 * Version checks are done with the module to allow only loading versions of
	 * modules that are greater than the specified value.
	 *
	 * @param string $module_name The module name
	 * @param string $version an optional version string.
	 * @param bool $force an optional flag to indicate whether the module should be force-loaded if necessary
	 * @return mixed CMSModule or a subclass of that | null
	 */
	public function &get_module_instance(string $module_name,string $version = '',bool $force = FALSE)
	{
		if( empty($module_name) && isset($this->variables['module'])) $module_name = $this->variables['module'];

		$obj = null;
		if( isset(self::$_modules[$module_name]) ) {
			if( $force ) {
				unset(self::$_modules[$module_name]);
			}
			else {
				$obj = self::$_modules[$module_name];
			}
		}
		if( !is_object($obj) ) {
			// gotta load it.
			$res = $this->_load_module($module_name,$force);
			if( $res ) $obj = self::$_modules[$module_name];
		}

		if( is_object($obj) && !empty($version) ) {
			$res = version_compare($obj->GetVersion(),$version);
			if( $res < 0 || $res === FALSE ) $obj = null;
		}

		return $obj;
	}

	/**
	 * Test if the specified module name is a system module
	 *
	 * @param string $module_name The module name
	 * @return bool
	 */
	public function IsSystemModule(string $module_name)
	{
		if (self::$_coremodules === null) {
			//log 'core' modules
			$names = [];
			$path = cms_join_path(CMS_ROOT_PATH,'lib','modules');
			if (is_dir($path)) {
				$patn = $path.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.'*.module.php';
				$files = glob($patn,GLOB_NOESCAPE);
				foreach ($files as $fn) {
					$names[] = basename($fn,'.module.php');
				}
			}
			global $CMS_INSTALL_PAGE;
			if (isset($CMS_INSTALL_PAGE)) {
				return in_array($module_name,$names);
			} else {
				self::$_coremodules = $names;
			}
		}
		return in_array($module_name,self::$_coremodules);
	}

	public function RegisterAdminAuthenticationModule(CMSModule $mod)
	{
		if( self::$_auth_module ) throw new LogicException( 'Sorry, only one non standard auth module is supported' );
		if( ! $mod instanceof CMSMS\IAuthModuleInterface ) throw new LogicException('Sorry. '.$mod->GetName().' is not a valid authentication module');
		self::$_auth_module = $mod;
	}

	public function GetAdminLoginModule()
	{
		if( self::$_auth_module ) return self::$_auth_module;
		return $this->get_module_instance( self::STD_AUTH_MODULE, '', TRUE );
	}

	/**
	 * Return the current syntax highlighter module object
	 *
	 * This method retrieves the specified syntax highlighter module, or uses the
	 * current user preference for the syntax hightlighter module for a name.
	 *
	 * @param mixed string|null|-1 $module_name allows bypassing the automatic detection process
	 *  and specifying a wysiwyg module.
	 * @return mixed CMSModule | null
	 * @since 1.10
	 */
	public function GetSyntaxHighlighter($module_name = null)
	{
		if( !$module_name ) {
			global $CMS_ADMIN_PAGE;
			if( isset($CMS_ADMIN_PAGE) ) $module_name = cms_userprefs::get_for_user(get_userid(FALSE),'syntaxhighlighter');
			if( $module_name ) $module_name = html_entity_decode( $module_name ); // for some reason entities may have gotten in there?
		}

		if( !$module_name || $module_name == -1 ) return;
		$obj = $this->get_module_instance($module_name);
		if( $obj && $obj->HasCapability(CmsCoreCapabilities::SYNTAX_MODULE) ) return $obj;
	}

	/**
	 * Return the current wysiwyg module object
	 *
	 * This method makes an attempt to find the appropriate wysiwyg module
	 * given the current request context and admin user preference.
	 *
	 * @param mixed string|null $module_name allows bypassing the automatic detection process
	 *  and specifying a wysiwyg module.
	 * @return mixed CMSModule | null
	 * @since 1.10
	 * @deprecated
	 */
	public function GetWYSIWYGModule($module_name = null)
	{
		if( !$module_name ) {
			if( CmsApp::get_instance()->is_frontend_request() ) {
				$module_name = cms_siteprefs::get('frontendwysiwyg');
			}
			else {
				$module_name = cms_userprefs::get_for_user(get_userid(FALSE),'wysiwyg');
			}
			if( $module_name ) $module_name = html_entity_decode($module_name);
		}

		if( !$module_name || $module_name == -1 ) return;
		$obj = $this->get_module_instance($module_name);
		if( $obj && $obj->HasCapability(CmsCoreCapabilities::WYSIWYG_MODULE) ) return $obj;
	}

	/**
	 * Return the current search module object
	 *
	 * This method returns module object for the currently selected search module.
	 *
	 * @return mixed CMSModule | null
	 * @since 1.10
	 */
	public function GetSearchModule()
	{
		$module_name = cms_siteprefs::get('searchmodule','Search');
		if( $module_name && $module_name != 'none' && $module_name != '-1' ) {
			$obj = $this->get_module_instance($module_name);
			if( $obj ) return $obj;
		}
	}

	/**
	 * Return the current filepicker module object.
	 *
	 * This method returns module object for the currently selected filepicker module.
	 *
	 * @return FilePicker
	 * @since 2.2
	 */
	public function GetFilePickerModule()
	{
		$module_name = cms_siteprefs::get('filepickermodule','FilePicker');
		if( $module_name && $module_name != 'none' && $module_name != '-1' ) {
			$obj = $this->get_module_instance($module_name);
			if( $obj ) return $obj;
		}
	}

	/**
	 * Alias for the GetSyntaxHiglighter method.
	 *
	 * @see ModuleOperations::GetSyntaxHighlighter()
	 * @deprecated
	 * @since 1.10
	 * @param mixed $module_name string | null
	 * @return CMSModule
	 */
	public function GetSyntaxModule($module_name = null)
	{
		return $this->GetSyntaxHighlighter($module_name);
	}

	/**
	 * Unload a module from memory
	 *
	 * @internal
	 * @since 1.10
	 * @param string $module_name
	 */
	public function unload_module(string $module_name)
	{
		if( isset(self::$_modules[$module_name]) && is_object(self::$_modules[$module_name]) )
			unset(self::$_modules[$module_name]);
	}

	/**
	 * Given a request and an 'id' return the parameters for the module call
	 *
	 * @internal
	 * @param string $id
	 * @return array, maybe empty
	 */
	public function GetModuleParameters(string $id) : array
	{
		$params = [];

		if( $id ) {
			$len = strlen($id);
			foreach ($_REQUEST as $key=>$value) {
				if( strncmp($key,$id,$len) == 0 ) {
					$key = substr($key,$len);
					if( $key == 'id' || $key == 'returnid' ) $value = (int)$value;
//					if( $key == 'id' || $key == 'returnid' || $key == 'action' ) continue; 2.3 deprecation, breaks lot of stuff
					$params[$key] = $value;
				}
			}
		}

		return $params;
	}
} // class

//backward-compatibility shiv
\class_alias(ModuleOperations::class, 'ModuleOperations', false);
