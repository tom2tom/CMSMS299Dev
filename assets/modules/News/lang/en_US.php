<?php

$lang['addarticle'] = 'Add Item';
$lang['addcategory'] = 'Add Category';
$lang['addtemplate'] = 'Add Template';
$lang['alert_drafts'] = 'Unpublished-item notices';
$lang['all'] = 'All';
$lang['allcategories'] = 'All Categories';
$lang['allentries'] = 'All Entries';
$lang['anonymous'] = 'Anonymous';
$lang['apply'] = 'Apply';
$lang['approve'] = 'Set status to \'final\'';
$lang['archived'] = 'Archived';
$lang['article'] = 'Article';
$lang['articleadded'] = 'The news item has been added.';
$lang['articlecopied'] = 'The news item has been cloned.';
$lang['articledeleted'] = 'The news item has been deleted.';
$lang['articles'] = 'News Items';
$lang['articleupdated'] = 'The news item has been updated.';
$lang['at'] = 'at'; //at a time
$lang['author_label'] = 'Posted by:';
$lang['author'] = 'Author';

$lang['browsecattemplate'] = 'Browse Category Templates';
$lang['bulk_delete'] = 'Delete';
$lang['bulk_setcategory'] = 'Set Category';
$lang['bulk_setdraft'] = 'Take Down';
$lang['bulk_setpublished'] = 'Publish Now';

$lang['categories'] = 'Categories';
$lang['category_label'] = 'Category:';
$lang['category'] = 'Category';
$lang['categoryadded'] = 'The category was successfully added.';
$lang['categorydeleted'] = 'The category was successfully deleted.';
$lang['categoryupdated'] = 'The category was successfully updated.';
$lang['close'] = 'Close';
$lang['confirm_bulk'] = 'Are you sure you want to perform this action on multiple items?';
$lang['confirm_delete'] = 'Are you sure you want to delete?';
//$lang['confirm_deletebulk'] = 'Are you sure you want to delete multiple items';
$lang['confirm_tpldefault'] = 'Are you sure you want to make this the default?';
$lang['content'] = 'Content';
$lang['copy'] = 'Clone';
$lang['created'] = 'Created';

//$lang['dateformat'] = '%s not in a valid yyyy-mm-dd hh:mm:ss format';
$lang['date_format'] = 'Displayed datetime format';
$lang['default_category'] = 'Item default category';
$lang['default_templates'] = 'Default Templates';
$lang['delete_article'] = 'Delete Item';
$lang['delete_selected'] = 'Delete Selected Items';
$lang['delete'] = 'Delete';
$lang['deprecated'] = 'unsupported';
$lang['desc_adminsearch'] = 'Search all news items (regardless of status or expiry)';
$lang['desc_news_settings'] = 'Adjust settings for the News module';
$lang['description'] = 'Add, change or remove news items';
$lang['detail_page'] = 'Detail Page';
$lang['detail_returnid'] = 'Detail views default page';
$lang['detail_template'] = 'Detail Template';
$lang['detailtemplate'] = 'Detail Templates';
//$lang['detailtemplateupdated'] = 'The updated Detail Template was successfully saved to the database.';
$lang['displaytemplate'] = 'Display Template';
$lang['down'] = 'Down';
$lang['draft'] = 'Draft';
$lang['dropdown'] = 'Dropdown';

