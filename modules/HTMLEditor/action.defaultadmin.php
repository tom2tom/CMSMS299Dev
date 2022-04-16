<?php
/*
HTMLEditor module action: defaultadmin
Copyright (C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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
use HTMLEditor\Profile;

//if( some worthy test fails ) exit;
if(!$this->CheckPermission('Modify Site Preferences') ) exit;

$tpl = $smarty->createTemplate($this->GetTemplateResource('adminpanel.tpl')); //,null,null,$smarty);

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
        $val = trim($params['theme']);
        if ($val) {
            if (0) {
               // TODO is valid name
            }
        } else {
            $val = ''; // no specific theme
        }
        $this->SetPreference('theme',$val);
    }

    $formstart = $this->CreateFormStart($id,'defaultadmin');
    $hash = $this->GetPreference('source_sri'); //like 'hashtype-base64string'
    $url = $this->GetPreference('source_url'); //e.g. 'https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20'
    $theme = $this->GetPreference('theme'); // c.f. App/User Params:: ['wysiwyg_theme']

    $tpl->assign('form_start',$formstart)
     ->assign('info',$this->Lang('info_source'))
     ->assign('warning',null)
     ->assign('source_sri',$hash)
     ->assign('source_url',$url)
     ->assign('theme',$theme);
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
