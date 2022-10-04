<?php
/*
Deprecated plugin to get includable scripts and/or related styles
Deprecated since 3.0, this just hands-over to plugin {get_jquery}
Copyright (C) 2004-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

function smarty_function_cms_jquery($params, $template)
{
	require_once __DIR__.DIRECTORY_SEPARATOR.'function.get_jquery.php';
	return smarty_function_get_jquery($params, $template);
}

function smarty_cms_help_function_cms_jquery()
{
	echo '<p>Deprecated since CMSMS 3.0.</p>
<p>This merely hands-over to plugin <code>{get_jquery}</code>.<br>
Use that plugin instead.</p>';
}