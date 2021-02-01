<?php
/*
Function to get includable jquery-related style and/or scripts
Copyright (C) 2019-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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
use CMSMS\ScriptsMerger;

function smarty_function_get_jquery($params, $template)
{
	$core = cms_to_bool($params['core'] ?? true);
	$migrate = cms_to_bool($params['migrate'] ?? false);
	$ui = cms_to_bool($params['ui'] ?? true);
	$uicss = $ui || cms_to_bool($params['uicss'] ?? false);

	$incs = cms_installed_jquery($core, $migrate, $ui, $uicss);

	if ($uicss) {
		$url = cms_path_to_url($incs['jquicss']);
		$out = <<<EOS
<link rel="stylesheet" type="text/css" href="{$url}" />

EOS;
	} else {
		$out = '';
	}

	$jsm = new ScriptsMerger();
	if ($core) $jsm->queue_file($incs['jqcore'], 1);
	if ($migrate) $jsm->queue_file($incs['jqmigrate'], 1);
	if ($ui) $jsm->queue_file($incs['jqui'], 1);
	$out .= $jsm->page_content('', false, false);
	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $out);
		return '';
	}
	return $out;
}

function smarty_cms_help_function_get_jquery()
{
	echo lang_by_realm('tags', 'help_function_get_jquery');
}

function smarty_cms_about_function_get_jquery()
{
	echo <<<'EOS'
<p>Version: 1.0</p>
<p>Change History:<br />
None
</p>
EOS;
}
