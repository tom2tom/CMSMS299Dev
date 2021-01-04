<?php
/*
CMSContentManager module utility methods class
Copyright (C) 2013-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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

namespace CMSContentManager;

use CMSMS\StylesheetOperations;
use CMSMS\TemplateOperations;
use CMSMS\TemplateType;
use CMSMS\UserParams;
use CMSMS\Utils as AppUtils;
use stdClass;
use Throwable;
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

	public static function get_pagedefaults() : array
	{
		$mod = AppUtils::get_module('CMSContentManager');
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
				$tpl = TemplateOperations::get_default_template_by_type(TemplateType::CORE.'::page');
				$tpl_id = $tpl->get_id();
			}
			catch( Throwable $t ) {
				$type = TemplateType::load(TemplateType::CORE.'::page');
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
				'secure'=>false, // deprecated from 2.99
				'showinmenu'=>true,
				'styles'=>'', // OR some sensible default ?
				'template_id'=>$tpl_id,
			];
		}

		return $page_prefs;
	}

	public static function locking_enabled() : bool
	{
		$mod = AppUtils::get_module('CMSContentManager');
		$timeout = (int)$mod->GetPreference('locktimeout');
		return $timeout > 0;
	}

	public static function get_pagenav_display() : string
	{
		$userid = get_userid(false);
		$pref = UserParams::get($userid,'ce_navdisplay');
		if( !$pref ) {
			$mod = AppUtils::get_module('CMSContentManager');
			$pref = $mod->GetPreference('list_namecolumn','title');
		}
		return $pref;
	}

	/**
	 * @since 2.99
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
			$mod = AppUtils::get_module('CMSContentManager');
			$gname = $mod->Lang('group').' : ';

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
