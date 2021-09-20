<?php
/*
Deprecated plugin to ...
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

function smarty_function_breadcrumbs($params, $template)
{
	// put mention into the admin log
	cms_error('', '{breadcrumbs} plugin', 'is non-functional');

	return <<<'EOT'
<span style="font-weight:bold;color:#f00;">
ERROR:<br />
The &#123breadcrumbs&#125 plugin is non-functional.<br />
</span>
EOT;
}
/*
function smarty_cms_about_function_breadcrumbs()
{
	echo _ld('tags', 'about_generic'[2], 'htmlintro', <<<'EOS'
<li>detail</li> ... OR lang('none')
EOS
	);
}
*/

function smarty_cms_help_function_breadcrumbs()
{
	echo <<<'EOT'
The {breadcrumbs} plugin is non-functional, and will be removed from CMSMS.
EOT;
}
