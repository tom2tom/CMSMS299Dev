<?php
$lang = [
// A
'all' => 'All',

// D
'desc_css_search' => 'Search for matching text in stylesheets',
'desc_content_search' => 'Search for matching text in content pages',
'desc_filter_all' => 'Toggle all filters',
'desc_oldmodtemplate_search' => 'Search old module templates',
'desc_template_search' => 'Search for matching text in templates',

// E
'error_nosearchtext' => 'Please enter a search term',
'error_noslaves' => 'No search capability is installed',
'error_search_text' => 'You must specify search text (at least 2 characters long)',
'error_select_slave' => 'You must select at least one search-scope',

// F
'filter' => 'Search Scope',
'finished' => 'Finished',
'friendlyname' => 'Database Search',

// H
'help' => <<<'EOS'
<h3>What does this do?</h3>
<p>This module provides the ability to quickly find places in templates, content pages, and other database where a text string occurs.  It is particularly useful for finding smarty tags, class names, ids or other bits of HTML code that might be hard to find in a large website.</p>
<p>This module has no frontend interaction it is designed for use by CMSMS site developers or editors to find sub strings of text or code.  Not for use in finding text on the frontend of websites.</p>
<h3>How is it used</h3>
<p>This module is visible to most administrators of the website with at least some permissions to edit templates, stylesheets, or some content   Though the list of what can be searched might be reduced.</p>
<p>The module provides a text field where a single string can be entered (the string is not divided into words or otherwise parsed).  It also provides the ability to only search certain subsections of the website.</p>
<p>Searching will generate a nested, expandable list of sections where matches were found.  Under each section a description of the match is displayed.  Usually with a link that will direct you to a form to edit the item.</p>
<h3>Support</h3>
<p>As per the GPL, this software is provided as-is. Please read the text of the license for the full disclaimer.</p>
<h3>Copyright and License</h3>
<p>Copyright &copy; 2012-2020 CMS Made Simple Foundation <a href="mailto:foundation@cmsmadesimple.org">&lt;foundation@cmsmadesimple.org&gt;</a>. All rights reserved.</p>
<p>This module has been released under the <a href="http://www.gnu.org/licenses/licenses.html#GPL">GNU Public License</a>. The module may not be distributed or used otherwise than in accordance with that license.</p>
EOS
,

// L
'lbl_content_search' => 'Content Pages',
'lbl_css_search' => 'Stylesheets',
'lbl_gcb_search' => 'Global Content Blocks', //deprecated - UDT's?
'lbl_oldmodtemplate_search' => 'Module Templates',
'lbl_search_desc' => 'Descriptions <em>(where applicable)</em>',
'lbl_template_search' => 'Templates',

// M
'moddescription' => 'Search the website database for specified text',

// N
'nomatch' => 'No matches were found',

// P
'perm_Use_Admin_Search' => 'Perform Database Searches',
'placeholder_search_text' => 'Enter search text',
'postinstall' => 'Database Search module installed',
'postuninstall' => 'Database Search module uninstalled',

// S
'search' => 'Search',
'search_text' => 'Search Text',
'search_typed' => 'Search %s',
'search_results' => 'Search Results',
'sectiondesc_oldmodtemplates' => 'Results in this section are not clickable, as each module provides its own Admin panel interface for editing templates',
'settings' => 'Settings',
'starting' => 'Starting',

// W
'warn_clickthru' => 'This will open another window. Canceling from that window might not return you to this page. Your search results might be lost.',

] + $lang;
