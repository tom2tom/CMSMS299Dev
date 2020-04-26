<?php
#autoloader for CMS Made Simple <http://www.cmsmadesimple.org>
#Copyright (C) 2004-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.
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

/**
 * @ignore
 * Type-declarations don't trigger autoloading, so renamed/re-spaced
 * classes cannot be passed to or received from functions or methods
 * which have 'old' type declaration(s).
 * But for usages other than those, this supports using a foreshadowed
 * future [space/]name as an alias for the current.
 */
class class_forwarder
{
	private const CLASSREPLACES = [
		'cms_config' => 'CMSMS\\Config',
		'cms_content_tree' => 'CMSMS\\ContentTree',
		'cms_cookies' => 'CMSMS\\Cookies',
		'cms_http_request' => 'CMSMS\\HttpRequest',
		'cms_tree' => 'CMSMS\\Tree',
		'cms_url' => 'CMSMS\\Url',
		'cms_utils' => 'CMSMS\\Utils',
		'CmsAdminMenuItem' => 'CMSMS\\AdminMenuItem',
		'CmsApp' => 'CMSMS\\App',
		'CmsDbQueryBase' => 'CMSMS\\DbQueryBase',
		'CmsLanguageDetector' => 'CMSMS\\LanguageDetector',
		'CmsLayoutCollection' => 'DesignManager\\Design',
		'CmsLayoutStylesheet' => 'CMSMS\\Stylesheet',
		'CmsLayoutStylesheetQuery' => 'CMSMS\\StylesheetQuery',
		'CmsLayoutTemplate' => 'CMSMS\\Template',
		'CmsLayoutTemplateCategory' => 'CMSMS\\TemplatesGroup',
		'CmsLayoutTemplateQuery' => 'CMSMS\\TemplateQuery',
		'CmsLayoutTemplateType' => 'CMSMS\\TemplateType',
//		'CMSModule' => 'CMSMS\\Module', //mebbe stet global namespace ?
		'CMSModuleContentType' => 'CMSMS\\ModuleContentType',
		'CMSMS\\Async\\JobManager' => 'CMSMS\\Async\\JobOperations',
		'CMSMS\\FilePickerProfile' => 'CMSMS\\FileSystemProfile',
		'CMSMS\\HookManager' => 'CMSMS\\HookOperations',
		'CMSMS\\internal\\CmsAdminThemeNotification' => 'CMSMS\\internal\\AdminNotification',
		'CmsPermission' => 'CMSMS\\Permission',
		'CmsRegularTask' => 'CMSMS\\RegularTask', //interface
		'CmsRoute' => 'CMSMS\\Route',
	];

	/**
	 * @internal
	 * @ignore
	 */
	public static function get_new_names() : array
	{
		return array_values(self::CLASSREPLACES);
	}

	/**
	 * Load the file defining the 'current' class, interface etc corresponding to $classname
	 * @internal
	 * @ignore
	 * @param string $classname new [space\]name
	 * @return bool indicating success
	 * @throws RuntimeException if old-class file cannot be found
	 */
	public static function load_old_name(string $classname) : bool
	{
		$current = array_search($classname, self::CLASSREPLACES);
		if ($current) {
			$p = strpos($current, '\\', 1);
			$space = ($p !== false) ? substr($current, 0, $p) : '';
			if (!$space || strncasecmp($space, 'CMS', 3) == 0) {
				$root = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes';
				//TODO support others e.g. CMSAsset
				//$root = CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR;
				//$root = CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR;
			} else {
				$root = cms_module_path($space, true);
				if (!$root) {
					return false;
				}
			}
			$path = $root.DIRECTORY_SEPARATOR.str_replace([$space,'\\'], ['',DIRECTORY_SEPARATOR], $current);
			$sroot = dirname($path).DIRECTORY_SEPARATOR;
			$base = basename($path);
			foreach (['class.', 'trait.', 'interface.', ''] as $test) {
				$fp = $sroot.$test.$base.'.php';
				if (is_file($fp)) {
					require_once $fp;
					return true;
				}
			}
			throw new RuntimeException('System error: cannot find '.$current);
		}
		throw new RuntimeException('System error: invalid '.$classname);
	}
} // class

/**
 * A function for auto-loading classes.
 *
 * @since 1.7
 * @internal
 * @ignore
 * @param string A possibly-namespaced class name
 */
