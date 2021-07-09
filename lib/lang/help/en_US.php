<?php

$lang = [
// H
'help_group_permissions' => <<<'EOS'
<h4>CMSMS Admin Permission Model</h4>
<ul>
<li>CMSMS Uses a system of named permissions. Access to these permissions determines a users ability to perform different functions in the CMSMS admin console.</li>
<li>The CMSMS core creates several permissions on installation <em>(occasionally permissions are added or deleted during an upgrade process)</em>. Non-core modules might create additional permissions.</li>
<li>Permissions are associated with user groups. An authorized user can adjust the permissions that are associated with certain member groups <em>(including the permission to change a group permissions)</em>.</li><li>The <strong>Admin</strong> group is a special group. Members of this group will have all permissions. Membership of that group may be authorized only by the website's 'super' administrator, an account created during CMSMS installation.</li>
<li>Admin user accounts can be members of zero or more groups. It might be possible for a user account that is not a member of any groups to still perform various functionality <em>(please read about ownership and additional-editors in the Content Manager help, and Design Manager help)</em>.</li>
</ul>
EOS
,

// U
'user_active' => 'If disabled, the user will be ignored, but the user\'s data will be preserved',
'user_admincallout' => 'If enabled, administrative bookmarks <em>(bookmarks)</em> will be enabled allowing you to manage a list of frequently used actions in the admin console.',
'user_admintheme' => 'Select an admin console theme to use. Themes may have different appearances and/or menu layouts, work differently on mobile devices, or have different features.',
'user_ce_navdisplay' => 'Select which content field should be displayed in content lists. Options include the page title, or menu text. If &quot;Default&quot; is selected, then the site preference will be used',
'user_clearsettings' => 'This will cause all of this user\'s settings to be returned to default values',
'user_copysettings' => 'Change this user\'s settings to match those of another existing user',
'user_dateformat' => 'Specify a date format string to use when dates are displayed. This string uses <a href="http://php.net/manual/en/function.strftime.php" class="external" target="_blank">strftime</a> format. <strong>Note:</strong> some independently-developed modules and plugins might ignore this setting.</strong>',
'user_dfltparent' => 'Specify the default parent page for creating a new content page. The use of this setting also depends on your content editing permissions.<br/><br/>Drill down to the selected default parent page by selecting the topmost parent, and successive child pages from the provided dropdowns.<br/><br/>The text field on the right will always indicate which page is currently selected.',
'user_edit_password' => 'Change this field to change the current password',
'user_edit_passwordagain' => 'Match this field to the other password entry, to change the password',
'user_email' => 'Specify an email address. This is used for the lost password functionality, and for any notification emails sent by the system (or add-on modules).',
'user_enablenotifications' => 'If enabled, the system will display various notifications about things that need to be taken care of in the navigation',
'user_firstname' => 'Optionally specify your given name. This might be used in the Admin theme, or to personally address emails to you',
'user_hidehelp' => 'If enabled, the system will hide module help links from the admin console. In most circumstances the help provided with modules is targeted towards site developers and might not be useful to content editors.',
'user_homepage' => 'You may select a page to automatically direct to when you login to the CMSMS admin console. This might be useful when you primarily use one function.',
'user_ignoremodules' => 'If Admin notifications are enabled you can select to ignore notifications from some modules',
'user_indent' => 'This option will indent the content list view to illustrate the parent and child page relationship',
'user_language' => 'Select the language to display for the Admin interface. The list of available languages might vary on each CMSMS install',
'user_lastname' => 'Optionally specify your surname. This might be used in the Admin theme, or to personally address emails to you',
//'user_login' => 'If disabled, the user will be prevented from logging in to the admin console', //for adminaccess
'user_name' => 'The username field must consist of alphanumeric characters, dot(.), underscore, or space',
'user_password' => 'Please enter a secure password for this website. The password must be eight or more characters long, and must comply with the site\'s passwords-policy (content, repetition). Please leave this field blank if you do no wish to change your password.',
'user_passwordagain' => 'To reduce errors, please enter your password again. Leave this field empty if you do not wish to change your password.',
'user_syntax' => 'Select which syntax highlighting module to use when editing stylesheets, templates, or smarty code. The list of available modules might change depending on what your site administrator has configured',
'user_syntaxtheme' => 'If the selected editor supports theming, enter here the name of your preferred theme.',
'user_username' => 'Your username is your unique name for the CMSMS Admin panel. Please use only alphanumeric characters and the underscore',
'user_wysiwyg' => 'Select which WYSIWYG <em>(What You See Is What You Get)</em> module to use when editing HTML content. You might also select &quot;None&quot; if you are comfortable with HTML. The list of available WYSIWYG editors might change depending on what the site administrator has configured.',
'user_wysiwygtheme' => 'If the selected editor supports theming, enter here the name of your preferred theme.',

// S
'settings_adminlog_lifetime' => 'This setting indicates the maximum amount of time that entries in the Admin log should be retained.',
'settings_autoclearcache' => 'This option allows you to specify the maximum age <em>(in days)</em> before files in the cache directory will be deleted.<br/><br/>This option is useful to ensure that cached files are regenerated periodically, and that the file system does not become polluted with old and unnecessary files. An ideal value for this field is 14 or 30 days.<br /><br /><strong>Note:</strong> Cached files are cleared at most once per day.',
'settings_autocreate_flaturls' => 'If SEF/pretty URLs are enabled, and the option to auto-create URLs is enabled, this option indicates hat those auto-created URLS should be flat <em>(i.e: identical to the page alias)</em>. <strong>Note:</strong> the two values do not need to remain identical, the URL value can be changed to be different than the page alias in subsequent page edits',
'settings_autocreate_url' => 'When editing content pages, should SEF/pretty URLS be auto-created?  Auto creating URLS will have no effect if pretty urls are not enabled in the CMSMS config.php file.',
'settings_backendwysiwyg' => 'Select the default WYSIWYG editor for newly-created admin-user accounts. Such users will be able to select a different WYSIWYG editor as one of their own preferences.',
'settings_badtypes' => 'Select which content types to remove from the content type dropdown when editing or adding content. This feature is useful if you do not want editors to be able to create certain types of content. Use CTRL+Click to select, unselect items. Having no selected items will indicate that all content types are allowed. <em>(applies to all users)</em>',
'settings_basicattribs2' => 'This field allows you to specify which content properties that users without the &quot;Manage All Content&quot; permission are allowed to edit.<br />This feature is useful when you have content editors with restricted permission and want to permit editing of additional content properties.',
'settings_browsercache_expiry' => 'Specify the amount of time (in minutes) that browsers should cache pages for. Setting this value to 0 disables the functionality. In most circumstances you should specify a value greater than 30',
'settings_browsercache' => 'Applicable only to cachable pages, this setting indicates that browsers should be allowed to cache the pages for an amount of time. If enabled, repeat visitors to your site might not immediately see changes to the content of the pages. However enabling this option can seriously improve the performance of some websites.',
'settings_checkversion' => 'If enabled, the system will perform a daily check for a new release of CMSMS',
'settings_contentimage_path' => 'This setting is used when a page template contains the {content_image} tag. The directory specified here is used to provide a selection of images to associate with the tag.<br /><br />Relative to the uploads path, specify a directory name that contains the paths containing files for the {content_image} tag. This value is used as a default for the dir parameter',
'settings_cssnameisblockname' => 'If enabled, the content block name <em>(id)</em> will be used as a default value for the cssname parameter for each content block.<br/><br/>This is useful for WYSIWYG editors. The stylesheet (block name) can be loaded by the WYSIWYG editor and provide an appearance that is closer to that of the front web page.<br/><br/><strong>Note:</strong> WYSIWYG Editors might not read information from the supplied stylesheets (if they exist) depending upon their settings and capabilities.',
'settings_dateformat' => 'Specify the date format string in <a href="http://ca2.php.net/manual/en/function.strftime.php" class="external" target="_blank"><u>PHP strftime</u></a> format that will be used <em>(by default)</em> to display dates and times on the pages of this website.</p><p>Admin users can adjust these settings in the user preferences panel.</p><p><strong>Note:</strong> Some modules might choose to display times and dates differently.',
'settings_disablesafemodewarn' => 'This option will disable a warning notice if CMSMS detects that <a href="http://php.net/manual/en/features.safe-mode.php" class="external" target="_blank">PHP Safe Mode</a> has been detected.<br /><br /><strong>Note:</strong> Safe mode has been deprecated as of PHP 5.3.0 and removed for PHP 5.4.0. CMSMS Does not support operation under safe mode, and our support team will not render any technical assistance for installs where safe mode is active',
'settings_enablenotifications' => 'This option will enable notifications being shown at the top of the page in each Admin request. This is useful for important notifications about the system that might require user action. It is possible for each Admin user to turn off notifications in their preferences.',
'settings_enablesitedown' => 'This option determines whether the website is presented to website visitors as "down for maintenance"',
'settings_enablewysiwyg' => 'Enable WYSIWYG editor in the text area below',
'settings_frontendlang' => 'The language normally used for translated strings on frontend pages. This can be changed as required, using a smarty tag: <code>{cms_set_language}</code>',
'settings_frontendwysiwyg' => 'When WYSIWYG editors are provided on frontend forms, which one should be used?  If any.',
'settings_globalmetadata' => 'This text area provides the ability to enter meta information that is relevant to all content pages. This is an ideal location for meta tags such as Generator, and Author, etc.',
'settings_help_url' => 'Specify an URL (e.g. email or website) to open when a site-help link is activated. Leave blank to use the CMSMS website support-page.',
'settings_job_frequency' => 'Minimum interval between processing of jobs. Enter a value from 1 to 10. Lower is better, but not so low that performance of the website is noticeably degraded.',
'settings_job_timelimit' => "Timeout for processing pending async jobs. Enter a value from 2 to 120, or 0 to use PHP's 'max_execution_time' setting.",
'settings_job_url' => 'Enter a suitable URL to replace the default internal URL, if that cannot be used. Leave blank to use the default.',
'settings_imagefield_path' => 'This setting is used when editing content. The directory specified here is used to provide a list of images from which to associate an image with the content page.<br/></br/>Relative to the image uploads path, specify a directory name that contains the paths containing files for the image field',
'settings_lock_refresh' => 'A CMSMS lock is a semaphore to block simultaneous editing of an item by more than one user. Enter here the interval (in seconds, 0 or 30..3600) between successive lock-now-cleared checks. This the default value used when no context-specific interval applies.',
'settings_lock_timeout' => 'A CMSMS lock is a semaphore to block simultaneous editing of an item by more than one user. Enter here the lifetime of created locks (in minutes, 0 or 5..480). This the default value used when no context-specific timeout value applies.',
'settings_lockrefresh' => 'This field specifies the minimum frequency (in minutes) the Ajax based locking mechanism should &quot;touch&quot; a lock. An ideal value for this field is 5.',
'settings_locktimeout' => 'This field specifies the number of minutes of inactivity before a lock times out. After a lock times out other users might steal the lock. In order for a lock to not time-out it must be &quot;touched&quot; before its expiry time. This resets the expiry time of the lock. Under most circumstances a 60 minute lock should be suitable.',
'settings_login_module' => 'Select the appropriate authentication type to be applied to admin console logins.',
'settings_login_processor' => 'This allows you to customise the way admin console logins are handled. The current theme\'s login mechanism (if any), or the current login module. The module will automatically be used if the theme does not itself process logins.',
'settings_logintheme' => 'Select a theme (from those installed) to be used to generate the admin login form, and as the default theme for new admin user accounts. Admin users will be able to select their own preferred admin theme in the user-preferences panel.',
'settings_mailprefs_from' => 'This option controls the <em>default<em> address that CMSMS will use to send email messages. This cannot just be any email address. It must match the domain that CMSMS is providing. Specifying a personal email address from a different domain is known as &quot;<a href="https://en.wikipedia.org/wiki/Open_mail_relay" class="external" target="_blank">relaying</a>&quot; and will most probably result in emails not being sent, or not being accepted by the recipient email server. A good typical example for this field is noreply@mydomain.com',
'settings_mailprefs_fromuser' => 'Here you can specify a name to be associated with the email address specified above. This name can be anything but should reasonably correspond to the email address. i.e: &quot;Do Not Reply&quot;',
'settings_mailprefs_mailer' => 'This choice controls how CMSMS will send mail. Using PHPs mail function, sendmail, or by communicating directly with an SMTP server.<br/><br/>The &quot;mail&quot; option should work on most shared hosts, however it almost certainly will not work on most self hosted windows installations.<br/><br/>The &quot;sendmail&quot; option should work on most properly configured self hosted Linux servers. However it might not work on shared hosts.<br/><br/>The SMTP Option requires configuration information from your host.',
'settings_mailprefs_sendmail' => 'If using the &quot;sendmail&quot; mailer method, you must specify the complete path to the sendmail binary program. A typical value for this field is &quot;/usr/sbin/sendmail&quot;. This option is typically not used on windows hosts.<br/><br/><strong>Note:</strong> If using this option your host must allow the popen and pclose PHP functions which are often disabled on shared hosts.',
'settings_mailprefs_smtpauth' => 'When using the SMTP mailer, this option indicates that the SMTP server requires authentication to send emails. You then must specify <em>(at a minimum)</em> a username, and password. Your host should indicate whether SMTP authentication is required, and if so provide you with a username and password, and optionally an encryption method.<br/><br/><strong>Note:</strong> SMTP authentication is required if your domain is using Google apps for email.',
'settings_mailprefs_smtphost' => 'When using the SMTP mailer this option specifies the hostname <em>(or IP address)</em> of the SMTP server to use when sending email. You might need to contact your host for the proper value.',
'settings_mailprefs_smtppassword' => 'This is the password for connecting to the SMTP server if SMTP authentication is enabled.',
'settings_mailprefs_smtpport' => 'When using the SMTP mailer this option specifies the integer port number for the SMTP server. In most cases this value is 25, though you might need to contact your host for the proper value.',
'settings_mailprefs_smtpsecure' => 'This option, when using SMTP authentication specifies an encryption mechanism to use when communicating with the SMTP server. Your host should provide this information if SMTP authentication is required.',
'settings_mailprefs_smtptimeout' => 'When using the SMTP mailer, this option specifies the number of seconds before an attempted connection to the SMTP server will fail. A typical value for this setting is 60.<br/><br/><strong>Note:</strong> If you need a longer value here it probably indicates an underlying DNS, routing or firewall problem, and you might need to contact your host.',
'settings_mailprefs_smtpusername' => 'This is the username for connecting to the SMTP server if SMTP authentication is enabled.',
'settings_mailtest_testaddress' => 'Specify a valid email address that will receive the test email',
'settings_mandatory_urls' => 'If SEF/pretty URLs are enabled, this option indicates whether page URLS are a required field in the content editor.',
'settings_nogcbwysiwyg' => 'This option will disable the WYSIWYG editor on all global content blocks independent of user settings, or of the individual global content blocks',
'settings_nosefurl' => 'To configure <strong>S</strong>each <strong>E</strong>ngine <strong>F</strong>riendly <em>(pretty)</em> URLs you need to edit a few lines in your config.php file and possibly to edit a .htaccess file or your web servers configuration.  You can read more about configuring pretty URLS <a href="https://docs.cmsmadesimple.org/configuration/pretty-url" class="external" target="blank"><u>here</u></a> &raquo;',
'settings_pseudocron_granularity' => 'This setting indicates how often the system will attempt to handle regularly scheduled tasks.',
'settings_searchmodule' => 'Select the module that should be used to index words for searching, and will provide the site search capabilities',
'settings_sitedownexcludeadmins' => 'Do show the website to users logged in to the CMSMS admin console. This allows administrators to work on the site without interference',
'settings_sitedownexcludes' => 'Do show the website to these IP addresses',
'settings_sitedownmessage' => 'The content presented to website visitors when the site is &quot;down for maintenance&quot;',
'settings_sitelogo' => 'Optional image file, to be displayed on login pages and admin console pages. Enter an absolute URL (typically somewhere in the site\'s uploads folder), or an uploaded-images-folder-relative URL for the file.',
'settings_sitename' => 'This is a human-readable name for this website, such as the business, club, or organization name',
'settings_smarty_cachemodules' => 'This option controls the default type of caching applied by smarty to all module-action tags.<br/>If enabled, smarty will process such tags in the same manner as for other content i.e. on content pages marked as cachable, the tag ouput will be cached, unless disabled by a nocache parameter applied to the tag in the page/template e.g. <code>{modulename nocache}</code>. The <a href="https://www.smarty.net/docs/en/caching.cacheable.tpl#cacheability.tags">Smarty manual</a> has more on this. Such tag-caching might have adverse effect on some modules, or modules with forms.<br/>Or if disabled, no module-tag output will ever be cached, which might have an adverse effect on performance.<br/>Otherwise, caching will be determined by the module\'s configuration data, perhaps a user-choice, but more likely a module-developer choice. This too can be overridden by a nochache parameter.<br/>Historically, CMSMS always disabled module-tag caching, and it might be appropriate to continue that approach.',
'settings_smarty_cacheusertags' => 'If enabled, smarty will process user-defined tags in the same manner as other plugins i.e. on content pages marked as cachable, plugin ouput will be cached, unless disabled by a nocache parameter applied to the tag in the page/template e.g. <code>{myusertag nocache}</code>. The <a href="https://www.smarty.net/docs/en/caching.cacheable.tpl#cacheability.tags">Smarty manual</a> has more on this.<br/>Caching might improve page-creation speed, but it might interfere with the behavior of the tag.<br/>Historically, CMSMS always disabled user-plugin (aka UDT) caching, and it might be appropriate to continue that approach.',
'settings_smartycachelife' => 'Enter a value >= 0, or leave blank to use the smarty default (3600). 0 effectively disables caching.',
'settings_smartycompilecheck' => 'During each request, smarty normally checks each used template to determine whether it has changed since the last time it was compiled. When templates are stable, such checks just slow things down. Note that if this option is deselected and a template is later changed, such change will not be displayed until the pages-cache is cleared or times out (per the cache-lifetime setting).',
'settings_syntax' => 'Select one of these, to use for text editing with syntax hightlighting and many other advanced capabilities.<br /><br />Each such editor requires a substantial download at runtime, and if that is a problem, disable this capability.',
'settings_syntaxtheme' => 'If the default editor supports theming, enter here the name of the default theme. Admin users will be able to select a different theme as one of their own preferences.',
'settings_syntaxtheme' => 'Specify the theme name (lower case, any \' \' replaced by \'_\').',
'settings_thumbfield_path' => 'This setting is used when editing content. The directory specified here is used to provide a list of images from which to associate a thumbnail with the content page.<br/><br/>Relative to the image uploads path, specify a directory name that contains the paths containing files for the image field. Usually this will be the same as the path above.',
'settings_thumbheight' => 'Specify a height <em>(in pixels)</em> to be used by default when generating thumbnails from uploaded image files. Thumbnails are typically displayed in the Admin panel in the FileManager module or when selecting an image to insert into page content. However, some modules might use the thumbnails on the website frontend.<br/><br/><strong>Note:</strong> Some modules might have additional preferences for how to generate thumbnails, and ignore this setting.',
'settings_thumbwidth' => 'Specify a width <em>(in pixels)</em> to be used by default when generating thumbnails from uploaded image files. Thumbnails are typically displayed in the Admin panel in the FileManager module or when selecting an image to insert into page content. However, some modules might use the thumbnails on the website frontend.<br/><br/><strong>Note:</strong> Some modules might have additional preferences for how to generate thumbnails, and ignore this setting.',
'settings_umask' => 'The &quot;umask&quot; is an integer (often octal) value that specifies which permissions are to be revoked from the system defaults when a file or directory is created on a *NIX filesystem. Usually, the default umask is 022, which means that &quot;group&quot; and &quot;other&quot; processes are not able to modify the item. For more information see <a href="http://en.wikipedia.org/wiki/Umask" class="external" target="_blank">this Wikipedia article</a>.',
'settings_wysiwyg' => 'Select one of these, to use for editing HTML.<br /><br />Some such editors may require a substantial download at runtime, and if that is a problem, select another option.',
'settings_wysiwygtheme' => 'If the default WYSIWYG editor supports theming, enter here the name of the default theme. Admin users will be able to select a different theme as one of their own preferences.',

'settings_password_level' => 'This sets the minimum acceptable standard of admin users\' password \'complexity\'. That can be increased by greater length, more variability, fewer recognizable words/character-sequences. The choice is applied to new and renewed passwords. Remember, complex passwords may actually be <strong>less</strong> secure, if people resort to storing them carelessly.',
'settings_username_settings' => 'This sets the minimum acceptable standard of admin users\' login (a.k.a. account name) \'complexity\'.',

] + $lang;

//'settings_smartycaching' => 'When enabled, the output from various plugins will be cached to increase performance. Additionally, most portions of compiled templates will be cached. This only applies to output on content pages marked as cachable, and only for non-admin users. Note, this functionality might interfere with the behavior of some modules or plugins, or plugins that use non-inline forms.<br/><br/><strong>Note:</strong> When smarty caching is enabled, global content blocks <em>(GCBs)</em> are always cached by smarty, and User Defined Tags <em>(UDTs)</em> are never cached. Additionally, content blocks are never cached.',
