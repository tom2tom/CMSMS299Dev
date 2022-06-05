<?php
/*
Interface for page-content-editor classes
Copyright (C) 2019-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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
 * This prescribes the methods needed to edit a content-object's properties.
 * It does not use PHP7+ type-declarations, to make it easier for legacy
 * content-types to comply.
 *
 * @since   3.0
 * @package CMS
 */
interface IContentEditor
{
	/**
	 * Convert this object to an array.
	 *
	 * This can be considered a simple DTO (Data Transfer Object)
	 *
	 * @return array
	 */
	public function ToData() : array;

	/**
	* Set the properties of this object from a database row or equivalent array.
	 *
	 * @param array $data Row loaded from the database
	 * @param bool  $extended Optional flag whether to also load non-core
	 *  properties (via a database SELECT). Default false.
	 * @return  bool indicating success
	 */
	public function LoadFromData($data, $extended = false);

	/**
	 * Function for subclasses to parse out data for their specifix parameters.
	 * This method is typically called from an editor form to allow modifying
	 * this object using form input fields
	 *
	 * @param array $params The input array (usually from $_POST)
	 * @param bool  $editing Indicates whether this is an edit or add operation.
	 */
	public function FillParams($params, $editing = false);

	/**
	 * Return html for displaying an input element for modifying a property of this object.
	 *
	 * @param string $propname The property name
	 * @param bool $adding Whether we are in add or edit mode.
	 * @return mixed 2-member array: [0] = label, [1] = input element, or null
	 */
	public function ShowElement($propname, $adding);

	/**
	 * Return the raw value of a property of this object.
	 *
	 * @param string $propname An optional property name to display. Default 'content_en'.
	 * @return string
	 */
	public function Show($propname = 'content_en');

	/**
	 * Return this object's tabindex value
	 *
	 * @return int
	 */
	public function TabIndex() : int;

	/**
	 * Set this object's tabindex value
	 *
	 * @param int $tabindex tab index
	 */
	public function SetTabIndex(int $tabindex);

	/**
	 * Return a list of distinct sections that divide the various logical sections
	 * that this object supports for editing.
	 * Used from a object that allows content editing.
	 *
	 * @return array Associative array of tab keys and labels.
	 */
	public function GetTabNames() : array;

	/**
	 * Get an optional message for each tab.
	 *
	 * @param string $key the tab key (as returned with GetTabNames)
	 * @return string html text to display at the top of the tab.
	 */
	public function GetTabMessage($key);

	/**
	 * Get the elements for a specific tab.
	 * @deprecated since 3.0 does nothing - instead process results from GetSortedEditableProperties()
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
	 * Return 'non-core' properties (if any) of this object, previously
	 * loaded from the content-properties table or otherwise set.
	 *
	 * @return array
	 */
	public function Properties() : array;

	/**
	 * Set all the ('non-core') properties of this object. Subclasses should set
	 * their specific properties after calling back to the parent's method.
	 */
	public function SetProperties();

	/**
	 * Return the editor-UI properties of this object (whether or not the
	 * user is entitled to view them)
	 *
	 * @return array of assoc. arrays
	 */
	public function GetPropertiesArray() : array;

	/**
	 * Return the editor-UI properties that may be edited by the
	 * current user when editing this object in a content editor form.
	 *
	 * Content-type classes should call their parent's method as well as processing
	 * their own properties.
	 * @see IContentEditor::GetSortedEditableProperties()
	 *
	 * @return array of assoc. arrays, each of those having members
	 *  'name' (string), 'tab' (string), 'priority' (int), maybe 'required' (bool), maybe 'basic' (bool)
	 *  Other(s) may be added by a subclass
	 */
	public function GetEditableProperties() : array;

	/**
	 * Return a sorted list of all properties that may be edited by the
	 * current user when editing this object in a content editor form.
	 *
	 * @return array
	 */
	public function GetSortedEditableProperties() : array;

	/**
	 * Test whether this object has the named property.
	 * Properties will be loaded from the database if necessary.
	 *
	 * @param string $propname
	 * @return bool
	 */
	public function HasProperty(string $propname) : bool;

	/**
	 * Get the value for the named non-core property|ies.
	 * All such properties will be loaded from the database if necessary.
	 *
	 * @param mixed $propname string | string[]
	 * @return mixed value, or null if the property does not exist, or assoc. array of same
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
	public function AddProperty(string $propname, int $priority, string $tabname = 'Main', bool $required = false, bool $basic = false);

	/**
	 * Remove a property from the known-properties list, and optionally specify
	 * a default value to use if the property is subsequently sought.
	 *
	 * @param string $propname The property name
	 * @param mixed $dflt Optional new default value. Default null.
	 */
	public function RemoveProperty(string $propname, $dflt = null);

