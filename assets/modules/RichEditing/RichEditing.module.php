<?php
/*
RichEditing: a CMS Made Simple module enabling WYSIWYG editing of website-page content.
Copyright (C) 2019-2020 Tom Phane <tomph@cmsmadesimple.org>
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

use CMSMS\CoreCapabilities;
use RichEditing\Utils;

class RichEditing extends CMSModule //implements CMSMS\RichEditor
{
    /**
     * @var array $editors
     * Supported editors (in alpha-order) each member like 'Trumbowyg'=>'RichEditing::Trumbowyg';
     */
    public $editors = null;

    public function GetAdminDescription() { return $this->Lang('description'); }
    public function GetAuthor() { return 'Tom Phane'; }
    public function GetAuthorEmail() { return 'tomph@cmsmadesimple.org'; }
    public function GetChangeLog() { return ''.@file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.htm'); }
    public function GetFriendlyName() { return $this->Lang('friendlyname'); }
    public function GetName() { return 'RichEditing'; }
    public function GetVersion() { return '0.2'; }
    public function HasAdmin() { return true; }
//    public function InstallPostMessage() { return $this->Lang('postinstall'); }
    public function IsAdminOnly() { return false; }
    public function LazyLoadAdmin() { return true; } //deprecated 2.3
    public function LazyLoadFrontend() { return true; } //ditto
    public function MinimumCMSVersion() { return '2.8.900'; }
//    public function UninstallPostMessage() { return $this->Lang('postuninstall'); }
//    public function UninstallPreMessage() { return $this->Lang('confirm_uninstall'); }
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
            case CoreCapabilities::WYSIWYG_MODULE:
            case CoreCapabilities::USER_PREFERENCER:
            case CoreCapabilities::SITE_PREFERENCER:
                return true;
            default:
                return false;
        }
    }

    //TODO hook functions to populate 'centralised' site and user settings update

    /**
     * Return page-header content (js and/or css) needed to use this WYSIWYG.
     * Module-API method, used during post-action page-processing and/or by cms_init_editor plugin.
     * @deprecated since 2.3 for admin-pages, instead use RichEditing::GetEditorSetup()
     *
     * @param string $selector Optional id of the single textarea element to be
	 *  displayed|edited. Default empty, hence 'textarea.RichEditing' (i.e. module name is the suffix)
     * @param string $css_name Optional name of a stylesheet to include.
     * @throws Exception, CmsException
     * @return string, empty for admin pages
     */
    public function WYSIWYGGenerateHeader($selector = '', $css_name = '')
    {
        return Utils::GeneratePageContent($this, $selector, $css_name);
    }

    // RichEditor interface methods

    /**
     * @param bool $selectable Optional flag whether to return assoc array. Default true.
     * @return array
     */
    public function ListEditors() //: array
    {
        if ($this->editors === null) {
            $text = $this->GetName().'::';
            $names = [];
            $files = glob(__DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.'editor-setup.php', GLOB_NOSORT);
            foreach ($files as $fp) {
                $editor = basename(dirname($fp));
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
     * @param string $editor Optional ... Default ''.
     * @return array
     */
    public function GetMainHelpKey($editor = '') //: array
    {
        if ($editor) {
            $n = strtolower($editor);
            return [$this->GetName(), $n.'_helpmain'];
        }
        return [];
    }

    /**
     * @param string $editor Optional ... Default ''.
     * @return string
     */
    public function GetMainHelp($editor = '') //: string
    {
        if ($editor) {
            $n = strtolower($editor);
            return $this->Lang($n.'_helpmain');
        }
        return '';
    }

    /**
     * @param string $editor Optional ... Default ''.
     * @return array
     */
    public function GetThemeHelpKey($editor = '') //: array
    {
        if ($editor) {
            $n = strtolower($editor);
            return [$this->GetName(), $n.'_helptheme'];
        }
        return [];
    }

    /**
     * @param string $editor Optional ... Default ''.
     * @return string
     */
    public function GetThemeHelp($editor = '') //: string
    {
        if ($editor) {
            $n = strtolower($editor);
            return $this->Lang($n.'_helptheme');
        }
        return '';
    }

    /**
     *
     * @param string $editor
     * @param array $params
     * @return array
     */
    public function GetEditorSetup($editor, array $params) //: array
    {
        $fp = __DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.$editor.DIRECTORY_SEPARATOR.'editor-setup.php';
        if (is_file($fp)) {
            require_once $fp;
            $fname = $this->GetName().'\\'.$editor.'\\GetPageSetup'; //namespaced func
            return $fname($this, $params);
        }
        return [];
    }
}
