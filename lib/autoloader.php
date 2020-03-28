<?php
#autoloader for CMS Made Simple <http://www.cmsmadesimple.org>
#Copyright (C) 2004-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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
 * A function for auto-loading classes.
 *
 * @since 1.7
 * @internal
 * @ignore
 * @param string A possibly-namespaced class name
 */
function cms_autoloader(string $classname)
{
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
		$space = substr($classname, $o, $p - $o);
		if ($space == 'CMSMS') {
		    // type-declarations don't trigger autoloading, so all renamed/respaced classes
		    // which might be used in a typehint must be pre-aliased (loaded) every request (BAH!)
		    // or else not actually changed until after reasonable advance notice to coders.
		    // Hence: future re-classes which may be used now ...
			static $class_replaces = null;
			if ($class_replaces === null) {
				$class_replaces = [
				'CMSMS\AdminMenuItem' => 'CmsAdminMenuItem',
				'CMSMS\AppData' => 'CmsApp',
				'CMSMS\Async\JobOperations' => 'CMSMS\Async\JobManager',
				'CMSMS\CacheHandler' => 'cms_cache_handler',
				'CMSMS\Config' => 'cms_config',
				'CMSMS\ContentTree' => 'cms_content_tree',
				'CMSMS\Cookies' => 'cms_cookies',
				'CMSMS\CoreCapabilities' => 'CmsCoreCapabilities',
				'CMSMS\DbQueryBase' => 'CmsDbQueryBase',
				'CMSMS\FileSystemProfile' => 'CMSMS\FilePickerProfile',
				'CMSMS\HookOperations' => 'CMSMS\HookManager',
				'CMSMS\HttpRequest' => 'cms_http_request',
				'CMSMS\internal\AdminThemeNotification' => 'CmsAdminThemeNotification',
				'CMSMS\LanguageDetector' => 'CmsLanguageDetector',
				'CMSMS\RegularTask' => 'CmsRegularTask', // interface
				'CMSMS\Stylesheet' => 'CmsLayoutStylesheet',
				'CMSMS\StylesheetQuery' => 'CmsLayoutStylesheetQuery',
				'CMSMS\Template' => 'CmsLayoutTemplate',
				'CMSMS\TemplatesGroup' => 'CmsLayoutTemplateCategory',
				'CMSMS\TemplateQuery' => 'CmsLayoutTemplateQuery',
				'CMSMS\TemplateType' => 'CmsLayoutTemplateType',
				'CMSMS\Module' => 'CMSModule',	//mebbe not this one ?
				'CMSMS\ModuleContentType' => 'CMSModuleContentType',
				'CMSMS\Permission' => 'CmsPermission',
				'CMSMS\Route' => 'CmsRoute',
				'CMSMS\Siteprefs' => 'cms_siteprefs',
				'CMSMS\Tree' => 'cms_tree',
				'CMSMS\TreeOperations' => 'cms_tree_operations',
				'CMSMS\Url' => 'cms_url',
				'CMSMS\Userprefs' => 'cms_userprefs',
				'CMSMS\Utils' => 'cms_utils',
				];
			}

			$path = substr($classname, $o); //ignore any leading \
			$old = $class_replaces[$path] ?? null;
			if ($old !== null) {
				$fp = $root.'class.'.$old.'.php';
				require_once $fp;
				class_alias($old, $classname, false);
				return;
			}
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
		$path = str_replace('\\', DIRECTORY_SEPARATOR, substr($classname, $p + 1));
		$base = basename($path);
		$path = dirname($path);
		if ($path != '.') {
			$sroot .= $path.DIRECTORY_SEPARATOR;
		}
		$sysp = ($space == 'CMSMS' || $space == 'CMSAsset' || $space == 'CMSResource');
		foreach (['class.', 'trait.', 'interface.', ''] as $test) {
			$fp = $sroot.$test.$base.'.php';
			if (is_file($fp)) {
				if (!($sysp || class_exists($space, false))) {
					//deprecated since 2.3 - some modules require existence of this, or assume, and actually use it
					$gCms = CmsApp::get_instance();
					require_once $mpath;
				}
				require_once $fp;
				return;
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
					return;
				}
				$fp = $sroot.$test.$base.'.php';
				if (is_file($fp)) {
					require_once $fp;
					return;
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
			return; //hand over to smarty autoloader
		}
	}

	// standard classes
	$fp = $root.'class.'.$base.'.php';
	if (is_file($fp)) {
/* FUTURE
		if (isset($class_replaces[$classname])) {
			require_once $fp;
			class_alias($classname, $class_replaces[$classname], false);
			unset($class_replaces[$classname]); //no repeats
			return;
		}
*/
		require_once $fp;
		if (class_exists($classname)) return;
	}

	// standard internal classes - all are spaced
	// lowercase classes - all are renamed
	// lowercase internal classes - all are spaced

	// standard interfaces
	$fp = $root.'interface.'.$base.'.php';
	if (is_file($fp)) {
		require_once $fp;
		if (class_exists($classname)) return;
	}

	// internal interfaces
	$fp = $root.'internal'.DIRECTORY_SEPARATOR.'interface.'.$base.'.php';
	if (is_file($fp)) {
		require_once $fp;
		if (class_exists($classname)) return;
	}

	// standard tasks
	if (endswith($base, 'Task')) {
		$class = substr($base, 0, -4);
		$fp = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'tasks'.DIRECTORY_SEPARATOR.'class.'.$class.'.task.php';
		if (is_file($fp)) {
			require_once $fp;
			if (class_exists($classname)) return;
		}
	}

	// module classes
	$fp = cms_module_path($base);
	if ($fp) {
		//deprecated since 2.3 - some modules require existence of this, or assume, and actually use it
		$gCms = CmsApp::get_instance();
		require_once $fp;
		if (class_exists($classname)) return;
	}

	// unspaced module-ancillary classes
	// (the module need not be loaded - we might be installing, upgrading)
	foreach (cms_module_places() as $root) {
		$fp = $root.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'{class.,,interface.,trait.}'.$base.'.php';
		$files = glob($fp, GLOB_NOSORT | GLOB_NOESCAPE | GLOB_BRACE);
		if ($files) {
			require_once $files[0];
			if (class_exists($classname)) return;
		}
	}
}

spl_autoload_register('cms_autoloader');
