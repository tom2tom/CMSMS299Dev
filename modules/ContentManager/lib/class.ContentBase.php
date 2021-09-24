<?php
/*
Base content-editing class
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace ContentManager;

use ContentManager\Utils;
use CMSMS\AdminUtils;
use CMSMS\AppParams;
use CMSMS\ContentException;
use CMSMS\ContentOperations;
use CMSMS\Crypto;
use CMSMS\DeprecationNotice;
use CMSMS\Events;
use CMSMS\FileType;
use CMSMS\FormUtils;
use CMSMS\IContentEditor;
use CMSMS\internal\content_assistant;
use CMSMS\Route;
use CMSMS\RouteOperations;
use CMSMS\SingleItem;
use CMSMS\Url;
use CMSMS\Utils as AppUtils;
use Exception;
use RuntimeException;
use Serializable;
use const CMS_DB_PREFIX;
use const CMS_DEPREC;
use const CMS_ROOT_URL;
use function add_page_foottext;
use function check_permission;
use function cms_join_path;
use function cms_to_stamp;
use function CMSMS\specialize;
use function create_file_dropdown;
use function debug_buffer;
use function endswith;
use function get_userid;
use function munge_string_to_url;

/**
 * Base content-editing class for the CmsContentManager module.
 *
 * @since	0.8
 * @package	CMS
 */
abstract class ContentBase implements IContentEditor, Serializable
{
	/**
	 * Lang key for tab name
	 * @ignore
	 */
	const TAB_MAIN = 'za_1main_tab__';

	/**
	 * Lang key for tab name
	 * @ignore
	 */
	const TAB_DISPLAY = 'za_2display_tab__';

	/**
	 * Lang key for tab name
	 * @ignore
	 */
	const TAB_OPTIONS = 'zz_1options_tab__';

	/**
	 * Lang key for tab names
	 * @ignore
	 */
	const TAB_LONGOPTS = 'zz_2logic_tab__';
	const TAB_LOGIC = 'zz_2logic_tab__'; //deprecated since 2.0

	/**
	 * Lang key for tab name
	 * @ignore
	 */
	const TAB_NAV = 'zz_3nav_tab__';

	/**
	 * @ignore
	 */
	const TAB_PERMS = 'zz_4perms_tab__';

	/**
	 * @ignore
	 */
	const CMS_CONTENT_HIDDEN_NAME = '--------';

	// NOTE any private or static property will not be serialized

	/**
	 * Module object
	 * @ignore
	 */
	protected $mod;

	/**
	 * Module name, used for translation-domain
	 * @ignore
	 */
	protected $domain;

	/**
	 * The numeric identifier of this content
	 * Integer
	 *
	 * @ignore
	 */
	protected $mId = -1;

	/**
	 * The name of this content (like a filename)
	 * String
	 *
	 * @internal
	 */
	protected $mName = '';

	/**
	 * The owner of this content
	 * Integer
	 *
	 * @internal
	 */
	protected $mOwner = -1;

	/**
	 * The ID of the parent, -1 if none, -2 if new content
	 * Integer
	 */
	protected $mParentId = -2;

	/**
	 * The numeric id of the template used to display this page. -1 if none.
	 * Integer
	 *
	 * @internal
	 */
	protected $mTemplateId = -1;

	/**
	 * The item order of this content in its level
	 * Integer
	 *
	 * @internal
	 */
	protected $mItemOrder = -1;

	/**
	 * The metadata (head tags) for this content
	 *
	 * @internal
	 */
	protected $mMetadata = '';

	/**
	 * @internal
	 */
	protected $mTitleAttribute = '';

	/**
	 * @internal
	 */
	protected $mAccessKey = '';

	/**
	 * @internal
	 * Unsigned integer
	 */
	protected $mTabIndex = 0;

	/**
	 * The full hierarchy of this content
	 * String of the form : '1.4.3'
	 *
	 * @internal
	 */
	protected $mHierarchy = '';

	/**
	 * The full hierarchy of this content id's
	 * String of the form : '1.4.3'
	 *
	 * @internal
	 */
	protected $mIdHierarchy = '';

	/**
	 * The full path through the hierarchy
	 * String of the form : parent/parent/child
	 *
	 * @internal
	 */
	protected $mHierarchyPath = '';

	/**
	 * What should be displayed in a menu
	 *
	 * @internal
	 */
	protected $mMenuText = '';

	/**
	 * Is this content active ?
	 * Integer : 0 / 1
	 *
	 * @internal
	 */
	protected $mActive = 0;

	/**
	 * Alias of this content
	 *
	 * @internal
	 */
	protected $mAlias = '';

	/**
	 * Old content-alias
	 *
	 * @internal
	 */
	protected $mOldAlias = '';

	/**
	 * Is this page cachable?
	 * @since 2.0 Default true, formerly false
	 * @internal
	 */
	protected $mCachable = true;

	/**
	 * Secure access to this page?
	 * @deprecated since 2.0
	 * Integer : 0 / 1
	 *
	 * @internal
	 */
	protected $mSecure = 0;

	/* *
	 * The content-type of this page ('content','link' etc)
	 * String
	 *
	 * @internal
	 */
//	protected $mType = ''; not cached, derived from classname

	/**
	 * URL
	 *
	 * @internal
	 */
	protected $mURL = '';

	/**
	 * Should it show up in the menu?
	 * Integer : 0 / 1
	 *
	 * @internal
	 */
	protected $mShowInMenu = 0;

	/**
	 * Is this page the default?
	 * Integer : 0 / 1
	 *
	 * @internal
	 */
	protected $mDefaultContent = 0;

	/**
	 * Id of user who most-recently modified this content
	 * 0 indicates none
	 *
	 * @internal
	 */
	protected $mLastModifiedBy = 0;

	/**
	 * Creation date
	 *
	 * @internal
	 */
	protected $mCreationDate = '';

	/**
	 * Modification date
	 *
	 * @internal
	 */
	protected $mModifiedDate = '';

	/**
	 * Additional editors array
	 *
	 * @internal
	 */
	protected $mAdditionalEditors;

	/**
	 * Comma-separated sequence of stylesheet id's and/or stylesheet-group id's (groups < 0)
	 *
	 * @internal
	 */
	protected $mStyles = '';

	/**
	 * The 'non-core' properties
	 *
	 * @internal
	 */
	protected $_props;

	/**
	 * @internal
	 */
	private $_prop_defaults;

	/**
	 * state or meta information
	 *
	 * @internal
	 */
	private $_properties = [];

	/**
	 * @internal
	 */
	private $_editable_properties;

	/************************/
	/* Construction related	*/
	/************************/

	/**
	 * @param mixed $params Optional array of property names and values, or null
	 */
	public function __construct($params = null)
	{
		$this->mod = AppUtils::get_module('ContentManager');
		$this->domain = $this->mod->GetName();
		if( is_array($params) ) {
			$this->LoadFromData($params);
		}
		else {
			//legacy mode
			$this->SetInitialValues();
		}
		$this->SetProperties();
	}

	/**
	 * @ignore
	 */
	public function __clone()
	{
		$this->mId = -1;
		$this->mItemOrder = -1;
		$this->mURL = '';
		$this->mAlias = '';
	}

