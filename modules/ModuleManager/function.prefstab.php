<?php
/*
ModuleManager module function: populate preferences tab
Copyright (C) 2008-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

if( !$this->CheckPermission('Modify Site Preferences') ) exit;

$s1 = addcslashes($this->Lang('confirm_reseturl'), "'\n\r");
$s2 = addcslashes($this->Lang('confirm_settings'), "'\n\r");

$js = <<<EOS
<script>
$(function() {
  $('#reseturl').on('click', function(ev) {
    ev.preventDefault();
    var form = $(this).closest('form');
    cms_confirm('$s1').done(function() {
      $('#inp_reset').val(1);
      form.submit();
    });
    return false;
  });
  $('#settings_submit').on('click', function(ev) {
    ev.preventDefault();
    var form = $(this).closest('form');
    cms_confirm('$s2').done(function() {
      form.submit();
    });
    return false;
  });
});
</script>

EOS;
add_page_foottext($js);

if( $config['develop_mode'] ) {
  $tpl->assign('develop_mode',1)
   ->assign('module_repository',$this->GetPreference('module_repository'))
   ->assign('disable_caching',$this->GetPreference('disable_caching',0));
}
$tpl->assign('dl_chunksize',$this->GetPreference('dl_chunksize',256))
 ->assign('latestdepends',$this->GetPreference('latestdepends',1))
 ->assign('allowuninstall',$this->GetPreference('allowuninstall',0));