$lang['edit'] = 'Edit';
$lang['editthis'] = 'Edit this item';
$lang['enddate'] = 'Expire';
$lang['endrequiresstart'] = 'Entering an end date requires a start date also';
$lang['entries'] = '%s Entries';
$lang['error_categorynotfound'] = 'The category specified was not found';
$lang['error_categoryparent'] = 'Invalid category parent';
$lang['error_detailed'] = 'Error: %s';
$lang['error_duplicatename'] = 'An item with that name already exists';
$lang['error_filesize'] = 'An uploaded file exceeded the maximum allowed size';
$lang['error_insufficientparams'] = 'Insufficient (or empty) parameters';
$lang['error_invaliddates'] = 'One or both of the entered dates were invalid';
$lang['error_invalidfiletype'] = 'Cannot upload this type of file';
$lang['error_invalidurl'] = 'Invalid URL <em>(maybe it is already used, or there are invalid characters)</em>';
$lang['error_noarticlesselected'] = 'No item was selected';
$lang['error_templatenamexists'] = 'A template by that name already exists';
$lang['error_unknown'] = 'An unknown error occurred';
$lang['error_upload'] = 'Problem occurred uploading a file';
$lang['expired_searchable'] = 'Expired items are searchable';
$lang['expired_viewable'] = 'Expired items are viewable';
$lang['expired'] = 'Expired';
$lang['expiry_interval'] = 'Item default lifetime (days)';
$lang['expiry'] = 'Expiry';
$lang['extra_label'] = 'Extra:';
$lang['extra'] = 'Extra';

$lang['file'] = 'Uploaded File';
$lang['filter'] = 'Filter Items';
$lang['final'] = 'Final';
$lang['first'] = 'First';
$lang['firstpage'] = '&lt;&lt;';
$lang['formtemplate'] = 'Form Templates';

$lang['help_alert_drafts'] = 'If enabled, an admin-console notice will be created when there are news item(s) which have not been published, and might be waiting on pre-publication review.';
$lang['help_article_category'] = 'For organization purposes, you may select a category';
$lang['help_article_content'] = 'Enter the main news item content here';
$lang['help_article_enddate'] = 'If use expiry is enabled, this date specifies when the news item will be hidden from view';
$lang['help_article_expire'] = "Enter the date, and if relevant the time on that date, when the news item will cease to be displayed. Or an empty date will be regarded as 'forever' i.e. manual expiry is needed.";
$lang['help_article_extra'] = 'This is extra data to associate with the news item.  It might be used for a sort-order, or any other purpose.  Consult the site developer about how this field is used (if at all)';
$lang['help_article_publish'] = 'Enter the date, and if relevant the time on that date, when the news item will [re]start being displayed. Or an empty date will be regarded as when-final (which may be immediate).';
$lang['help_article_searchable'] = 'This field indicates whether this news item should be indexed by the search module';
$lang['help_article_status'] = 'If the news item is ready to be displayed on the website, then select status final (and the publish-date will come into play). If this news item needs more work, select draft. If this news item is to be removed from display, select archived.';
$lang['help_article_summary'] = 'Enter a brief paragraph to describe the news item.  This summary might be used when displaying views of a number of items';
$lang['help_article_title'] = 'Enter the news item title.  It should be brief, and not include any html tags.';
$lang['help_article_url'] = 'An optional URL-suffix <em>(some other platforms call this a slug)</em> to access this news item.  Users can navigate to &lt;site_root&gt;/&lt;item_url&gt; to view this news item.';
$lang['help_article_useexpiry'] = 'This checkbox toggles the expiry date behavior.  Expiry date behavior dictates when a news item becomes visible on the website, and when it subsequently becomes invisible.';
$lang['help_articleid'] = 'This parameter is only applicable to the detail view.  It allows specifying which news item to display in detail mode.  If the special value -1 is used, the system will display the newest, published, non expired news item.';
$lang['help_articles_filtercategory'] = 'Optionally filter the list of displayed items in this list by those that belong to the selected category';
$lang['help_articles_filterchildcats'] = 'If enabled, items in the selected category, and their child categories will be displayed.';
$lang['help_articles_pagelimit'] = 'Select the number of items to show in one page.  For sites with a large number of items specifying a page limit between 10 and 100 will significantly improve performance';
$lang['help_bulk'] = 'Perform selected operation on all selected news item(s) at once';
$lang['help_category_name'] = 'Enter a name for this category.  The name should be safe for use in URL\'s and not include any special characters.';
$lang['help_category_parent'] = 'Optionally specify a parent category to build a hierarchy of categories.';
$lang['help_date_format'] = 'Enter a format string recognizable by <a href="https://www.php.net/manual/function.strftime.php" class="external" target="_blank"><u>PHP strftime</u></a>. Best if the format suits useful sorting. Or if empty, the site-default setting will be used.';
$lang['help_detail_returnid'] = 'This preference specifies a site page (and therefore a template) to use for displaying news items in detail-format. Custom news-detail URL\'s will not work if this parameter is not set to a valid page.  Additionally, if this preference is set, and no detailpage parameter is provided on the news tag, then this value will be used for detail links';
$lang['help_dflt_category'] = 'This option allows specifying the default category for new news-items.';
$lang['help_expired_searchable'] = 'If enabled, expired items might continue to be indexed by the search module, and appear in search results';
$lang['help_expired_viewable'] = 'If enabled, expired items can be viewed in detail mode (this is reproducing older functionality). Also, the showall parameter can be included in the URL (when not using pretty urls) to indicate that expired items can be viewed';
$lang['help_expiry_interval'] = 'Set the default lifetime (days, minimum 1) for news items flagged to expire. This is ignored for non-expiring items. The expiry date can be adjusted when adding or editing a news item';

