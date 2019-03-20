<?php
# DesignManager module action: bulk delete|export|import stylesheets
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

use CMSMS\StylesheetOperations;

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Manage Stylesheets') ) return;

if( isset($params['allparms']) ) {
    $params = array_merge($params,unserialize(base64_decode($params['allparms'])));
}

$this->SetCurrentTab('stylesheets');

if( !isset($params['css_bulk_action']) || !isset($params['css_select']) ||
    !is_array($params['css_select']) || count($params['css_select']) == 0 ) {
    $this->SetError($this->Lang('error_missingparam'));
    $this->RedirectToAdminTab();
}
if( isset($params['cancel']) ) {
    $this->SetInfo($this->Lang('msg_cancelled'));
    $this->RedirectToAdminTab();
}

try {
    $bulk_op = null;
    $stylesheets = StylesheetOperations::load_bulk_stylesheets($params['css_select']);
    switch( $params['css_bulk_action'] ) {
    case 'delete':
        $bulk_op = 'bulk_action_delete_css';
        if( isset($params['submit']) ) {
            if( !isset($params['check1']) || !isset($params['check2']) ) {
                $this->ShowErrors($this->Lang('error_notconfirmed'));
            }
            else {
                $stylesheets = StylesheetOperations::load_bulk_stylesheets($params['css_select']);
                foreach( $stylesheets as $one ) {
                    if( in_array($one->get_id(),$params['css_select']) ) {
                        $one->delete();
                    }
                }

                audit('',$this->GetName(),'Deleted '.count($stylesheets).' stylesheets');
                $this->SetMessage($this->Lang('msg_bulkop_complete'));
                $this->RedirectToAdminTab();
            }
        }
        break;

    case 'export':
        $bulk_op = 'bulk_action_export_css';
        $first_css = $stylesheets[0];
        $outfile = $first_css->get_content_filename();
        $dn = dirname($outfile);
        if( !is_dir($dn) || !is_writable($dn) ) {
            throw new RuntimeException($this->Lang('error_assets_writeperm'));
        }
        if( isset($params['submit']) ) {
            $n = 0;
            foreach( $stylesheets as $one ) {
                if( in_array($one->get_id(),$params['css_select']) ) {
                    $outfile = $one->get_content_filename();
                    if( !is_file($outfile) ) {
                        file_put_contents($outfile,$one->get_content());
                        $n++;
                    }
                }
            }
            if( $n == 0 ) throw new RuntimeException($this->Lang('error_bulkexport_noneprocessed'));

            audit('',$this->GetName(),'Exported '.count($stylesheets).' stylesheets');
            $this->SetMessage($this->Lang('msg_bulkop_complete'));
            $this->RedirectToAdminTab();
        }
        break;

    case 'import':
        $bulk_op = 'bulk_action_import_css';
        if( isset($params['submit']) ) {
            $n=0;
            foreach( $stylesheets as $one ) {
                if( in_array($one->get_id(),$params['css_select']) ) {
                    $infile = $one->get_content_filename();
                    if( is_file($infile) && is_readable($infile) && is_writable($infile) ) {
                        $data = file_get_contents($infile);
                        $one->set_content($data);
                        $one->save();
                        unlink($infile);
                        $n++;
                    }
                }
            }
            if( $n == 0 ) throw new RuntimeException($this->Lang('error_bulkimport_noneprocessed'));

            audit('',$this->GetName(),'Imported '.count($stylesheets).' stylesheets');
            $this->SetMessage($this->Lang('msg_bulkop_complete'));
            $this->RedirectToAdminTab();
        }
        break;

    default:
        $this->SetError($this->Lang('error_missingparam'));
        $this->RedirectToAdminTab();
        break;
    }

    $tpl = $smarty->createTemplate($this->GetTemplateResource('admin_bulk_css.tpl'),null,null,$smarty);
    $tpl->assign('bulk_op',$bulk_op);
    $allparms = base64_encode(serialize(['css_select'=>$params['css_select'],'css_bulk_action'=>$params['css_bulk_action']]));
    $tpl->assign('allparms',$allparms)
     ->assign('templates',$stylesheets);

    $tpl->display();
}
catch( Exception $e ) {
    // master exception
    $this->SetError($e->GetMessage());
    $this->RedirectToAdminTab();
}
