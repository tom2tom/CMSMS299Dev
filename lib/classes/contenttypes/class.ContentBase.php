<?php
/*
Base class for working with page content at runtime
Copyright (C) 2019-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS\contenttypes;

//use CMSMS\AppParams;
use CMSMS\Crypto;
use CMSMS\Lone;
use Exception;
use Serializable;
use const CMS_DB_PREFIX;
//use const CMS_DEBUG;
use const CMS_ROOT_URL;
use function cms_to_stamp;
//use function cmsms;

/**
 * Page content-display base class.
 * This is for preparation of displayed pages at runtime. Object
 * properties are modifiable only by [re]retrieval from the database.
 * Content properties which are only for 'management' are not used here.
 * @see also interface CMSMS\IContentEditor
 *
 * @since 3.0
 * @package  CMS
 */
class ContentBase implements Serializable
{
	/**
	 * Property-name aliases
	 * @ignore
	 */
	private const PROPALIAS = [
		'alias' => 'content_alias',
		'content' => 'content_en',
		'creationdate' => 'create_date',
		'defaultcontent' => 'default_content',
		'fields' => '_fields',
		'hierarchypath' => 'hierarchy_path',
		'id' => 'content_id',
		'idhierarchy' => 'id_hierarchy',
		'itemorder' => 'item_order',
		'lastmodifiedby' => 'last_modified_by',
		'menutext' => 'menu_text',
		'modifieddate' => 'modified_date',
		'name' => 'content_name',
		'owner' => 'owner_id',
		'parentid' => 'parent_id',
		'properties' => '_props',
		'showinmenu' => 'show_in_menu',
		'templateid' => 'template_id',
		'url' => 'page_url',
		'values' => '_fields',
	];
// TODO 'stylesheetype' => 'csstype_id', //non-core prop to support typed stylesheeets for theming
// TODO 'templatetype' => 'tpltype_id', //non-core prop to support typed templates for theming

	// NOTE any private or static property will not be serialized

	/**
	 * Main-table field values for this page (a.k.a. core properties)
	 * array
	 * @internal
	 */
	protected $_fields = [];

	/**
	 * Content-properties-table field values (if any) for this page
	 * (a.k.a. non-core properties)
	 * array
	 * @internal
	 */
	protected $_props = [];

	/**
	 * Flag whether non-core properties array has been populated (tho' might still be empty)
	 * boolean
	 * @internal
	 */
	protected $_propsloaded = false;

	/**
	 * Constructor. Sets initial properties of this page from the supplied data.
	 *
	 * @param mixed $params Properties to be set (optional, to support legacy sub-classes)
	 */
	public function __construct($params)
	{
		if (!empty($params)) {
			foreach ($params as $key => $value) {
				$this->__set($key, $value);
			}
			// special case, might have been loaded simultaneously
			if (isset($this->_fields['content_en'])) {
				$this->_props['content_en'] = $this->_fields['content_en'];
				unset($this->_fields['content_en']);
			}
		} elseif (!isset($params)) {
			// legacy sub-class init
			$this->SetInitialValues();
		}
	}

	/**
	 * @ignore
	 */
	public function __clone(): void
	{
		$this->_fields['content_alias'] = '';
		$this->_fields['content_id'] = -1;
		$this->_fields['item_order'] = -1;
		$this->_fields['page_url'] = '';
	}

	/**
	 * This should only be used during construction
	 * @ignore
	 */
	public function __set(string $key, $value): void
	{
		$use = strtolower($key);
		$use = self::PROPALIAS[$use] ?? $use;
		switch ($use) {
			case '_props':
				$this->_propsloaded = true;
				// no break here
			case '_fields':
				$this->$use = $value;
				break;
			default:
/*				if (CMS_DEBUG) {
					TODO for some properties ...
					CMSMS\log_warning 'Attempt to set specific content-property: '.$key);
				}
*/
				$this->_fields[$use] = $value;
				break;
		}
	}

