<?php
#Plugin to send page-content-related notices
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

use CMSMS\ContentOperations;
use CMSMS\Events;

function smarty_function_send_content_notice($params, $template)
{
    $obj = ContentOperations::get_instance()->LoadContentFromId($params['pageid']);
    $originator = $params['originator'] ?? 'Core';
    Events::SendEvent($originator, $params['type'], ['content'=>$obj, 'html'=>&$params['content']]);
    if( !empty($params['assign']) ) {
        $template->assign(trim($params['assign']), $params['content']);
    }
}

function smarty_cms_about_send_content_notice()
{
	echo <<<'EOS'
<p>Change History:</p>
<ul>
<li>Initial version (for CMSMS 2.3)</li>
</ul>
EOS;
}

function smarty_cms_help_send_content_notice()
{
	echo <<<'EOS'
Parameters:<br />
<ul>
<li>originator: event originator, default 'Core'</li>
<li>type: event type, PageTopPreRender etc</li>
<li>pageid: numeric identifier of the page being processed</li>
<li>content: the current content, a html string</li>
<li>assign: optional variable to assign the (possibly modified) content to</li>
</ul>
EOS;
}
