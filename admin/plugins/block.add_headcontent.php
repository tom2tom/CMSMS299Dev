<?php
#Plugin to inject supplied content into the (admin) page header
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

function smarty_block_add_headcontent($params, $content, $template, &$repeat)
{
    $repeat = false;
	if( !($content) ) return;

	$obj = cms_utils::get_theme_object();
	if( $obj ) $obj->add_headtext( $content );
}

function smarty_cms_help_block_add_headcontent()
{
	echo <<<'EOS'
<h3>What does it do?</h3>
Supports out-of-order processing, by appending content to the page header.
<h3>How is it used?</h3>
Put this in a template<br />
<code>
{add_headcontent}
e.g.
<script> .. </script>
<link .. >
etc
{/add_headcontent}
</code>
EOS;
}

function smarty_cms_about_block_add_headcontent()
{
	echo <<<'EOS'
<p>Initial release May 2019</p>
<p>Change History:<br />
<ul>
</ul>
</p>
EOS;
}