$lang['help_idlist'] = 'Applicable only to the default action (summary view). A comma-separated sequence of numeric news-item id(s). It allows filtering items to specific item(s). The actual list of items output is still subject to news item status, expiry date, and other parameters.';
$lang['help_pagelimit'] = 'Maximum number of items to display per page.  If this parameter is not supplied, all matching items will be displayed.  If it is, and there are more items available than specified in the parameter, text and links will be supplied to allow scrolling through the results.  The maximum value for this parameter is 1000.';
// plugin parameters advice
$lang['helpbrowsecat'] = 'Shows a browsable category list.';
$lang['helpbrowsecattemplate'] = 'Use the named template for displaying the category browser. If this parameter is not specified, the default template of the "browsecat" type will be used if needed.';
$lang['helpcategory'] = 'Used in the summary view to display only items for the specified categories. <b>Use * after the name to show children.</b>  Multiple categories can be used if separated with a comma. Leaving empty will show all categories.  This parameter also works for the frontend submit action, however only a single category name is supported.';
$lang['helpdetailpage'] = 'Page to display news details in.  This can either be a page alias or an id. Used to allow details to be displayed in a different template from the summary.  This parameter will have no effect for items with custom URLs.';
$lang['helpdetailtemplate'] = 'Use the named template for displaying a news item in detail. If this parameter is not specified, the default template of the "detail" type will be used if needed. This parameter is not used when generating URLs if custom URLs are specified.';
$lang['helpmoretext'] = 'Text to display at the end of a news item if it goes over the summary length.  The string used is translated.';
$lang['helpnumber'] = 'Maximum number of items to display (per page) -- leaving empty will show all items.  This is an alias for the pagelimit parameter.';
$lang['helpshowall'] = 'Show all items, irrespective of end date';
$lang['helpshowarchive'] = 'Show only expired news items.';
$lang['helpsortasc'] = 'Sort displayed news items in ascending order rather than descending.';
$lang['helpsortby'] = 'Field to sort displayed item by. Options are: "news_date", "summary", "news_data", "news_category", "news_title", "news_extra", "end_time", "start_time", "random". If "random" is specified, the sortasc param is ignored.';
$lang['helpstart'] = 'Start at the nth item -- leaving empty will start at the first item.';
$lang['helpsummarytemplate'] = 'Use the named template for displaying the news items summary view. If this parameter is not specified, the default template of the "summary" type will be used if needed.';

$lang['info_categories'] = 'To assist access and comprehension, news items can be organized into a hierarchy of categories';
//$lang['info_notemplate'] = 'Cannot find any template that you are authorized to modify';
$lang['info_reorder_categories'] = 'Drag and drop each item into the correct order to change category relationships';
$lang['info_sysdefault'] = '(the content used by default when a new template is created)';
$lang['info_sysdefault2'] = '<strong>Note:</strong> This tab contains text areas to allow you to edit a set of templates that are displayed when you create a \'new\' summary, detail, or form template.  Changing content in this tab, and clicking \'submit\' will <strong>not affect any current displays</strong>.';

