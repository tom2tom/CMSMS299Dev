<?php
# DesignManager module action: delete design
# Copyright (C) 2012-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

//use CMSMS\StylesheetOperations;
//use CMSMS\TemplateOperations;
use DesignManager\Design;

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Manage Designs') ) exit;

$this->SetCurrentTab('designs');
if( isset($params['cancel']) ) {
    $this->SetInfo($this->Lang('msg_cancelled'));
    $this->RedirectToAdminTab();
}

try {
    if( !isset($params['design']) ) {
        throw new CmsException($this->Lang('error_missingparam'));
    }
    $design = Design::load($params['design']);

    $can_delete_stylesheets = $this->CheckPermission('Manage Stylesheets');
    $can_delete_templates = $this->CheckPermission('Modify Templates');

    if( isset($params['submit']) ) {
        if( !isset($params['confirm_delete1']) || $params['confirm_delete1'] != 'yes' ||
            !isset($params['confirm_delete2']) || $params['confirm_delete2'] != 'yes') {
            $this->SetError($this->Lang('error_notconfirmed'));
            $this->RedirectToAdminTab();
        }
/*
        if( isset($params['delete_stylesheets']) && $can_delete_stylesheets ) {
            $css_id_list = $design->get_stylesheets();
            if( $css_id_list ) {
                // get the designs that are attached to these stylesheets
                $css_list = StylesheetOperations::get_bulk_stylesheets($css_id_list);
                if( $css_list ) {
                    foreach( $css_list as &$css ) {
                        $x = $css->get_designs(); DISABLED
                        if( is_array($x) && count($x) == 1 && $x[0] == $design->get_id() ) {
                            // its orphaned
                            $css->delete();
                        }
                    }
                }
            }
        }

        if( isset($params['delete_templates']) && $can_delete_templates ) {
            $tpl_id_list = $design->get_templates();
            if( $tpl_id_list ) {
				$templates = TemplateOperations::get_bulk_templates($tpl_id_list);
				if( $templates ) {
					foreach( $templates as &$tpl ) {
						$x = $tpl->get_designs(); DISABLED
						if( is_array($x) && count($x) == 1 && $x[0] == $design->get_id() ) {
							// its orphaned
							$tpl->delete();
						}
					}
				}
            }
        }
*/
        // done... we 'force' the delete because we loaded the design object
		// before deleting the templates and stylesheets.
        $design->delete(TRUE);
        $this->SetMessage($this->Lang('msg_design_deleted'));
        $this->RedirectToAdminTab();
    }

    $tpl = $smarty->createTemplate($this->GetTemplateResource('admin_delete_design.tpl'),null,null,$smarty);
    $tpl->assign('tpl_permission',$can_delete_templates)
     ->assign('css_permission',$can_delete_stylesheets)
     ->assign('design',$design);

    $tpl->display();
    return '';
}
catch( CmsException $e ) {
    $this->SetError($e->GetMessage());
    $this->RedirectToAdminTab();
}
