<?php
/*
DesignManager module action: import design
Copyright (C) 2012-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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

use DesignManager\Design;
use DesignManager\reader_factory;

//if( some worthy test fails ) exit;
if( !$this->CheckPermission('Manage Designs') ) exit;

$this->SetCurrentTab('designs');

if( isset($params['cancel']) ) {
  if( $params['cancel'] == $this->Lang('cancel') ) $this->SetInfo($this->Lang('msg_cancelled'));
  $this->RedirectToAdminTab();
}

$step = 1;
if( isset($params['step']) ) $step = (int)$params['step'];

try {
  switch( $step ) {
  case 1:
    try {
      if( isset($params['next1']) ) {
        // check for uploaded file
        $key = $id.'import_xml_file';
        if( !isset($_FILES[$key]) || $_FILES[$key]['name'] == '' ) throw new Exception($this->Lang('error_nofileuploaded'));
        if( $_FILES[$key]['error'] != 0 || $_FILES[$key]['tmp_name'] == '' || $_FILES[$key]['type'] == '') {
            throw new Exception($this->Lang('error_uploading','xml'));
        }
        if( $_FILES[$key]['type'] != 'text/xml' ) throw new Exception($this->Lang('error_upload_filetype',$_FILES[$key]['type']));

        $reader = reader_factory::get_reader($_FILES[$key]['tmp_name']);
        $reader->validate();

        // copy uploaded file to temporary location
        $tmpfile = tempnam(PUBLIC_CACHE_LOCATION,'dm_');
        if( $tmpfile === FALSE ) throw new Exception($this->Lang('error_create_tempfile'));
        @copy($_FILES[$key]['tmp_name'],$tmpfile);

        // redirect back here, to do step 2.
        $this->Redirect($id,'import_design',$returnid,['step'=>2,'tmpfile'=>$tmpfile]);
      }
    }
    catch( Throwable $t ) {
      $this->ShowErrors($t->GetMessage());
    }

    $tpl = $smarty->createTemplate($this->GetTemplateResource('import_design.tpl','','',$smarty));
    $tpl->display();
    break;

  case 2:
    // preview what's going to be imported
    $tpl = $smarty->createTemplate($this->GetTemplateResource('import_design2.tpl','','',$smarty));
    try {
        if( !isset($params['tmpfile']) ) {
            // bad error, redirect to admin tab.
            $this->SetError($this->Lang('error_missingparam'));
            $this->RedirectToAdminTab();
        }
        $tmpfile = trim($params['tmpfile']);
        if( !is_file($tmpfile) ) {
            // bad error, redirect to admin tab.
            $this->SetError($this->Lang('error_filenotfound',$tmpfile));
            $this->RedirectToAdminTab();
        }

        $reader = reader_factory::get_reader($tmpfile);

        if( isset($params['next2']) ) {
            if( !isset($params['check1']) ) {
                $this->ShowErrors($this->Lang('error_notconfirmed'));
            }
            elseif( !isset($params['newname']) || $params['newname'] == '' ) {
                $this->ShowErrors($this->Lang('error_missingparam'));
            }
            else {
                // redirect back here, do do step 3
                $this->Redirect($id,'import_design',$returnid,['step'=>3,'tmpfile'=>$tmpfile,'newname'=>$params['newname'], 'newdescription'=>$params['newdescription']]);
            }
        }

        // suggest a new name for the 'theme'.

        $tpl->assign('tmpfile',$tmpfile)
         ->assign('cms_version',CMS_VERSION);
        $design_info = $reader->get_design_info();
        $tpl->assign('design_info',$design_info)
         ->assign('templates',$reader->get_template_list())
         ->assign('stylesheets',$reader->get_stylesheet_list());
        $newname = Design::suggest_name($design_info['name']);
        $tpl->assign('new_name',$newname);
    }
    catch( Throwable $t ) {
      $this->ShowErrors($t->GetMessage());
    }

    $js = <<<EOS
<script type="text/javascript">
//<![CDATA[
$(function() {
  $('.template_view').on('click', function() {
    var row = $(this).closest('tr');
    cms_dialog($('.template_content',row), {
      width: 'auto',
      close: function(ev, ui) {
        cms_dialog($(this), 'destroy');
      }
    });
    return false;
  });
  $('.stylesheet_view').on('click', function() {
    var row = $(this).closest('tr');
    cms_dialog($('.stylesheet_content',row), {
      width: 'auto',
      close: function(ev, ui) {
        cms_dialog($(this), 'destroy');
      }
    });
    return false;
  });
});
//]]>
</script>
EOS;
    add_page_foottext($js);

    $tpl->display();
    break;

  case 3:
    // do the importing.
    if( !isset($params['tmpfile']) || !isset($params['newname']) ||
        $params['newname'] == '') {
        // bad error, redirect to admin tab.
        throw new Exception($this->Lang('error_missingparam'));
    }
    $tmpfile = trim($params['tmpfile']);
    $newname = trim($params['newname']);
    $newdescription = trim($params['newdescription']);

    if( !is_file($tmpfile) ) {
        // bad error, redirect to admin tab.
        throw new Exception($this->Lang('error_filenotfound',$tmpfile));
    }

    $destdir = $config['uploads_path'].'/designmanager_import';
    $reader = reader_factory::get_reader($tmpfile);
    $reader->set_suggested_name($newname);
    $reader->set_suggested_description($newdescription);
    $reader->import();
    $this->SetMessage($this->Lang('msg_design_imported'));
    $this->RedirectToAdminTab();
    break;

  default:
    break;
  } // switch
}
catch( Throwable $t ) {
  $this->SetError($t->GetMessage());
  $this->RedirectToAdminTab();
}