$lang['last'] = 'Last';
$lang['lastpage'] = '&gt;&gt;';
$lang['lbl_adminsearch'] = 'news items'; //no preceeding 'Search'
$lang['linkedfile'] = 'Linked File';

$lang['maxlength'] = 'Maximum Length';
$lang['modified'] = 'Modified';
$lang['moreprompt'] = 'more ...';
$lang['more'] = 'More';
$lang['moretext'] = 'More Text';
$lang['msg_cancelled'] = 'Operation cancelled';
$lang['msg_categoriesreordered'] = 'Category order updated';
$lang['msg_contenttype_removed'] = 'The news content type has been removed. Please place {News} tags with appropriate parameters into your page template or into your page content to replace this functionality.';
$lang['msg_success'] = 'Operation Successful';

$lang['name'] = 'Name';
$lang['needpermission'] = 'You need the \'%s\' permission to perform that function.';
$lang['newcategory'] = 'New Category';
$lang['news_return'] = 'Return';
$lang['news'] = 'News';
$lang['next'] = 'Next';
$lang['nextpage'] = '&gt;';
$lang['noarticles'] = 'No news item is recorded';
$lang['noarticlesinfilter'] = 'The applied filter excludes all news items';
$lang['nocategorygiven'] = 'No category given';
$lang['nocontentgiven'] = 'No content given';
$lang['noitemsfound'] = '<strong>No</strong> items found for category: %s';
$lang['nonamegiven'] = 'No name given';
$lang['none'] = 'None';
$lang['notanumber'] = 'Maximum length is not a number';
$lang['note'] = '<em>Note:</em> Dates must be in a \'yyyy-mm-dd hh:mm:ss\' format.';
$lang['notemplate'] = 'No template is recorded';
$lang['notify_n_draft_items_sub'] = '%d news item(s)';
$lang['notify_n_draft_items'] = '%d unpublished news-item(s) is/are recorded'; //notification message
$lang['notitlegiven'] = 'No title given';
$lang['numbertodisplay'] = 'Number to Display (empty shows all records)';

$lang['options'] = 'Options';
$lang['optionsupdated'] = 'The settings were successfully updated.';

$lang['pageof'] = 'Page %s of %s';
$lang['pagerows'] = 'rows per page';
$lang['parent'] = 'Parent';
$lang['postinstall'] = 'Make sure to set the "Modify News" permission for users who will be administering news-items.';
$lang['preview'] = 'Preview';
$lang['previous'] = 'Previous';
$lang['prevpage'] = '&lt;';
$lang['print'] = 'Print';

//$lang['prompt_asc'] = 'Title Ascending';
//$lang['prompt_desc'] = 'Title Descending';
//$lang['prompt_form_sysdefault'] = 'Default Form Template';
//$lang['prompt_form_template'] = 'Form Template Editor';
//$lang['type_browsecat'] = 'Browse Category';

$lang['prompt_addarticle'] = 'Add News Item';
$lang['prompt_addcategory'] = 'Add News Category';
$lang['prompt_addtemplate'] = 'Add News-Item Template';
$lang['prompt_available_templates'] = 'Available Templates';
$lang['prompt_browsecat_sysdefault'] = 'Default Browse-Category Template';
$lang['prompt_browsecat_template'] = 'Browse-Category Template Editor';
$lang['prompt_bulk'] = 'Bulk Operation';
$lang['prompt_category'] = 'Show Category'; //for filter dialog
$lang['prompt_default'] = 'Default';
$lang['prompt_detail_settings'] = 'Detail View Settings';
$lang['prompt_detail_sysdefault'] = 'Default Detail Template';
$lang['prompt_detail_template'] = 'Detail Template Editor';
$lang['prompt_draft_entries'] = 'Draft news-item(s) exist'; //notification message
$lang['prompt_editarticle'] = 'Edit News Item';
$lang['prompt_editcategory'] = 'Edit News Category';
$lang['prompt_edittemplate'] = 'Edit News-Item Template';
$lang['prompt_expire'] = 'Expire news item on'; //refers to a date selector
$lang['prompt_filter'] = 'Filter'; //see also 'filter'
$lang['prompt_filtered'] = 'Filter applied';
$lang['prompt_go'] = 'Go';
$lang['prompt_history'] = 'History';
$lang['prompt_name'] = 'Name';
$lang['prompt_news_settings'] = 'News Settings';
$lang['prompt_newtemplate'] = 'Create A New Template';
$lang['prompt_of'] = 'of';
$lang['prompt_page'] = 'Page';
$lang['prompt_pagelimit'] = 'Page Limit';
$lang['prompt_publish'] = 'Publish news item on'; //refers to a date selector
$lang['prompt_redirecttocontent'] = 'Return to page';
$lang['prompt_summary_sysdefault'] = 'Default Summary Template';
$lang['prompt_summary_template'] = 'Summary Template Editor';
$lang['prompt_template'] = 'Template Source';
$lang['prompt_templatename'] = 'Template Name';