	/**
	 * @ignore
	 */
	public function __toString()
	{
		return json_encode(get_object_vars($this), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
	}

	/**
	 * Convert this object to an array.
	 *
	 * This can be considered a simple DTO (Data Transfer Object)
	 *
	 * @since 2.0
	 * @return array
	 */
	public function ToData()
	{
		$l = $this->HasUsableLink();
		$w = $this->WantsChildren();
		return [
			'accesskey' => $this->mAccessKey,
			'active' => (($this->mActive)?1:0),
			'cachable' => (($this->mCachable)?1:0),
			'content_alias' => $this->mAlias,
			'content_id' => $this->mId,
			'content_name' => $this->mName,
			'create_date' => $this->mCreationDate,
			'default_content' => (($this->mDefaultContent)?1:0),
			'has_usable_link' => $l, // method, not property
			'hierarchy' => $this->mHierarchy,
			'hierarchy_path' => $this->mHierarchyPath,
			'id_hierarchy' => $this->mIdHierarchy,
			'item_order' => $this->mItemOrder,
			'last_modified_by' => $this->mLastModifiedBy,
			'menu_text' => $this->mMenuText,
			'metadata' => $this->mMetadata,
			'modified_date' => $this->mModifiedDate,
			'owner_id' => $this->mOwner,
			'page_url' => (($this->mURL)?1:0),
			'parent_id' => $this->mParentId,
			'secure' => $this->mSecure, //deprecated since 2.0
			'show_in_menu' => (($this->mShowInMenu)?1:0),
			'styles' => $this->mStyles,
			'tabindex' => $this->mTabIndex,
			'template_id' => $this->mTemplateId,
			'titleattribute' => $this->mTitleAttribute,
//			'type' => $this->mType,
			'wants_children' => $w, // method, not property
		];
		//TODO non-core properties 'tpltype_id','csstype_id' to support typed components for theme switching

	}

	/**
	 * Set the properties of this page from a database row or equivalent array.
	 *
	 * @param array $data Row loaded from the database
	 * @param bool  $loadProperties Optional flag whether to also load non-core
	 *  properties (via a database SELECT). Default false.
	 * @return	bool indicating success
	 */
	public function LoadFromData($data, $loadProperties = false)
	{
		$this->mAccessKey	 = $data['accesskey'] ?? null;
		$this->mActive		 = !empty($data['active']);
		$this->mCachable	 = !empty($data['cachable']);
		$this->mId			 = $data['content_id'] ?? 0;
		$this->mName		 = $data['content_name'] ?? null;
		$this->mAlias		 = $data['content_alias'] ?? null;
		$this->mOldAlias	 = $data['content_alias'] ?? null;
		$this->mCreationDate = $data['create_date'] ?? null;
		$this->mDefaultContent = !empty($data['default_content']);
		$this->mHierarchy	 = $data['hierarchy'] ?? null;
		$this->mHierarchyPath = $data['hierarchy_path'] ?? null;
		$this->mIdHierarchy	 = $data['id_hierarchy'] ?? null;
		$this->mItemOrder	 = $data['item_order'] ?? 0;
		$this->mLastModifiedBy = $data['last_modified_by'] ?? 0;
		$this->mMenuText	 = $data['menu_text'] ?? null;
		$this->mMetadata	 = $data['metadata'] ?? null;
		$this->mModifiedDate = $data['modified_date'] ?? null;
		$this->mOwner		 = $data['owner_id'] ?? 0;
		$this->mURL			 = $data['page_url'] ?? null;
		$this->mParentId	 = $data['parent_id'] ?? -1; //root, no parent
		$this->mSecure		 = $data['secure'] ?? false; //deprecated since 2.0
		$this->mShowInMenu	 = !empty($data['show_in_menu']);
		$this->mStyles		 = $data['styles'] ?? null; //since 2.0, replaces design_id
		$this->mTabIndex	 = $data['tabindex'] ?? 0; //since 2.0, default formerly was 1
		$this->mTemplateId	 = $data['template_id'] ?? 0;
		$this->mTitleAttribute = $data['titleattribute'] ?? null;
//		$this->mType		= $data['type'] ?? null;

		$result = true;
		if( $loadProperties ) {
			$this->_load_properties();
			if( !is_array($this->_props) ) {
				$result = false;
				$this->SetInitialValues();
			}
		}

		$this->Load();
		return $result;
	}

	/**
	 * Set initial property-values
	 *
	 * @abstract
	 * @internal
	 */
	protected function SetInitialValues()
	{
	}

	/**
	 * Function for the subclass to parse out data for its parameters.
	 * This method is typically called from an editor form to allow
	 * modifying this content object from form input fields (usually $_POST)
	 *
	 * @param array $params The input array (usually from $_POST)
	 * @param bool  $editing Indicates whether this is an edit or add operation.
	 * @abstract
	 */
	public function FillParams($params, $editing = false)
	{
		// content property parameters
		$parameters = ['extra1','extra2','extra3','image','thumbnail'];
		foreach( $parameters as $oneparam ) {
			if( isset($params[$oneparam]) ) $this->SetPropertyValue($oneparam, $params[$oneparam]);
		}

		// go through the list of base parameters
		// setting them from params

		// title
		if( isset($params['title']) ) $this->mName = strip_tags($params['title']);

		// menu text
		if( isset($params['menutext']) ) $this->mMenuText = strip_tags(trim($params['menutext']));

		// parent id
		if( isset($params['parent_id']) ) {
			if( $params['parent_id'] == -2 && !$editing ) $params['parent_id'] = -1;
			if( $this->mParentId != $params['parent_id'] ) {
				$this->mHierarchy = '';
				$this->mItemOrder = -1;
			}
			$this->mParentId = (int) $params['parent_id'];
		}

		// active
		if( isset($params['active'])) {
			$this->mActive = (int) $params['active'];
			if( $this->DefaultContent() ) $this->mActive = 1;
		}

		// show in menu
		if( isset($params['showinmenu']) ) $this->mShowInMenu = (int) $params['showinmenu'];

		// alias
		// alias field can exist if the user has manage all content... OR alias is a basic property
		// and this user has other edit rights to this content page.
		// empty value on the alias field means we need to generate a new alias
		$new_alias = null;
		$alias_field_exists = isset( $params['alias'] );
		if( isset($params['alias']) ) $new_alias = trim(strip_tags($params['alias']));
		// if we are adding or we have a new alias, set alias to the field value, or calculate one, adjust as needed
		if( $new_alias || $alias_field_exists ) {
			$this->SetAlias($new_alias);
		}

		// target
		if( isset($params['target']) ) {
			$val = strip_tags($params['target']);
			if( $val == '---' ) $val = '';
			$this->SetPropertyValue('target', $val);
		}

		// title attribute
		if( isset($params['titleattribute']) ) $this->mTitleAttribute = trim(strip_tags($params['titleattribute']));

		// accesskey
		if( isset($params['accesskey']) ) $this->mAccessKey = strip_tags($params['accesskey']);

		// tab index
		if( isset($params['tabindex']) ) $this->mTabIndex = (int) $params['tabindex'];

		// cachable
		if( isset($params['cachable']) ) {
			$this->mCachable = (int) $params['cachable'];
		}
		else {
			$this->_handleRemovedBaseProperty('cachable','mCachable');
		}

		// secure (deprecated since 2.0)
		if (isset($params['secure'])) {
			$this->mSecure = (int) $params['secure'];
		}
		else {
			$this->_handleRemovedBaseProperty('secure','mSecure');
		}

		//stylesheet id's (from checkboxes array)
		if( !empty($params['styles'])) {
			if( is_array($params['styles']) ) {
				$this->mStyles = implode(',',$params['styles']);
			}
			else {
				$this->mStyles = trim($params['styles']);
			}
		}
		else {
			$this->mStyles = null;
//			$this->_handleRemovedBaseProperty('styles','mStyles'); //CHECKME
		}

		// url
		if( isset($params['page_url']) ) {
			$tmp = trim($params['page_url']);
			if( $tmp ) {
				$this->mURL = (new Url())->sanitize($tmp);
			}
			else {
				$this->mURL = '';
				$this->_handleRemovedBaseProperty('page_url','mURL');
			}
		}
		else {
			$this->mURL = '';
			$this->_handleRemovedBaseProperty('page_url','mURL');
		}

		// owner
		if( isset($params['owner_id']) ) $this->SetOwner((int) $params['owner_id']);

		// additional editors
		if( isset($params['additional_editors']) ) {
			$addtarray = [];
			if( is_array($params['additional_editors']) ) {
				foreach( $params['additional_editors'] as $addt_user_id ) {
					$addtarray[] = (int) $addt_user_id;
				}
			}
			$this->SetAdditionalEditors($addtarray);
		}
	}

	/**
	 * Return html to display an input element for modifying a property of this page.
	 *
	 * @abstract
	 * @param string $propname The property name
	 * @param bool $adding Whether we are in add or edit mode.
	 * @return array 3- or 4-members
	 * [0] = heart-of-label 'for="someid">text' | text
	 * [1] = popup-help | ''
	 * [2] = input element | text
	 * [3] = optional extra displayable content
	 * or empty
	 */
	public function ShowElement($propname, $adding)
	{
		$id = 'm1_';

		switch( $propname ) {
		case 'title':
			return [
			'for="in_title">* '.$this->mod->Lang('title'),
			AdminUtils::get_help_tag($this->domain,'help_content_title',$this->mod->Lang('help_title_content_title')),
			'<input type="text" id="in_title" name="'.$id.'title" required="required" value="'. specialize($this->mName).'" />'
			];

		case 'menutext':
			return [
			'for="in_menutext">'.$this->mod->Lang('menutext'),
			AdminUtils::get_help_tag($this->domain,'help_content_menutext',$this->mod->Lang('help_title_content_menutext')),
			'<input type="text" id="in_menutext" name="'.$id.'menutext" value="'. specialize($this->mMenuText).'" />'
			];

		case 'parent':
			$input = AdminUtils::CreateHierarchyDropdown($this->mId, $this->mParentId, 'parent_id', ($this->mId <= 0), true, true, true);
			if( !($input || check_permission(get_userid(),'Manage All Content')) ) {
				return [
				'','',
				'<input type="hidden" name="'.$id.'parent_id" value="'.$this->mParentId.'" />'
				];
			}
			if( $input ) {
				return [
				'for="parent_id">* ' .$this->mod->Lang('parent'),
				AdminUtils::get_help_tag($this->domain,'help_content_parent',$this->mod->Lang('help_title_content_parent')),
				$input];
			}
			break;

		case 'active':
			if( !$this->DefaultContent() ) {
				return [
				'for="id_active">'.$this->mod->Lang('active'),
				AdminUtils::get_help_tag($this->domain,'help_content_active',$this->mod->Lang('help_title_content_active')),
				'<input type="hidden" name="'.$id.'active" value="0" /><input type="checkbox" id="id_active" class="pagecheckbox" name="'.$id.'active" value="1"'.($this->mActive?' checked="checked"':'').' />'
				];
			}
			break;

		case 'showinmenu':
			return [
			'for="showinmenu">'.$this->mod->Lang('showinmenu'),
			AdminUtils::get_help_tag($this->domain,'help_content_showinmenu',$this->mod->Lang('help_title_content_showinmenu')),
			'<input type="hidden" name="'.$id.'showinmenu" value="0" /><input type="checkbox" id="showinmenu" class="pagecheckbox" value="1" name="'.$id.'showinmenu"'.($this->mShowInMenu?' checked="checked"':'').' />'
			];

		case 'target':
			$arr = [
				$this->mod->Lang('none') => '---',
				'blank' => '_blank',
				'parent' => '_parent',
				'self' => '_self',
				'top' => '_top',
			];
			$sel = $this->GetPropertyValue('target');
 			if (!$sel) $sel = '---';
			$input = FormUtils::create_select([ // DEBUG
				'type' => 'drop',
				'name' => 'target',
				'htmlid' => 'target',
				'getid' => $id,
				'multiple' => false,
				'options' => $arr,
				'selectedvalue' => $sel,
			]);
			return [
			'for="target">'.$this->mod->Lang('target'),
			AdminUtils::get_help_tag($this->domain,'help_content_target',$this->mod->Lang('help_title_content_target')),
			$input
			];

		case 'alias':
			return [
			'for="alias">'.$this->mod->Lang('pagealias'),
			AdminUtils::get_help_tag($this->domain,'help_page_alias',$this->mod->Lang('help_title_page_alias')),
			'<input type="text" id="alias" name="'.$id.'alias" value="'.$this->mAlias.'" />'
			];

		case 'cachable':
			return [
			'for="in_cachable">'.$this->mod->Lang('cachable'),
			AdminUtils::get_help_tag($this->domain,'help_content_cachable',$this->mod->Lang('help_title_content_cachable')),
			'<input type="hidden" name="'.$id.'cachable" value="0" /><input type="checkbox" id="in_cachable" class="pagecheckbox" value="1" name="'.$id.'cachable"'.($this->mCachable?' checked="checked"':'').' />'
			];

		case 'secure':
			return [
			'for="secure">'.$this->mod->Lang('secure_page'),
			AdminUtils::get_help_tag($this->domain,'help_content_secure',$this->mod->Lang('help_title_content_secure')),
			'<input type="hidden" name="'.$id.'secure" value="0"/><input type="checkbox" id="secure" class="pagecheckbox" value="1" name="'.$id.'secure"'.($this->mSecure?' checked="checked"':'').' />'];

		case 'page_url':
			if( !$this->DefaultContent() ) {
				$config = SingleItem::Config();
				$pretty_urls = $config['url_rewriting'] == 'none' ? 0 : 1;
				if( $pretty_urls != 0) {
					$marker = ( AppParams::get('content_mandatory_urls',0) ) ? '*' : '';
					return [
					$marker.'for="page_url">'.$this->mod->Lang('page_url'),
					AdminUtils::get_help_tag($this->domain,'help_page_url',$this->mod->Lang('help_title_page_url')),
					'<input type="text" id="page_url" name="'.$id.'page_url" size="50" maxlength="255" value="'.$this->mURL.'" />'
					];
				}
			}
			break;

		case 'styles':
			$styles = explode(',',$this->mStyles);
			list($sheets,$grouped,$js) = Utils::get_sheets_data($styles);
			if( $sheets ) {
				if( $js ) {
					add_page_foottext($js);
				}
				$smarty = SingleItem::Smarty();
				$tpl = $smarty->createTemplate($this->mod->GetTemplateResource('setstyles.tpl')); //,null,null,$smarty);
				$tpl->assign('mod',$this->mod)
				 ->assign('actionid',$id)
				 ->assign('grouped',$grouped)
				 ->assign('sheets',$sheets);
				$input = $tpl->fetch();
				return [
				'for="allsheets">'.$this->mod->Lang('stylesheets'),
				AdminUtils::get_help_tag($this->domain,'info_styles',$this->mod->Lang('help_title_styles')),
				$input
				];
			}
			break;

/* this is handled upstream
		case 'type':
			// TODO a selector if $adding, or else just text ?
			$input = FormUtils::create_select([
				'type' => 'drop',
				'name' => 'content_type',
				'htmlid' => 'content_type',
				'getid' => $id,
				'multiple' => false,
				'options' => array_flip($existingtypes),
				'selectedvalue' => $content_type,
			]);
			//TODO js to handle selector-change
			return [
			'for="content_type">* ' .$this->Lang('prompt_editpage_contenttype'),
			AdminUtils::get_help_tag($domain,'help_content_type',$this->Lang('help_title_content_type')),
			$input
			];
*/
		case 'image':
			$config = SingleItem::Config();
			$dir = cms_join_path($config['image_uploads_path'],AppParams::get('content_imagefield_path'));
			$data = $this->GetPropertyValue('image');
			$filepicker = AppUtils::get_filepicker_module();
			if( $filepicker ) {
				$profile = $filepicker->get_default_profile( $dir, get_userid() );
				$profile = $profile->overrideWith([ 'top'=>$dir,'type'=>FileType::IMAGE ]);
				$input = $filepicker->get_html($id.'image',$data,$profile);
			}
			else {
				$input = create_file_dropdown($id.'image',$dir,$data,'jpg,jpeg,png,gif','',true,'','thumb_',0,1);
			}
			if( !$input ) return false;
			return [
			'for="image">'.$this->mod->Lang('image'),
			AdminUtils::get_help_tag($this->domain,'help_content_image',$this->mod->Lang('help_title_content_image')),
			$input
			];

		case 'thumbnail':
			$config = SingleItem::Config();
			$dir = cms_join_path($config['image_uploads_path'],AppParams::get('content_thumbnailfield_path'));
			$data = $this->GetPropertyValue('thumbnail');
			$filepicker = AppUtils::get_filepicker_module();
			if( $filepicker ) {
				$profile = $filepicker->get_default_profile( $dir, get_userid() );
				$profile = $profile->overrideWith([ 'top'=>$dir,'type'=>FileType::IMAGE,'match_prefix'=>'thumb_' ]);
				$input = $filepicker->get_html($id.'thumbnail',$data,$profile);
			}
			else {
				$input = create_file_dropdown($id.'thumbnail',$dir,$data,'jpg,jpeg,png,gif','',true,'','thumb_',0,1);
			}
			if( !$input ) return false;
			return [
			'for="thumbnail">'.$this->mod->Lang('thumbnail'),
			AdminUtils::get_help_tag($this->domain,'help_content_thumbnail',$this->mod->Lang('help_title_content_thumbnail')),
			$input
			];

		case 'titleattribute':
			return [
			'for="titleattribute">'.$this->mod->Lang('titleattribute'),
			AdminUtils::get_help_tag($this->domain,'help_content_titleattribute',$this->mod->Lang('help_title_content_titleattribute')),
			'<input type="text" id="titleattribute" name="'.$id.'titleattribute" size="80" maxlength="255" value="'.specialize($this->mTitleAttribute).'" />'
			];

		case 'accesskey':
			return [
			'for="accesskey">'.$this->mod->Lang('accesskey'),
			AdminUtils::get_help_tag($this->domain,'help_content_accesskey',$this->mod->Lang('help_title_content_accesskey')),
			'<input type="text" id="accesskey" name="'.$id.'accesskey" maxlength="5" size="3" value="'. specialize($this->mAccessKey).'" />'
			];

		case 'tabindex':
			return [
			'for="tabindex">'.$this->mod->Lang('tabindex'),
			AdminUtils::get_help_tag($this->domain,'help_content_tabindex',$this->mod->Lang('help_title_content_tabindex')),
			'<input type="text" id="tabindex" name="'.$id.'tabindex" maxlength="3" size="3" value="'.specialize($this->mTabIndex).'" />'
			]; // prob. redundant cleaner

		case 'extra1':
			return [
			'for="extra1">'.$this->mod->Lang('extra1'),
			AdminUtils::get_help_tag($this->domain,'help_content_extra1',$this->mod->Lang('help_title_content_extra1')),
			'<input type="text" id="extra1" name="'.$id.'extra1" size="80" maxlength="255" value="'. specialize($this->GetPropertyValue('extra1')).'" />'
			];

		case 'extra2':
			return [
			'for="extra2">'.$this->mod->Lang('extra2'),
			AdminUtils::get_help_tag($this->domain,'help_content_extra2',$this->mod->Lang('help_title_content_extra2')),
			'<input type="text" id="extra2" name="'.$id.'extra2" size="80" maxlength="255" value="'. specialize($this->GetPropertyValue('extra2')).'" />'
			];

		case 'extra3':
			return [
			'for="extra3">'.$this->mod->Lang('extra3'),
			AdminUtils::get_help_tag($this->domain,'help_content_extra3',$this->mod->Lang('help_title_content_extra3')),
			'<input type="text" id="extra3" name="'.$id.'extra3" size="80" maxlength="255" value="'. specialize($this->GetPropertyValue('extra3')).'" />'
			];

		case 'owner':
			$userid = get_userid();
			$showadmin = SingleItem::ContentOperations()->CheckPageOwnership($userid, $this->Id());
			if( !$adding && (check_permission($userid,'Manage All Content') || $showadmin) ) {
				$users = SingleItem::UserOperations()->GetList(); // TODO get public names in preference to account-names
				$input = FormUtils::create_select([
					'type' => 'drop',
					'name' => 'owner_id',
					'getid' => $id,
					'htmlid' => 'owner',
					'multiple' => false,
					'options' => array_flip($users),
					'selectedvalue' => $this->Owner(),
				]);
				return [
				'for="owner">'.$this->mod->Lang('owner'),
				AdminUtils::get_help_tag($this->domain,'help_content_owner',$this->mod->Lang('help_title_content_owner')),
				$input
				];
			}
			break;

		case 'additionaleditors':
			// do owner/additional-editor stuff
			$userid = get_userid();
			$contentops = SingleItem::ContentOperations();
			if( $adding || check_permission($userid,'Manage All Content') ||
				$contentops->CheckPageOwnership($userid,$this->Id()) ) {
				$addteditors = $this->GetAdditionalEditors();
				$owner_id = $this->Owner();
				$input = '<input type="hidden" name="'.$id.'additional_editors" value="" /><select id="addteditors" name="'.$id.'additional_editors[]" multiple="multiple" size="5">';
				$topts = $contentops->ListAdditionalEditors();
				foreach( $topts as $k => $v ) {
					if( $k == $owner_id ) continue;
					$input .= FormUtils::create_option(['label'=>$v,'value'=>$k],$addteditors);
				}
				$input .= '</select>';
				return [
				'for="addteditors">'.$this->mod->Lang('additionaleditors'),
				AdminUtils::get_help_tag($this->domain,'help_content_addteditor',$this->mod->Lang('help_title_content_addteditor')),
				$input
				];
			}
			break;

		default:
			throw new RuntimeException('Attempt to display invalid property '.$propname);
		}
	}

	/**
	 * An alias for ShowElement()
	 * @deprecated since 2.0
	 * @param string $propname
	 * @param bool $adding
	 * @return mixed 2-member array: [0] = label, [1] = input element | null | false
	 */
	public function display_single_element($propname, $adding)
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method','ShowElement'));
		return $this->ShowElement($propname, $adding);
	}

