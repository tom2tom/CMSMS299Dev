<?php
/*
Interface for page-content-editor classes
Copyright (C) 2019-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
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
 * This prescribes the methods needed to edit a content-page's properties.
 * It does not use PHP7+ type-declarations, to make it easier for legacy
 * content-types to comply.
 *
 * @since		2.99
 * @package		CMS
 */
interface ContentEditor
{
	/**
	 * Convert this object to an array.
	 *
	 * This can be considered a simple DTO (Data Transfer Object)
	 *
	 * @return array
	 */
	public function ToData();

	/**
	* Set the properties of this page from a database row or equivalent array.
	 *
	 * @param array $data Row loaded from the database
	 * @param bool  $loadProperties Optional flag whether to also load non-core
	 *  properties (via a database SELECT). Default false.
	 * @return	bool indicating success
	 */
	public function LoadFromData($data, $loadProperties = false);

	/**
	 * Function for subclasses to parse out data for their specifix parameters.
	 * This method is typically called from an editor form to allow modifying
	 * this page using form input fields
	 *
	 * @param array $params The input array (usually from $_POST)
	 * @param bool  $editing Indicates whether this is an edit or add operation.
	 */
	public function FillParams($params, $editing = false);

	/**
	 * Return html for displaying an input element for modifying a property of this page.
	 *
	 * @param string $propname The property name
	 * @param bool $adding Whether we are in add or edit mode.
	 * @return mixed 2-member array: [0] = label, [1] = input element, or null
	 */
	public function ShowElement($propname, $adding);

	/**
	 * Return the raw value of a page property.
	 *
	 * @param string $propname An optional property name to display. Default 'content_en'.
	 * @return string
	 */
	public function Show($propname = 'content_en');

	/**
	 * Return this page's tabindex value
	 *
	 * @return int
	 */
	public function TabIndex();

	/**
	 * Set this page's tabindex value
	 *
	 * @param int $tabindex tab index
	 */
	public function SetTabIndex($tabindex);

	/**
	 * Return a list of distinct sections that divide the various logical sections
	 * that this page supports for editing.
	 * Used from a page that allows content editing.
	 *
	 * @return array Associative array of tab keys and labels.
	 */
	public function GetTabNames();

	/**
	 * Get an optional message for each tab.
	 *
	 * @param string $key the tab key (as returned with GetTabNames)
	 * @return string html text to display at the top of the tab.
	 */
	public function GetTabMessage($key);

	/**
	 * Get the elements for a specific tab.
	 * @deprecated since 2.99 does nothing - instead process results from GetSortedEditableProperties()
	 *
	 * @param string $key tab key
	 * @param bool   $adding  Optional flag whether this is an add operation. Default false (i.e. edit).
	 * @return array Each member an array:
     *  [0] = prompt field
	 *  [1] = input field for the prompt with its js if needed
	 * or just a scalar false upon some errors
	 */
	public function GetTabElements($key, $adding = false);

	/**
	 * Return all of the 'non-core' properties of this page.
	 * Sourced originally from the object-properties table.
	 *
	 * @return array
	 */
	public function Properties();

	/**
	 * Set all the ('non-core') properties of this page. Subclasses should set
	 * their specific properties after calling back to the parent's method.
	 */
	public function SetProperties();

	/**
	 * Get all the properties of this page (whether or not the user is entitled to view them)
	 *
	 * @return array of assoc. arrays
	 */
	public function GetPropertiesArray();

	/**
	 * Return a list of all properties that may be edited by the current user
	 * when editing this page in a content editor form.
	 *
	 * Content-type classes should call their parent's method as well as processing
	 * their own properties.
	 * @see ContentEditor::GetSortedEditableProperties()
	 *
	 * @return array of assoc. arrays, each of those having members
	 *  'name' (string), 'tab' (string), 'priority' (int), maybe 'required' (bool), maybe 'basic' (bool)
	 *  Other(s) may be added by a subclass
	 */
	public function GetEditableProperties();

	/**
	 * Return a sorted list of all properties that may be edited by the
	 * current user when editing this page in a content editor form.
	 *
	 * @return array
	 */
	public function GetSortedEditableProperties();

	/**
	 * Test whether this page has the named property.
	 * Properties will be loaded from the database if necessary.
	 *
	 * @param string $propname
	 * @return bool
	 */
	public function HasProperty($propname);

	/**
	 * Get the value for the named property.
	 * Properties will be loaded from the database if necessary.
	 *
	 * @param string $propname
	 * @return mixed String value, or null if the property does not exist.
	 */
	public function GetPropertyValue($propname);