$lang['public'] = 'Public';
$lang['published'] = 'Published';

$lang['reassign_category'] = 'Change Category To';
$lang['removed'] = 'Removed';
$lang['reorder_categories'] = 'Reorder Categories';
$lang['reorder'] = 'Reorder';
//$lang['reset'] = 'Reset';
//$lang['resettodefault'] = 'Reset to Factory Defaults';
//$lang['restoretodefaultsmsg'] = 'This operation will restore the template contents to their system defaults.  Are you sure you want to proceed?';
$lang['revert'] = 'Set status to \'draft\'';

$lang['searchable'] = 'Searchable';
$lang['select_option'] = 'Select Option';
$lang['select'] = 'Select';
$lang['selectall'] = 'Select all';
$lang['selectcategory'] = 'Select category';
$lang['selector_badday'] = 'The day you have just selected is not available';
//js selector plugin properties (comma-separated)
$lang['selector_days'] = 'Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday';
$lang['selector_months'] = 'January,February,March,April,May,June,July,August,September,October,November,December';
$lang['selector_shortdays'] = 'Sun,Mon,Tue,Wed,Thu,Fri,Sat';
$lang['selector_shortmonths'] = 'Jan,Feb,Mar,Apr,May,Jun,Jul,Aug,Sep,Oct,Nov,Dec';
$lang['selector_times'] = 'am,pm,AM,PM,.,mins,hr,hrs';
$lang['settings_title'] = 'News Settings';
$lang['showchildcategories'] = 'With All Descendants';
$lang['startdate'] = 'Publish';
$lang['startdatetoolate'] = 'The start date is too late (after end date?)';
$lang['startoffset'] = 'Start displaying at the nth item';
$lang['startrequiresend'] = 'If a start date is specified, an end date must also be specified';
$lang['status'] = 'Status';
$lang['subject_newnews'] = 'A news-item has been submitted';
$lang['summary'] = 'Summary';
$lang['summarytemplate'] = 'Summary Templates';
//$lang['summarytemplateupdated'] = 'The News Summary Template was successfully updated.';
$lang['sysdefaults'] = 'Restore to defaults';

$lang['template'] = 'Template';
$lang['templateadded'] = 'The template was successfully added.';
$lang['templatedeleted'] = 'The template was successfully deleted.';
$lang['templateupdated'] = 'The template was successfully updated.';
$lang['tip_addcategory'] = 'Add new category';
$lang['tip_bulk'] = 'Select this item for bulk processing';
$lang['tip_copy_template'] = 'Clone this template';
$lang['tip_delete_template'] = 'Delete this template';
$lang['tip_edit_template'] = 'Edit this template';
$lang['tip_reordercat'] = 'Adjust the categories hierarchy';
$lang['tip_tpl_type'] = 'This column indicates whether the template is the default for its type (if any)';
$lang['tip_typedefault'] = 'Set this as the default for its type';
$lang['tip_viewfilter'] = 'Display filters dialog';
$lang['title'] = 'Title';
$lang['type_browsecat'] = 'Categories Browser'; //template-type public name component
$lang['type_detail'] = 'Detail'; //template-type public name component
$lang['type_form'] = 'Frontend Form (abandonware)'; //abandoned template-type public name component
$lang['type_News'] = 'News'; //template-type public name component
$lang['type_summary'] = 'Summary'; //template-type public name component

