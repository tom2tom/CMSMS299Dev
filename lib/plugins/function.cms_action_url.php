<?php
/*
Plugin to...
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
use CMSMS\ContentOperations;
use CMSMS\Utils;

function smarty_function_cms_action_url($params, $template)
{
	$module = $template->getTemplateVars('_module');
	$returnid = $template->getTemplateVars('returnid');
	$mid = $template->getTemplateVars('actionid');
	$action = null;
	$assign = null;
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
	$gCms = CmsApp::get_instance();
	if( $module == '' ) return;
	if( AppState::test_state(AppState::STATE_ADMIN_PAGE) && $returnid == '' ) {
		if( $mid == '' ) $mid = 'm1_';
		if( $action == '' ) $action = 'defaultadmin';
	}
	elseif( $gCms->is_frontend_request() ) {
		if( $mid == '' ) $mid = 'cntnt01';
		if( $action == '' ) $action = 'default';
		if( $returnid == '' ) {
			$returnid = Utils::get_current_pageid();
			if( $returnid < 1 ) {
				$returnid = ContentOperations::get_instance()->GetDefaultContent();
			}
		}
	}
	if( $action == '' ) return;

	$obj = Utils::get_module($module);
	if( !$obj ) return;

	$url = $obj->create_url($mid,$action,$returnid,$actionparms);
	if( !$url ) return;

	if( !empty($urlparms) ) {
		$url_ob = new CMSMS\Url( $url );
		foreach( $urlparms as $k => $v ) {
			$url_ob->set_queryvar( $key, $value );
		}
		$url = (string)$url_ob;
	}

	if( $forjs ) {
		$url = str_replace('&amp;', '&', $url);
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
	echo lang_by_realm('tags', 'about_generic'[2], 'htmlintro', <<<'EOS'
<li>detail</li> ... OR lang('none')
EOS
	);
}
*/
/*
function smarty_cms_help_function_cms_action_url()
{
	echo lang_by_realm('tags', 'help_generic', 'This plugin does ...', 'action_url ...',  <<<'EOS'
<li>param</li>
<li>module</li>
<li>action</li>
<li>returnid</li>
<li>mid</li>
<li>jobtype</li>
<li>forjs</li>
<li>_*</li>
EOS
	);
}
*/
