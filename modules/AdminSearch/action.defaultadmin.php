<?php
/*
AdminSearch module action: defaultadmin
Copyright (C) 2012-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use AdminSearch\Tools;
use CMSMS\UserParams;

//if( some worthy test fails ) exit;
if( !$this->CheckPermission('Use Admin Search') ) exit;
//TODO consider slave-specific permissions e.g. 'Manage All Content' or 'Modify Any Page' for the content slave
//via Tools::get_slave_classes() and slaveclass::check_authority(int $userid)

/*
TODO inline css might be bad for content security policy
in which case
$csm = new CMSMS\StylesMerger();
$csm->queue_string($styles);
$out = $csm->page_content();
*/
$styles = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'defaultadmin.css');
$out = <<<EOS
<style>
$styles
</style>

EOS;
add_page_headtext($out, false);

$s1 = addcslashes($this->Lang('warn_clickthru'), "'\n\r");
$s2 = addcslashes($this->Lang('error_search_text'), "'\n\r");
$s3 = addcslashes($this->Lang('error_select_slave'), "'\n\r");
$ajax_url = $this->create_action_url($id,'admin_search',['forjs'=>1, CMS_JOB_KEY=>1]);

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
     cms_confirm_linkclick(this,'$s1');
     return false;
   });
  }
 });
 var mi = c.find('li.section'),
   l = mi.length;
 mi.on('click',function() {
  var s = $(this).children('.section_children'),
    v = s.is(':visible');
  if (l > 1) {
    $('.section_children').hide();
  } else {
    s.hide();
  }
  if (!v) {
    s.show();
  }
 });
}
$(function() {
 $('#filter_all').on('change',function() {
  $('#filter_box .filter_toggle').prop('checked',this.checked);
 });
 $('#searchbtn').on('click', function() {
   var ndl = $('#searchtext').val();
   if(ndl.length < 2) {
     cms_alert('$s2');
     return false;
   }
   var cb = $('#filter_box :checkbox.filter_toggle:checked');
   if(cb.length === 0) {
     cms_alert('$s3');
     return false;
   } else {
     var parms = {};
     parms['{$id}search_text'] = encodeURIComponent(ndl);
     var s = [];
     cb.each(function() {
       s.push(this.value);
     });
     parms['{$id}slaves'] = s.join();
     cb = $('#opts_box').find(':checkbox');
     cb.each(function() {
       var key = '$id' + this.id;
       parms[key] = (this.checked) ? 1 : 0;
     });
     $('#searchresults').html('');
     $.ajax('$ajax_url', {
       method: 'POST',
       data: parms,
       dataType: 'html'
     }).done(function(data) {
         var \$el = $('#searchresults_cont');
         \$el.hide();
         var \$c = \$el.find('#searchresults');
         \$c.html(data);
         process_results(\$c);
         \$el.show();
     }).fail(function(jqXHR, textStatus, errorThrown) {
       cms_notify('error', errorThrown);
     });
   }
 });
});
//]]>
</script>

EOS;
add_page_foottext($out);

$template = $params['template'] ?? 'defaultadmin.tpl';
$tpl = $smarty->createTemplate($this->GetTemplateResource($template)); //,null,null,$smarty);

$userid = get_userid(false);
$tmp = UserParams::get_for_user($userid,$this->GetName().'saved_search');
if( $tmp ) {
    $init = unserialize($tmp,[]);
}
if( empty($init) ) {
    $init = [
     'search_text' => '',
     'slaves' => [],
     'search_descriptions' => false,
     'search_casesensitive' => false,
     'verbatim_search' => false,
     'search_fuzzy'=>false,
     'save_search' => false,
    ];
}
$tpl->assign('saved_search',$init);

$slaves = Tools::get_slave_classes(); //TODO might want $userid
$tpl->assign('slaves',$slaves);

$tpl->display();
