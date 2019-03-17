<?php
#Plugin which accumulates stylesheet files to be included in a page or template
#Copyright (C) 2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

function smarty_function_cms_queue_css($params, $template)
{
	if( !isset($params['file']) ) return;
	$combiner = CmsApp::get_instance()->GetStylesManager();

	$file = trim($params['file']);
	$priority = $params['priority'] ?? 0;
	$combiner->queue_file($file, $priority); //fails if file not found
}

function smarty_cms_help_function_cms_queue_css()
{
	echo lang_by_realm('tags','help_function_queue_css');
}

function smarty_cms_about_function_cms_queue_css()
{
	echo <<<'EOS'
<p>Author: Robert Campbell &lt;calguy1000@cmsmadesimple.org&gt;</p>
<p>Version: 1.0</p>
<p>
Change History:<br />
None
</p>
EOS;
}

