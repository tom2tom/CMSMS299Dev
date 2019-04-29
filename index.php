<?php
#Entry point for all non-admin pages
#Copyright (C) 2004-2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

/**
 * Entry point for all non-admin pages
 *
 * @package CMS
 */

use CMSMS\Events;
use CMSMS\HookManager;
use CMSMS\internal\content_plugins;
use CMSMS\NlsOperations;
use CMSMS\PageLoader;

$starttime = microtime();
$orig_memory = (function_exists('memory_get_usage')?memory_get_usage():0);

if (isset($_REQUEST['cmsjobtype'])) {
	$type = (int)$_REQUEST['cmsjobtype'];
	$CMS_JOB_TYPE = min(max($type, 0), 2);
} elseif (isset($_REQUEST['showtemplate']) && $_REQUEST['showtemplate'] == 'false') {
	// undocumented, deprecated, output-suppressor
	$CMS_JOB_TYPE = 1;
} else {
	//normal output
	$CMS_JOB_TYPE = 0;
}

clearstatcache();

if (!isset($_SERVER['REQUEST_URI']) && isset($_SERVER['QUERY_STRING'])) $_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];

require_once __DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

if (!is_writable(TMP_TEMPLATES_C_LOCATION) || !is_writable(TMP_CACHE_LOCATION)) {
	echo '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head><title>Error</title></head><body>
<p>The following directories must be writable by the web server:<br />
tmp/cache<br />
tmp/templates_c</p><br />
<p>Please correct by executing:<br /><em>chmod 777 tmp/cache<br />chmod 777 tmp/templates_c</em><br />or the equivalent for your platform before continuing.</p>
</body></html>';
	exit;
}

if ($CMS_JOB_TYPE == 0) {
	ob_start();
}

// initial setup
$_app = CmsApp::get_instance(); // internal use only, subject to change.
$config = cms_config::get_instance();
$params = array_merge($_GET, $_POST);
if ($CMS_JOB_TYPE > 0) {
	$showtemplate = false;
	$_app->disable_template_processing();
} else {
	$showtemplate = true;
}

//TODO which of the following should be $CMS_JOB_TYPE-dependant ?
$smarty = $_app->GetSmarty(); //<2
$page = get_pageid_or_alias_from_url(); //<2
$contentobj = null; //<2