$lang['type_item'] = 'News Item'; // typed-string %s replacement
$lang['type_category'] = 'News Category'; // typed-string %s replacement
$lang['type_template'] = 'Template';  // typed-string %s replacement
$lang['type'] = 'Type';

$lang['unknown'] = 'Unknown';
$lang['unlimited'] = 'Unlimited';
$lang['up'] = 'Up';
//$lang['uploadscategory'] = 'Uploads Category';
$lang['url'] = 'Access URL';
$lang['useexpiration'] = 'Use Expiration Date';

$lang['warning_preview'] = 'This preview panel allows you to navigate away from the initially previewed page. Be aware that if you do so, you might experience unexpected behavior.  Navigating away from the initial page and returning will not give the expected results.<br /><strong>Note:</strong> The preview does not upload files you might have selected for upload.';
$lang['with_selected'] = 'With Selected';

//$lang['yes'] = 'Yes';

//<li>&quot;fesubmit&quot; - <strong>Deprecated</strong> to display the frontend form for allowing users to submit news items on the front end. Add the <code>{cms_init_editor}</code> tag in the metadata section to initialize the selected WYSIWYG editor. (Site Admin >> Global Settings)</li>
//$lang['addfielddef'] = 'Add Field Type';
//$lang['allow_fesubmit'] = 'Allow frontend users to submit news items';
//$lang['allow_summary_wysiwyg'] = 'Summary field WYSIWYG editing';
//$lang['allowed_upload_types'] = 'Insertable filetypes';
//$lang['articlesubmitted'] = 'The news item has been submitted.';
//$lang['auto_create_thumbnails'] = 'Automatically create thumbnail files for files with these extensions';
//$lang['checkbox'] = 'Checkbox';
//$lang['editfielddef'] = 'Edit Field Type';
//$lang['email_subject'] = 'Outgoing emails\' subject ';
//$lang['email_template'] = 'Outgoing emails\' template';
//$lang['error_mkdir'] = 'Could not create directory: %s';
//$lang['error_movefile'] = 'Could not create file: %s';
//$lang['error_nooptions'] = 'No option specified for field type';
//$lang['expiry_date_asc'] = 'Expiry-date Ascending';
//$lang['expiry_date_desc'] = 'Expiry-date Descending';
//$lang['fesubmit_redirect'] = 'PageID or alias to redirect to after a news item has been submitted via the fesubmit action';
//$lang['fesubmit_status'] = 'The status of news items submitted via the frontend';
//$lang['fielddef'] = 'Field Definition';
//$lang['fielddefadded'] = 'Field type successfully added';
//$lang['fielddefdeleted'] = 'Field type deleted';
//$lang['fielddefupdated'] = 'Field type updated';
//$lang['formsubmit_emailaddress'] = 'Email address to receive notification of news submission';
//$lang['help_article_postdate'] = 'The postdate <em>(usually the current date, for new items)</em> is the date that will be used as the publish date for the news item.  It is also used in sorting';
//$lang['help_article_startdate'] = 'This date specifies the date from which the news item will be visible on the website';
//$lang['help_articles_sortby'] = 'Select how items will be initially sorted.';
//$lang['help_opt_allow_summary_wysiwyg'] = 'This field indicates whether a WYSIWYG editor should be enabled for the summary field when editing a news item.  In many circumstances the summary field is a simple text field, however this is optional.<br />This setting is ignored if the summary field is disabled completely <em>(see above)</em>';
//$lang['help_opt_hide_summary'] = 'This option allows disabling the summary field when adding and/or editing a news item';
//$lang['helpsortasc'] = 'Sort news items in ascending date order rather than descending.';
//$lang['helpsortby'] = 'Field to sort the display by.  Options are: "summary", "news_data", "news_category", "news_title", "news_extra", "start_time", "end_time", "random".  Defaults to "start_time". If "random" is specified, the sortasc parameter is ignored.';
//$lang['hide_summary_field'] = 'Hide the summary field when adding or editing items';
//$lang['info_searchable'] = 'This field indicates whether this news item should be indexed by the search module';
//$lang['nameexists'] = 'A field by that name already exists';
//$lang['nopostdategiven'] = 'No post-date given';
//$lang['post_date_asc'] = 'Publish-date Ascending';
//$lang['post_date_desc'] = 'Publish-date Descending';
//$lang['postdate'] = 'Post Date';
//$lang['prompt_sorting'] = 'Sort By';
//$lang['sortascending'] = 'Sort Ascending';
//$lang['status_asc'] = 'Status Ascending';
//$lang['status_desc'] = 'Status Descending';
//$lang['prompt_notification_settings'] = 'Notification Settings';
//$lang['prompt_submission_settings'] = 'News Submission Settings';

