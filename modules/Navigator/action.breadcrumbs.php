<?php
/*
Navigator module action: breadcrumbs
Copyright (C) 2013-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\SingleItem;
use CMSMS\TemplateOperations;
use Navigator\Utils;
use function CMSMS\log_error;

//if( some worthy test fails ) exit;

debug_buffer('Start Navigator breadcrumbs action');

if( !empty($params['template']) ) {
    $template = trim($params['template']);
}
else {
    $tpl = TemplateOperations::get_default_template_by_type('Navigator::breadcrumbs');
    if( !is_object($tpl) ) {
        log_error('No default breadcrumbs template found',$this->GetName().'::breadcrumbs');
        $this->ShowErrorPage('No default breadcrumbs template found');
        return;
    }
    $template = $tpl->get_name();
}

//
// initialization
//
$content_obj = $gCms->get_content_object();
if( !$content_obj ) {
    $this->ShowErrorPage('No current page');
    return; // no current page?
}
$thispageid = $content_obj->Id();
if( !$thispageid ) {
    $this->ShowErrorPage('No current page');
    return; // no current page?
}
$hm = $gCms->GetHierarchyManager();
$endNode = $hm->find_by_tag('id',$thispageid);
if( !$endNode ) {
    $this->ShowErrorPage('No current page');
    return; // no current page?
}
$starttext = $this->Lang('youarehere');
if( isset($params['start_text']) ) $starttext = trim($params['start_text']);

$deep = 1;
$stopat = Navigator::__DFLT_PAGE;
$showall = 0;
if( isset($params['loadprops']) && $params['loadprops'] = 0 ) $deep = 0;
if( isset($params['show_all']) && $params['show_all'] ) $showall = 1;
if( isset($params['root']) ) $stopat = trim($params['root']);

$pagestack = [];
$curNode = $endNode;
$have_stopnode = FALSE;

while( is_object($curNode) && $curNode->get_tag('id') > 0 ) {
    $content = $curNode->getContent($deep,TRUE,TRUE);
    if( !$content ) {
        $curNode = $curNode->get_parent();
        break;
    }

    if( $content->Active() && ($showall || $content->ShowInMenu()) ) {
        $pagestack[$content->Id()] = Utils::fill_node($curNode,$deep,-1,$showall);
    }
    if( $content->Alias() == $stopat || $content->Id() == (int) $stopat ) {
        $have_stopnode = TRUE;
        break;
    }
    $curNode = $curNode->get_parent();
}

// add in the 'default page'
if( !$have_stopnode && $stopat == Navigator::__DFLT_PAGE ) {
    // get the 'home' page and push it on the list
    $dflt_content_id = SingleItem::ContentOperations()->GetDefaultContent();
    $node = $hm->find_by_tag('id',$dflt_content_id);
    $pagestack[$dflt_content_id] = Utils::fill_node($node,$deep,0,$showall);
}

$tpl = $smarty->createTemplate($this->GetTemplateResource($template)); //,null,null,$smarty);
$tpl->assign('starttext',$starttext)
 ->assign('nodelist',array_reverse($pagestack))
 ->display();
unset($tpl);

debug_buffer('Finished Navigator breadcrumbs action');
