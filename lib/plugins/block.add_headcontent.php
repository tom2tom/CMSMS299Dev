<?php
/*
Plugin to accumulate supplied content for later insertion into the page header
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

function smarty_block_add_headcontent($params, $content, $template, &$repeat)
{
	$repeat = false;
	if( $content ) add_page_headtext($content); // no sanitization
}

function smarty_cms_help_block_add_headcontent()
{
	echo '<h3>What does it do?</h3>
Supports out-of-order processing, by appending content to the page header.
<h3>How is it used?</h3>
Put this in a template<br />
<code>
{add_headcontent}
e.g.
<script type="text/javascript"> .. </script>
<link .. >
etc
{/add_headcontent}
</code>';
}

function smarty_cms_about_block_add_headcontent()
{
	$n = _la('none');
	echo _ld('tags', 'about_generic', 'May 2019', "<li>$n</li>");
}