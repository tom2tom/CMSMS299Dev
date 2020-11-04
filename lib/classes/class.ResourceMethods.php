<?php
/*
Class of not-often-used methods included on-demand by 'light' modules.
Copyright (C) 2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you may redistribute it and/or modify it
under the terms of the GNU General Public License as published by the
Free Software Foundation; either version 2 of that license, or (at your option)
any later version.

CMS Made Simple is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS;

use CMSMS\AdminMenuItem;
use CMSMS\AppParams;
use CMSMS\AppSingle;
use CMSMS\Crypto;
use CMSMS\LangOperations;
use CMSMS\RequestParameters;
use LogicException;
use ReflectionClass;
use ReflectionException;
use Throwable;
use const CMS_ROOT_URL;
use function check_permission;
use function cms_htmlentities;
use function cms_path_to_url;
use function endswith;
use function get_userid;
use function startswith;

class ResourceMethods
{
	protected $mod;
	protected $modpath;

	public function __construct($mod, $modpath)
	{
		$this->mod = $mod;
		$this->modpath = $modpath;
	}

	public function __call($name, $args)
	{
		if (method_exists($this->mod, $name)) {
			return call_user_func([$this->mod, $name], ...$args);
		}
		if (strncmp($name, 'Create', 6) == 0) {
			//maybe it's a now-removed form-element call
			// static properties here >> StaticProperties class ?
			static $flect = null;

			if ($flect === null) {
				$flect = new ReflectionClass('CMSMS\\FormTags');
			}
			try {
				$md = $flect->getMethod($name);
			} catch (ReflectionException $e) {
				return;
			}

			$parms = [];
			foreach ($md->getParameters() as $i => $one) {
				$val = $args[$i] ?? (($one->isOptional()) ? $one->getDefaultValue() : '!oOpS!');
				$parms[$one->getName()] = $val;
			}
			return FormUtils::create($this, $name, $parms);
		}
	}

	// methods required/accessed by the module-manager module, but whose result can be ignored
	public function AdminStyle() {}
	public function AllowUninstallCleanup() {}
	public function GetAuthor() {}
	public function GetAuthorEmail() {}
	public function GetChangeLog() {}
	public function GetDescription() {}
	public function GetHeaderHTML() {}
	public function GetHelp() {}
	public function SuppressAdminOutput() {}
	public function UninstallPostMessage() {}

	// default versions of methods required/accessed by the module-manager module

	public function get_tasks()
	{
		return false;
	}

	public function GetModulePath() : string
	{
		return $this->modpath;
	}

	public function GetModuleURLPath() : string
	{
		return cms_path_to_url($this->modpath);
	}

	public function CheckPermission(...$perms) : bool
	{
		$userid = get_userid(false);
	    return ($userid) ? check_permission($userid, ...$perms) : false;
	}

	// TODO arguments $targetcontentonly, $prettyurl are ignored ATM
	public function create_url($id, $action, $returnid = null, $params = [],
		$inline = false, $targetcontentonly = false, $prettyurl = '', $format = 0) : string
	{
		$id = chr(mt_rand(97, 122)) . Crypto::random_string(3, true);
		$parms = [
		'module' => $this->GetName(),
		'id' => $id,
		'action' => $action,
		'inline' => ($inline) ? 1 : 0,
		];
		$ignores = ['assign', 'returnid', 'module', 'id', 'action', 'inline', ];
		foreach ($params as $key => $val) {
			if (!in_array($key, $ignores)) {
				$parms[$key] = $val;
			}
		}

		if (is_numeric($returnid)) {
			$text = CMS_ROOT_URL . '/index.php?';
		} else {
			$text = CMS_ROOT_URL . '/lib/moduleinterface.php?';
		}
		$text .= RequestParameters::create_action_params($parms, $format);
		if ($format == 3) {
			$text = cms_htmlentities($text, ENT_QUOTES | ENT_SUBSTITUTE, '');
		}
		return $text;
	}

	public function DoAction(string $action, $id, array $params)
	{
		$params['id'] = $id;
		$params['action'] = $action;
		return $this->Run($params);
	}

	public function DoActionBase(string $action, $id, array $params, $returnid, $smartob)
	{
		$params['id'] = $id;
		$params['action'] = $action;
		$params['returnid'] = $returnid;
		return $this->Run($params);
	}

	public function GetAdminMenuItems()
	{
		if ($this->mod->VisibleToAdminUser()) {
			return [AdminMenuItem::from_module($this)];
		}
	}

	public function GetName() : string
	{
		$name = get_class($this->mod);
		$p = strrpos($name, '\\');
		if ($p !== false) {
			return substr($name, $p+1);
		}
		return $name;
	}

	public function GetPreference(string $name = '', $def = '')
	{
		$pref = $this->GetName().AppParams::NAMESPACER;
		if ($name) {
			return AppParams::getraw($pref.$name, $def); //get NOT getraw ??
		}
		$params = AppParams::getraw($pref, '', true);
		if ($params) {
			$keys = array_keys($params);
			array_walk($keys, function(&$value, $indx, $skip) {
				$value = substr($value, $skip);
			}, strlen($pref));
			return array_combine($keys, array_values($params));
		}
		return [];
	}

	public function GetTemplateObject(string $tpl_name)
	{
		if (strpos($tpl_name,':') === false) {
			if (endswith($tpl_name,'.tpl')) {
				$resource = 'module_file_tpl:'.$this->GetName().';'.$tpl_name;
			} else {
				$resource = 'cms_template:'.$tpl_name;
			}
		} elseif (startswith($tpl_name,'string:') ||
			startswith($tpl_name,'eval:') ||
			startswith($tpl_name,'extends:')) {
			throw new LogicException('Invalid smarty resource specified for a module template');
		} else {
			$resource = $tpl_name;
		}

		$smarty = AppSingle::Smarty();
		$tpl = $smarty->createTemplate($resource); //, null, null, $smarty);
		$tpl->assign([
			'mod' => $this->mod, //or just $this ?
			'_module' => $this->GetName(),
		]);
		return $tpl;
	}

	public function InitializeAdmin() {}
	public function InitializeFontend() {}

	public function Install()
	{
		$fp = $this->modpath.DIRECTORY_SEPARATOR.'method.install.php';
		if (is_file($fp)) {
			$gCms = AppSingle::App();
			$db = AppSingle::Db();
			$config = AppSingle::Config();
			//TODO other in-scope vars?
			//$smarty = AppSingle::Smarty(); c.f. CMSModule::Install()
			$res = include_once $fp;
			return ($res && $res !== 1) ? $res : false;
		}
		return false;
	}

	public function InstallPostMessage() {}

	public function Lang(...$args) //: string|null
	{
		return LangOperations::lang_from_realm($this->GetName(), ...$args);
	}

	public function RemovePreference(string $name = '', bool $like = false)
	{
		$pref = $this->GetName().AppParams::NAMESPACER;
		$args = ($name) ? [$pref.$name, $like] : [$pref, true];
		AppParams::remove(...$args);
	}

	public function Run(array $params)
	{
		$name = $params['action'] ?? null;
		if ($name) {
			unset($params['action']);
			// intra-class method-calls will divert to $this->mod if needed and possible
			// sort out relevant variables c.f. module-action in-scope vars
			$id = $params['id'] ?? '';
			$returnid = $params['returnid'] ?? 0;
			$gCms = AppSingle::App();
			$db = AppSingle::Db();
			$config = AppSingle::Config();
			$smarty = AppSingle::Smarty();
			$uuid = $gCms->GetSiteUUID(); //since 2.9
			try {
				ob_start();
				$result = include $this->modpath.DIRECTORY_SEPARATOR.'action.'.$name.'.php';
				if( $result === 1 ) { $result = ''; } // ignore PHP's 'successful inclusion' indicator
				elseif( !($result || is_numeric($result)) ) { $result = ''; }
				if( $result !== '' ) { echo $result; }
				$result = ob_get_clean();
				return $result;
			} catch (Throwable $t) {
				//TODO handle error better
				return '<p style="color:red">Error: '.$t->getMessage().'</p>';
			}
		}
		$name = $params['call'] ?? null;
		if ($name) {
			try {
				unset($params['call']);
				return call_user_func_array($name, $params);
			} catch (Throwable $t) {
				//TODO handle error better
				return '<p style="color:red">Error: '.$t->getMessage().'</p>';
			}
		}
	}

	public function SetPreference(string $name, $val)
	{
		return AppParams::set($this->GetName().AppParams::NAMESPACER.$name, $val);
	}

	public function Uninstall()
	{
		$fp = $this->modpath.DIRECTORY_SEPARATOR.'method.uninstall.php';
		if (is_file($fp)) {
			$gCms = AppSingle::App();
			$db = AppSingle::Db();
			$config = AppSingle::Config();
			//TODO other in-scope vars?
			//$smarty = AppSingle::Smarty(); c.f. CMSModule::Uninstall()
			$res = include_once $fp;
			return ($res && $res !== 1) ? $res : false;
		}
		return false;
	}

	public function UninstallPreMessage() {}

	public function Upgrade($oldversion, $newversion)
	{
		$fp = $this->modpath.DIRECTORY_SEPARATOR.'method.upgrade.php';
		if (is_file($fp)) {
			$gCms = AppSingle::App();
			$db = AppSingle::Db();
			$config = AppSingle::Config();
			// TODO other in-scope vars?
			//$smarty = AppSingle::Smarty(); c.f. CMSModule::Upgrade()
			$res = include_once $fp;
			return ($res && $res !== 1) ? $res : false;
		}
		return false;
	}
}