	/**
	 * Return all recorded user id's and group id's in a format suitable
	 * for use in a select field.
	 * @deprecated since 2.0 instead use ContentOperations->ListAdditionalEditors();
	 *
	 * @return array each member like id => name
	 * Note: group id's are expressed as negative integers in the keys.
	 */
	public static function GetAdditionalEditorOptions() : array
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method','ContentOperations->ListAdditionalEditors()'));
		return SingleItem::ContentOperations()->ListAdditionalEditors();
	}

	/**
	 * Return the page tabindex value
	 *
	 * @return int
	 */
	public function TabIndex()
	{
		return $this->mTabIndex ?? 0;
	}

	/**
	 * Set the page tabindex value
	 *
	 * @param int $tabindex tab index
	 */
	public function SetTabIndex($tabindex)
	{
		$this->mTabIndex = (int)$tabindex;
	}

	/**
	 * Return a list of distinct sections that divide the various logical sections
	 * that this content type supports for editing.
	 * Used from a page that allows content editing.
	 *
	 * @abstract
	 * @return array Associative array of tab keys and labels.
	 */
	public function GetTabNames()
	{
		$props = $this->GetSortedEditableProperties();
		$arr = [];
		foreach( $props as &$one ) {
			if( !isset($one['tab']) || $one['tab'] === '' ) $one['tab'] = self::TAB_MAIN;
			$key = $one['tab'];
			if( endswith($key,'_tab__') ) {
				$lbl = $this->mod->Lang($key);
			}
			else {
				$lbl = $key;
			}
			$arr[$key] = $lbl;
		}
		unset($one);
		return $arr;
	}

	/**
	 * Get an optional message for each tab.
	 *
	 * @abstract
	 * @since 2.0
	 * @param string $key the tab key (as returned with GetTabNames)
	 * @return string html text to display at the top of the tab.
	 */
	public function GetTabMessage($key)
	{
		switch( $key ) {
		case self::TAB_PERMS:
			return $this->mod->Lang('msg_permstab');
		}
		return '';
	}

	/**
	 * Get this contents for a specific tab.
	 * @deprecated since 2.0 Instead process results from GetSortedEditableProperties()
	 *
	 * @param string $key tab key
	 * @param bool   $adding  Optional flag whether this is an add operation. Default false (i.e. edit).
	 * @return array Each member an array:
	 *  [0] = prompt field
	 *  [1] = input field for the prompt with its js if needed
	 * or just a scalar false upon some errors
	 */
	public function GetTabElements($key, $adding = false)
	{
		$props = $this->GetSortedEditableProperties();
		$ret = [];
		foreach( $props as &$one ) {
			if( !isset($one['tab']) || $one['tab'] === '' ) $one['tab'] = self::TAB_MAIN;
			if( $one['tab'] == $key ) {
				$ret[] = $this->ShowElement($one['name'],$adding);
			}
		}
		unset($one);
		return $ret;
	}

	/**
	 * Return the raw value for a content property.
	 * If no property name is specified 'content_en' is assumed
	 *
	 * @abstract
	 * @param string $propname An optional property name to display. If none specified, the system should assume content_en.
	 * @return string
	 */
	public function Show($propname = 'content_en')
	{
	}

	/**
	 * Content page objects only directly store enough information to build a basic navigation from content objects.
	 * This method will return all of the other parts of this content object.
	 *
	 * Note: this method does not directly load properties.
	 *
	 * @return array
	 */
	public function Properties()
	{
		return $this->_props;
	}

	/**
	 * Subclasses should override this to set their property types after calling back here.
	 * NOTE this method is a significant contributor to the duration of each frontend request.
	 * Benchmark reported at https://steemit.com/php/@crell/php-use-associative-arrays-basically-never
	 * recommends (in spite of the URL) against stdClass data-storage in this sort of context.
	 * And arrays have been benchmarked here, they're faster.
	 *
	 * @abstract
	 * @internal
	 * @param array undeclared since 2.0 optional array of properties to be
	 * excluded from the initial properties. If present, each member an array
	 * [0] = name, [1] = value to return if the property is sought
	 */
	public function SetProperties()
	{
		$defaults = [
			'title' => [1,self::TAB_MAIN,1],
			'alias' => [2,self::TAB_MAIN],
//			'type' => [2,self::TAB_MAIN],

			'styles' => [2,self::TAB_DISPLAY],
			'image' => [5,self::TAB_DISPLAY],
			'thumbnail' => [6,self::TAB_DISPLAY],

			// priority 3 is also used by some subclasses
			'active' => [3,self::TAB_OPTIONS],
			'secure' => [3,self::TAB_OPTIONS], //deprecated property since 2.0
			'cachable' => [4,self::TAB_OPTIONS],
			'extra1' => [12,self::TAB_OPTIONS],
			'extra2' => [13,self::TAB_OPTIONS],
			'extra3' => [14,self::TAB_OPTIONS],

			'parent' => [1,self::TAB_NAV,1],
			'showinmenu' => [2,self::TAB_NAV],
			'menutext' => [3,self::TAB_NAV,1],
			'titleattribute' => [4,self::TAB_NAV],
			'accesskey' => [5,self::TAB_NAV],
			'tabindex' => [6,self::TAB_NAV],
			'page_url' => [7,self::TAB_NAV],
			'target' => [8,self::TAB_NAV],

			'owner' => [1,self::TAB_PERMS],
			'additionaleditors' => [2,self::TAB_PERMS],
		];

		$except = func_get_args(); //prevent subclass API incompatibility
		if( $except ) {
			$except = $except[0];
			$tmp = array_column($except, 0);
			$nonames = array_flip($tmp);
			$defaults = array_diff_key($defaults, $nonames);
		}

		foreach( $defaults as $name => &$one ) {
			$this->_properties[] = [
				'tab' => $one[1],
				'priority' => $one[0],
				'name' => $name,
				'required' => !empty($one[2]),
				'basic' => false,
			];
		}
		unset($one);

		if( $except ) {
			$this->_prop_defaults = [];
			foreach( $nonames as $name => &$one ) {
				$this->_prop_defaults[$name] = $except[$one][1] ?? '';
			}
			unset($one);
		}
	}

	/**
	 * Get all the properties of this content object (whether or not the user is entitled to view them)
	 *
	 * @since 2.0
	 * @return array of assoc. arrays
	 */
	public function GetPropertiesArray()
	{
		return $this->_SortProperties($this->_properties);
	}

	/* *
	 * Get all of the properties of this content object (whether or not the user is entitled to view them)
	 *
	 * @since 2.0
	 * @deprecated since 2.0 Instead use ContentBase::GetPropertiesArray()
	 * @return array of stdClass objects
	 */
