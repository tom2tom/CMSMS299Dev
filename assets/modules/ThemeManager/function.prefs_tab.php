<?php
/*
Settings tab populator for CMS Made Simple module: ThemeManager.
Copyright (C) 2005-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Refer to licence and other details at the top of file ThemeManager.module.php
*/

if (!$this->CheckPermission('Modify Site Preferences')) {
    exit;
}

$s1 = json_encode($this->Lang('confirm_reseturl'));
$s2 = json_encode($this->Lang('confirm_settings'));

$js = <<<EOS
<script type="text/javascript">
//<![CDATA[
$(function() {
  $('#reseturl').on('click', function(ev) {
    ev.preventDefault();
    var form = $(this).closest('form');
    cms_confirm($s1).done(function() {
      $('#inp_reset').val(1);
      form.submit();
    });
    return false;
  });
  $('#settings_submit').on('click', function(ev) {
    ev.preventDefault();
    var form = $(this).closest('form');
    cms_confirm($s2).done(function() {
      form.submit();
    });
    return false;
  });
});
//]]>
</script>

EOS;
add_page_foottext($js);

if ($config['develop_mode']) {
    $tpl->assign('develop_mode',1);
}
