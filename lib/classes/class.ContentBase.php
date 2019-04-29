<?php
# Class for working with page content at runtime
# Copyright (C) 2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# BUT withOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.    See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace CMSMS;

use cms_config;
use CmsApp;
use Exception;
use const CMS_DB_PREFIX;
use const CMS_ROOT_URL;
use function lang;

/**
 * Page content class.
 * This is for preparation of displayed pages at runtime. The content is generally read-only.
 *
 * @since    2.3
 * @package    CMS
 */
class ContentBase
{
    /**
     * Numeric identifier of this page
     * integer
     * @internal
     */
    protected $_id = -1;

    /**
     * Name of this page, displayed during content management only?
     * string
     * @internal
     */
    protected $_name = '';

    /**
     * Alias of this page
     * string
     * @internal
     */
    protected $_alias = '';

    /**
     * The page type
     * string
     * @internal
     */
    protected $_type = 'content';

    /**
     * The id of the parent page, -2 if none
     * integer
     * @internal
     */
    protected $_parentid = -2;

    /**
     * This page's template id
     * integer
     * @internal
     */
    protected $_templateid = -1;

    /**
     * The item order of this page in its level
     * integer
     * @internal
     */
    protected $_itemorder = -1;

    /**
     * The full hierarchy of the content
     * string like '1.4.3'
     * @internal
     */
    protected $_hierarchy = '';

    /**
     * The full hierarchy of the content id's
     * string like '1.4.3'
     * @internal
     */
    protected $_idhierarchy = '';

    /**
     * The full path through the hierarchy
     * string like parent/parent/child
     *
     * @internal
     */
    protected $_hierarchypath = '';

    /**
     * The text to be displayed for this page in a menu
     * string
     * @internal
     */
    protected $_menutext = '';

    /**
     * The menu-item title/tip for this page
     * string
     * @internal
     */
    protected $_titleattribute = '';

    /**
     * The single-key which opens this page
     * string
     * @internal
     */
    protected $_accesskey = '';

    /**
     * Whether this page is active
     * boolean
     * @internal
     */
    protected $_active = false;

    /**
     * Whether this page is cachable
     * boolean
     * @internal
     */
    protected $_cachable = false;

    /**
     * Whether this page is the default
     * boolean
     * @internal
     */
    protected $_defaultcontent = false;

    /**
     * Whether this page requires secure access (regardless of the site as a whole)
     * boolean
     * @deprecated since 2.3
     * @internal
     */
    protected $_secure = false;

    /**
     * Whether this page is included in the menu
     * boolean
     * @internal
     */
    protected $_showinmenu = false;

    /**
     * Metadata (head tags) for this page
     * string
     * @internal
     */
    protected $_metadata = '';

    /**
     * Date/Time when the page properties were first saved
     * string
     * @internal
     */
    protected $_created = '';

    /**
     * Date/Time when the page properties were last saved
     * string
     * @internal
     */
    protected $_modified = '';

    /**
     * Custom page-URL
     * string
     * @internal
     */
    protected $_url = '';

    /**
     * Page content-block (content_en) property value
     * string
     * @internal
     */
    protected $_content = '';

    /**
     * Page-type-specific properties of this page, excluding content_en (from the content_properties table)
     * array
     * @internal
     */
    protected $_props = [];

    /**
	 * Constructor. Sets initial properties of this page from the supplied data.
     *
     * @param array $params properties to be set
     */
	public function __construct(array $params)
	{
/* the map from reported content-table fieldnames is:
    accesskey;
    active;
    cachable;
    content; (this is from the content_properties table)
    content_alias; >> _alias
    content_id;    >> _id
    content_name;  >> _name
    create_date; >> _created
    default_content; >> _defaultcontent
    hierarchy_path; >> _hierarchypath
    hierarchy;
    id_hierarchy; >> _idhierarchy
    item_order;   >> _itemorder
    metadata;
    modified_date; >> _modified
    page_url;     >> url
    parent_id;  >> _parentid
    secure;
    show_in_menu; >> _showinmenu
    template_id; >> _templateid
    titleattribute;
    type;
*/
        foreach ($params as $key => $value) {
            $key = strtr($key, ['_' => '']);
            if (strncmp($key, 'content', 7) == 0) {
                if ($key !== 'content') {
                    $key = substr($key, 7);
                }
            } elseif ($key == 'pageurl') {
                $key = 'url';
            }
            $this->__set($key, $value);
        }
	}