/*	public function GetProperties()
	{
		$ret = $this->_SortProperties($this->_properties);
		if( $ret ) {
			foreach( $ret as &$one ) {
				$one = (object)$one;
			}
		}
		return $ret;
	}
*/

	/**
	 * Return a list of all the properties that may be edited by the current user
	 * when editing this content page in a content editor form.
	 *
	 * Content-type classes may override this method, but should call this base method.
	 *
	 * @abstract
	 * @return array Array of assoc. arrays, each of those having members
	 *  'name' (string), 'tab' (string), 'priority' (int), maybe 'required' (bool), maybe 'basic' (bool)
	 *  Other(s) may be added by a subclass
	 */
	public function GetEditableProperties()
	{
		$all = $this->IsEditable(true,false);
		if( !$all ) {
			$basic_properties = ['title','parent'];
			$tmp_basic_properties = AppParams::get('basic_attributes');
			if( $tmp_basic_properties ) {
				$tmp = explode(',',$tmp_basic_properties);
				$tmp_basic_properties = array_walk($tmp,function(&$one) { return trim($one); });
				$basic_properties = array_merge($tmp_basic_properties,$basic_properties);
			}
		}

		$ret = [];
		foreach( $this->_properties as &$one ) {
			if( $all || !empty($one['basic']) || in_array($one['name'],$basic_properties) ) {
				$ret[] = $one;
			}
		}
		unset($one);
		return $ret;
	}

	/**
	 * Sort properties by their attributes - tab, priority, name
	 * @ignore
	 */
	private function _SortProperties(array $props) : array
	{
		if( count($props) > 1 ) {
			usort($props,function($a,$b)
			{
				$res = strcmp($a['tab'],$b['tab']);
				if( $res == 0 ) $res = $a['priority'] <=> $b['priority'];
				if( $res == 0 ) $res = strcmp($a['name'],$b['name']);
				return $res;
			});
		}

		return $props;
	}

	/**
	 * Return a sorted list of all the properties that may be edited by the
	 * current user when editing this content page in a content editor form.
	 *
	 * @return array
	 */
	public function GetSortedEditableProperties()
	{
		if( isset($this->_editable_properties) ) return $this->_editable_properties;

		$props = $this->_SortProperties($this->GetEditableProperties());
		$this->_editable_properties = $props;
		return $props;
	}

	/**
	 * Test whether this content page has the named property.
	 * Properties will be loaded from the database if necessary.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function HasProperty($name)
	{
		if( !$name ) return false;
		if( !is_array($this->_props) ) $this->_load_properties();
		if( !is_array($this->_props) ) return false;
		return isset($this->_props[$name]);
	}

	/**
	 * Get the value for the named property.
	 * Properties will be loaded from the database if necessary.
	 *
	 * @param string $name
	 * @return mixed String value, or null if the property does not exist.
	 */
	public function GetPropertyValue($name)
	{
		if( $this->HasProperty($name) ) return $this->_props[$name];
	}

	/**
	 * @ignore
	 */
	private function _load_properties() : bool
	{
		if( $this->mId <= 0 ) return false;

		$this->_props = [];
		$db = SingleItem::Db();
		$query = 'SELECT prop_name,content FROM '.CMS_DB_PREFIX.'content_props WHERE content_id = ?';
		$dbr = $db->getAssoc($query,[(int)$this->mId]);
		if( $dbr !== false ) {
			$this->_props = $dbr;
			return true;
		}
		return false;
	}

	/**
	 * @ignore
	 */
	private function _save_properties() : bool
	{
		if( $this->mId <= 0 ) return false;
		if( !$this->_props ) return false;

		$db = SingleItem::Db();
		$query = 'SELECT prop_name FROM '.CMS_DB_PREFIX.'content_props WHERE content_id = ?';
		$gotprops = $db->getCol($query,[$this->mId]);

		$longnow = $db->DbTimeStamp(time());
		$iquery = 'INSERT INTO '.CMS_DB_PREFIX."content_props
(content_id,type,prop_name,content,create_date)
VALUES (?,?,?,?,$longnow)";
		$uquery = 'UPDATE '.CMS_DB_PREFIX."content_props SET content = ?, modified_date = $longnow WHERE content_id = ? AND prop_name = ?";

		foreach( $this->_props as $key => $value ) {
			if( in_array($key,$gotprops) ) {
				// update
//				$dbr = NB unreliable return value use ->errorNo() if appropriate
				$db->execute($uquery,[$value,$this->mId,$key]);
			}
			else {
				// insert
				$dbr = $db->execute($iquery,[$this->mId,'string',$key,$value]);
				if( $dbr === false ) return false;
			}
		}
		return true;
	}

	/**
	 * Set the value of a the named property.
	 * This method will load properties for this content page if necessary.
	 *
	 * @param string $name The property name
	 * @param string $value The property value.
	 */
	public function SetPropertyValue($name, $value)
	{
		if( !is_array($this->_props) ) $this->_load_properties();
		$this->_props[$name] = $value;
	}

	/**
	 * Set the value of a the named property.
	 * This method will not load properties
	 *
	 * @param string $name The property name
	 * @param string $value The property value.
	 */
	public function SetPropertyValueNoLoad($name, $value)
	{
		if( !is_array($this->_props) ) $this->_props = [];
		$this->_props[$name] = $value;
	}

	/**
	 * Set the value (by member) of a base (not addon property) property of the
	 * content object for base properties that have been removed from the form.
	 *
	 * @ignore
	 */
	private function _handleRemovedBaseProperty(string $name, string $member) : bool
	{
		if( !$this->_properties ) return false;
		$fnd = false;
		foreach( $this->_properties as &$one ) {
			if( $one['name'] == $name ) {
				$fnd = true;
				break;
			}
		}
		unset($one);

		if( !$fnd ) {
			if( isset($this->_prop_defaults[$name]) ) {
				$this->$member = $this->_prop_defaults[$name];
				return true;
			}
		}
		return false;
	}

	/**
	 * Add a property definition.
	 * NOTE this method can be a significant contributor to the duration of each frontend request
	 * @see comment for BaseContent::SetProperties() re data format
	 *
	 * @since 1.11
	 * @param string $name Property name
	 * @param int $priority Sort order
	 * @param string $tabname Optional tab for the property (see tab constants) Default self::TAB_MAIN
	 * @param bool $required Optional flag whether the property is required Default false
	 * @param bool $basic Optional flag whether the property is basic (i.e. editable even by restricted editors) Default false
	 */
	public function AddProperty($name, $priority, $tabname = self::TAB_MAIN, $required = false, $basic = false)
	{
		if( !$tabname ) $tabname = self::TAB_MAIN;
		$this->_properties[] = [
			'tab' => $tabname,
			'priority' => $priority,
			'name' => $name,
			'required' => $required,
			'basic' => $basic,
		];
	}

	/* *
	 * Add a property that is directly associated with a field in this content table
	 * @alias for AddProperty
	 * @deprecated since 2.0 (at most?)
	 *
	 * @param string $name The property name
	 * @param int	$priority The priority
	 * @param bool   $is_required Whether this field is required for this content type
	 */
