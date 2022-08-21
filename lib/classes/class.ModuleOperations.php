<?php
/*
Singleton class of utility-methods for operating on and with modules
Copyright (C) 2004-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS;

//use const CMS_SCHEMA_VERSION;
//use CMSMS\Crypto;
//use CMSMS\SystemCache;
//use ReflectionMethod;
//use function cmsms;
use CMSModule;
use CMSMS\AdminAlerts\Alert;
use CMSMS\AppParams;
use CMSMS\AppState;
use CMSMS\ArrayTree;
use CMSMS\CapabilityType;
use CMSMS\DeprecationNotice;
use CMSMS\Events;
use CMSMS\IAuthModule;
use CMSMS\internal\JobOperations;
use CMSMS\IResource;
use CMSMS\LoadedDataType;
use CMSMS\Lone;
use CMSMS\ModuleOperations;
use CMSMS\RequestParameters;
use CMSMS\TemplateOperations;
use CMSMS\TemplateType;
use CMSMS\UserParams;
use LogicException;
use RuntimeException;
use UnexpectedValueException;
use const CMS_DB_PREFIX;
use const CMS_DEPREC;
use const CMS_VERSION;
use const CMSSAN_FILE;
use function cms_module_path;
use function cms_module_places;
use function CMSMS\de_entitize;
use function CMSMS\is_frontend_request;
use function CMSMS\log_error;
use function CMSMS\log_notice;
use function CMSMS\log_warning;
use function CMSMS\sanitizeVal;
use function CMSMS\schema_is_current;
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
	/**
	 * AppParams key for recorded 'permanent' module-class-aliases
	 * Dodgy workaround. Custom module-namespaces should be properly dealt with
	 * @ignore
	 */
	private const CLASSMAP_PREF = 'module_classmap';

	/**
	 * AppParams key for recorded core-module names
	 * @ignore
	 */
	private const CORENAMES_PREF = 'coremodules';

//	const CORENAMES_DEFAULT = TODO
//'Authenticator,AdminSearch,ContentManager,FileManager,FilePicker,MicroTiny,ModuleManager,Navigator,OutMailer,Search';

	/**
	 * Name of default login-processor module
	 * @ignore
	 */
	const STD_LOGIN_MODULE = 'Authenticator';

	/**
	 * @ignore
	 * @deprecated since 3.0 Instead use LoadedMetadata::ANY_RESULT
	 */
	const ANY_RESULT = '.*';

	/* *
	 * @ignore
	 */
//	private static $_instance = NULL;

	/**
	 * @ignore
	 */
	private $auth_module = NULL;

	/**
	 * @var array Recorded module-class aliases
	 * Initially unset to trigger loading upon 1st request
	 * @todo the autoloader should make use of this map via get_module_classname()
	 * @ignore
	 */
	private $classmap;

	/**
	 * @var string
	 * @ignore
	 */
	private $modulespace = '\\'; //modules in global namespace

	/* *
	 * @ignore
	 */
//	private static $module_class_map;

	/**
	 * @var strings array Currently-installed core/system modules' names
	 * The population of such modules can change, so names are not hardcoded
	 * @ignore
	 */
//abandoned	private $coremodules = [];
	/**
	 * @var string comma-separated names of modules supplied by/with
	 * the installer, and recorded at self::CORENAMES_PREF
	 */
	private $corenames;

	/**
	 * @var array Loaded modules cache, each member like modname => modobject
	 * Populated via self::get_module_instance() >> self::_get_module()
	 * @ignore
	 */
	private $modules = [];

	/**
	 * @var array Installed-module properties cache, each member like modname => [props]
	 * Unset triggers initialization
	 * @ignore
	 */
	private $moduleinfo;

	/* *
	 * @var string name of module currently 'running the show' during init
	 * One of the keys in $modules[], maybe also in
	 *  Lone::get('LoadedData')->get('module_depstree') etc
	 * @ignore
	 */
