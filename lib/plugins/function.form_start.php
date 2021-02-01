<?php
/*
Plugin to create elements for a CMSMS form start
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

use CMSMS\AppSingle;
use CMSMS\AppState;

function smarty_function_form_start($params, $template)
{
	//populate some default params
	//cuz this form will be POST'd, we don't use secure mact parameters
	$mactparms = [];
	$mactparms['module'] = $params['module'] ?? $template->getTemplateVars('_module');
	$mactparms['mid'] = $params['mid'] ?? $template->getTemplateVars('actionid');
	$mactparms['returnid'] = $params['returnid'] ?? $template->getTemplateVars('returnid');
	$mactparms['inline'] = (!empty($params['inline'])) ? 1 : 0;

	$tagparms = [
	'method' => 'post',
	'enctype' => 'multipart/form-data',
	];
	$gCms = AppSingle::App();
	if( AppState::test_state(AppState::STATE_LOGIN_PAGE) ) {
		$tagparms['action'] = 'login.php'; // TODO might be using a login-module action
	}
	elseif( AppState::test_state(AppState::STATE_ADMIN_PAGE) ) {
		// check if it's a module action
		if( $mactparms['module'] ) {
			$tmp = $template->getTemplateVars('_action');
			if( $tmp ) $mactparms['action'] = $tmp;

			$tagparms['action'] = CMS_ROOT_URL.'/lib/moduleinterface.php';
			if( empty($mactparms['action']) ) $mactparms['action'] = 'defaultadmin';
			$mactparms['returnid'] = '';
			if( empty($mactparms['mid']) ) $mactparms['mid'] = 'm1_';
		}
	}
	elseif( $gCms->is_frontend_request() ) {
		if( $mactparms['module'] ) {
			$tmp = $template->getTemplateVars('actionparams');
			if( is_array($tmp) && isset($tmp['action']) ) $mactparms['action'] = $tmp['action'];

			$tagparms['action'] = CMS_ROOT_URL.'/lib/moduleinterface.php';
			if( !$mactparms['returnid'] ) $mactparms['returnid'] = CmsApp::get_instance()->get_content_id();
			$hm = $gCms->GetHierarchyManager();
			$node = $hm->find_by_tag('id',$mactparms['returnid']);
			if( $node ) {
				$content_obj = $node->getContent();
				if( $content_obj ) $tagparms['action'] = $content_obj->GetURL();
			}
		}
	}

	$parms = [];
	foreach( $params as $key => $value ) {
		switch( $key ) {
//		case 'module': above
		case 'action':
//		case 'mid': above
//		case 'returnid': above
			$mactparms[$key] = trim($value);
			break;

//		case 'inline': above
//			$mactparms[$key] = ($value) ? 1 : 0;
//			break;

		case 'prefix':
			$mactparms['mid'] = trim($value);
			break;

		case 'method':
			$tagparms[$key] = strtolower(trim($value));
			break;

		case 'url':
			$key = 'action';
			if( dirname($value) == '.' ) {
				$config = $gCms->GetConfig();
				$value = $config['admin_url'].'/'.trim($value);
			}
			$tagparms[$key] = trim($value);
			break;

		case 'enctype':
		case 'id':
		case 'class':
			$tagparms[$key] = trim($value);
			break;

		case 'extraparms':
			if( $value ) {
				foreach( $value as $key=>$value2 ) {
					$parms[$key] = $value2;
				}
			}
			break;

		case 'assign':
			break;

		default:
			if( startswith($key,'form-') ) {
				$key = substr($key,5);
				$tagparms[$key] = $value;
			} else {
				$parms[$key] = $value;
			}
			break;
		}
	}

	$out = '<form';
	foreach( $tagparms as $key => $value ) {
		if( $value ) {
			$out .= " $key=\"$value\"";
		} else {
			$out .= " $key";
		}
	}
	$out .= '>'."\n".'<div class="hidden">';
	if( $mactparms['module'] && $mactparms['action'] ) {
		$mact = $mactparms['module'].','.$mactparms['mid'].','.$mactparms['action'].','.(int)$mactparms['inline'];
		$out .= '<input type="hidden" name="mact" value="'.$mact.'" />';
		if( $mactparms['returnid'] != '' ) {
			$out .= '<input type="hidden" name="'.$mactparms['mid'].'returnid" value="'.$mactparms['returnid'].'" />';
		}
	}
	if( !$gCms->is_frontend_request() ) {
		if( !isset($mactparms['returnid']) || $mactparms['returnid'] == '' ) {
			if( isset( $_SESSION[CMS_USER_KEY] ) ) {
				$out .= '<input type="hidden" name="'.CMS_SECURE_PARAM_NAME.'" value="'.$_SESSION[CMS_USER_KEY].'" />';
			}
		}
	}
	foreach( $parms as $key => $value ) {
		if( !in_array($key, ['module','mid','returnid','inline',]) ) {
			$out .= '<input type="hidden" name="'.$mactparms['mid'].$key.'" value="'.$value.'" />'."\n";
		}
	}
	$out .= '</div>'."\n";
	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $out);
		return '';
	}
	return $out;
}
/*
function smarty_cms_about_function_form_start()
{
	echo lang_by_realm('tags', 'about_generic'[2], 'htmlintro', <<<'EOS'
<li>detail</li> ... OR lang('none')
EOS
	);
}
*/
/*
function smarty_cms_help_function_form_start()
{
	echo lang_by_realm('tags', 'help_generic', 'This plugin does ...', 'form_start ...', <<<'EOS'
<li>param</li>
EOS
	);
}
*/
