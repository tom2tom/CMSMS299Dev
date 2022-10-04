<?php
/*
The main Content class
Copyright (C) 2004-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

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
namespace ContentManager\contenttypes;

use CMSMS\AdminUtils;
use CMSMS\AppParams;
use CMSMS\ContentException;
use CMSMS\CapabilityType;
use CMSMS\FileType;
use CMSMS\FormUtils;
use CMSMS\internal\page_template_parser;
use CMSMS\Lone;
use CMSMS\TemplateOperations;
use CMSMS\TemplateType;
use CMSMS\Utils;
use ContentManager\ContentBase;
use SmartyException;
use Throwable;
use function check_permission;
use function cms_join_path;
use function cms_to_bool;
use function CMSMS\log_error;
use function CMSMS\specialize;
use function create_file_dropdown;
use function get_userid;
use function startswith;

/**
 * Implements the Content (page) content type.
 *
 * This is the primary content type. This represents an HTML page generated by smarty.
 *
 * @package CMS
 * @subpackage content_types
 * @license GPL
 */
class Content extends ContentBase
{
	/**
	 * @ignore
	 */
	protected $_contentBlocks = null;

	public function FriendlyName() : string
	{
		return $this->mod->Lang('contenttype_content');
	}

	public function HasPreview() : bool
	{
		return $this->mId > 0;
	}

	public function HasTemplate() : bool
	{
		return true;
	}

	public function IsCopyable() : bool
	{
		return true;
	}

	public function IsDefaultPossible() : bool
	{
		return true;
	}

	public function IsSearchable() : bool
	{
		if (!parent::IsSearchable()) {
			return false;
		}
		return $this->GetPropertyValue('searchable') != false;
	}

	public function WantsChildren() : bool
	{
		$tmp = $this->GetPropertyValue('wantschildren');
		// an empty/null value is considered true
		return $tmp !== '0';
	}

	/**
	 * Set up base property attributes for this content type
	 *
	 * This property type adds properties: NOPEdesign_id,/NOPE template, defaultcontent, wantschildren, searchable, disable_wysiwyg, pagemetadata, pagedata
	 */
	public function SetProperties()
	{
		parent::SetProperties();
		$this->AddProperty('template', 1, parent::TAB_DISPLAY);
//		$this->AddProperty('design_id',0,parent::TAB_OPTIONS);
//		$this->AddProperty('template_rsrc',0,parent::TAB_OPTIONS);
		$this->AddProperty('defaultcontent', 3, parent::TAB_OPTIONS); //co-locate with 'main' checkboxes
		$this->AddProperty('searchable', 4, parent::TAB_OPTIONS);
		$this->AddProperty('wantschildren', 5, parent::TAB_OPTIONS);
		$this->AddProperty('disable_wysiwyg', 6, parent::TAB_OPTIONS);

		$this->AddProperty('pagemetadata', 1, parent::TAB_LONGOPTS); // aka TAB_LOGIC
		$this->AddProperty('pagedata', 2, parent::TAB_LONGOPTS);
	}

