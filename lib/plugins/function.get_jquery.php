<?php
#Function to get includable jquery-related style and/or scripts
#Copyright (C) 2019-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

// since 2.3

use CMSMS\ScriptOperations;

function smarty_function_get_jquery($params, $template)
{
	$core = (isset($params['core'])) ? cms_to_bool($params['core']) : true;
	$migrate = (isset($params['migrate'])) ? cms_to_bool($params['migrate']) : false;
	$ui = (isset($params['ui'])) ? cms_to_bool($params['ui']) : true;
	$uicss = $ui || ((isset($params['uicss'])) ? cms_to_bool($params['uicss']) : false);

	$incs = cms_installed_jquery($core, $migrate, $ui, $uicss);

	if ($uicss) {
		$url = cms_path_to_url($incs['jquicss']);
		$out = <<<EOS
<link rel="stylesheet" type="text/css" href="{$url}" />

EOS;
	} else {
		$out = '';
	}

	$sm = new ScriptOperations();
	if ($core) $sm->queue_file($incs['jqcore'], 1);
	if ($migrate) $sm->queue_file($incs['jqmigrate'], 1);
	if ($ui) $sm->queue_file($incs['jqui'], 1);
	$out .= $sm->render_inclusion('', false, false);

	if( isset($params['assign']) ) {
		$template->assign(trim($params['assign']), $out);
		return;
	}
	return $out;
}

function smarty_cms_help_function_get_jquery()
{
	echo lang_by_realm('tags','help_function_get_jquery');
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