function cms_autoloader(string $classname)
{
	static $classmap = null;
	if ($classmap === null) {
		$classmap = class_forwarder::get_new_names();
	}

	$root = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR;
/*
	// standard content types (prioritized)
	$fp = $root.'contenttypes'.DIRECTORY_SEPARATOR.'class.'.$classname.'.php';
	if (is_file($fp)) {
		require_once $fp;
		return;
	}
*/
	$o = ($classname[0] != '\\') ? 0 : 1;
	$p = strpos($classname, '\\', $o + 1);
	if ($p !== false) {
		// ignore any leading \
		if ($o > 0) {
			$classname = substr($classname, $o);
			$p -= $o;
			$o = 0;
		}
		$space = substr($classname, $o, $p - $o);
		if ($space == 'CMSMS') {
			$sroot = $root;
		} elseif ($space == 'CMSAsset') {
			$sroot = CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR;
		} elseif ($space == 'CMSResource') {
			$sroot = CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR;
		} else {
			$mpath = cms_module_path($space);
			if ($mpath) {
				$sroot = dirname($mpath).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR;
			} else {
				return;
			}
		}

		if (in_array($classname, $classmap)) {
			class_forwarder::load_old_name($classname);
			return;
		}
		$path = str_replace('\\', DIRECTORY_SEPARATOR, substr($classname, $p + 1));
		$base = basename($path);
		$path = dirname($path);
		if ($path != '.') {
			$sroot .= $path.DIRECTORY_SEPARATOR;
		}
		switch ($space) {
			case 'CMSMS':
			case 'CMSAsset':
			case 'CMSResource':
				$sysp = true;
				break;
			default:
				$sysp = false;
		}
		foreach (['class.', 'trait.', 'interface.', ''] as $test) {
			$fp = $sroot.$test.$base.'.php';
			if (is_file($fp)) {
				if (!($sysp || class_exists($space, false))) {
					//deprecated since 2.3 - some modules require existence of this, or assume, and actually use it
					$gCms = CmsApp::get_instance();
					require_once $mpath;
				}
				require_once $fp;
				if (class_exists($classname, false)) return;
			}
		}

		if (endswith($base, 'Task')) {
			if ($space == 'CMSMS') {
				$sroot = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'tasks'.DIRECTORY_SEPARATOR;
			}
			$t2 = substr($base, 0, -4).'.task';
			foreach (['class.', 'trait.', 'interface.', ''] as $test) {
				$fp = $sroot.$test.$t2.'.php';
				if (is_file($fp)) {
					require_once $fp;
					if (class_exists($classname, false)) return;
				} else {
					$fp = $sroot.$test.$base.'.php';
					if (is_file($fp)) {
						require_once $fp;
						if (class_exists($classname, false)) return;
					}
				}
			}
		}
		return; //failed
	} elseif ($o) {
		return; //a 'foreign' namespace to be handled elsewhwere
	} else {
		$base = $classname;
	}

	if (strpos($base, 'Smarty') !== false) {
		if (strpos($base, 'CMS') === false) {
			return; //hand over ASAP to slow smarty autoloading
		}
	}

	if (in_array($base, $classmap)) {
		class_forwarder::load_old_name($base);
		return;
	}

	// standard classes
	$fp = $root.'class.'.$base.'.php';
	if (is_file($fp)) {
		if ($classmap === null) {
			$classmap = get_future_classes();
		}
		if (in_array($classname, $classmap)) {
			require_once $fp;
//			class_alias($classmap[$classname], $classname, false);
//			unset($classmap[$classname]); //no further need
			if (class_exists($classname, false)) return;
		} else {
			require_once $fp;
			if (class_exists($classname, false)) return;
		}
	}

	// standard internal classes - all are spaced
	// lowercase classes - all are renamed
	// lowercase internal classes - all are spaced

	// standard interfaces
	$fp = $root.'interface.'.$base.'.php';
	if (is_file($fp)) {
		require_once $fp;
		if (class_exists($classname, false)) return;
	}

	// internal interfaces
	$fp = $root.'internal'.DIRECTORY_SEPARATOR.'interface.'.$base.'.php';
	if (is_file($fp)) {
		require_once $fp;
		if (class_exists($classname, false)) return;
	}

	// standard tasks
	if (endswith($base, 'Task')) {
		$class = substr($base, 0, -4);
		$fp = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'tasks'.DIRECTORY_SEPARATOR.'class.'.$class.'.task.php';
		if (is_file($fp)) {
			require_once $fp;
			if (class_exists($classname, false)) return;
		}
	}

	// module classes
	$fp = cms_module_path($base);
	if ($fp) {
		//deprecated since 2.3 - some modules require existence of this, or assume, and actually use it
		$gCms = CmsApp::get_instance();
		require_once $fp;
		if (class_exists($classname, false)) return;
	}

	// unspaced module-ancillary classes
	// (the module need not be loaded - we might be installing, upgrading)
	foreach (cms_module_places() as $root) {
		$fp = $root.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'{class.,,interface.,trait.}'.$base.'.php';
		$files = glob($fp, GLOB_NOSORT | GLOB_NOESCAPE | GLOB_BRACE);
		if ($files) {
			require_once $files[0];
			if (class_exists($classname, false)) return;
		}
	}
}

spl_autoload_register('cms_autoloader');
