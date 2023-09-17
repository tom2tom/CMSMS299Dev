<?php
/*
Plugin to redirect to a specified URL
Copyright (C) 2004-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

function smarty_function_redirect_url($params, $template)
{
	if( isset($params['to']) ) redirect(trim($params['to']));
}
/*
function smarty_cms_about_function_redirect_url()
{
	echo _ld('tags', 'about_generic', 'Ted Kulp 2004','<li>'._la('none').'</li>');
}
*/
/*
D function smarty_cms_help_function_redirect_url()
{
	echo _ld('tags', 'help_generic', 'This plugin intitiates redirection to a specified URL', 'redirect_url to=...',
	'<li>to: URL</li>'
	);
}
*/