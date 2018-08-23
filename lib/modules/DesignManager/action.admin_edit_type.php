<?php
# DesignManager module action: edit template-type
# Copyright (C) 2012-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
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

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Templates') ) return;

$this->SetCurrentTab('types');

if( isset($params['cancel']) ) {
  $this->SetInfo($this->Lang('msg_cancelled'));
  $this->RedirectToAdminTab();
}

if( !isset($params['type']) ) {
  $this->SetError($this->Lang('error_missingparam'));
  $this->RedirectToAdminTab();
}

try {
  $type = CmsLayoutTemplateType::load($params['type']);

  if( isset($params['reset']) ) {
      $type->reset_content_to_factory();
      $type->save();
  }
  else if( isset($params['submit']) ) {
    if( isset($params['dflt_contents']) ) {
      $type->set_dflt_contents($params['dflt_contents']);
    }
    $type->set_description($params['description']);
    $type->save();

    $this->SetMessage($this->Lang('msg_type_saved'));
    $this->RedirectToAdminTab();
  }

  $content = get_editor_script(['edit'=>true, 'htmlid'=>$id.'dflt_contents', 'typer'=>'smarty']);
  if (!empty($content['head'])) {
    $this->AdminHeaderContent($content['head']);
  }
  $js = $content['foot'] ?? '';

  $s = json_encode($this->Lang('confirm_reset_type'));
  $js .= <<<EOS
<script type="text/javascript">
//<![CDATA[
$(document).ready(function() {
 $('[name={$id}reset]').on('click', function(ev) {
  var self = this;
  cms_confirm($s).done(function() {
   $(self).closest('form').submit();
  });
  return false;
 });
});
//]]>
</script>

EOS;
  $this->AdminBottomContent($js);

  $tpl = $smarty->createTemplate($this->GetTemplateResource('admin_edit_type.tpl'),null,null,$smarty);
  $tpl->assign('type',$type);
  $tpl->display();
}
catch( CmsException $e ) {
  $this->SetError($e->GetMessage());
  $this->RedirectToAdminTab();
}