	/**
	 * @ignore
	 */
	#[\ReturnTypeWillChange]
	public function __get(string $key)//: mixed
	{
		$use = strtolower($key);
		$use = self::PROPALIAS[$use] ?? $use;
		switch ($use) {
			case '_fields':
			case '_props': // too bad if not loaded before
				return $this->$use;
			default:
				if (isset($this->_fields[$use])) {
					return $this->_fields[$use];
				} elseif (isset($this->_props[$use])) {
					return $this->_props[$use];
				}
/*				if (CMS_DEBUG) {
					TODO for some properties ...
					CMSMS\log_warning 'Attempt to retrieve unrecognised content-property: '.$key);
				}
*/
				return null;
		}
	}

	/**
	 * Enable relevant property-accessors prescribed in the IContentEditor interface
	 * NOTE (get)URL() is distinct from GetURL(). The latter gets the URL
	 * to use for page display
	 * @ignore
	 */
	#[\ReturnTypeWillChange]
	public function __call(string $name, array $args)//: mixed
	{
		$chk = strtolower($name);
		$pre = substr($chk, 0, 3);
		switch ($pre) {
			case 'set':
				$len = ($chk[4] == '_') ? 4 : 3;
				$key = substr($chk, $len);
				$this->__set($key, $args[0] ?? null);
				break;
			case 'get':
				$len = ($chk[4] == '_') ? 4 : 3;
				$key = substr($chk, $len);
				return $this->__get($key);
			default:
				if ($pre[0] == 'i' && $pre[1] == 's') {
					$key = substr($chk, 2);
				} else {
					$key = $chk;
				}
				return $this->__get($key);
		}
	}

	public function __toString(): string
	{
		return json_encode($this->__serialize(), JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE); // PHP7.2+
	}

	// ======= SERIALIZABLE INTERFACE METHODS =======

	public function __serialize(): array
	{
		$this->LoadProperties();
		return get_object_vars($this);
	}

	public function __unserialize(array $data): void
	{
		foreach ($data as $key => $value) {
			$this->$key = $value;
		}
	}

	public function serialize(): ?string
	{
		//TODO can all cachers cope with embedded null's in strings ? NB internal cryption is slow!
		return Crypto::encrypt_string($this->__toString(),__CLASS__,'best');
//		return $this->__toString();
	}

	public function unserialize(string $serialized): void
	{
		$str = Crypto::decrypt_string($serialized,__CLASS__,'best');
		if (!$str) {
			throw new Exception('Invalid object data in '.__METHOD__);
		}
		$props = json_decode($str, true, JSON_INVALID_UTF8_IGNORE);
		if ($props) {
			foreach ($props as $key => $value) {
				$this->$key = $value;
			}
		} else {
			throw new Exception('Invalid object data in '.__METHOD__);
		}
	}

	/**
	 * Legacy method to initialize this object
	 * @deprecated since 3.0 instead supply object property-values as arguments
	 *  to the subclass constructor
	 *
	 * @abstract
	 * @internal
	 */
	protected function SetInitialValues()
	{
	}

	/**
	 * A convenience function to replicate a bit of the functionality of a
	 * IContentEditor object. The retrieved parameters are cached in this object,
	 * but not pushed back to the global cache.
	 *
	 * @param bool $deep optional flag whether to also process the page's non-core properties. Default false.
	 * @return array maybe empty
	 */
	public function ToData(bool $deep = false): array
	{
		$res = $this->_fields;
		if ($deep && $this->LoadProperties()) {
			$res += $this->_props;
		}
		return $res;
		//TODO sometimes even if !$deep: non-core properties 'tpltype_id','csstype_id' to support typed components for theme switching
	}

