<?php
# CMSContentManager module utility methods class
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
use CmsLayoutTemplateType;
use CMSMS\StylesheetOperations;
use CMSMS\TemplateOperations;
use stdClass;
use Throwable;
use function get_userid;
use function lang;

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

	public static function get_pagedefaults() : array
	{
		$mod = cms_utils::get_module('CMSContentManager');
		$tmp = $mod->GetPreference('page_prefs');
		if( $tmp ) {
			try {
				$page_prefs = unserialize($tmp, ['allowed_classes'=>false]);
			}
			catch( Throwable $t ) {
				$tmp = false;
			}
		}
		if( !$tmp ) {
			try {
				$tpl = TemplateOperations::get_default_template_by_type(CmsLayoutTemplateType::CORE.'::page');
				$tpl_id = $tpl->get_id();
			}
			catch( Throwable $t ) {
				$type = CmsLayoutTemplateType::load(CmsLayoutTemplateType::CORE.'::page');
				$list = TemplateOperations::get_all_templates_by_type($type);
				$tpl = $list[0];
				$tpl_id = $tpl->get_id();
			}
			$page_prefs = [
				'active'=>true,
				'addteditors'=>[], // array of userid|groupid ints
				'cachable'=>true,
				'content'=>'',
				'contenttype'=>'content',
				'defaultcontent'=>false,
				'disallowed_types'=>[], // array of strings
				'extra1'=>'',
				'extra2'=>'',
				'extra3'=>'',
				'metadata'=>'',
				'parent_id'=>-2, // int
				'searchable'=>true,
				'secure'=>false, // deprecated from 2.3
				'showinmenu'=>true,
				'styles'=>'', // OR some sensible default ?
				'template_id'=>$tpl_id,
			];
		}

		return $page_prefs;
	}

	public static function locking_enabled() : bool
	{
		$mod = cms_utils::get_module('CMSContentManager');
		$timeout = (int)$mod->GetPreference('locktimeout');
		return $timeout > 0;
	}

	public static function get_pagenav_display() : string
	{
		$userid = get_userid(false);
		$pref = cms_userprefs::get($userid,'ce_navdisplay');
		if( !$pref ) {
			$mod = cms_utils::get_module('CMSContentManager');
			$pref = $mod->GetPreference('list_namecolumn','title');
		}
		return $pref;
	}

	/**
	 * @since 2.3
	 * @param mixed $selected int[] | int | comma-separated string
	 * @return array
	 */
	public static function get_sheets_data($selected = null) : array
	{
		$sheets = StylesheetOperations::get_displaylist();
		if( $sheets ) {
			if( $selected ) {
				if( !is_array($selected) ) {
					$selected = explode(',',$selected);
				}
			}
			else {
				$selected = [];
			}
			$grouped = false;
			foreach( $sheets as $one ) {
				if( $one['members'] ) {
					$grouped = true;
					break;
				}
			}
			$gname = lang('group').' : ';

			$selrows = [];
			$unselrows = [];
			foreach( $sheets as $one ) {
				$ob = new stdClass();
				$ob->id = $one['id'];
				$ob->name = ($ob->id > 0) ? $one['name'] : $gname.$one['name'];
				if( $grouped ) {
					$ob->members = $one['members'];
				}
				if( $selected && in_array($one['id'],$selected) ) {
					$ob->checked = true;
					$selrows[] = $ob;
				}
				else {
					$ob->checked = false;
					$unselrows[] = $ob;
				}
			}

			if( count($selrows) > 1 ) {
				usort($selrows, function($a, $b) use ($selected)
				{
					// by selection order
					$pa = array_search($a->id, $selected);
					$pb = array_search($b->id, $selected);
					return $pa <=> $pb;
				});
			}
			if( count($unselrows) > 1 ) {
				usort($unselrows, function($a, $b)
				{
					// groups first, then by name
					if( $a->id < 0 && $b->id > 0 ) return -1;
					if( $a->id > 0 && $b->id < 0 ) return 1;
					return strcmp($a->name, $b->name);
				});
			}
			$rows = array_merge($selrows,$unselrows);

			if( count($rows) > 1 ) {
				$js = <<<EOS
<script type="text/javascript">
//<![CDATA[
$(function() {
 var tbl = $('#allsheets');
 tbl.find('tbody.rsortable').sortable({
  connectWith: '.rsortable',
  items: '> tr',
  appendTo: tbl,
  helper: 'clone',
  zIndex: 9999
 }).disableSelection();
 tbl.droppable({
  accept: '.rsortable tr',
  hoverClass: 'ui-state-hover',
  drop: function(ev,ui) {
   return false;
  }
 });
});
//]]>
</script>
EOS;
			}
			else {
				$js = '';
			}
			return [$rows, $grouped, $js];
		}
		return [null, false, ''];
	}
} // class