	/**
	 * Callback for pre-loading content or other things if necessary, immediately
	 * after the object content is loaded from the database.
	 */
	public function Load();

	/**
	 * Insert this object into, or update it in, the database.
	 *
	 * @return boolean indicating successful completion
	 */
	public function Save();

	/**
	 * Delete this this object from the database.
	 *
	 * @return boolean indicating successful completion
	 */
	public function Delete();

	/**
	 * Test whether this object is valid.
	 * This function checks that no compulsory argument has been forgotten by the user
	 *
	 * We do not check the Id because there might not be one yet (new content)
	 * The Save() method needs to validate Id.
	 *
	 * @return mixed array of error-message string(s), or false indicates success
	 */
	public function ValidateData();

	/**
	 * Return this object's numeric identifier
	 *
	 * @return int
	 */
	public function Id() : int;

	/**
	 * Set this object's numeric id
	 *
	 * @param int Integer id
	 */
	public function SetId(int $id);

	/**
	 * Return this object's name
	 *
	 * @return string
	 */
	public function Name() : string;

	/**
	 * Set this object's name
	 *
	 * @param string $name
	 */
	public function SetName(string $name);

	/**
	 * Return a friendly name for this object
	 *
	 * Normally this method returns this object's type translated into
	 * the user's current language
	 *
	 * @return string
	 */
	public function FriendlyName() : string;

	/**
	 * Return this object alias
	 *
	 * @return string
	 */
	public function Alias() : string;

	/**
	 * Set this object alias for this object.
	 * If an empty alias is supplied, and depending upon the doAutoAliasIfEnabled flag,
	 * and config entries a suitable alias may be calculated from other data in this object object.
	 * This method relies on the menutext and the name of this object already being set.
	 *
	 * @param string $alias The alias
	 * @param bool $doAutoAliasIfEnabled Whether an alias should be calculated or not.
	 */
	public function SetAlias(string $alias = '', bool $doAutoAliasIfEnabled = true);

	/**
	 * Return whether this object support aliasing
	 *
	 * @return bool Default false..
	 */
	public function HandlesAlias() : bool;

	/**
	 * Return whether this object requires an alias.
	 * Pages that are not directly navigable do not require an alias.
	 *
	 * @return bool
	 */
	public function RequiresAlias() : bool;

	/**
	 * Return this object's type
	 *
	 * @return string
	 */
	public function Type() : string;

	/**
	 * Return the owner's user id
	 *
	 * @return int
	 */
	public function Owner() : int;

	/**
	 * Set this object's owner.
	 * No validation is performed.
	 *
	 * @param int $owner Owner's user id
	 */
	public function SetOwner(int $owner);

	/**
	 * Return this object's metadata
	 *
	 * @return string
	 */
	public function Metadata();

	/**
	 * Set this object's metadata
	 *
	 * @param string $metadata The metadata
	 */
	public function SetMetadata($metadata);

	/**
	 * Return this object's title attribute
	 *
	 * @return string
	 */
	public function TitleAttribute();

	/**
	 * Set the title attribute of this object
	 *
	 * The title attribute can be used in navigations to set the
	 * "title=" attribute of a link.
	 * Some menu templates may ignore this.
	 *
	 * @param string $titleattribute The title attribute
	 */
	public function SetTitleAttribute($titleattribute);

	/**
	 * Return the creation date/time of this object
	 *
	 * @return int UNIX UTC timestamp
	 */
	public function GetCreationDate() : int;

	/**
	 * Set the creation date/time of this object
	 *
	 * @param mixed $datevalue  string | null Database DATETIME-field format, not a timestamp
	 */
	public function SetCreationDate($datevalue);

	/**
	 * Return the date/time of the last modification of this object
	 *
	 * @return int UNIX UTC timestamp
	 */
	public function GetModifiedDate() : int;

	/**
	 * Set the last-modified date/time of this object
	 *
	 * @param mixed $datevalue  string | null Database DATETIME-field format, not a timestamp
	 */
	public function SetModifiedDate($datevalue);

	/**
	 * Get the access key (for accessibility) for this object.
	 *
	 * @see http://www.w3schools.com/tags/att_global_accesskey.asp
	 * @return string
	 */
	public function AccessKey();