//	private $currentparent;

	/**
	 * @var bool whether to (somewhat) restrict possibly recursive
	 * engagement with dependencies etc
	 * However, module ->Install(), ->Upgrade(), ->Uninstall() might
	 * trigger recursion anyway ...
	 * @ignore
	 */
	private $polling = false;

	/**
	 * @var bool whether an installer-session is currently running
	 * Some system-capabilities (e.g. language-support) are N/A when so
	 * And things here generally operate as if $polling == $installing
	 *
	 * @ignore
	 */
	private $installing = false;

	/**
	 * @ignore
	 */
	public function __construct()
	{
		$this->installing = AppState::test(AppState::INSTALL);
	}

	/**
	 * @ignore
	 */
	#[\ReturnTypeWillChange]
	private function __clone() {}// : void {}

	/**
	 * Get the singleton instance of this class.
	 * @deprecated since 3.0 use CMSMS\Lone::get('ModuleOperations')
	 * @return ModuleOperations
	 */
	public static function get_instance() : self
	{
		assert(empty(CMS_DEPREC),new DeprecationNotice('method','CMSMS\Lone::get(\'ModuleOperations\')'));
		return Lone::get('ModuleOperations');
	}

	/**
	 * Initialize LoadedData-caching for 'modules', 'module_deps' and 'module_depstree'
	 * @since 3.0
	 */
	public static function load_setup()
	{
		$cache = Lone::get('LoadedData');
		// see also JobOperations::refresh_jobs()
		$obj = new LoadedDataType('modules',function() {
			$db = Lone::get('Db');
			$query = 'SELECT * FROM '.CMS_DB_PREFIX.'modules';
			return $db->getAssoc($query); // keyed by module_name
		});
		$cache->add_type($obj);
		// the dependencies flat list
		$obj = new LoadedDataType('module_deps',function() {
			$db = Lone::get('Db');
			$query = 'SELECT parent_module,child_module,minimum_version FROM '.CMS_DB_PREFIX.'module_deps ORDER BY parent_module';
			$data = $db->getArray($query);
			if (!$data || !is_array($data)) { return '-'; } // something non-empty, for cache storage
			$out = [];
			foreach ($data as $row) {
				$out[$row['child_module']][$row['parent_module']] = $row['minimum_version'];
			}
			return $out;
		});
		$cache->add_type($obj);
		// hence the dependencies tree
		$obj = new LoadedDataType('module_depstree',function(bool $force) {
			$flatlist = Lone::get('LoadedData')->get('module_deps',$force);
			if ($flatlist !== '-') {
				$items = [['name' => 'all','version' => '','parent' => NULL]];
				foreach ($flatlist as $child => $row) {
					$vers = current($row);
					$parent = key($row);
					$items[] = ['name' => $parent,'version' => $vers,'parent' => 'all'];
					$items[] = ['name' => $child,'version' => $vers,'parent' => $parent];
				}
				$unique = array_map('unserialize',array_unique(array_map('serialize',$items)));
				return ArrayTree::load_array($unique);
			}
			return '-';
		});
		$cache->add_type($obj);
	}

	/**
	 * @ignore
	 */
	private function get_module_classmap() : array
	{
		if( !isset($this->classmap) ) {
			$this->classmap = [];
			$val = AppParams::get(self::CLASSMAP_PREF);
			if( $val ) { $this->classmap = unserialize($val); }
			else { $this->classmap = []; }
		}
		return $this->classmap;
	}

	/**
	 * Get the classname corresponding to the named module.
	 * It might differ from what's expected for $modname
	 * e.g. an alias or have a specific namespace.
	 * @since 3.0 public access
	 *
	 * @param string $modname
	 * @return mixed string | null if $modname is empty
	 */
	public function get_module_classname(string $modname)
	{
		$modname = trim($modname);
		if( $modname ) {
			$map = $this->get_module_classmap();
			if( $map && isset($map[$modname]) ) { return $map[$modname]; }
			return $this->modulespace.$modname;
		}
	}

	/**
	 * Cache a 'permanent' alias for a module class.
	 * This records an alias which will be used until it's altered in
	 * or removed from AppParams data, as distinct from class_alias().
	 * Useful e.g. for back-compatibility when a module has been
	 * renamed, or a module-class has a non-standard namespace
	 * (which is not otherwise handled in this class).
	 *
	 * @param string $modname The module name
	 * @param mixed $classname The actual class name string or falsy to remove the alias
	 */
	public function set_module_classname(string $modname,$classname)
	{
		$modname = trim($modname);
		if( !$modname) return;
		$classname = trim($classname);

		$this->get_module_classmap();
		if( $classname ) {
			if( strpos($classname,'\\') === false ) {
				$classname = $this->modulespace.$classname;
			}
			$this->classmap[$modname] = $classname;
		}
		else {
			unset($this->classmap[$modname]);
		}
		AppParams::set(self::CLASSMAP_PREF,serialize($this->classmap));
	}

	/**
	 * @param string $modname
	 * @return mixed string | null if corresponding class-file not found
	 */
	public function get_module_filename(string $modname)
	{
		$modname = trim($modname);
		if( $modname ) {
			$fn = cms_module_path($modname);
			if( $fn ) return $fn;
		}
	}

	/**
	 * @param string $modname
	 * @return mixed string | null
	 */
	public function get_module_path(string $modname)
	{
		$modname = trim($modname);
		if( $modname ) {
			$dir = cms_module_path($modname, true);
			if( $dir ) return $dir;
		}
	}

	/* *
	 * Generate a moduleinfo.ini file for a module.
	 *
	 * @since 3.0
	 * @param CMSModule $mod a loaded-module object
	 */