/*	protected function AddBaseProperty($name, $priority, $is_required = false)
	{
		$this->AddProperty($name,$priority,self::TAB_MAIN,$is_required);
	}
*/
	/* *
	 * Alias for AddProperty
	 * @deprecated  since 2.0 (at most?)
	 *
	 * @param string $name
	 * @param int	$priority
	 * @param bool   $is_required
	 * @return null
	 */
/*	protected function AddContentProperty($name, $priority, $is_required = false)
	{
		return $this->AddProperty($name,$priority,self::TAB_MAIN,$is_required);
	}
*/
	/**
	 * Remove a property from the known-properties list, and specify a default
	 * value to use if the property is sought.
	 *
	 * @param string $name The property name
	 * @param mixed $dflt Optional default value.
	 */
	public function RemoveProperty($name, $dflt = null)
	{
		if( !$this->_properties ) return;
		for( $i = 0, $n = count($this->_properties); $i < $n; ++$i ) {
			if( $this->_properties[$i] && $this->_properties[$i]['name'] == $name ) {
				unset($this->_properties[$i]);
				if( $i < $n - 1 ) {
					$this->_properties = array_values($this->_properties);
				}
				$this->_prop_defaults[$name] = $dflt;
				return;
			}
		}
	}

	/**
	 * Callback function for content types to use to preload content or other things if necessary.
	 * This is called right after this content is loaded from the database.
	 *
	 * @abstract
	 */
	public function Load()
	{
	}

	/**
	 * Update the database with this contents of this content object.
	 *
	 * This method will calculate a new item order for the object if necessary and then
	 * save this content record, the additional editors, and the properties.
	 * Additionally, if a page url is specified a static route will be created
	 *
	 * Because multiple content objects may be modified in one batch
	 * the calling function is responsible for ensuring that page hierarchies are
	 * updated.
	 *
	 * @see ContentOperations::SetAllHierarchyPositions()
	 * @todo this function should return something, or throw an exception.
	 */
	protected function Update()
	{
		$db = SingleItem::Db();

		// Figure out the item_order (if necessary)
		if( $this->mItemOrder < 1 ) {
			$query = 'SELECT '.$db->IfNull('MAX(item_order)','0').' AS new_order FROM '.CMS_DB_PREFIX.'content WHERE parent_id = ?';
			$dbr = (int)$db->getOne($query,[$this->mParentId]);

			if( $dbr < 1 ) {
				$this->mItemOrder = 1;
			}
			else {
				$this->mItemOrder = $dbr + 1;
			}
		}

		$this->mModifiedDate = $db->DbTimeStamp(time(),false);

		$query = 'UPDATE '.CMS_DB_PREFIX.'content SET
content_name = ?,
owner_id = ?,
type = ?,
template_id = ?,
parent_id = ?,
active = ?,
default_content = ?,
show_in_menu = ?,
cachable = ?,
secure = ?,
page_url = ?,
menu_text = ?,
content_alias = ?,
metadata = ?,
titleattribute = ?,
accesskey = ?,
styles = ?,
tabindex = ?,
modified_date = ?,
item_order = ?,
last_modified_by = ?
WHERE content_id = ?';

		$db->execute($query, [
			$this->mName,
			$this->mOwner,
			$this->Type(),
			$this->mTemplateId,
			$this->mParentId,
			($this->mActive		 ? 1 : 0),
			($this->mDefaultContent ? 1 : 0),
			($this->mShowInMenu	 ? 1 : 0),
			($this->mCachable	 ? 1 : 0),
			($this->mSecure		 ? 1 : 0),
			$this->mURL,
			$this->mMenuText,
			$this->mAlias,
			$this->mMetadata,
			$this->mTitleAttribute,
			$this->mAccessKey,
			$this->mStyles,
			$this->mTabIndex,
			$this->mModifiedDate,
			$this->mItemOrder,
			$this->mLastModifiedBy,
			(int) $this->mId
		]);

		if( isset($this->mAdditionalEditors) ) {
			$content_id = (int) $this->mId;
			$query = 'DELETE FROM '.CMS_DB_PREFIX.'additional_users WHERE content_id = ?';
			$dbr = $db->execute($query, [$content_id]);

			$query = 'INSERT INTO '.CMS_DB_PREFIX.'additional_users (user_id, content_id) VALUES (?,?)';
			foreach( $this->mAdditionalEditors as $oneeditor ) {
				$dbr = $db->execute($query, [$oneeditor, $content_id]);
			}
		}

		if( $this->_props ) {
			// :TODO: maybe some error checking
			$res = $this->_save_properties();
		}

		RouteOperations::del_static('','__CONTENT__',$this->mId);
		if( $this->mURL ) {
			$route = new Route($this->mURL,'__CONTENT__',null,true,$this->mId);
			RouteOperations::add_static($route);
		}
	}

	/**
	 * Initially save a content object with no id to the database.
	 *
	 * Like the Update method this method will determine a new item order
	 * save the record, save properties and additional editors, but will not
	 * update the hierarchy positions.
	 *
	 * @see ContentOperations::SetAllHierarchyPositions()
	 */
	protected function Insert()
	{
		# :TODO: This function should return something
		# :TODO: Careful about hierarchy here, it has no value !
		# :TODO: Figure out proper item_order
		$db = SingleItem::Db();

		$query = 'SELECT content_id FROM '.CMS_DB_PREFIX.'content WHERE default_content = 1';
		$dflt_pageid = (int)$db->getOne($query);
		if( $dflt_pageid < 1 ) $this->SetDefaultContent(true);

		// Figure out the item_order
		if( $this->mItemOrder < 1 ) {
			$query = 'SELECT MAX(item_order) AS new_order FROM '.CMS_DB_PREFIX.'content WHERE parent_id = ?';
			$dbr = (int)$db->getOne($query, [$this->mParentId]);

			if( $dbr < 1) {
				$this->mItemOrder = 1;
			}
			else {
				$this->mItemOrder = $dbr + 1;
			}
		}

		$newid = $db->genID(CMS_DB_PREFIX.'content_seq');
		$this->mId = $newid;

		$this->mModifiedDate = null;
		//explicit set create_date is redundant, on recent db servers at least (field default is CURRENT_TIMESTAMP)
		$this->mCreationDate = $db->DbTimeStamp(time(), false); //should be redundant with DT DEFAULT ...

		$query = 'INSERT INTO '.CMS_DB_PREFIX.'content (
content_id,
content_name,
content_alias,
type,
owner_id,
parent_id,
template_id,
item_order,
hierarchy,
id_hierarchy,
active,
default_content,
show_in_menu,
cachable,
secure,
page_url,
menu_text,
metadata,
titleattribute,
accesskey,
styles,
tabindex,
last_modified_by,
create_date) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
		$dbr = $db->execute($query, [
			$newid,
			$this->mName,
			$this->mAlias,
			$this->Type(),
			$this->mOwner,
			$this->mParentId,
			$this->mTemplateId,
			$this->mItemOrder,
			$this->mHierarchy,
			$this->mIdHierarchy,
			($this->mActive		 ? 1 : 0),
			($this->mDefaultContent ? 1 : 0),
			($this->mShowInMenu	 ? 1 : 0),
			($this->mCachable	 ? 1 : 0),
			($this->mSecure		 ? 1 : 0),
			$this->mURL,
			$this->mMenuText,
			$this->mMetadata,
			$this->mTitleAttribute,
			$this->mAccessKey,
			$this->mStyles,
			$this->mTabIndex,
			$this->mLastModifiedBy,
			$this->mCreationDate
		]);

		if( !$dbr ) {
			throw new Exception($db->sql.'<br />'.$db->errorMsg());
		}

		if( $this->_props ) {
			// :TODO: maybe some error checking
			debug_buffer('save from ' . __LINE__);
			$this->_save_properties();
		}
		if( isset($this->mAdditionalEditors) ) {
			$query = 'INSERT INTO '.CMS_DB_PREFIX.'additional_users (user_id, content_id) VALUES (?,?)';
			$content_id = $this->Id();
			foreach( $this->mAdditionalEditors as $oneeditor ) {
				$db->execute($query, [$oneeditor, $content_id]);
			}
		}

		if( $this->mURL ) {
			$route = new Route($this->mURL,'__CONTENT__',null,true,$this->mId);
			RouteOperations::add_static($route);
		}
	}

	/**
	 * Save or update this content.
	 *
	 * @todo This function should return something (or throw an exception)
	 */
	public function Save()
	{
		Events::SendEvent( 'Core', 'ContentEditPre', [ 'content' => &$this ] ); //TODO deprecate? module for originator?

		if( !is_array($this->_props) ) {
			debug_buffer('save is loading properties');
			$this->_load_properties();
		}

		if( -1 < $this->mId ) {
			$this->Update();
		}
		else {
			$this->Insert();
		}

		$contentops = SingleItem::ContentOperations();
		$contentops->SetContentModified();
		$contentops->SetAllHierarchyPositions();
		Events::SendEvent( 'Core', 'ContentEditPost', [ 'content' => &$this ] ); //TODO deprecate? module for originator?
		return true;
	}

	/**
	 * Delete the current content object from the database.
	 *
	 * @todo this function should return something, or throw an exception
	 */
	public function Delete()
	{
		Events::SendEvent( 'Core', 'ContentDeletePre', [ 'content' => &$this ] ); //TODO deprecate? module for originator?
		if( $this->mId > 0 ) {
			$db = SingleItem::Db();

			$query = 'DELETE FROM '.CMS_DB_PREFIX.'content WHERE content_id = ?';
			$dbr = $db->execute($query, [$this->mId]);

			// Fix the item_order if necessary
			$query = 'UPDATE '.CMS_DB_PREFIX.'content SET item_order = item_order - 1 WHERE parent_id = ? AND item_order > ?';
//			$dbr = unreliable for update
			$db->execute($query,[$this->ParentId(),$this->ItemOrder()]); //NB unreliable result after update

			// DELETE properties
			$query = 'DELETE FROM '.CMS_DB_PREFIX.'content_props WHERE content_id = ?';
			$dbr = $db->execute($query,[$this->mId]);
			$this->_props = null;

			// Delete additional editors.
			$query = 'DELETE FROM '.CMS_DB_PREFIX.'additional_users WHERE content_id = ?';
			$dbr = $db->execute($query,[$this->mId]);
			$this->mAdditionalEditors = null;

			// Delete route
			if( $this->mURL ) RouteOperations::del_static($this->mURL);
		}

		Events::SendEvent( 'Core', 'ContentDeletePost', [ 'content' => &$this ] ); //TODO deprecate? module for originator?
		$this->mId = -1;
		$this->mItemOrder = -1;

		return true;
	}

	/**
	 * Test whether this content object is valid.
	 * This function is used to check that no compulsory argument
	 * has been forgotten by the user
	 *
	 * We do not check the Id because there can be no Id (new content)
	 * That's up to Save to check this.
	 *
	 * @abstract
	 * @return array Error string(s) | empty if valid
	 */
	public function ValidateData()
	{
		$errors = [];

		if( $this->mParentId < -1 ) {
			$errors[] = $this->mod->Lang('invalidparent');
		}

		if( $this->mName === '' ) {
			if( $this->mMenuText ) {
				$this->mName = $this->mMenuText;
			}
			else {
				$errors[] = $this->mod->Lang('nofieldgiven', $this->mod->Lang('title'));
			}
		}

		if( $this->mMenuText === '' && $this->mShowInMenu ) {
			if( $this->mName ) {
				$this->mMenuText = $this->mName;
			}
			else {
				$errors[] = $this->mod->Lang('nofieldgiven', $this->mod->Lang('menutext'));
			}
		}

		if( !$this->HandlesAlias()) {
			if( $this->mAlias != $this->mOldAlias || ($this->mAlias === '' && $this->RequiresAlias()) ) {
				$error = SingleItem::ContentOperations()->CheckAliasError($this->mAlias, $this->mId);
				if( $error !== false ) {
					$errors[] = $error;
				}
			}
		}

		$auto_type = content_assistant::auto_create_url();
		if( $this->mURL === '' && AppParams::get('content_autocreate_urls') ) {
			// create a valid url.
			if( !$this->DefaultContent() ) {
				if( AppParams::get('content_autocreate_flaturls',0) ) {
					// the default url is the alias... but not synced to the alias.
					$this->mURL = $this->mAlias;
				}
				else {
					// if it doesn't explicitly say 'flat' we're creating a hierarchical url.
					$hm = SingleItem::App()->GetHierarchyManager();
					$node = $hm->find_by_tag('id',$this->ParentId());
					$stack = [$this->mAlias];
					$parent_url = '';
					$count = 0;
					while( $node ) {
						$tmp_content = $node->getContent();
						if( $tmp_content ) {
							$tmp = $tmp_content->URL();
							if( $tmp && $count == 0 ) {
								// try to build the url out of the parent url.
								$parent_url = $tmp;
								break;
							}
							array_unshift($stack,$tmp_content->Alias());
						}
						$node = $node->GetParent();
						$count++;
					}

					$this->mURL = implode('/',$stack);
					if( $parent_url ) {
						// woot, we got a parent url.
						$this->mURL = $parent_url.'/'.$this->mAlias;
					}
				}
			}
		}
		if( $this->mURL === '' && AppParams::get('content_mandatory_urls') &&
			!$this->mDefaultContent && $this->HasUsableLink() ) {
			// page url is empty and mandatory
			$errors[] = $this->mod->Lang('content_mandatory_urls');
		}
		elseif( $this->mURL ) {
			// page url is not empty, silently delete bad chars
			$this->mURL = (new Url())->sanitize($this->mURL);
			// and confirm (via munging) its suitability for pretty-url etc
			if( $this->mURL && !content_assistant::is_valid_url($this->mURL,$this->mId) ) {
				$errors[] = $this->mod->Lang('invalid_url2');
			}
		}
		return $errors;
	}

	/**************************************************************/
	/* Functions giving access to needed elements of this content */
	/**************************************************************/

	/**
	 * Return the page ID
	 */
	public function Id()
	{
		return $this->mId;
	}

	/**
	 * Set the numeric id of this content page
	 *
	 * @param int Integer id
	 * @access private
	 * @internal
	 */
	public function SetId($id)
	{
		$this->mId = $id;
	}

	/**
	 * Return the page name
	 *
	 * @return string
	 */
	public function Name()
	{
		return $this->mName;
	}

	/**
	 * Set the the page name
	 *
	 * @param string $name The name.
	 */
	public function SetName($name)
	{
		$this->mName = $name;
	}

	/**
	 * Return a friendly name for this content type
	 *
	 * Normally this content type returns a string representing the name of this content type translated into the users current language
	 *
	 * @abstract
	 * @return string
	 */
	abstract public function FriendlyName();

	/**
	 * Return the page alias
	 *
	 * @return string
	 */
	public function Alias()
	{
		return $this->mAlias;
	}

	/**
	 * Set the page alias for this content page.
	 * If an empty alias is supplied, and depending upon the doAutoAliasIfEnabled flag,
	 * and config entries a suitable alias may be calculated from other data in the page object.
	 * This method relies on the menutext and the name of this content page already being set.
	 *
	 * @param string $alias The alias
	 * @param bool $doAutoAliasIfEnabled Whether an alias should be calculated or not.
	 */
	public function SetAlias($alias = '', $doAutoAliasIfEnabled = true)
	{
		$contentops = SingleItem::ContentOperations();
		$config = SingleItem::Config();
		if( $alias === '' && $doAutoAliasIfEnabled && $config['auto_alias_content'] ) {
			$alias = trim($this->mMenuText);
			if( $alias === '' ) $alias = trim($this->mName);

			// auto generate an alias
			$tolower = true;
			$alias = munge_string_to_url($alias, $tolower);
			$res = $contentops->CheckAliasValid($alias);
			if( !$res ) {
				$alias = 'p'.$alias;
				$res = $contentops->CheckAliasValid($alias);
				if( !$res ) throw new ContentException($this->mod->Lang('invalidalias2'));
			}
		}

		if( $alias ) {
			// Make sure auto-generated new alias is not already in use on a different page, if it does, add "-2" to the alias

			// make sure we start with a valid alias.
			$res = $contentops->CheckAliasValid($alias);
			if( !$res ) throw new ContentException($this->mod->Lang('invalidalias2'));

			// now auto-increment the alias.
			$prefix = $alias;
			$num = 1;
			if( preg_match('/(.*)-([0-9]*)$/',$alias,$matches) ) {
				$prefix = $matches[1];
				$num = (int) $matches[2];
			}
			$test = $alias;
			do {
				if( !$contentops->CheckAliasUsed($test,$this->Id()) ) {
					$alias = $test;
					break;
				}
				$num++;
				$test = $prefix.'-'.$num;
			} while( $num < 100 );
			if( $num >= 100 ) throw new ContentException($this->mod->Lang('aliasalreadyused'));
		}

		$this->mAlias = $alias;
		$cache = SingleItem::LoadedData();
// TODO or refresh() & save, ready for next stage ?
		$cache->delete('content_quicklist');
		$cache->delete('content_tree');
		$cache->delete('content_flatlist');
	}

	/**
	 * Return whether this content object handles the alias
	 *
	 * @abstract
	 * @return bool Default false
	 */
	public function HandlesAlias()
	{
		return false;
	}

	/**
	 * Return whether this content type requires an alias.
	 * Some content types that are not directly navigable do not require page aliases.
	 *
	 * @abstract
	 * @return bool Default true
	 */
	public function RequiresAlias()
	{
		return true;
	}

	/**
	 * Return the content-type
	 *
	 * @return string
	 */
	public function Type()
	{
		$c = get_class($this);
		$p = strrpos($c, '\\');
		return ($p !== false) ? strtolower(substr($c, $p+1)) : strtolower($c);
	}

	/**
	 * Return the owner's user id
	 *
	 * @return int
	 */
	public function Owner()
	{
		return $this->mOwner;
	}

	/**
	 * Set the page owner.
	 * No validation is performed.
	 *
	 * @param int $owner Owner's user id
	 */
	public function SetOwner($owner)
	{
		$owner = (int)$owner;
		if( $owner <= 0 ) return;
		$this->mOwner = $owner;
	}

	/**
	 * Return the page metadata
	 *
	 * @return string
	 */
	public function Metadata()
	{
		return $this->mMetadata;
	}

	/**
	 * Set the page metadata
	 *
	 * @param string $metadata The metadata
	 */
	public function SetMetadata($metadata)
	{
		$this->mMetadata = $metadata;
	}

	/**
	 * Return the page title attribute
	 *
	 * @return string
	 */
	public function TitleAttribute()
	{
		return $this->mTitleAttribute;
	}

	/**
	 * Set the title attribute of the page
	 *
	 * The title attribute can be used in navigations to set the "title=" attribute of a link
	 * some menu templates may ignore this.
	 *
	 * @param string $titleattribute The title attribute
	 */
	public function SetTitleAttribute($titleattribute)
	{
		$this->mTitleAttribute = $titleattribute;
	}

	/**
	 * Return the creation date/time of this content object.
	 *
	 * @return int UNIX UTC timestamp. Default 1.
	 */
	public function GetCreationDate()
	{
		$value = $this->mCreationDate ?? '';
		return ($value) ? cms_to_stamp($value) : 1;
	}

	/**
	 * Return the date/time of the last modification of this content object.
	 *
	 * @return int UNIX UTC timestamp. Default 1.
	 */
	public function GetModifiedDate()
	{
		$value = $this->mModifiedDate ?? '';
		return ($value) ? cms_to_stamp($value) : $this->GetCreationDate();
	}

	/**
	 * Get the access key (for accessibility) for this page.
	 *
	 * @see http://www.w3schools.com/tags/att_global_accesskey.asp
	 * @return string
	 */
	public function AccessKey()
	{
		return $this->mAccessKey;
	}

	/**
	 * Set the access key (for accessibility) for this page
	 *
	 * @see http://www.w3schools.com/tags/att_global_accesskey.asp
	 * @param string $accesskey
	 */
	public function SetAccessKey($accesskey)
	{
		$this->mAccessKey = $accesskey;
	}

	/**
	 * Return the id of this page's parent.
	 * The parent id may be -2 to indicate a new page.
	 * A parent id value of -1 indicates that the page has no parent.
	 * oterwise a positive integer is returned.
	 *
	 * @return int
	 */
	public function ParentId()
	{
		return $this->mParentId;
	}

	/**
	 * Set the parent of this page
	 *
	 * @param int $parentid The numeric page parent id. Use -1 for no parent.
	 */
	public function SetParentId($parentid)
	{
		$parentid = (int) $parentid;
		if( $parentid < 1 ) $parentid = -1;
		$this->mParentId = $parentid;
	}

	/**
	 * Return the id of the template associated with this content page.
	 *
	 * @return int.
	 */
	public function TemplateId()
	{
		return $this->mTemplateId;
	}

	/**
	 * Set the id of the template associated with this content page.
	 *
	 * @param int $templateid
	 */
	public function SetTemplateId($templateid)
	{
//		$templateid = (int)$templateid;
		if( $templateid > 0 ) $this->mTemplateId = $templateid;
	}

	//TODO support typed templates for theming (non-core prop)
	// ditto for typed stylesheets