	/**
	 * Set the access key (for accessibility) for this object
	 *
	 * @see http://www.w3schools.com/tags/att_global_accesskey.asp
	 * @param string $accesskey
	 */
	public function SetAccessKey($accesskey);

	/**
	 * Return the id of this object's parent.
	 * Value -2 indicates a new object.
	 * Value -1 indicates that this object has no parent.
	 * Otherwise a positive integer is returned.
	 *
	 * @return int
	 */
	public function ParentId() : int;

	/**
	 * Set the parent of this object
	 *
	 * @param int $parentid The numeric object parent id.
	 *  Use -1 for no parent, -2 for new object.
	 */
	public function SetParentId(int $parentid);

	/**
	 * Return the numeric id of the template associated with this object.
	 *
	 * @return int
	 */
	public function TemplateId() : int;

	/**
	 * Set the id of the template associated with this object.
	 *
	 * @param int $templateid
	 */
	public function SetTemplateId(int $templateid);

	/**
	 * Return whether this object uses a template.
	 * Some content types like sectionheader and separator do not.
	 *
	 * @return bool default false
	 */
	public function HasTemplate() : bool;

	/**
	 * Return a resource identifier for Smarty to retrieve the template assigned to this object.
	 *
	 * @return string
	 */
	public function TemplateResource();

	/**
	 * Return the itemOrder
	 * That is used to specify the order of this object among its peers
	 *
	 * @return int
	 */
	public function ItemOrder() : int;

	/**
	 * Set this object's item-order.
	 * That is used to specify the order of this object within the parent.
	 * A value of -1 indicates that a new item order will be calculated on save.
	 * Otherwise a positive integer is expected.
	 *
	 * @param int $itemorder
	 */
	public function SetItemOrder(int $itemorder);

	/**
	 * Move this content up or down with respect to its peers.
	 *
	 * Note: This method modifies two this objects.
	 *
	 * @param int $direction direction. negative value indicates up, positive value indicates down.
	 */
	public function ChangeItemOrder($direction);

	/**
	 * Return the hierarchy of this object.
	 * A string like #.##.## indicating the path to this object and its order
	 * This value uses the item order when calculating the output e.g. 3.3.3
	 * to indicate the third grandchild of the third child of the third root object.
	 *
	 * @return string
	 */
	public function Hierarchy() : string;

	/**
	 * Set the hierarchy
	 *
	 * @param string $hierarchy
	 */
	public function SetHierarchy($hierarchy);

	/**
	 * Return the id Hierarchy.
	 * A string like #.##.## indicating the path to this object and its order
	 * This property uses the id's of objects when calculating the output i.e: 21.5.17
	 * to indicate that object id 17 is the child of object with id 5 which is in turn the
	 * child of this object with id 21
	 *
	 * @return string
	 */
	public function IdHierarchy() : string;

	/**
	 * Return the hierarchy path.
	 * Similar to the Hierarchy and IdHierarchy this string uses object aliases
	 * and outputs a string like root_alias/parent_alias/object_alias
	 *
	 * @return string
	 */
	public function HierarchyPath() : string;

	/**
	 * Return this object-active state
	 *
	 * @return bool
	 */
	public function Active() : bool;

	/**
	 * Set this object as active
	 *
	 * @param bool $state
	 */
	public function SetActive(bool $state);

	/**
	 * Return whether this object should (by default) be shown in navigation menus.
	 *
	 * @return bool
	 */
	public function ShowInMenu() : bool;

	/**
	 * Set whether this object should be (by default) shown in menus
	 *
	 * @param bool $state
	 */
	public function SetShowInMenu(bool $state);

	/**
	 * Return whether this object is the default.
	 * The default object is the one that is displayed when no alias or objectid is
	 * specified in the route. Only one object can be the default.
	 *
	 * @return bool
	 */
	public function DefaultContent() : bool;

	/**
	 * Set whether this object should be considered the default.
	 * Does not modify the flags for any other object i.e. any pre-existing
	 * default object must be separately handled.
	 *
	 * @param bool $state
	 */
	public function SetDefaultContent(bool $state);

	/**
	 * Return whether this object may be the default object for the website.
	 *
	 * @return bool Default true
	 */
	public function IsDefaultPossible() : bool;

	/**
	 * Return whether this object is cachable.
	 * Cachable objects (when enabled in global settings) are cached by the browser
	 * (also server side caching of HTML output may be enabled)
	 *
	 * @return bool
	 */
	public function Cachable() : bool;

	/**
	 * Set whether this object is cachable
	 *
	 * @param bool $state
	 */
	public function SetCachable(bool $state);

