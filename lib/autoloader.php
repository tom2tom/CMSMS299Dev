<?php
#autoloader for CMS Made Simple <http://www.cmsmadesimple.org>
#Copyright (C) 2004-2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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
 * @package CMS
 * @ignore
 */

/**
 * @ignore
 * @ since 2.3 spaced and/or renamed 'core' CMSMS classes
 * @ deprecated, remove this in a decade or so ...
 */
static $class_replaces = null;

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
	global $class_replaces;

	$root = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR;

	// standard content types (prioritized)
	$fp = $root.'contenttypes'.DIRECTORY_SEPARATOR.'class.'.$classname.'.php';
	if (is_file($fp)) {
		require_once $fp;
		return;
	}

	if ($class_replaces === null) {
		$class_replaces = [
/*
'cms_cache_driver' => 'CMSMS\CacheDriver',
'cms_cache_handler' => 'CMSMS\CacheHandler',
'cms_config' => 'CMSMS\Config',
'cms_content_tree' => 'CMSMS\ContentTree',
'cms_cookies' => 'CMSMS\Cookies',
'cms_filecache_driver' => 'CMSMS\FilecacheDriver',
'cms_http_request' => 'CMSMS\HttpRequest',
'cms_route_manager' => 'CMSMS\RouteManager',
'cms_siteprefs' => 'CMSMS\Siteprefs',
'cms_tree' => 'CMSMS\Tree',
'cms_tree_operations' => 'CMSMS\TreeOperations',
'cms_url' => 'CMSMS\Url',
'cms_userprefs' => 'CMSMS\Userprefs',
'cms_utils' => 'CMSMS\Utils',
//mebbe not these
'CmsApp' => 'CMSMS\App',
'CMSModule' => 'CMSMS\Module',
'CMSModuleContentType' => 'CMSMS\ModuleContentType',
*/
		];
	}

	$o = ($classname[0] != '\\') ? 0 : 1;
	$p = strpos($classname, '\\', $o + 1);
	if ($p !== false) {
		$space = substr($classname, $o, $p - $o);
		if ($space == 'CMSMS') {
			$path = substr($classname, $o); //ignore any leading \
			$old = array_search($path, $class_replaces);
			if ($old !== false) {
				$fp = $root.'class.'.$old.'.php';
				require_once $fp;
				class_alias($old, $class_replaces[$old], false);
				unset($class_replaces[$old]); //no repeats
				return;
			}
			$sroot = $root;
		} else {
			if (!class_exists($space, false)) { //CHECKME nested autoload ok here?
				return;
			}
            //do not require module to be loaded & current (we might be installing, upgrading)
			$path = cms_module_path($space);
			if ($path) {
				$sroot = dirname($path).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR;
			} else {
				return;
			}
		}
		$path = str_replace('\\', DIRECTORY_SEPARATOR, substr($classname, $p + 1));
		$classname = basename($path);
		$path = dirname($path);
		if ($path != '.') {
			$sroot .= $path.DIRECTORY_SEPARATOR;
		}
		foreach (['class.', 'trait.', 'interface.', ''] as $test) {
			$fp = $sroot.$test.$classname.'.php';
			if (is_file($fp)) {
				require_once $fp;
				return;
			}
		}

		if (endswith($classname, 'Task')) {
			if ($space == 'CMSMS') {
				$sroot = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'tasks'.DIRECTORY_SEPARATOR;
			}
			$t2 = substr($classname, 0, -4).'.task';
			foreach (['class.', 'trait.', 'interface.', ''] as $test) {
				$fp = $sroot.$test.$t2.'.php';
				if (is_file($fp)) {
					require_once $fp;
					return;
				}
				$fp = $sroot.$test.$classname.'.php';
				if (is_file($fp)) {
					require_once $fp;
					return;
				}
			}
		}
		return; //failed
	} elseif ($o) {
		return;
	}

	if (strpos($classname, 'Smarty') !== false) {
		if (strpos($classname, 'CMS') === false) {
			return; //hand over to smarty autoloader
		}
	}

	// standard classes
	$fp = $root.'class.'.$classname.'.php';
	if (is_file($fp)) {
		if (array_key_exists($classname, $class_replaces)) {
			require_once $fp;
			class_alias($classname, $class_replaces[$classname], false);
			unset($class_replaces[$classname]); //no repeats
			return;
		}
		require_once $fp;
		return;
	}

	// standard internal classes - all are spaced
	// lowercase classes - all are renamed
	// lowercase internal classes - all are spaced

	// standard interfaces
	$fp = $root.'interface.'.$classname.'.php';
	if (is_file($fp)) {
		require_once $fp;
		return;
	}

	// internal interfaces
	$fp = $root.'internal'.DIRECTORY_SEPARATOR.'interface.'.$classname.'.php';
	if (is_file($fp)) {
		require_once $fp;
		return;
	}

	// standard tasks
	if (endswith($classname, 'Task')) {
		$class = substr($classname, 0, -4);
		$fp = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'tasks'.DIRECTORY_SEPARATOR.'class.'.$class.'.task.php';
		if (is_file($fp)) {
			require_once $fp;
			return;
		}
	}

	// module classes
	$modops = ModuleOperations::get_instance();
	$fp = $modops->get_module_filename($classname);
	if ($fp && is_file($fp)) {
		//deprecated - some modules require existence of this, or assume, and actually use it
		$gCms = CmsApp::get_instance();
		require_once $fp;
		return;
	}

	// unspaced module-ancillary classes
	// (the module need not be loaded - we might be installing, upgrading)
	foreach (cms_module_places() as $root) {
		$fp = $root.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'{class.,,interface.,trait.}'.$classname.'.php';
		$files = glob($fp, GLOB_NOSORT | GLOB_NOESCAPE | GLOB_BRACE);
		if ($files) {
			require_once $files[0];
			return;
		}
	}
}

spl_autoload_register('cms_autoloader');

