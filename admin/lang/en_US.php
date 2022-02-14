<?php

$lang = [

'jobs_settings' => 'Background Jobs', // see also 'jobslabel'
'prompt_frequency' => 'Interval (<em>minutes</em>)',
//'prompt_joburl' => 'Custom Job-Processing URL',
'prompt_timelimit' => 'Maximum Duration (<em>seconds</em>)',

'msg' => 'Message',
'message' => 'Message', // TODO duplication fix
'notice' => 'Notice',
'warning' => 'Warning',
'detail' => 'Details',
//'nolog' => 'No entry matches the applied filter',

// 4
'403description' => 'Page Forbidden',
'404description' => 'Page Not Found',
'503description' => 'Service Not Available',

// A
'about' => 'About',
'accessdescription' => 'Manage website-folders\' access controls',
'accesslabel' => 'Folder Controls',
'accountupdated' => 'User account has been updated.',
'action' => 'Action',
'actioncontains' => 'Action Contains',
'actionstatus' => 'Action/Status',
'active' => 'Active',
'add_usrplg' => 'Add User Defined Tag', //title
'add' => 'Add',
'addbookmark' => 'Add bookmark',
'addcontent' => 'Add New Content',
'added_content' => 'Added Content',
'added_group' => 'Added Group',
'added_template' => 'Added template',
'added_usrplg' => 'Added user plugin', //status message
'added_user' => 'Added User',
'addgroup' => 'Add New Group',
'addhandler' => 'Add selected event handler',
'addtemplate' => 'Add New Template',
'adduser' => 'Add New User',
'admin_enablenotifications' => 'Allow users to view notifications<br /><em>(notifications will be displayed on all Admin pages)</em>',
'admin_lock_refresh' => 'Locks Refresh Interval <em>(seconds)</em>',
'admin_lock_timeout' => 'Locks Timeout <em>(minutes)</em>',
'admin_login_module' => 'Login Processor',
'admin_login_processor' => 'Login Displayer',
'admin' => 'Admininstration',
//'adminaccess' => 'Enable Login',
'admincallout' => 'Enable Bookmarks',
'admindescription' => 'Administration functions for this site',
'adminhelpurl' => 'Custom Site-Help URL',
'adminhome' => 'Administration Home',
'adminindent' => 'Indent Content Display',
'adminlog_1day' => '1 day',
'adminlog_1month' => '1 month',
'adminlog_1week' => '1 week',
'adminlog_2weeks' => '2 weeks',
'adminlog_3months' => '3 months',
'adminlog_6months' => '6 months',
'adminlog_lifetime' => 'Admin-Log Entries Timeout',
'adminlog_manual' => 'Manual deletion',
'adminlog' => 'Admin Log', // see also 'loglabel'
'adminlogcleared' => 'The Admin Log was successfully cleared',
'adminlogdescription' => 'This logs Admin activity and shows i.e. content changes, user information, and time of access.',
'adminlogempty' => 'The Admin Log is empty',
'adminpaging' => 'Number of content items to show per page in the page list',
'adminpaneltitle' => 'Admin Console',
'adminplugin' => 'Admin Only',
'adminprefs' => 'User Preferences',
'adminprefsdescription' => 'Set your specific preferences for site administration',
'adminspecialgroup' => 'Warning: Members of this group automatically have all permissions',
'adminsystemtitle' => 'CMSMS Admin System',
'admintheme' => 'Admin Console Theme',
'advanced' => 'Advanced',
'alert' => 'Alert',
'alerts' => 'Alerts',
'alias' => 'Alias',
'aliasmustbelettersandnumbers' => 'Alias must be all letters and numbers',
'aliasnotaninteger' => 'Alias cannot be an integer',
'all_groups' => 'All Groups',
'all' => 'All',
'allow_browser_cache' => 'Allow Browser to Cache Pages',
'allpagesmodified' => 'All pages modified!',
'always' => 'Always',
'applied' => 'Applied',
'apply' => 'Apply',
'applydescription' => 'Save changes and continue to edit',
'assignmentchanged' => 'Group Assignments have been updated.',
'assignments' => 'Assign Users',
'author' => 'Author',
'autoclearcache' => 'Automatically clear the cache every <em>N</em> days',
'autoclearcache2' => 'Server Cache Lifetime <em>(days)</em>',
'autoinstallupgrade' => 'Automatically install or upgrade',
'automatedtask_success' => 'Automated task performed',

// B
'back' => 'Back to Menu',
'backendwysiwyg' => 'Default Rich-Text-Editor for Admin Console Use',
'backtoplugins' => 'Back to Plugins List',
'basic_attributes' => 'Basic Properties',
'bookmarks' => 'Bookmarks',
'browser_cache_expiry' => 'Browser Cache Lifetime <em>(minutes)</em>',
'browser_cache_settings' => 'Browser Cache',
'bulk_success' => 'Bulk operation was successfully performed',

// C
'cachecleared' => 'Cache(s) cleared',
'cachenotwritable' => 'Cache folder is not writeable. Clearing cache will not work. Please make the tmp/cache folder has full read/write/execute permissions (chmod 777).  You might also have to disable safe mode.',
'callable' => 'Function',
'cancel' => 'Cancel',
'cantchmodfiles' => "Couldn't change permissions on some files",
'cantremove' => 'Cannot remove',
'cantremovefiles' => 'Problem removing files (permissions?)',
'caution' => 'Caution',
'ce_navdisplay' => 'Content List Navigation Display',
'changehistory' => 'Change History',
'changeowner' => 'Change Owner',
'changepermissions' => 'Change Permissions',
'check_ini_set_off' => 'You might have difficulty with some functionality without this capability. This test might fail if safe_mode is enabled',
'check_ini_set' => 'Test ini_set',
'checksum_passed' => 'All checksums match those in the uploaded file',
'checksumdescription' => 'Check the integrity of CMSMS files by comparing against known checksums',
'checkversion' => 'New Version Checks',
'choose' => 'Choose',
'clear' => 'Clear',
'clearadminlog' => 'Clear Admin Log',
'clearcache_taskname' => 'Clear Cached Files',
'clearcache' => 'Clear Cache',
'clearusersettings' => 'Reset Settings',
'close' => 'Close',
'cms_forums' => 'CMSMS Forums',
'cms_home' => 'CMSMS Home',
'cms_install_information' => 'CMS Made Simple Install Information',
'cms_team' => 'CMSMS Team',
'cms_version' => 'CMSMS Version',
'CMSEX_C001' => 'Could not create a valid page alias from the input supplied',
'CMSEX_F001' => 'File system permissions problem',
'CMSEX_G001' => 'Attempt to set invalid property into object',
'CMSEX_INVALIDMEMBER' => 'Unknown array member',
'CMSEX_INVALIDMEMBERSET' => 'Invalid array member',
'CMSEX_L001' => 'Attempt to delete a non-expired lock owned by another user',
'CMSEX_L002' => 'Attempt to delete a lock which is not yet saved',
'CMSEX_L003' => 'Lock type empty',
'CMSEX_L004' => 'Lock not saved',
'CMSEX_L005' => 'Could not find a lock with the specified identifiers',
'CMSEX_L006' => 'Lock is owned by a different user. It cannot be manipulated.',
'CMSEX_L007' => 'Other/unknown locking error',
'CMSEX_L008' => 'Locking exception',
'CMSEX_L009' => 'Problem removing lock',
'CMSEX_L010' => 'This item is locked, and cannot be edited now.  Additionally, only expired locks can be removed.',
'CMSEX_M001' => 'Module already installed',
'CMSEX_M002' => 'Installing this package would result in a downgrade. Operation aborted',
'CMSEX_MODULENOTFOUND' => 'Module %s not found',
'CMSEX_SQL001' => 'Problem creating/updating lock record',
'CMSEX_XML001' => 'Problem opening XML file',
'CMSEX_XML002' => 'DTD version missing from or incompatible with the XML file',
'CMSEX_XML003' => 'XML file is incomplete or invalid',
'code' => 'Code',
'config_information' => 'CMS Made Simple Config Settings',
'config_issue' => 'Configuration Issue',
'config_writable' => 'config.php is writeable. The system is safer when that file is read-only',
'confirm_bulkuserop' => "Be cautious about performing operations on multiple users simultaneously.\n\nAre you sure you want to continue?",
'confirm_delete_usrplg' => 'Are you sure you want to delete this plugin?',
'confirm_delete_user' => 'Are you sure you want to delete this user account',
'confirm_edituser' => 'Are you sure you want to apply changes to this user account',
'confirm_leave' => 'Are you sure you want to leave this page? Any unsaved change(s) will be lost.',
'confirm_set_template_1' => 'Are you sure you want to set all of these pages to use this template',
'confirm_set_template_2' => 'Yes, I am sure.',
'confirm_set_template_3' => 'Yes, I am <strong>really</strong> sure.',
'confirm_switchuser' => 'Are you sure you want to switch the effective UID to this user?  You will need to logout from the admin console and re-login to resume normal operations under your user account.',
'confirm_toggleuseractive' => 'Are you sure you want to toggle the active state of this user?',
'confirm_uploadmodule' => 'Are you sure you would like to upload the selected XML file. Incorrectly uploading a module file might break a functioning website',
'confirm' => 'Confirm',
'confirmcancel' => 'Are you sure you want to discard your changes? Click OK to discard all changes. Click Cancel to continue editing.',
'confirmdefault' => 'Are you sure you want to set - %s - as site default page?',
'confirmdeletedir' => 'Are you sure you want to delete this directory and all of its contents?',
'connection_error' => 'Outgoing HTTP connections do not appear to work! There is a firewall or some ACL for external connections? This will result in module manager, and potentially other functionality failing.',
'connection_failed' => 'Connection failed!',
'content_autocreate_flaturls' => "Automatically created URL's are flat",
'content_autocreate_urls' => "Automatically create page URL's",
'content_copied' => 'Content Item Copied to %s',
'content_editor_legend' => 'Content settings',
'content_id' => 'Content ID',
'content_imagefield_path' => 'Path for the <em>page_image</em> tag',
'content_mandatory_urls' => "Page URL's are required",
'content_thumbnailfield_path' => 'Path for thumbnail field',
'content' => 'Content',
'contentadded' => 'The content was successfully added to the database.',
'contentdeleted' => 'The content was successfully removed from the database.',
'contentdescription' => 'Add, edit or examine website content',
'contentimage_path' => 'Path for {content_image} tag',
'contentmanagement' => 'Content Management',
'contenttype' => 'Content Type', //?
// 'contentupdated' => 'The content was successfully updated.', contentmanager module sting
'contract' => 'Collapse Section',
'contractall' => 'Collapse All Sections',
'copy_from' => 'Copy From',
'copy_paste_forum' => 'Text Format',
'copy_to' => 'Copy To',
'copy' => 'Copy',
'copycontent' => 'Copy Content Item', // content ?
'copyfromuser' => 'User',
'copystylesheet' => 'Copy stylesheet',
'copytemplate' => 'Copy Template',
'copyusersettings' => 'Get From Another User',
'copyusersettings2' => 'Copy settings from another user',
'core' => 'Core',
'create_dir_and_file' => 'Checking if the HTTPD process can create a file inside of a directory it created',
'create' => 'Create',
'created_at' => 'Created at',
'created_directory' => 'Created Directory',
'createnewfolder' => 'Create New Directory',
'credential_settings' => 'Credentials',
'cron_120m' => '2 Hours',
'cron_12h' => '12 Hours',
'cron_15m' => '15 Minutes',
'cron_24h' => '24 Hours',
'cron_30m' => '30 Minutes',
'cron_3h' => '3 Hours',
'cron_60m' => '1 Hour',
'cron_6h' => '6 Hours',
'cron_request' => 'Each Request',
'css_max_age' => 'Maximum amount of time (seconds) stylesheets can be cached in the browser',
'CSS' => 'CSS',
'cssalreadyused' => 'CSS name already in use',
'cssmanagement' => 'CSS Management',
'cssnameisblockname' => 'Use the block id as the default value for the CSS name parameter in content blocks',
'curl_versionstr' => 'version %s, minimum recommended version is %s',
'curl' => 'Test for the curl library',
'curlversion' => 'Test curl version',
'currentassociations' => 'Current Associations',
'currentdirectory' => 'Current Directory',
'currentgroups' => 'User-Groups',
'currentpages' => 'Current Pages',
'currenttemplates' => 'Current Templates',
'currentusers' => 'All Users',
'custom404' => 'Custom 404 Error Message',

// D
'dashboard' => 'Dashboard',
'database' => 'Database',
'databaseprefix' => 'Database Prefix',
'databasetype' => 'Database Type',
'date_format' => 'Displayed Date-Value Format',
'datetime_format' => 'Displayed Date-Time Value Format',
'date' => 'Date',
'day' => 'day',
'days' => 'days',
'days_counted' => '%s days',
'default_charset' => 'Is the default character-endoding UTF-8',
'default_contenttype' => 'Default Content Type',
'default_editor' => 'Default Editor',
'defaultparentpage' => 'Default Parent Page',
'default' => 'Default',
'delete' => 'Delete',
'deleteconfirm' => 'Are you sure you want to delete &quot;%s&quot; ?',
'deletecontent' => 'Delete Content',
'deleted_content' => 'Deleted content',
'deleted_directory' => 'Deleted directory',
'deleted_file' => 'Deleted file',
'deleted_group' => 'Deleted group',
'deleted_module' => 'Permanently removed %s',
'deleted_usrplg' => 'Deleted user plugin',
'deleted_template' => 'Deleted template', //USED?
'deleted_user' => 'Deleted user',
'deletepages' => 'Delete these pages?',
'deletetemplate' => 'Delete Template', //USED?
'deletetemplates' => 'Delete Templates', //USED?
'deletetheme' => 'Delete Theme', //USED?
'deleteuser' => 'Delete User Account',
'dependencies' => 'Dependencies',
'description' => 'Description',
'design_id' => 'Design Id', //?
'directoryabove' => 'directory above current level',
'directoryexists' => 'This directory already exists.',
'disable_functions' => 'disable_functions in PHP',
'disable' => 'Disable',
'disabled' => 'Disabled',
'disablesafemodewarning' => 'Disable Admin safe mode warning',
'disallowed_contenttypes' => 'Content Types that are NOT allowed',
'documentation' => 'Documentation',
'documentationtip' => 'CMSMS Documentation',
'down' => 'Down',
'download_cksum_file' => 'Create a new checksum file',
'download' => 'Download',
'duration_settings' => 'Other Durations',

//E
'E_ALL' => 'Is E_ALL enabled in error_reporting',
'E_DEPRECATED' => 'Is E_DEPRECATED disabled in error_reporting ?',
'E_STRICT' => 'Is E_STRICT disabled in error_reporting ?',
'ecommerce_desc' => 'E-commerce services',
'ecommerce' => 'E-Commerce',
'edit_usrplg' => 'Edit User Plugin', //title
'edit' => 'Edit',
'editconfiguration' => 'Edit Configuration',
'editcontent_settings' => 'Content Editing',
'edited_group' => 'Edited Group',
'edited_usrplg' => 'Edited user plugin', //status message
'edited_user_preferences' => 'Edited user preferences',
'edited_user' => 'Edited user',
'editgroup' => 'Edit Group',
'edittemplate' => 'Edit Template', //USED?
'edittemplatesuccess' => 'Template updated', //USED?
'edituser' => 'Edit User',
'email' => 'Email Address',
'emptyblock' => 'Empty content block: %s',
'enable' => 'Enable',
'enablecustom404' => 'Enable Custom 404 Message',
'enablenotifications' => 'Enable user notifications in the Admin section',
'enablesitedown' => 'The website is now in &quot;Maintenance Mode&quot',
'enablewysiwyg' => 'Use rich-text-editing on the &quot;Maintenance Mode&quot; message',
'encoding' => 'Encoding',
//'err_mail_sendwithoutsetup' => 'CMSMS has not been configured to send mail',
//'error_badfield' => 'Invalid %s given!',
//'error_nofield' => 'No %s given!',
//'error_poorfield' => 'The %s is not suitable',
'error_contenttype' => 'The content type associated with this page is invalid or not permitted',
'error_copyusersettings_self' => 'This user account is the template user. You cannot copy user settings here',
'error_coreupgradeneeded' => 'The CMSMS core must be upgraded before this operation can succeed',
'error_delete_default_parent' => 'Cannot delete the default page, or a parent of the default page.',
'error_deletespecialgroup' => 'Cannot delete the special Admin group',
//'error_frominvalid' => 'The "from" address specified is not a valid email address',
//'error_fromrequired' => 'A "from" address is required',
//'error_hostrequired' => 'A host name is required when using the SMTP mailer',
'error_internal' => 'Internal error',
'error_locknotsaved' => 'Cannot retrieve this information... lock has not been saved',
'error_logintoken' => 'Invalid login-session token',
//'error_mailnotset_notest' => 'Mail settings have not been saved.  Cannot test',
//'error_mailtest_noaddress' => 'No address specified for testing',
//'error_mailtest_notemail' => 'Value specified is not a valid email address',
'error_informationbad' => 'Missing or incorrect information',
'error_module_mincmsversion' => 'This module requires a newer version of CMS Made Simple',
'error_multiusersettings' => 'Cannot set multiple user settings options at the same time',
'error_no_content_blocks' => 'No content block was detected in this template.  Please ensure that you have at least the default {content} block defined in this template',
'error_no_default_content_block' => 'No default content block was detected in this template. Please ensure that you have a {content} tag in the page template.',
'error_nofileuploaded' => 'No file has been uploaded',
'error_nograntall_found' => 'Could not find a suitable &quot;GRANT ALL&quot; permission. This is not necessarily a problem, but failure when installing/removing modules or adding/deleting items/pages might be a consequence of this!',
'error_nomodules' => 'No module installed! Check Site Admin > Module Manager',
'error_notconfirmed' => 'Operation not confirmed',
'error_objectcantsetthis' => 'Cannot adjust the %s property of this object',
'error_parsing_content_blocks' => 'An error occurred parsing content blocks (look for an invalid template, or duplicated content blocks)',
'error_passwordinvalid' => 'The password is not suitable',
'error_passwordrequired' => 'A <strong>password</strong> is required for SMTP authentication',
'error_portinvalid' => 'Port number is invalid',
'error_retrieving_file_list' => 'Error retrieving file list',
'error_sitedownmessage' => 'It appears that the site-down message is empty.  Please at least display some text to inform visitors that this website is down for maintenance',
'error_usrplg_del' => 'Error deleting plugin. Permissions problem?',
'error_usrplg_exists' => 'A user-defined tag with this name already exists. Please choose another.',
'error_usrplg_name' => 'The name of a user-defined tag must start with a letter or underscore, with any number of letters, numbers or underscores to follow.',
'error_usrplg_nocode' => 'No code is specified for the plugin',
'error_usrplg_save' => 'Error saving plugin',
'error_usrplg_update' => 'Error updating plugin',
'error_timedifference2' => 'A discrepancy in time with the PHP environment was detected. This might cause problems when publishing i.e. news articles.',
'error_timeoutinvalid' => 'The time-out specified is invalid (must be between 1 and 3600 seconds)',
'error_type' => 'Error Type',
'error_uploadproblem' => 'An error occurred in the upload',
'error_usernamerequired' => 'A <b>username</b> is required for SMTP authentication',
'error' => 'Error',
'errorbadname' => 'Invalid item name',
'errorcantcreatefile' => 'Could not create a file (permissions problem?)',
'errorchildcontent' => 'Content still contains child contents. Please remove them first.',
'errordefaultpage' => 'Cannot delete the current default page. Please set a different one first.',
'errordeletingassociation' => 'Error deleting association',
'errordeletingcontent' => 'Error deleting content (either this page has children or is the default content)',
'errordeletingdirectory' => 'Could not delete directory. Permissions problem?',
'errordeletingfile' => 'Could not delete file. Permissions problem?',
'errordirectorynotwritable' => 'No permission to write in directory.  This could be caused by file permissions and ownership.  Safe mode might also be in effect.',
'errorfilenotwritable' => 'No permission to write to file \'%s\'.',
'errorgettingcontent' => 'Could not retrieve information for the specified content object',
'errorgroupexists' => 'A users group named \'%s\' already exists',
'errorinsertingbookmark' => 'Error inserting bookmark',
'errorinsertinggroup' => 'Error inserting group',
'errorinsertinguser' => 'Error inserting user',
'errormodulenotfound' => 'Internal error, could not find the instance of a module',
'errormodulenotloaded' => 'Internal error, the module has not been instantiated',
'errormoduleversionincompatible' => 'Module is incompatible with this version of CMSMS',
'errormodulewontload' => 'Problem instantiating an available module',
'errornofilesexported' => 'Error exporting files to XML',
'errorsendingemail' => 'There was an error sending the email.  Contact your administrator.',
'errorupdatetemplateallpages' => 'Template is not active',
'errorupdatingbookmark' => 'Error updating bookmark',
'errorupdatinggroup' => 'Error updating group',
'errorupdatingpages' => 'Error updating pages',
'errorupdatingtemplate' => 'Error updating template',
'errorupdatinguser' => 'Error updating user',
'errorusernameexists' => '\'%s\' is not available',
'erroruserinuse' => 'This user still owns content pages. Please change ownership to another user before deleting.',
'errorwrongfile' => 'Invalid file',
'event_description' => 'Event Description',
'event_name' => 'Event Name',
'event' => 'Event',
'eventhandlerdescription' => 'Events and associated event-handlers',
'eventhandlers' => 'Event Handlers',
'execute' => 'Execute',
'expand' => 'Expand Section',
'expandall' => 'Expand All Sections',
'expanded_xml' => 'Expanded XML file consisting of %s %s',
'export' => 'Export',
'exportsite_tip' => 'Construct an XML file containing pages, templates, designs etc',
'exportsite' => 'Export Site Content',
'exporttheme' => 'Export Theme',
'extensions' => 'Extensions',
'extensionsdescription' => 'Tailor the way CMSMS works, and other functions',
'external_setdesc' => 'Site settings implemented and managed elsewhere (by modules etc)',
'external_settings' => 'External',

// F
'f_msg' => 'Detail includes', // adminlog filter labels
'f_sev' => 'Min status',
'f_subj' => 'Subject includes',
'f_user' => 'Username is',
'failure' => 'Failure',
'false' => 'False',
'filecreatedirbadchars' => 'Invalid characters were detected in the submitted directory name',
'filecreatedirnoname' => 'Cannot create a directory with no name.',
'filemanagement' => 'File Management',
'filemanager' => 'File Manager',
'filemanagerdescription' => 'Upload and manage files.',
'filename' => 'Filename',
'filenotuploaded' => 'File could not be uploaded. This could be a Permission or Safe Mode problem?',
'file_uploads' => 'File uploads',
'files_checksum_failed' => 'Files could not be checksummed',
'files_failed' => 'Files failed md5sum check',
'files_not_found' => 'Files not found',
'files' => 'Files',
'filesdescription' => 'File and media management',
'filesize' => 'File Size',
'filter_applied' => 'Filter Applied', // common?
'filter' => 'Filter',
'filteraction' => 'Action contains',
'filterapplied' => 'Current Filter',
'filterapply' => 'Apply filters',
'filterbymodule' => 'Filter By Originator',
'filtername' => 'Event name contains',
'filterreset' => 'Reset filters',
'filters' => 'Filters',
'filteruser' => 'Username is',
'find' => 'Find',
'first' => 'First',
'firstname' => 'First Name',
'forge' => 'Forge',
'forgotpwprompt' => 'Enter your admin username.  After submitting, an email containing new login information will be sent to the address associated with the entered username.',
'forgotpwtitle' => 'Recover login credentials for<br />%s',
'forums' => 'Forums',
'frontendlang' => 'Default Language',
'frontendwysiwyg' => 'Rich-Text-Editor for Frontend Use',

// G
'gcb_wysiwyg_help' => 'Enable rich-text-editing of Global Content Blocks',
'gcb_wysiwyg' => 'Enable GCB Rich-Text-Editing',
'gd_version' => 'GD version',
'general_operation_settings' => 'Miscellaneous',
'general_settings' => 'General',
'generic' => 'Generic',
'global_umask' => 'File/Directory Creation Mask (umask)',
'globalconfig' => 'System Settings',
'globalmetadata' => 'Global Metadata',
'go' => 'Go',
'gotit' => 'Got it!',
'goto' => 'Go to: %s',
'group' => 'Group',
'groupassignmentdescription' => 'Assign admin users to groups',
'groupassignments' => 'Group Membership',
'groupmanagement' => 'Group Management',
'groupname' => 'Group Name',
'grouppermissions' => 'Group Permissions',
'grouppermsdescription' => 'Set permissions and access levels for admin groups',
'groups' => 'Admin Groups',
'groupsdescription' => 'Manage admin groups',

// H
'handle_404' => 'Custom 404 Handling',
'handlers' => 'Handlers',
'hasdependents' => 'Has Dependents',
'headtags' => 'Head Tags',
'help_page_alias' => 'A unique identifier used in the \'pretty-URL\' for the page',
'help_page_disablewysiwyg' => 'Check this box to prevent rich-text-editing of page content-blocks. (Is there a sensible reason for doing that ??)',
'help_page_searchable' => 'Check this box to permit searching of this page during site-wide content searches',
'help_page_wantschildren' => 'Check this box if new page(s) may be created as a child of this page and/or current page(s) may be re-ordered to become a child of this page.',
'help_systeminformation' => 'The information displayed below is from a variety of locations, and summarized here so that you can conveniently find some of the information required when trying to diagnose a problem or request help with your CMS Made Simple&trade; installation.',
'help_themeexport' => 'Export the selected theme to an XML file, for sharing',
'help_themeimport' => 'Select a theme XML file that you received from another user, or downloaded',
'help' => 'Help',
'helpaddtemplate' => '<p>A template is what controls the look and feel of your site\'s content.<br />Create the layout here and also add your CSS in the stylesheet section to control the look of your various elements.</p>',
'helplisttemplate' => '<p>Here you can edit, delete, and create templates.<br />To create a new template, click on the <u>Add New Template</u> button.<br />If you wish to set all content pages to use the same template, click on the <u>Set All Content</u> link.<br />If you wish to duplicate a template, click on the <u>Copy</u> icon and you will be prompted to name the new duplicate template.</p>',
'helpwithsection' => '%s Help',
'hide_help_links_help' => 'Disable the module help link in page headers.',
'hide_help_links' => 'Hide Module Help Links',
'hidefrommenu' => 'Hide From Menu',
'home' => 'Home',
'homepage' => 'Start Page',
'hostname' => 'Host name',
'hour' => 'hour',
'hours' => 'hours',

// I
'idnotvalid' => 'The given id is not valid',
'ignorenotificationsfrommodules' => 'Ignore notifications from these modules',
'illegalcharacters' => 'Invalid characters in field %s.',
'image' => 'Image',
'importtheme' => 'Import Theme',
'inactive' => 'Inactive',
'indent' => 'Indent Pagelist to Emphasize Hierarchy',
'info_adduser' => 'Add a administrative new user account',
'info_autoalias' => 'If this field is empty, an alias will be created automatically.',
'info_changegroupperms' => 'Here you can specify the permission(s) of each admin user group.  Keep in mind: any admin user can belong to multiple admin groups.<br /><strong>Note:</strong> the &quot;Admin&quot; group is a special group and is automatically granted all permissions.',
'info_changeusergroup' => 'Group membership determines which permissions the user has, hence her/his capabilities in the admin console',
'info_cookies' => 'Some admin operations use cookies and/or track your IP address.',
'info_default_contenttype' => 'Applicable when adding new content objects, this control specifies the type that is selected by default.  Please ensure that the selected item is not one of the &quot;disallowed types&quot;.',
'info_deletepages' => 'Note: due to permission restrictions, some of the pages you selected for deletion might not be listed below',
'info_edeprecated_failed' => 'If E_DEPRECATED is enabled in your error reporting users will see a lot of warning messages that could affect the display and functionality',
'info_estrict_failed' => 'Some libraries that CMSMS uses do not work well with E_STRICT.  Please disable this before continuing',
'info_generate_cksum_file' => 'This will generate a checksum file and save it on your local computer for later validation.  This should be done prior to rolling out the website, and/or after any upgrade or major modification.',
'info_group_inactive' => 'This group is inactive.  Members of this group will not realize the permissions associated with the group',
'info_handlers' => 'Here you can select from plugins and modules which are potentially able to respond to this event. It is not certain that they can, and will, actually do so. Typically, modules are already configured to process all events which are relevant to them, and if so, that is not reflected here.',
//'info_mail_notset' => 'Mail settings have not yet been saved. Please ensure the information in Site Admin >> System Settings >> Mail Settings tab is correct for your server.',
//'info_mailtest' => 'This form will send a pre formatted email to the address you specify.<br />If you do not receive the mail you might need to re-check your settings.<br /><strong>Note:</strong> you might also want to check your spam folder.',
'info_membergroups' => 'A user may be a member of zero or more groups.  A user who is not a member of any group will still be able to log in to the admin console (if not login-barred), and change her/his own account details.',
'info_noalerts' => 'There is no alert at this time',
'info_noedituser' => 'Although this user account exists, your permissions do not permit you to manage that account',
'info_pagealias' => 'Specify a unique alias for this page.',
'info_pagedefaults' => 'This form allows specifying various options as to the initial settings when creating new content pages.  The items in this page have no effect when editing existing pages',
'info_preview_notice' => 'Warning: This preview panel allows you to navigate away from the initially previewed page. However, if you do so, you might experience unexpected behavior. If you navigate away from the initial display and return, you might not see the un-committed content until you make a change to the content in the main tab, and then reload this tab. When adding content, if you navigate away from this page, you will be unable to return, and must refresh this panel.',
'info_selectuser' => 'Toggle selection to perform actions on multiple users at once',
'info_sitedownexcludes' => 'A comma-separated list of IP addresses or networks.  Addresses can be specified in the following formats:<br />
1. xxx.xxx.xxx.xxx -- (exact IP address)<br />
2. xxx.xxx.xxx.[yyy-zzz] -- (IP address range)<br />
3. xxx.xxx.xxx.xxx/nn -- (nnn = number of bits, cisco style.  i.e:  192.168.0.100/24 = entire 192.168.0 class C subnet)',
'info_smarty_options' => 'The following options have effect only when the above caching options are enabled',
'info_target' => 'This option might used by the menu manager to indicate when and how new frames or windows should be opened.  Some menu manager templates might ignore this option.', //TODO relevant to Navigator module?
'info_templateuser' => 'This account is the template user account.  New users will be created using this account\'s settings',
'info_this_templateuser' => 'This account is set as the template user.  New accounts will inherit this users settings, and you can copy this users settings to any user account',
'info_user_active2' => 'Toggle this flag to preserve the user information, but prevent the user from logging in to the admin console',
'info_user_switch' => 'Test as this user',
'info_validation' => 'This will compare the checksums found in the uploaded file with the files on the current installation.  It can assist in finding problems with uploads, or exactly what files were modified if this system has been hacked.',
'insecure' => 'Insecure (HTTP)',
'installed_modules' => 'Installed Modules',
'installed' => 'Installed',
'installfileexists' => '<em><strong>Warning:</strong></em> The installation assistant file: %s still exists in the root directory.  As this could potentially be a security vulnerability, please delete it.',
'invalid_test' => 'Invalid test parameter value!',
'invalid' => 'Invalid',
'invalidalias' => 'The alias entered contains invalid characters. White space, / . and other punctuation characters are not permitted.',
'invalidcode_brace_missing' => 'Uneven number of braces',
'invalidcode' => 'Invalid code entered.',
'invalidemail' => 'The email address entered is invalid',
'ip_addr' => 'IP Address',
'irc' => 'IRC',
'item_name_contains' => 'Item Name Contains',
'itemid' => 'Item ID',
'itemname' => 'Item Name',
'itsbeensincelogin' => 'It has been %s since you last logged in',

// J
'jobsdescription' => 'Inspect background jobs',
'jobslabel' => 'Background Jobs',
'jsdisabled' => 'Sorry, this function requires that you have JavaScript enabled.',
'json_function' => 'JSON functions',

// L
'lang_settings_legend' => 'Language related settings',
'language' => 'Language',
'last_modified_at' => 'Last modified at',
'last_modified_by' => 'Last modified by',
'last' => 'Last',
'lastname' => 'Last Name',
'layout' => 'Presentation',
'layoutdescription' => 'Site content design functions',
'lines_in_error' => '%d lines with errors',
'lock_refresh' => 'Locks Refresh Interval (seconds)',
'lock_timeout' => 'Locks Time-out Interval (minutes)',
'login_admin' => 'Log in to website Admin&nbsp;Console',
'login_failed' => 'User Login Failed',
'login_info_needjs' => 'For login and the admin console to work properly, javascript must be enabled in your browser',
//'login_info_params' => 'For the admin console to work properly:<br />&#8729; popup windows must be allowed for %s<br />&#8729; cookies must be enabled in your browser.',
'login_info_params' => 'For the admin console to work properly, cookies must be enabled in your browser.',
'login_info_title' => 'Information',
'login_sitetitle' => 'Log in to<br />%s Admin&nbsp;Console',
'loginprompt' => 'Enter a valid user credential to get access to the Admin&nbsp;Console.',
'logintitle' => 'Log in to CMS Made Simple&trade;',
'loginto' => 'Log in to %s',
'logout' => 'Logout',
'logdescription' => 'View all or filtered admin log contents',
'loglabel' => 'Admin Log',
'lostpw' => 'Forgot your password?',
'lostpwemail' => '
<h3>Hello</h3>
<p>You have received this message because a request has been made to recover the password for website \'%s\' user account \'%s\'.</p>
<p>If you consider this is incorrect or sent to you in error, simply ignore this email.</p>
<p>Password recovery is not possible as such, instead a replacement password must be recorded. To initiate that, click the link (below) or paste it into the URL field of your web browser.</p>
<p>%s</p>',
'lostpwemailsubject' => '%s Password Recovery',

// M
//N/A PHP7+'magic_quotes_gpc_on' => 'Single-quote, double quote and backslash are escaped automatically. You can experience problems when saving templates',
//'magic_quotes_gpc' => 'Magic quotes for Get/Post/Cookie',
//'magic_quotes_runtime_on' => 'Most functions that return data will have quotes escaped with a backslash. You can experience problems',
//'magic_quotes_runtime' => 'Magic quotes in runtime',
//'mail_settings' => 'Email',
//'mail_testbody' => '<h2 style=&quot;color: green;&quot;>Greetings</h2><p>You are receiving this message from an installation of <strong>CMS Made Simple</strong>.  This message is proving the validity of the settings used for sending email messages.   If you are reading this message, then everything appears to be working fine.  However, if you did not solicit this email from a CMS Made Simple admin console, please contact the website administrator.</p>',
//'mail_testsubject' => 'CMSMS Mail Test message',
'main' => 'Main',
'mainmenu' => 'Main Menu',
'maintenance_warning' => 'The website is in maintenance mode! Are you sure you want to log out now?',
'master_admintheme' => 'Default Admin Theme',
'max_execution_time' => 'Maximum Execution Time',
'maximumversion' => 'Maximum Version',
'maximumversionsupported' => 'Maximum CMSMS Version Supported',
'md5_function' => 'md5 function',
'media_query_description' => '<strong>Notice:</strong> when Media Query is used, Media Type selection will be ignored.<br />Use any valid expression as recommended by <a href=&quot;http://www.w3.org/TR/css3-mediaqueries/&quot; rel=&quot;external&quot title=&quot;W3C&quot;>W3C</a>.',
'media_query' => 'Media Query',
'mediatype_' => 'None set : will affect everywhere ',
'mediatype_all' => 'all : Suitable for all devices.',
'mediatype_aural' => 'aural : Intended for speech synthesizers.',
'mediatype_braille' => 'braille : Intended for Braille tactile feedback devices.',
'mediatype_embossed' => 'embossed : Intended for paged Braille printers.',
'mediatype_handheld' => 'handheld : Intended for handheld devices',
'mediatype_print' => 'print : Intended for paged, opaque material and for documents viewed on screen in print preview mode.',
'mediatype_projection' => 'projection : Intended for projected presentations, for example projectors or print to transparencies.',
'mediatype_screen' => 'screen : Intended primarily for color computer screens.',
'mediatype_speech' => 'speech : Intended for speech synthesizers.',
'mediatype_tty' => 'tty : Intended for media using a fixed-pitch character grid, such as teletypes and terminals.',
'mediatype_tv' => 'tv : Intended for television-type devices.',
'mediatype' => 'Media Type',
'memory_limit' => 'PHP Effective Memory Limit',
'menu_bookmarks' => '[+]',
'menu' => 'Menu',
'menulabel_styles' => 'Page Styling',
'menulabel_templates' => 'Templates',
'menutitle_styles' => 'Manage frontend-page stylesheets and groups of them',
'menutitle_templates' => 'Manage layout templates and groups of them',
'menutext' => 'Menu Text',
//'metadata' => 'Metadata', //?
'minimumversion' => 'Minimum Version',
'minimumversionrequired' => 'Minimum CMSMS Version Required',
'minute' => 'minute',
'minutes' => 'minutes',
'missingdependency' => 'Missing dependency',
'missingparams' => 'Missing and/or invalid parameter(s)',
'missingtype' => 'Missing %s',
'modifyeventhandlers' => 'Modify Event Handlers', //also used for elements' title
'modifygroupassignments' => 'Modify Group Assignments',
'module_help' => 'Module Help',
'module_name' => 'Module Name',
'module_param_lang' => '<strong>Deprecated</strong> - Override the current language that is used for selecting translated strings.',
'module_setting' => 'Module Specific',
'module' => 'Module',
'moduleabout' => 'About the %s module',
'moduledecides' => 'Module Decides',
'moduleerrormessage' => 'Error message for %s module',
'modulehelp_english' => 'View In English',
'modulehelp_yourlang' => 'View in Your Language',
'modulehelp' => 'Help for the <em>%s</em> module',
'moduleinstalled' => 'Module already installed',
'moduleinstallmessage' => 'Install Message for %s Module',
'moduleinterface' => '%s Interface',
'modules' => 'Modules',
'modulesnotwritable' => 'The core modules folder <em>(and/or the uploads folder)</em> is not writeable, if you would like to install modules by uploading an XML file you need ensure that these folders have full read/write/execute permissions (chmod 777).  Safe mode might also be in effect.',
'moduleuninstallmessage' => 'Uninstall Message for %s Module',
'moduleupgraded' => 'Upgrade Successful',
'moduleupgradeerror' => 'There was an error upgrading the module.',
'months_counted' => '%s months',
'move' => 'Move',
'movecontent' => 'Move Pages',
'msg_default_charset' => 'The site default character-encoding is %s. A value other than UTF-8 may cause content problems.  Use at your own risk',
'msg_grantall_found' => 'Found a &quot;GRANT ALL&quot; statement that appears to be suitable',
//'msg_mailprefs_set' => 'Email settings saved',
'msg_notimedifference2' => 'No time difference found',
'msg_settemplateuser' => 'Template user account set',
'msg_userdeleted' => 'Selected user account successfully deleted.',
'msg_usersdeleted' => '%d users were deleted',
'msg_usersedited' => '%d users were modified',
'msg_usersettingscleared' => 'User settings cleared',
'msg_usersettingscopied' => 'User settings copied from template user account',
'msg_usersrepass' => '%d user-passwords were flagged for replacement',
'myaccount' => 'Account',
'myaccountdescription' => 'Update the details of your admin account',
'mybookmarks' => 'Bookmarks',
'mybookmarksdescription' => 'Manage your bookmarks',
'myprefs' => 'Me &amp; Mine',
'myprefsdescription' => 'Adjust the way this admin console works for you',
'mysettings' => 'Settings',
'mysettingsdescription' => 'Update your settings for this console',

// N
'n_a' => 'N/A',
'name' => 'Name',
'needpermissionto' => "You need the '%s' permission to perform that function.",
'never' => 'Never',
'new_version_avail_title' => 'Your CMSMS version is out of date',
'new_version_avail2' => '<strong>Notice:</strong> You are currently running CMSMS version %s. Version %s is available.  Please upgrade soon.',
'new_window' => 'new window',
'next' => 'Next',
'no_bookmarks' => 'No bookmark is recorded yet. You can add them by clicking the button below.',
'no_bulk_performed' => 'No bulk operation performed.',
'no_file_url' => 'None (Use URL Above)',
'no_files_scanned' => 'No file was scanned during the verification process (maybe the file is invalid)',
'no_permission' => 'You are not authorised to perform that function.',
'no' => 'No',
'noaccessto' => 'No Access to %s',
'nodefault' => 'No Default Selected',
'noentries' => 'No Entry',
'nofieldgiven' => 'No %s given!',
'nofiles' => 'No File',
'noncachable' => 'Non Cachable',
'none' => 'None',
'nopaging' => 'Show All Items',
'nopasswordforrecovery' => 'No email address is recorded for this user. Password recovery is not possible. Please contact your administrator.',
'nopasswordmatch' => 'Passwords do not match',
'nopluginabout' => 'No about information is available for this plugin',
'nopluginhelp' => 'No help is available for this plugin',
'norealdirectory' => 'No real directory given',
'norealfile' => 'No real file given',
'notifications_to_handle2' => 'You have %d unhandled notification(s)',
'notifications' => 'Notifications',
'notinstalled' => 'Not installed',
'notspecified' => 'Not specified / Empty',

// O
'of' => 'of',
'off' => 'Off',
'ok' => 'Ok',
'on' => 'On',
'onetimepassword' => 'Onetime Password',
'open_basedir_active' => 'No check because open basedir active',
'open_basedir' => 'PHP Open Basedir',
'open' => 'Open',
'options' => 'Options',
'order_too_large' => 'A page order cannot be larger than the number of pages in that level. Pages were not reordered.',
'order_too_small' => 'A page order cannot be zero. Pages were not reordered.',
'order' => 'Order',
'originator' => 'Originator',
'os_session_save_path' => 'No check because OS path',
'other' => 'Other',
'output_buffering' => 'PHP output_buffering',
'output' => 'Output',
'owner' => 'Owner',

// P
'page_reordered' => 'Page was successfully reordered.',
'page' => 'Page',
'pagedefaults' => 'Page Defaults',
'pagedefaultsdescription' => 'Set default values for new pages',
'pagedefaultsupdated' => 'Page default settings updated',
'pages_reordered' => 'Pages were successfully reordered',
'pages' => 'Pages',
'pagesdescription' => 'Add and edit site pages and other content',
'parameters' => 'Parameters',

'password_level' => 'Password Security Level',
'guess_medium' => 'Somewhat hard to guess', // OR 'Medium'
'guess_hard' => 'Quite hard to guess', // OR 'Strong'
'guess_vhard' => 'Very hard to guess', // OR 'Very Strong'
'username_settings' => 'User/Account Name Requirements',

'password' => 'Password',
'passwordagain' => 'Password (again)',
'passwordchange' => 'Please provide the new password',
'passwordchangedlogin' => 'Password changed.  Please log in using the new credentials.',
'perform_validation' => 'Perform Validation',
'performance_information' => 'Performance and Tuning Information (recommended settings, but not required)',
'period_ago' => 'ago',
'period_day' => 'day',
'period_days' => 'days',
'period_decade' => 'decade',
'period_decades' => 'decades',
'period_fmt' => '%d %s %s',
'period_fromnow' => 'from now',
'period_hour' => 'hour',
'period_hours' => 'hours',
'period_min' => 'minute',
'period_mins' => 'minutes',
'period_month' => 'month',
'period_months' => 'months',
'period_sec' => 'second',
'period_secs' => 'seconds',
'period_week' => 'week',
'period_weeks' => 'weeks',
'period_year' => 'year',
'period_years' => 'years',
'perm_Add_Pages' => 'Add Pages',
'perm_Add_Templates' => 'Add Templates',
'perm_Clear_Admin_Log' => 'Clear Admin Log',
'perm_Delete_News_Articles' => 'Delete News Articles', // should go in News
'perm_Manage_All_Content' => 'Manage All Content',
'perm_Manage_Groups' => 'Manage Groups',
'perm_Manage_My_Account' => 'Manage My Account',
'perm_Manage_My_Bookmarks' => 'Manage My Bookmarks',
'perm_Manage_My_Settings' => 'Manage My Settings',
'perm_Manage_Stylesheets' => 'Manage stylesheets',
'perm_Manage_Users' => 'Manage Users',
'perm_Modify_Any_Page' => 'Modify Any Page',
'perm_Modify_Events' => 'Modify Events',
'perm_Modify_Files' => 'Modify Files',
'perm_Modify_Modules' => 'Modify Modules',
'perm_Modify_News' => 'Modify News Articles', // should go in News.
'perm_Modify_Permissions' => 'Modify Permissions',
'perm_Modify_Site_Preferences' => 'Modify Site Preferences',
'perm_Modify_Templates' => 'Modify Templates',
'perm_Modify_User_Plugins' => 'Modify User-Tags',
'perm_Remove_Pages' => 'Remove Pages',
'perm_Reorder_Content' => 'Reorder Content',
'perm_View_Tag_Help' => 'View Plugins Help',
'perm_View_UserTag_Help' => 'View User Plugins Help',
'permdesc_Manage_All_Content' => 'A user with this permission can perform all tasks on any and all content pages',
'permdesc_Modify_Any_Page' => 'A user with this permission has additional editor privileges on all content pages',
'permission_information' => 'Permission Information',
'permission' => 'Permission',
'permissiondenied' => 'Permission denied',
'permissions' => 'Permissions',
'permissionschanged' => 'Permissions have been updated.',
'php_information' => 'PHP Information',
'phpversion' => 'Current PHP Version',
'pluginabout' => 'About the <em>%s</em> tag',
'pluginhelp' => 'Help for the <em>%s</em> tag',
'pluginmanagement' => 'Plugin Management',
'plugins' => 'Plugins',
'post_max_size' => 'Maximum Post Size',
'power_by' => 'Powered by',
'preferences' => 'Preferences',
'preferencesdescription' => 'Set site-wide preferences',
'prefsupdated' => 'User preferences have been updated.',
'prettyurls_noeffect' => 'Pretty URLS are not configured... this URL will have no effect',
'preview' => 'Preview',
'previewdescription' => 'Preview changes',
'previous' => 'Previous',
'profile' => 'Profile',

// R
'read' => 'Read',
'recentpages' => 'Recent Pages',
'recover_start' => 'Start recovery',
'recoveryemailsent' => 'Email sent to recorded address.  Please check your inbox for further instructions.',
'register_globals' => 'PHP register_globals',
'remote_connection_timeout' => 'Connection Timed Out!',
'remote_response_404' => 'Remote response: not found!',
'remote_response_error' => 'Remote response: error!',
'remote_response_ok' => 'Remote response: OK!',
'remove_alert' => 'Remove this alert',
'remove' => 'Remove',
'renewpwprompt' => 'Your password has expired. Enter and submit a replacement.',
'renewpwtitle' => 'Renew password for<br />%s',
'reorder' => 'Reorder',
'reorderpages' => 'Reorder Pages',
'replacepwemail' => '
<h3>Hello</h3>
<p>You have received this message because the password associated with website (%s) user account \'%s\' has been flagged for mandatory replacement.</p>
<p>If you consider this is sent to you in error, simply ignore this email.</p>
<p>To initiate replacement, click the link (below) or paste it into the URL field of your web browser.</p>
<p>%s</p>',
'replacepwemailsubject' => '%s Password Replacement',
'reset' => 'Reset',
'results' => 'Results',
'retirepass' => 'Force password replacement',
'revert' => 'Revert all changes',
'root' => 'Root',
'routesrebuilt' => 'The logged routes have been rebuilt',
'run' => 'Run',

// S
'safe_mode' => 'PHP Safe Mode',
'saveconfig' => 'Save Config',
'search_module' => 'Search Module',
'search_string_find' => 'Connection ok',
'secure' => 'Secure (HTTPS)',
'security_issue' => 'Security Issue',
'select_file' => 'Select File', //for filepicker??
'selectall' => 'Select All',
'selecteditems' => 'With Selected',
'selectgroup' => 'Select Group',
'send' => 'Send',
'sendmail_settings' => 'Sendmail Settings',
'sendtest' => 'Send',
'server_api' => 'Server API',
'server_cache_settings' => 'Server Cache',
'server_db_grants' => 'Check database access levels',
'server_db_type' => 'Server Database',
'server_db_version' => 'Server Database Version',
'server_information' => 'Server Information',
'server_os' => 'Server Operating System',
'server_software' => 'Server Software',
'server_time_diff' => 'Check for file system time differences',
'services' => 'Services',
'servicesdescription' => 'Utilities and activities for site visitors',
'session_save_path' => 'Session Save Path',
'session_use_cookies' => 'Sessions are allowed to use cookies',
'setfalse' => 'Set False',
'settemplate' => 'Set Template',
'settings_authentication' => 'Authentication',
'settings_authpassword' => 'Password',
'settings_authsecure' => 'Encryption method',
'settings_authusername' => 'User name',
'settings_linktext' => 'Go there',
//'settings_mailer' => 'Mailer',
//'settings_mailfrom' => 'From Address',
//'settings_mailfromuser' => 'From Name',
//'settings_sendmailpath' => 'Sendmail Path',
//'settings_smtpauth' => 'SMTP Authentication is Required',
//'settings_smtphost' => 'SMTP Hostname',
//'settings_smtpport' => 'SMTP Port',
//'settings_smtptimeout' => 'SMTP Time-out (seconds)',
//'settings_testaddress' => 'Email Address',
'settings_title' => '%s Settings',
'settings' => 'Settings',
'settrue' => 'Set True',
'setup' => 'Advanced',
'severity' => 'Status',
'show_bookmarks_message' => 'To show the bookmarks button in your Admin theme, set Me and Mine >> Preferences >> Enable  Bookmarks',
'showall' => 'Show All',
'showbookmarks' => 'Show Admin Bookmarks',
'showfilters' => 'Edit filter',
'showrecent' => 'Show Recently Used Pages',
'showsite' => 'Show Site',
'sibling_duplicate_order' => 'Two sibling pages cannot have the same order. Pages were not reordered.',
'signed_in' => 'Signed in as: %s',
'site_support' => 'Seek Help',
'siteadmin' => 'Site Admin',
'sitedown_settings' => 'Maintenance Mode',
'sitedownexcludeadmins' => '&quot;Maintenance Mode&quot; will not block users logged in to the CMSMS admin console',
'sitedownexcludes' => '&quot;Maintenance Mode&quot; will not block these IP addresses',
'sitedownmessage' => 'Maintenance Mode Message',
'sitedownwarning' => '<strong>Warning:</strong> Your site is currently showing a &quot;Site Down for Maintenance&quot; message. Remove file \'%s\' to resolve this.',
'sitelogo' => 'Site Logo/Icon',
'sitename' => 'Site Name',
'siteprefs_confirm' => 'Are you sure you want to alter these settings?',
'siteprefs' => 'Global Settings',
'siteprefsupdated' => 'Global settings updated',
'smarty_cachelife' => 'Cache Item Lifetime <em>(seconds)</em>',
'smarty_cachemodules' => 'Cache Module-Action Plugins',
'smarty_cacheusertags' => 'Cache User Plugins',
'smarty_compilecheck' => 'Do a Compilation Check',
'smarty_settings' => 'Smarty',
'smtp_settings' => 'SMTP Settings',
'sqlerror' => 'SQL error in %s',
'start_upgrade_process' => 'Start Upgrade Process',
'status' => 'Status',
'stylesheet' => 'Stylesheet', //USED?
'stylesheetcopied' => 'Stylesheet copied', //USED?
'stylesheetexists' => 'Stylesheet exists', //USED?
'stylesheetnotfound' => 'Stylesheet %d not found', //USED?
'stylesheets' => 'Stylesheets', //USED?
'stylesheetsdescription' => 'Stylesheet management is an advanced way to handle cascading stylesheets (CSS) separately from templates.', //USED?
'stylesheetstodelete' => 'These stylesheets will be deleted', //USED?
'subitems' => 'Subitems',
'subject' => 'Subject',
'submit' => 'Submit',
'submitdescription' => 'Save changes',
'success' => 'Success',
'switchuser' => 'Test as this user',
'syntax_editor_deftheme' => 'Default Theme for the Editor',
'syntax_editor_settings' => 'Textfile Editor',
'syntax_editor_theme' => 'Theme for Textfile Editor',
'syntax_editor_touse' => 'Textfile Editor',
'sysmain_aliasesfixed' => 'aliases fixed',
'sysmain_cache_status' => 'Cache status',
'sysmain_cache_type' => 'System parameters are cached by %s',
'sysmain_confirmclearlog' => 'Are you sure you want to clear the Admin Log?',
'sysmain_confirmfixaliases' => 'Are you sure you want to add aliases to pages without one?',
'sysmain_confirmfixtypes' => 'Are you sure you want to convert all with invalid content into standard content pages?',
'sysmain_confirmupdatehierarchy' => 'Are you sure you want to update page hierarchy positions?',
'sysmain_confirmupdateroutes' => 'Are you sure you want to refresh the route database',
'sysmain_content_status' => 'Content status',
'sysmain_database_status' => 'Database status',
'sysmain_fixaliases' => 'Add aliases where missed',
'sysmain_fixtypes' => 'Convert into standard content pages',
'sysmain_hierarchyupdated' => 'Page hierarchy positions updated',
'sysmain_nocontenterrors' => 'No content error was detected',
'sysmain_nostr_errors' => 'No structural error was detected in the database',
'sysmain_optimize' => 'Optimize',
'sysmain_optimizetables' => 'Optimize tables',
'sysmain_pagesfound' => '%d pages found',
'sysmain_pagesinvalidtypes' => '%d pages with invalid content type',
'sysmain_pagesmissinalias' => '%d pages without alias',
'sysmain_repair' => 'Repair',
'sysmain_repairtables' => 'Repair Tables',
'sysmain_str_error' => 'Structural error detected in table',
'sysmain_str_errors' => 'Structural errors detected in tables',
'sysmain_tablesfound' => '%d tables found, %d of them are not sequence-generators',
'sysmain_tablesoptimized' => 'Tables optimized',
'sysmain_tablesrepaired' => 'Tables repaired',
'sysmain_tipoptimizetables' => 'Trim storage space and improve I/O efficiency. Do this after deleting a large amount of data, or making many changes.',
'sysmain_tiprepairtables' => 'Repair possibly-corrupt tables. Backup tables before doing this.',
'sysmain_tipupdatehierarchy' => 'Update the hierarchy position of all content items. Do this after content pages have been relocated in the page structure.',
'sysmain_tipupdateroutes' => 'Re-populate the static routes table, from page content and modules.',
'sysmain_typesfixed' => '%d page content types fixed',
'sysmain_update' => 'Update',
'sysmain_updatehierarchy' => 'Update Page-Hierarchy Positions',
'sysmain_updateroutes' => 'Update Routes',
'sysmaintab_cache' => 'Cache',
'sysmaintab_changelog' => 'Changelog',
'sysmaintab_content' => 'Content',
'sysmaintab_database' => 'Database',
'system_cachelife' => 'System Cache Item Lifetime <em>(seconds)</em>',
'system_cachetype' => 'System Parameters Cache',
'system_verification' => 'System Verification',
'systeminfo_copy_paste' => 'Please copy and paste this selected text into your forum posting',
'systeminfo' => 'System Information',
'systeminfodescription' => 'Display various pieces of information about this system that might be useful in diagnosing problems',
'systemmaintenance' => 'System Maintenance',
'systemmaintenancedescription' => 'Various functions for maintaining the health of this system. You can also browse the changelog for all releases.',

// T
'tag' => 'Tag',
'tagdescription' => 'System plugins/tags',
'tags' => 'Plugins',
'team' => 'Team',
'template' => 'Template', //USED?
'templateexists' => 'A template with that name already exists', //USED?
'templates' => 'Templates', //USED?
'tempnam_function' => 'tempnam function',
'test_allow_browser_cache' => 'Allowing browsers to cache pages will improve performance by not requiring this system to serve the page on repeated visits to a page.',
'test_allow_url_fopen_failed' => 'When allow URL fopen is disabled you will not be able to accessing URL object like file using the ftp or http protocol.',
'test_auto_clear_cache_age' => 'The system should be configured to destroy old temporary files after a reasonable time to improve performance and minimize disk space requirements',
'test_browser_cache_expiry' => 'A longer value will have increased performance benefits',
'test_check_open_basedir_failed' => 'Open basedir restrictions are in effect. You might have difficulty with some add-on functionality with this restriction',
'test_curl' => 'Test for curl availability',
'test_curlversion' => 'Test Curl Version',
'test_db_timedifference_msg' => 'Detected a difference of at least %d seconds.  This might affect the system dramatically',
'test_db_timedifference' => 'Testing for time difference in the database',
'test_eall_failed' => 'E_ALL is not enabled in error reporting, this could mean that you might not see important problems in your error log.',
'test_edeprecated_failed' => 'E_DEPRECATED is enabled',
'test_error_eall' => 'Testing if E_ALL is enabled in php.ini error_reporting',
'test_error_edeprecated' => 'Testing if E_DEPRECATED is enabled in php.ini error_reporting',
'test_error_estrict' => 'Testing if E_STRICT is enabled in php.ini error_reporting',
'test_estrict_failed' => 'E_STRICT is enabled in the error_reporting',
'test_file_timedifference_msg' => 'Detected a difference of at least %d seconds.  This might affect the system dramatically',
'test_file_timedifference' => 'Testing for time difference in the file system',
'test_remote_url_failed' => 'You will probably not be able to open a file on a remote web server.',
'test_remote_url' => 'Test for remote URL',
'test_smarty_compilecheck' => 'Disabling template-change checks speeds up operation, but if a template is changed, such change will not be displayed until the pages-cache is cleared or times out (per the cache-lifetime setting)',
'test' => 'Test',
'testmsg_success' => 'Test message sent... check your inbox.',
'text_changeowner' => 'Set Selected Pages to a Different User',
'text_settemplate' => 'Apply a different template to selected pages',
'theme' => 'Theme',
'thumbnail_height' => 'Thumbnail Height',
'thumbnail_width' => 'Thumbnail Width',
'thumbnail' => 'Thumbnail',
'title_apply_usrplg' => 'Save this plugin, and continue editing',
'title_event_description' => 'This column contains brief descriptions for each event',
'title_event_handlers' => 'This column indicates the number of handlers for each event (if any)',
'title_event_name' => 'This column contains a unique name for each event',
'title_event_originator' => 'This column contains the name of the module that sends the event, or &quot;Core&quot; which indicates that the event is sent by a core operation of CMSMS',
'title_hierselect_select' => 'Select a content page. If the selected page has children a new dropdown will appear.  Selecting "None" indicates that the selection stops with the value of the previous select if any.',
'title_hierselect' => 'This field displays the selected content page. The actual string displayed (page title or menu text) is dependent on user and site preference.',
//'title_mailtest' => 'Mail Test',
'title_mysettings' => 'Your settings',
'title_themeexport' => 'Export theme to XML',
'title_themeimport' => 'Import theme from XML',
'title' => 'Title',
'togglemenu' => 'Toggle %s submenu',
'tools' => 'Tools',
'tplhelp_page' => 'TODO',
'troubleshooting' => '(Troubleshooting)',
'true' => 'True',
'type' => 'Type',
'typenotvalid' => 'Type is not valid',

// U
'unknown' => 'Unknown',
'unlimited' => 'Unlimited',
'unrestricted' => 'Unrestricted',
'untested' => 'Not Tested',
'up' => 'Up',
'updated_usrplg' => 'The plugin was successfully updated.',
'updateperm' => 'Update Permissions',
'upload_cksum_file' => 'Upload Checksum File',
'upload_filetobig' => 'This file is too large to upload',
'upload_largeupload' => 'The total size of files to upload exceeds the limit specified in the PHP configuration',
'upload_max_filesize' => 'Maximum Upload Size',
'upload_plugin_file' => 'Upload Tag File',
'uploaded_file' => 'Uploaded File',
'uploadfile' => 'Upload File',
'url' => 'URL',
'use_name' => 'In the parent page dropdown, show the page title instead of the menu text',
'use_wysiwyg' => 'Use rich-text-editor', //see also 'usewysiwyg'
'user_created' => 'Custom Bookmarks',
'user_login' => 'User Login',
'user_logout' => 'User Logout',
'user' => 'User',
'useraccount' => 'Profile',
'userdisabled' => 'User account disabled',
'usermanagement' => 'User Management',
'username' => 'Username',
'usernameincorrect' => 'Unrecognised username and/or password',
'usernotfound' => 'User Not Found',
'userprefs' => 'Settings',
'users' => 'Admin Users',
'usersassignedtogroup' => 'Users Assigned to Group %s',
'usersdescription' => 'Manage admin users',
'usersettings' => 'User Settings',
'usersgroups' => 'People', //'Users &amp; Groups',
'usersgroupsdescription' => 'User and user-group related functions',
'usertags_description' => 'Plugins added by authorised users',
'usertags' => 'User Plugins',
'usewysiwyg' => 'Use rich-text-editor for content', //see also 'use_wysiwyg'

// V
'validate' => 'Validate',
'version' => 'Version',
'view_page' => 'View this page in a new window',
'view' => 'Display',
'viewdescription' => 'Examine places and things',
'viewsite' => 'Main Site',

// W
'warn_addgroup' => 'Creating a new group does not assign any permissions. You will need to assign permissions to the new group in a separate step.',
'warn_nosefurl' => 'SEO-friendly (\'pretty\') URLs are not enabled. Settings for those are not displayed.',
//'warning_mail_settings' => 'The website mailer settings have not been configured. This could interfere with the ability of this website to send email messages. You should go to <a href="%s">Extensions >> OutMailer</a> and configure the email settings with the information provided by your host.',
'warning_safe_mode' => '<strong><em>WARNING:</em></strong> PHP Safe mode is enabled. This will cause difficulty with files uploaded via the web browser interface, including images, theme and module XML packages.  You are advised to contact your site administrator to see about disabling safe mode.',
'warning_upgrade_info1' => 'The website is now running schema version %s and needs to be upgraded to version %s',
'warning_upgrade_info2' => 'Please click the following link: %s.',
'warning_upgrade' => '<em><strong>Warning:</strong></em> CMSMS is in need of an upgrade!',
'welcome_user' => 'Signed in as', //deprecated since 2.99 For compability with themes which run on 2.2
'welcomemsg' => 'Welcome %s',
'when' => 'When',
'wiki' => 'Wiki',  // backwards compatibility
'wontdeletetemplateinuse' => 'These templates are in use and will not be deleted',
'write' => 'Write',
'wysiwygtouse' => 'Rich-Text Editor',
'wysiwyg_deftheme' => 'Default Theme for Admin Rich-Text Editor',
'wysiwyg_theme' => 'Theme for Rich-Text Editor',

// X
'xml_function' => 'Basic XML (expat) support',
'xml' => 'XML',
'xmlreader_class' => 'Checking for the XMLReader class',

// Y
'years_1' => '1 year',
'years_counted' => '%s years',
'yes' => 'Yes',
'your_ipaddress' => 'Your IP address is',

/*
// Serving third-party modules
'added_template' => 'Added Template',
'addtemplate' => 'Add New Template',

'deleted_template' => 'Deleted Template',
'deletetemplate' => 'Delete Template',
'deletetemplates' => 'Delete Templates',

'edittemplate' => 'Edit Template',
'edittemplatesuccess' => 'Template updated',

'template' => 'Template',
'templateexists' => 'A template with that name already exists',
'templates' => 'Templates',
*/

] + $lang;

