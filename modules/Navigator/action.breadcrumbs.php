<?php
/*
Navigator module action: breadcrumbs
Copyright (C) 2013-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\Lone;
use CMSMS\NlsOperations;
use CMSMS\TemplateOperations;
use Navigator\Utils;
use function CMSMS\log_error;

//if( some worthy test fails ) exit;

debug_buffer('Start Navigator breadcrumbs action');

if( !empty($params['template']) ) {
    $tplname = trim($params['template']); //TODO = sanitizeVal(trim($params['template']), CMSSAN_TODO);
    $tpl = $smarty->createTemplate($this->GetTemplateResource($tplname)); //,null,null,$smarty);
    if( !is_object($tpl) ) {
        $msg = "Unrecognized breadcrumbs template '$tplname'";
    }
}
else {
    $tpl = TemplateOperations::get_default_template_by_type('Navigator::breadcrumbs');
    if( is_object($tpl) ) {
        $tplname = $tpl->name;
        $tpl = $smarty->createTemplate($this->GetTemplateResource($tplname)); //,null,null,$smarty);
    }
    else {
        $msg = 'No default breadcrumbs template found';
    }
}
if( !is_object($tpl) ) {
    log_error($msg,$this->GetName().'::breadcrumbs');
    $this->ShowErrorPage($msg);
    debug_buffer('Finished Navigator breadcrumbs action');
    return;
}

$cur_content_id = $gCms->get_content_id();
if( !$cur_content_id ) { // no current page?
    $this->ShowErrorPage('No current page');
    debug_buffer('Finished Navigator breadcrumbs action');
    return;
}
$ptops = $gCms->GetHierarchyManager();
$tmp = $ptops->props[$cur_content_id] ?? null;
if( !$tmp ) { // no endpoint?
    $this->ShowErrorPage('Internal error: no pages-hierarchy data for: '.$cur_content_id);
    debug_buffer('Finished Navigator breadcrumbs action');
    return;
}

$rtl = (NlsOperations::get_language_direction() == 'rtl');

if( !empty($params['start_text']) ) { $starttext = trim($params['start_text']); }
else { $starttext = $this->Lang('youarehere'); }

//$params['loadprops'], if any, is ignored

if( isset($params['show_all']) ) { $showall = cms_to_bool($params['show_all']); }
else { $showall = FALSE; }

//TODO ensure $stopat < 0 works as per usage doc
if( !empty($params['root']) ) { $stopat = trim($params['root']); }
else { $stopat = Navigator::__DFLT_PAGE; }

$asnodes = $this->TemplateNodes($params,$tplname);
$asnodes = FALSE; //DEBUG
$nodelist = [];
$idslist = [];
$have_stopnode = FALSE;
$pid = $cur_content_id;
while( $pid > 0 ) {
    $tmp = Utils::fill_context($pid,1,$showall,TRUE);
    if( $tmp ) {
        $nodelist += $tmp;
        array_unshift($idslist,$pid);
    }
//    else {
//TODO ok to ignore unwanted-status of the node?
//    }
    // $stopat may be integer, and if so, maybe < 0
    if( $ptops->props[$cur_content_id]['alias'] == $stopat || (is_numeric($stopat) && $pid == (int)$stopat) ) {
        $have_stopnode = TRUE;
        break;
    }
    $pid = $ptops->props[$cur_content_id]['parent'];
}

// maybe add in the 'default page'
//TODO ensure $stopat < 0 works per usage doc
if( !$have_stopnode && $stopat == Navigator::__DFLT_PAGE ) {
    // prepend the home-page node if not already in there
    $pid = Lone::get('ContentOperations')->GetDefaultContent();
    if( $pid && !isset($nodelist[$pid]) ) {
        $tmp = Utils::fill_context($pid,1,$showall,TRUE);
        if( $tmp ) {
            $nodelist += $tmp;
            array_unshift($idslist,$pid);
        }
    }
}

if( $asnodes ) {
    $tmp = [];
    foreach( $idslist as $pid ) {
        $tmp[] = $nodelist[$pid];
    }
    $nodelist = $tmp;
}
else {
    $nodelist[-1] = (object)['id'=>-1,'children'=>$idslist];
}
$tpl->assign('starttext',$starttext)
  ->assign('rtl',$rtl)
  ->assign('nodelist',$nodelist)
  ->display();
unset($tpl); // garbage-collector assistance

debug_buffer('Finished Navigator breadcrumbs action');