/*	public function generate_moduleinfo(CMSModule $mod)
	{
		$dir = $this->get_module_path($mod->GetName());
		if( !is_writable($dir) ) throw new CMSMS\FileSystemException(lang('errordirectorynotwritable'));

		$fh = @fopen($dir.'/moduleinfo.ini','w');
		if( $fh === false ) throw new CMSMS\FileSystemException(lang('errorfilenotwritable','moduleinfo.ini'));

		fputs($fh,"[module]\n");
		fputs($fh,'name = "'.$mod->GetName()."\"\n");
		fputs($fh,'version = "'.$mod->GetVersion()."\"\n");
		fputs($fh,'description = "'.$mod->GetDescription()."\"\n");
		fputs($fh,'author = "'.$mod->GetAuthor()."\"\n");
		fputs($fh,'authoremail = "'.$mod->GetAuthorEmail()."\"\n");
		fputs($fh,'mincmsversion = "'.$mod->MinimumCMSVersion()."\"\n");
		fputs($fh,'lazyloadadmin = '.($mod->LazyLoadAdmin()?'1':'0')."\n");
		fputs($fh,'lazyloadfrontend = '.($mod->LazyLoadFrontend()?'1':'0')."\n");
		$requisites = $mod->GetDependencies();
		if( $requisites ) {
			fputs($fh,"[depends on]\n");
			foreach( $requisites as $mname => $mversion ) {
				fputs($fh,"$mname = \"$mversion\"\n");
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
	 * @param CMSModule | IResource $mod module object
	 * @return 2-member array
	 *  [0] = bool indicating success
	 *  [1] = mixed int != 0 or 1 (failure indicator) or message (failed or success)
	 */
	private function _install_module($mod) : array
	{
		$modname = $mod->GetName();
		debug_buffer('install_module '.$modname);

		$gCms = Lone::get('App'); // vars in scope for Install()
		$db = Lone::get('Db');

		$result = $mod->Install(); // this might initiate other-module engagement, maybe circular
		if( $result && $result !== 1 ) {
			return [false,$result];
		}
		// a successful installation
		$query = 'DELETE FROM '.CMS_DB_PREFIX.'modules WHERE module_name=?';
//		$dbr = if result-check done
		$db->execute($query,[$modname]);
		$query = 'DELETE FROM '.CMS_DB_PREFIX.'module_deps WHERE child_module=?';
//		$dbr =
		$db->execute($query,[$modname]);

//		$lazyload_fe = (method_exists($mod,'LazyLoadFrontend') && $mod->LazyLoadFrontend())?1:0;
//		$lazyload_admin = (method_exists($mod,'LazyLoadAdmin') && $mod->LazyLoadAdmin())?1:0;
		$query = 'INSERT INTO '.CMS_DB_PREFIX.'modules
(module_name,version,admin_only,active)
VALUES (?,?,?,1)';
//(module_name,version,status,admin_only,active,allow_fe_lazyload,allow_admin_lazyload)
//		$dbr =
		$db->execute($query,[
			$modname,$mod->GetVersion(),
			($mod->IsAdminOnly()) ? 1 : 0 //,$lazyload_fe,$lazyload_admin
		]);

		$requisites = $mod->GetDependencies();
		if( $requisites ) {
			//setting create_date should be redundant with DT default-setting
			$longnow = $db->dbTimeStamp(time());
			$stmt = $db->prepare('INSERT INTO '.CMS_DB_PREFIX.'module_deps
(parent_module,child_module,minimum_version,create_date)
VALUES (?,?,?,?)');
			foreach( $requisites as $mname => $mversion ) {
				if( !$mname || !$mversion ) continue;
//				$dbr =
				$db->execute($stmt,[$mname,$modname,$mversion,$longnow]);
			}
			$stmt->close();
		}
		if( !$this->installing) {
			// installer will do this stuff after processing all modules
//			$this->generate_moduleinfo($mod);
			unset($this->moduleinfo); // start again when wanted
			$cache = Lone::get('LoadedData');
			$cache->refresh('modules');
			$cache->refresh('module_deps');
			$cache->refresh('module_depstree');
			$cache->refresh('module_plugins');
			$cache->delete('menu_modules');
			Lone::get('LoadedMetadata')->refresh('*');

			log_notice('Installed module '.$modname.' version '.$mod->GetVersion());
			Events::SendEvent('Core','ModuleInstalled',['name' => $modname,'version' => $mod->GetVersion()]);
		} else {
			log_notice('Installed module '.$modname.' version '.$mod->GetVersion());
		}

		return [true,$mod->InstallPostMessage()];
	}

	/**
	 * Install a module into the database
	 * Any dependencies are also handled
	 *
	 * @param string $modname The name of the module to install
	 * @return array 2 members:
	 *  [0] = bool indicating whether the install was successful
	 *  [1] = string error message if [0] == false
	 */
	public function InstallModule(string $modname) : array
	{
		// try to get an instance of the class
		$mod = $this->get_module_instance($modname,'',true); // forced
		if( !$mod ) {
			$classname = $this->get_module_classname($modname);
			// load the module itself (autoloaded if necessary and possible)
			if( !class_exists($classname,true) ) {
				// autoload failed, try manual fallback
				$filename = $this->get_module_filename($modname);
				if( $filename ) {
					require_once $filename;
				}
				else {
					log_warning("Module class-file does not exist",$modname);
					return [false,$msg];
				}
			}
			$mod = new $classname(true); // overload the contstructor, to skip some f/e init stuff
			// TODO set $this->moduleinfo[$modname]
		}
		if( $mod ) {
/*abandoned			$core = $this->IsSystemModule($modname);
			if( $core ) {
abandoned				if( !$mod->HasCapability(CapabilityType::CORE_MODULE) ) {
					$result = 'Module '.$modname.' installation failed: re-use core-module name';
					if( !$this->installing ) {
						if( !isset($this->moduleinfo[$modname]) ) {
							// undo unwanted new install
							$this->UninstallModule($modname);
						}
						$msg = lang('errorbadname');
					} else {
						$msg = $result; // no lang available when installing
					}
					log_error($result);
					return [false,$msg];
				}
			}
*/
			// process any prerequisite modules
			$requisites = $mod->GetDependencies();
			if( $requisites ) {
//TODO use known ordering to re-order $requisites, if possible BUT that should be managed upstream in installer
//				$tree = Lone::get('LoadedData')->get('module_depstree');
				foreach( $requisites as $mname => $mversion ) {
					if( $mname == '' || $mversion == '' ) continue; // invalid entry
//abandoned					if( $core ) {
						$newmod = $this->get_module_instance($mname);
						if( !is_object($newmod) || version_compare($newmod->GetVersion(),$mversion) < 0 ) {
							log_error('Module installation failed', $modname.' depends on '.$mname.'-'.$mversion);
							$msg = ( !$this->installing ) ?
								lang('missingdependency').': '.$mname:
								'Missing dependency: '.$mname;
							return [false,$msg];
						}
//					}
//					else {
						$minfo = $this->moduleinfo[$mname] ?? NULL;
						if( $minfo ) {
							if( version_compare($minfo['version'],$mversion) < 0 ) {
								log_error('Module installation failed', $modname.' depends on '.$mname.'-'.$mversion);
								$msg = ( !$this->installing ) ?
									lang('missingdependency').': '.$mname:
									'Missing dependency: '.$mname;
								return [false,$msg];
							}
							$newmod = $this->get_module_instance($mname);
						}
						else {
							log_error('Module installation failed', $modname.' depends on '.$mname);
							$msg = ( !$this->installing ) ?
								lang('missingdependency').': '.$mname:
								'Missing dependency: '.$mname;
							return [false,$msg];
						}
//					}
				}
			}

			// do the actual installation stuff
//abandoned			if( !$core ) {
				// if newly installed during the last get_module_instance(), we're done
				if( !empty($this->moduleinfo[$modname]) ) {
					return [true,''];
				}
				//otherwise, try now
				$result = $this->_install_module($mod);
				if( $result[0] == false ) {
					if( !is_string($result[1]) || $result[1] == '' ) {
						$result[1] = ( !$this->installing ) ?
							lang('failure'): //TODO better default messages
							'Unspecified reason';
					}
					log_error('Module installation failed', $modname.','.$result[1]);
				}
				return $result;
//			}
//			return [true,''];
		}
		else {
			$result = "Module '$modname' installation failed";
			$msg = ( !$this->installing ) ?
				lang('errormodulenotloaded'):
				$result;
			log_error($result);
			return [false,$msg];
		}
	}

	/**
	 * Return sorted names of all modules present in the
	 * modules-search-path (regardless of their status)
	 *
	 * @return array
	 */
	public function FindAllModules() : array
	{
		$result = [];
		foreach( cms_module_places() as $dir ) {
			$items = scandir($dir,SCANDIR_SORT_NONE);
			if( $items ) {
				$tmpl = $dir.DIRECTORY_SEPARATOR.'%s'.DIRECTORY_SEPARATOR.'%s.module.php';
				foreach( $items as $name ) {
					if( $name === '..' || $name === '.' ) continue;
					$file = sprintf($tmpl,$name,$name);
					if( @is_file($file) && !in_array($name,$result) ) $result[] = $name;
				}
			}
		}
		usort($result,'strnatcasecmp');
		return $result;
	}

	/**
	 * Upgrade
	 * @ignore
	 * @access private
	 * @param CMSModule | IResource $mod
	 * @param string $to_version
	 * @return 2-member array
	 *  [0] = bool indicating success
	 *  [1] = '' or error message
	 */
	private function _upgrade_module($mod,string $to_version = '') : array
	{
		// upgrade only if the database schema is up-to-date. TODO might be circular?
		if( !schema_is_current() ) {
			$msg = ( !$this->installing ) ?
				lang('error_coreupgradeneeded'):
				'Upgrade CMSMS itself before module-upgrade';
			return [false,$msg];
		}

		$info = $this->_get_installed_module_info();
		$modname = $mod->GetName();
		$dbversion = $info[$modname]['version'];
		if( $to_version == '' ) {
			$to_version = $mod->GetVersion();
		}
		if( version_compare($dbversion,$to_version) >= 0 ) {
			return [true,'']; // nothing to do
		}

		$gCms = Lone::get('App'); // deprecated - some modules check this
		$result = $mod->Upgrade($dbversion,$to_version);
		if( $result && $result !== 1 ) {
			if( is_numeric($result) ) {
				$result = ( !$this->installing ) ?
					lang('failure'): // TODO better default message
					'Unspecified reason';
			}
			log_error('Module upgrade failed', $modname.','.$result);
			return [false,$result];
		}

		$db = Lone::get('Db');
		$pref = CMS_DB_PREFIX;
		//TODO handle module re-location, if any
//		$lazyload_fe    = (method_exists($mod,'LazyLoadFrontend') && $mod->LazyLoadFrontend())?1:0;
//		$lazyload_admin = (method_exists($mod,'LazyLoadAdmin') && $mod->LazyLoadAdmin())?1:0;
		$admin_only = ($mod->IsAdminOnly()) ? 1 : 0;

//		$query = "UPDATE {$pref}modules SET version = ?,allow_fe_lazyload = ?,allow_admin_lazyload = ?,admin_only = ? WHERE module_name = ?";
		$query = "UPDATE {$pref}modules SET version = ?,admin_only = ? WHERE module_name = ?";
//		$dbr =
//		$db->execute($query,[$to_version,$lazyload_fe,$lazyload_admin,$admin_only,$modname]);
		$db->execute($query,[$to_version,$admin_only,$modname]);

		// upgrade dependencies
		$query = "DELETE FROM {$pref}module_deps WHERE child_module=?";
//		$dbr =
		$db->execute($query,[$modname]);

		$requisites = $mod->GetDependencies();
		if( $requisites ) {
			$result = [];
			$longnow = $db->dbTimeStamp(time());
			$query = <<<EOS
INSERT INTO {$pref}module_deps
(parent_module,child_module,minimum_version,create_date)
VALUES (?,?,?,$longnow)
EOS;
			$stmt = $db->prepare($query);
			foreach( $requisites as $mname => $mversion ) {
				if( !$mname || !$mversion ) continue;
//				$dbr =
				$db->execute($stmt,[$mname,$modname,$mversion]);
//				if( $dbr )
				$result[] = $mname;
			}
			$stmt->close();
			if( $result ) {
				$val = implode(',',$result);
				$query = <<<EOS
UPDATE {$pref}modules M
JOIN {$pref}modules P ON M.module_name = P.module_name
SET M.active=1
WHERE P.module_name=? AND P.active=1 AND M.module_name IN ($val)
EOS;
				$db->execute($query,[$modname]);
			}
		}

		if( !$this->installing ) {
			// installer will do this stuff after processing all modules
//			$this->generate_moduleinfo($mod);
			unset($this->moduleinfo); // TODO check what havoc this causes
			$cache = Lone::get('LoadedData');
			$cache->refresh('modules');
			$cache->refresh('module_deps');
			$cache->refresh('module_depstree');
			$cache->refresh('module_plugins'); // TODO might this actually change via upgrade?
			Lone::get('LoadedMetadata')->refresh('*');

			$vers = $mod->GetVersion();
			log_notice('Upgraded module '.$modname.' to version '.$vers);
			Events::SendEvent('Core','ModuleUpgraded',
				['name' => $modname,'oldversion' => $dbversion,'newversion' => $vers]);
			Lone::get('LoadedData')->delete('events'); // WHY ??
		}
		else {
			log_notice('Upgraded module '.$modname.' to version '.$mod->GetVersion());
		}
		return [true,''];
	}

	/**
	 * Upgrade a module
	 *
	 * @internal
	 * @param string $modname The name of the module to upgrade
	 * @param string $to_version The destination version
	 * @return array, 1 or 2 members
	 *  [0] : bool indicating whether the upgrade was successful
	 *  [1] : string error message if [0] == false
	 */
	public function UpgradeModule(string $modname,string $to_version = '') : array
	{
		$mod = $this->get_module_instance($modname,'',true); // forced
		if( is_object($mod) ) {
			$result = $this->_upgrade_module($mod,$to_version);
			return $result;
		}
		$msg = ( !$this->installing ) ?
			lang('errormodulenotloaded'):
			'No module exists to upgrade';
		return [false,$msg];
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
		unset($this->modules[$modname]);
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
		$mod = $this->get_module_instance($modname,'',true); // forced
		if( !$mod ) {
			$msg = ( !$this->installing ) ?
				lang('errormodulenotloaded'):
				'Module not found';
			return [false,$msg];
		}

		$result = $mod->Uninstall();
		if( $result && $result !== 1 ) {
			if( is_numeric($result) ) {
				$result = ( !$this->installing ) ?
					lang('failure'): // TODO better default error message (again)
					'Unspecified reason';
			}
			log_error('Module uninstall failed', $modname.','.$result);
			return [false,$result];
		}

		$db = Lone::get('Db');
		// delete the record
		$db->execute('DELETE FROM '.CMS_DB_PREFIX.'modules WHERE module_name=?',[$modname]);

		// clear any dependencies
		$db->execute('DELETE FROM '.CMS_DB_PREFIX.'module_deps WHERE child_module=?',[$modname]);

		$cleanup = $this->installing || $mod->AllowUninstallCleanup();
		// deep-clean, if permitted
		if( $cleanup ) {
			$db->execute('DELETE FROM '.CMS_DB_PREFIX.TemplateOperations::TABLENAME.' WHERE originator=?',[$modname]);
			$db->execute('DELETE FROM '.CMS_DB_PREFIX.'event_handlers WHERE class=? AND type=\'M\'',[$modname]);
			$db->execute('DELETE FROM '.CMS_DB_PREFIX.'events WHERE originator=?',[$modname]);

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

//			$jobmgr = cmsms()->GetJobManager();
//			if( $jobmgr ) $jobmgr->unload_jobs_by_module($modname);
			(new JobOperations())->unload_jobs_by_module($modname);

			$db->execute('DELETE FROM '.CMS_DB_PREFIX.'module_smarty_plugins WHERE module=?',[$modname]);
//			$db->execute('DELETE FROM '.CMS_DB_PREFIX.'siteprefs WHERE sitepref_name LIKE '\'. str_replace("'",'',$db->qStr($modname))."_mapi_pref%'"); TODO AppParams::NAMESPACER
			AppParams::remove($modname.AppParams::NAMESPACER,true);
			$db->execute('DELETE FROM '.CMS_DB_PREFIX.'routes WHERE dest1=?',[$modname]);
		}

		if( !$this->installing ) {
			// installer will do this stuff after processing all modules
			$this->ReHomeUsers($db,$modname);
			// TODO effects on class properties: minfo, modules etc
			unset($this->modules[$modname]);
			// Remove module from info
			unset($this->moduleinfo[$modname]);
			// clear related caches
			$cache = Lone::get('LoadedData');
			$cache->refresh('modules');
			$cache->refresh('module_deps');
			$cache->refresh('module_depstree');
			$cache->refresh('module_plugins');
			$cache->delete('menu_modules','*');

			Lone::get('LoadedMetadata')->refresh('*');

			log_notice('Uninstalled module',$modname);
			Events::SendEvent('Core','ModuleUninstalled',['name' => $modname]);
			$cache->delete('Events'); // WHY ??
		}
		else {
			log_notice('Uninstalled module',$modname);
		}
		return [true,''];
	}

	/**
	 * Test if a module is active
	 *
	 * @param string $modname
	 * @return bool
	 */
	public function IsModuleActive(string $modname) : bool
	{
		if( !$modname ) return false;
		$info = $this->_get_installed_module_info();
		if( !isset($info[$modname]) ) return false;

		return (bool)$info[$modname]['active'];
	}

	/**
	 * [De]activate a module
	 *
	 * @param string $modname
	 * @param bool $activate flag indicating whether to activate or deactivate the module
	 * @return bool indicating success
	 */
	public function ActivateModule(string $modname,bool $activate = true) : bool
	{
		if( !$modname ) return false;
		$info = $this->_get_installed_module_info();
		if( !isset($info[$modname]) ) return false;

		$current = !empty($info[$modname]['active']);
		if( $activate ) {
			$this->moduleinfo[$modname]['active'] = 1;
		}
		else {
			$this->moduleinfo[$modname]['active'] = 0;
		}
		$info = $this->moduleinfo;
		if( $info[$modname]['active'] != $current ) {
			Events::SendEvent('Core','BeforeModuleActivated',['name'=>$modname,'activated'=>$activate]);
			$db = Lone::get('Db');
			$query = 'UPDATE '.CMS_DB_PREFIX.'modules SET active = ? WHERE module_name = ?';
//			$dbr =
			$db->execute($query,[$info[$modname]['active'],$modname]);
//TODO if $activate i.e. newly active, ditto for all dependent modules
			if( !$activate ) {
				$this->ReHomeUsers($db,$modname);
			}
			$cache = Lone::get('LoadedData');
			$cache->refresh('modules'); //force refresh of the cached active property
			$cache->refresh('module_deps');
			$cache->refresh('module_depstree');
			$cache->refresh('module_plugins');
//TODO			$cache->delete('menu_modules','*'); // if not installing ?
			Lone::get('LoadedMetadata')->refresh('*');

			Events::SendEvent('Core','AfterModuleActivated',['name'=>$modname,'activated'=>$activate]);
			if( $activate ) {
				log_notice('Activated module',$modname);
			}
			else {
				log_notice('Deactivated module',$modname);
			}
		}
		return true;
	}

	/**
	 * @since 3.0
	 * @internal
	 * @param Connection $db
	 * @param string $modname
	 */
	private function ReHomeUsers($db,string $modname)
	{
		$query = 'SELECT user_id,`value` FROM '.CMS_DB_PREFIX.'userprefs WHERE preference=\'homepage\' AND `value` LIKE ?';
		$data = $db->getArray($query, ['%mact='.$modname.'%']);
		if( $data ) {
			$query = 'UPDATE '.CMS_DB_PREFIX."userprefs set `value`='' WHERE preference='homepage' AND user_id=";
			foreach( $data as $row) {
				if( strpos($row['value'], 'moduleinterface.php?mact') !== false ) {
					$db->execute($query.$row['user_id']);
				}
			}
		}
	}

	/**
	 * Pass a named module to a nominated handler
	 * The handler will be called with the module-object (or null if N/A) as argument.
	 * @since 3.0
	 *
	 * @param string $modname
	 * @param bool $force flag whether to force-load the module
	 * @param callable $reporter
	 */
	public function PollModule(string $modname,bool $force,callable $reporter)
	{
		if( !$force && isset($this->modules[$modname]) ) {
			$mod = $this->modules[$modname];
			$unload = false;
		}
		else {
			$this->polling = true; // restrict downstream engagements
			$mod = $this->get_module_instance($modname,'',$force);
			$this->polling = false;
			$unload = ($mod !== NULL && ($force || !isset($this->modules[$modname])));
		}
		$reporter($mod);
		if( $unload ) {
			unset($mod);
		}
	}

	/**
	 * Return all currently-loaded modules.
	 *
	 * @return mixed array | null Each array member like modname => modobject
	 */
	public function GetLoadedModules()
	{
		return $this->modules;
	}

	/**
	 * @internal
	 */
	public function is_module_loaded(string $modname) : bool
	{
		$modname = trim($modname);
		return isset($this->modules[$modname]);
	}

	/**
	 * Return properties of all installed modules.
	 * The data are sourced from the database via a LoadedData cache,
	 * plus some derivations e.g. dependencies
	 *
	 * @since 2.0
	 * @return array
	 */
	public function GetInstalledModuleInfo()
	{
		return $this->_get_installed_module_info();
	}

	/**
	 * Return all the information we know about installed modules.
	 * @deprecated since 3.0 instead use ModuleOperations::GetInstalledModuleInfo()
	 *
	 * @return array
	 */
	public function GetAllModuleInfo()
	{
		assert(empty(CMS_DEPREC),new DeprecationNotice('method','ModuleOperations::GetInstalledModuleInfo()'));
		return $this->_get_installed_module_info();
	}

	/**
	 * Return locally-cached info about installed modules
	 * @param bool $force since 3.0 Flag whether to force-load the modules cache. Default false
	 * @return array, maybe empty, or each member like $modname => props
	 *  props are from db via LoadedData 'modules', plus calculated 'dependents'
	 * @ignore
	 */
	private function _get_installed_module_info(bool $force = false) : array
	{
		if( $force || !isset($this->moduleinfo) ) {
			$this->moduleinfo = [];
			$data = Lone::get('LoadedData')->get('modules', $force);
			if( $data && is_array($data) ) {
				foreach( $data as $modname => $props ) {
					//double-check that cache data are current TODO ignore it if upgrade needed
					$filename = $this->get_module_filename($modname);
					if( $filename ) {
						if( !isset($this->moduleinfo[$modname]) ) {
							$this->moduleinfo[$modname] = ['module_name'=>$modname] + $props;
						}
					}
				}
				$all_deps = $this->_get_module_dependencies(); // quick flat overview
				if( $all_deps ) {
					foreach( $all_deps as $mchild => $deps ) {
						if( $deps && is_array($deps) && isset($this->moduleinfo[$mchild]) ) {
							$ordered = $this->get_module_dependencies($mchild, false); // again, but in dependency-order
							$this->moduleinfo[$mchild]['dependents'] =
								( $ordered ) ? array_keys($ordered) : [];
						}
					}
				}
			}
		}
		return $this->moduleinfo;
	}

	/**
	 * Return the names of installed modules
	 * @since 3.0
	 * @see also ModuleOpeations::GetInstalledModules()
	 *
	 * @return array
	 */
	public function GetInstalledModuleNames()
	{
		return array_keys($this->_get_installed_module_info());
	}

	/**
	 * Return the names of installed modules
	 * @deprecated since 3.0 instead use ModuleOperations::GetInstalledModuleNames()
	 *
	 * @return array
	 */
	public function GetAllModuleNames()
	{
		assert(empty(CMS_DEPREC),new DeprecationNotice('method','ModuleOperations::GetInstalledModuleNames()'));
		return array_keys($this->_get_installed_module_info());
	}

	/**
	 * Return the names of active | all installed modules
	 * @see also ModuleOpeations::GetInstalledModuleNames()
	 *
	 * @param bool $include_inactive true: report all modules, false: report active modules only. Default false.
	 * @return array
	 */
	public function GetInstalledModules(bool $include_inactive = false) : array
	{
		// TODO this gets called a lot during page construction, might be worth a local-cache
		$info = $this->_get_installed_module_info();
		if( is_array($info) ) {
			if( $include_inactive ) { return array_keys($info); }
			$out = [];
			foreach( $info as $modname => $props ) {
				if( $props['active'] ) {
					$out[] = $modname;
				}
			}
			return $out;
		}
		return [];
	}

	/**
	 * Return the names of available but not-loaded modules
	 * @since 3.0
	 *
	 * @return array maybe empty
	 */
	public function GetLoadableModuleNames() : array
	{
		return array_diff($this->GetInstalledModules(),array_keys($this->modules));
	}

	/**
	 * @ignore
	 * @param bool $flat since 3.0 whether to get un-ordered dependencies
	 * @return mixed
	 *  if $flat, then array, each member like
	 *    child=>[parent1=>minver1, parent2=>minver2, ...]
	 *  if !$falt, then ArrayTree
	 *  or null if no data were retrieved
	 * child depends on parent(s) i.e. child is a dependent of each parent
	 */
	private function _get_module_dependencies($flat = true)
	{
		if( $flat ) {
			$out = Lone::get('LoadedData')->get('module_deps');
		}
		else {
			$out = Lone::get('LoadedData')->get('module_depstree');
		}
		if( $out !== '-' ) {
			return $out;
		}
	}

	/**
	 * Return the dependencies (i.e. not dependents) of a module.
	 * This reads 'parent-modules' information from cache or database.
	 *
	 * @since 1.11.8
	 * @param string $modname The module name
	 * @param bool $flat since 3.0 whether to get un-ordered dependencies
	 * @return mixed array of prerequisite-module names and versions | null
	 */
	public function get_module_dependencies(string $modname, $flat = true)
	{
		if( !$modname ) return;
		$all_deps = $this->_get_module_dependencies($flat);
		if( $flat ) {
			return $all_deps[$modname] ?? NULL;
		}
		$path = ArrayTree::find($all_deps, 'name', $modname);
		if( $path !== NULL ) {
			$n = count($path);
			if( $path[$n-1] == $modname ) {
				unset($path[$n-1]); // no self-dependency
			}
			if( count($path) == 1 ) {
				return NULL; // just the irrelevant root node
			}
			$ver = ArrayTree::path_get_data($all_deps, $path, 'version', '');
			unset($path[0]); // bye, root node
			$ret = array_combine($path, $ver);
			return $ret;
		}
	}

	/**
	 * @internal
	 * @param string $modname
	 * @param bool $force Optional flag, whether to reload the module if
	 *  already loaded. Default false.
	 * @return boolean indicating success, incl. false if aborted because
	 *  loading of $modname began during a prior call here (i.e. indirect circularity)
	 */
	private function _get_module(string $modname,bool $force = false) : bool
	{
		$force |= $this->installing; // CHECKME
		$info = $this->_get_installed_module_info();
		if( !($force || isset($info[$modname])) ) {
			log_warning("Unknown module",$modname);
			return false;
		}

		$gCms = Lone::get('App'); // some crappy old modules expect this during loading. Deprecated since 3.0

		if( !($this->installing || $this->polling) ) {
			// okay, lessee if we can load dependencies/prerequisites
			$requisites = $this->get_module_dependencies($modname,false);
			if( $requisites ) {
// TODO use 'module_depstree' cache-data to the extent possible to optimize the processing order
				foreach( $requisites as $mname => $mversion ) {
					if( $mname == $modname ) continue; // a module may not depend directly on itself
					// this is the start of a recursive process: get_module_instance() may call _get_module(), and iterate ...
					$mod2 = $this->get_module_instance($mname,$mversion); // TODO unforced ok for deps ?
					if( !is_object($mod2) ) {
						log_warning("Module not loaded, problem with prerequisite-module '$mname' version '$mversion'",$modname);
						return false;
					}
				}
			}
		}

		$classname = $this->get_module_classname($modname);
		// load the module itself (autoloaded if necessary and possible)
		if( !class_exists($classname,true) ) {
			// autoload failed, try manual fallback
			$filename = $this->get_module_filename($modname);
			if( $filename ) {
				require_once $filename;
			}
			else {
				log_warning("Module class-file does not exist",$modname);
				return false;
			}
		}

		if( $force ) {
			$mod = new $classname(true); // overload the contstructor, to skip some f/e init stuff
		}
		else {
			$mod = new $classname();
		}

		if( !is_object($mod) || !($mod instanceof CMSModule || $mod instanceof IResource) ) {
			// some problem loading
			log_error('Failed to instantiate module class', $modname);
			unset($mod);
			return false;
		}

		if( version_compare($mod->MinimumCMSVersion(),CMS_VERSION) > 0 ) {
			// not compatible, can't load
			log_error('Module incompatible with CMSMS version', $modname);
			unset($mod);
			return false;
		}

		$this->modules[$modname] = $mod;
		// if the installer is not running but the module is 'installer-bundled', try to install/upgrade it if need be
		if( !$this->installing ) { //&& $this->IsBundledModule($modname) ) {
			if( schema_is_current() ) {
				// current schema, ok to install module if necessary
				if( !isset($info[$modname]) ) {
					$result = $this->_install_module($mod);
					if( $result[0] ) {
						$info = $this->_get_installed_module_info(true); // TODO incremental $info[] change
						// now installed TODO bypass upgrade-check, just Initialize*()
					}
					else {
						// nope, can't auto install...
						debug_buffer("Automatic installation of module '$modname' failed");
						unset($mod,$this->modules[$modname]);
						return false;
					}
				}
			}
			// current schema or not, check whether an upgrade is needed
			if( isset($info[$modname]) ) {
				$dbversion = $info[$modname]['version'];
				if( version_compare($dbversion,$mod->GetVersion()) < 0 ) {
					// looks like upgrade is needed
					$result = $this->_upgrade_module($mod);
					if( $result ) {
						$info = $this->_get_installed_module_info(true); // TODO incremental $info[] change
					}
					else {
						// upgrade failed
						debug_buffer("Automatic upgrade of module '$modname' failed");
						unset($mod,$this->modules[$modname],$info[$modname]);
						return false;
					}
				}
			}
		}
		if( !isset($info[$modname]) ) { // not installed
			debug_buffer('Cannot load an uninstalled module');
			unset($mod,$this->modules[$modname]);
			return false;
		}
//		if( !($this->installing || AppState::test_any(AppState::STYLESHEET | ...)) ) {
		if( !($this->installing || $this->polling) ) {
			// when $this->polling, these Init's are done upstream
			if( AppState::test(AppState::ADMIN_PAGE) ) {
				$mod->InitializeAdmin();
			}
			elseif( !$force && AppState::test(AppState::FRONT_PAGE) ) {
				$mod->InitializeFrontend();
			}
			Events::SendEvent('Core','ModuleLoaded',['name' => $modname]);
		}
		return true;
	}

	/**
	 * Return a module object, if possible
	 * If the module is not already loaded, and $force is true, the module
	 * will be loaded. If a (min) version is specified, a module version
	 * check is done, to prevent loading an insufficient version.
	 *
	 * @param string $modname
	 * @param string $version Optional version identifier.
	 * @param bool $force Optional flag whether to reload the module if
	 *  already loaded e.g. there might be a new version. Default false.
	 * @return mixed CMSModule subclass | IResource | null
	 *  Since 3.0 (and PHP 5.0) : object, not an object-reference ("returning object-references is totally wasted")
	 */
	public function get_module_instance($modname, string $version = '', bool $force = false)
	{
		if( !$modname ) {
			return NULL;
		}

		$mod = NULL;
		if( isset($this->modules[$modname]) ) {
			if( $force ) {
				unset($this->modules[$modname]);
			}
			else {
				$mod = $this->modules[$modname];
			}
		}
		if( !is_object($mod) ) {
			// load it, if possible
			$result = $this->_get_module($modname,$force);
			if( $result ) {
				$mod = $this->modules[$modname];
			}
		}

		if( is_object($mod) && ($version || is_numeric($version)) ) {
			$result = version_compare($mod->GetVersion(),$version);
			if( $result < 0 ) {
				$mod = NULL;
			}
		}
		return $mod;
	}

	/* *
	 * Record the names of core/system modules known to the system
	 * (wherever they are stored)  NAH >> , and whatever status they currently have)
	 * We don't need to assume those modules are in any specific folder(s),
	 * and need to be polled in there
	 * @param mixed $val Optional comma-separated string | strings[] | falsy
	 */
/* abandoned
	public function RegisterSystemModules($val = '')
	{
		if( !$val ) {
			$val = AppParams::get(self::CORENAMES_PREF);
		}
		if( $val ) {
			if( !is_array($val) ) {
				$val = array_map('trim',explode(',',$val));
			}
		}
		else {
			// Absolutely definite module-names could be hardcoded e.g.
			//   $val = explode(',', self::CORENAMES_DEFAULT);
			// But instead, we do an expensive, slow, probably-incomplete
			// during installation, poll
			$val = [];
			$names = $this->FindAllModules();
			$gCms = Lone::get('App'); // compatibility for some crappy old modules, deprecated since 3.0
			// processing order doesn't matter: only HasCapability() calls here
			foreach( $names as $modname ) {
				$classname = $this->modulespace.$modname;
				if( !class_exists($classname) ) {
					require_once cms_module_path($modname);
				}
				$mod = new $classname();
				if( $mod->HasCapability(CapabilityType::CORE_MODULE) ) {
					$val[] = $modname;
				}
				unset($mod);
				$mod = NULL;
			}
		}
		sort($val,SORT_STRING);
		$this->coremodules = $val;
	}
*/
	/**
	 * Determine whether the specified name corresponds to a system/core module.
     * @deprecated since 3.0 instead use ModuleOprations::IsBundledModule()
	 *
	 * @param string $modname The module name
	 * @return bool
	 */
	public function IsSystemModule(string $modname) : bool
	{
		assert(empty(CMS_DEPREC),new DeprecationNotice('method','IsBundledModule'));
		return $this->IsBundledModule($modname);
/*		if( !$this->coremodules ) {
			$this->RegisterSystemModules();
		}
		if( $this->coremodules ) {
			$result = in_array($modname,$this->coremodules);
			if( AppState::test(AppState::INSTALL) ) {
				//revert the modules-list, in case they change during install
				$this->coremodules = NULL;
			}
			return $result;
		}
		return false;
*/
	}

	/**
	 * Determine whether the specified name represents a module that's 'supplied-with-installer'.
     * @since 3.0
	 *
	 * @param string $modname The module name
	 * @return bool
	 */
	public function IsBundledModule(string $modname) : bool
	{
		if( empty($this->corenames) ) {
			$this->corenames = AppParams::get(self::CORENAMES_PREF);
			if( !$this->corenames ) { throw new RuntimeException('Failed to find the site-preference which defines core modules'); }
		}
		return stripos($this->corenames, $modname) !== false; // OR strict >> strpos() ?
	}

	/**
	 * Record the (non-default) login module to be used from now
	 * @since 3.0
	 * @param CMSModule | IResource $mod
	 * @throws LogicException or UnexpectedValueException
	 */
	public function RegisterAdminLoginModule($mod)
	{
		if( $this->auth_module ) { throw new LogicException('An authentication module has already been recorded for current use'); }
		if( ! $mod instanceof IAuthModule ) {
			throw new UnexpectedValueException($mod->GetName().' is not a valid authentication module');
		}
		$this->auth_module = $mod;
	}

	/**
	 * @since 3.0
	 * @return mixed CMSModule | IResource | null
	 */
	public function GetAdminLoginModule()
	{
		if( $this->auth_module ) return $this->auth_module;
		return $this->get_module_instance(self::STD_LOGIN_MODULE,'',true);
	}

	/**
	 * Return a syntax highlighter module object, if possible.
	 * This method retrieves the specified syntax highlighter module,
	 * or the current user's preference for such module, in each case
	 * provided that all of the module's dependencies are satisfied
	 * @since 1.10
	 * @deprecated since 3.0. Instead, generate and place content (js etc) directly
	 *
	 * @param mixed string|null|-1 $modname allows specifying a
	 * module to be used instead of the user's recorded preference.
	 * @return mixed CMSModule | IResource | null
	 */
	public function GetSyntaxHighlighter($modname = NULL)
	{
		if( !$modname ) {
			if( AppState::test(AppState::ADMIN_PAGE) ) {
				$modname = UserParams::get_for_user(get_userid(false),'syntaxhighlighter');
			}
			if( $modname ) {
				$modname = de_entitize($modname); // for some reason entities may have gotten in there?
			}
		}

		if( $modname && $modname != -1 ) {
			$modname = sanitizeVal($modname,CMSSAN_FILE);
			$mod = $this->get_module_instance($modname);
			if( $mod && $mod->HasCapability(CapabilityType::SYNTAX_MODULE) ) {
				return $mod;
			}
		}
	}

	/**
	 * Alias for GetSyntaxHiglighter().
	 *
	 * @see ModuleOperations::GetSyntaxHighlighter()
	 * @deprecated since 3.0
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
	 * and the current user's preference for such module, in each
	 * case provided that all the module's dependencies are satisfied.
	 * @since 1.10
	 * @deprecated since 3.0. Instead, generate and place content (js etc) directly
	 *
	 * @param mixed string|null $modname allows bypassing the automatic
	 *  detection process and specifying a WYSIWYG module.
	 * @return mixed CMSModule | IResource | null
	 */
	public function GetWYSIWYGModule($modname = NULL)
	{
		if( !$modname ) {
			if( is_frontend_request() ) {
				$modname = AppParams::get('frontendwysiwyg');
			}
			else {
				$modname = UserParams::get_for_user(get_userid(false),'wysiwyg');
			}
			if( $modname ) $modname = html_entity_decode($modname); // TODO OR CMSMS replacement ?
		}

		if( !$modname || $modname == -1 ) return;
		$mod = $this->get_module_instance($modname);
		if( $mod && $mod->HasCapability(CapabilityType::WYSIWYG_MODULE) ) {
			return $mod;
		}
	}

	/**
	 * Return the currently selected search module object, if any, and
	 * if all its dependencies are satisfied.
	 * @since 1.10
	 *
	 * @return mixed CMSModule | IResource | null
	 */
	public function GetSearchModule()
	{
		$modname = AppParams::get('searchmodule','Search');
		if( $modname && $modname != 'none' && $modname != '-1' ) {
			$mod = $this->get_module_instance($modname);
			if( $mod && $mod->HasCapability(CapabilityType::SEARCH_MODULE) ) {
				return $mod;
			}
		}
	}

	/**
	 * Return the currently-selected file-picker module object, if any,
	 * and if all its dependencies are satisfied.
	 * @since 2.2
	 *
	 * @return mixed IFilePicker | null
	 */
	public function GetFilePickerModule()
	{
		$modname = AppParams::get('filepickermodule','FilePicker');
		if( $modname && $modname != 'none' && $modname != '-1' ) {
			$mod = $this->get_module_instance($modname);
			if( $mod ) return $mod;
		}
	}

	/**
	 * Return the members of $_REQUEST[] whose key begins with $id
	 * $id is stripped from the start of returned keys.
	 * @deprecated since 3.0 instead use RequestParameters::get_identified_params()
	 *
	 * @param string $id parameter identifier/prefix
	 * @return array, maybe empty
	 */
	public function GetModuleParameters(string $id) : array
	{
		assert(empty(CMS_DEPREC),new DeprecationNotice('method','CMSMS\RequestParameters::get_identified_params()'));
		return RequestParameters::get_identified_params($id);
	}
} // class

//backward-compatibility shiv
\class_alias(ModuleOperations::class,'ModuleOperations',false);
