<?php
/*
Plugin to generate page content for a tab-header
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

function smarty_function_tab_header($params, $template)
{
	$name = trim($params['name'] ?? '');
	if( $name !== '' ) {
		$label = trim($params['label'] ?? '');
		if( $label === '' ) $label = $name;

		$active = FALSE;
		if( isset($params['active']) ) {
			$tmp = trim($params['active']);
			if( $tmp == $name ) {
				$active = TRUE;
			}
			else {
				$active = cms_to_bool($tmp);
				//TODO NOT some other tab-name looking like a bool
				//if( !$active && $tmp ) {}
			}
		}

		$out = CMSMS\AdminTabs::set_tab_header($name, $label, $active);
	}
	else {
		$out = ''; // no error-feedback
	}

	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $out);
		return '';
	}
	return $out;
}
/*
function smarty_cms_about_function_tab_header()
{
	echo lang_by_realm('tags', 'about_generic'[2], 'htmlintro', <<<'EOS'
<li>detail</li> ... OR lang('none')
EOS
	);
}
*/
/*
D function smarty_cms_help_function_tab_header()
{
	echo lang_by_realm('tags', 'help_generic',
	'This plugin generates page content for a tab-header',
	'tab_header name=... label=... active=true',
	<<<'EOS'
<li>name: internal identifier of the tab</li>
<li>label: public identifier of the tab</li>
<li>active: optional whether the tab is to be initially displayed</li>
EOS
	);
}
*/