	/**
	 * Set content attribute values (from parameters received from admin add/edit form)
	 *
	 * @param array $params Hash of parameters to load into content attributes
	 * @param bool  $editing Whether this is an edit operation. Default false i.e. adding.
	 */
	public function FillParams($params, $editing = false)
	{
		if (!empty($params)) {
			$parameters = ['pagedata', 'searchable', 'disable_wysiwyg', /*'design_id',*/'wantschildren'];

			// pick up the template id before we do parameters
			if (isset($params['template_id'])) {
				if ($this->mTemplateId != $params['template_id']) {
					$this->_contentBlocks = null;
				}
				$this->mTemplateId = (int) $params['template_id'];
			}

			if ($this->IsDefaultPossible() && isset($params['defaultcontent'])) {
				$this->mDefaultContent = (int) $params['defaultcontent'];
			}

			// add content blocks
			$blocks = $this->get_content_blocks();
			if ($blocks) {
				foreach ($blocks as $blockName => $blockInfo) {
					$name = $blockInfo['id'];
					$parameters[] = $name;
					if (isset($blockInfo['type']) && $blockInfo['type'] == 'module') {
						$mod = Utils::get_module($blockInfo['module']);
						if (!is_object($mod)) {
							continue;
						}
						if (!$mod->HasCapability(CapabilityType::CONTENT_BLOCKS)) {
							continue;
						}
						// TODO if falsy value	$current = $params[$name];
						$tmp = $mod->GetContentBlockFieldValue($blockName, $blockInfo['params'], $params, $this);
						if ($tmp/* != null*/) { // TODO allow (some?) falsy value
							$params[$name] = $tmp;
						}
					}
				}
			}

			// do the content property parameters
			foreach ($parameters as $oneparam) {
				if (!isset($params[$oneparam])) {
					continue;
				}
				$val = $params[$oneparam];
				switch ($oneparam) {
				case 'pagedata':
					// nothing
					break;
				default:
					if ($blocks && isset($blocks[$oneparam])) {
						// it's a content block.
						$val = $val;
					} else {
						$val = (int) $val;
					}
					break;
				}
				$this->SetPropertyValue($oneparam, $val);
			}

			// metadata
			if (isset($params['metadata'])) {
				$this->mMetadata = $params['metadata'];
			}
		}
		parent::FillParams($params, $editing);
	}

	/**
	 * Return the value of the named page property.
	 * The name may have been quoted by upstream.
	 *
	 * @param string $propname which attribute to return (content_en is assumed)
	 * @return string the specified content
	 */
	public function Show($propname = 'content_en')
	{
		$propname = trim($propname, " \r\n\t'\"");
		if (!$propname) {
			$propname = 'content_en';
		}
		$propname = str_replace(' ', '_', $propname);
		return $this->GetPropertyValue($propname);
	}

	/**
	 * Return a list of all of the properties that may be edited by the current user when editing this content item
	 * in a content editor form.
	 *
	 * This method calls the same method in the base class, then parses the content blocks in the templates and adds
	 * the appropriate information for all detected content blocks.
	 *
	 * @see ContentBase::GetEditableProperties()
	 * @return array Array of assoc arrays, each having members
	 *  'name' (string), 'tab' (string), 'priority' (int), maybe:'required' (bool), maybe:'basic' (bool), maybe:'extra' (array)
	 */
	public function GetEditableProperties() : array
	{
		$props = parent::GetEditableProperties();

		if ($this->IsEditable(true, true)) {
			// add in content blocks
			$blocks = $this->get_content_blocks();
			if ($blocks) {
				$priority = 100; // aka page_template_parser::$_priority default value
				foreach ($blocks as &$block) {
					$prop = ['name' => $block['name']];
					if (isset($block['tab']) && $block['tab'] !== '') {
						$prop['tab'] = $block['tab'];
					} else {
						$prop['tab'] = parent::TAB_DISPLAY;
					}
					if (isset($block['priority'])) {
						$prop['priority'] = $block['priority'];
					} else {
						$prop['priority'] = $priority++;
					}
					$prop['extra'] = $block;
					$props[] = $prop;
				}
				unset($block);
			}
		}

		return $props;
	}

	/**
	 * Validate the data in the content add/edit form
	 *
	 * This method also calls the parent method to validate the standard properties
	 *
	 * @return array validation error string(s) | empty to indicate no error
	 */
	public function ValidateData()
	{
		$errors = parent::ValidateData();
		if ($errors === false) {
			$errors = [];
		}

		if ($this->mTemplateId <= 0) {
			$errors[] = $this->mod->Lang('nofieldgiven', $this->mod->Lang('template'));
		}

		$blocks = $this->get_content_blocks();
		if (!$blocks) {
			$errors[] = $this->mod->Lang('error_parsing_content_blocks');
		}

		$have_content_en = false;
		if ($blocks) {
			foreach ($blocks as $blockName => $blockInfo) {
				if ($blockInfo['id'] == 'content_en') {
					$have_content_en = true;
				}
				if (isset($blockInfo['required']) && $blockInfo['required'] && ($val = $this->GetPropertyValue($blockName)) == '') {
					$errors[] = $this->mod->Lang('emptyblock', $blockName);
				}
				if (isset($blockInfo['type']) && $blockInfo['type'] == 'module') {
					$mod = Utils::get_module($blockInfo['module']);
					if (!is_object($mod)) {
						continue;
					}
					if (!$mod->HasCapability(CapabilityType::CONTENT_BLOCKS)) {
						continue;
					}
					$value = $this->GetPropertyValue($blockInfo['id']);
					$tmp = $mod->ValidateContentBlockFieldValue($blockName, $value, $blockInfo['params'], $this);
					if (!empty($tmp)) {
						$errors[] = $tmp;
					}
				}
			}
		}

		if (!$have_content_en) {
			$errors[] = $this->mod->Lang('error_no_default_content_block');
		}
		return $errors;
	}

