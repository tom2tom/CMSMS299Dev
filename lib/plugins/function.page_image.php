<?php
/*
Plugin to retrieve an uploaded thumbnail- or image-object associated with the current page
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
use function CMSMS\sanitizeVal;

function smarty_function_page_image($params, $template)
{
	$thumbnail = cms_to_bool($params['thumbnail'] ?? false);
	$tag = cms_to_bool($params['tag'] ?? false);
	$full = ( $tag ) ? true : cms_to_bool($params['full'] ?? false);
//	$assign = trim($params['assign'] ?? '');
	unset($params['full'], $params['thumbnail'], $params['tag'], $params['assign']);

	$val = null;
	$contentobj = SingleItem::App()->get_content_object();
	if( is_object($contentobj) ) {
		$propname = ( $thumbnail ) ? 'thumbnail' : 'image';
		$val = $contentobj->GetPropertyValue($propname);
		if( $val == -1 ) $val = null;
	}

	$out = '';
	if( $val ) {
		$orig_val = $val;
		$config = SingleItem::Config();
		if( $full ) $val = $config['image_uploads_url'].'/'.$val;
		if( !$tag ) {
			$out = $val;
		}
		else {
			if( !isset($params['alt']) ) $params['alt'] = $orig_val;
			// build a tag.
			$out = "<img src=\"$val\"";
			foreach( $params as $key => $val ) {
				$key = trim($key);
				if( !$key ) continue;
				$val = sanitizeVal($val, CMSSAN_PUNCT);
				$out .= " $key=\"$val\"";
			}
			$out .= ' />';
		}
	}

	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $out);
		return '';
	}
	return $out;
}

function smarty_cms_about_function_page_image()
{
	echo <<<'EOS'
<p>Author: Ted Kulp &lt;ted@cmsmadesimple.org&gt;</p>
<p>Change History:</p>
<ul>
<li>Fix for CMSMS 1.9</li>
<li>Jan 2016 <em>(Robert Campbell)</em> - Add the full param for CMSMS 2.2</li>
</ul>
EOS;
}

function smarty_cms_help_function_page_image()
{
	echo _ld('tags', 'help_generic',
	'This plugin retrieves an uploaded thumbnail- or image-object associated with the current page',
	'page_image ...',
	<<<'EOS'
<li>thumbnail: optional boolean get the thumbnail image</li>
<li>tag: optional boolean if false, get just the URL</li>
<li>full: </li>
<li>others suitable for image-element attributes</li>
EOS
	);
}
