<?php
/*
An interface to define methods in multi-editor modules, supporting
rich-text (html) editing or syntax-highlight editing more generally.
Copyright (C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS;

/**
 * An interface to define methods in syntax-highlight text-editor modules.
 * @since 3.0
 * @package CMS
 * @license GPL
 */
interface IMultiEditor
{
	/**
	 * A CMSModule method, included here to ensure this mechanism for
	 * identifying capable modules.
	 * Return true when $capability is CMSMS\CoreCapabilities::WYSIWYG_MODULE
	 * or CMSMS\CoreCapabilities::SYNTAX_MODULE, as appropriate.
	 * @see CMSModule::HasCapability();
	 */
	public function HasCapability($capability, $params = []);

	// DO NOT DELETE OR CHANGE OR MOVE THIS LINE - INDICATES START OF API HELPTEXT

	/**
	 * Identify supported editor(s)
	 *
	 * @param bool $selectable Optional flag whether to populate returned
	 *  array-keys with (untranslated) editor names, for use e.g. in a selector
	 * @return array, key-sorted, each member like 'editorname'=>'modulename::editorname',
	 * where each (sub-)string is as recorded in the filesystem
	 */
	public function ListEditors() : array;

	/**
	 * Get data for constructing a help message for the editor generally
	 *
	 * @see IMultiEditor::GetMainHelp()
	 * @param string $editor Optional editor name/type (for modules supporting > 1 such editor)
	 *
	 * @return array: 2-members, [0] = help-realm or null, [1] = lang key for the realm
	 */
	public function GetMainHelpKey(string $editor = '') : array;

	/**
	 * Get help message for the editor generally e.g. a brief summary/description
	 *  and/or a link to a site with more detail
	 *
	 * @param string $editor Optional editor name/type (for modules supporting > 1 such editor)
	 *
	 * @return string [x]html suitable for a popup information-dialog
	 */
	public function GetMainHelp(string $editor = '') : string;

	/**
	 * Get data for constructing a help message for the editor generally
	 *
	 * @see IMultiEditor::GetMainHelp()
	 * @param string $editor Optional editor name/type (for modules supporting > 1 such editor)
	 *
	 * @return array: 2-members, [0] = help-realm or null [1] = lang key for the realm
	 */
	public function GetThemeHelpKey(string $editor = '') : array;

	/**
	 * Get help message for the editor themes, probably including a link to a site for
	 *  selection or evaluation
	 *
	 * @param string $editor Optional editor name/type (for modules supporting > 1 such editor)
	 *
	 * @return string [x]html suitable for a popup information-dialog
	 */
	public function GetThemeHelp(string $editor = '') : string;

	/**
	 * Get page content (css, js etc) needed for setup and operation of a text-editor
	 *
	 * @param string $editor editor name/type
	 * @param array $params  configuration details
	 *  Recognized members are:
	 * bool   'edit'   whether the content is editable. Default false (i.e. just for display)
	 * string 'handle' js variable (name) for the created editor. Default 'editor'
	 * string 'htmlid' id of the page-element whose content is to be edited. Default 'Editor'
	 *  (The element must be a type which can contain others e.g. <div/>, <p/>.)
	 * string 'style'  override for the normal editor theme/style.  Default ''
	 * string 'typer'  content-type identifier, an absolute filepath or at least
	 *   an extension or pseudo recognized by the editor (c.f. 'smarty'). Default ''
	 *
	 * @return array, up to 2 members 'head' and/or 'foot', being html and/or
	 *  javascript for inclusion in a page header or footer respectively
	 * The javascript includes (among other things) 3 functions:
	 *  seteditorcontent(t[,m]) to supply text t to the editor-object, and (optionally) set syntax type
	 *  geteditorcontent() to get text from the editor-object
	 *  setpagecontent(t) to put text t into the original form element (probably for submission to the server)
	 */
	public function GetEditorSetup(string $editor, array $params) : array;

	// DO NOT DELETE OR CHANGE OR MOVE THIS LINE - INDICATES END OF API HELPTEXT
} // interface
