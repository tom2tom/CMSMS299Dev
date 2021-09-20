<?php
/*
Final shared stage of admin-page display
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you may redistribute it and/or modify it
under the terms of the GNU General Public License as published by the
Free Software Foundation; either version 2 of that license, or (at your option)
any later version.

CMS Made Simple is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\Events;
use CMSMS\HookOperations;
use CMSMS\SingleItem;
use function CMSMS\get_debug_messages;

// $USE_THEME inherited from parent scope
if (!isset($USE_THEME) || $USE_THEME) {
	// using the theme... output the footer
	$themeObject->do_footer();
}

// $config inherited from parent scope
if ($config['debug']) {
	$arr = get_debug_messages();
	if ($arr) {
		// echo debug output to stdout
		echo '<div id="DebugFooter">';
		foreach ($arr as $msg) {
			echo $msg;
		}
		echo '</div> <!-- end DebugFooter -->';
	}
}

$aout = HookOperations::do_hook_accumulate('AdminBottomSetup');
if ($aout) {
	foreach($aout as $bundle) {
		foreach($bundle as $list) {
			add_page_foottext($list); //record for separate smarty assignment
		}
	}
}

// pull everything out of the buffer...
$pagecontent = @ob_get_clean();

if (!isset($USE_THEME) || $USE_THEME) {
	if ($pagecontent) {
		$pagecontent = $themeObject->fetch_page($pagecontent);
	}
	echo $pagecontent; // mebbe empty!
	if ($pagecontent && strpos($pagecontent,'</body') === false ) {
		echo '</body></html>';
	}
	if (isset($config['show_performance_info'])) {
		$db = SingleItem::Db();
		$endtime = microtime();
		$memory = (function_exists('memory_get_usage')?memory_get_usage():0);
		$memory_net = 'n/a';
		if (isset($orig_memory)) $memory_net = $memory - $orig_memory;
		$memory_peak = (function_exists('memory_get_peak_usage')?memory_get_peak_usage():0);
		echo '<div style="clear: both;">'.microtime_diff($starttime,$endtime).' / '.($db->query_count??'')." queries / Net Memory: {$memory_net} / End: {$memory} / Peak: {$memory_peak}</div>\n";
	}
} else {
	echo $pagecontent; // mebbe empty!
}

Events::SendEvent('Core', 'PostRequest');
