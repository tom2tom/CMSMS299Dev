<?php
/*
Plugin which aggregates stylesheet files for use in a page or template
Copyright (C) 2019-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use function CMSMS\Lone;
// since 3.0
function smarty_function_cms_render_css($params, $template)
{
	$force = isset($params['force']) ? cms_to_bool($params['force']) : false;
	$combiner = Lone::get('StylesMerger');
	$out = '';
	$filename = $combiner->render_styles(TMP_CACHE_LOCATION, $force);
	if( $filename ) {
		$url = cms_path_to_url(TMP_CACHE_LOCATION)."/$filename";
		$nocache = cms_to_bool($params['nocache'] ?? false);
		if( $nocache ) $url .= '?t='.time();
		$out = "<link rel=\"stylesheet\" type=\"text/css\" href=\"$url\" />\n";
	}
	else {
		trigger_error('Failed to merge recorded stylesheets');
	}
	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $out);
		return '';
	}
	return $out;
}

function smarty_cms_help_function_cms_render_css()
{
	echo _ld('tags', 'help_generic',
	'This plugin generates a link tag for retrieving the result of merging queued css-files',
	'cms_render_css',
	'<li>force: optional flag. If true, re-create the package even if its content seems unchanged</li>'
	);
	echo 'See also the complementary {cms_queue_css} tag.';
}

function smarty_cms_about_function_cms_render_css()
{
	$n = _la('none');
	echo _ld('tags', 'about_generic', 'Robert Campbell Dec 2019', "<li>$n</li>");
}