	/**
	 * Load all of this page's non-core properties (if any) into _props[]
	 * @since 3.0 Formerly protected _load_properties()
	 * @return bool indicating success
	 */
	public function LoadProperties(): bool
	{
		if (isset($this->_fields['content_id']) && $this->_fields['content_id'] > 0) {
			$db = Lone::get('Db');
			$query = 'SELECT prop_name,content FROM '.CMS_DB_PREFIX.'content_props WHERE content_id = ?';
			$dbr = $db->getAssoc($query, [(int)$this->_fields['content_id'] ]);
			$this->_props = $dbr; // might be empty
			$this->_propsloaded = true;
			return true;
		}
		return false;
	}

	/**
	 * @deprecated since 3.0 instead use LoadProperties()
	 * @return bool
	 */
	protected function _load_properties(): bool
	{
		return $this->LoadProperties();
	}

	/**
	 * Return this page's non-core properties
	 * @since 3.0 this method loads those properties if not already done
	 * @return array, maybe empty
	 */
	public function Properties(): array
	{
		if (!$this->_propsloaded) {
			$this->LoadProperties();
		}
		return $this->_props;
	}

	/**
	 * Test whether this page page has the named 'non-core' property.
	 * Properties will be loaded from the database if necessary.
	 *
	 * @param string $propname
	 * @return bool
	 */
	public function HasProperty(string $propname): bool
	{
		if (!$propname) {
			return false;
		}
		if (!$this->_propsloaded) {
			$this->LoadProperties();
		}
		if ($this->_propsloaded) {
			return isset($this->_props[$propname]);
		}
		return false;
	}

	/**
	 * Set the value of a the named 'non-core' property.
	 * (Used during tree-population)
	 * This method will pre-load all properties for this page if necessary.
	 *
	 * @param string $propname The property name
	 * @param mixed  $value The property value.
	 */
	public function SetPropertyValue(string $propname, $value)
	{
		if (!$this->_propsloaded) {
			$this->LoadProperties();
		}
		$this->_props[$propname] = $value;
	}

	/**
	 * Set the value of a the named 'non-core' property.
	 * (Used during tree-population)
	 * This method will NOT pre-load all properties for this page.
	 *
	 * @param string $propname The property name
	 * @param mixed $value The property value.
	 */
	public function SetPropertyValueNoLoad(string $propname, $value)
	{
		$this->_props[$propname] = $value;
	}

	/**
	 * Get the value for the named 'non-core' property|ies.
	 * Properties will be loaded from the database if necessary.
	 *
	 * @param mixed $propname property key(s) string | string[]
	 * @return mixed property value | null if the property does not exist
	 *  or assoc. array of same
	 */
	public function GetPropertyValue($propname)
	{
		if (!is_array($propname)) {
			if ($this->HasProperty($propname)) {
				return $this->_props[$propname];
			}
			return null;
		} else {
			if (!is_array($this->_props)) {
				$this->LoadProperties();
			}
			$ret = [];
			foreach ($propname as $key) {
				$ret[$key] = $this->_props[$key] ?? null;
			}
			return $ret;
		}
	}

	/**
	 * Return the value of a 'non-core' property, for display by Smarty.
	 *
	 * @param string $propname An optional property name to display. Default 'content_en'.
	 * The name may have been quoted upstream.
	 *
	 * @return string, maybe empty
	 */
	public function Show(string $propname = 'content_en')
	{
		$name = strtr(trim($propname, " \r\n\t'\""), ' ', '_');
		switch ($name) {
			case 'content_en':
			case 'pagedata':
				return $this->_props['content_en'] ?? '';
			default:
				return (string)$this->GetPropertyValue($name);
		}
	}

	/**
	 * Return a smarty resource identifier for the (possibly-themed) template assigned to this page.
	 * @todo theme-support
	 * @since 3.0
	 * @abstact
	 * @return string
	 */
	public function TemplateResource(): string
	{
/* TODO support typed templates for theming
		if (!empty($this->_props['tpltype_id'])) {
			if (X) {
				$theme = $TODOfunc(AppParams::get('frontend_theme', 'default').';'.$this->_props['tpltype_id']);
			} else {
				$theme = $TODOfunc($this->_props['tpltype_id']); //TODO adjust to suit smarty
			}
			return 'cms_theme:'.$theme;
		}
*/
		if (isset($this->_fields['template_id']) && $this->_fields['template_id'] > 0) {
			return 'cms_template:'.$this->_fields['template_id'];
		}
		return '';
	}