/*	public function TemplateName()
	{
		return $this->mTemplateType;
	}

	public function SetTemplateName($templatename)
	{
		$this->mTemplateType = trim($templatename);
	}
*/
	/**
	 * Return whether this page uses a template.
	 * Some content types like sectionheader and separator do not.
	 *
	 * @return bool Default false
	 */
	public function HasTemplate()
	{
		return false;
	}

	/**
	 * Return a smarty resource string for the template assigned to this page.
	 *
	 * @since 2.0
	 * @abstract
	 * @return string
	 */
	public function TemplateResource()
	{
		throw new Exception('this method must be overridden for displayable content pages');
	}

	/**
	 * Return the itemOrder
	 * That is used to specify the order of this page among its peers
	 *
	 * @return int
	 */
	public function ItemOrder()
	{
		return $this->mItemOrder;
	}

	/**
	 * Set the page itemOrder
	 * That is used to specify the order of this page within the parent.
	 * A value of -1 indicates that a new item order will be calculated on save.
	 * Otherwise a positive integer is expected.
	 *
	 * @internal
	 * @param int $itemorder
	 */
	public function SetItemOrder($itemorder)
	{
		$itemorder = (int)$itemorder;
		if( $itemorder > 0 || $itemorder == -1 ) $this->mItemOrder = $itemorder;
	}

	/**
	 * Move this content up, or down with respect to its peers.
	 *
	 * Note: This method modifies two content objects.
	 *
	 * @since 2.0
	 * @param int $direction direction. negative value indicates up, positive value indicates down.
	 */
	public function ChangeItemOrder($direction)
	{
		$db = SingleItem::Db();
		$longnow = $db->DbTimeStamp(time());
		$parentid = $this->ParentId();
		$order = $this->ItemOrder();
		if( $direction < 0 && $this->ItemOrder() > 1 ) {
			// up
			$query = 'UPDATE '.CMS_DB_PREFIX.'content SET item_order = (item_order + 1), modified_date = '.$longnow.'
 WHERE item_order = ? AND parent_id = ?';
			$db->execute($query,[$order-1,$parentid]);
			$query = 'UPDATE '.CMS_DB_PREFIX.'content SET item_order = (item_order - 1), modified_date = '.$longnow.'
 WHERE content_id = ?';
			$db->execute($query,[$this->Id()]);
		}
		elseif( $direction > 0 ) {
			// down.
			$query = 'UPDATE '.CMS_DB_PREFIX.'content SET item_order = (item_order - 1), modified_date = '.$longnow.'
 WHERE item_order = ? AND parent_id = ?';
			$db->execute($query,[$order+1,$parentid]);
			$query = 'UPDATE '.CMS_DB_PREFIX.'content SET item_order = (item_order + 1), modified_date = '.$longnow.'
 WHERE content_id = ?';
			$db->execute($query,[$this->Id()]);
		}
		$cache = SingleItem::LoadedData();
// TODO or refresh() & save, ready for next stage ?
		$cache->delete('content_quicklist');
		$cache->delete('content_tree');
		$cache->delete('content_flatlist');
	}

	/**
	 * Return the hierarchy of the current page.
	 * A string like N.N.N indicating the path to this page and its order
	 * This value uses the item order when calculating the output e.g. 3.3.3
	 * to indicate the third grandchild of the third child of the third root page.
	 *
	 * @return string
	 */
	public function Hierarchy()
	{
		return SingleItem::ContentOperations()->CreateFriendlyHierarchyPosition($this->mHierarchy); //should match this->mIdHierarchy
	}

	/**
	 * Set the (unfriendly i.e. 0-padded) hierarchy
	 *
	 * @internal
	 * @param string $hierarchy
	 */
	public function SetHierarchy($hierarchy)
	{
		$this->mHierarchy = $hierarchy;
	}

	/**
	 * Return the id (aka friendly, not-0-padded) hierarchy.
	 * A string like N.N.N indicating the path to the page and its order
	 * This property uses the id's of pages when calculating the output i.e: 21.5.17
	 * to indicate that page id 17 is the child of page with id 5 which is in turn the
	 * child of the page with id 21
	 *
	 * @return string
	 */
	public function IdHierarchy()
	{
		return $this->mIdHierarchy;
	}

	/**
	 * Return the hierarchy path.
	 * Similar to the Hierarchy and IdHierarchy this string uses page aliases
	 * and outputs a string like root_alias/parent_alias/page_alias
	 *
	 * @return string
	 */
	public function HierarchyPath()
	{
		return $this->mHierarchyPath;
	}

	/**
	 * Return the page-active state
	 *
	 * @return bool
	 */
	public function Active()
	{
		return $this->mActive;
	}

	/**
	 * Set this page as active
	 *
	 * @param bool $active
	 */
	public function SetActive($active)
	{
		$this->mActive = (bool)$active;
	}

	/**
	 * Return whether this content page should (by default) be shown in navigation menus.
	 *
	 * @abstract
	 * @return bool
	 */
	public function ShowInMenu()
	{
		return $this->mShowInMenu;
	}

	/**
	 * Set whether this page should be (by default) shown in menus
	 *
	 * @param bool $showinmenu
	 */
	public function SetShowInMenu($showinmenu)
	{
		$this->mShowInMenu = (bool)$showinmenu;
	}

	/**
	 * Return whether the page is the default.
	 * The default page is the one that is displayed when no alias or pageid is specified in the route
	 * Only one content page can be the default.
	 *
	 * @return bool
	 */
	public function DefaultContent()
	{
		if( $this->IsDefaultPossible() ) return $this->mDefaultContent;
		return false;
	}

	/**
	 * Set whether this page should be considered the default.
	 * Note: does not modify the flags for any other content page.
	 *
	 * @param bool $defaultcontent
	 */
	public function SetDefaultContent($defaultcontent)
	{
		if( $this->IsDefaultPossible() ) {
			$this->mDefaultContent = (bool)$defaultcontent;
		}
	}

	/**
	 * Return whether this content type can be the default page for a CMSMS website.
	 *
	 * this content editor module may adjust its user interface to not allow
	 * setting pages that return false for this method as the default page.
	 *
	 * @abstract
	 * @return bool Default is false
	 */
	public function IsDefaultPossible()
	{
		return false;
	}

	/**
	 * Return whether this page is cachable.
	 * Cachable pages (when enabled in global settings) are cached by the browser
	 * (also server side caching of HTML output may be enabled)
	 *
	 * @return bool
	 */
	public function Cachable()
	{
		return $this->mCachable;
	}

	/**
	 * Set whether this page is cachable
	 *
	 * @param bool $cachable
	 */
	public function SetCachable($cachable)
	{
		$this->mCachable = (bool)$cachable;
	}

	/**
	 * Return whether this page should be accessed via a secure protocol.
	 * The secure flag affects whether the ssl protocol and appropriate config entries are used when generating urls to this page.
	 * @deprecated since 2.0
	 *
	 * @return bool
	 */
	public function Secure()
	{
		return $this->mSecure;
	}

	/**
	 * Set whether this page should be accessed via a secure protocol.
	 * The secure flag affects whether the ssl protocol and appropriate config entries are used when generating urls to this page.
	 * @deprecated since 2.0
	 *
	 * @param bool $secure
	 */
	public function SetSecure($secure)
	{
		$this->mSecure = (bool)$secure;
	}

	/**
	 * Return the page URL (if any) associated with this content page.
	 * The page url is not the complete URL to this content page, but merely the 'stub' or 'slug' appended after the root url when accessing the site
	 * If the page is specified as the default page then the "page url" will be ignored.
	 * Some content types do not support page urls.
	 *
	 * @return string
	 */
	public function URL()
	{
		return $this->mURL;
	}

	/**
	 * Set the page URL associated with this content page.
	 * Verbatim, no immediate validation.
	 * The URL should be relative to the root URL i.e: /some/path/to/the/page
	 * Note: some content types do not support page URLs.
	 *
	 * @param string $url May be empty.
	 */
	public function SetURL($url)
	{
		$this->mURL = $url;
	}

	/**
	 * Return the internally-generated URL for this content.
	 *
	 * @param bool $rewrite optional flag, default true. If true, and mod_rewrite is enabled, build an URL suitable for mod_rewrite.
	 * @return string
	 */
	public function GetURL($rewrite = true)
	{
		$config = SingleItem::Config();
		$url = '';
		$alias = ($this->mAlias?$this->mAlias:$this->mId);

		$base_url = CMS_ROOT_URL;

		/* use root_url for default content */
		if($this->DefaultContent()) {
			$url = $base_url . '/';
			return $url;
		}

		if( $rewrite ) {
			$url_rewriting = $config['url_rewriting'];
			$page_extension = $config['page_extension'];
			if( $url_rewriting == 'mod_rewrite' ) {
				$str = $this->HierarchyPath();
				if( $this->mURL ) $str = $this->mURL;	// we have a url path
				$url = $base_url . '/' . $str . $page_extension;
				return $url;
			}
			elseif( isset($_SERVER['PHP_SELF']) && $url_rewriting == 'internal' ) {
				$str = $this->HierarchyPath();
				if( $this->mURL ) $str = $this->mURL; // we have a url path
				$url = $base_url . '/index.php/' . $str . $page_extension;
				return $url;
			}
		}

		$url = $base_url . '/index.php?' . $config['query_var'] . '=' . $alias;
		return $url;
	}

	/**
	 * Return the integer id of the admin user that last modified this content page.
	 *
	 * @return int
	 */
	public function LastModifiedBy()
	{
		return $this->mLastModifiedBy;
	}

	/**
	 * Set the last modified date for this item
	 *
	 * @param int $lastmodifiedby
	 */
	public function SetLastModifiedBy($lastmodifiedby)
	{
		$lastmodifiedby = (int)$lastmodifiedby;
		if( $lastmodifiedby > 0 ) $this->mLastModifiedBy = $lastmodifiedby;
	}

	/**
	 * Return whether preview should be available for this page
	 *
	 * @abstract
	 * @return bool Default false
	 */
	public function HasPreview()
	{
		return false;
	}

	/**
	 * Return whether this content type is viewable (i.e: can be rendered).
	 * Some content types (like redirection links) are not viewable.
	 *
	 * @abstract
	 * @return bool Default true
	 */
	public function IsViewable()
	{
		return true;
	}

	/**
	 * Return whether the current user is permitted to edit this content page
	 *
	 * @param $main optional flag whether to check for global page-edit authority. Default true
	 * @param $extra optional flag whether to check for membership of additional-editors. Default true
	 * @return bool
	 */
	public function IsEditable($main = true, $extra = true)
	{
		$userops = SingleItem::UserOperations();
		$userid = get_userid();
		if( $main ) {
			if( $userops->CheckPermission($userid,'Manage All Content')
			 || $userops->CheckPermission($userid,'Modify Any Page')
			 || $userops->CheckPermission($userid,'Add Pages') ) {
				return true;
			}
		}
		if( $extra ) {
			$eds = $this->GetAdditionalEditors();
			if( $eds ) {
				if( in_array($userid, $eds) ) {
					return true;
				}
				else {
					foreach( $eds as $one ) {
						if( $one < 0 ) {
							if( $userops->UserInGroup($userid, -(int)$one) ) {
								return true;
							}
						}
					}
				}
			}
		}
		return false;
	}

	/**
	 * Return whether the current user is permitted to view this content page.
	 *
	 * @abstract
	 * @return bool Default true
	 */
	public function IsPermitted()
	{
		return true;
	}

	/**
	 * An abstract method that indicates that this content type is navigable and generates a useful URL.
	 *
	 * @abstract
	 * @return bool Default true
	 */
	public function HasUsableLink()
	{
		return true;
	}

	/**
	 * An abstract method indicating whether this content type is copyable.
	 *
	 * @abstract
	 * @return bool Default false
	 */
	public function IsCopyable()
	{
		return false;
	}

	/**
	 * An abstract method to indicate whether this content type generates a system page.
	 * System pages are used to handle things like 404 errors etc.
	 *
	 * @abstract
	 * @return bool Default false
	 */
	public function IsSystemPage()
	{
		return false;
	}

	/**
	 * Return whether this content type is searchable.
	 *
	 * Searchable pages can be indexed by the search module.
	 *
	 * This function by default uses a combination of other methods to determine
	 * whether the page is searchable.
	 *
	 * @return bool
	 */
	public function IsSearchable()
	{
		if( !$this->isPermitted() || !$this->IsViewable() || !$this->HasTemplate() || $this->IsSystemPage() ) return false;
		return $this->HasSearchableContent();
	}

	/**
	 * Return whether this content type may have content that can be used by a search module.
	 *
	 * Content types should override this method if they are special purpose
	 * content types which cannot support searchable content in any way.
	 * Example content types are ErrorPage, Section Header and Separator.
	 *
	 * @since 2.0
	 * @abstract
	 * @return bool Default true
	 */
	public function HasSearchableContent()
	{
		return true;
	}

	/**
	 * Return the menu text for this content page.
	 * The MenuText is by default used as the text portion of a navigation link.
	 *
	 * @return string
	 */
	public function MenuText()
	{
		return $this->mMenuText;
	}

	/**
	 * Set the menu text for this content page
	 *
	 * @param string $menutext
	 */
	public function SetMenuText($menutext)
	{
		$this->mMenuText = $menutext;
	}

	/**
	 * Return the styles-sequence for this content page
	 * @since 2.0
	 *
	 * @return string, comma-separated stylesheet, stylesheetgroup id(s)
	 */
	public function Styles()
	{
		return $this->mStyles;
	}

	/**
	 * Set the styles-sequence for this content page
	 * @since 2.0
	 *
	 * @param string $stylestext comma-separated stylesheet, stylesheetgroup id(s)
	 */
	public function SetStyles($stylestext)
	{
		$this->mStyles = $stylestext;
	}

	/**
	 * Return the number of immediate child content pages of this content page.
	 *
	 * @return int
	 */
	public function ChildCount()
	{
		$hm = SingleItem::App()->GetHierarchyManager();
		$node = $hm->find_by_tag('id',$this->mId);
		if( $node ) return $node->count_children();
	}

	/**
	 * Return whether the current page has children.
	 *
	 * @param bool $activeonly Optional flag whether to test only for active children. Default false.
	 * @return bool
	 */
	public function HasChildren($activeonly = false)
	{
		if( $this->mId <= 0 ) return false;
		$hm = SingleItem::App()->GetHierarchyManager();
		$node = $hm->quickfind_node_by_id($this->mId);
		if( !$node || !$node->has_children() ) return false;

		if( !$activeonly ) return true;
		$children = $node->get_children();
		if( $children ) {
			for( $i = 0, $n = count($children); $i < $n; $i++ ) {
				$content = $children[$i]->getContent();
				if( $content->Active() ) return true;
			}
		}

		return false;
	}

	/**
	 * An abstract method that extended content types can use to indicate whether or not they want children.
	 * Some content types, such as a separator do not want to have any children.
	 *
	 * @since 0.11
	 * @abstract
	 * @return bool Default true
	 */
	public function WantsChildren()
	{
		return true;
	}

	/**
	 * Return a list of additional editors.
	 * Note: in the returned array, group id's are specified as negative integers.
	 *
	 * @return array user id's and group id's entitled to edit this content, or empty
	 */
	public function GetAdditionalEditors()
	{
		if( !isset($this->mAdditionalEditors) ) {
			$db = SingleItem::Db();

			$query = 'SELECT user_id FROM '.CMS_DB_PREFIX.'additional_users WHERE content_id = ?';
			$dbr = $db->getCol($query,[$this->mId]);
			if( $dbr ) {
				$this->mAdditionalEditors = $dbr;
			}
			else {
				$this->mAdditionalEditors = [];
			}
		}
		return $this->mAdditionalEditors;
	}

	/**
	 * Set the list of additional editors.
	 * Note: in the provided array, group id's are specified as negative integers.
	 *
	 * @param mixed $editorarray Array of user id's and group id's, or null
	 */
	public function SetAdditionalEditors($editorarray = null)
	{
		$this->mAdditionalEditors = $editorarray;
	}

	// ======= SERIALIZABLE INTERFACE METHODS =======

	public function __serialize()
	{
		return $this->serialize();
	}

	public function __unserialize($serialized)
	{
		$this->unserialize($serialized);
	}

	public function serialize()
	{
		$mod = $this->mod;
		$this->mod = null;
		$str = $this->__toString();
		$this->mod = $mod;
		$mod = null; //force-garbage
		//TODO can cachers cope with embedded null's? NB 'internal' cryption is slow!
		return Crypto::encrypt_string($str,__CLASS__,'best');
// 		return $str;
	}

	public function unserialize($serialized)
	{
		$serialized = Crypto::decrypt_string($serialized,__CLASS__,'best');
		if( !$serialized ) {
			return;
		}
		$props = json_decode($serialized, true);
		if( $props !== null ) {
			foreach( $props as $key => $val ) {
				$this->$key = $val;
			}
			$this->mod = AppUtils::get_module('ContentManager');
			return;
		}
		throw new Exception('Invalid object data in '.__METHOD__);
	}
} // class