/* exported to CmsContentManager-module lang/realm
'help_content_accesskey' => 'Specify an access key character (single character) that can be used to access this content page.  This is useful for accessibility purposes',
'help_content_active' => 'Inactive pages cannot be displayed, or appear in the navigation',
'help_content_addteditor' => 'This field allows you to specify other admin users who will be able to edit this content page.  This field is useful when editors have limited access privileges, and need the ability to edit different pages.',
'help_content_cachable' => 'Check this box if the content of this page is to be cached on the server and on the browser.  If a page is not cachable, then it must be regenerated on each and every request.  Setting a page to be cachable can be a significant performance boost for most websites.',
'help_content_content_en' => 'This is the default content block.  Here you enter the content that will be most prominently displayed on the content page',
'help_content_default' => 'Check this box if this is the website\'s default page',
'help_content_disablewysiwyg' => 'This checkbox is used to indicate that regardless of settings in the page template and user settings, no rich-text-editor should be used on any text area on this page.  This is useful when the page uses a standard site page template, but contains either hard-coded HTML, smarty logic, or only displays the output of an independently-developed module',
'help_content_extra1' => 'This field is used for advanced navigation or template logic. Consult your site developer to see if you need to edit this value when managing content',
'help_content_extra2' => 'This field is used for advanced navigation or template logic. Consult your site developer to see if you need to edit this value when managing content',
'help_content_extra3' => 'This field is used for advanced navigation or template logic. Consult your site developer to see if you need to edit this value when managing content',
'help_content_image' => 'This field allows you to associate an image with the content page.  The images must have already been uploaded to the website in a directory specified by the website designer.  The image may optionally be displayed on the page, or used when building a navigation',
'help_content_menutext' => 'This is the text that represents this page in the navigation.  The menu text is also used to create a page alias if none is specified.',
'help_content_owner' => 'This field allows you to adjust the owner of this content item.  It is useful when giving access to this page to an editor with less access privileges',
'help_content_pagedata' => 'This field is used for Smarty tags or logic that are specific to this content page, will probably not generate any direct output, and must be processed before anything else on the page',
'help_content_pagemeta' => 'This field is used for HTML meta tags. They will be merged with the default meta tags and inserted in the head section of the generated HTML page.',
'help_content_parent' => 'Select an existing page in the content hierarchy which will be the parent page for this content page.  This relationship is used when building a navigation',
'help_content_secure' => 'Check this box to indicate that this page may only be accessed via a secure (encrypted) connection i.e. via HTTPS, regardless of the arrangements for the site as a whole. This option is deprecated, and will be removed.',
'help_content_showinmenu' => 'Select whether this page will be visible (by default) in the navigation.',
*/

