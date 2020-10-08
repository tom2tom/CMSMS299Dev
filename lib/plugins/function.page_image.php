<?php
#Plugin to...
#Copyright (C) 2004-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.
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

use CMSMS\AppSingle;
use CMSMS\Utils;

function smarty_function_page_image($params, $template)
{
	$get_bool = function(array $params,$key,$dflt) {
		if( !isset($params[$key]) ) return (bool) $dflt;
		if( empty($params[$key]) ) return (bool) $dflt;
		return (bool) cms_to_bool($params[$key]);
	};

	$full = $get_bool($params,'full',false);
	$thumbnail = $get_bool($params,'thumbnail',false);
	$tag = $get_bool($params,'tag',false);
	$assign = trim(get_parameter_value($params,'assign'));
	unset($params['full'], $params['thumbnail'], $params['tag'], $params['assign']);

	$propname = 'image';
	if( $thumbnail ) $propname = 'thumbnail';
	if( $tag ) $full = true;

	$contentobj = Utils::get_current_content();
	$val = null;
	if( is_object($contentobj) ) {
		$val = $contentobj->GetPropertyValue($propname);
		if( $val == -1 ) $val = null;
	}

	$out = null;
	if( $val ) {
		$orig_val = $val;
		$config = AppSingle::Config();
		if( $full ) $val = $config['image_uploads_url'].'/'.$val;
		if( ! $tag ) {
			$out = $val;
		} else {
			if( !isset($params['alt']) ) $params['alt'] = $orig_val;
			// build a tag.
			$out = "<img src=\"$val\"";
			foreach( $params as $key => $val ) {
				$key = trim($key);
				$val = trim($val);
				if( !$key ) continue;
				$out .= " $key=\"$val\"";
			}
			$out .= ' />';
		}
	}

	if( $assign ) {
		$template->assign($assign,$out);
		return;
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
<li>Jan 2016 <em>(calguy1000)</em> - Adds the full param for CMSMS 2.2</li>
</ul>
EOS;
}