	/**
	 * Add a property definition.
	 *
	 * @param string $propname Property name
	 * @param int $priority Sort order
	 * @param string $tabname Optional tab identifier for the property.  Default 'Main'
	 * @param bool $required Optional flag whether the property is required. Default false
	 * @param bool $basic Optional flag whether the property is basic (i.e. editable even by restricted editors). Default false
	 */
	public function AddProperty($propname, $priority, $tabname = 'Main', $required = false, $basic = false);

	/**
	 * Remove a property from the known-properties list, and optionally specify
	 * a default value to use if the property is subsequently sought.
	 *
	 * @param string $propname The property name
	 * @param mixed $dflt Optional new default value. Default null.
	 */
	public function RemoveProperty($propname, $dflt = null);

	/**
	 * Callback for pre-loading content or other things if necessary, immediately
	 * after the object content is loaded from the database.
	 */
	public function Load();

	/**
	 * Insert this page into, or update it in, the database.
	 *
	 * @return boolean indicating successful completion
	 */
	public function Save();

	/**
	 * Delete this this page from the database.
	 *
	 * @return boolean indicating successful completion
	 */
	public function Delete();

	/**
	 * Test whether this page is valid.
	 * This function checks that no compulsory argument has been forgotten by the user
	 *
	 * We do not check the Id because there might not be one yet (new content)
	 * The Save() method needs to validate Id.
	 *
	 * @returns	mixed array of error-message string(s), or false indicates success
	 */
	public function ValidateData();

	/**
	 * Return this page's numeric identifier
	 *
	 * @return int
	 */
	public function Id();

	/**
	 * Set this page's numeric id
	 *
	 * @param int Integer id
	 */
	public function SetId($id);

	/**
	 * Return this page's name
	 *
	 * @return string
	 */
	public function Name();

	/**
	 * Set this page's name
	 *
	 * @param string $name
	 */
	public function SetName($name);

	/**
	 * Return a friendly name for this page
	 *
	 * Normally this method returns this page's type translated into the user's
	 * current language
	 *
	 * @return string
	 */
	public function FriendlyName();

	/**
	 * Return this page alias
	 *
	 * @return string
	 */
	public function Alias();

	/**
	 * Set this page alias for this page.
	 * If an empty alias is supplied, and depending upon the doAutoAliasIfEnabled flag,
	 * and config entries a suitable alias may be calculated from other data in this page object.
	 * This method relies on the menutext and the name of this page already being set.
	 *
	 * @param string $alias The alias
	 * @param bool $doAutoAliasIfEnabled Whether an alias should be calculated or not.
	 */
	public function SetAlias($alias = '', $doAutoAliasIfEnabled = true);

	/**
	 * Return whether this page support aliasing
	 *
	 * @return bool Default false..
	 */
	public function HandlesAlias();

	/**
	 * Return whether this page requires an alias.
	 * Pages that are not directly navigable do not require an alias.
	 *
	 * @return bool
	 */
	public function RequiresAlias();

	/**
	 * Return this page's type
	 *
	 * @return string
	 */
	public function Type();

	/**
	 * Return the owner's user id
	 *
	 * @return int
	 */
	public function Owner();

	/**
	 * Set this page's owner.
	 * No validation is performed.
	 *
	 * @param int $owner Owner's user id
	 */
	public function SetOwner($owner);

	/**
	 * Return this page's metadata
	 *
	 * @return string
	 */
	public function Metadata();

	/**
	 * Set this page's metadata
	 *
	 * @param string $metadata The metadata
	 */
	public function SetMetadata($metadata);

	/**
	 * Return this page's title attribute
	 *
	 * @return string
	 */
	public function TitleAttribute();

	/**
	 * Set the title attribute of this page
	 *
	 * The title attribute can be used in navigations to set the "title=" attribute of a link
	 * some menu templates may ignore this.
	 *
	 * @param string $titleattribute The title attribute
	 */
	public function SetTitleAttribute($titleattribute);

	/**
	 * Return the creation date/time of this page.
	 *
	 * @return int UNIX UTC timestamp
	 */
	public function GetCreationDate();

	/**
	 * Return the date/time of the last modification of this page.
	 *
	 * @return int UNIX UTC timestamp
	 */
	public function GetModifiedDate();

	/**
	 * Get the access key (for accessibility) for this page.
	 *
	 * @see http://www.w3schools.com/tags/att_global_accesskey.asp
	 * @return string
	 */
	public function AccessKey();

