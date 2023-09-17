<?php
/*
MicroTiny module action: defaultadmin
Copyright (C) 2009-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\Url;
use MicroTiny\Profile;

//if( some worthy test fails ) exit;
if(!$this->CheckPermission('Modify Site Preferences') ) exit;

//TODO manage 'disable_cache' module-setting

$tpl = $smarty->createTemplate($this->GetTemplateResource('adminpanel.tpl')); //,'','',$smarty);

$pmod = $this->CheckPermission('Modify Site Preferences');
if ($pmod) {
    if (isset($params['apply'])) {
        $url_ob = new Url();
        $val = trim($params['source_url']);
        if ($val) {
            $url = $url_ob->sanitize($val);
            if ($url == $val) {
                $this->SetPreference('source_url',$url);
            } else {
                $this->ShowErrors($this->Lang('error_badparam').' URL '.$val);
            }
        } else {
            $this->ShowErrors($this->Lang('error_missingparam').' URL');
        }
        $val = trim($params['source_sri'],' "\''); // TODO sanitizeVal() empty OR base64 chars plus '-'
        $this->SetPreference('source_sri',$val);
        $val = trim($params['skin_url']);
        if ($val) {
            if (0) { // TODO valid relative url
                //TODO process it
                $url = $val;
            } else {
                $url = $url_ob->sanitize($val);
                if ($url !== $val) {
                    $this->ShowErrors($this->Lang('error_badparam').' URL '.$val);
                }
            }
        } else {
            $url = ''; // no specific skin
        }
        $this->SetPreference('skin_url',$url);
    }

    $formstart = $this->CreateFormStart($id,'defaultadmin');
    $hash = $this->GetPreference('source_sri'); //e.g. 'hashtype-base64string'
    $url = $this->GetPreference('source_url'); //e.g. 'https://cdnjs.cloudflare.com/ajax/libs/tinymce/4.9.11' TODO support preconnection, SRI hash
    $url2 = $this->GetPreference('skin_url'); // c.f. UserParams:: ['wysiwyg_theme']

    $tpl->assign('form_start',$formstart)
     ->assign('info',$this->Lang('info_source'))
     ->assign('warning',null)
     ->assign('source_sri',$hash)
     ->assign('source_url',$url)
     ->assign('skin_url',$url2);
}

try {
    $list = Profile::list_all();
    if( !$list || !is_array($list) ) { throw new Exception('No profiles found'); }
    $profiles = [];
    foreach( $list as $one ) {
        $profiles[] = Profile::load($one);
    }
    $tpl->assign('profiles',$profiles);
}
catch( Throwable $t ) {
    $this->ShowErrors($t->GetMessage());
    $tpl->assign('profiles',null);
}

$tpl->display();
