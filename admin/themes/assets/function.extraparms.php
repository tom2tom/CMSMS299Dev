<?php

use CMSMS\AppParams;
use CMSMS\NlsOperations;

//these are extra smarty-params for a 'full' login form

$tplvars['admin_url'] = $config['admin_url'];

$tplvars['encoding'] = NlsOperations::get_encoding();
$lang = NlsOperations::get_current_language();
if ($lang) {
    $lang = NlsOperations::get_lang_attribute($lang);
} else {
    $lang = '';
}
$tplvars['lang_code'] = $lang;
$tplvars['lang_dir'] = NlsOperations::get_language_direction();

//optional, or theme-specific ?

$sitelogo = AppParams::get('site_logo');
if ($sitelogo) {
    if (!preg_match('~^\w*:?//~',$sitelogo)) {
        $sitelogo = $config['image_uploads_url'].'/'.trim($sitelogo, ' /');
    }
}
$tplvars['sitelogo'] = $sitelogo;