// multi-line strings

$lang['eventdesc-NewsArticleAdded'] = 'Sent when a news item is added.';
$lang['eventhelp-NewsArticleAdded'] = <<<'EOF'
<h4>Parameters</h4>
<ul>
<li>"news_id" - Id of the news item</li>
<li>"category_id" - Id of the category of the news item</li>
<li>"title" - Title of the news item</li>
<li>"content" - Content of the news item</li>
<li>"summary" - Summary of the news item</li>
<li>"status" - Status of the news item ("draft" or "final")</li>
<li>"start_time" - Date the news item should start being displayed</li>
<li>"end_time" - Date the news item should stop being displayed</li>
<li>"useexp" - Whether or not the expiration date should be ignored</li>
</ul>
EOF;

$lang['eventdesc-NewsArticleDeleted'] = 'Sent when a news item is deleted.';
$lang['eventhelp-NewsArticleDeleted'] = '<h4>Parameters</h4>
<ul>
<li>"news_id" - Id of the news item</li>
</ul>
';

$lang['eventdesc-NewsArticleEdited'] = 'Sent when a news item is edited.';
$lang['eventhelp-NewsArticleEdited'] = <<<'EOF'
<h4>Parameters</h4>
<ul>
<li>"news_id" - Id of the news item</li>
<li>"category_id" - Id of the category of the news item</li>
<li>"title" - Title of the news item</li>
<li>"content" - Content of the news item</li>
<li>"summary" - Summary of the news item</li>
<li>"status" - Status of the news item ("draft" or "final")</li>
<li>"start_time" - Date the news item should start being displayed</li>
<li>"end_time" - Date the news item should stop being displayed</li>
<li>"useexp" - Whether the expiration date should be ignored or not</li>
</ul>
<p><strong>Note:</strong> Not all parameters might be present when this event is sent.</p>
EOF;

$lang['eventdesc-NewsCategoryAdded'] = 'Sent when a category is added.';
$lang['eventhelp-NewsCategoryAdded'] = '<h4>Parameters</h4>
<ul>
<li>"category_id" - Id of the news category</li>
<li>"name" - Name of the news category</li>
</ul>
';

$lang['eventdesc-NewsCategoryDeleted'] = 'Sent when a category is deleted.';
$lang['eventhelp-NewsCategoryDeleted'] = '<h4>Parameters</h4>
<ul>
<li>"category_id" - Id of the deleted category </li>
<li>"name" - Name of the deleted category</li>
</ul>
';

$lang['eventdesc-NewsCategoryEdited'] = 'Sent when a category is edited.';
$lang['eventhelp-NewsCategoryEdited'] = <<<'EOF'
<h4>Parameters</h4>
<ul>
<li>"category_id" - Id of the news category</li>
<li>"name" - Name of the news category</li>
<li>"origname" - The original name of the news category</li>
</ul>
EOF;

$lang['helpaction'] = <<<'EOF'
Override the default action.  Possible values are:
<ul>
<li>&quot;detail&quot; - to display a specified articleid in detail mode.</li>
<li>&quot;default&quot; - to display the summary view</li>
<li>&quot;browsecat&quot; - to display a browsable category list.</li>
</ul>
EOF;


