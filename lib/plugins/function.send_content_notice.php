<?php
/*
Plugin to send page-content-related notices
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

use CMSMS\Events;
use CMSMS\PageLoader;

// since 2.99
function smarty_function_send_content_notice($params, $template)
{
	try {
		$obj = PageLoader::LoadContent($params['pageid']);
	} catch (Throwable $t) {
		trigger_error('send_content_notice plugin: '.$t->getMessage());
		return '';
	}
	$originator = $params['originator'] ?? 'Core';
	Events::SendEvent($originator, $params['type'], ['content'=>$obj, 'html'=>&$params['content']]);

	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $params['content']);
	}
	return '';
}

function smarty_cms_about_function_send_content_notice()
{
	echo lang_by_realm('tags', 'about_generic',
	'Initial version (for CMSMS 2.99)',
	lang('none')
	);
}

function smarty_cms_help_function_send_content_notice()
{
	echo lang_by_realm('tags', 'help_generic',
	'This plugin sends page-content-related notices. It is intended for use at intermediate stages during page-template out-of-order processsing',
	'send_content_notice params',
	<<<'EOS'
<li>originator: optional event originator, default 'Core'</li>
<li>type: event type, PageTopPreRender etc</li>
<li>pageid: identifier of the page being processed, numeric id or string alias</li>
<li>content: the current content, a html string, maybe empty</li>
EOS
	);
}
