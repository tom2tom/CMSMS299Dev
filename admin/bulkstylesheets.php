<?php
# Bulk delete|export|import stylesheets
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

$CMS_ADMIN_PAGE = 1;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

if (!isset($_REQUEST[CMS_SECURE_PARAM_NAME]) || !isset($_SESSION[CMS_USER_KEY]) || $_REQUEST[CMS_SECURE_PARAM_NAME] != $_SESSION[CMS_USER_KEY]) {
    exit;
}

check_login();
$userid = get_userid();
if( !check_permission($userid,'Manage Stylesheets') ) {
	return;
}
$urlext = '?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
$themeObject = cms_utils::get_theme_object();
if( isset($_REQUEST['cancel']) ) {
	$themeObject->ParkNotice('info',lang_by_realm('layout','msg_cancelled'));
	redirect('liststyles.php'.$urlext);
}
if( isset($_REQUEST['allparms']) ) {
	$_REQUEST = array_merge($_REQUEST,unserialize(base64_decode($_REQUEST['allparms'])));
}
if( !isset($_REQUEST['css_bulk_action']) || !isset($_REQUEST['css_select']) ||
	!is_array($_REQUEST['css_select']) || count($_REQUEST['css_select']) == 0 ) {
	$themeObject->ParkNotice('error',lang_by_realm('layout','error_missingparam'));
	redirect('liststyles.php'.$urlext);
}

cleanArray($_REQUEST);

try {
	$bulk_op = null;
	$stylesheets = StylesheetOperations::get_bulk_stylesheets($_REQUEST['css_select']);
	switch( $_REQUEST['css_bulk_action'] ) {
	case 'delete':
		$bulk_op = 'bulk_action_delete_css';
		if( isset($_REQUEST['submit']) ) {
			if( !isset($_REQUEST['check1']) || !isset($_REQUEST['check2']) ) {
				$themeObject->RecordNotice('error',lang_by_realm('layout','error_notconfirmed'));
			}
			else {
				$stylesheets = StylesheetOperations::get_bulk_stylesheets($_REQUEST['css_select']);
				foreach( $stylesheets as $one ) {
					if( in_array($one->get_id(),$_REQUEST['css_select']) ) {
						$one->delete();
					}
				}

				audit('','Deleted',count($stylesheets).' stylesheets');
				$themeObject->ParkNotice('info',lang_by_realm('layout','msg_bulkop_complete'));
				redirect('liststyles.php'.$urlext);
			}
		}
		break;

/*	case 'export':
		$bulk_op = 'bulk_action_export_css';
		$first_css = $stylesheets[0];
		$outfile = $first_css->get_content_filename();
		$dn = dirname($outfile);
		if( !is_dir($dn) || !is_writable($dn) ) {
			throw new RuntimeException(lang_by_realm('layout','error_assets_writeperm'));
		}
		if( isset($_REQUEST['submit']) ) {
			$n = 0;
			foreach( $stylesheets as $one ) {
				if( in_array($one->get_id(),$_REQUEST['css_select']) ) {
					$outfile = $one->get_content_filename();
					if( !is_file($outfile) ) {
						file_put_contents($outfile,$one->get_content());
						$n++;
					}
				}
			}
			if( $n == 0 ) throw new RuntimeException(lang_by_realm('layout','error_bulkexport_noneprocessed'));

			audit('','Exported',count($stylesheets).' stylesheets');
			$themeObject->ParkNotice('info',lang_by_realm('layout','msg_bulkop_complete'));
			redirect('liststyles.php'.$urlext);
		}
		break;

	case 'import':
		$bulk_op = 'bulk_action_import_css';
		if( isset($_REQUEST['submit']) ) {
			$n=0;
			foreach( $stylesheets as $one ) {
				if( in_array($one->get_id(),$_REQUEST['css_select']) ) {
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
			if( $n == 0 ) throw new RuntimeException(lang_by_realm('layout','error_bulkimport_noneprocessed'));

			audit('','Imported',count($stylesheets).' stylesheets');
			$themeObject->ParkNotice('info',lang_by_realm('layout','msg_bulkop_complete'));
			redirect('liststyles.php'.$urlext);
		}
		break;
*/
	default:
		$themeObject->ParkNotice('error',lang_by_realm('layout','error_missingparam'));
		redirect('liststyles.php'.$urlext);
		break;
	}

	$allparms = base64_encode(serialize(['css_select'=>$_REQUEST['css_select'],'css_bulk_action'=>$_REQUEST['css_bulk_action']]));
	$selfurl = basename(__FILE__);

	$smarty = CmsApp::get_instance()->GetSmarty();
	$smarty->assign('bulk_op',$bulk_op)
	 ->assign('allparms',$allparms)
	 ->assign('templates',$stylesheets)
     ->assign('urlext',$urlext)
	 ->assign('selfurl',$selfurl);

	include_once 'header.php';
	$smarty->display('bulkstylesheets.tpl');
	include_once 'footer.php';
}
catch( Exception $e ) {
	// master exception
	$themeObject->ParkNotice('error',$e->GetMessage());
	redirect('liststyles.php'.$urlext);
}