for ($trycount = 0; $trycount < 2; ++$trycount) {
	try {
		if ($trycount == 0) {
			if (is_file(TMP_CACHE_LOCATION.DIRECTORY_SEPARATOR.'SITEDOWN')) throw new CmsError503Exception('Site down for maintenance');
			if (is_sitedown()) throw new CmsError503Exception('Site down for maintenance');
		}

		if ($page == CMS_PREVIEW_PAGEID) {
			// preview
			setup_session(false);
			if (!isset($_SESSION[CMS_PREVIEW]) || !isset($_SESSION[CMS_PREVIEW_TYPE]) ) {
				throw new CmsException('Preview page data not found');
			}
			PageLoader::load_type($_SESSION[CMS_PREVIEW_TYPE]); // load the class so it can be unserialized
			$contentobj = unserialize($_SESSION[CMS_PREVIEW]);
			if (!$contentobj || !($contentobj instanceof CMSContentManager\ContentEditor)) {
				throw new CmsException('Preview page content error');
			}
			unset($_SESSION[CMS_PREVIEW]);
			isset($_SESSION[CMS_PREVIEW_TYPE]);
			$contentobj->SetId(CMS_PREVIEW_PAGEID);
			$contentobj->SetCachable(false);
		} else {
			// $page could be an integer ID or a string alias (or false if some error occurred)
			$contentobj = PageLoader::get_content($page);
			if (!is_object($contentobj)) {
				throw new CmsError404Exception('Page '.$page.' not found');
			}
		}

		// from here in, we're assured to have a content object of some sort

		if (!$contentobj->IsViewable()) {
			$url = $contentobj->GetURL();
			if ($url != '' && $url != '#') {
				redirect($url);
			}
			// not viewable, throw a 404.
			throw new CmsError404Exception('Cannot view an unviewable page');
		}

		// deprecated (since 2.3) secure-page processing
		if ($contentobj->Secure() && !$_app->is_https_request()) {
			// redirect to the secure page
			$url = $contentobj->GetURL(); // CMS_ROOT_URL... i.e. absolute
			if (startswith($url, 'http://')) {
				str_replace('http://', 'https://', $url);
			} elseif (startswith($url, 'ws://')) {
				//TODO generally support the websocket protocol
				str_replace('ws://', 'wss://', $url);
			}
			redirect($url);
		}

		if (!$contentobj->IsPermitted()) {
			throw new CmsError403Exception('Permission denied');
		}

		$uid = get_userid(false);
		if ($page == CMS_PREVIEW_PAGEID || $uid || $_SERVER['REQUEST_METHOD'] != 'GET') {
			$cachable = false;
		}
		else {
			$cachable = $contentobj->Cachable();
		}

		// session stuff is needed from here on.
		setup_session($cachable);
		$_app->set_content_object($contentobj);
		$smarty->assignGlobal('content_obj',$contentobj)
		  ->assignGlobal('content_id', $contentobj->Id())
		  ->assignGlobal('page_id', $page)
		  ->assignGlobal('page_alias', $contentobj->Alias());

		NlsOperations::set_language(); // <- NLS detection for frontend
		$smarty->assignGlobal('lang',NlsOperations::get_current_language())
		  ->assignGlobal('encoding',NlsOperations::get_encoding());

		Events::SendEvent('Core', 'ContentPreRender', [ 'content' => &$contentobj ]);

		if (!$showtemplate || $config['content_processing_mode'] == 2) { //default mode: process content before template(s)
			debug_buffer('preprocess page content');
			content_plugins::get_default_content_block_content($contentobj->Id(), $smarty);
		}

		$html = null;
//		$showtemplate = $_app->template_processing_allowed();
		if ($showtemplate) {
			$tpl_rsrc = $contentobj->TemplateResource();
			if( startswith( $tpl_rsrc, 'cms_template:')/* || startswith( $tpl_rsrc, 'cms_file:')*/ ) {

				debug_buffer('process template top');
				Events::SendEvent('Core', 'PageTopPreRender', [ 'content'=>&$contentobj, 'html'=>&$top ]);
				$tpl = $smarty->createTemplate($tpl_rsrc.';top');
				$top = ''.$tpl->fetch();
				Events::SendEvent('Core', 'PageTopPostRender', [ 'content'=>&$contentobj, 'html'=>&$top ]);

				if( $config['content_processing_mode'] == 1 ) { //process content after template page top
					debug_buffer('preprocess module action');
					content_plugins::get_default_content_block_content($contentobj->Id(), $smarty);
				}

				debug_buffer('process template body');
				Events::SendEvent('Core', 'PageBodyPreRender', [ 'content'=>&$contentobj, 'html'=>&$body ]);
				$tpl = $smarty->createTemplate($tpl_rsrc.';body');
				$body = ''.$tpl->fetch();
				Events::SendEvent('Core', 'PageBodyPostRender', [ 'content'=>&$contentobj, 'html'=>&$body ]);

				debug_buffer('process template head');
				Events::SendEvent('Core', 'PageHeadPreRender', [ 'content'=>&$contentobj, 'html'=>&$head ]);
				$tpl = $smarty->createTemplate($tpl_rsrc.';head');
				$head = ''.$tpl->fetch();
				unset($tpl);
				Events::SendEvent('Core', 'PageHeadPostRender', [ 'content'=>&$contentobj, 'html'=>&$head ]);

				$html = $top.$head.$body;
			}
			elseif( $tpl_rsrc) {
				// not a cms_file or cms_template resource, process it as a whole
				$tpl = $smarty->createTemplate($tpl_rsrc);
				$html = $tpl->fetch();
				unset($tpl);
			}
		} else {
			$html = content_plugins::get_default_content_block_content($contentobj->Id(), $smarty);
		}
		break; // no more iterations
	}

	catch (CmsStopProcessingContentException $e) {
		// we do not display an error message.
		// this can be useful for caching siutations or in certain situations
		// where we only want to gather limited output
		break;
	}

	catch (CmsError404Exception $e) {
		// Catch CMSMS 404 error
		// 404 error thrown... gotta do this process all over again
		$page = 'error404';
		unset($_REQUEST['mact']);
		unset($_REQUEST['module']);
		unset($_REQUEST['action']);
		$handlers = ob_list_handlers();
		for ($cnt = 0, $n = count($handlers); $cnt < $n; ++$cnt) { ob_end_clean(); }

		// specified page not found, load the 404 error page
		$contentobj = PageLoader::get_content($page);
		if ($showtemplate && is_object($contentobj)) {
			// we have a 404 error page
			header('HTTP/1.0 404 Not Found');
			header('Status: 404 Not Found');
		} else {
			// no 404 error page
			header('HTTP/1.0 404 Not Found');
			header('Status: 404 Not Found');
			echo '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head><title>404 Not Found</title></head><body>
<h1>Not Found</h1>
<p>The requested URL was not found on this server.</p>
</body></html>';
			exit;
		}
	}

	catch (CmsError403Exception $e) // <- Catch CMSMS 403 error
	{
		$page = 'error403';
		unset($_REQUEST['mact']);
		unset($_REQUEST['module']);
		unset($_REQUEST['action']);
		$handlers = ob_list_handlers();
		for ($cnt = 0, $n = count($handlers); $cnt < $n; ++$cnt) { ob_end_clean(); }

		// specified page not found, load the 404 error page.
		$contentobj = PageLoader::get_content($page);
		$msg = $e->GetMessage();
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Cache-Control: post-check=0, pre-check=0', false);
		header('HTTP/1.0 403 Forbidden');
		header('Status: 403 Forbidden');
		if ($showtemplate && is_object($contentobj)) {
			// we have a 403 error page.
		} else {
			if (!$msg) $msg = '<p>Sorry, you do not have the appropriate permission to view this item.</p>';
			echo '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head><title>403 Forbidden</title></head><body>
<h1>Forbidden</h1>'.$msg.'
</body></html>';
			exit;
		}
	}

	catch (CmsError503Exception $e) { // <- Catch CMSMS 503 error
		$page = 'error503';
		unset($_REQUEST['mact']);
		unset($_REQUEST['module']);
		unset($_REQUEST['action']);
		$handlers = ob_list_handlers();
		for ($cnt = 0, $n = count($handlers); $cnt < $n; ++$cnt) { ob_end_clean(); }

		// specified page not found, load the 404 error page
		$contentobj = PageLoader::get_content($page);
		$msg = $e->GetMessage();
		if (!$msg) $msg = '<p>You do not have permission to view this item.</p>';
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Cache-Control: post-check=0, pre-check=0', false);
		header('HTTP/1.0 503 Temporarily unavailable');
		header('Status: 503 Temporarily unavailable');
		if ($showtemplate && is_object($contentobj)) {
			// we have a 503 error page.
		} else {
			@ob_end_clean();
			echo '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head><title>503 Site down for maintenance</title></head><body>
<h1>Sorry, down for maintenance.  Check back shortly.</h1>'.$msg.'
</body></html>';
			exit;
		}
	}

	catch (Exception $e) {
		// catch other sorts of exceptions
		$handlers = ob_list_handlers();
		for ($cnt = 0, $n = count($handlers); $cnt < $n; ++$cnt) { ob_end_clean(); }
		if (CMS_DEBUG) {
			$keeps = ['file'=>1,'line'=>1,'function'=>1];
			$data = array_map(function($a) use ($keeps)
			{
				return array_intersect_key($a,$keeps);
			}, $e->getTrace());
			debug_display($data, $e->GetMessage().'<br /><br />Backtrace:');
		} else {
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
			header('Cache-Control: no-store, no-cache, must-revalidate');
			header('Cache-Control: post-check=0, pre-check=0', false);
			echo '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head><title>Site Operation Error</title></head><body>
<h1>Site Operation Error</h1>
'.$e->GetMessage().'<br /><br />
Please notify the site administrator.
</body></html>';
		}
		exit;
	}
} // trycount loop

