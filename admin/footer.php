<?php
#Final shared stage of admin-page display
#Copyright (C) 2004-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\Events;
use CMSMS\HookManager;

// $USE_THEME inherited from parent scope
if (!isset($USE_THEME) || $USE_THEME) {
	// using the theme... output the footer
	$themeObject->do_footer();
}

// $config inherited from parent scope
if ($config['debug']) {
	// echo debug output to stdout
	echo '<div id="DebugFooter">';
	$arr = CmsApp::get_instance()->get_errors();
	foreach ($arr as $error) {
		echo $error;
	}
	echo '</div> <!-- end DebugFooter -->';
}

if (!isset($USE_OUTPUT_BUFFERING) || $USE_OUTPUT_BUFFERING) {
	// pull everything out of the buffer...
	$pagecontent = @ob_get_contents();
	@ob_end_clean();
} else {
	$pagecontent = '';
}

$aout = HookManager::do_hook_accumulate('AdminBottomSetup');
if ($aout) {
	foreach($aout as $bundle) {
		foreach($bundle as $list) {
			$out = is_array($list) ? implode("\n",$list) : $list;
			$themeObject->add_footertext($out."\n");
		}
	}
}

if ($pagecontent) {
	$pagecontent = $themeObject->postprocess($pagecontent);
	// now we have the final form to display
}
echo $pagecontent;

if (!isset($USE_THEME) || $USE_THEME) {
	if ($pagecontent && strpos($pagecontent,'</body') === false ) echo '</body></html>';

	if (isset($config['show_performance_info'])) {
		$db = CmsApp::get_instance()->GetDb();
		$endtime = microtime();
		$memory = (function_exists('memory_get_usage')?memory_get_usage():0);
		$memory_net = 'n/a';
		if (isset($orig_memory)) $memory_net = $memory - $orig_memory;
		$memory_peak = (function_exists('memory_get_peak_usage')?memory_get_peak_usage():0);
		echo '<div style="clear: both;">'.microtime_diff($starttime,$endtime).' / '.($db->query_count??'')." queries / Net Memory: {$memory_net} / End: {$memory} / Peak: {$memory_peak}</div>\n";
	}
}

Events::SendEvent('Core', 'PostRequest');
