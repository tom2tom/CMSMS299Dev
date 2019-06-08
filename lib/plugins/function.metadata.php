<?php
#Plugin to retrieve site metadata property
#Copyright (C) 2004-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\HookManager;

function smarty_function_metadata($params, $template)
{
	$gCms = CmsApp::get_instance();
	$config = cms_config::get_instance();
	$content_obj = $gCms->get_content_object();
	$cid = $content_obj->Id();

	$result = '';
	$showbase = true;

	// Show a base tag unless showbase is false in config.php
	// It really can't hinder, only help
	if( isset($config['showbase']))  $showbase = $config['showbase'];

	// But allow a parameter to override it
	if (isset($params['showbase']))	{
		if ($params['showbase'] == 'false')	$showbase = false;
	}

	HookManager::do_hook('metadata_prerender',[ 'content_id'=>$cid,'showbase'=>&$showbase,'html'=>&$result ]); //deprecated since 2.3 TODO BAD no namespace, only valid for 1st handler ...
	Events::SendEvent('Core','MetadataPrerender',[ 'content_id'=>$cid,'showbase'=>&$showbase,'html'=>&$result ]);

	if ($showbase)	{
		$base = CMS_ROOT_URL;
		if( $gCms->is_https_request() ) $base = $config['ssl_url'];
		$result .= "\n<base href=\"".$base."/\" />\n";
	}

	$result .= cms_siteprefs::get('metadata', '');

	if (is_object($content_obj) && $content_obj->Metadata() != '') $result .= "\n" . $content_obj->Metadata();

	if (strpos($result,$template->smarty->left_delimiter) !== false && strpos($result,$template->smarty->right_delimiter) !== false) {
		$result = $template->fetch('string:'.$result);
	}

	HookManager::do_hook('metadata_postrender',[ 'content_id'=>$cid,'html'=>&$result ]); //deprecated since 2.3 TODO BAD no namespace, only valid for 1st handler ...
	Events::SendEvent('Core','MetadataPostrender',[ 'content_id'=>$cid,'html'=>&$result ]);
	if( isset($params['assign']) )	{
		$template->assign(trim($params['assign']),$result);
		return;
	}
	return $result;
}

function smarty_cms_about_function_metadata()
{
	echo <<<'EOS'
<p>Author: Ted Kulp &lt;ted@cmsmadesimple.org&gt;</p>
<p>Change History:</p>
<ul>
<li>None</li>
</ul>
EOS;
}