	/* *
	 * Return a smarty resource identifier for the (possibly-themed) stylesheet assigned to this page.
	 * @todo theme-support
	 * @since 3.0
	 * @abstact
	 * @return string
	 */
/* TODO support typed stylesheets for theming
 	public function StylesheetResource(): string
	{
		if (!empty($this->_props['csstype_id'])) {
			if (X) {
				$styler = $TODOfunc(AppParams::get('frontend_theme', 'default').';'.$this->_props['csstype_id']);
			} else {
				$styler = $TODOfunc($this->_props['csstype_id']); //TODO adjust to suit smarty
			}
			return 'cms_theme:'.$styler;
		}
		return '';
	}
*/
	/**
	 * Return the hierarchy of the current page.
	 * A string like #.##.## indicating the path to this page and its order
	 * This value uses the item order when calculating the output e.g. 3.3.3
	 * to indicate the third grandchild of the third child of the third root page.
	 * (Used during tree-management, page-editing)
	 *
	 * @return string
	 */
	public function Hierarchy(): string
	{
		if (isset($this->_fields['hierarchy'])) {
			return Lone::get('ContentOperations')->CreateFriendlyHierarchyPosition($this->_fields['hierarchy']);
		}
		return '';
	}

	/**
	 * Return whether this page is the default.
	 * The default page is the one that is displayed when no alias or pageid is specified in the route
	 * Only one content page can be the default.
	 *
	 * @return boolean
	 */
	public function DefaultContent(): bool
	{
		if ($this->IsDefaultPossible()) {
			return $this->_fields['default_content'] ?? false;
		}
		return false;
	}

	/* *
	 * Set whether this page should be considered the default.
	 * Note: does not modify the flags for any other content page.
	 *
	 * @param mixed $defaultcontent value recognised by cms_to_bool()
	 */
/*	public function SetDefaultContent($defaultcontent)
	{
		if ($this->IsDefaultPossible()) {
			$this->_fields['default_content'] = cms_to_bool($defaultcontent);
		}
	}
*/

	/**
	 * Return whether this page may be the default content page.
	 *
	 * @abstract
	 * @return bool Default true for content pages, false otherwise
	 */
	public function IsDefaultPossible(): bool
	{
		return (isset($this->_fields['type'])) ?
			(strcasecmp($this->_fields['type'], 'content') == 0) :
			false;
	}

	/**
	 * Return whether the current user is permitted to view this page.
	 *
	 * @since 1.11.12
	 * @abstract
	 * @return boolean Default true
	 */
	public function IsPermitted(): bool
	{
		return true;
	}

	/**
	 * Return whether this page can be displayed. Some content types
	 * (e.g. redirection links) are not viewable.
	 *
	 * @abstract
	 * @return bool Default true for content pages, false otherwise
	 */
	public function IsViewable(): bool
	{
		return (isset($this->_fields['type'])) ?
			(strcasecmp($this->_fields['type'], 'content') == 0) :
			false;
	}

	/**
	 * Return whether this page is a system page.
	 * System pages are used to handle things like 404 errors etc.
	 * (used during tree-construction)
	 *
	 * @abstract
	 * @return bool Default false
	 */
	public function IsSystemPage(): bool
	{
		return false;
	}

	/**
	 * Return whether this page is navigable and generates a useful URL.
	 * (Used during list-creation, page-editing?)
	 *
	 * @abstract
	 * @return bool Default true
	 */
	public function HasUsableLink(): bool
	{
		return true;
	}

