<?php
/*
Entry point for all non-admin pages
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\AppParams;
use CMSMS\AppState;
use CMSMS\IContentEditor;
use CMSMS\Error403Exception;
use CMSMS\Error404Exception;
use CMSMS\Error503Exception;
use CMSMS\Events;
use CMSMS\internal\content_plugins;
use CMSMS\NlsOperations;
use CMSMS\PageLoader;

/**
 * Entry point for all non-admin pages
 *
 * @package CMS
 */

$starttime = microtime();
$orig_memory = (function_exists('memory_get_usage')?memory_get_usage():0);
clearstatcache();

if (!isset($_SERVER['REQUEST_URI']) && isset($_SERVER['QUERY_STRING'])) {
	$_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
}

require_once __DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_FRONT_PAGE; // in scope for inclusion, sets initial state
require_once __DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

if (!is_writable(TMP_TEMPLATES_C_LOCATION) || !is_writable(TMP_CACHE_LOCATION)) {
	header('HTTP/1.0 500 Internal Server Error');
	header('Status: 500 Internal Server Error');
	echo '<!DOCTYPE html>
<html><head><title>Error</title></head><body>
<p>The web server is prevented from writing to one or more temporary-storage directories.<br />
Please contact the website administrator to request that this problem be corrected.</p>
</body></html>';
	Events::SendEvent('Core', 'PostRequest');
	exit;
}

// further setup (see also include.php)
$params = array_merge($_GET, $_POST);
if (!isset($smarty)) $smarty = $_app->GetSmarty();
$page = get_pageid_or_alias_from_url();
$contentobj = null;
$showtemplate = true;

ob_start();

