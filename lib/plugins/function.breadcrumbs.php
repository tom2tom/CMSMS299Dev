<?php
#Plugin to ...
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


function smarty_function_breadcrumbs($params, $template)
{
	// put mention into the admin log
	cms_error('', '&#123breadcrumbs&#125 tag', 'is removed from CMSMS Core. Instead, now use in your HTML template: &#123nav_breadcrumbs&#125 !');

	return '<span style="font-weight: bold; color: #f00;">WARNING:<br />The &#123breadcrumbs&#125 tag is removed from CMSMS Core<br />Instead, now use in your HTML template: &#123nav_breadcrumbs&#125 !</span>';
}