	/**
	 * Set the access key (for accessibility) for this page
	 *
	 * @see http://www.w3schools.com/tags/att_global_accesskey.asp
	 * @param string $accesskey
	 */
	public function SetAccessKey($accesskey);

	/**
	 * Return the id of this page's parent.
	 * Value -2 indicates a new page.
	 * Value -1 indicates that this page has no parent.
	 * Otherwise a positive integer is returned.
	 *
	 * @return int
	 */
	public function ParentId();

	/**
	 * Set the parent of this page
	 *
	 * @param int $parentid The numeric page parent id.
	 *  Use -1 for no parent, -2 for new page.
	 */
	public function SetParentId($parentid);

	/**
	 * Return the numeric id of the template associated with this page.
	 *
	 * @return int
	 */
	public function TemplateId();

	/**
	 * Set the id of the template associated with this page.
	 *
	 * @param int $templateid
	 */
	public function SetTemplateId($templateid);

	/**
	 * Return whether this page uses a template.
	 * Some content types like sectionheader and separator do not.
	 *
	 * @return bool default false
	 */
	public function HasTemplate();

	/**
     * Return a resource identifier for Smarty to retrieve the template assigned to this page.
     *
     * @return string
     */
    public function TemplateResource();

	/**
	 * Return the itemOrder
	 * That is used to specify the order of this page among its peers
	 *
	 * @return int
	 */
	public function ItemOrder();

	/**
	 * Set this page's item-order.
	 * That is used to specify the order of this page within the parent.
	 * A value of -1 indicates that a new item order will be calculated on save.
	 * Otherwise a positive integer is expected.
	 *
	 * @param int $itemorder
	 */
	public function SetItemOrder($itemorder);

	/**
	 * Move this content up or down with respect to its peers.
	 *
	 * Note: This method modifies two this pages.
	 *
	 * @param int $direction direction. negative value indicates up, positive value indicates down.
	 */
	public function ChangeItemOrder($direction);

	/**
	 * Return the hierarchy of this page.
	 * A string like #.##.## indicating the path to this page and its order
	 * This value uses the item order when calculating the output e.g. 3.3.3
	 * to indicate the third grandchild of the third child of the third root page.
	 *
	 * @return string
	 */
	public function Hierarchy();

	/**
	 * Set the hierarchy
	 *
	 * @param string $hierarchy
	 */
	public function SetHierarchy($hierarchy);

	/**
	 * Return the id Hierarchy.
	 * A string like #.##.## indicating the path to this page and its order
	 * This property uses the id's of pages when calculating the output i.e: 21.5.17
	 * to indicate that page id 17 is the child of page with id 5 which is in turn the
	 * child of this page with id 21
	 *
	 * @return string
	 */
	public function IdHierarchy();

	/**
	 * Return the hierarchy path.
	 * Similar to the Hierarchy and IdHierarchy this string uses page aliases
	 * and outputs a string like root_alias/parent_alias/page_alias
	 *
	 * @return string
	 */
	public function HierarchyPath();

	/**
	 * Return this page-active state
	 *
	 * @return bool
	 */
	public function Active();

	/**
	 * Set this page as active
	 *
	 * @param bool $state
	 */
	public function SetActive($state);

	/**
	 * Return whether this page should (by default) be shown in navigation menus.
	 *
	 * @return bool
	 */
	public function ShowInMenu();

	/**
	 * Set whether this page should be (by default) shown in menus
	 *
	 * @param bool $state
	 */
	public function SetShowInMenu($state);

	/**
	 * Return whether this page is the default.
	 * The default page is the one that is displayed when no alias or pageid is
	 * specified in the route. Only one page can be the default.
	 *
	 * @return bool
	 */
	public function DefaultContent();

	/**
	 * Set whether this page should be considered the default.
	 * Does not modify the flags for any other page i.e. any pre-existing
	 * default page must be separately handled.
	 *
	 * @param bool $state
	 */
	public function SetDefaultContent($state);

	/**
	 * Return whether this page may be the default page for the website.
	 *
	 * @returns bool Default true
	 */
	public function IsDefaultPossible();

	/**
	 * Return whether this page is cachable.
	 * Cachable pages (when enabled in global settings) are cached by the browser
	 * (also server side caching of HTML output may be enabled)
	 *
	 * @return bool
	 */
	public function Cachable();

	/**
	 * Set whether this page is cachable
	 *
	 * @param bool $state
	 */
	public function SetCachable($state);

