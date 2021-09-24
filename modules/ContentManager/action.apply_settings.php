<?php
/*
ContentManager module action: apply_settings
Copyright (C) 2019-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use ContentManager\Utils;

if( !$this->CheckContext() ) exit;
if( !$this->CheckPermission('Modify Site Preferences') ) exit;

if( isset($params['cancel']) ) {
	$this->RedirectToAdminTab('','','settings');
}

switch( $params['tab'] ) {
	case 'general':
		$val = (int)$params['locktimeout'];
		if( $val != 0 ) $val = max(5,min(480,$val));
		$this->SetPreference('locktimeout',$val);

		$val = (int)$params['lockrefresh'];
		if( $val != 0 ) $val = max(30,min(3600,(int)$params['lockrefresh']));
		$this->SetPreference('lockrefresh',$val);

		$str = $params['template_list_mode'] ?? 'allpage';
		$this->SetPreference('template_list_mode',$str);
		break;
	case 'listsettings':
		$str = $params['list_namecolumn'] ?? 'title';
		$this->SetPreference('list_namecolumn',$str);

		$this->SetPreference('list_visiblecolumns',implode(',',$params['list_visiblecolumns']));
		break;
	case 'pagedefaults':
		$page_prefs = Utils::get_pagedefaults();
		$modified = false;
		foreach( $params as $fld => $val ) {
			switch( $fld ) {
				case 'action':
				case 'submit':
				case 'tab':
					break;
				case 'styles':
					$val = implode(',',$val);
					//no break here
				default:
					if( !isset($page_prefs[$fld]) || $page_prefs[$fld] != $val) {
						$page_prefs[$fld] = $val;
						$modified = true;
					}
					break;
			}
		}
		if( !$modified ) {
			$this->RedirectToAdminTab($params['tab'],'','settings');
		}
		// verify as needed
		if( is_array($page_prefs['disallowed_types']) && in_array($page_prefs['contenttype'],$page_prefs['disallowed_types']) ) {
			$this->SetError($this->Lang('error_contenttype_disallowed'));
			$this->RedirectToAdminTab($params['tab'],'','settings');
		}
		$this->SetPreference('page_prefs',serialize($page_prefs));
		break;
	default:
		$this->RedirectToAdminTab('','','settings');
}

$this->SetMessage($this->Lang('msg_prefs_saved'));
$this->RedirectToAdminTab($params['tab'],'','settings');
