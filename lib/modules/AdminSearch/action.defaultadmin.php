<?php
# AdminSearch module action: defaultadmin
# Copyright (C) 2012-2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use AdminSearch\tools;

if( !isset($gCms) ) exit;
if( !$this->VisibleToAdminUser() ) return;

$url = $this->create_url($id,'admin_search');
$ajax_url = str_replace('&amp;','&',$url).'&cmsjobtype=1';
$js_url = $this->GetModuleURLPath().'/lib/js/admin_search_tab.js';

$s1 = json_encode($this->Lang('error_select_slave'));

$template = get_parameter_value( $params, 'template', 'defaultadmin.tpl' );
$tpl = $smarty->createTemplate($this->GetTemplateResource($template),null,null,$smarty);

$userid = get_userid(false);
$tmp = cms_userprefs::get_for_user($userid,$this->GetName().'saved_search');
if( $tmp ) $tpl->assign('saved_search',unserialize($tmp));

$slaves = tools::get_slave_classes();
$tpl->assign('slaves',$slaves);

$out = <<<EOS
<style type="text/css" scoped>
#status_area,#searchresults_cont,#workarea {
 display: none
}
#searchresults {
 max-height: 25em;
 overflow: auto;
 cursor: pointer
}
.search_oneresult {
 color: red
}
</style>

<script type="text/javascript">
//<![CDATA[
 var ajax_url = '$ajax_url';
 $('#searchbtn').on('click', function() {
   var l = $('#filter_box :checkbox.filter_toggle:checked').length;
   if(l === 0) {
     cms_alert($s1);
   } else {
     $('#searchresults').html('');
   }
 });
//]]>
</script>
<script type="text/javascript" src="$js_url"></script>

EOS;
$this->AdminHeaderContent($out);

$tpl->display();

