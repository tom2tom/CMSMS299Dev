<?php
/*
An interface to define methods in syntax-highlight text-editor modules.
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

namespace CMSMS;

/**
 * An interface to define methods in syntax-highlight text-editor modules.
 * @since 2.3
 * @package CMS
 * @license GPL
 */

interface SyntaxEditor
{
    /**
     * A CMSModule method, included here to ensure this mechanism for identifying
     * syntax-capable modules.
     * Return true when $capability is CmsCoreCapabilities::SYNTAX_MODULE.
     * @see CMSModule::HasCapability();
     */
    public function HasCapability($capability, $params = []);

    // DO NOT DELETE OR CHANGE OR MOVE THIS LINE - INDICATES START OF API HELPTEXT

	/**
     * Identify supported editor(s)
     *
	 * @param bool $selectable Optional flag whether to populate returned
	 *  array-keys with UI-friendly identifiers, for use e.g. in a selector
	 * @return array
	 */
	public function ListEditors(bool $selectable = true) : array;

	/**
	 * Get data for constructing a help message for the editor generally
	 *
	 * @see SyntaxEditor::GetMainHelp()
	 * @param string $editor Optional editor name/type (for modules supporting > 1 such editor)
	 *
	 * @return array: 2-members, [0] = help-realm or null [1] = lang key for the realm
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
	 * @see SyntaxEditor::GetMainHelp()
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
	 * Get javascript for initialization of a text-editor
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
	 * @return array, up to 2 members 'head' and/or 'foot', string(s) for inclusion
	 *  in a page header or footer respectively - to provide relevant css, javascript
	 * The js includes (among other things) 3 functions:
	 *  seteditorcontent(v[,m]) to supply text to the editor, and (optionally) set syntax type
	 *  geteditorcontent() to get text from the editor
	 *  setpagecontent(v) to put text into the original form element (probably for submission to the server)
	 */
	public function GetEditorScript(string $editor, array $params) : array;

    // DO NOT DELETE OR CHANGE OR MOVE THIS LINE - INDICATES END OF API HELPTEXT
} // interface
