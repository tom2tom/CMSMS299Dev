<?php

//these are extra smarty-params for a 'full' login form

$params['admin_url'] = $config['admin_url'];

$params['encoding'] = CmsNlsOperations::get_encoding();
$lang = CmsNlsOperations::get_current_language();
if (($p = strpos($lang,'_')) !== false) {
    $lang = substr($lang,0,$p);
}
$params['lang_code'] = $lang;
$params['lang_dir'] = CmsNlsOperations::get_language_direction();

//optional, or theme-specific ?

$sitelogo = cms_siteprefs::get('sitelogo');
if ($sitelogo) {
    if (!preg_match('~^\w*:?//~',$sitelogo)) {
        $sitelogo = CMS_ROOT_URL.'/'.trim($sitelogo, ' /');
    }
}
$params['sitelogo'] = $sitelogo;