	/**
	 * @since 2.0
	 * @returns string
	 */
	public function TemplateResource() : string
	{
/*
		$tmp = $this->GetPropertyValue('template_rsrc');
		if( !$tmp ) $tmp = $this->mTemplateId;
		if( $tmp ) {
			$num = (int) $tmp;
			if( $num > 0 && trim($num) == $tmp ) {
				// numeric: assume normal (database) template
				return "cms_template:$tmp";
			} else {
				return $tmp;
			}
		}
		return '';
*/
		return 'cms_template:'.$this->mTemplateId;
	}

	/**
	 * Return html to display an input element for modifying a property
	 * of this object.
	 *
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
// static properties here >> Lone property|ies ?
//		static $_designs;
//		static $_types;
//		static $_designtree;
//		static $_designlist = null;
		static $_templates = null;

		$id = 'm1_';

		switch ($propname) {
		case 'design_id':
/*
			// get the dflt/current design id
			try {
				$design_id = $this->GetPropertyValue('design_id');
				if( $design_id < 1 ) {
					try {
						$dflt_design = DesignManager\Design::load_default(); DISABLED
						$design_id = $dflt_design->get_id();
					}
					catch (Throwable $t) {
						log_error('No default design specified');
					}
				}
				$input = '';
				if( $_designlist ) {
					$input = FormUtils::create_dropdown('design_id',array_flip($_designlist),$this->GetPropertyValue('design_id'),
														 ['prefix'=>$id',id'=>'design_id']);
					return [
					'for="design_id">*'.$this->mod->Lang('design'),
					AdminUtils::get_help_tag($this->domain,'info_editcontent_design',$this->mod->Lang('help_title_editcontent_design')),
					$input
					];
				}
			}
			catch (Throwable $t) {
				// nothing here yet.
			}
*/
			break;

		case 'template':
			try {
				$template_id = $this->TemplateId();
				if ($template_id < 1) {
					try {
						$dflt_tpl = TemplateOperations::get_default_template_by_type(TemplateType::CORE.'::page');
						$template_id = $dflt_tpl->get_id();
					} catch (Throwable $t) {
						log_error('No default page template found');
					}
				}

				if ($_templates == null) {
					$_templates = [];
					// TODO see get_template_list()
					$list = TemplateOperations::template_query(['as_list' => 1]);
					if ($list) {
						natcasesort($list);
						foreach ($list as $tpl_id => $tpl_name) {
							$_templates[] = ['value' => $tpl_id, 'label' => $tpl_name];
						}
					}
//					$_designlist = DesignManager\Design::get_list(); DISABLED
				}

				$input = FormUtils::create_dropdown('template_id', $_templates, $template_id, ['prefix' => $id, 'id' => 'template_id']);
				return [
				'for="template_id">*'.$this->mod->Lang('template'),
				AdminUtils::get_help_tag($this->domain, 'info_editcontent_template', $this->mod->Lang('help_title_editcontent_template')),
				$input
				];
			} catch (Throwable $t) {
				// nothing here (yet?)
			}
			break;
/*
		case 'template_rsrc': //TODO make this work
			try {
				$current = $this->GetPropertyValue('template_rsrc');
				if( !$current ) $current = $this->TemplateId();
				$options = $this->get_template_list();
/ *
				$input = FormUtils::create_dropdown('template_rsrc',$options,$current,['prefix'=>$id,'id'=>'template_rsrc']);
				return [
				'for="template_rsrc">*'.$this->mod->Lang('template'),
				AdminUtils::get_help_tag($this->domain,'info_editcontent_template',$this->mod->Lang('help_title_editcontent_template')),
				$input
				];
* /
				return [];
			}
			catch (Throwable $t) {
				// nothing here (yet?)
			}
			break;
*/
		case 'pagemetadata':
			$input = FormUtils::create_textarea([
				'getid' => $id,
				'htmlid' => 'idmetadata',
				'name' => 'pagemetadata',
				'class' => 'pagesmalltextarea',
				'value' => $this->MetaData(),
			]);
			return [
			'for="idmetadata">'.$this->mod->Lang('page_metadata'),
			AdminUtils::get_help_tag($this->domain, 'help_content_pagemeta', $this->mod->Lang('help_title_content_pagemeta')),
			$input
			];