for ($trycount = 0; $trycount < 2; ++$trycount) {
	try {
		if ($trycount == 0) {
			if (is_file(TMP_CACHE_LOCATION.DIRECTORY_SEPARATOR.'SITEDOWN') ||
				is_sitedown()) throw new CmsError503Exception('.'); // indicate system-default message
		}

		if ($page == CMS_PREVIEW_PAGEID) {
			// preview
			setup_session(false);
			if (!isset($_SESSION[CMS_PREVIEW]) || !isset($_SESSION[CMS_PREVIEW_TYPE]) ) {
				throw new Exception('Preview page data not found');
			}
			PageLoader::LoadContentType($_SESSION[CMS_PREVIEW_TYPE]); // load the class so it can be unserialized
			$contentobj = unserialize($_SESSION[CMS_PREVIEW]);
			if (!$contentobj || !($contentobj instanceof IContentEditor)) {
				throw new Exception('Preview page content error');
			}
			unset($_SESSION[CMS_PREVIEW]);
			isset($_SESSION[CMS_PREVIEW_TYPE]);
			$contentobj->SetId(CMS_PREVIEW_PAGEID);
			$contentobj->SetCachable(false);
		} else {
			// $page could be an integer ID or a string alias (or false if some error occurred)
			$contentobj = PageLoader::LoadContent($page);
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

		// deprecated (since 2.99) secure-page processing
		if ($contentobj->Secure() && !$_app->is_https_request()) {
			// redirect to the secure page
			$url = $contentobj->GetURL(); // CMS_ROOT_URL... i.e. absolute
			if (startswith($url, 'http://')) {
				str_replace('http://', 'https://', $url);
//			} elseif (startswith($url, 'ws://')) {
				//TODO generally support the websocket protocol 'wss' : 'ws'
//				str_replace('ws://', 'wss://', $url);
			}
			redirect($url);
		}

		if (!$contentobj->IsPermitted()) {
			throw new Error403Exception('Permission denied');
		}

		$userid = get_userid(false);
		if ($page == CMS_PREVIEW_PAGEID || $userid || $_SERVER['REQUEST_METHOD'] != 'GET') {
			$cachable = false;
		} else {
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
		$smarty->assignGlobal('lang', NlsOperations::get_current_language())
		  ->assignGlobal('encoding', NlsOperations::get_encoding());

		Events::SendEvent('Core', 'ContentPreRender', [ 'content' => &$contentobj ]);

		$html = null;
		$showtemplate = $_app->template_processing_allowed();
		if ($showtemplate) {
			$tpl_rsrc = $contentobj->TemplateResource();
			if ($tpl_rsrc) {
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
		// this can be useful for caching siutations or in certain
		// situations where we only want to gather limited output
		break;
	}

	catch (Error404Exception $e) {
		// 404 error thrown... maybe gotta do this process all over again
		$page = 'error404';
		unset($_REQUEST['mact'], $_REQUEST['module'], $_REQUEST['action']); //ignore any secure params
		$handlers = ob_list_handlers();
		for ($cnt = 0, $n = count($handlers); $cnt < $n; ++$cnt) { ob_end_clean(); }

		// specified page not found, load the 404 error page, if any
		$contentobj = ($showtemplate) ? PageLoader::LoadContent($page) : null;
		header('HTTP/1.0 404 Not Found');
		header('Status: 404 Not Found');
		if (!$showtemplate || !is_object($contentobj)) {
			// default
			echo '<!DOCTYPE html>
<html><head><title>404 Not Found</title></head><body>
<h1>Not Found</h1>
<p>The requested URL was not found on this server.</p>
</body></html>';
			exit;
		}
	}

	catch (Error403Exception $e) {
		$page = 'error403';
		unset($_REQUEST['mact'], $_REQUEST['module'], $_REQUEST['action']); //ignore any secure params
		$handlers = ob_list_handlers();
		for ($cnt = 0, $n = count($handlers); $cnt < $n; ++$cnt) { ob_end_clean(); }

		$msg = $e->GetMessage();
		if (!$msg) $msg = 'You do not have the appropriate permission to view the requested page.';
		// specified page blocked, load the 403 error page, if any
		$contentobj = ($showtemplate) ? PageLoader::LoadContent($page) : null;
		header('Expires: Wed, 29 Dec 2010 05:00:00 GMT');
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Cache-Control: post-check=0, pre-check=0', false);
		header('HTTP/1.0 403 Forbidden');
		header('Status: 403 Forbidden');
		if (!$showtemplate || !is_object($contentobj)) {
			// default
			echo '<!DOCTYPE html>
<html><head><title>403 Forbidden</title></head><body>
<h1>Forbidden</h1>
<p>'.$msg.'</p>
</body></html>';
			exit;
		}
	}

	catch (Error503Exception $e) {
		$page = 'error503';
		unset($_REQUEST['mact'], $_REQUEST['module'], $_REQUEST['action']); //ignore any secure params
		$handlers = ob_list_handlers();
		for ($cnt = 0, $n = count($handlers); $cnt < $n; ++$cnt) { ob_end_clean(); }

		$msg = $e->GetMessage();
		if ($msg == 'Service unavailable - .') { // default message intended
			$msg = AppParams::get('sitedownmessage');
			if (!$msg ) {
			$msg = <<<'EOS'
Maintenance is being done.<br />
Please check back again shortly.
EOS;
			}
		}
		else {
			$msg .= <<<'EOS'
<br />
Please check back again shortly.
EOS;
		}
		//TODO c.f. AppParams::get('sitedownmessage')
		// specified page not available, load the 503 error page, if any
		$contentobj = ($showtemplate) ? PageLoader::LoadContent($page) : null;
		header('Expires: Wed, 29 Dec 2010 05:00:00 GMT');
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Cache-Control: post-check=0, pre-check=0', false);
		header('HTTP/1.0 503 Temporarily unavailable');
		header('Status: 503 Temporarily unavailable');
//TODO	header('Retry-After: e.g. Wed, 21 Oct 2015 07:28:00 GMT');
		if (!$showtemplate || !is_object($contentobj)) {
			// default
			echo '<!DOCTYPE html>
<html><head><title>503 Site Not Available</title></head><body>
<h1>Site Not Available</h1>
<p>'.$msg.'</p>
</body></html>';
			exit;
		}
	}

	catch (Throwable $t) { // <- Catch other exceptions|errors
		$handlers = ob_list_handlers();
		for ($cnt = 0, $n = count($handlers); $cnt < $n; ++$cnt) { ob_end_clean(); }
		if (CMS_DEBUG) {
			$keeps = ['file'=>1,'line'=>1,'function'=>1];
			$data = array_map(function($a) use ($keeps)
			{
				return array_intersect_key($a,$keeps);
			}, $t->getTrace());
			debug_display($data, $t->GetMessage().'<br /><br />Backtrace:');
		} else {
			$msg = $t->GetMessage();
			if (!$msg) $msg = 'The cause was not reported.';
			header('Expires: Wed, 29 Dec 2010 05:00:00 GMT');
			header('Cache-Control: no-store, no-cache, must-revalidate');
			header('Cache-Control: post-check=0, pre-check=0', false);
			echo '<!DOCTYPE html>
<html><head><title>Site Operation Error</title></head><body>
<h1>Site Operation Error</h1>
<p>'.$msg.'</p><br />
<p>Please notify the site administrator.</p>
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

ob_flush();

//unset($_SESSION[CMS_PREVIEW]); // if any

$debug = constant('CMS_DEBUG');
//$config assigned in 'include.php'
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

Events::SendEvent('Core', 'PostRequest');
exit;
