<?php
/*
CoreTextEditing: a CMS Made Simple module enabling feature-rich editing of website text files.
Copyright (C) 2018-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\CoreCapabilities;
use CMSMS\MultiEditor;

class CoreTextEditing extends CMSModule implements MultiEditor
{
	/**
	 * @var array $editors
	 * Supported editors (in alpha-order) each member like 'Ace'=>'CoreTextEditing::Ace';
	 */
	public $editors = null;

	public function GetAdminDescription() { return $this->Lang('description'); }
	public function GetAuthor() { return 'Tom Phane'; }
	public function GetAuthorEmail() { return 'tomph@cmsmadesimple.org'; }
	public function GetChangeLog() { return ''.@file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.htm'); }
	public function GetFriendlyName() { return $this->Lang('friendlyname'); }
	public function GetName() { return 'CoreTextEditing'; }
	public function GetVersion() { return '0.7'; }
	public function HasAdmin() { return true; }
	public function IsAdminOnly() { return true; }
//	public function LazyLoadAdmin() { return true; }
//	public function LazyLoadFrontend() { return true; }
	public function MinimumCMSVersion() { return '2.8.900'; }
	public function VisibleToAdminUser() { return $this->CheckPermission('Modify Site Preferences'); }

	public function GetHelp()
	{
		$detail = 'Missing!';
		$text = @file_get_contents(cms_join_path(CMS_ROOT_PATH,'lib','classes','interface.MultiEditor.php'));
		if ($text) {
			$p = strpos($text, 'DO NOT DELETE OR CHANGE');
			if ($p !== false) {
				$ps = strpos($text, "\n", $p+10) + 1;
				$p = strpos($text, 'DO NOT DELETE OR CHANGE', $ps);
				$pe = strpos($text, "\n", $p-20);
				$extract = trim(substr($text,$ps, $pe-$ps), " \n\r");
				$detail = '<code>'.nl2br(htmlentities($extract)).'</code>';
			}
		}
		return $this->Lang('help_module', $detail);
	}

	public function HasCapability($capability, $params = [])
	{
		switch ($capability) {
			case CoreCapabilities::CORE_MODULE:
			case CoreCapabilities::SYNTAX_MODULE:
			case CoreCapabilities::SITE_PREFERENCER:
			case CoreCapabilities::USER_PREFERENCER:
				return true;
			default:
				return false;
		}
	}

	//TODO hook functions to populate 'centralised' site and user settings update

	/**
	 * Generate page-header content needed to run syntax-highlighter(s) in an admin page.
	 * Does nothing for frontend pages.
	 * This is a CMSModule method for SYNTAX_MODULE modules
	 * @return string always empty
	 */
	public function SyntaxGenerateHeader() //: string
	{
		$fe = CmsApp::get_instance()->is_frontend_request();
		if ($fe) {
			return '';
		}
		$themeObj = cms_utils::get_theme_object();
		if (!$themeObj) {
			return '';
		}
		/*
		 *  array $params  Configuration details. Recognized members are:
		 *  bool   'edit'   whether the content is editable. Default false (i.e. just for display)
		 *  string 'handle' js variable (name) for the created editor. Default 'editor'
		 *  string 'htmlclass' class of the page-element(s) whose content is to be edited. Default ''.
		 *  string 'htmlid' id of the page-element whose content is to be edited. Default 'richeditor'.
		 *  string 'theme'  override for the normal editor theme/style.  Default ''
		 *  //string 'workid' id of a div to be created to work on the content of htmlid-element. Default 'edit_work'
		 */
   		$params = [];
/*		if ($selector) {
			$params['htmlid'] = $selector;
		} else {
*/
			$params['htmlclass'] = 'textarea.'.$this->GetName();
//		}
		$params['edit'] = true; //TODO

		$val = cms_userprefs::get_for_user(get_userid(false), 'syntax_editor');
		if (!$val) {
			$val = cms_userprefs::get('syntax_editor');
			if (!$val) {
				$all = $this->ListEditors();
				$val = reset($all);
			}

		}

		$parts = explode('::', $val);
		$editor = isset($parts[1]) ? $parts[1] : $parts[0]; //TODO handle invalid module::editor

		$parts = $this->GetEditorSetup($editor, $params); //maybe empty

		if (!empty($parts['head'])) {
			$themeObj->add_headtext($parts['head']);
		}
		if (!empty($parts['foot'])) {
			$themeObj->add_footertext($parts['foot']);
		}
		return '';
	}

	// MultiEditor interface methods

	/**
	 * @param bool $selectable Optional flag whether to return assoc array. Default true.
	 * @return array
	 */
	public function ListEditors() : array
	{
		if ($this->editors === null) {
			$text = $this->GetName().'::';
			$names = [];
			$files = glob(__DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'editor.*.php', GLOB_NOSORT);
			foreach ($files as $fp) {
				$n = basename($fp, '.php');
				$editor = substr($n, 7); //strip prefix
				$names[$editor] = $text.$editor;
			}
			natcasesort($names); //caseless
			$this->editors = $names;
		}
		return $this->editors;
	}

	/* *
	 * @return array
	 */
/*    public function ShowEditors() : array
	{
		$names = $this->ListEditors();
		if ($names) {
			array_flip ($names);
			foreach ($names as $val => $editor) {
				$n = strtolower($editor);
				$names[$val] = $this->Lang($n.'_friendlyname');
			}
		}
		return $names;
	}
*/
	public function GetMainHelpKey(string $editor = '') : array
	{
		if ($editor) {
			$n = strtolower($editor);
			return [$this->GetName(), $n.'_helpmain'];
		}
		return [];
	}

	public function GetMainHelp(string $editor = '') : string
	{
		if ($editor) {
			$n = strtolower($editor);
			return $this->Lang($n.'_helpmain');
		}
		return '';
	}

	public function GetThemeHelpKey(string $editor = '') : array
	{
		if ($editor) {
			$n = strtolower($editor);
			return [$this->GetName(), $n.'_helptheme'];
		}
		return [];
	}

	public function GetThemeHelp(string $editor = '') : string
	{
		if ($editor) {
			$n = strtolower($editor);
			return $this->Lang($n.'_helptheme');
		}
		return '';
	}

	public function GetEditorSetup(string $editor, array $params) : array
	{
		$fp = __DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'editor.'.$editor.'.php';
		if (is_file($fp)) {
			require_once $fp;
			$fname = $this->GetName().'\\'.$editor.'\\GetPageSetup'; //namespaced func
			return $fname($this, $params);
		}
		return [];
	}
} // class
