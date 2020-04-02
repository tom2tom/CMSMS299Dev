<?php
/*
RichEditing module utility-methods class
Copyright (C) 2019-2020 Tom Phane <tomph@cmsmadesimple.org>
This file is a component of the RichEditing module for CMS Made Simple
 <http://dev.cmsmadesimple.org/projects/richedit>

This file is free software; you can redistribute it and/or modify it
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This file is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.
<https://www.gnu.org/licenses/#AGPL>
*/
namespace RichEditing;

use CmsApp;
use cms_siteprefs;
use cms_userprefs;
use RichEditing as Modclass;
//use function get_userid; 2.3

class Utils
{
    // static properties here >> StaticProperties class ?
	// intra-request cache of used lang data, each member like $editor => [$lang,$scriptpath]
	private static $langscripts = [];

	/**
	 * @param string $editor editor type/name
	 * @param string $wanted translation-identifier used by CMSMS
	 * @param string $langsdir absolute path of lang-files folder, no trailing separator
	 * @param string $suffix Optional filename suffix. Default '.js'
	 * @return 2-member array:
	 * [0] = string lang identifier usable by editor or ''
	 * [1] = string lang-file absolute path or ''
	 */
	public static function GetLangData($editor, $wanted, $langsdir, $suffix = '.js')
	{
		if (empty(self::$langscripts[$editor])) {
			$ifiles = glob($langsdir.DIRECTORY_SEPARATOR.'*'.$suffix);
			$list = [];
			$pieces = [];
			foreach ($ifiles as $fp) {
				$key = basename($fp, $suffix);
				$list[$key] = $fp;
				preg_match('~^([a-z]{2})[-_]?([a-z]{2})?([\w.\-]*)?$~i', $key, $matches);
				$x = '';
				foreach ([1,2,3] as $i) {
					$s = $matches[$i];
					if ($s !== '') {
						$x .= strtolower($s);
						if (!isset($pieces[$x])) {
							$pieces[$x] = $key;
						}
					}
				}
			}

			preg_match('~^([a-z]{2})[-_]?([a-z]{2})?([\w.\-]*)?$~i', $wanted, $matches);
			$checks = array_map('strtolower', $matches);
			$x = $checks[1].$checks[2].$checks[3];
			if (isset($pieces[$x])) {
				$fp = $list[$pieces[$x]];
			} else {
				$x = $checks[1].$checks[2];
				if (isset($pieces[$x])) {
					$fp = $list[$pieces[$x]];
				} else {
					$x = $checks[1];
					if (isset($pieces[$x])) {
						$fp = $list[$pieces[$x]];
					} else {
						$fp = '';
					}
				}
			}

			if ($fp !== '') {
				$lang = basename($fp, $suffix);
				self::$langscripts[$editor] = [$lang, $fp];
			} else {
				self::$langscripts[$editor] = ['', ''];
			}
		}
		return self::$langscripts[$editor];
	}

	/**
	 * Return page-content (js and/or css) needed to use a richtext editor aka WYSIWYG.
	 * Supports module-API method, used during post-action page-processing and/or by cms_init_editor plugin.
	 * @deprecated since 2.3 for admin-page use
	 *
	 * @param RichEditing $mod The class module
	 * @param string $selector Optional id of the textarea element to be displayed|edited
	 *  Default textarea.RichEditing (i.e. module name is the suffix)
	 * @param string $css_name Optional name of a stylesheet to include.
	 *   TODO If $selector is not empty then $cssname is only used for that specific element.
	 *   TODO $cssname might be ignored, depending on module settings and capabilities.
	 * @throws Exception, CmsException
	 * @return string, empty for admin pages
	 */
	public static function GeneratePageContent(Modclass $mod, $selector = '', $css_name = '') //: string
	{
		/*
		 *  array $params  Configuration details. Recognized members are:
		 *  bool   'edit'   whether the content is editable. Default false (i.e. just for display)
         *  bool   'frontend' whether the editor is being used in a frontend page. Default false.
		 *  string 'handle' js variable (name) for the created editor. Default 'editor'
		 *  string 'htmlclass' class of the page-element(s) whose content is to be edited. Default ''.
		 *  string 'htmlid' id of the page-element whose content is to be edited. Default 'richeditor'.
		 *  string 'theme'  override for the normal editor theme/style.  Default ''
		 *  //string 'workid' id of a div to be created to work on the content of htmlid-element. Default 'edit_work'
		 */
   		$params = [];
		if ($selector) {
    		$params['htmlid'] = $selector;
		} else {
			$params['htmlclass'] = 'textarea.'.$mod->GetName();
		}
		$params['edit'] = true; //TODO
		//TODO $params[] for $css_name

		$params['frontend'] = $fe = CmsApp::get_instance()->is_frontend_request();
		if ($fe) {
			$editor = cms_siteprefs::get('frontendwysiwyg'); //TODO already know module, want editor-type here
		} else {
			$uid = \get_userid(false);
			$editor = cms_userprefs::get_for_user($uid, 'wysiwyg_type');
		}
		if (!$editor) {
			$all = $mod->ListEditors();
			$editor = reset($all);
		}
		if (!$fe) {
			$params['theme'] = cms_userprefs::get_for_user($uid, 'wysiwyg_theme');
		}

		$parts = $mod->GetEditorSetup($editor, $params); //maybe empty
		if (!$parts) { return ''; }

		if ($fe) {
			if (!empty($parts['head'])) {
				$out = $parts['head'];
			} else {
				$out = '';
			}
			if (!empty($parts['foot'])) {
				if ($out) {
					$out .= "\n";
				}
				$out .= $parts['foot'];
			}
			return $out."\n";
		} else {
			if (!empty($parts['head'])) {
				add_page_headtext($parts['head']);
			}
			if (!empty($parts['foot'])) {
				add_page_foottext($parts['foot']);
			}
			return '';
   		}
	}
}