/* exported to 'events' realm
'event_desc_addglobalcontentpost' => 'Sent after a new global content block is created',
'event_desc_addglobalcontentpre' => 'Sent before a new global content block is created',
'event_desc_addgrouppost' => 'Sent after a new group is created',
'event_desc_addgrouppre' => 'Sent before a new group is created',
'event_desc_addstylesheetpost' => 'Sent after a new stylesheet is created',
'event_desc_addstylesheetpre' => 'Sent before a new stylesheet is created',
'event_desc_addtemplatepost' => 'Sent after a new template is created',
'event_desc_addtemplatepre' => 'Sent before a new template is created',
'event_desc_addtemplatetypepost' => 'Sent after a template type definition is saved to the database',
'event_desc_addtemplatetypepre' => 'Sent prior to a template type definition being saved to the database',
'event_desc_adduserpost' => 'Sent after a new user is created',
'event_desc_adduserpre' => 'Sent before a new user is created',
'event_desc_changegroupassignpost' => 'Sent after group assignments are saved',
'event_desc_changegroupassignpre' => 'Sent before group assignments are saved',
'event_desc_contentdeletepost' => 'Sent after content is deleted from the system',
'event_desc_contentdeletepre' => 'Sent before content is deleted from the system',
'event_desc_contenteditpost' => 'Sent after edits to content are saved',
'event_desc_contenteditpre' => 'Sent before edits to content are saved',
'event_desc_contentpostcompile' => 'Sent after content has been processed by Smarty',
'event_desc_contentpostrender' => 'Sent before the combined HTML is sent to the browser',
'event_desc_contentprecompile' => 'Sent before content is sent to Smarty for processing',
'event_desc_contentprerender' => 'Sent before any Smarty processing is performed.',
'event_desc_contentstylesheet' => 'Sent before the stylesheet is sent to the browser',
'event_desc_deleteglobalcontentpost' => 'Sent after a global content block is deleted from the system',
'event_desc_deleteglobalcontentpre' => 'Sent before a global content block is deleted from the system',
'event_desc_deletegrouppost' => 'Sent after a group is deleted from the system',
'event_desc_deletegrouppre' => 'Sent before a group is deleted from the system',
'event_desc_deletestylesheetpost' => 'Sent after a stylesheet is deleted from the system',
'event_desc_deletestylesheetpre' => 'Sent before a stylesheet is deleted from the system',
'event_desc_deletetemplatepost' => 'Sent after a template is deleted from the system',
'event_desc_deletetemplatepre' => 'Sent before a template is deleted from the system',
'event_desc_deletetemplatetypepost' => 'Sent after a template type definition is deleted',
'event_desc_deletetemplatetypepre' => 'Sent prior to a template type definition being deleted',
'event_desc_deleteuserpost' => 'Sent after a user is deleted from the system',
'event_desc_deleteuserpre' => 'Sent before a user is deleted from the system',
'event_desc_editglobalcontentpost' => 'Sent after edits to a global content block are saved',
'event_desc_editglobalcontentpre' => 'Sent before edits to a global content block are saved',
'event_desc_editgrouppost' => 'Sent after edits to a group are saved',
'event_desc_editgrouppre' => 'Sent before edits to a group are saved',
'event_desc_editstylesheetpost' => 'Sent after edits to a stylesheet are saved',
'event_desc_editstylesheetpre' => 'Sent before edits to a stylesheet are saved',
'event_desc_edittemplatepost' => 'Sent after edits to a template are saved',
'event_desc_edittemplatepre' => 'Sent before edits to a template are saved',
'event_desc_edittemplatetypepost' => 'Sent after a template type definition is saved',
'event_desc_edittemplatetypepre' => 'Sent before a template type definition is saved',
'event_desc_edituserpost' => 'Sent after edits to a user are saved',
'event_desc_edituserpre' => 'Sent before edits to a user are saved',
'event_desc_globalcontentpostcompile' => 'Sent after a global content block has been processed by Smarty',
'event_desc_globalcontentprecompile' => 'Sent before a global content block is sent to Smarty for processing',
'event_desc_loginfailed' => 'Sent after a user failed to login into the Admin panel',
'event_desc_loginpost' => 'Sent after a user logs into the Admin panel',
'event_desc_logoutpost' => 'Sent after a user logs out of the Admin panel',
'event_desc_lostpassword' => 'Sent when the lost password form is submitted',
'event_desc_lostpasswordreset' => 'Sent when the lost password form is submitted',
'event_desc_metadatapostrender' => 'Sent from the metadata plugin after page metadata has been processed via Smarty',
'event_desc_metadataprerender' => 'Sent from the metadata plugin before any processing has occurred',
'event_desc_moduleinstalled' => 'Sent after a module is installed',
'event_desc_moduleuninstalled' => 'Sent after a module is uninstalled',
'event_desc_moduleupgraded' => 'Sent after a module is upgraded',
'event_desc_pagebodyprerender' => 'Sent before the page content after the head section (if any) is populated by Smarty',
'event_desc_pagebodypostrender' => 'Sent after the page content after the head section (if any) is populated by Smarty',
'event_desc_pageheadprerender' => 'Sent before the page head section is populated by Smarty',
'event_desc_pageheadpostrender' => 'Sent after the page head section is populated by Smarty',
'event_desc_pagetopprerender' => 'Sent before the page-top (from page start to &lt;head&gt;) is populated by Smarty',
'event_desc_pagetoppostrender' => 'Sent after the page-top (from page start to &lt;head&gt;) is populated by Smarty',
'event_desc_postrequest' => 'Sent at the end of processing each admin or frontend request',
'event_desc_smartypostcompile' => 'Sent after any content destined for Smarty has been processed',
'event_desc_smartyprecompile' => 'Sent before any content destined for Smarty is sent for processing',
'event_desc_stylesheetpostcompile' => 'Sent after a stylesheet is compiled through Smarty',
'event_desc_stylesheetpostrender' => 'Sent after a stylesheet is passed through Smarty, but before cached to disk',
'event_desc_stylesheetprecompile' => 'Sent before a stylesheet is compiled through Smarty',
'event_desc_templatepostcompile' => 'Sent after a template has been processed by Smarty',
'event_desc_templateprecompile' => 'Sent before a template is sent to Smarty for processing',
'event_desc_templateprefetch' => 'Sent before a template is fetched from Smarty',

'event_help_addglobalcontentpost' => "<h4>Parameters</h4>
<ul>
<li>'global_content' - Reference to the affected global content block object.</li>
</ul>
",
'event_help_addglobalcontentpre' => "<h4>Parameters</h4>
<ul>
<li>'global_content' - Reference to the affected global content block object.</li>
</ul>
",
'event_help_addgrouppost' => "<h4>Parameters</h4>
<ul>
<li>'group' - Reference to the affected group object.</li>
</ul>
",
'event_help_addgrouppre' => "<h4>Parameters</h4>
<ul>
<li>'group' - Reference to the affected group object.</li>
</ul>
",
'event_help_addstylesheetpost' => "<h4>Parameters</h4>
<ul>
<li>'stylesheet' - Reference to the affected stylesheet object.</li>
</ul>
",
'event_help_addstylesheetpre' => "<h4>Parameters</h4>
<ul>
<li>'stylesheet' - Reference to the affected stylesheet object.</li>
</ul>
",
'event_help_addtemplatepost' => "<h4>Parameters</h4>
<ul>
<li>'template' - Reference to the affected template object.</li>
</ul>
",
'event_help_addtemplatepre' => "<h4>Parameters</h4>
<ul>
<li>'template' - Reference to the affected template object.</li>
</ul>
",
'event_help_addtemplatetypepost' => "<h4>Parameters</h4>
<ul>
  <li>'TemplateType' - Reference to the affected template type object.</li>
</ul>",
'event_help_addtemplatetypepre' => "<h4>Parameters</h4>
<ul>
  <li>'TemplateType' - Reference to the affected template type object.</li>
</ul>",
/*
'event_help_adduserpluginpost' => '<h4>Parameters</h4>
<ul>
<li>None</li>
</ul>
',
'event_help_adduserpluginpre' => '<h4>Parameters</h4>
<ul>
<li>None</li>
</ul>
',
* /
'event_help_adduserpost' => "<h4>Parameters</h4>
<ul>
<li>'user' - Reference to the affected user object.</li>
</ul>
",
'event_help_adduserpre' => "<h4>Parameters</h4>
<ul>
<li>'user' - Reference to the affected user object.</li>
</ul>
",
'event_help_changegroupassignpost' => "<h4>Parameters></h4>
<ul>
<li>'group' - Reference to the affected group object.</li>
<li>'users' - Array of references to user objects now belonging to the affected group.</li>
</ul>
",
'event_help_changegroupassignpre' => "<h4>Parameters></h4>
<ul>
<li>'group' - Reference to the group object.</li>
<li>'users' - Array of references to user objects belonging to the group.</li>
</ul>
",
'event_help_contentdeletepost' => "<h4>Parameters</h4>
<ul>
<li>'content' - Reference to the affected content object.</li>
</ul>
",
'event_help_contentdeletepre' => "<h4>Parameters</h4>
<ul>
<li>'content' - Reference to the affected content object.</li>
</ul>
",
'event_help_contenteditpost' => "<h4>Parameters</h4>
<ul>
<li>'content' - Reference to the affected content object.</li>
</ul>
",
'event_help_contenteditpre' => "<h4>Parameters</h4>
<ul>
<li>'global_content' - Reference to the affected content object.</li>
</ul>
",
'event_help_contentpostcompile' => "<h4>Parameters</h4>
<ul>
<li>'content' - Reference to the affected content text.</li>
</ul>
",
'event_help_contentpostrender' => "<h4>Parameters</h4>
<ul>
<li>'content' - Reference to the html text.</li>
</ul>
",
'event_help_contentprecompile' => "<h4>Parameters</h4>
<ul>
<li>'content' - Reference to the affected content text.</li>
</ul>
",
'event_help_contentprerender' => "<h4>Parameters</h4>
<ul>
<li>'content' - Reference to the affected content object..</li>
</ul>
",
'event_help_contentstylesheet' => "<h4>Parameters</h4>
<ul>
<li>'content' - Reference to the affected stylesheet text.</li>
</ul>
",
'event_help_deleteglobalcontentpost' => "<h4>Parameters</h4>
<ul>
<li>'global_content' - Reference to the affected global content block object.</li>
</ul>
",
'event_help_deleteglobalcontentpre' => "<h4>Parameters</h4>
<ul>
<li>'global_content' - Reference to the affected global content block object.</li>
</ul>
",
'event_help_deletegrouppost' => "<h4>Parameters</h4>
<ul>
<li>'group' - Reference to the affected group object.</li>
</ul>
",
'event_help_deletegrouppre' => "<h4>Parameters</h4>
<ul>
<li>'group' - Reference to the affected group object.</li>
</ul>
",
'event_help_deletestylesheetpost' => "<h4>Parameters</h4>
<ul>
<li>'stylesheet' - Reference to the affected stylesheet object.</li>
</ul>
",
'event_help_deletestylesheetpre' => "<h4>Parameters</h4>
<ul>
<li>'stylesheet' - Reference to the affected stylesheet object.</li>
</ul>
",
'event_help_deletetemplatepost' => "<h4>Parameters</h4>
<ul>
<li>'template' - Reference to the affected template object.</li>
</ul>
",
'event_help_deletetemplatepre' => "<h4>Parameters</h4>
<ul>
<li>'template' - Reference to the affected template object.</li>
</ul>
",
'event_help_deletetemplatetypepost' => "<h4>Parameters</h4>
<ul>
  <li>'TemplateType' - Reference to the affected template type object.</li>
</ul>",
'event_help_deletetemplatetypepre' => "<h4>Parameters</h4>
<ul>
  <li>'TemplateType' - Reference to the affected template type object.</li>
</ul>",
/*
'event_help_deleteuserpluginpost' => '<h4>Parameters</h4>
<ul>
<li>None</li>
</ul>
',
'event_help_deleteuserpluginpre' => '<h4>Parameters</h4>
<ul>
<li>None</li>
</ul>
',
* /
'event_help_deleteuserpost' => "<h4>Parameters</h4>
<ul>
<li>'user' - Reference to the affected user object.</li>
</ul>
",
'event_help_deleteuserpre' => "<h4>Parameters</h4>
<ul>
<li>'user' - Reference to the affected user object.</li>
</ul>
",
'event_help_editglobalcontentpost' => "<h4>Parameters</h4>
<ul>
<li>'global_content' - Reference to the affected global content block object.</li>
</ul>
",
'event_help_editglobalcontentpre' => "<h4>Parameters</h4>
<ul>
<li>'global_content' - Reference to the affected global content block object.</li>
</ul>
",
'event_help_editgrouppost' => "<h4>Parameters</h4>
<ul>
<li>'group' - Reference to the affected group object.</li>
</ul>
",
'event_help_editgrouppre' => "<h4>Parameters</h4>
<ul>
<li>'group' - Reference to the affected group object.</li>
</ul>
",
'event_help_editstylesheetpost' => "<h4>Parameters</h4>
<ul>
<li>'stylesheet' - Reference to the affected stylesheet object.</li>
</ul>
",
'event_help_editstylesheetpre' => "<h4>Parameters</h4>
<ul>
<li>'stylesheet' - Reference to the affected stylesheet object.</li>
</ul>
",
'event_help_edittemplatepost' => "<h4>Parameters</h4>
<ul>
<li>'template' - Reference to the affected template object.</li>
</ul>
",
'event_help_edittemplatepre' => "<h4>Parameters</h4>
<ul>
<li>'template' - Reference to the affected template object.</li>
</ul>
",
'event_help_edittemplatetypepost' => "<h4>Parameters</h4>
<ul>
  <li>'TemplateType' - Reference to the affected template type object.</li>
</ul>",
'event_help_edittemplatetypepre' => "<h4>Parameters</h4>
<ul>
  <li>'TemplateType' - Reference to the affected template type object.</li>
</ul>",
/*
'event_help_edituserpluginpost' => '<h4>Parameters</h4>
<ul>
<li>None</li>
</ul>
',
'event_help_edituserpluginpre' => '<h4>Parameters</h4>
<ul>
<li>None</li>
</ul>
',
* /
'event_help_edituserpost' => "<h4>Parameters</h4>
<ul>
<li>'user' - Reference to the affected user object.</li>
</ul>
",
'event_help_edituserpre' => "<h4>Parameters</h4>
<ul>
<li>'user' - Reference to the affected user object.</li>
</ul>
",
'event_help_globalcontentpostcompile' => "<h4>Parameters</h4>
<ul>
<li>'global_content' - Reference to the affected global content block text.</li>
</ul>
",
'event_help_globalcontentprecompile' => "<h4>Parameters</h4>
<ul>
<li>'global_content' - Reference to the affected global content block text.</li>
</ul>
",
'event_help_loginfailed' => "<h4>Parameters</h4>
<ul>
  <li>'user' - (string) The username of the failed login attempt.</li>
</ul>",
'event_help_loginpost' => "<h4>Parameters</h4>
<ul>
<li>'user' - Reference to the affected user object.</li>
</ul>
",
'event_help_logoutpost' => "<h4>Parameters</h4>
<ul>
<li>'user' - Reference to the affected user object.</li>
</ul>
",
'event_help_lostpassword' => "<h4>Parameters</h4>
<ul>
<li>'username' - The username entered in the lostpassword form.</li>
</ul>
",
'event_help_lostpasswordreset' => "<h4>Parameters</h4>
<ul>
<li>'uid' - The integer userid for the account.</li>
<li>'username' - The username for the reset account.</li>
<li>'ip' - The IP address of the client that performed the reset.</li>
</ul>
",
'event_help_moduleinstalled' => '<h4>Parameters</h4>
<ul>
<li>None</li>
</ul>
',
'event_help_moduleuninstalled' => '<h4>Parameters</h4>
<ul>
<li>None</li>
</ul>
',
'event_help_moduleupgraded' => '<h4>Parameters</h4>
<ul>
<li>None</li>
</ul>
',
'event_help_smartypostcompile' => "<h4>Parameters</h4>
<ul>
<li>'content' - Reference to the affected text.</li>
</ul>
",
'event_help_smartyprecompile' => "<h4>Parameters</h4>
<ul>
<li>'content' - Reference to the affected text.</li>
</ul>
",
'event_help_stylesheetpostcompile' => '<h4>Parameters</h4>
<ul>
<li>None</li>
</ul>
',
'event_help_stylesheetpostrender' => "<h4>Parameters</h4>
<ul>
<li>'content' - Reference to the stylesheet text.</li>
</ul>
",
'event_help_stylesheetprecompile' => '<h4>Parameters</h4>
<ul>
<li>None</li>
</ul>
',
'event_help_templatepostcompile' => "<h4>Parameters</h4>
<ul>
<li>'template' - Reference to the affected template text.</li>
<li>'type' - The type of template call.  i.e: template for a whole template, tpl_head, tpl_body, or tpl_top for a partial template.</li>
</ul>
",
'event_help_templateprecompile' => "<h4>Parameters</h4>
<ul>
<li>'template' - Reference to the affected template text.</li>
<li>'type' - The type of template call.  i.e: template for a whole template, tpl_head, tpl_body, or tpl_top for a partial template.</li>
</ul>
",
'event_help_metadatapostrender' => "<h4>Parameters</h4>
<ul>
<li>'content_id' - Page numeric identifier.</li>
<li>'html' - Reference to processed metadata (string) which may be amended as appropriate.</li>
</ul>
",
'event_help_metadataprerender' => "<h4>Parameters</h4>
<ul>
<li>'content_id' - Page numeric identifier.</li>
<li>'showbase' - Reference to boolean variable: whether to show a base tag.</li>
<li>'html' - Reference to string which may be populated/amended with metadata as appropriate.</li>
</ul>
",
'event_help_pagebodyprerender' => "<h4>Parameters</h4>
<ul>
<li>'content' - The page content-object.</li>
<li>'html' - Reference to the (string) content (if any) of the page-section.</li>
</ul>
",
'event_help_pagebodypostrender' => "<h4>Parameters</h4>
<ul>
<li>'content' - The page content-object.</li>
<li>'html' - Reference to the (string) content (if any) of the page-section.</li></ul>
",
'event_help_pageheadprerender' => "<h4>Parameters</h4>
<ul>
<li>'content' - The page content-object.</li>
<li>'html' - Reference to the (string) content (if any) of the page-section.</li></ul>
",
'event_help_pageheadpostrender' => "<h4>Parameters</h4>
<ul>
<li>'content' - The page content-object.</li>
<li>'html' - Reference to the (string) content (if any) of the page-section.</li></ul>
",
'event_help_pagetopprerender' => "<h4>Parameters</h4>
<ul>
<li>'content' - The page content-object.</li>
<li>'html' - Reference to the (string) content (if any) of the page-section.</li></ul>
",
'event_help_pagetoppostrender' => "<h4>Parameters</h4>
<ul>
<li>'content' - The page content-object.</li>
<li>'html' - Reference to the (string) content (if any) of the page-section.</li></ul>
",
'event_help_postrequest' => '<h4>Parameters</h4>
<ul>
<li>None</li>
</ul>',
'event_help_templateprefetch' => '<h4>Parameters</h4>
<ul>
<li>None</li>
</ul>',
*/
