<?php
/*
CoreTextEditing: a CMS Made Simple module enabling feature-rich editing of website text files.
Copyright (C) 2018-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

class CoreTextEditing extends CMSModule implements CMSMS\SyntaxEditor
{
    /**
     * Default cdn URL for retrieving Ace text-editor code
     */
    const ACE_CDN = 'https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.3';

    /**
     * Default theme/style for Ace text-editor
     */
    const ACE_THEME = 'clouds';

    /**
     * Default cdn URL for retrieving CodeMirror text-editor code
     */
    const CM_CDN = 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.44.0';

    /**
     * Default theme/style for CodeMirror text-editor
     */
    const CM_THEME = 'elegant';

    /**
     * Supported editors (in display-order) (NB maybe some bug during install if this is before the other const's)
     */
    const EDITORS = ['Ace', 'CodeMirror'];

	public function GetAdminDescription() { return $this->Lang('description'); }
	public function GetAuthor() { return 'Tom Phane'; }
	public function GetAuthorEmail() { return 'tomph@cmsmadesimple.org'; }
	public function GetChangeLog() { return ''.@file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.htm'); }
	public function GetFriendlyName() { return $this->Lang('friendlyname'); }
	public function GetName() { return 'CoreTextEditing'; }
	public function GetVersion() { return '0.6'; }
	public function HasAdmin() { return true; }
	public function IsAdminOnly() { return true; }
//	public function LazyLoadAdmin() { return true; }
//	public function LazyLoadFrontend() { return true; }
	public function MinimumCMSVersion() { return '2.2.910'; }
	public function VisibleToAdminUser() { return $this->CheckPermission('Modify Site Preferences'); }

	public function GetHelp()
	{
		$detail = 'Missing!';
		$text = @file_get_contents(cms_join_path(CMS_ROOT_PATH,'lib','classes','interface.SyntaxEditor.php'));
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
		return $capability == CmsCoreCapabilities::SYNTAX_MODULE;
	}

	// interface methods

	public function ListEditors(bool $selectable = true) : array
	{
		$me = $this->GetName().'::';
		$names = [];
		foreach (self::EDITORS as $editor) {
		    if (is_file(__DIR__.DIRECTORY_SEPARATOR.'editor.'.$editor.'.php')) {
				if ($selectable) {
					$names[$editor] = $me.$editor;
				} else {
					$names[] = $me.$editor;
				}
			}
		}
		return $names;
	}

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

	public function GetEditorScript(string $editor, array $params) : array
	{
		$fp = __DIR__.DIRECTORY_SEPARATOR.'editor.'.$editor.'.php';
		if (is_file($fp)) {
			include $fp;
			return GetScript($this, $params);
		}
		return '';
	}
} // class
