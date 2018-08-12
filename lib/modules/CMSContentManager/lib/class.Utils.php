<?php
# Class: CmsContentManager module utility methods
# Copyright (C) 2013-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
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
use CmsLayoutCollection;
use CmsLayoutTemplate;
use CmsLayoutTemplateType;
use function get_userid;

/**
 * Utility methods for the CmsContentManager module.
 *
 * This is an internal class, for the CMSContentManager module only.
 *
 * @package CMS
 * @internal
 * @ignore
 * @author Robert Campbell
 * @copyright Copyright (c) 2013, Robert Campbell <calguy1000@cmsmadesimple.org>
 */
final class Utils
{
	private function __construct() {}

	public static function get_pagedefaults()
	{
		$tpl_id = null;
		try {
			$tpl = CmsLayoutTemplate::load_dflt_by_type(CmsLayoutTemplateType::CORE.'::page');
			$tpl_id = $tpl->get_id();
		}
		catch( CmsDataNotFoundException $e ) {
			$type = CmsLayoutTemplateType::load(CmsLayoutTemplateType::CORE.'::page');
			$list = CmsLayoutTemplate::load_all_by_type($type);
			$tpl = $list[0];
			$tpl_id = $tpl->get_id();
		}

		$page_prefs = [
			'active'=>1, // boolean
			'addteditors'=>[], // array of ints.
			'cachable'=>1, // boolean
			'content'=>'', // string
			'contenttype'=>'content', // string
			'design_id'=>CmsLayoutCollection::load_default()->get_id(), // int
			'disallowed_types'=>'', // array of strings
			'extra1'=>'', // string
			'extra2'=>'', // string
			'extra3'=>'', // string
			'metadata'=>'', // string
			'parent_id'=>-2, // int
			'searchable'=>1, // boolean
			'secure'=>0, // boolean
			'showinmenu'=>1, // boolean
			'template_id'=>$tpl_id,
		];
		$mod = cms_utils::get_module('CMSContentManager');
		$tmp = $mod->GetPreference('page_prefs');
		if( $tmp ) $page_prefs = unserialize($tmp);

		return $page_prefs;
	}

	public static function locking_enabled()
	{
		$mod = cms_utils::get_module('CMSContentManager');
		$timeout = (int) $mod->GetPreference('locktimeout');
		if( $timeout > 0 ) return TRUE;
		return FALSE;
	}

	public static function get_pagenav_display()
	{
		$userid = get_userid(FALSE);
		$pref = cms_userprefs::get($userid,'ce_navdisplay');
		if( !$pref ) {
			$mod = cms_utils::get_module('CMSContentManager');
			$pref = $mod->GetPreference('list_namecolumn');
			if( !$pref ) $pref = 'title';
		}
		return $pref;
	}
} // class

