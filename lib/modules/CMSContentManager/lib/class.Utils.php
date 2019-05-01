<?php
# Class: CMSContentManager module utility methods
# Copyright (C) 2013-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace CMSContentManager;

use cms_userprefs;
use cms_utils;
use CmsDataNotFoundException;
//use CmsLayoutCollection;
use CmsLayoutTemplateType;
use CMSMS\TemplateOperations;
use function check_permission;
use function get_userid;

/**
 * Utility methods for the CMSContentManager module.
 *
 * This is an internal class, for the CMSContentManager module only.
 *
 * @package CMS
 * @internal
 * @ignore
 * @author Robert Campbell
 *
 */
final class Utils
{
	private function __construct() {}

	public static function get_pagedefaults()
	{
		$tpl_id = null;
		try {
			$tpl = TemplateOperations::get_default_template_by_type(CmsLayoutTemplateType::CORE.'::page');
			$tpl_id = $tpl->get_id();
		}
		catch( CmsDataNotFoundException $e ) {
			$type = CmsLayoutTemplateType::load(CmsLayoutTemplateType::CORE.'::page');
			$list = TemplateOperations::get_all_templates_by_type($type);
			$tpl = $list[0];
			$tpl_id = $tpl->get_id();
		}

		$mod = cms_utils::get_module('CMSContentManager');
		$tmp = $mod->GetPreference('page_prefs');
		if( $tmp ) {
			$page_prefs = unserialize($tmp, ['allowed_classes'=>false]);
		}
		else {
			$page_prefs = [
			'active'=>true,
			'addteditors'=>[], // array of ints
			'cachable'=>true,
			'content'=>'',
			'contenttype'=>'content',
			'defaultcontent'=>false,
//TODO styles 'design_id'=>CmsLayoutCollection::load_default()->get_id(), // int
			'disallowed_types'=>[], // array of strings
			'extra1'=>'',
			'extra2'=>'',
			'extra3'=>'',
			'metadata'=>'',
			'parent_id'=>-2, // int
			'searchable'=>true,
			'secure'=>false, // deprecated from 2.3
			'showinmenu'=>true,
			'styles'=>'',
			'template_id'=>$tpl_id,
			];
		}

		return $page_prefs;
	}

	public static function locking_enabled()
	{
		$mod = cms_utils::get_module('CMSContentManager');
		$timeout = (int) $mod->GetPreference('locktimeout');
		return $timeout > 0;
	}

	public static function get_pagenav_display()
	{
		$userid = get_userid(false);
		$pref = cms_userprefs::get($userid,'ce_navdisplay');
		if( !$pref ) {
			$mod = cms_utils::get_module('CMSContentManager');
			$pref = $mod->GetPreference('list_namecolumn');
			if( !$pref ) $pref = 'title';
		}
		return $pref;
	}

	/**
	 * Create a hierarchical ordered dropdown of some or all of the content objects
	 * in the system, for use in the admin console and various modules.
	 * If $current or $parent parameters are provided, care is taken to ensure that
	 * children which could cause a loop are hidden, when creating a dropdown for
	 * changing a content object's parent.
	 *
	 * This method uses the CMSMS jQuery hierselector widget.
	 * @since 2.3 This method was migrated from the ContentOperations s
	 *for the
	 * @pahe id of the content object we are working with. Default 0.
	 *   Used with $allow_current to ignore children of the current current object, or itself.
	 * @param int $value The id of the currently selected content object. Default 0.
	 * @param string $name The html name of the dropdown. Default 'parent_id'.
	 * @param bool $allow_current Ensures that the current value cannot be selected, or $current and its children.
	 *   Used to prevent circular deadlocks.
	 * @param bool $use_perms If true, check page-edit permission and show
	 *  only the pages that the current user may edit. Default false.
	 * @param bool $allow_all Whether to also show items which don't have a
	 *  valid link. Default false.
	 * @param bool $for_child since 2.2 Whether to obey the WantsChildren()
	 *  result reported by each content object. Default false.
	 * @return string html and js for the dropdown
	 */
	public static function CreateHierarchyDropdown(
		$current = 0,
		$value = 0,
		$name = 'parent_id',
		$allow_current = false,
		$use_perms = false,
		$allow_all = false,
		$for_child = false)
	{
		static $count = 0;

		++$count;
		$elemid = 'cms_hierdropdown'.$count;
		$id = 'm1_';
		$value = (int) $value;
		$uid = get_userid(false);
		$modify_all = check_permission($uid,'Manage All Content') || check_permission($uid,'Modify Any Page');
		$mod = cms_utils::get_module('CMSContentManager');
		$root_url = $mod->GetModuleURLPath().'/lib/js';
		$url = $mod->create_url('','ajax_hiersel_content');
		$ajax_url = str_replace('&amp;','&',$url) . '&'.CMS_JOB_KEY.'=1';
		$title = $mod->Lang('title_hierselect_select');
		$ititle = $mod->Lang('title_hierselect');

		$opts = [];
		$opts['current'] = (int)$current;
		$opts['value'] = $value;
		$opts['is_manager'] = ($modify_all) ? 'true' : 'false';
		$opts['allow_current'] = ($allow_current) ? 'true' : 'false';
		$opts['allow_all'] = ($allow_all) ? 'true' : 'false';
		$opts['use_perms'] = ($use_perms) ? 'true' : 'false';
		$opts['use_simple'] = ($modify_all) ? 'false' : 'true';
		$opts['for_child'] = ($for_child) ? 'true' : 'false';

		$str = '';
		foreach($opts as $key => $val) {
			$str .= "\n   ".$key.':'.$val.',';
		}
		$str = substr($str,0,-1)."\n  ";
		//TODO min.js for production
		$out = <<<EOS
<script type="text/javascript" src="{$root_url}/jquery.cmsms_hierselector.js"></script>
<script type="text/javascript">
 //<![CDATA[
 $(function() {
  cms_data.ajax_hiersel_url = '$ajax_url';
  cms_data.lang_hierselect_title = '$title';
  $('#$elemid').hierselector({{$str}});
 });
 //]]>
</script>
<input type="text" id="$elemid" class="cms_hierdropdown" name="{$id}{$name}" title="$ititle" value="$value" size="8" maxlength="8" />

EOS;
		return $out;
	}
} // class
