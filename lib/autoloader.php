<?php
/*
Autoloader
Copyright (C) 2004-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you may redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\Lone;

/**
 * A function for auto-loading core- and module-related- CMSMS classes.
 *
 * @since 1.7
 * @internal
 * @ignore
 * @param string A possibly-namespaced class name Any leading '\' is ignored.
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
	$p = strpos($classname, '\\', 1);
	if ($p !== false) {
		// ignore any leading \
		if ($classname[0] === '\\') {
			$classname = substr($classname, 1);
			$p--;
		}
		$space = substr($classname, 0, $p);
		if ($space == 'CMSMS') {
			$sroot = $root;
		} elseif ($space == 'CMSAsset') {
			$sroot = CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR;
//		} elseif ($space == 'CMSResource') { maybe in future ...
//			$sroot = CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR;
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
		switch ($space) {
			case 'CMSMS':
			case 'CMSAsset':
//			case 'CMSResource':
				$sysp = true;
				break;
			default:
				$sysp = false;
		}
		foreach (['class.', 'trait.', 'interface.', ''] as $test) {
			$fp = $sroot.$test.$base.'.php';
			if (is_file($fp)) {
/* since 3.0 don't also autoload a module-object
				if (!($sysp || class_exists($space, false))) {
					//deprecated since 3.0 - some modules require existence of this, or assume, and actually use it
					$gCms = Lone::get('App');
					require_once $mpath;
				}
*/
				require_once $fp;
				if (class_exists($classname, false)) return;
			}
		}

		if (endswith($base, 'Task')) {
			if ($space == 'CMSMS') {
				$sroot = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'tasks'.DIRECTORY_SEPARATOR; // TODO redundant >> jobs
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

		// aliased classes
		$sroot = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'aliases'.DIRECTORY_SEPARATOR;
		foreach (['class.', 'interface.'] as $test) {
			$fp = $sroot.$test.$base.'.php';
			if (is_file($fp)) {
				require_once $fp;
				if (class_exists($classname, false)) return;
			}
		}
		return; //failed
	} elseif ($classname[0] === '\\') {
		return; //a 'foreign' namespace to be handled elsewhere TODO handle aliased module-class
	} else {
		$base = $classname;
	}

	if (strpos($base, 'Smarty') !== false) {
		if (strpos($base, 'CMS') === false) {
			return; //hand over ASAP to slow smarty autoloading
		}
	}

	// aliased classes
	$sroot = $root.'aliases'.DIRECTORY_SEPARATOR;
	foreach (['class.', 'interface.'] as $test) {
		$fp = $sroot.$test.$base.'.php';
		if (is_file($fp)) {
			require_once $fp;
			if (class_exists($classname, false)) return;
		}
	}

	// standard classes
	$fp = $root.'class.'.$base.'.php';
	if (is_file($fp)) {
		require_once $fp;
		if (class_exists($classname, false)) return;
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

/*	// pre-3.0 standard tasks
	if (endswith($base, 'Task')) {
		$fp = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'tasks'.DIRECTORY_SEPARATOR.'class.'.$base.'.php';
		if (is_file($fp)) {
			require_once $fp;
			if (class_exists($classname, false)) return;
		}
		$class = substr($base, 0, -4);
		$fp = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'tasks'.DIRECTORY_SEPARATOR.'class.'.$class.'.task.php';
		if (is_file($fp)) {
			require_once $fp;
			if (class_exists($classname, false)) return;
		}
	}
*/
	// In theory at least, module-classes may be 'permanently' aliased
	// (with their details in AppParams), and aliases retrieved via
	//   ModuleOperations->get_module_classname($modname)
	// This capability was never announced ? Probably never used ...

	// module classes
	$fp = cms_module_path($base);
	if ($fp) {
		//deprecated since 3.0 - some modules require existence of this, or assume, and actually use it
		$gCms = Lone::get('App');
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
		if (endswith($base, 'Task')) { // deprecated since 3.0 instead use Jobs
			$class = substr($base, 0, -4);
			$fp = $root.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'{class.,}'.$class.'.task.php';
			$files = glob($fp, GLOB_NOSORT | GLOB_NOESCAPE | GLOB_BRACE);
			if ($files) {
				require_once $files[0];
				if (class_exists($classname, false)) return;
			}
		}
	}
}

spl_autoload_register('cms_autoloader');