    /**
     * @ignore
     */
    public function __clone()
    {
        $this->_alias = '';
        $this->_id = -1;
        $this->_itemorder = -1;
        $this->_url = '';
    }

    /**
     * Support some of the antecedent-class property accessors.
     * @ignore
     */
    public function __call($name, $args)
    {
        $chk = strtolower($name);
        $pre = substr($chk, 0, 3);
        switch ($pre) {
            case 'set':
                $len = ($chk[4] == '_') ? 4 : 3;
                $key = substr($chk, $len);
                $this->__set($key, $args[0]);
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

    /**
	 * This should only be used during construction
     * @ignore
     */
    public function __set($key, $value)
    {
        switch (strtolower($key)) {
            case 'accesskey':
            case 'active':
            case 'alias':
            case 'cachable':
            case 'content':
            case 'created':
            case 'defaultcontent':
            case 'hierarchy':
            case 'hierarchypath':
            case 'id':
            case 'idhierarchy':
            case 'itemorder':
            case 'menutext':
            case 'metadata':
            case 'modified':
            case 'name':
            case 'parentid':
            case 'secure':
            case 'showinmenu':
            case 'templateid':
            case 'titleattribute':
            case 'type':
            case 'url':
                $key = '_'.$key;
                // no break here
            case '_props':
                $this->$key = $value;
                break;
            default:
                throw new Exception('Attempt to set unrecognised content-property: '.$key);
        }
    }

    /**
     * @ignore
     */
    public function __get($key)
    {
        switch (strtolower($key)) {
            case 'accesskey':
            case 'active':
            case 'alias':
            case 'cachable':
            case 'content':
            case 'created':
            case 'defaultcontent':
            case 'hierarchy':
            case 'hierarchypath':
            case 'id':
            case 'idhierarchy':
            case 'itemorder':
            case 'menutext':
            case 'metadata':
            case 'modified':
            case 'name':
            case 'owner':
            case 'parentid':
            case 'secure':
            case 'showinmenu':
            case 'templateid':
            case 'titleattribute':
            case 'type':
                $key = '_'.$key;
                // no break here
            case '_props':
                return $this->$key;
            case 'url':
                return $this->GetURL(); //too bad about the force re-write!
            default:
                throw new Exception('Attempt to retrieve unrecognised content-property: '.$key);
        }
    }

    /**
     * @ignore
     */
    protected function _load_properties() : bool
    {
        if ($this->_id > 0) {
            $this->_props = [];
            $db = CmsApp::get_instance()->GetDb();
            $query = 'SELECT prop_name,content FROM '.CMS_DB_PREFIX.'content_props WHERE content_id = ?';
            $dbr = $db->GetAssoc($query, [ (int)$this->_id ]);
            if ($dbr) {
                $this->_props = $dbr;
                return true;
            }
        }
        return false;
    }

    /**
     * Test whether this page page has the named property.
     * Properties will be loaded from the database if necessary.
     *
     * @param string $name
     * @return bool
     */
    public function HasProperty(string $propname) : bool
    {
        if (!$propname) {
            return false;
        }
        if (!is_array($this->_props)) {
            $this->_load_properties();
        }
        if (is_array($this->_props)) {
            return isset($this->_props[$propname]);
        }
        return false;
    }

    /* *
     * Set the value of a the named property. CHECKME this is probably irrelevant for runtime use
     * This method will load properties for this page page if necessary.
     *
     * @param string $name The property name
     * @param string $value The property value.
     */
/*  public function SetPropertyValue(string $name, $value)
    {
        if (!is_array($this->_props)) {
            $this->_load_properties();
        }
        $this->_props[$name] = $value;
    }
*/

    /**
     * Get the value for the named property.
     * Properties will be loaded from the database if necessary.
     *
     * @param string $name property key
     * @return mixed value, or null if the property does not exist.
     */
    public function GetPropertyValue(string $propname)
    {
        if ($this->HasProperty($propname)) {
            return $this->_props[$propname];
        }
    }

    /**
     * Return the value of a 'non-core' property, for display by Smarty.
     *
     * @param string $propname An optional property name to display. Default 'content_en'.
	 *
     * @return mixed
     */
    public function Show(string $propname = 'content_en')
    {
        switch ($propname) {
            case 'content_en':
                return $this->_content;
            case 'pagedata':
                return ''; // nothing to show for this one
            default:
                return $this->GetPropertyValue($propname);
        }
    }

    /**
     * Return a friendly name for this page type
     *
     * Normally this returns a string representing the name of the content type
     * translated into the user's current language
	 * @abstact
     *
     * @return string
     */
    public function FriendlyName() : string
    {
        return lang($this->_type);
    }

    /**
     * Return a smarty resource string for the template assigned to this page.
     *
     * @return string
     */
    public function TemplateResource() : string
    {
        if (strcasecmp($this->_type, 'content') == 0) {
            return 'cms_template:'.$this->templateid;
        }
        return '';
    }

    /**
     * Return the hierarchy of the current page.
     * A string like #.##.## indicating the path to this page and its order
     * This value uses the item order when calculating the output e.g. 3.3.3
     * to indicate the third grandchild of the third child of the third root page.
     *
     * @return string
     */
    public function Hierarchy() : string
    {
        $contentops = ContentOperations::get_instance();
        return $contentops->CreateFriendlyHierarchyPosition($this->_hierarchy);
    }

    /**
     * Return whether this page is the default.
     * The default page is the one that is displayed when no alias or pageid is specified in the route
     * Only one content page can be the default.
     *
     * @return boolean
     */
    public function DefaultContent() : bool
    {
        if ($this->IsDefaultPossible()) {
            return $this->_defaultcontent;
        }
        return false;
    }

    /* *
     * Set whether this page should be considered the default.
     * Note: does not modify the flags for any other content page.
     *
     * @param mixed $defaultcontent value recognised by cms_to_bool()
     */
/*    public function SetDefaultContent($defaultcontent)
    {
        if ($this->IsDefaultPossible()) {
            $this->_defaultcontent = cms_to_bool($defaultcontent);
        }
    }
*/

    /**
     * Return whether this page may be the default page.
     *
     * @abstract TODO
     * @return boolean
     */
    public function IsDefaultPossible() : bool
    {
        return (strcasecmp($this->type, 'content') == 0); //TODO support types from independent sources
    }

    /**
     * Return whether the current user is permitted to view this page.
     *
     * @since 1.11.12
     * @abstract TODO
     * @return boolean Default true
     */
    public function IsPermitted() : bool
    {
        return true;
    }

    /**
     * Return whether this content type is viewable (i.e: can be rendered).
     * Some content types (like redirection links) are not viewable.
     *
     * @abstract TODO
     * @return boolean Default true
     */
    public function IsViewable() : bool
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
/*    public function IsSearchable() : bool
    {
        if (!$this->isPermitted() || !$this->IsViewable() || !$this->HasTemplate() || $this->IsSystemPage()) {
            return false;
        }
        return $this->HasSearchableContent();
    }
*/
    /* *
     * Set this page alias for this page page.
     * If an empty alias is supplied, and depending upon the doAutoAliasIfEnabled flag,
     * and config entries a suitable alias may be calculated from other data in this page object.
     * This method relies on the menutext and the name of the content page already being set.
     *
     * @param mixed string|null $alias The alias
     * @param bool $doAutoAliasIfEnabled Whether an alias should be calculated or not.
     */
/*    public function SetAlias($alias = '', $doAutoAliasIfEnabled = true)
    {
        $contentops = ContentOperations::get_instance();
        $config = cms_config::get_instance();
        if ($alias === '' && $doAutoAliasIfEnabled && $config['auto_alias_content']) {
            $alias = trim($this->_menutext);
            if ($alias === '') {
                $alias = trim($this->_name);
            }

            // auto generate an alias
            $alias = munge_string_to_url($alias, true);
            $res = $contentops->CheckAliasValid($alias);
            if (!$res) {
                $alias = 'p'.$alias;
                $res = $contentops->CheckAliasValid($alias);
                if (!$res) {
                    throw new CmsContentException(lang('invalidalias2'));
                }
            }
        }

        if ($alias) {
            // Make sure auto-generated new alias is not already in use on a different page, if it does, add "-2" to the alias

            // make sure we start with a valid alias.
            $res = $contentops->CheckAliasValid($alias);
            if (!$res) {
                throw new CmsContentException(lang('invalidalias2'));
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
                if (!$contentops->CheckAliasUsed($test, $this->_id)) {
                    $alias = $test;
                    break;
                }
                $num++;
                $test = $prefix.'-'.$num;
            } while ($num < 100);
            if ($num >= 100) {
                throw new CmsContentException(lang('aliasalreadyused'));
            }
        }

        $this->_alias = $alias;
        //CHECMe are these caches worth retaining for admin use only?
        global_cache::clear('content_quicklist');
        global_cache::clear('content_tree');
        global_cache::clear('content_flatlist');
    }
*/

    /**
     * Return the timestamp representing when this object was first saved.
     *
     * @return int Unix Timestamp
     */
    public function GetCreationDate() : int
    {
        return strtotime($this->_created);
    }

    /**
     * Return the timestamp representing when this object was last saved.
     *
     * @return int Unix Timestamp
     */
    public function GetModifiedDate() : int
    {
        return strtotime($this->_modified);
    }

    /**
     * Return the internally-generated URL for this page.
     *
     * @param bool $rewrite optional flag, default true.
     * If true, and mod_rewrite is enabled, build an URL suitable for mod_rewrite.
     * @return string
     */
    public function GetURL(bool $rewrite = true) : string
    {
        $base_url = CMS_ROOT_URL;
        // use root_url for default content
        if ($this->_defaultcontent) {
            return $base_url . '/';
        }

        $config = cms_config::get_instance();
        if ($rewrite) {
            $url_rewriting = $config['url_rewriting'];
            $page_extension = $config['page_extension'];
            if ($url_rewriting == 'mod_rewrite') {
                if ($this->_url) {
                    $str = $this->_url; // we have an URL path
                } else {
                    $str = $this->_hierarchypath;
                }
                return $base_url . '/' . $str . $page_extension;
            } elseif (isset($_SERVER['PHP_SELF']) && $url_rewriting == 'internal') {
                $str = $this->hierarchypath;
                if ($this->_url) {
                    $str = $this->_url;
                } // we have a url path
                return $base_url . '/index.php/' . $str . $page_extension;
            }
        }

        $alias = ($this->_alias) ? $this->_alias : $this->_id;
        return $base_url . '/index.php?' . $config['query_var'] . '=' . $alias;
    }

	/**
     * Return whether this page has children.
     *
     * @param bool $activeonly Optional flag whether to test only for active children. Default false.
     * @return boolean
     */
    public function HasChildren(bool $activeonly = false) : bool
    {
        if ($this->_id <= 0) {
            return false;
        }
        $hm = CmsApp::get_instance()->GetHierarchyManager();
        $node = $hm->quickfind_node_by_id($this->_id);
        if (!$node || !$node->has_children()) {
            return false;
        }

        if (!$activeonly) {
            return true;
        }
        $children = $node->get_children();
        if ($children) {
            for ($i = 0, $n = count($children); $i < $n; $i++) {
                $content = $children[$i]->getContent();
                if ($content->Active()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Return the number of children of this page.
     *
     * @return int
     */
    public function ChildCount() : int
    {
        $hm = CmsApp::get_instance()->GetHierarchyManager();
        $node = $hm->find_by_tag('id', $this->_id);
        if ($node) {
            return $node->count_children();
        }
        return 0;
    }
} // class

//backward-compatibility shiv
\class_alias(ContentBase::class, 'ContentBase', false);
