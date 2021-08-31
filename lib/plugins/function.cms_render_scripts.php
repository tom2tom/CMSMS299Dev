<?php
/*
Plugin which aggregates accumulated javascript for use in a page or template
Copyright (C) 2018-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

// since 2.99
function smarty_function_cms_render_scripts( $params, $template )
{
	$combiner = CMSMS\SingleItem::App()->GetScriptsManager();
	$force = cms_to_bool($params['force'] ?? false);

	$out = '';
	$filename = $combiner->render_scripts(PUBLIC_CACHE_LOCATION, $force, false);
	if( $filename ) {
		$url = PUBLIC_CACHE_URL."/$filename";
		$nocache = cms_to_bool($params['nocache'] ?? false);
		if( $nocache ) $url .= '?t='.time();
		$val = cms_to_bool($params['defer'] ?? true);
		$val2 = cms_to_bool($params['async'] ?? false);
		$defer = ( $val2 ) ? ' async' : (($val) ? ' defer' : '');
		$out = "<script type=\"text/javascript\" src=\"$url\"$defer></script>";
	}
	else {
		//TODO handle error
	}
	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $out);
		return '';
	}
	return $out;
}
/*
D function smarty_cms_help_function_cms_render_scripts()
{
	echo lang_by_realm('tags', 'help_generic',
	'This plugin generates a script tag for retrieving the result of merging queued script-files',
	'cms_render_scripts',
	<<<EOS
<li>force: optional flag. If true, re-create the package even if its content seems unchanged</li>
<li>defer: optional flag. If true, defer package download</li>
<li>async: optional flag. If true, do async download</li>
EOS
	);
	echo 'See also the complementary {cms_queue_script} tag.';
}
*/
function smarty_cms_about_function_cms_render_scripts()
{
	echo lang_by_realm('tags', 'about_generic',
	<<<EOS
<p>Author: Robert Campbell</p>
<p>Version: 1.0</p>
EOS
	,
	lang('none')
	);
}
