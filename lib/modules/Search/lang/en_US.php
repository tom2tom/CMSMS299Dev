<?php

$lang = [

// A
'apply' => 'Apply',

// C
'cancel' => 'Cancel',
'clear' => 'Clear',
'confirm_clearstats' => 'Are you sure you want to permanently clear all statistics?',
'confirm_reindex' => 'This operation could take an extended amount of time, and/or require an extensive amount of PHP memory.  Are you sure you want to re-index all content?',
'count' => 'Count',

// D
'default_stopwords' => 'i, me, my, myself, we, our, ours, ourselves, you, your, yours, yourself, yourselves, he, him, his, himself, she, her, hers, herself, it, its, itself, they, them, their, theirs, themselves, what, which, who, whom, this, that, these, those, am, is, are, was, were, be, been, being, have, has, had, having, do, does, did, doing, a, an, the, and, but, if, or, because, as, until, while, of, at, by, for, with, about, against, between, into, through, during, before, after, above, below, to, from, up, down, in, out, on, off, over, under, again, further, then, once, here, there, when, where, why, how, all, any, both, each, few, more, most, other, some, such, no, nor, not, only, own, same, so, than, too, very',
'description' => 'Search across the website for specified words or phrases',

// E
'eventdesc-SearchAllItemsDeleted' => 'Sent when all items are deleted from the index.',
'eventhelp-SearchAllItemsDeleted' => <<<'EOS'
<h4>Parameters</h4>
<ul>
<li>None</li>
</ul>
EOS
,

'eventdesc-SearchCompleted' => 'Sent when a search is completed.',
'eventhelp-SearchCompleted' => <<<'EOS'
<h4>Parameters</h4>
<ol>
<li>Text that was searched for.</li>
<li>Array of the completed results.</li>
</ol>
EOS
,

'eventdesc-SearchInitiated' => 'Sent when a search is started.',
'eventhelp-SearchInitiated' =>  <<<'EOS'
<h4>Parameters</h4>
<ol>
<li>Text that was searched for.</li>
</ol>
EOS
,

'eventdesc-SearchItemAdded' => 'Sent when a new item is indexed.',
'eventhelp-SearchItemAdded' =>  <<<'EOS'
<h4>Parameters</h4>
<ol>
<li>Module name.</li>
<li>Id of the item.</li>
<li>Additional Attribute.</li>
<li>Content to index and add.</li>
</ol>
EOS
,

'eventdesc-SearchItemDeleted' => 'Sent when an item is deleted from the index.',
'eventhelp-SearchItemDeleted' =>  <<<'EOS'
<h4>Parameters</h4>
<ol>
<li>Module name.</li>
<li>Id of the item.</li>
<li>Additional Attribute.</li>
</ol>
EOS
,
'export_to_csv' => 'Export to CSV',

// H

//'help_alpharesults' => 'Sort the discovered words alphabetically (English only)',
'help_resetstopwords' => 'Revert to the list of stopwords included in the strings-translation for the current language',
'help_resultpage' => 'TODO',
'help_savephrases' => 'Search for specified text as a whole, instead of its individual words',
'help_searchtext' => 'Default text displayed in the search box',
'help_stemming' => 'Ignore words which have the more common morphological and inflexional endings of other words',
'help_stopwords' => 'Words to be ignored when conducting a search',
'help' => '<h3>What does this do?</h3>
<p>Search is a module for searching "core" content along with certain registered modules.  You put in a word or two and it gives you back matching, relevant results.</p>
<h3>How is it used?</h3>
<p>The easiest way to use it is with the {search} wrapper tag (wraps the module in a tag, to simplify the syntax). This will insert the module into your template or page anywhere you wish, and display the search form.  The code would look something like: <code>{search}</code></p>
<h4>How do i prevent certain content from being indexed</h4>
<p>The search module will not search any "inactive" pages. However on occasion, when you are using the CustomContent module, or other smarty logic to show different content to different groups of users, it might be advisable to prevent the entire page from being indexed even when it is live.  To do this include the following tag anywhere on the page <em>&lt;!-- pageAttribute: NotSearchable --&gt;</em> When the search module sees this tag in the page it will not index any content for that page.</p>
<p>The <em>&lt;!-- pageAttribute: NotSearchable --&gt;</em> tag can be placed in the template as well.  if this is done, none of the pages attached to that template will be indexed.  Those pages will be re-indexed if the tag is removed</p>
',

// I
'input_resetstopwords' => 'Load',

// N
'noresultsfound' => 'No result found',
'nostatistics' => 'No statistics found',

// O
'options' => 'Options',

// P
'param_action' => 'Specify the mode of operation for the module. Acceptable values are \'default\', and \'keywords\'.  The keywords action can be used to generate a comma seperated list of words suitable for use in a keywords meta tag.',
'param_count' => 'Used with the keywords action, this parameter will limit the output to the specified number of words',
'param_detailpage' => 'Used only for matching results from modules, this parameter allows specifying a different detail page for the results.  This is useful if, for example, you always display your detail views in a page with a different template.  <em>(<strong>Note:</strong> modules have the ability to override this parameter.)</em>',
'param_formtemplate' => 'Used only for the default action, this parameter allows specyfing the name of a non default template.',
'param_inline' => 'If true, the output from the search form will replace the original content of the \'search\' tag in the originating content block.  Use this parameter if your template has multiple content blocks, and you do not want the output of the search to replace the default content block',
'param_modules' => 'Limit search results to values indexed from the specified (comma separated) list of modules',
'param_pageid' => 'Applicable only with the keywords action, this parameter can be used to specify a different pageid to return results for',
'param_passthru' => 'Pass named parameters down to specified modules.  The format of each of these parameters is: "passtru_MODULENAME_PARAMNAME=\'value\'" i.e.: passthru_News_detailpage=\'newsdetails\'"',
'param_resultpage' => 'Page to display search results in.  This can either be a page alias or an id.  Used to allow search results to be displayed in a different template from the search form',
'param_resulttemplate' => 'This parameter allows specifying the name of a non default template to use for displaying search results.',
'param_searchtext' => 'Text to place into the search box',
'param_submit' => 'Text to place into the submit button',
'param_useor' => 'Change the default relationship from an OR relationship to an AND relationship',
'prompt_alpharesults' => 'Sort results alphabetically instead of by weight',
'prompt_resetstopwords' => 'Load default Stop Words',
'prompt_resultpage' => 'Page for individual module results <em>(Note modules might override this)</em>',
'prompt_savephrases' => 'Track phrases instead of individual words',
'prompt_searchtext' => 'Search entry placeholder text',

// R
'reindexallcontent' => 'Re-index All Content',
'reindexcomplete' => 'Re-index Complete!',
'restoretodefaultsmsg' => 'This operation will restore the template contents to their system defaults.  Are you sure you want to proceed?',
'resulttemplate' => 'Result Template',
'resulttemplateupdated' => 'Result Template Updated',

// S

'search' => 'Content Search',
'searchplaceholder' => 'Enter search...',
'searchresultsfor' => 'Search Results For',
'searchsubmit' => 'Submit',
'searchtemplate' => 'Search Template',
'searchtemplateupdated' => 'Search template updated',
'search_method' => 'Pretty URLs compatibility via method POST, default value is always GET, to make this work just put {search search_method="post"} ',
'settings_title' => '%s Settings',
'statistics' => 'Statistics',
'stopwords' => 'Stop Words',
'submit' => 'Submit',
'sysdefaults' => 'Restore To Defaults',

// T
'timetaken' => 'Time Taken',
'type_Search' => 'Search',
'type_searchform' => 'Search Form',
'type_searchresults' => 'Search Results',

// U
'usestemming' => 'Use word stemming (English only)',
'use_or' => 'Find results that match ANY word',

// W
'word' => 'Word',

] + $lang;
