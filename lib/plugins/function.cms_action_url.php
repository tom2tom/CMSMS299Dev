<?php
#Plugin to...
#Copyright (C)2013-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

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
			$urlparms[CMS_JOB_KEY] = max(0,min(2,(int)$value));
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
	if( $gCms->test_state(CmsApp::STATE_ADMIN_PAGE) && $returnid == '' ) {
		if( $mid == '' ) $mid = 'm1_';
		if( $action == '' ) $action = 'defaultadmin';
	}
	elseif( $gCms->is_frontend_request() ) {
		if( $mid == '' ) $mid = 'cntnt01';
		if( $action == '' ) $action = 'default';
		if( $returnid == '' ) {
			$returnid = cms_utils::get_current_pageid();
			if( $returnid < 1 ) {
				$contentops = $gCms->GetContentOperations();
				$returnid = $contentops->GetDefaultContent();
			}
		}
	}
	if( $action == '' ) return;

	$obj = cms_utils::get_module($module);
	if( !$obj ) return;

	$url = $obj->create_url($mid,$action,$returnid,$actionparms);
	if( !$url ) return;

	if( !empty($urlparms) ) {
		$url_ob = new cms_url( $url );
		foreach( $urlparms as $k => $v ) {
			$url_ob->set_queryvar( $key, $value );
		}
		$url = (string) $url_ob;
	}

	if( $forjs ) {
		$url = str_replace('&amp;','&',$url);
	}

	if( $assign ) {
		$template->assign($assign,$url);
		return;
	}
	return $url;
}