		case 'pagedata':
			$input = FormUtils::create_textarea([
				'getid' => $id,
				'htmlid' => 'idpagedata',
				'name' => 'pagedata',
				'class' => 'pagesmalltextarea',
				'value' => $this->GetPropertyValue('pagedata'),
			]);
			return [
			'for="idpagedata">'.$this->mod->Lang('pagedata_codeblock'),
			AdminUtils::get_help_tag($this->domain, 'help_content_pagedata', $this->mod->Lang('help_title_content_pagedata')),
			$input
			];

		case 'defaultcontent':
			return [
			'for="defaultcontent">'.$this->mod->Lang('defaultcontent'),
			AdminUtils::get_help_tag($this->domain, 'help_content_default', $this->mod->Lang('help_title_content_default')),
			'<input type="hidden" name="'.$id.'defaultcontent" value="0"><input type="checkbox" id="defaultcontent" name="'.$id.' value="1" defaultcontent"'.($this->mDefaultContent ? ' checked' : '').'>'
			];

		case 'searchable':
			$searchable = $this->GetPropertyValue('searchable');
			if ($searchable == '') {
				$searchable = 1;
			}
			return [
			'for="searchable">'.$this->mod->Lang('searchable'),
			AdminUtils::get_help_tag($this->domain, 'help_page_searchable', $this->mod->Lang('help_title_page_searchable')),
			'<input type="hidden" name="'.$id.'searchable" value="0"><input type="checkbox" id="searchable" name="'.$id.'searchable" value="1"'.(($searchable) ? ' checked' : '').'>'
			];

		case 'disable_wysiwyg':
			$disable_wysiwyg = $this->GetPropertyValue('disable_wysiwyg');
		if ($disable_wysiwyg == '') {
			$disable_wysiwyg = 0;
		}
			return [
			'for="disablewysiwyg">'.$this->mod->Lang('disable_wysiwyg'),
			AdminUtils::get_help_tag($this->domain, 'help_page_disablewysiwyg', $this->mod->Lang('help_title_page_disablewysiwyg')),
			'<input type="hidden" name="'.$id.'disable_wysiwyg" value="0"><input type="checkbox" id="disablewysiwyg" name="'.$id.'disable_wysiwyg" value="1"'.(($disable_wysiwyg) ? ' checked' : '').'>'
			];

		case 'wantschildren':
			$showadmin = Lone::get('ContentOperations')->CheckPageOwnership(get_userid(), $this->Id());
			if (check_permission(get_userid(), 'Manage All Content') || $showadmin) {
				$wantschildren = $this->WantsChildren();
				return [
				'for="wantschildren">'.$this->mod->Lang('wantschildren'),
				AdminUtils::get_help_tag($this->domain, 'help_page_wantschildren', $this->mod->Lang('help_title_page_wantschildren')),
				'<input type="hidden" name="'.$id.'wantschildren" value="0"><input type="checkbox" id="wantschildren" name="'.$id.'wantschildren" value="1"'.(($wantschildren) ? ' checked' : '').'>'
				];
			}
			break;

