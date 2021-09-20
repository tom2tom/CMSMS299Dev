<?php
/*
Plugin to retrieve xhtml representing an uploaded image
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

use CMSMS\SingleItem;

function smarty_function_image($params, $template)
{
	$text = '';
	if( !empty($params['src'] ) ) {
		$imgstart = '<img src=';
		$imgend = ' />';
		$config = SingleItem::Config();
		$text = $imgstart .= '"'.$config['image_uploads_url'].'/'.strtr($params['src'], '\\', '/').'"';
		$size = @getimagesize($config['image_uploads_path'].DIRECTORY_SEPARATOR.strtr($params['src'], '\/', DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR));

		if( !empty($params['width'] ) ) {
			$text .= ' width="'.$params['width'].'"';
		} elseif ($size[0] > 0) {
			$text .= ' width="'.$size[0].'"';
		}

		if( !empty($params['height'] ) ) {
			$text .= ' height="'.$params['height'].'"';
		} elseif ($size[1] > 0) {
			$text .= ' height="'.$size[1].'"';
		}

		if( !empty($params['alt'] ) ) {
			$alt = $params['alt'];
		} else {
			$alt = '['.$params['src'].']';
		}

		$text .= ' alt="'.$alt.'"';
		if( !empty($params['title'] ) )	{
			$text .= ' title="'.$params['title'].'"';
		} else {
			$text .= ' title="'.$alt.'"';
		}

		if( !empty($params['class'] ) )	$text .= ' class="'.$params['class'].'"';
		if( !empty($params['addtext'] ) ) $text .= ' ' . $params['addtext'];
		$text .= $imgend;
	} else {
		$text = '<!-- empty results from image plugin -->';
	}

	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $text);
		return '';
	}
	return $text;
}

function smarty_cms_about_function_image()
{
	echo <<<'EOS'
<p>Author: Robert Campbell</p>
<p>Change History</p>
<ul>
<li>Added alt param and removed the &lt;/img&gt;</li>
<li>Added default width, height and alt <small>(contributed by Walter Wlodarski)</small></li>
</ul>
EOS;
}
/*
D function smarty_cms_help_function_image()
{
	echo _ld('tags', 'help_generic', 'This plugin does ...', 'image ...', TODO);
<li>addtext</li>
<li>alt</li>
<li>class</li>
<li>height</li>
<li>src</li>
<li>title</li>
<li>width</li>
}
*/