	/* *
	 * Return whether a preview of this page can be generated
	 * (Used only during page-editing)
	 *
	 * @abstract
	 * @return bool Default false
	 */
/*	public function HasPreview(): bool
	{
		return false;
	}
*/
	/**
	 * Return whether this page type uses a template.
	 * (Used during list-creation, page-editing?)
	 *
	 * @abstract
	 * @since 2.0
	 * @return bool Default true for content pages, false otherwise
	 */
	public function HasTemplate(): bool
	{
		return (isset($this->_fields['type'])) ?
			(strcasecmp($this->_fields['type'], 'content') == 0) :
			false;
	}

	/* *
	 * Return whether this page type uses a template.
	 * (Used only during page-editing)
	 *
	 * @abstract
	 * @since 2.0
	 * @return array
	 */
/*	public function GetTabNames(): array
	{
		return ['ONE'=>'BLAH','TWO'=>'222','THREE'=>'3','FOUR'=>'four',];
	}
*/
	/* *
	 * Return whether this content type has content that can be used by a search module.
	 *
	 * @since 2.0
	 * @abstract
	 * @return boolean Default true
	 */
	public function HasSearchableContent(): bool
	{
		return true;
	}

	/* *
	 * Return whether this page type is searchable. This is for admin, not for runtime processing
	 *
	 * Searchable pages can be indexed by the search module.
	 *
	 * This function by default uses a combination of other abstract methods to
	 * determine whether this page is searchable but extended content types can override this.
	 *
	 * @since 2.0
	 * @return boolean
	 */
	public function IsSearchable(): bool
	{
		if (!$this->IsPermitted() || !$this->IsViewable() || !$this->HasTemplate() || $this->IsSystemPage()) {
			return false;
		}
		return $this->HasSearchableContent();
	}

	/* *
	 * Set the alias for this page.
	 * If an empty alias is supplied, and depending upon the doAutoAliasIfEnabled flag,
	 * and config entries a suitable alias may be calculated from other data in this page object.
	 * This method relies on the menutext and the name of the content page already being set.
	 *
	 * @param mixed string|null $alias The alias
	 * @param bool $doAutoAliasIfEnabled Whether an alias should be calculated or not.
	 */
/*	public function SetAlias(string $alias = '', bool $doAutoAliasIfEnabled = true)
	{
		$contentops = Lone::get('ContentOperations');
		$config = Lone::get('Config');
		if ($alias === '' && $this->doAutoAliasIfEnabled && $config['auto_alias_content']) {
			$alias = trim($this->_fields['menu_text']);
			if ($alias === '') {
				$alias = trim($this->_fields['content_name']);
			}

			// auto generate an alias
			$alias = munge_string_to_url($alias, true);
			$res = $contentops->CheckAliasValid($alias);
			if (!$res) {
				$alias = 'p'.$alias;
				$res = $contentops->CheckAliasValid($alias);
				if (!$res) {
					throw new CMSMS\ContentException(lang('invalidalias2'));
				}
			}
		}

		if ($alias) {
			// Make sure auto-generated new alias is not already in use on a different page, if it does, add "-2" to the alias

			// make sure we start with a valid alias.
			$res = $contentops->CheckAliasValid($alias);
			if (!$res) {
				throw new CMSMS\ContentException(lang('invalidalias2'));
			}

			// now auto-increment the alias
			$prefix = $alias;
			$num = 1;
			if (preg_match('/(.*)-([0-9]*)$/', $alias, $matches)) {
				$prefix = $matches[1];
				$num = (int) $matches[2];
			}
			$test = $alias;
			do {
				if (!$contentops->CheckAliasUsed($test, $this->_fields['content_id'])) {
					$alias = $test;
					break;
				}
				$num++;
				$test = $prefix.'-'.$num;
			} while ($num < 100);
			if ($num >= 100) {
				throw new CMSMS\ContentException(lang('aliasalreadyused'));
			}
		}

		$this->_fields['content_alias'] = $alias;
		//CHECME are these caches worth retaining? if not, ->delete()
//		$cache = Lone::get('LoadedData')
//		$cache->refresh('content_quicklist');
//		$cache->refresh('content_tree');
//		$cache->refresh('content_flatlist');
	}
*/

