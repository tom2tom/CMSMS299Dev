<?php
# AdminSearch module action: defaultadmin
# Copyright (C) 2012-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

$out = <<<EOS
<style type="text/css">
#status_area,#searchresults_cont,#workarea {
 display:none
}
#searchresults {
 max-height:25em;
 overflow:auto;
 cursor:pointer
}
.search_oneresult {
 color:red
}
</style>

EOS;
add_page_headtext($out, false);

$s1 = json_encode($this->Lang('warn_clickthru'));
$s2 = json_encode($this->Lang('error_search_text'));
$s3 = json_encode($this->Lang('error_select_slave'));
$url = $this->create_url($id,'admin_search');
$ajax_url = str_replace('&amp;','&',$url) . '&'.CMS_JOB_KEY.'=1';

/*function _update_status(html) {
  $('#status_area').html(html);
  $('#status_area').show();
}*/
$out = <<<EOS
<script type="text/javascript">
//<![CDATA[
function process_results(c) {
 c.find('.section_children').hide();
 c.find('a').each(function() {
  var \$el = $(this),
      d = \$el.data('events');
  if(d === undefined || d.length === 0) {
   \$el.on('click', function(e) {
     e.preventDefault();
     cms_confirm_linkclick(this,$s1);
     return false;
   });
  }
 });
 c.find('li.section').on('click',function() {
  $('.section_children').hide();
  $(this).children('.section_children').show();
 });
}

$(function() {
 $('#filter_all').on('change',function() {
  $('#filter_box .filter_toggle').prop('checked',this.checked);
 });
 $('#searchbtn').on('click', function() {
   var t = $('#searchtext').val();
   if(t.length < 2) {
     cms_alert($s2);
     return false;
   }
   var cb = $('#filter_box :checkbox.filter_toggle:checked');
   if(cb.length === 0) {
     cms_alert($s3);
     return false;
   } else {
     $('#searchresults').html('');
     var s = [];
     cb.each(function() {
       s.push(this.value);
     });
     var d = $('#filter_box #search_desc:checked').length;
     var u = '$ajax_url' + '&{$id}search_text=' + encodeURIComponent(t) + '&{$id}slaves=' + s.join() + '&{$id}search_descriptions=' + d;
     $.ajax({
      url: u,
      method: 'POST',
      dataType: 'html',
      success: function (data, textStatus, jqXHR) {
       var \$el = $('#searchresults_cont');
       \$el.hide();
	   var \$c = \$el.find('#searchresults');
       \$c.html(data);
       process_results(\$c);
       \$el.show();
      },
      error: function(jqXHR, textStatus) {
       cms_alert(jqXHR.responseText);
      }
     });
   }
 });
});
//]]>
</script>

EOS;
add_page_foottext($out);

$template = get_parameter_value($params,'template','defaultadmin.tpl');
$tpl = $smarty->createTemplate($this->GetTemplateResource($template),null,null,$smarty);

$userid = get_userid(false);
$tmp = cms_userprefs::get_for_user($userid,$this->GetName().'saved_search');
if( $tmp ) {
    $init = unserialize($tmp,[]);
}
else {
    $init = [
     'search_text' => '',
     'slaves' => [],
     'search_descriptions' => FALSE,
    ];
}
$tpl->assign('saved_search',$init);

$slaves = tools::get_slave_classes();
$tpl->assign('slaves',$slaves);

$tpl->display();
return false;
