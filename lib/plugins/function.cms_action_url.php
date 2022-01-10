<?php
/*
Plugin to get an action-URL
Copyright (C) 2013-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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

use CMSMS\AppState;
use CMSMS\SingleItem;
use CMSMS\Url;
use CMSMS\Utils;

function smarty_function_cms_action_url($params, $template)
{
	$module = $template->getTemplateVars('_module');
	$returnid = $template->getTemplateVars('returnid');
	$mid = $template->getTemplateVars('actionid');
	$action = '';
	$assign = '';
	$forjs  = 0;

	$actionparms = [];
	foreach( $params as $key => $value ) {
		switch( $key ) {
		case 'module':
			$module = trim($value);
			break;
		case 'action':
			$action = trim($value);
			break;
		case 'returnid':
			$returnid = (int)trim($value);
			break;
		case 'mid':
			$mid = trim($value);
			break;
		case 'jobtype':
			$urlparms[CMS_JOB_KEY] = max(0, min(2, (int)$value));
			break;
		case 'assign':
			$assign = trim($value);
			break;
		case 'forjs':
			$forjs = 1;
			break;
		default:
			if( startswith($key, '_') ) {
				$urlparms[substr($key, 1)] = $value;
			} else {
				$actionparms[$key] = $value;
			}
			break;
		}
	}

	// validate params
	$gCms = SingleItem::App();
	if( $module == '' ) return;
	if( AppState::test(AppState::ADMIN_PAGE) && $returnid == '' ) {
		if( $mid == '' ) $mid = 'm1_';
		if( $action == '' ) $action = 'defaultadmin';
	}
	elseif( $gCms->is_frontend_request() ) {
		if( $mid == '' ) $mid = 'cntnt01';
		if( $action == '' ) $action = 'default';
		if( $returnid == '' ) {
			$returnid = Utils::get_current_pageid();
			if( $returnid < 1 ) {
				$returnid = SingleItem::ContentOperations()->GetDefaultContent();
			}
		}
	}
	if( $action == '' ) return;

	$mod = Utils::get_module($module);
	if( !$mod ) return;

	$url = $mod->create_action_url($mid,$action,$actionparms);
	if( !$url ) return;

	if( !empty($urlparms) ) {
		$url_ob = new Url($url);
		foreach( $urlparms as $key => $value ) {
			$url_ob->set_queryvar($key,$value);
		}
		$url = (string)$url_ob;
	}

	if( $forjs ) {
		$url = str_replace('&amp;','&',$url); // prob. redundant
	}

	if( $assign ) {
		$template->assign($assign,$url);
		return;
	}
	return $url;
}
/*
function smarty_cms_about_function_cms_action_url()
{
	$n = _la('none');
	echo _ld('tags', 'about_generic', 'Robert Campbell 2013', "<li>$n</li>");
}
*/
function smarty_cms_help_function_cms_action_url()
{
	echo _ld('tags', 'help_generic', 'This plugin generates nn action-URL',
	'action_url ...',
	'<li>module: Name of module where the action exists</li>
<li>action: Name of action</li>
<li>returnid: Page ID to return to, or falsy for admin</li>
<li>mid: Action-parameters prefix Default \'m1_\' or \'cntnt01\'</li>
<li>jobtype: CMS_JOB_KEY 0..2 to include in the URL</li>
<li>forjs: Whether to format the URL suited to javascript</li>
<li>_*: other URL parameter keys and values</li>'
	);
}