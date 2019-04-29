<?php
# Bulk delete|import|export templates
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

use CMSMS\TemplateOperations;

$CMS_ADMIN_PAGE = 1;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

if (!isset($_REQUEST[CMS_SECURE_PARAM_NAME]) || !isset($_SESSION[CMS_USER_KEY]) || $_REQUEST[CMS_SECURE_PARAM_NAME] != $_SESSION[CMS_USER_KEY]) {
    exit;
}

check_login();

$urlext = '?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
$userid = get_userid();
$themeObject = cms_utils::get_theme_object();

cleanArray($_REQUEST);

if( isset($_REQUEST['allparms']) ) $_REQUEST = array_merge($_REQUEST,unserialize(base64_decode($_REQUEST['allparms'])));

try {
	if( !isset($_REQUEST['bulk_action']) || !isset($_REQUEST['tpl_select']) ||
		!is_array($_REQUEST['tpl_select']) || count($_REQUEST['tpl_select']) == 0 ) {
		throw new LogicException(lang_by_realm('layout','error_missingparam'));
	}
	if( isset($_REQUEST['cancel']) ) {
		$themeObject->ParkNotice('info',lang_by_realm('layout','msg_cancelled'));
		redirect('listtemplates.php'.$urlext);
	}

	if( !check_permission($userid,'Modify Templates') ) {
		// check if we have ownership/delete permission for these templates
		//TODO consider	include __DIR__.DIRECTORY_SEPARATOR.'method.TemplateQuery.php';
		$my_templates = TemplateOperations::template_query([0=>'u:'.get_userid(),'as_list'=>1]);
		if( !is_array($my_templates) || count($my_templates) == 0 ) {
			throw new RuntimeException(lang_by_realm('layout','error_retrieving_mytemplatelist'));
		}
		$tpl_ids = array_keys($my_templates);

		foreach( $_REQUEST['tpl_select'] as $one ) {
			if( !in_array($one,$tpl_ids) ) throw new RuntimeException(lang_by_realm('layout','error_permission_bulkoperation'));
		}
	}

	$bulk_op = null;
	$templates = TemplateOperations::get_bulk_templates($_REQUEST['tpl_select']);
	switch( $_REQUEST['bulk_action'] ) {
	case 'delete':
		$bulk_op = 'bulk_action_delete';
		if( isset($_REQUEST['submit']) ) {
			if( !isset($_REQUEST['check1']) || !isset($_REQUEST['check2']) ) {
				$themeObject->RecordNotice('error',lang_by_realm('layout','error_notconfirmed'));
			}
			else {
				foreach( $templates as $one ) {
					if( in_array($one->get_id(),$_REQUEST['tpl_select']) ) {
						$one->delete();
					}
				}

				audit('','Deleted',count($templates).' templates');
				$themeObject->ParkNotice('info',lang_by_realm('layout','msg_bulkop_complete'));
				redirect('listtemplates.php'.$urlext);
			}
		}
		break;

/*	case 'export':
		$bulk_op = 'bulk_action_export';
		$first_tpl = $templates[0];
		$outfile = $first_tpl->get_content_filename();
		$dn = dirname($outfile);
		if( !is_dir($dn) || !is_writable($dn) ) {
			throw new RuntimeException(lang_by_realm('layout','error_assets_writeperm'));
		}
		if( isset($_REQUEST['submit']) ) {
			$n = 0;
			foreach( $templates as $one ) {
				if( in_array($one->get_id(),$_REQUEST['tpl_select']) ) {
					$outfile = $one->get_content_filename();
					if( !is_file($outfile) ) {
						file_put_contents($outfile,$one->get_content());
						$n++;
					}
				}
			}
			if( $n == 0 ) throw new RuntimeException(lang_by_realm('layout','error_bulkexport_noneprocessed'));

			audit('','Exported',count($templates).' templates');
			$themeObject->ParkNotice('info',lang_by_realm('layout','msg_bulkop_complete'));
			redirect('listtemplates.php'.$urlext);
		}
		break;

	case 'import':
		$bulk_op = 'bulk_action_import';
		$first_tpl = $templates[0];
		if( isset($_REQUEST['submit']) ) {
			$n = 0;
			foreach( $templates as $one ) {
				if( in_array($one->get_id(),$_REQUEST['tpl_select']) ) {
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
			if( $n == 0 ) {
				throw new RuntimeException(lang_by_realm('layout','error_bulkimport_noneprocessed'));
			}

			audit('','imported',count($templates).' templates');
			$themeObject->ParkNotice('info',lang_by_realm('layout','msg_bulkop_complete'));
			redirect('listtemplates.php'.$urlext);
		}
		break;
*/
	default:
		throw new LogicException(lang_by_realm('layout','error_missingparam'));
	}

	$selfurl = basename(__FILE__);
	$allparms = base64_encode(serialize(['tpl_select'=>$_REQUEST['tpl_select'], 'bulk_action'=>$_REQUEST['bulk_action']]));

	$smarty = CmsApp::get_instance()->GetSmarty();
	$smarty->assign('bulk_op',$bulk_op)
	 ->assign('allparms',$allparms)
	 ->assign('templates',$templates)
     ->assign('urlext',$urlext)
     ->assign('selfurl',$selfurl);

	include_once 'header.php';
	$smarty->display('bulktemplates.tpl');
	include_once 'footer.php';
}
catch( Exception $e ) {
	// master exception
	$themeObject->ParkNotice('error',$e->GetMessage());
	redirect('listtemplates.php'.$urlext);
}