$lang['help'] = <<<'EOF'
<h3>What does this do?</h3>
<p>News is a module for displaying news items on a website page, similar to a blog, but with more features.</p>
<h3>How is it used?</h3>
<p>Include a {News} tag&nbsp; in relevant page(s) and/or template(s). The tag would be something like:</p>
<pre>{News number='5'}</pre>
<p>Each such tag will display news items in accord with the tag parameters.</p>
<h3>Features</h3>
<p>Each news item can have a summary, for display where space is restricted.</p>
<p>Each news item can have a publication date and/or expiry date.</p>
<p>The layout and styling of displayed news items are flexible and customisable. Smary templates drive the display.</p>
<p>The module may be configured to require independent approval of news items before their publication.</p>
<p>The content of news items can be searched and indexed as part of site-wide scans.</p>
<p>A hierarchy of categories can be created, for organizing news items.</p>
<h3>Templates</h3>
<p>News-module templates are managed in the same way as other site templates. Operations are initiated via the admin console layout-menu
item 'templates'. This means that template managment requires the general site template-management authority.</p>
<p>Starting in version 3, News supports multiple templates, and no longer supports additional file templates.</p>
<p>Sites which use the old file-template system need an upgrade. Follow the following steps (for each file template):</p>
<ul>
<li>Copy the file template into the clipboard</li>
<li>Create a new template <em>(either summary or detailed as required)</em>. Give that new template the same name as the old file template, and paste the contents.</li>
<li>Hit Submit</li>
</ul>
<p>Following these steps should solve the problem of news templates not being found and other Smarty errors.</p>
<h4>Template Parameters</h4>
For each relevant news item, the following properties are provided for use in templates:<br />
<ul>
<li>author</li>
<li>author_id</li>
<li>authorname</li>
<li>category</li>
<li>content</li>
<li>create_date</li>
<li>created <em>(UTC timestamp)</em></li>
<li>detail_url</li>
<li>enddate</li>
<li>stop <em>(UTC timestamp)</em></li>
<li>id</li>
<li>link</li>
<li>modified_date</li>
<li>modified <em>(UTC timestamp)</em></li>
<li>morelink</li>
<li>moreurl</li>
<li>postdate <em>(deprecated, same as startdate)</em></li>
<li>startdate</li>
<li>start <em>(UTC timestamp)</em></li>
<li>summary</li>
<li>title</li>
<li>titlelink</li>
</ul>
<br />Formerly-provided parameters <code>fields</code>, <code>fieldsbyname</code> and <code>file_location</code> were for frontend use, and are gone.
<br/><br />
The parameters which may validly be submitted from a frontend-displayed news item are:<br />
<ul>
<li>articleid</li>
<li>assign</li>
<li>browsecat</li>
<li>browsecattemplate</li>
<li>category</li>
<li>category_id</li>
<li>detailpage</li>
<li>detailtemplate</li>
<li>formtemplate</li>
<li>idlist</li>
<li>inline</li>
<li>moretext</li>
<li>number</li>
<li>origid</li>
<li>pagelimit</li>
<li>pagenumber</li>
<li>preview</li>
<li>showall</li>
<li>showarchive</li>
<li>sortasc</li>
<li>sortby</li>
<li>start</li>
<li>summarytemplate</li>
</ul>
<h4>Deprecations</h4>
The provided news-item property <code>-&gt;postdate</code> is deprecated. Templates should use <code>item-&gt;startdate</code> instead.
<br />
Reminder: the <code>-&gt;formatpostdate</code> and <code>-&gt;dateformat</code> properties are long-gone (News 2.9?).
<h3>Permissions</h3>
<p>To add or edit news items, the user must belong to a group with the 'Modify News' permission.
<br />
To delete news items, the user must <strong>also</strong> belong to a group with the 'Delete News' permission.
<br />
To approve news items for display, the user must belong to a group with the 'Approve News' permission.
<br />
To modify news item templates, the user must belong to a group with the 'Modify News Preferences' permission, or if authorized, may process the templates via the site-wide templates interface.
<br />
To modify news categories or News-module preferences, the user must belong to a group with the 'Modify News Preferences' permission.
</p>
EOF;
