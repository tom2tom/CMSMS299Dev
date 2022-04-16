<?php
/*
Plugin to retrieve site metadata property
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

use CMSMS\AppParams;
use CMSMS\Events;
use CMSMS\HookOperations;
use CMSMS\SingleItem;

function smarty_function_metadata($params, $template)
{
	$gCms = SingleItem::App();
	$content_obj = $gCms->get_content_object();
	$cid = $content_obj->Id();

	$result = '';
	$showbase = true;

	$config = SingleItem::Config();
	// Show a base tag unless showbase is false in config.php
	// It really can't hinder, only help
	if( isset($config['showbase'])) $showbase = cms_to_bool($config['showbase']);

	// But allow a parameter to override it
	if( isset($params['showbase']) ) {
		$showbase = cms_to_bool($params['showbase']);
	}

	HookOperations::do_hook('metadata_prerender', ['content_id'=>$cid, 'showbase'=>&$showbase, 'html'=>&$result]); //deprecated since 3.0 TODO BAD no namespace, only valid for 1st handler ...
	Events::SendEvent('Core','MetadataPrerender', ['content_id'=>$cid, 'showbase'=>&$showbase, 'html'=>&$result]);

	if( $showbase ) {
		if( $gCms->is_https_request() ) $base = $config['ssl_url'];
		else $base = CMS_ROOT_URL;
		$result .= "\n<base href=\"".$base."/\" />\n";
	}

	$result .= AppParams::get('metadata', '');

	if( is_object($content_obj) && $content_obj->Metadata() != '' ) {
		$result .= "\n" . $content_obj->Metadata();
	}
	if( strpos($result, $template->smarty->left_delimiter) !== false && strpos($result, $template->smarty->right_delimiter) !== false ) {
		$result = $template->fetch('string:'.$result);
	}

	HookOperations::do_hook('metadata_postrender', ['content_id'=>$cid,'html'=>&$result]); //deprecated since 3.0 TODO BAD no namespace, only valid for 1st handler ...
	Events::SendEvent('Core','MetadataPostrender', ['content_id'=>$cid,'html'=>&$result]);

	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $result);
		return '';
	}
	return $result;
}
/*
function smarty_cms_about_function_metadata()
{
	$n = _la('none');
	echo _ld('tags', 'about_generic','Ted Kulp 2004',"<li>$n</li>");
}
*/
function smarty_cms_help_function_metadata()
{
	echo _ld('tags', 'help_generic',
	'This plugin retrieves the site metadata property',
	'metadata ...',
	'<li>(optional)showbase: whether to include a &lt;base href%gt; item</li>'
	);
}