	/**
	 * Return whether this object should be accessed via a secure protocol.
	 * The secure flag affects whether the ssl protocol and appropriate config entries are used when generating urls to this object.
	 * @deprecated since 3.0
	 *
	 * @return bool
	 */
	public function Secure() : bool;

	/**
	 * Set whether this object should be accessed via a secure protocol.
	 * The secure flag affects whether the ssl protocol and appropriate config entries are used when generating urls to this object.
	 * @deprecated since 3.0
	 *
	 * @param bool $state
	 */
	public function SetSecure(bool $state);

	/**
	 * Return the custom URL-path (if any) associated with this object.
	 * Not the complete URL, but merely the 'stub' or 'slug'
	 * appended to the root url.
	 * If this object is specified as the default object, then its
	 * URL-path will be ignored.
	 * Some types of object do not support a custom URL-path.
	 * Not to be confused with IContentEditor::GetURL(), for previewing
	 *
	 * @return string
	 */
	public function URL() : string;

	/**
	 * Set the custom URL-path associated with this object.
	 * Verbatim, no immediate validation.
	 * The URL should be relative to the root URL i.e: /some/path/to/the/object
	 * Note: some objects do not support object URLs.
	 *
	 * @param string $url May be empty.
	 */
	public function SetURL(string $url);

	/**
	 * Return an actionable URL for opening/previewing the page
	 * represented by this object. No re-writing is done.
	 * Not to be confused with IContentEditor::URL(), for property retrieval.
	 * @see also CMSMS\contenttypes\ContentBase::GetURL()
	 *
	 * @return string
	 */
	public function GetURL() : string;

	/**
	 * Return the integer id of the admin user that last modified this object.
	 *
	 * @return int
	 */
	public function LastModifiedBy() : int;

	/**
	 * Set the last modified date for this item
	 *
	 * @param int $lastmodifiedby
	 */
	public function SetLastModifiedBy(int $lastmodifiedby);

	/**
	 * Return whether preview should be available for this object
	 *
	 * @return bool
	 */
	public function HasPreview() : bool;

	/**
	 * Return whether this object is viewable (i.e: can be rendered).
	 * Some objects (like redirection links) are not viewable.
	 *
	 * @return bool Default true
	 */
	public function IsViewable() : bool;

	/**
	 * Check the current user's edit-authority
	 *
	 * @param $main optional flag whether to check for main-property editability. Default true
	 * @param $extra optional flag whether to check for membership of additional-editors. Default true
	 * @return bool
	 */
	public function IsEditable(bool $main = true, bool $extra = true) : bool;

	/**
	 * Return whether this user is permitted to view this object.
	 *
	 * @return boolean
	 */
	public function IsPermitted() : bool;

	/**
	 * Return whether this object has a usable link.
	 *
	 * @return bool default true
	 */
	public function HasUsableLink() : bool;

	/**
	 * Return whether this object is copyable.
	 *
	 * @return bool default false
	 */
	public function IsCopyable() : bool;

	/**
	 * Return whether this object is a system object.
	 * System objects are used to handle things like 404 errors etc.
	 *
	 * @return bool default false
	 */
	public function IsSystemPage() : bool;

	/**
	 * Return whether this object is searchable.
	 *
	 * Searchable objects can be indexed by the search module etc.
	 *
	 * This function would probably use a combination of other methods to
	 * determine whether this object is searchable.
	 *
	 * @return bool
	 */
	public function IsSearchable() : bool;

	/**
	 * Return whether this object may have content that can be used by a search module.
	 *
	 * @return bool Default true
	 */
	public function HasSearchableContent() : bool;

	/**
	 * Return the menu text for this object.
	 * The MenuText is by default used as the text portion of a navigation link.
	 *
	 * @return string
	 */
	public function MenuText() : string;

	/**
	 * Set the menu text for this object
	 *
	 * @param string $menutext
	 */
	public function SetMenuText(string $menutext);

	/**
	 * Return the number of immediate child objects of this object.
	 *
	 * @return int
	 */
	public function ChildCount() : int;

	/**
	 * Return whether this object has children.
	 *
	 * @param bool $activeonly Optional flag whether to test only for active children. Default false.
	 * @return bool
	 */
	public function HasChildren(bool $activeonly = false) : bool;

	/**
	 * Return whether this object may have child objects.
	 * Some content types, such as a separator, do not.
	 *
	 * @return bool Default true
	 */
	public function WantsChildren() : bool;

	/**
	 * Return a list of additional editors of this object.
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
} // interface