	/**
	 * Report whether this page is allowed to have child-page(s).
	 * Some content types e.g. separator do not have any child.
	 * (Used during list-creation, page-editing?)
	 *
	 * @since 0.11
	 * @abstract
	 * @return bool Default true
	 */
	public function WantsChildren(): bool
	{
		return true;
	}

	/**
	 * Return the timestamp representing when this object was first saved.
	 * (Used during list-creation)
	 *
	 * @return int UNIX UTC timestamp. Default 1.
	 */
	public function GetCreationDate(): int
	{
		$value = $this->_fields['create_date'] ?? '';
		return ($value) ? cms_to_stamp($value) : 1;
	}

	/**
	 * Return the timestamp representing when this object was last saved.
	 * Used by Smarty.
	 *
	 * @return int UNIX UTC timestamp. Default 1.
	 */
	public function GetModifiedDate(): int
	{
		$value = $this->_fields['modified_date'] ?? '';
		return ($value) ? cms_to_stamp($value) : $this->GetCreationDate();
	}

	/**
	 * Return an actionable URL for opening/previewing this page.
	 *
	 * @param bool $rewrite optional flag, default true.
	 * If true, and mod_rewrite is enabled, build an URL suitable for mod_rewrite.
	 * @return string
	 */
	public function GetURL(bool $rewrite = true): string
	{
		if ($rewrite) {
			if (!empty($this->_fields['default_content'])) {
				// use root url for default content
				return CMS_ROOT_URL . '/';
			}
			$config = Lone::get('Config');
			$url_rewriting = $config['url_rewriting'];
			if ($url_rewriting == 'mod_rewrite') {
				if ($this->_fields['page_url']) {
					$str = $this->_fields['page_url']; // we have an URL path
				} else {
					$str = $this->_fields['hierarchy_path'];
				}
				return CMS_ROOT_URL . '/' . $str . $config['page_extension'];
			} elseif ($url_rewriting == 'internal' && isset($_SERVER['PHP_SELF'])) {
				if ($this->_fields['page_url']) {
					$str = $this->_fields['page_url']; // we have an url path
				} else {
					$str = $this->_fields['hierarchy_path'];
				}
				return CMS_ROOT_URL . '/index.php/' . $str . $config['page_extension'];
			}
		}

		$config = Lone::get('Config');
		$alias = ($this->_fields['content_alias']) ? $this->_fields['content_alias'] : $this->_fields['content_id'];
		return CMS_ROOT_URL . '/index.php?' . $config['query_var'] . '=' . $alias;
	}

	/* *
	 * Return whether this page has children.
	 *
	 * @param bool $activeonly Optional flag whether to test only for active children. Default false.
	 * @return boolean
	 */
/*	public function HasChildren(bool $activeonly = false): bool
	{
		if ($this->_fields['content_id'] <= 0) {
			return false;
		}
		$ptops = Lone::get('PageTreeOperations');
		$node = $ptops->get_node_by_id($this->_fields['content_id']);
		if (!$node || !$node->has_children()) {
			return false;
		}

		if (!$activeonly) {
			return true;
		}
		$children = $node->get_children();
		if ($children) {
			for ($i = 0, $n = count($children); $i < $n; $i++) {
				$content = $children[$i]->get_content();
				if ($content->Active()) {
					return true;
				}
			}
		}

		return false;
	}
*/
	/* *
	 * Return the number of children of this page.
	 *
	 * @return int
	 */
/*	public function ChildCount(): int
	{
		$ptops = Lone::get('PageTreeOperations');
		$node = $ptops->get_node_by_id($this->_fields['content_id']);
		if ($node) {
			return $node->count_children();
		}
		return 0;
	}
*/
} // class
//backward-compatibility shiv
\class_alias(ContentBase::class, 'ContentBase', false);
