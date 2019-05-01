<?php
/*
CoreAdminLogin module action: login
Copyright (C) 2018-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
BUT WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\NlsOperations;

//TODO security checks

$this->StageLogin(); //generate core-form for display

$id = 'm1_';
$url = $this->create_url($id, 'login'); //back to self

$params = ['actionid' => $id];
$params['loginurl'] = $url.'&amp;'.CMS_JOB_KEY.'=1';
$params['forgoturl'] = $url.'&amp;'.$id.'forgotpw=1&amp;'.CMS_JOB_KEY.'=1';
$params['admin_url'] = $config['admin_url'];
$params['encoding'] = NlsOperations::get_encoding();

$lang = NlsOperations::get_current_language();
if (($p = strpos($lang,'_')) !== false) {
    $lang = substr($lang,0,$p);
}
$params['lang_code'] = $lang;
$params['lang_dir'] = NlsOperations::get_language_direction();

$sitelogo = cms_siteprefs::get('sitelogo');
if ($sitelogo) {
    if (!preg_match('~^\w*:?//~',$sitelogo)) {
        $sitelogo = $config['image_uploads_url'].'/'.trim($sitelogo, ' /');
    }
}
$params['sitelogo'] = $sitelogo;

$baseurl = $this->GetModuleURLPath();

$out = <<<EOS
<link rel="stylesheet" type="text/css" href="$baseurl/css/module.css" />

EOS;

$tpl = '<script type="text/javascript" src="%s"></script>'."\n";
// scripts: jquery, jquery-ui
$scripts = cms_installed_jquery(true, false, true, false);
$url = cms_path_to_url($scripts['jqcore']);
$out .= sprintf($tpl,$url);
$url = cms_path_to_url($$scripts['jqui']);
$out .= sprintf($tpl,$url);
$url = $baseurl.'/lib/js/login.js';
$out .= sprintf($tpl,$url);

$params['header_includes'] = $out; //NOT into bottom (to avoid UI-flash)

$smarty->assign($params);

$saved = $smarty->template_dir;
$smarty->template_dir = __DIR__ . DIRECTORY_SEPARATOR . 'templates';
$smarty->display('login.tpl');
$smarty->template_dir = $saved;

exit;

