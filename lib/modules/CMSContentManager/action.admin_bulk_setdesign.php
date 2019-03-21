<?php
# CMSContentManaager module action: set bulk design
# Copyright (C) 2016-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

use CMSMS\ContentOperations;
use CMSMS\TemplateOperations;

if( !isset($gCms) ) exit;
$this->SetCurrentTab('pages');
if( !$this->CheckPermission('Manage All Content') ) {
$this->SetError($this->Lang('error_bulk_permission'));
$this->RedirectToAdminTab();
}

if( !isset($params['multicontent']) ) {
    $this->SetError($this->Lang('error_missingparam'));
    $this->RedirectToAdminTab();
}

if( isset($params['cancel']) ) {
    $this->SetInfo($this->Lang('msg_cancelled'));
    $this->RedirectToAdminTab();
}

$hm = $gCms->GetHierarchyManager();
$pagelist = unserialize(base64_decode($params['multicontent']));

$showmore = 0;
if( isset($params['showmore']) ) {
    $showmore = (int) $params['showmore'];
    cms_userprefs::set('cgcm_bulk_showmore',$showmore);
}
if( isset($params['submit']) ) {
    if( !isset($params['confirm1']) || !isset($params['confirm2']) ) {
        $this->SetError($this->Lang('error_notconfirmed'));
        $this->RedirectToAdminTab();
    }
    if( !isset($params['design']) || !isset($params['template']) ) {
        $this->SetError($this->Lang('error_missingparam'));
        $this->RedirectToAdminTab();
    }

    // do the real work
    try {
        @set_time_limit(9999);
        ContentOperations::get_instance()->LoadChildren(-1,FALSE,FALSE,$pagelist);

        $i = 0;
        foreach( $pagelist as $pid ) {
            $node = $hm->find_by_tag('id',$pid);
            if( !$node ) continue;
            $content = $node->getContent(FALSE,FALSE,TRUE);
            if( !is_object($content) ) continue;

            $content->SetTemplateId((int)$params['template']);
            $content->SetPropertyValue('design_id',$params['design']);
            $content->SetLastModifiedBy(get_userid());
            $content->Save();
            $i++;
        }
        if( $i != count($pagelist) ) {
            throw new CmsException('Bulk operation to set design did not adjust all selected pages');
        }
        audit('','Content','Changed template and design on '.count($pagelist).' pages');
        $this->SetMessage($this->Lang('msg_bulk_successful'));
        $this->RedirectToAdminTab();
    }
    catch( Exception $e ) {
        cms_warning('Changing design and template on multiple pages failed: '.$e->GetMessage());
        $this->SetError($e->GetMessage());
        $this->RedirectToAdminTab();
    }
}

$displaydata = [];
foreach( $pagelist as $pid ) {
    $node = $hm->find_by_tag('id',$pid);
    if( !$node ) continue;  // this should not happen, but hey.
    $content = $node->getContent(FALSE,FALSE,FALSE);
    if( !is_object($content) ) continue; // this should never happen either

    $rec = [];
    $rec['id'] = $content->Id();
    $rec['name'] = $content->Name();
    $rec['menutext'] = $content->MenuText();
    $rec['owner'] = $content->Owner();
    $rec['alias'] = $content->Alias();
    $displaydata[] = $rec;
}

$tpl = $smarty->createTemplate($this->GetTemplateResource('admin_bulk_setdesign.tpl'),null,null,$smarty);

$tpl->assign('showmore',cms_userprefs::get('cgcm_bulk_showmore'))
 ->assign('multicontent',$params['multicontent'])
 ->assign('displaydata',$displaydata)
 ->assign('alldesigns',CmsLayoutCollection::get_list());
$dflt_design = CmsLayoutCollection::load_default();
$tpl->assign('dflt_design_id',$dflt_design->get_id());

$dflt_tpl_id = -1;
try {
    $dflt_tpl = TemplateOperations::load_default_template_by_type(CmsLayoutTemplateType::CORE.'::page');
    $dflt_tpl_id = $dflt_tpl->get_id();
}
catch( Exception $e ) {
    // ignore
}
$tpl->assign('dflt_tpl_id',$dflt_tpl_id);
if( $showmore ) {
    $_tpl = TemplateOperations::template_query(['as_list'=>1]);
    $tpl->assign('alltemplates',$_tpl);
}
else {
    // gotta get the core page template type
    $_type = CmsLayoutTemplateType::load(CmsLayoutTemplateType::CORE.'::page');
    $_tpl = TemplateOperations::template_query(['t:'.$_type->get_id(),'as_list'=>1]);
    $tpl->assign('alltemplates',$_tpl);
}

$tpl->display();
