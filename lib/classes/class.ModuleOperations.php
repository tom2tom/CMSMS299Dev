<?php
/*
Singleton class of utility-methods for operating on and with modules
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS;

//use FilePicker; //module class
use CMSModule;
use CMSMS\AdminAlerts\Alert;
use CMSMS\AppParams;
use CMSMS\AppSingle;
use CMSMS\AppState;
use CMSMS\CoreCapabilities;
use CMSMS\DeprecationNotice;
use CMSMS\Events;
use CMSMS\IAuthModule;
use CMSMS\internal\JobOperations;
use CMSMS\IResource;
use CMSMS\TemplateOperations;
use CMSMS\TemplateType;
use CMSMS\UserParams;
use const CMS_DB_PREFIX;
use const CMS_DEPREC;
use const CMS_SCHEMA_VERSION;
use const CMS_VERSION;
use const CMSSAN_FILE;
use function cms_error;
use function cms_module_path;
use function cms_module_places;
use function cms_notice;
use function cms_warning;
use function CMSMS\de_entitize;
use function CMSMS\de_specialize;
use function CMSMS\sanitizeVal;
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
//'AdminLog,AdminSearch,CMSContentManager,CMSMailer,CmsJobManager,AdminLogin,FileManager,FilePicker,MicroTiny,ModuleManager,Navigator,Search';

	/**
	 * Name of default login-processor module
	 * @ignore
	 */
	const STD_LOGIN_MODULE = 'AdminLogin';

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
//	private static $_module_class_map;

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
	 * @deprecated since 2.99 use CMSMS\AppSingle::ModuleOperations()
	 * @return ModuleOperations
	 */
	public static function get_instance() : self
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method','CMSMS\\AppSingle::ModuleOperations()'));
		return AppSingle::ModuleOperations();
	}

	/**
	 * @ignore
	 */
	private function get_module_classmap() : array
	{
		if( !isset($this->_classmap) ) {
			$this->_classmap = [];
			$tmp = AppParams::get(self::CLASSMAP_PREF);
			if( $tmp ) $this->_classmap = unserialize($tmp);
		}
		return $this->_classmap;
	}

	/**
	 * @ignore
	 * @param string $modname
	 * @return mixed string | null
	 */
	private function get_module_classname(string $modname)
	{
		$modname = trim($modname);
		if( !$modname ) return;
		$map = $this->get_module_classmap();
		if( isset($map[$modname]) ) return $map[$modname];
		return $modname;
	}

	/**
	 * Set the classname of a module.
	 * Useful when the module class is in a namespace.
	 * This caches the alias permanently, as distinct from class_alias()
	 *
	 * @param string $modname The module name
	 * @param string $classname The class name
	 */
	public function set_module_classname(string $modname, string $classname)
	{
		$modname = trim($modname);
		$classname = trim($classname);
		if( !$modname || !$classname ) return;

		$this->get_module_classmap();
		$this->_classmap[$modname] = $classname;
		AppParams::set(self::CLASSMAP_PREF, serialize($this->_classmap));
	}

	/**
	 * @param string $modname
	 * @return mixed string | null
	 */
	public function get_module_filename(string $modname)
	{
		$modname = trim($modname);
		if( $modname ) {
			$fn = cms_module_path($modname);
			if( is_file($fn) ) return $fn;
		}
	}

	/**
	 * @param string $modname
	 * @return mixed string | null
	 */
	public function get_module_path(string $modname)
	{
		$fn = $this->get_module_filename($modname);
		if( $fn ) return dirname( $fn );
	}

	/* *
	 * Generate a moduleinfo.ini file for a module.
	 *
	 * @since 2.99
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
	 * @param CMSModule | IResource $module_obj a module object
	 */
	private function _install_module($module_obj)
	{
		$modname = $module_obj->GetName();
		debug_buffer('install_module '.$modname);

		$gCms = AppSingle::App(); // vars in scope for Install()
		$db = $gCms->GetDb();

		$result = $module_obj->Install();
		if( $result && $result !== 1 ) {
			return [FALSE,$result];
		}

		// a successful installation
		$query = 'DELETE FROM '.CMS_DB_PREFIX.'modules WHERE module_name = ?';
//		$dbr = if result-check done
		$db->Execute($query,[$modname]);
		$query = 'DELETE FROM '.CMS_DB_PREFIX.'module_deps WHERE child_module = ?';
//			$dbr =
		$db->Execute($query,[$modname]);

//		$lazyload_fe    = (method_exists($module_obj,'LazyLoadFrontend') && $module_obj->LazyLoadFrontend())?1:0;
//		$lazyload_admin = (method_exists($module_obj,'LazyLoadAdmin') && $module_obj->LazyLoadAdmin())?1:0;
		$query = 'INSERT INTO '.CMS_DB_PREFIX.'modules
(module_name,version,admin_only,active)
VALUES (?,?,?,1)';
//(module_name,version,status,admin_only,active,allow_fe_lazyload,allow_admin_lazyload)
//		$dbr =
		$db->Execute($query,[
		$modname,$module_obj->GetVersion(),
			($module_obj->IsAdminOnly()) ? 1 : 0 //,$lazyload_fe,$lazyload_admin
		]);

		$deps = $module_obj->GetDependencies();
		if( $deps ) {
			//setting create_date should be redundant with DT setting
			$stmt = $db->Prepare('INSERT INTO '.CMS_DB_PREFIX.'module_deps
(parent_module,child_module,minimum_version,create_date)
VALUES (?,?,?,NOW())');
			foreach( $deps as $depname => $depversion ) {
				if( !$depname || !$depversion ) continue;
//				$dbr =
				$db->Execute($stmt,[$depname,$modname,$depversion]);
			}
			$stmt->close();
		}
//		$this->generate_moduleinfo( $module_obj );
		$this->_moduleinfo = [];
		$cache = AppSingle::SysDataCache();
		$cache->release('modules');
		$cache->release('module_deps');
		$cache->release('module_plugins');
		$cache->release('module_menus');
		AppSingle::module_meta()->clear_cache();

		cms_notice('Installed module '.$modname.' version '.$module_obj->GetVersion());
		Events::SendEvent( 'Core', 'ModuleInstalled', [ 'name' => $modname, 'version' => $module_obj->GetVersion() ] );
		return [TRUE,$module_obj->InstallPostMessage()];
	}

	/**
	 * Install a module into the database
	 *
	 * @param string $modname The name of the module to install
	 * @return array, 1 or 2 members:
	 *  [0] = bool indicating whether the install was successful
	 *  [1] = string error message if [0] == false
	 */
	public function InstallModule(string $modname) : array
	{
		$installing = AppState::test_state(AppState::STATE_INSTALL);
		// get an instance of the class (force it)
		$modinst = $this->get_module_instance($modname,'',TRUE);
		if( $modinst ) {
			$core = $this->IsSystemModule($modname);
			if( $core ) {
				if( !$modinst->HasCapability(CoreCapabilities::CORE_MODULE) ) {
					if( !$installing ) {
						if( !isset($this->_moduleinfo[$modname]) ) {
							// undo unwanted new install
							$this->UninstallModule($modname);
						}
						cms_error('Module '.$modname.' installation failed: re-use core-module name');
						return [FALSE,'Module '.$modname.' installation failed: re-use core-module name'];
					}
					return [FALSE,lang('errorbadname')];
				}
			}

			// process any dependencies
			$deps = $modinst->GetDependencies();
			if( $deps ) {
				foreach( $deps as $mname => $mversion ) {
					if( $mname == '' || $mversion == '' ) continue; // invalid entry
					if( $core ) {
						$newmod = $this->get_module_instance($mname,'',TRUE);
						if( !is_object($newmod) || version_compare($newmod->GetVersion(),$mversion) < 0 ) {
							if( !$installing ) {
								cms_error('Module '.$modname.' installation failed: depends on '.$mname);
							}
							return [FALSE,lang('missingdependency').': '.$mname];
						}
					}
					else {
						$data = $this->_moduleinfo[$mname] ?? NULL;
						if( $data ) {
							if( version_compare($data['version'],$mversion) < 0 ) {
								if( !$installing ) {
									cms_error('Module '.$modname.' installation failed: depends on '.$mname);
								}
								return [FALSE,lang('missingdependency').': '.$mname];
							}
							$newmod = $this->get_module_instance($mname,'',TRUE); // read without installation
						}
						else {
							if( !$installing ) {
								cms_error('Module '.$modname.' installation failed: depends on '.$mname);
							}
							return [FALSE,lang('missingdependency').': '.$mname];
						}
					}
				}
			}

			// do the actual installation stuff
			if( !$core ) { // if not already done during the recent get_module_instance()
				$res = $this->_install_module($modinst);
				if( $res[0] == FALSE) {
					if( !is_string($res[1]) || $res[1] == '' ) {
						$res[1] = lang('failure'); //TODO better default message
					}
					if( !$installing ) {
						cms_error('Module '.$modname.' installation failed: '.$res[1]);
					}
				}
				return $res;
			}
			return [TRUE, ''];
		}
		else {
			if( !$installing ) {
				cms_error('Module '.$modname.' installation failed');
//				return [FALSE,lang('errormodulenotloaded')];
			}
			return [FALSE,'Module '.$modname.' installation failed'];
		}
	}

	/**
	 * @ignore
	 */
	private function _get_module_info() : array
	{
		if( !$this->_moduleinfo ) {
			$tmp = AppSingle::SysDataCache()->get('modules');
			if( is_array($tmp) ) {
				$this->_moduleinfo = [];
				foreach( $tmp as $modname => $props ) {
					//double-check that cache data are current
					$filename = $this->get_module_filename($modname);
					if( is_file($filename) ) {
						if( !isset($this->_moduleinfo[$modname]) ) {
							$this->_moduleinfo[$modname] = ['module_name'=>$modname] + $props; //,'status'=>'installed' = hack for removed table-field
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
	 * @param string $modname
	 * @param bool $force Optional flag, whether to reload the module
	 *  if already loaded. Default false.
	 * @param bool $dependents Optional flag, whether to also load
	 *  module-dependents. Default true, but always false when installer is running.
	 * @return boolean indicating success
	 */
	private function _load_module(
		string $modname,
		bool $force = FALSE,
		bool $dependents = TRUE) : bool
	{
		$info = $this->_get_module_info();
		if( !isset($info[$modname]) && !$force ) {
			cms_warning("Nothing is known about $modname... can't load it");
			return FALSE;
		}

		$installing = AppState::test_state(AppState::STATE_INSTALL);
		if( $installing ) {
			$dependents = FALSE;
		}

		$gCms = AppSingle::App(); // compatibility for some crappy old modules, deprecated since 2.99

		// okay, lessee if we can load dependents
		if( $dependents ) {
			$deps = $this->get_module_dependencies($modname);
			if( $deps ) {
				foreach( $deps as $name => $ver ) {
					if( $name == $modname ) continue; // a module cannot depend on itself.
					// this is the start of a recursive process: get_module_instance() may call _load_module().
					$modinst2 = $this->get_module_instance($name,$ver); // TODO not forced ok ?
					if( !is_object($modinst2) ) {
						cms_warning("Cannot load module $modname ... Problem loading dependent module $name version $ver");
						return FALSE;
					}
				}
			}
		}

		// now load the module itself... recurses into the autoloader if possible.
		$class_name = $this->get_module_classname($modname);
		if( !class_exists($class_name,true) ) {
			$fname = $this->get_module_filename($modname);
			if( !is_file($fname) ) {
				cms_warning("Cannot load $modname because the module file does not exist");
				return FALSE;
			}

			debug_buffer('including source for module '.$modname);
			require_once($fname);
		}

		$modinst = new $class_name();
		if( !is_object($modinst) || ! ($modinst instanceof CMSModule || $modinst instanceof IResource) ) {
			// oops, some problem loading.
			cms_error("Cannot load module $modname ... some problem instantiating the class");
			return FALSE;
		}

		if( version_compare($modinst->MinimumCMSVersion(),CMS_VERSION) == 1 ) {
			// oops, not compatible.... can't load.
			cms_error('Cannot load module '.$modname.' it is not compatible wth this version of CMSMS');
			unset($modinst);
			return FALSE;
		}

		$this->_modules[$modname] = $modinst;

		// when the installer is running, or the module is 'core', try to install/upgrade it
		if( $installing || $this->IsSystemModule($modname) ) {
			// auto-upgrade modules only if schema-version is up-to-date
			$tmp = $gCms->get_installed_schema_version(); // int from CMSMS\AppParams table, if any
			if( $tmp == CMS_SCHEMA_VERSION ) {
				if( !isset($info[$modname]) ) { //|| $info[$modname]['status'] != 'installed' ) {
					$res = $this->_install_module($modinst);
					if( !$res[0] ) {
						// nope, can't auto install...
						debug_buffer("Automatic installation of $modname failed");
						unset($modinst,$this->_modules[$modname]);
						return FALSE;
					}
				}
			}
			// otherwise, check whether an auto-upgrade is appropriate
			if( isset($info[$modname]) ) { //&& $info[$modname]['status'] == 'installed' ) {
				$dbversion = $info[$modname]['version'];
				if( version_compare($dbversion, $modinst->GetVersion()) == -1 ) {
					// looks like upgrade is needed
					$res = $this->_upgrade_module($modinst);
					if( !$res ) {
						// upgrade failed
						debug_buffer("Automatic upgrade of $modname failed");
						unset($modinst,$this->_modules[$modname]);
						return FALSE;
					}
				}
			}
		}

/*		if( !$force && false ) { //(!isset($info[$modname]['status']) || $info[$modname]['status'] != 'installed') ) {
			debug_buffer('Cannot load an uninstalled module');
			unset($modinst,$this->_modules[$modname]);
			return FALSE;
		}
*/
//		if( !($installing || AppState::test_state(CMSMS\AppState::STATE_STYLESHEET)) ) {
		if( !$installing && in_array($modname, $this->GetInstalledModules()) ) {
			if( AppState::test_state(AppState::STATE_ADMIN_PAGE) ) {
				$modinst->InitializeAdmin();
			}
			elseif( !$force ) { // CHECKME IsFontentonly() usage
				if( $gCms->is_frontend_request() ) {
					$modinst->InitializeFrontend();
				}
			}
		}

		// we're all done.
		Events::SendEvent( 'Core', 'ModuleLoaded', [ 'name' => $modname ] );
		return TRUE;
	}

	/**
	 * Remove the named module from the local cache
	 *
	 * @internal
	 * @since 1.10
	 * @param string $modname
	 */
	public function unload_module(string $modname)
	{
		if( isset($this->_modules[$modname]) &&
			is_object($this->_modules[$modname]) )
			unset($this->_modules[$modname]);
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
				while( ($file = readdir($handle)) !== false ) {
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
	 * Upgrade
	 * @ignore
	 * @access private
	 * @param CMSModule | IResource $module_obj
	 * @param string $to_version
	 * @return array
	 */
	private function _upgrade_module($module_obj, string $to_version = '') : array
	{
		// upgrade only if the database schema is up-to-date.
		$gCms = AppSingle::App();
		$tmp = $gCms->get_installed_schema_version();
		if( $tmp && $tmp < CMS_SCHEMA_VERSION ) {
			return [FALSE,lang('error_coreupgradeneeded')];
		}

		$info = $this->_get_module_info();
		$modname = $module_obj->GetName();
		$dbversion = $info[$modname]['version'];
		if( $to_version == '' ) $to_version = $module_obj->GetVersion();
		$dbversion = $info[$modname]['version'];
		if( version_compare($dbversion, $to_version) != -1 ) {
			return [TRUE,'']; // nothing to do.
		}

		$db = $gCms->GetDb();
		$result = $module_obj->Upgrade($dbversion,$to_version);
		if( $result && $result !== 1 ) {
			if( is_numeric($result) ) {
				$result = lang('failure');
			}
			$installing = AppState::test_state(AppState::STATE_INSTALL);
			if( !$installing ) { cms_error('Module '.$modname.' upgrade failed: '.$result); }
			return [FALSE,$result];
		}

		//TODO handle module re-location, if any
//		$lazyload_fe    = (method_exists($module_obj,'LazyLoadFrontend') && $module_obj->LazyLoadFrontend())?1:0;
//		$lazyload_admin = (method_exists($module_obj,'LazyLoadAdmin') && $module_obj->LazyLoadAdmin())?1:0;
		$admin_only = ($module_obj->IsAdminOnly())?1:0;

//		$query = 'UPDATE '.CMS_DB_PREFIX.'modules SET version = ?, allow_fe_lazyload = ?, allow_admin_lazyload = ?, admin_only = ?, active = 1 WHERE module_name = ?';
		$query = 'UPDATE '.CMS_DB_PREFIX.'modules SET version = ?, admin_only = ?, active = 1 WHERE module_name = ?';
//		$dbr =
//		$db->Execute($query,[$to_version,$lazyload_fe,$lazyload_admin,$admin_only,$modname]);
		$db->Execute($query,[$to_version,$admin_only,$modname]);

		// upgrade dependencies
		$query = 'DELETE FROM '.CMS_DB_PREFIX.'module_deps WHERE child_module = ?';
//		$dbr =
		$db->Execute($query,[$modname]);

		$deps = $module_obj->GetDependencies();
		if( $deps ) {
			$now = $db->dbTimeStamp(time());
			$stmt = $db->Prepare('INSERT INTO '.CMS_DB_PREFIX."module_deps
(parent_module,child_module,minimum_version,create_date)
VALUES (?,?,?,$now)");
			$stmt2 = $db->Prepare('UPDATE '.CMS_DB_PREFIX.'modules SET active=1 WHERE module_name=?');
			foreach( $deps as $depname => $depversion ) {
				if( !$depname || !$depversion ) continue;
//				$dbr =
				$db->Execute($stmt,[$depname,$modname,$depversion]);
				$db->Execute($stmt2,[$depname]);
			}
			$stmt->close();
			$stmt2->close();
		}
//		$this->generate_moduleinfo( $module_obj );
		$this->_moduleinfo = [];
		$cache = AppSingle::SysDataCache();
		$cache->release('modules');
		$cache->release('module_deps');
		$cache->release('module_plugins');
		$cache->release('module_menus');
		AppSingle::module_meta()->clear_cache();

		cms_notice('Upgraded module '.$modname.' to version '.$module_obj->GetVersion());
		Events::SendEvent( 'Core', 'ModuleUpgraded', [ 'name' => $modname, 'oldversion' => $dbversion, 'newversion' => $module_obj->GetVersion() ] );

		AppSingle::SysDataCache()->release('Events');
		return [TRUE,''];
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
	 * @param string $modname The name of the module to upgrade
	 * @param string $to_version The destination version
	 * @return array, 1 or 2 members
	 *  [0] : bool whether or not the upgrade was successful
	 *  [1] : string error message if [0] == false
	 */
	public function UpgradeModule( string $modname, string $to_version = '') : array
	{
		$modinst = $this->get_module_instance($modname,'',TRUE);
		if( is_object($modinst) ) {
			return $this->_upgrade_module($modinst,$to_version);
		}
		return [FALSE,lang('errormodulenotloaded')];

	}

	/**
	 * Uninstall a module
	 *
	 * @internal
	 * @param string $modname The name of the module to remove
	 * @return array, 1 or 2 members
	 *  [0] : bool whether or not the uninstall was successful
	 *  [1] : string error message if [0] == false
	 */
	public function UninstallModule(string $modname) : array
	{
		$modinst = $this->get_module_instance($modname);
		if( !$modinst ) return [FALSE,lang('errormodulenotloaded')];

		$cleanup = $modinst->AllowUninstallCleanup();
		$result = $modinst->Uninstall();
		if( $result && $result !== 1 ) {
			if( is_numeric($result) ) {
				$result = lang('failure');
			}
			$installing = AppState::test_state(AppState::STATE_INSTALL);
			if( !$installing ) { cms_error('Module '.$modname.' uninstall failed: '.$result); }
			return [FALSE,$result];
		}

		$db = AppSingle::Db();
		// now delete the record
		$db->Execute('DELETE FROM '.CMS_DB_PREFIX.'modules WHERE module_name=?',[$modname]);

		// delete any dependencies
		$db->Execute('DELETE FROM '.CMS_DB_PREFIX.'module_deps WHERE child_module=?',[$modname]);

		// clean up, if permitted
		if( $cleanup ) {
			$db->Execute('DELETE FROM '.CMS_DB_PREFIX.TemplateOperations::TABLENAME.' WHERE originator=?',[$modname]);
			$db->Execute('DELETE FROM '.CMS_DB_PREFIX.'event_handlers WHERE class=? AND type=\'M\'',[$modname]);
			$db->Execute('DELETE FROM '.CMS_DB_PREFIX.'events WHERE originator=?',[$modname]);

			$types = TemplateType::load_all_by_originator($modname);
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
					if( $alert->module == $modname ) $alert->delete();
				}
			}

//			$jobmgr = AppSingle::App()->GetJobManager();
//			if( $jobmgr ) $jobmgr->delete_jobs_by_module($modname);
			(new JobOperations())->unload_jobs_by_module($modname);

			$db->Execute('DELETE FROM '.CMS_DB_PREFIX.'module_smarty_plugins WHERE module=?',[$modname]);
//			$db->Execute('DELETE FROM '.CMS_DB_PREFIX.'siteprefs WHERE sitepref_name LIKE '\'. str_replace("'",'',$db->qStr($modname))."_mapi_pref%'"); TODO AppParams::NAMESPACER
			AppParams::remove($modname.AppParams::NAMESPACER, true);
			$db->Execute('DELETE FROM '.CMS_DB_PREFIX.'routes WHERE key1=?',[$modname]);
		}

		// clear related caches
		$cache = AppSingle::SysDataCache();
		$cache->release('modules');
		$cache->release('module_deps');
		$cache->release('module_plugins');
		$cache->release('module_menus');
		AppSingle::module_meta()->clear_cache();

		// Removing module from info
		$this->_moduleinfo = [];

		cms_notice('Uninstalled module '.$modname);
		Events::SendEvent( 'Core', 'ModuleUninstalled', [ 'name' => $modname ] );

		$cache->release('Events');
		return [TRUE,''];
	}

	/**
	 * Test if a module is active
	 *
	 * @param string $modname
	 * @return bool
	 */
	public function IsModuleActive(string $modname) : bool
	{
		if( !$modname ) return FALSE;
		$info = $this->_get_module_info();
		if( !isset($info[$modname]) ) return FALSE;

		return (bool)$info[$modname]['active'];
	}

	/**
	 * Activate a module
	 *
	 * @param string $modname
	 * @param bool $activate flag indicating whether to activate or deactivate the module
	 * @return bool
	 */
	public function ActivateModule(string $modname,bool $activate = TRUE) : bool
	{
		if( !$modname ) return FALSE;
		$info = $this->_get_module_info();
		if( !isset($info[$modname]) ) return FALSE;

		$o_state = $info[$modname]['active'];
		if( $activate ) {
			$info[$modname]['active'] = 1;
		}
		else {
			$info[$modname]['active'] = 0;
		}
		if( $info[$modname]['active'] != $o_state ) {
			Events::SendEvent( 'Core', 'BeforeModuleActivated', [ 'name'=>$modname, 'activated'=>$activate ] );
			$db = AppSingle::Db();
			$query = 'UPDATE '.CMS_DB_PREFIX.'modules SET active = ? WHERE module_name = ?';
//			$dbr =
			$db->Execute($query,[$info[$modname]['active'],$modname]);
			$this->_moduleinfo = [];
			$cache = AppSingle::SysDataCache();
			$cache->release('modules'); //force refresh of the cached active property
			$cache->release('module_plugins');
			$cache->release('module_menus');
			AppSingle::module_meta()->clear_cache();
			Events::SendEvent( 'Core', 'AfterModuleActivated', [ 'name'=>$modname, 'activated'=>$activate ] );
			if( $activate ) {
				cms_notice("Module $modname activated"); //TODO lang
			}
			else {
				cms_notice("Module $modname deactivated");
			}
		}
		return TRUE;
	}

	/**
	 * Initialize named modules, after loading if necessary.
	 * @since 2.99
	 * @param array $poll_modules module names
	 * @param mixed $callback Optional callable | null
	 * Processing is terminated if $callback returns false.
	 */
	public function PollModules(array $poll_modules, $callback = NULL)
	{
		$flag = AppState::test_state(AppState::STATE_ADMIN_PAGE);

		foreach( $poll_modules as $modname ) {
			if( $this->is_module_loaded($modname) ) {
				$modinst = $this->_modules[$modname];
			}
			else {
				$modinst = $this->get_module_instance($modname);
			}
			if( !$modinst ) continue;

			if( $flag ) {
				$modinst->InitializeAdmin();
			}
			else {
				$modinst->InitializeFrontend();
			}
			if( $callback ) {
				if( !$callback() ) {
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
	public function is_module_loaded(string $modname) : bool
	{
		$modname = trim( $modname );
		return isset( $this->_modules[$modname] );
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
//				if( $rec['status'] != 'installed' ) continue;
				if( !$rec['active'] && !$include_all ) continue;
				$result[] = $name;
			}
		}
		return $result;
	}

	/**
	 * Return an array of the names of all available but not-loaded modules.
	 * @since 2.99
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
	public static function get_modules_with_capability(string $capability, $args = NULL)
	{
		return (new self())->GetCapableModules($capability, $args);
	}

	/**
	 * Return the installed modules that have the specified capability.
	 * This retrieves data from cache if possible, so it does not necessarily
	 * check actual capabilities. Absent cached data, this method temporarily
	 * loads modules which are not currently loaded.
	 * @since 2.99 this is a non-static equivalent to get_modules_with_capability()
	 *
	 * @param string $capability The capability name
	 * @param mixed $args Optional CMSModule::HasCapability() arguments other than the name. Default null.
	 * @return array Names of all modules which match the given parameters
	 */
	public function GetCapableModules(string $capability, $args = NULL)
	{
		if( !is_array($args) ) {
			if( $args ) {
				$args = [ $args ];
			}
			else {
				$args = [];
			}
		}
		return AppSingle::module_meta()->module_list_by_capability($capability,$args);
	}

	/**
	 * Return the installed modules that have the specified method, and
	 * when called, that method returns the specified $returnvalue.
	 * This retrieves data from cache if possible, so it does not necessarily
	 * load modules and call their method. Absent cached data, this method
	 * temporarily loads modules which are not currently loaded.
	 * @since 2.99
	 *
	 * @param string $method The method name
	 * @param mixed $returnvalue Optional method return-value to be (non-strictly) matched.
	 *  Default self::ANY_RESULT, hence anything.
	 * @return array Names of all modules which match the given parameters
	 */
	public function GetMethodicModules(string $method, $returnvalue = self::ANY_RESULT)
	{
		return AppSingle::module_meta()->module_list_by_method($method,$returnvalue);
	}

	/**
	 * @ignore
	 */
	private function _get_all_module_dependencies()
	{
		$out = AppSingle::SysDataCache()->get('module_deps');
		if( $out !== '-' ) return $out;
	}

	/**
	 * Return a list of dependencies from a module.
	 * This works by reading the dependencies from the database.
	 *
	 * @since 1.11.8
	 * @author Robert Campbell
	 * @param string $modname The module name
	 * @return mixed array of module names and dependencies | null
	 */
	public function get_module_dependencies(string $modname)
	{
		if( !$modname ) return;

		$deps = $this->_get_all_module_dependencies();
		if( isset($deps[$modname]) ) return $deps[$modname];
	}

	/**
	 * Return a module object, if possible
	 * If the module is not already loaded, and $force is true, the module will
	 * be loaded.
	 * If a (min) version is specified, a module version check is done,
	 * to prevent loading a version that is unwanted.
	 *
	 * @param mixed string | empty $modname The module name
	 * @param string $version Optional version identifier.
	 * @param bool $force Optional flag whether to reload the module if already loaded. Default false.
	 * @return mixed CMSModule subclass | IResource | null
	 *  Since 2.99 (and PHP 5.0) : object, not an object-reference ("returning object-references is totally wasted")
	 */
	public function get_module_instance(
		$modname,
		string $version = '',
		bool $force = FALSE)
	{
		if( !$modname ) {
			if( !empty($this->variables['module']) ) {
				$modname = $this->variables['module'];
			}
			else {
				return NULL;
			}
		}

		$modinst = NULL;
		if( isset($this->_modules[$modname]) ) {
			if( $force ) {
				unset($this->_modules[$modname]);
			}
			else {
				$modinst = $this->_modules[$modname];
			}
		}
		if( !is_object($modinst) ) {
			// gotta load it, if possible (includes install for a core module).
			$res = $this->_load_module($modname,$force);
			if( $res ) { $modinst = $this->_modules[$modname]; }
		}

		if( is_object($modinst) && ($version || is_numeric($version)) ) {
			$res = version_compare($modinst->GetVersion(),$version);
			if( $res < 0 ) { $modinst = NULL; }
		}

		return $modinst;
	}

	/**
	 * Record the names of core/system modules known to the system
	 * (wherever they are stored)  NAH >> , and whatever status they currently have)
	 * We don't need to assume those modules are in any specific folder(s),
	 * and need to be polled in there
	 * @param mixed $val Optional comma-separated string | strings[] | falsy
	 */
	public function RegisterSystemModules($val = '')
	{
		if( !$val ) {
			$val = AppParams::get(self::CORENAMES_PREF);
		}
		if( $val ) {
			if( !is_array($val) ) {
				$tmp = explode(',', $val);
				$val = array_map(function($modname) {
					return trim($modname);
				}, $tmp);
			}
		}
		else {
			//TODO absolutely definite module-names could be hardcoded
			// e.g.	$val = explode(',', self::CORENAMES_DEFAULT);
			//OR do expensive, slow, probably-incomplete during installation, poll
			$gCms = AppSingle::App(); // compatibility for some crappy old modules, deprecated since 2.99
			$val = [];
			$names = $this->FindAllModules();
			foreach( $names as $modname ) {
				// we assume namespace for modules is still global
				if( !class_exists($modname) ) {
					require_once cms_module_path($onename);
				}
				$modinst = new $modname();
				if( $modinst->HasCapability(CoreCapabilities::CORE_MODULE) ) {
					$val[] = $modname;
				}
				unset($modinst);
				$modinst = NULL;
			}
		}
		sort($val, SORT_STRING);
		$this->_coremodules = $val;
	}

	/**
	 * Determine whether the specified name corresponds to a system/core module.
	 *
	 * @param string $modname The module name
	 * @return bool
	 */
	public function IsSystemModule(string $modname) : bool
	{
		if( $this->_coremodules === NULL ) {
			$this->RegisterSystemModules();
		}
		if( $this->_coremodules ) {
			$res = in_array($modname, $this->_coremodules);
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
	 * @since 2.99
	 * @param CMSModule | IResource $mod
	 * @throws LogicException
	 */
	public function RegisterAdminLoginModule($mod)
	{
		if( $this->_auth_module ) throw new LogicException('An authentication module has already been recorded for current use');
		if( ! $mod instanceof IAuthModule ) {
			throw new LogicException($mod->GetName().' is not a valid authentication module');
		}
		$this->_auth_module = $mod;
	}

	/**
	 * @since 2.99
	 * @return mixed CMSModule | IResource | null
	 */
	public function GetAdminLoginModule()
	{
		if( $this->_auth_module ) return $this->_auth_module;
		return $this->get_module_instance(self::STD_LOGIN_MODULE, '', TRUE);
	}

	/**
	 * Return a syntax highlighter module object, if possible.
	 * This method retrieves the specified syntax highlighter module,
	 * or the current user's preference for such module.
	 * @since 1.10
	 * @deprecated since 2.99. Instead, generate and place content (js etc) directly
	 *
	 * @param mixed string|null|-1 $modname allows specifying a
	 * module to be used instead of the user's recorded preference.
	 * @return mixed CMSModule | IResource | null
	 */
	public function GetSyntaxHighlighter($modname = NULL)
	{
		if( !$modname ) {
			if( AppState::test_state(AppState::STATE_ADMIN_PAGE) ) { $modname = UserParams::get_for_user(get_userid(FALSE),'syntaxhighlighter'); }
			if( $modname ) { $modname = de_entitize($modname); } // for some reason entities may have gotten in there?
		}

		if( $modname && $modname != -1 ) {
			$modname = sanitizeVal($modname, CMSSAN_FILE);
			$modinst = $this->get_module_instance($modname);
			if( $modinst && $modinst->HasCapability(CoreCapabilities::SYNTAX_MODULE) ) {
				return $modinst;
			}
		}
	}

	/**
	 * Alias for GetSyntaxHiglighter().
	 *
	 * @see ModuleOperations::GetSyntaxHighlighter()
	 * @deprecated since 2.99
	 * @since 1.10
	 * @param mixed $modname string | null
	 * @return CMSModule | IResource
	 */
	public function GetSyntaxModule($modname = NULL)
	{
		return $this->GetSyntaxHighlighter($modname);
	}

	/**
	 * Return a WYSIWYG module object, if possible.
	 * This method retrieves the specified WYSIWYG module, or the
	 * appropriate WYSIWYG module for the current request context
	 * and THE current user's preference for such module.
	 * @since 1.10
	 * @deprecated since 2.99. Instead, generate and place content (js etc) directly
	 *
	 * @param mixed string|null $modname allows bypassing the automatic detection process
	 *  and specifying a wysiwyg module.
	 * @return mixed CMSModule | IResource | null
	 */
	public function GetWYSIWYGModule($modname = NULL)
	{
		if( !$modname ) {
			if( AppSingle::App()->is_frontend_request() ) {
				$modname = AppParams::get('frontendwysiwyg');
			}
			else {
				$modname = UserParams::get_for_user(get_userid(FALSE),'wysiwyg');
			}
			if( $modname ) $modname = \html_entity_decode($modname);
		}

		if( !$modname || $modname == -1 ) return;
		$modinst = $this->get_module_instance($modname);
		if( $modinst && $modinst->HasCapability(CoreCapabilities::WYSIWYG_MODULE) ) {
			return $modinst;
		}
	}

	/**
	 * Return the currently selected search module object
	 * @since 1.10
	 *
	 * @return mixed CMSModule | IResource | null
	 */
	public function GetSearchModule()
	{
		$modname = AppParams::get('searchmodule','Search');
		if( $modname && $modname != 'none' && $modname != '-1' ) {
			$modinst = $this->get_module_instance($modname);
			if( $modinst && $modinst->HasCapability(CoreCapabilities::SEARCH_MODULE) ) {
				return $modinst;
			}
		}
	}

	/**
	 * Return the currently-selected filepicker module object, if any.
	 * @since 2.2
	 *
	 * @return mixed IFilePicker | null
	 */
	public function GetFilePickerModule()
	{
		$modname = AppParams::get('filepickermodule','FilePicker');
		if( $modname && $modname != 'none' && $modname != '-1' ) {
			$modinst = $this->get_module_instance($modname);
			if( $modinst ) return $modinst;
		}
	}

	/**
	 * Return the members of $_REQUEST[] whose key begins with $id
	 * $id is stripped from the start of returned keys.
	 * @internal
	 * @see also RequestParameters class, CMSMS\de_specialize()
	 *
	 * @param string $id module-action identifier
	 * @param bool   $clean since 2.99 optional flag whether to pass
	 *  non-numeric string-values via CMSMS\de_specialize() Default false.
	 * @param mixed $names since 2.99 optional strings array, or single,
	 *  or comma-separated series of, wanted parameter key(s)
	 * @return array, maybe empty
	 */
	public function GetModuleParameters(string $id, bool $clean = false, $names = '') : array
	{
		$params = [];

		$len = strlen($id);
		if( $len ) {
//			$raw = RequestParameters::TODO();
			if( $names ) {
				if( is_array($names) ) {
					$matches = $names;
				}
				else {
					$matches = explode(',',$names);
				}
				$matches = array_map(function($val) { return trim($val); }, $matches);
			}
			else {
				$matches = false;
			}
//			foreach( $raw as $key=>$value ) {
			foreach( $_REQUEST as $key=>$value ) {
				if( strncmp($key,$id,$len) == 0 ) {
					$key = substr($key,$len);
					if( !$matches || in_array($key, $matches) ) {
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
} // class

//backward-compatibility shiv
\class_alias(ModuleOperations::class, 'ModuleOperations', false);