	/**
	 * Return whether this page should be accessed via a secure protocol.
	 * The secure flag affects whether the ssl protocol and appropriate config entries are used when generating urls to this page.
	 * @deprecated since 2.99
	 *
	 * @return bool
	 */
	public function Secure();

	/**
	 * Set whether this page should be accessed via a secure protocol.
	 * The secure flag affects whether the ssl protocol and appropriate config entries are used when generating urls to this page.
	 * @deprecated since 2.99
	 *
	 * @param bool $state
	 */
	public function SetSecure($state);

	/**
	 * Return the custom page URL (if any) assigned to this page.
	 * That is not the complete URL, but merely the 'stub' or 'slug' appended
	 * after the root url when accessing the site.
	 * If this page is specified as the default page then the "page url" will be ignored.
	 * Some pages do not support page urls.
	 * @see ContentEditor::GetURL()
	 *
	 * @return string
	 */
	public function URL();

	/**
	 * Set the custom page URL associated with this page.
	 * Verbatim, no immediate validation.
	 * The URL should be relative to the root URL i.e: /some/path/to/the/page
	 * Note: some pages do not support page URLs.
	 *
	 * @param string $url May be empty.
	 */
	public function SetURL($url);

	/**
	 * Return the constructed URL for this page.
	 *
	 * @param bool $rewrite optional flag, default true. If true, and mod_rewrite is enabled, build an URL suitable for mod_rewrite.
	 * @return string
	 */
	public function GetURL($rewrite = true);

	/**
	 * Return the integer id of the admin user that last modified this page.
	 *
	 * @return int
	 */
	public function LastModifiedBy();

	/**
	 * Set the last modified date for this item
	 *
	 * @param int $lastmodifiedby
	 */
	public function SetLastModifiedBy($lastmodifiedby);

	/**
	 * Return whether preview should be available for this page
	 *
	 * @return bool
	 */
	public function HasPreview();

	/**
	 * Return whether this page is viewable (i.e: can be rendered).
	 * Some pages (like redirection links) are not viewable.
	 *
	 * @return bool Default true
	 */
	public function IsViewable();

	/**
	 * Check this user's edit-authority
	 *
	 * @param $main optional flag whether to check for main-property editability. Default true
	 * @param $extra optional flag whether to check for membership of additional-editors. Default true
	 * @return bool
	 */
	public function IsEditable($main = true, $extra = true);

	/**
	 * Return whether this user is permitted to view this page.
	 *
	 * @return boolean
	 */
	public function IsPermitted();

	/**
	 * Return whether this page has a usable link.
	 *
	 * @return bool default true
	 */
	public function HasUsableLink();

	/**
	 * Return whether this page is copyable.
	 *
	 * @return bool default false
	 */
	public function IsCopyable();

	/**
	 * Return whether this page is a system page.
	 * System pages are used to handle things like 404 errors etc.
	 *
	 * @return bool default false
	 */
	public function IsSystemPage();

	/**
	 * Return whether this page is searchable.
	 *
	 * Searchable pages can be indexed by the search module etc.
	 *
	 * This function would probably use a combination of other methods to
	 * determine whether this page is searchable.
	 *
	 * @return bool
	 */
	public function IsSearchable();

	/**
	 * Return whether this page may have content that can be used by a search module.
	 *
	 * @return bool Default true
	 */
	public function HasSearchableContent();

	/**
	 * Return the menu text for this page.
	 * The MenuText is by default used as the text portion of a navigation link.
	 *
	 * @return string
	 */
	public function MenuText();

	/**
	 * Set the menu text for this page
	 *
	 * @param string $menutext
	 */
	public function SetMenuText($menutext);

	/**
	 * Return the number of immediate child pages of this page.
	 *
	 * @return int
	 */
	public function ChildCount();

	/**
	 * Return whether this page has children.
	 *
	 * @param bool $activeonly Optional flag whether to test only for active children. Default false.
	 * @return bool
	 */
	public function HasChildren($activeonly = false);

	/**
	 * Return whether this page may have child pages.
	 * Some content types, such as a separator, do not.
	 *
	 * @return bool Default true
	 */
	public function WantsChildren();

	/**
	 * Return a list of additional editors of this page.
	 * Note: in the returned array, group id's are specified as negative integers.
	 *
	 * @return array user id's and group id's entitled to edit this content, or empty
	 */
	public function GetAdditionalEditors();

	/**
	 * Set the list of additional editors.
	 * Note: in the provided array, group id's are specified as negative integers.
	 *
	 * @param mixed $editorarray Array of user id's and group id's, or null
	 */
	public function SetAdditionalEditors($editorarray = null);
} // class
