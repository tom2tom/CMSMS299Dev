<?php
#Singleton class of utility-methods for operating on and with modules
#Copyright (C) 2004-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

//use FilePicker; //module class
use cms_siteprefs;
use cms_userprefs;
use cms_utils;
use CmsApp;
use CmsCoreCapabilities;
use CmsLayoutTemplateType;
use CMSModule;
use CMSMS\AdminAlerts\Alert;
use CMSMS\IAuthModuleInterface;
use CMSMS\internal\module_meta;
use CMSMS\SysDataCache;
use CMSMS\TemplateOperations;
use DeprecationNotice;
use LogicException;
use const CMS_DB_PREFIX;
use const CMS_DEPREC;
use const CMS_SCHEMA_VERSION;
use const CMS_VERSION;
use function cms_error;
use function cms_module_path;
use function cms_module_places;
use function cms_notice;
use function cms_warning;
use function debug_buffer;
use function get_userid;
use function lang;

/**
 * A singleton class of utilities for working with modules.
 *
 * @since       0.9
 * @package     CMS
 * @license GPL
 */
final class ModuleOperations
{
	//TODO namespaced global variables here
	/**
     * Preference name for recorded module-aliases
	 * @ignore
	 */
	private const CLASSMAP_PREF = 'module_classmap';

	/**
     * Preference name for recorded core module names
	 * @ignore
	 */
	private const CORENAMES_PREF = 'coremodules';

//	const CORENAMES_DEFAULT = TODO
//'AdminLog,AdminSearch,CMSContentManager,CmsJobManager,CoreAdminLogin,FileManager,FilePicker,MicroTiny,ModuleManager,Navigator,Search';

	/**
     * Name of default login-processor module
	 * @ignore
	 */
	const STD_LOGIN_MODULE = 'CoreAdminLogin';

	/**
	 * @ignore
	 */
	const ANY_RESULT = '.*';

	/* *
	 * @ignore
	 */
//	private static $_instance = NULL;

	/**
	 * @ignore
	 */
	private $_auth_module = NULL;

	/**
	 * @var array Recorded module-class aliases
	 * @ignore
	 */
	private $_classmap;

	/* *
	 * @ignore
	 */
//    private static $_module_class_map;

	/**
	 * @var array Cached modules, each member like modname => modobject
	 * @ignore
	 */
	private $_modules = NULL;

	/**
	 * @var strings array Currently-installed core/system modules' names
	 * The population of such modules can change, so names are not hardcoded
	 * @ignore
	 */
	private $_coremodules = NULL;

	/**
	 * @var array Cached details, each member like modname => [modprops]
	 * @ignore
	 */
	private $_moduleinfo;

	/* *
	 * @ignore
	 */
//	private function __construct() {}

	/**
	 * @ignore
	 */
	private function __clone() {}

	/**
	 * Get the singleton instance of this class.
	 * @deprecated since 2.3 use CMSMS\AppSingle::ModuleOperations()
	 * @return ModuleOperations
	 */
	public static function get_instance() : self
	{
        assert(empty(CMS_DEPREC), new DeprecationNotice('method','CMSMS\AppSingle::ModuleOperations()'));
		return AppSingle::ModuleOperations();
	}

	/**
	 * @ignore
	 */
	private function get_module_classmap() : array
	{
		if( !isset($this->_classmap) ) {
			$this->_classmap = [];
			$tmp = cms_siteprefs::get(self::CLASSMAP_PREF);
			if( $tmp ) $this->_classmap = unserialize($tmp);
		}
		return $this->_classmap;
	}

	/**
	 * @ignore
	 * @param string $module_name
	 * @return mixed string | null
	 */
	private function get_module_classname(string $module_name)
	{
		$module_name = trim($module_name);
		if( !$module_name ) return;
		$map = $this->get_module_classmap();
		if( isset($map[$module_name]) ) return $map[$module_name];
		return $module_name;
	}