Events::SendEvent('Core', 'ContentPostRender', [ 'content' => &$html ]);
if (!headers_sent()) {
	$ct = $_app->get_content_type();
	header("Content-Type: $ct; charset=" . NlsOperations::get_encoding());
}
echo $html;

if ($CMS_JOB_TYPE == 0) {
	ob_flush();
}

//unset($_SESSION[CMS_PREVIEW]); // if any

$debug = constant('CMS_DEBUG');
if ($debug || isset($config['log_performance_info']) || (isset($config['show_performance_info']) && $showtemplate)) {
	$endtime = microtime();
	$time = microtime_diff($starttime,$endtime);
	$memory = (function_exists('memory_get_usage')?memory_get_usage():0);
	$memory = $memory - $orig_memory;
	$memory_peak = (function_exists('memory_get_peak_usage')?memory_get_peak_usage():'n/a');
	$db = $_app->GetDb();
	$sql_time = round($db->query_time_total,5);
	$sql_queries = $db->query_count;

	if (isset($config['log_performance_info'])) {
		$out = [ time(), $_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD'], $time, $sql_time, $sql_queries, $memory, $memory_peak ];
		$filename = TMP_CACHE_LOCATION.DIRECTORY_SEPARATOR.'performance.log';
		error_log(implode('|',$out)."\n", 3, $filename);
	} else {
		$txt = "Request duration: $time S | Database: $sql_time S for $sql_queries queries | Memory: net $memory, peak $memory_peak";
		echo '<div style="clear:both;"><pre><code>'.$txt.'</code></pre></div>';
	}
}

if ($debug || is_sitedown()) $smarty->clear_compiled_tpl();
if ($debug && !is_sitedown()) {
	$arr = $_app->get_errors();
	foreach ($arr as $error) {
		echo $error;
	}
}

HookManager::do_hook_simple('PostRequest');
exit;
