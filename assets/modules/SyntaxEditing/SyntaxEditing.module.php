<?php
/*
SyntaxEditing: a CMS Made Simple module enabling feature-rich editing of website text files.
Copyright (C) 2018-2020 Tom Phane <tomph@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This file is free software; you can redistribute it and/or modify it
under the terms of the GNU Affero General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This file  is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.
<https://www.gnu.org/licenses/#AGPL>
*/

class SyntaxEditing extends CMSModule //implements CMSMS\MultiEditor 2.3 interface
{
    /**
     * @var array $editors
     * Supported editors (in alpha-order) each member like 'Ace'=>'SyntaxEditing::Ace';
     */
    public $editors = null;

    public function GetAdminDescription() { return $this->Lang('description'); }
    public function GetAuthor() { return 'Tom Phane'; }
    public function GetAuthorEmail() { return 'tomph@cmsmadesimple.org'; }
    public function GetChangeLog() { return ''.@file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.htm'); }
    public function GetFriendlyName() { return $this->Lang('friendlyname'); }
    public function GetName() { return 'SyntaxEditing'; }
    public function GetVersion() { return '0.7'; }
    public function HasAdmin() { return true; }
//    public function InstallPostMessage() { return $this->Lang('postinstall'); }
    public function IsAdminOnly() { return true; }
    public function LazyLoadAdmin() { return true; } //deprecated 2.3
    public function LazyLoadFrontend() { return true; } //ditto
    public function MinimumCMSVersion() { return '2.2'; }
//    public function UninstallPostMessage() { return $this->Lang('postuninstall'); }
//    public function UninstallPreMessage() { return $this->Lang('confirm_uninstall'); }
    public function VisibleToAdminUser() { return $this->CheckPermission('Modify Site Preferences'); }

    public function GetHeaderHTML()
    {
        $urlpath = $this->GetModuleURLPath();
        return '<link rel="stylesheet" type="text/css" href="'.$urlpath.'/lib/css/module.css" />';
    }

    public function GetHelp()
    {
        $detail = 'Missing!';
        $text = @file_get_contents(cms_join_path(CMS_ROOT_PATH,'lib','classes','interface.MultiEditor.php')); //TODO 2.3
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

    /**
     * Generate page-header content needed to run syntax-highlighter(s) in an admin page.
     * Does nothing for frontend pages.
     * A CMSModule method for SYNTAX_MODULE modules
	 * This might be called with argument(s), in which case they will be anonymous,
	 * and if not a single assoc. array then processed in order (to the extent that
	 * they are present), as follows:
        string 'editor'	name of editor to use e.g. 'Ace'. Default '' (hence the user-preferred or first-listed editor)
        string 'htmlclass' class of the page-element(s) whose content is to be edited. Default ''.
        string 'htmlid' id of the page-element whose content is to be edited. Default 'edit_area.
        string 'workid' id of a div to be created to work on the content of htmlid-element. Default 'edit_work'
        bool   'edit'   whether the content is editable. Default false (i.e. just for display)
        string 'handle' js variable (name) for the created editor. Default 'viewer|editor' per 'edit'
        string 'typer'  syntax identifier, file path|name or a recognised pseudo like 'smarty'. Default ''
        string 'theme'  the theme/style to use for the editor. Particularly needed
	      if the chosen editor is not the recorded-default.  Default ''
     * @return string always empty (i.e. no success indication)
     */
    public function SyntaxGenerateHeader() //: string
    {
        if (CmsApp::get_instance()->is_frontend_request()) {
            return '';
        }
        $themeObj = cms_utils::get_theme_object();
        if (!$themeObj) {
            return '';
        }

        // we can't change this method's API away from module-standard, so ...
        $arg_list = func_get_args();
        if ($arg_list) {
             $defs = [
                'editor' => '',
                'htmlclass' => '',
                'htmlid' => 'edit_area',
                'workid' => 'edit_work',
                'edit' => false,
                'handle' => 'viewer',
                'typer' => '',
                'theme' => '',
            ];
			if (is_array($arg_list[0])) {
				$params = $arg_list[0] + $defs;
			} else {
				$params = [];
				$keys = array_keys($defs);
				$vals = $arg_list + array_values($defs);
				foreach ($keys as $i => $k) {
				   $params[$k] = $vals[$i];
				}
			}
            if ($params['edit'] && $params['handle'] == 'viewer') {
                $params['handle'] == 'editor';
            }
        } else {
            $params = ['edit' => true]; //when called without params (the old API) it is always to edit the content
            $params['htmlclass'] = $this->GetName(); // not necessarily a textarea
        }

		if (!empty($params['editor'])) {
			$editor = $params['editor'];
			unset($params['editor']);
		} else {
			$val = cms_userprefs::get_for_user(\get_userid(false), 'syntax_editor');
			if (!$val) {
				$val = cms_userprefs::get('syntax_editor');
				if (!$val) {
					$all = $this->ListEditors();
					$val = reset($all);
				}
			}

			$parts = explode('::', $val);
			$editor = isset($parts[1]) ? $parts[1] : $parts[0]; //TODO handle invalid module::editor
		}

        $parts = $this->GetEditorSetup($editor, $params); //maybe empty

        if (!empty($parts['head'])) {
            $themeObj->add_headtext($parts['head']);
        }
        if (!empty($parts['foot'])) {
            $themeObj->add_footertext($parts['foot']);
        }
        return '';
    }

    protected function DefaultEditor($editor)
    {
        if (!$editor || $editor == $this->GetName()) {
            $all = $this->ListEditors();
            if (reset($all) !== false) {
                return key($all);
            } else {
                return '';
            }
        }
        return $editor;
    }

    // MultiEditor interface methods

    /**
     * @return array
     */
    public function ListEditors() //: array
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
/*    public function ShowEditors() //: array
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
    /**
     * @param string $editor Optional specific editor name e.g. 'Ace' or this module's classname. Default ''.
     * @return array
     */
    public function GetMainHelpKey($editor = '') //: array
    {
        $editor = $this->DefaultEditor($editor);
        if ($editor) {
            $n = strtolower($editor);
            return [$this->GetName(), $n.'_helpmain'];
        }
        return [];
    }

    /**
     * @param string $editor Optional specific editor name e.g. 'Ace' or this module's classname. Default ''.
     * @return string
     */
    public function GetMainHelp($editor = '') //: string
    {
        $editor = $this->DefaultEditor($editor);
        if ($editor) {
            $n = strtolower($editor);
            return $this->Lang($n.'_helpmain');
        }
        return '';
    }

    /**
     * @param string $editor Optional specific editor name e.g. 'Ace' or this module's classname. Default ''.
     * @return array
     */
    public function GetThemeHelpKey($editor = '') //: array
    {
        $editor = $this->DefaultEditor($editor);
        if ($editor) {
            $n = strtolower($editor);
            return [$this->GetName(), $n.'_helptheme'];
        }
        return [];
    }

    /**
     * @param string $editor Optional specific editor name e.g. 'Ace' or this module's classname. Default ''.
     * @return string
     */
    public function GetThemeHelp($editor = '') //: string
    {
        $editor = $this->DefaultEditor($editor);
        if ($editor) {
            $n = strtolower($editor);
            return $this->Lang($n.'_helptheme');
        }
        return '';
    }

    /**
     * Generic setup function
     * @param string $editor specific editor name e.g. 'Ace' or this module's classname
     * @param array $params
     * @return array
     */
    public function GetEditorSetup($editor, array $params) //: array
    {
        $editor = $this->DefaultEditor($editor);
        if ($editor) {
            $fp = __DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'editor.'.$editor.'.php';
            if (is_file($fp)) {
                require_once $fp;
                $fname = $this->GetName().'\\'.$editor.'\\GetPageSetup'; //namespaced func
                return $fname($this, $params);
            }
        }
        return [];
    }
}
