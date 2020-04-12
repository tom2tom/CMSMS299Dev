<?php
# CMSContentManager module action: bulk_settemplate
# Copyright (C) 2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\SysDataCache;
use CMSMS\TemplateOperations;

if( !isset($gCms) ) exit;
if( !isset($action) || $action != 'admin_bulk_settemplate' ) exit;

if( isset($params['cancel']) ) {
    $this->SetInfo($this->Lang('msg_cancelled'));
    $this->Redirect($id,'defaultadmin',$returnid);
}
if( !isset($params['bulk_content']) ) {
    $this->SetError($this->Lang('error_missingparam'));
    $this->Redirect($id,'defaultadmin',$returnid);
}

$pagelist = $params['bulk_content'];
$hm = $gCms->GetHierarchyManager();

$showmore = 0;
if( isset($params['showmore']) ) {
    $showmore = (int) $params['showmore'];
    cms_userprefs::set('cgcm_bulk_showmore',$showmore);
}
if( isset($params['submit']) ) {
    if( !isset($params['confirm1']) || !isset($params['confirm2']) ) {
        $this->SetError($this->Lang('error_notconfirmed'));
        $this->Redirect($id,'defaultadmin',$returnid);
    }
    if( !isset($params['template']) ) {
        $this->SetError($this->Lang('error_missingparam'));
        $this->Redirect($id,'defaultadmin',$returnid);
    }

    set_time_limit(9999);
    $user_id = get_userid();
    $i = 0;

    try {
        foreach( $pagelist as $pid ) {
            $content = $this->GetContentEditor($pid);
            if( !is_object($content) ) continue;

            $content->SetTemplateId((int)$params['template']);
            $content->SetLastModifiedBy($user_id);
            $content->Save();
            ++$i;
        }
        if( $i != count($pagelist) ) {
            throw new CmsException('Bulk operation to set template did not adjust all selected pages');
        }
        audit('','Content','Changed template of '.$i.' pages');
        $this->SetMessage($this->Lang('msg_bulk_successful'));
    }
    catch( Throwable $t ) {
        cms_warning('Changing template on multiple pages failed: '.$t->getMessage());
        $this->SetError($t->getMessage());
    }
    $cache = SysDataCache::get_instance();
    $cache->release('content_quicklist');
    $cache->release('content_tree');
    $cache->release('content_flatlist');

    $this->Redirect($id,'defaultadmin',$returnid);
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

$tpl = $smarty->createTemplate($this->GetTemplateResource('admin_bulk_settemplate.tpl'),null,null,$smarty);

$tpl->assign('showmore',cms_userprefs::get('cgcm_bulk_showmore'))
 ->assign('pagelist',$params['bulk_content'])
 ->assign('displaydata',$displaydata);

$dflt_tpl_id = -1;
try {
    $dflt_tpl = TemplateOperations::get_default_template_by_type(CmsLayoutTemplateType::CORE.'::page');
    $dflt_tpl_id = $dflt_tpl->get_id();
}
catch( Throwable $t ) {
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

$js = <<<EOS
<script type="text/javascript">
//<![CDATA[
$(function() {
  $('#showmore_ctl').on('click', function() {
    $(this).closest('form').submit();
  });
});
//]]>
</script>
EOS;
add_page_foottext($js);

$tpl->display();
return false;