		default:
			// check if it's content block
			$blocks = $this->get_content_blocks();
			if (isset($blocks[$propname])) {
				// it's a content block
				$block = $blocks[$propname];
				$data = $this->GetPropertyValue($block['id']);
				return $this->display_content_block($propname, $block, $data, $adding);
			} else {
				// call the parent class
				return parent::ShowElement($propname, $adding);
			}
		}
		return [];
	}

	/**
	 * @ignore
	 * @since 2.0
	 */
	protected function get_template_list()
	{
		// static properties here >> Lone property|ies ?
		static $_list;
		if ($_list) {
			return $_list;
		}

		$_list = [];
//		$config = Lone::get('Config');
//		if (!$config['page_template_list']) { //WHAAAT ?
		$_tpl = TemplateOperations::template_query(['as_list' => 1]);
		if ($_tpl) {
			natcasesort($_tpl);
			foreach ($_tpl as $tpl_id => $tpl_name) {
				$_list[] = ['label' => $tpl_name, 'value' => $tpl_id];
			}
		}
/*		} else {
			$raw = $config['page_template_list'];
			if( is_string($raw) ) { $raw = [ $this->mod->Lang('default')=>$raw ]; }
			else { natcasesort($raw); }
			foreach( $raw as $label => $rsrc ) {
				$_list[] = [ 'label'=>$label, 'value'=>$rsrc ];
			}
		}
*/
		$tmp = array_column($_list, 'label');
		array_multisort($tmp, SORT_ASC, SORT_NATURAL, $_list); //TODO encoded-strings sort
		return $_list;
	}

	/**
	 * Return content blocks in the current page's template.
	 *
	 * @access private
	 * @internal
	 */
	private function get_content_blocks() : array
	{
		if (is_array($this->_contentBlocks)) {
			return $this->_contentBlocks;
		}

		$smarty = Lone::get('Smarty');
		try {
			$parser = new page_template_parser('cms_template:'.$this->TemplateId(), $smarty);
			//redundant  page_template_parser::reset();
			$parser->compileTemplateSource();
			$this->_contentBlocks = page_template_parser::get_content_blocks();
		} catch (SmartyException $e) {
			$this->_contentBlocks = [];
			// smarty exceptions here could be a bad template, or missing template, or something else.
			throw new ContentException($this->mod->Lang('error_parsing_content_blocks').': '.$e->getMessage());
		}
		return $this->_contentBlocks;
	}

	private function _get_param($in, $key, $dflt = null)
	{
		if (!is_array($in)) {
			return $dflt;
		}
		if (is_array($key)) {
			return $dflt;
		}
		if (!isset($in[$key])) {
			return $dflt;
		}
		return $in[$key];
	}

	/**
	 * @ignore
	 * @param array $blockInfo
	 * @param mixed $value string|null
	 * @return string
	 */
	private function _display_text_block(array $blockInfo, $value/*, bool $adding*/)
	{
/* TODO any valid page-editor
		if( cms_to_bool($this->_get_param($blockInfo,'adminonly',0)) ) {
			$uid = get_userid(false);
			$res = Lone::get('UserOperations')->UserInGroup($uid,1);
			if( !$res ) return '';
		}
*/
		if ($this->Id() < 1 && $value === '') { // unsaved content without value
			$value = trim($this->_get_param($blockInfo, 'default'));
		}
		$required = cms_to_bool($this->_get_param($blockInfo, 'required'));
		$placeholder = trim($this->_get_param($blockInfo, 'placeholder'));
		if (cms_to_bool($this->_get_param($blockInfo, 'oneline'))) {
			$size = (int) $this->_get_param($blockInfo, 'size', 50);
			$maxlength = (int) $this->_get_param($blockInfo, 'maxlength', 255);
			$ret = '<input type="text" size="'.$size.'" maxlength="'.$maxlength.'" name="'.$blockInfo['id'].'" value="'. specialize($value, ENT_NOQUOTES).'"';
			if ($required) {
				$ret .= ' required';
			}
			if ($placeholder) {
				$ret .= " placeholder=\"{$placeholder}\"";
			}
			$ret .= '>';
		} else {
			if ($this->GetPropertyValue('disable_wysiwyg')) {
				$block_wysiwyg = false;
			} else {
				$block_wysiwyg = cms_to_bool($blockInfo['usewysiwyg']);
			}

			$parms = ['name' => $blockInfo['id'], 'enablewysiwyg' => $block_wysiwyg, 'value' => $value, 'id' => $blockInfo['id']];
			if ($required) {
				$parms['required'] = 'required';
			}
			if ($placeholder) {
				$parms['placeholder'] = $placeholder;
			}
			$parms['width'] = (int) $this->_get_param($blockInfo, 'width', 80);
			$parms['height'] = (int) $this->_get_param($blockInfo, 'height', 10);
			if (isset($blockInfo['cssname']) && $blockInfo['cssname']) {
				$parms['cssname'] = $blockInfo['cssname'];
			}
			if ((!isset($parms['cssname']) || $parms['cssname'] == '') && AppParams::get('content_cssnameisblockname', 1)) {
				$parms['cssname'] = $blockInfo['id'];
			}
			foreach ($blockInfo as $key => $val) {
				if (!startswith($key, 'data-')) {
					continue;
				}
				$parms[$key] = $val;
			}
			$ret = FormUtils::create_textarea($parms);
		}
		return $ret;
	}

	/**
	 * @ignore
	 * @param array $blockInfo
	 */
	private function _display_static_text_block(array $blockInfo) : array
	{
		$input = '<div class="static_text" data-name="'.$blockInfo['name'].'"}>';
		$input .= $blockInfo['static_content'];
		$input .= "</div>\n";
		return [' ', $input];
	}

	/**
	 * @ignore
	 * @param array $blockInfo
	 * @param mixed $value string|null
	 * @return string
	 */
	private function _display_image_block(array $blockInfo, $value/*, bool $adding*/)
	{
/* TODO any valid page-editor
		$adminonly = cms_to_bool($this->_get_param($blockInfo,'adminonly',0));
		if( $adminonly ) {
			$uid = get_userid(false);
			$res = Lone::get('UserOperations')->UserInGroup($uid,1);
			if( !$res ) return '';
		}
*/
		$config = Lone::get('Config');
		$adddir = AppParams::get('contentimage_path');
		if ($blockInfo['dir'] != '') {
			$adddir = $blockInfo['dir'];
		}
		$dir = cms_join_path($config['uploads_path'], $adddir);
		$rp1 = realpath($config['uploads_path']);
		$rp2 = realpath($dir);

		$dropdown = null;
		if (!startswith($rp2, $rp1)) {
			$err = $this->mod->Lang('err_invalidcontentimgpath');
			return '<div class="error">'.$err.'</div>';
		}

		$id = 'm1_';
		$inputname = $blockInfo['id'];
		if (isset($blockInfo['inputname'])) {
			$inputname = $blockInfo['inputname'];
		}
		$prefix = '';
		if (isset($blockInfo['sort'])) {
			$sort = (int)$blockInfo['sort'];
		}
		if (isset($blockInfo['exclude'])) {
			$prefix = $blockInfo['exclude'];
		}
		$filepicker = Utils::get_filepicker_module();
		if ($filepicker) {
			$profile_name = $blockInfo['profile'] ?? '';
			$profile = $filepicker->get_profile_or_default($profile_name, $dir, get_userid());
			$parms = ['top' => $dir, 'type' => FileType::IMAGE];
			if ($sort) {
				$parms['sort'] = true;
			}
			if ($prefix) {
				$parms['exclude_prefix'] = $prefix;
			}
			$profile = $profile->overrideWith($parms);
			$input = $filepicker->get_html($id.$inputname, $value, $profile);
			return $input;
		} else {
			// TODO $id 'm1_'.$inputname if this is an admin request ?
			$dropdown = create_file_dropdown($id.$inputname, $dir, $value, 'jpg,jpeg,png,gif', '', true, '', $prefix, 1, $sort); //TODO other extensions e.g. webp see FileTypeHelper class
			if ($dropdown === false) {
				$dropdown = $this->mod->Lang('error_retrieving_file_list');
			}
			return $dropdown;
		}
	}

	/**
	 * NOTE $blockInfo['module']/$mod needs to self-manage
	 * delivery of any ancillaries e.g. block-related css, js
	 * @internal
	 * @param string $blockName content block name
	 * @param array $blockInfo method/element parameters
	 * @param mixed $value string|null initial value of the element to be created
	 * @param bool  $adding	Flag indicating whether the content editor is in create mode (adding) vs. edit mode.
	 * @return mixed string | null
	 */
	private function _display_module_block(string $blockName, array $blockInfo, $value, bool $adding)
	{
/* TODO any valid page-editor
		$adminonly = cms_to_bool($this->_get_param($blockInfo,'adminonly',0));
		if( $adminonly ) {
			$uid = get_userid(false);
			$res = Lone::get('UserOperations')->UserInGroup($uid,1);
			if( !$res ) return '';
		}
*/
		if (!isset($blockInfo['module'])) {
			return false;
		}
		$mod = Utils::get_module($blockInfo['module']);
		if (!is_object($mod)) {
			return false;
		}
		if (!$mod->HasCapability(CapabilityType::CONTENT_BLOCKS)) {
			return false;
		}
		if (!empty($blockInfo['inputname'])) {
			// a hack to allow overriding the input field name.
			$blockName = $blockInfo['inputname'];
		}
		$tmp = $mod->GetContentBlockFieldInput($blockName, $value, $blockInfo['params'], $adding, $this);
		return $tmp;
	}

	/**
	 * @ignore
	 * @param string $blockName e.g. 'content_en'
	 * @param array $blockInfo method parameters
	 * @param mixed $value string|null initial value
	 * @param bool $adding Optional flag indicating whether the content editor is in create mode (adding) vs. edit mode. Default false
	 * @return array 3-members (label, popup, input) | empty
	 */
	private function display_content_block(string $blockName, array $blockInfo, $value, bool $adding = false)
	{
		// it'd be nice if the content block was an object..
		// but I don't have the time to do it at the moment.
		$noedit = cms_to_bool($this->_get_param($blockInfo, 'noedit', 'false'));
		if ($noedit) {
			return [];
		}

		$labeltext = trim($this->_get_param($blockInfo, 'label'));
		if (!$labeltext) {
			$labeltext = $blockName;
		}
		if ($blockName == 'content_en' && $labeltext == $blockName) {
			$labeltext = $this->mod->Lang('content');
			$popup = AdminUtils::get_help_tag($this->domain, 'help_content_content_en', $this->mod->Lang('help_title_maincontent'));
		} else {
			$popup = '';
		}
		$required = cms_to_bool($this->_get_param($blockInfo, 'required', 'false'));
		if ($required) {
			$labeltext = '* '.$labeltext;
		}
		$label = 'for="'.$blockName.'">'.$labeltext;
		$input = '';

		switch ($blockInfo['type']) {
		case 'text':
			$input = $this->_display_text_block($blockInfo, $value/*,$adding*/);
			break;

		case 'image':
			$input = $this->_display_image_block($blockInfo, $value/*,$adding*/);
			break;

		case 'static':
			$tmp = $this->_display_static_text_block($blockInfo);
			if (is_array($tmp)) {
				$input = $tmp[0];
				if (count($tmp) == 2) {
					if (!$labeltext || $labeltext == $blockName) {
						$labeltext = $tmp[0];
					}
					$input = $tmp[1];
				}
			} else {
				$input = $tmp;
			}
			break;

		case 'module':
			$tmp = $this->_display_module_block($blockName, $blockInfo, $value, $adding);
			if (is_array($tmp)) {
				if (count($tmp) == 2) {
					if (!$labeltext || $labeltext == $blockName) {
						$labeltext = $tmp[0];
					}
					$input = $tmp[1];
				} else {
					$input = $tmp[0];
				}
			} else {
				$input = $tmp;
			}
			break;

		default:
			return [];
		} // switch

		return [
			$label,
			$popup,
			$input
		];
	}
} // class