	/**
	 * Set the classname of a module.
	 * Useful when the module class is in a namespace.
	 * This caches the alias permanently, as distinct from class_alias()
	 *
	 * @param string $module_name The module name
	 * @param string $classname The class name
	 */
	public function set_module_classname(string $module_name, string $classname)
	{
		$module_name = trim($module_name);
		$classname = trim($classname);
		if( !$module_name || !$classname ) return;

		$this->get_module_classmap();
		$this->_classmap[$module_name] = $classname;
		cms_siteprefs::set(self::CLASSMAP_PREF, serialize($this->_classmap));
	}

	/**
	 * @param string $module_name
	 * @return mixed string | null
	 */
	public function get_module_filename(string $module_name)
	{
		$module_name = trim($module_name);
		if( $module_name ) {
			$fn = cms_module_path($module_name);
			if( is_file($fn) ) return $fn;
		}
	}

	/**
	 * @param string $module_name
	 * @return mixed string | null
	 */
	public function get_module_path(string $module_name)
	{
		$fn = $this->get_module_filename($module_name);
		if( $fn ) return dirname( $fn );
	}

	/* *
	 * Generate a moduleinfo.ini file for a module.
	 *
	 * @since 2.3
	 * @param CMSModule $obj a loaded-module object
	 */
/*	public function generate_moduleinfo(CMSModule $obj)
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
*/
	/**
	 * @ignore
	 * @param CMSModule $module_obj a module object
	 */
	private function _install_module(CMSModule $module_obj)
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

//			$lazyload_fe    = (method_exists($module_obj,'LazyLoadFrontend') && $module_obj->LazyLoadFrontend())?1:0;
//			$lazyload_admin = (method_exists($module_obj,'LazyLoadAdmin') && $module_obj->LazyLoadAdmin())?1:0;
			$query = 'INSERT INTO '.CMS_DB_PREFIX.'modules
(module_name,version,status,admin_only,active)
VALUES (?,?,?,?,?)';
//(module_name,version,status,admin_only,active,allow_fe_lazyload,allow_admin_lazyload)
//			$dbr =
			$db->Execute($query,[
			$module_name,$module_obj->GetVersion(),'installed',
				($module_obj->IsAdminOnly()) ? 1 : 0,1//,$lazyload_fe,$lazyload_admin
			]);

