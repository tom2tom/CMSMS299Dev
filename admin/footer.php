<?php
#Final shared stage of admin-page display
#Copyright (C) 2004-2014 Ted Kulp <ted@cmsmadesimple.org>
#Copyright (C) 2015-2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

// $USE_THEME inherited from parent scope
if (!isset($USE_THEME) || $USE_THEME) {
    // using the theme... output the footer
    $themeObject->do_footer();
}

// $config inherited from parent scope
if ($config['debug']) {
    // echo debug output to stdout
    echo '<div id="DebugFooter">';
    $arr = \CmsApp::get_instance()->get_errors();
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

// do any header replacements (this is for WYSIWYG stuff)
// initialize the requested wysiwyg modules
// done here (after content generation) because this can change based on module actions etc
$list = CmsFormUtils::get_requested_wysiwyg_modules();
if (is_array($list) && count($list)) {
    foreach ($list as $module_name => $info) {

        $obj = cms_utils::get_module($module_name);
        if (!is_object($obj)) {
            audit('','Core','WYSIWYG module '.$module_name.' requested, but could not be instantiated');
            continue;
        }

        // parse the list once and get all of the stylesheet names (if any)
        // preload all of the named stylesheets.  to minimize queries.
        $css = null;
        $cssnames = array();
        foreach ($info as $rec) {
            if ($rec['stylesheet'] == '' || $rec['stylesheet'] == CmsFormUtils::NONE) continue;
            $cssnames[] = $rec['stylesheet'];
        }
        $cssnames = array_unique($cssnames);
        if (is_array($cssnames) && count($cssnames)) {
            $css = CmsLayoutStylesheet::load_bulk($cssnames);
        }
        // adjust the cssnames array to only contain the list of the stylesheets we actually found.
        if (is_array($css) && count($css)) {
            $tmpnames = array();
            foreach ($css as $stylesheet) {
                $name = $stylesheet->get_name();
                if (!in_array($name,$tmpnames)) $tmpnames[] = $name;
            }
            $cssnames = $tmpnames;
        }
        else {
            $cssnames = null;
        }

        // initialize each 'specialized' textarea.
        $need_generic = FALSE;
        foreach ($info as $rec) {
            $selector = $rec['id'];
            $cssname = $rec['stylesheet'];

            if ($cssname == CmsFormUtils::NONE) $cssname = null;
            if (!$cssname || !is_array($cssnames) || !in_array($cssname,$cssnames) || $selector == CmsFormUtils::NONE) {
                $need_generic = TRUE;
                continue;
            }

            $selector = 'textarea#'.$selector;
            $themeObject->add_headtext($obj->WYSIWYGGenerateHeader($selector,$cssname));
        }

        // now, do we need a generic iniitialization?
        if ($need_generic) {
            $themeObject->add_headtext($obj->WYSIWYGGenerateHeader());
        }
    }
}

// initialize the requested syntax hilighter modules
$list = CmsFormUtils::get_requested_syntax_modules();
if (is_array($list) && count($list)) {
    foreach ($list as $one) {
        $obj = cms_utils::get_module($one);
        if (is_object($obj)) $themeObject->add_headtext($obj->SyntaxGenerateHeader());
    }
}

$add_list = \CMSMS\HookManager::do_hook('AdminBottomSetup', []);
if ($add_list) {
    $themeObject->add_footertext(implode("\n",$add_list));
}

if ($pagecontent) {
    $pagecontent = $themeObject->postprocess($pagecontent);
    // now we have the final form to display
}
echo $pagecontent;

if (!isset($USE_THEME) || $USE_THEME) {
    if ($pagecontent && strpos($pagecontent,'</body') === false ) echo '</body></html>';

    if (isset($config['show_performance_info'])) {
        $db = \Cmsapp::get_instance()->GetDb();
        $endtime = microtime();
        $memory = (function_exists('memory_get_usage')?memory_get_usage():0);
        $memory_net = 'n/a';
        if (isset($orig_memory)) $memory_net = $memory - $orig_memory;
        $memory_peak = (function_exists('memory_get_peak_usage')?memory_get_peak_usage():0);
        echo '<div style="clear: both;">'.microtime_diff($starttime,$endtime)." / ".($db->query_count??'')." queries / Net Memory: {$memory_net} / End: {$memory} / Peak: {$memory_peak}</div>\n";
    }
}
