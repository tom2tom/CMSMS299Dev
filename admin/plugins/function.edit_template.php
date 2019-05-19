<?php
#Plugin to generate html and js for a syntax highlight textarea to edit a template
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

use CMSMS\TemplateOperations;

function smarty_function_edit_template($params, $template)
{
	if (empty($params['template']) || (int)$params['template'] < 0) {
		$params['value'] = '';
	} else {
		try {
			$tplobj = TemplateOperations::get_template($params['template']);
			$params['value'] = $tplobj->get_content();
		} catch (Throwable $t) {
			//TODO nice handler
			return $t->getMessage();
		}
	}
	$params['typer'] = 'smarty';
	require_once __DIR__.DIRECTORY_SEPARATOR.'function.syntax_area.php';
	return smarty_function_syntax_area($params, $template);
}

function smarty_cms_help_function_edit_template()
{
	echo <<<'EOS'
<h3>What does it do?</h3>
Generates html and js for a syntax-highlight textarea to edit a template.
<h4>Parameters:</h4>
template (identifier)<br />
<ul>
<li>not provided: new template</li>
<li>number < 1: new template</li>
<li>number > 0: template id</li>
<li>non-numeric string: template name</li>
</ul>
<br />
Other parameters as for the syntax_area plugin.
EOS;
}

function smarty_cms_about_function_edit_template()
{
	echo <<<'EOS'
<p>Initial release May 2019</p>
<p>Change History:<br />
<ul>
</ul>
</p>
EOS;
}