			$deps = $module_obj->GetDependencies();
			if( $deps ) {
				//setting create_date should be redundant with DT setting
				$stmt = $db->Prepare('INSERT INTO '.CMS_DB_PREFIX.'module_deps
(parent_module,child_module,minimum_version,create_date)
VALUES (?,?,?,NOW())');
				foreach( $deps as $depname => $depversion ) {
					if( !$depname || !$depversion ) continue;
//					$dbr =
					$db->Execute($stmt,[$depname,$module_name,$depversion]);
				}
				$stmt->close();
			}
//			$this->generate_moduleinfo( $module_obj );
			$this->_moduleinfo = [];
			$cache = SysDataCache::get_instance();
			$cache->release('modules');
			$cache->release('module_deps');
			$cache->release('module_plugins');
			$cache->release('module_menus');
			module_meta::get_instance()->clear_cache();

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
	 * @return array, 1 or 2 members:
	 *  [0] = bool whether or not the install was successful
	 *  [1] = string error message if [0] == false
	 */
	public function InstallModule(string $module_name) : array
	{
		// get an instance of the class (force it).
		$obj = $this->get_module_instance($module_name,'',TRUE);
		if( !$obj ) {
			cms_error('Installation of module '.$module_name.' failed');
			return [FALSE,lang('errormodulenotloaded')];
		}

		$names = $this->_list_system_modules();
		if( $names && in_array($module_name,$names) ) {
			if( !$obj->HasCapability(CmsCoreCapabilities::CORE_MODULE) ) {
				cms_error('Installation of module '.$module_name.' failed: re-use core-module name');
				return [FALSE,lang('errorbadname')];
			}
		}

		// check for dependencies
		$deps = $obj->GetDependencies();
		if( $deps ) {
			foreach( $deps as $mname => $mversion ) {
				if( $mname == '' || $mversion == '' ) continue; // invalid entry.
				$newmod = $this->get_module_instance($mname);
				if( !is_object($newmod) || version_compare($newmod->GetVersion(),$mversion) < 0 ) {
					cms_error('Installation of module '.$module_name.' failed: depends on '.$mname);
					return [FALSE,lang('missingdependency').': '.$mname];
				}
			}
		}

		// do the actual installation stuff.
		$res = $this->_install_module($obj);
		if( $res[0] == FALSE && $res[1] == '') {
			$res[1] = lang('errorinstallfailed');
			cms_error('Installation of module '.$module_name.' failed');
		}
		return $res;
	}

	/**
	 * @ignore
	 */
	private function _get_module_info() : array
	{
		if( !$this->_moduleinfo ) {
			$tmp = SysDataCache::get_instance()->get('modules');
			if( is_array($tmp) ) {
				$this->_moduleinfo = [];
				foreach( $tmp as $module_name => $props ) {
					//double-check that cache data are current
					$filename = $this->get_module_filename($module_name);
					if( is_file($filename) ) {
						if( !isset($this->_moduleinfo[$module_name]) ) {
							$this->_moduleinfo[$module_name] = ['module_name'=>$module_name] + $props;
						}
					}
				}

				$all_deps = $this->_get_all_module_dependencies();
				if( $all_deps && count($all_deps) ) {
					foreach( $all_deps as $mname => $deps ) {
						if( is_array($deps) && count($deps) && isset($this->_moduleinfo[$mname]) ) {
							$minfo =& $this->_moduleinfo[$mname];
							$minfo['dependants'] = array_keys($deps);
						}
					}
				}
			}
		}

		return $this->_moduleinfo;
	}

	/**
	 * @internal
	 * @param string $module_name
	 * @param bool $force Optional flag, whether to reload the module
	 *  if already loaded. Default false.
	 * @param bool $dependents Optional flag, whether to also load
	 *  module-dependants. Default true, but always false when installer is running.
	 * @return boolean indicating success
	 */
	private function _load_module(
		string $module_name,
		bool $force = FALSE,
		bool $dependents = TRUE) : bool
	{
		$info = $this->_get_module_info();
		if( !isset($info[$module_name]) && !$force ) {
			cms_warning("Nothing is known about $module_name... can't load it");
			return FALSE;
		}

		$installing = AppState::test_state(AppState::STATE_INSTALL);
		if( $installing ) {
			$dependents = FALSE;
		}

		$gCms = CmsApp::get_instance(); // compatibility for some crappy old modules, deprecated since 2.9

		// okay, lessee if we can load the dependants
		if( $dependents ) {
			$deps = $this->get_module_dependencies($module_name);
			if( $deps ) {
				foreach( $deps as $name => $ver ) {
					if( $name == $module_name ) continue; // a module cannot depend on itself.
					// this is the start of a recursive process: get_module_instance() may call _load_module().
					$obj2 = $this->get_module_instance($name,$ver); // including not $forced
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

		$this->_modules[$module_name] = $obj;

		// when the installer is running, or the module is 'core', try to install/upgrade it
		if( $installing || $this->IsSystemModule($module_name) ) {
			// auto-upgrade modules only if schema-version is up-to-date
			$tmp = $gCms->get_installed_schema_version(); // int from cms_siteprefs table, if any
			if( $tmp == CMS_SCHEMA_VERSION ) {
				if( !isset($info[$module_name]) || $info[$module_name]['status'] != 'installed' ) {
					$res = $this->_install_module($obj);
					if( !$res[0] ) {
						// nope, can't auto install...
						debug_buffer("Automatic installation of $module_name failed");
						unset($obj,$this->_modules[$module_name]);
						return FALSE;
					}
				}
			}
			// otherwise, check whether an auto-upgrade is appropriate
			if( isset($info[$module_name]) && $info[$module_name]['status'] == 'installed' ) {
				$dbversion = $info[$module_name]['version'];
				if( version_compare($dbversion, $obj->GetVersion()) == -1 ) {
					// looks like upgrade is needed
					$res = $this->_upgrade_module($obj);
					if( !$res ) {
						// upgrade failed
						debug_buffer("Automatic upgrade of $module_name failed");
						unset($obj,$this->_modules[$module_name]);
						return FALSE;
					}
				}
			}
		}

		if( !$force && (!isset($info[$module_name]['status']) || $info[$module_name]['status'] != 'installed') ) {
			debug_buffer('Cannot load an uninstalled module');
			unset($obj,$this->_modules[$module_name]);
			return FALSE;
		}

//		if( !($installing || AppState::test_state(CMSMS\AppState::STATE_STYLESHEET)) ) {
		if( !$installing ) {
			if( AppState::test_state(AppState::STATE_ADMIN_PAGE) ) {
				$obj->InitializeAdmin();
			}
			elseif( !$force ) { // CHECKME
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
	public function FindAllModules() : array
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

		sort($result, SORT_STRING);
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
	private function _upgrade_module( CMSModule &$module_obj, string $to_version = '' ) : array
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
//			$lazyload_fe    = (method_exists($module_obj,'LazyLoadFrontend') && $module_obj->LazyLoadFrontend())?1:0;
//			$lazyload_admin = (method_exists($module_obj,'LazyLoadAdmin') && $module_obj->LazyLoadAdmin())?1:0;
			$admin_only = ($module_obj->IsAdminOnly())?1:0;

//			$query = 'UPDATE '.CMS_DB_PREFIX.'modules SET version = ?, active = 1, allow_fe_lazyload = ?, allow_admin_lazyload = ?, admin_only = ? WHERE module_name = ?';
			$query = 'UPDATE '.CMS_DB_PREFIX.'modules SET version = ?, active = 1, admin_only = ? WHERE module_name = ?';
//			$dbr =
//			$db->Execute($query,[$to_version,$lazyload_fe,$lazyload_admin,$admin_only,$module_name]);
			$db->Execute($query,[$to_version,$admin_only,$module_name]);

			// upgrade dependencies
			$query = 'DELETE FROM '.CMS_DB_PREFIX.'module_deps WHERE child_module = ?';
//			$dbr =
			$db->Execute($query,[$module_name]);

			$deps = $module_obj->GetDependencies();
			if( $deps ) {
				$now = $db->dbTimeStamp(time());
				$stmt = $db->Prepare('INSERT INTO '.CMS_DB_PREFIX."module_deps
(parent_module,child_module,minimum_version,create_date)
VALUES (?,?,?,$now)");
				foreach( $deps as $depname => $depversion ) {
					if( !$depname || !$depversion ) continue;
//					$dbr =
					$db->Execute($stmt,[$depname,$module_name,$depversion]);
				}
				$stmt->close();
			}
//			$this->generate_moduleinfo( $module_obj );
			$this->_moduleinfo = [];
			$cache = SysDataCache::get_instance();
			$cache->release('modules');
			$cache->release('module_deps');
			$cache->release('module_plugins');
			$cache->release('module_menus');
			module_meta::get_instance()->clear_cache();

			cms_notice('Upgraded module '.$module_name.' to version '.$module_obj->GetVersion());
			Events::SendEvent( 'Core', 'ModuleUpgraded', [ 'name' => $module_name, 'oldversion' => $dbversion, 'newversion' => $module_obj->GetVersion() ] );

			SysDataCache::get_instance()->release('Events');
			return [TRUE];
		}

		cms_error('Upgrade failed for module '.$module_name);
		return [FALSE,$result];
	}

	/**
	 * Upgrade a module
	 *
	 * This is an internal method, subject to change in later releases.
	 * It should never be called for upgrading arbitrary modules.
	 * Use of this function by any third party is not supported.
	 * Use at your own risk and do not report bugs or issues related to your use of this module.
	 *
	 * @internal
	 * @param string $module_name The name of the module to upgrade
	 * @param string $to_version The destination version
	 * @return array, 1 or 2 members
	 *  [0] : bool whether or not the upgrade was successful
	 *  [1] : string error message if [0] == false
	 */
	public function UpgradeModule( string $module_name, string $to_version = '') : array
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
	 * @return array, 1 or 2 members
	 *  [0] : bool whether or not the uninstall was successful
	 *  [1] : string error message if [0] == false
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
			$cache = SysDataCache::get_instance();
			$cache->release('modules');
			$cache->release('module_deps');
			$cache->release('module_plugins');
			$cache->release('module_menus');
			module_meta::get_instance()->clear_cache();

			// Removing module from info
			$this->_moduleinfo = [];

			cms_notice('Uninstalled module '.$module_name);
			Events::SendEvent( 'Core', 'ModuleUninstalled', [ 'name' => $module_name ] );

			$cache->release('Events');
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
	public function ActivateModule(string $module_name,bool $activate = TRUE) : bool
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
			$this->_moduleinfo = [];
			$cache = SysDataCache::get_instance();
			$cache->release('modules'); //force refresh of the cached active property
			$cache->release('module_plugins');
			$cache->release('module_menus');
			module_meta::get_instance()->clear_cache();
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
	 * Initialize named modules, after loading if necessary.
	 * @since 2.3
	 * @param array $poll_modules module names
	 * @param mixed $callback Optional callable | null
	 * Processing is terminated if $callback returns false.
	 */
	public function PollModules(array $poll_modules, $callback = NULL)
	{
		$flag = AppState::test_state(AppState::STATE_ADMIN_PAGE);

		foreach( $poll_modules as $module_name ) {
			if( !$this->is_module_loaded($module_name) ) {
				$module_obj = $this->get_module_instance($module_name);
			}
			else {
				$module_obj = $this->_modules[$module_name];
			}
			if( !$module_obj ) continue;

			if( $flag ) {
				$module_obj->InitializeAdmin();
			}
			else {
				$module_obj->InitializeFrontend();
			}
			if( $callback ) {
				if( !$callbak() ) {
					return;
				}
			}
		}
	}

	/**
	 * Return all currently-loaded modules.
	 *
	 * @return mixed array | null Each array member like modname => modobject
	 */
	public function GetLoadedModules()
	{
		return $this->_modules;
	}

	/**
	 * @internal
	 */
	public function is_module_loaded(string $module_name) : bool
	{
		$module_name = trim( $module_name );
		return isset( $this->_modules[$module_name] );
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
	public function GetInstalledModules(bool $include_all = FALSE) : array
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
	 * Return an array of the names of all available but not-loaded modules.
	 * @since 2.3
	 *
	 * @return array maybe empty
	 */
	public function GetLoadableModuleNames() : array
	{
		return array_diff($this->GetInstalledModules(), array_keys($this->_modules));
	}

	/**
	 * Convenience static version of GetCapableModules()
	 * @see CMSModule::GetCapableModules()
	 *
	 * @param string $capability The capability name
	 * @param mixed $args Optional CMSModule::HasCapability() arguments other than the name
	 * @return array Names of all modules which match the given parameters
	 */
	public static function get_modules_with_capability(string $capability, $args = NULL )
	{
		return (new self())->GetCapableModules($capability, $args);
	}

	/**
	 * Return the installed modules that have the specified capability.
	 * This retrieves data from cache if possible, so it does not necessarily
	 * check actual capabilities. Absent cached data, this method temporarily
	 * loads modules which are not currently loaded.
	 * @since 2.3 this is a non-static equivalent to get_modules_with_capability()
	 *
	 * @param string $capability The capability name
	 * @param mixed $args Optional CMSModule::HasCapability() arguments other than the name. Default null.
	 * @return array Names of all modules which match the given parameters
	 */
	public function GetCapableModules(string $capability, $args = NULL)
	{
		if( !is_array($args) ) {
			if( !empty($args) ) {
				$args = [ $args ];
			}
			else {
				$args = [];
			}
		}
		return module_meta::get_instance()->module_list_by_capability($capability,$args);
	}

	/**
	 * Return the installed modules that have the specified method, and
	 * when called, that method returns the specified returnvalue.
	 * This retrieves data from cache if possible, so it does not necessarily
	 * load modules and call their method. Absent cached data, this method
	 * temporarily loads modules which are not currently loaded.
	 * @since 2.3
	 *
	 * @param string $method The method name
	 * @param mixed $returnvalue Optional method return-value to be (non-strictly) matched.
	 *  Default self::ANY_RESULT, hence anything.
	 * @return array Names of all modules which match the given parameters
	 */
	public function GetMethodicModules(string $method, $returnvalue = self::ANY_RESULT)
	{
		return module_meta::get_instance()->module_list_by_method($method,$returnvalue);
	}

	/**
	 * @ignore
	 */
	private function _get_all_module_dependencies()
	{
		$out = SysDataCache::get_instance()->get('module_deps');
		if( $out !== '-' ) return $out;
	}

	/**
	 * Return a list of dependencies from a module.
	 * This works by reading the dependencies from the database.
	 *
	 * @since 1.11.8
	 * @author Robert Campbell
	 * @param string $module_name The module name
	 * @return mixed array of module names and dependencies | null
	 */
	public function get_module_dependencies(string $module_name)
	{
		if( !$module_name ) return;

		$deps = $this->_get_all_module_dependencies();
		if( isset($deps[$module_name]) ) return $deps[$module_name];
	}

	/**
	 * Return a module object, if possible
	 * If the module is not already loaded, and $force is true, the module will be [re]loaded.
	 * Version checks are done with the module to allow only loading versions of
	 * modules that are greater than the specified value.
	 *
	 * @param mixed string | empty $module_name The module name
	 * @param string $version Optional version identifier.
	 * @param bool $force Optional flag whether to reload the module if already loaded. Default false.
	 * @return mixed CMSModule subclass | null
	 *  Since 2.9 (and PHP 5.0) : object, not an object-reference ("returning object-references is totally wasted")
	 */
	public function get_module_instance(
		$module_name,
		string $version = '',
		bool $force = FALSE)
	{
		if( empty($module_name) ) {
			if( !empty($this->variables['module']) ) {
				$module_name = $this->variables['module'];
			}
			else {
				return NULL;
			}
		}

		$obj = NULL;
		if( isset($this->_modules[$module_name]) ) {
			if( $force ) {
				unset($this->_modules[$module_name]);
			}
			else {
				$obj = $this->_modules[$module_name];
			}
		}
		if( !is_object($obj) ) {
			// gotta load it.
			$res = $this->_load_module($module_name, $force);
			if( $res ) { $obj = $this->_modules[$module_name]; }
		}

		if( is_object($obj) && !empty($version) ) {
			$res = version_compare($obj->GetVersion(),$version);
			if( $res < 0 || $res === FALSE ) { $obj = NULL; }
		}

		return $obj;
	}

	/**
	 * Record the names of core/system modules known to the system
	 * (wherever they are stored, and whatever status they currently have)
	 * We don't need to assume those modules are in any specific folder(s),
	 * and need to be polled in there
	 * @param mixed $val Optional comma-separated string | strings[] | falsy
	 */
	public function RegisterSystemModules($val = '')
	{
		if( !$val ) {
			$val = cms_siteprefs::get(self::CORENAMES_PREF);
		}
		if( $val ) {
			if( !is_array($val) ) {
				$tmp = explode(',', $val);
				$val = array_map(function($module_name) {
					return trim($module_name);
				}, $tmp);
			}
		}
		else {
			//TODO absolutely definite module-names could be hardcoded
			// e.g.	$val = explode(',', self::CORENAMES_DEFAULT);
			//OR do expensive, slow, probably-incomplete during installation, poll
			$gCms = CmsApp::get_instance(); // compatibility for some crappy old modules, deprecated since 2.9
			$val = [];
			$names = $this->FindAllModules();
			foreach( $names as $module_name ) {
				// we assume namespace for modules is still global
				if( !class_exists($module_name) ) {
					require_once cms_module_path($onename);
				}
				$obj = new $module_name();
				if( $obj->HasCapability(CmsCoreCapabilities::CORE_MODULE) ) {
					$val[] = $module_name;
				}
				unset($obj);
				$obj = NULL;
			}
		}
		sort($val, SORT_STRING);
		$this->_coremodules = $val;
	}

	/**
	 * Determine whether the specified name corresponds to a system/core module.
	 *
	 * @param string $module_name The module name
	 * @return bool
	 */
	public function IsSystemModule(string $module_name) : bool
	{
		if( $this->_coremodules === NULL ) {
			$this->RegisterSystemModules();
		}
		if( $this->_coremodules ) {
			$res = in_array($module_name, $this->_coremodules);
			if( AppState::test_state(AppState::STATE_INSTALL) ) {
				//revert the modules-list, in case they change during install
				$this->_coremodules = NULL;
			}
			return $res;
		}
		return FALSE;
	}

	/**
	 * Record the (non-default) login module to be used from now
	 * @since 2.3
	 * @param CMSModule $mod
	 * @throws LogicException
	 */
	public function RegisterAdminLoginModule(CMSModule $mod)
	{
		if( $this->_auth_module ) throw new LogicException('An authentication module has already been recorded for current use');
		if( ! $mod instanceof IAuthModuleInterface ) {
			throw new LogicException($mod->GetName().' is not a valid authentication module');
		}
		$this->_auth_module = $mod;
	}

	/**
	 * @since 2.3
	 * @return mixed CMSModule | null
	 */
	public function GetAdminLoginModule()
	{
		if( $this->_auth_module ) return $this->_auth_module;
		return $this->get_module_instance( self::STD_LOGIN_MODULE, '', TRUE );
	}

	/**
	 * Return a syntax highlighter module object, if possible.
	 * This method retrieves the specified syntax highlighter module,
     * or the current user's preference for such module.
	 * @since 1.10
	 * @deprecated since 2.3. Instead, generate and place content (js etc) directly
	 *
	 * @param mixed string|null|-1 $module_name allows specifying a
     * module to be used instead of the user's recorded preference.
	 * @return mixed CMSModule | null
	 */
	public function GetSyntaxHighlighter($module_name = NULL)
	{
		if( !$module_name ) {
			if( AppState::test_state(AppState::STATE_ADMIN_PAGE) ) $module_name = cms_userprefs::get_for_user(get_userid(FALSE),'syntaxhighlighter');
			if( $module_name ) $module_name = html_entity_decode( $module_name ); // for some reason entities may have gotten in there?
		}

		if( $module_name && $module_name != -1 ) {
			$obj = $this->get_module_instance($module_name);
			if( $obj && $obj->HasCapability(CmsCoreCapabilities::SYNTAX_MODULE) ) {
				return $obj;
			}
		}
	}

	/**
	 * Alias for GetSyntaxHiglighter().
	 *
	 * @see ModuleOperations::GetSyntaxHighlighter()
	 * @deprecated since 2.3
	 * @since 1.10
	 * @param mixed $module_name string | null
	 * @return CMSModule
	 */
	public function GetSyntaxModule($module_name = NULL)
	{
		return $this->GetSyntaxHighlighter($module_name);
	}

	/**
	 * Return a WYSIWYG module object, if possible.
	 * This method retrieves the specified WYSIWYG module, or the
	 * appropriate WYSIWYG module for the current request context
	 * and THE current user's preference for such module.
	 * @since 1.10
	 * @deprecated since 2.3. Instead, generate and place content (js etc) directly
	 *
	 * @param mixed string|null $module_name allows bypassing the automatic detection process
	 *  and specifying a wysiwyg module.
	 * @return mixed CMSModule | null
	 */
	public function GetWYSIWYGModule($module_name = NULL)
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
	 * Return the currently selected search module object
	 * @since 1.10
	 *
	 * @return mixed CMSModule | null
	 */
	public function GetSearchModule()
	{
		$module_name = cms_siteprefs::get('searchmodule','Search');
		if( $module_name && $module_name != 'none' && $module_name != '-1' ) {
			$obj = $this->get_module_instance($module_name);
			if( $obj && $obj->HasCapability(CmsCoreCapabilities::SEARCH_MODULE) ) return $obj;
		}
	}

	/**
	 * Return the currently-selected filepicker module object, if any.
	 * @since 2.2
	 *
	 * @return mixed FilePicker | null
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
	 * Remove the named module from the local cache
	 *
	 * @internal
	 * @since 1.10
	 * @param string $module_name
	 */
	public function unload_module(string $module_name)
	{
		if( isset($this->_modules[$module_name]) &&
			is_object($this->_modules[$module_name]) )
			unset($this->_modules[$module_name]);
	}

	/**
	 * Return the members of $_REQUEST[] whose key begins with $id (any case)
	 * $id is stripped from the start of returned keys.
	 * Values of parameters 'id', 'returnid' are cast to int i.e. adminish
	 * values '',null become 0
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
