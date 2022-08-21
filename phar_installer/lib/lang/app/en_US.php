<?php

// A
$lang['action_freshen'] = 'Freshening / Repairing a CMSMS %s installation';
$lang['action_install'] = 'Creating a new CMSMS %s website';
$lang['action_upgrade'] = 'Upgrading a CMSMS website to version %s';
$lang['advanced_mode'] = 'Enable advanced mode';
$lang['allow_url_fopen'] = '&quot;allow_url_fopen&quot; is disabled ?';
$lang['apptitle'] = 'Installation and upgrade assistant';
$lang['assets_dir_exists'] = 'Assets directory exists ?';

// B
$lang['build_date'] = 'Build Date';

// C
$lang['cache_extension'] = 'Suitable PHP cache extension is available ?';
$lang['cache_apcu'] = 'APCu will be used';
$lang['cache_memcached'] = 'Memcached will be used';
$lang['cache_predis'] = 'Predis will be used';
$lang['cache_yac'] = 'YAC will be used';
$lang['changelog_uc'] = 'CHANGELOG';
$lang['cleaning_files'] = 'Clean files that are no longer applicable to the release';
$lang['config_writable'] = 'Config file can be saved ?';
$lang['confirm_freshen'] = 'Are you sure you want to freshen/repair the existing CMSMS installation ? Use with extreme caution!';
$lang['confirm_upgrade'] = 'Are you sure you want to begin the upgrade process ?';
$lang['curl_extension'] = 'PHP cURL extension is available ?';
$lang['create_assets_structure'] = 'Create a location for file resources';
$lang['crypter_sodium'] = 'Sodium functionality will normally be used';
$lang['crypter_ssl'] = 'OpenSSL extension functionality will normally be used';
$lang['cryption_functions'] = 'Suitable en/decryption capabilities are available ?';

// CLI
$lang['cli_welcome'] = 'Welcome to the CMSMS installation assistant';
$lang['cli_cmsver'] = 'for CMSMS version %s';
$lang['cli_hdr_op'] = '%s CMSMS in %s';

// D
$lang['database_support'] = 'PHP database interface extension is available ?';
$lang['default_charset'] = 'Default Character-encoding';
$lang['desc_wizard_step1'] = 'Start the installation or upgrade process';
$lang['desc_wizard_step2'] = 'Analyze destination directory to find existing software';
$lang['desc_wizard_step3'] = 'Check whether everything is compatible with CMSMS';
$lang['desc_wizard_step4'] = 'Provide basic configuration info';
$lang['desc_wizard_step5'] = 'Provide some basic site details';
$lang['desc_wizard_step6'] = 'For new installs provide info about the initial administrator';
$lang['desc_wizard_step7'] = 'Extract and install files';
$lang['desc_wizard_step8'] = 'Create or update the database, events, permissions, user accounts, templates, stylesheets and config file';
$lang['desc_wizard_step9'] = 'Install and/or upgrade modules, install content-pages, and clean up';
$lang['destination_directory'] = 'Destination directory';
$lang['dest_writable'] = 'Write permission in destination directory';
$lang['disable_functions'] = 'PHP core functions are enabled ?';
$lang['done'] = 'Done';

// E
/*
$lang['email_accountinfo_message'] = <<<EOT
Installation of CMS Made Simple is complete.

This email contains sensitive information and should be stored in a secure location.

Here are the details of the installation.
username: %s
password: %s
install directory: %s
root url: %s

EOT;
$lang['email_accountinfo_message_exp'] = <<<EOT
Installation of CMS Made Simple is complete.

This email contains sensitive information and should be stored in a secure location.

Here are the details of the installation.
username: %s
password: %s
install directory: %s

EOT;
$lang['email_accountinfo_subject'] = 'CMS Made Simple Installation Successful';
$lang['emailaccountinfo'] = 'Email the account information';
*/
$lang['edeprecated_enabled'] = 'E_DEPRECATED is enabled in PHP\'s error_reporting. Although this will not prevent CMSMS from operating, it might result in warnings being displayed in the output screen, particularly from older non-core modules';
$lang['emailaddr'] = 'Email Address';
$lang['enotice_enabled'] = 'E_NOTICE is enabled in PHP\'s error_reporting. Although this will not prevent CMSMS from operating, it might result in warnings being displayed in the output screen, particularly from older non-core modules';
$lang['error_adminacct_emailaddr'] = 'The email address is invalid';
//$lang['error_adminacct_emailaddrrequired'] = 'You have selected to email the account information, but have not entered a valid email address';
$lang['error_adminacct_password'] = 'The password is unsuitable. Please try again.'; //TODO supplementary details
$lang['error_adminacct_repeatpw'] = 'The entered passwords do not match.';
$lang['error_adminacct_username'] = 'The username is unsuitable. Please try again.';
//$lang['error_admindirrenamed'] = 'It appears that the site\'s admin directory has been renamed. That change must be <a href="https://docs.cmsmadesimple.org/general-information/securing-cmsms#renaming-admin-folder" target="_blank" class="external">reversed</a> (on disk and in the config.php file) in order to proceed!<br /><br />After that directory has been reverted to &quot;admin&quot;, please reload this page.';
$lang['error_backupconfig'] = 'Unable to properly backup the config file';
$lang['error_checksum'] = 'Extracted file checksum does not match original (%s)';
$lang['error_cmstablesexist'] = 'It appears that there is already a CMSMS installation on this database. Please enter different database information. If you would like to use a different table prefix you might need to restart the installation process and enable advanced mode.';
$lang['error_createtable'] = 'Problem creating database table... perhaps this is a permissions issue';
$lang['error_dbconnect'] = 'Unable to connect to the database. Please double check the credentials you have supplied';
$lang['error_dbversion'] = 'Database server version (%s) does not meet the minimum requirement';
$lang['error_dirnotvalid'] = 'The directory %s does not exist (or is not writeable)';
$lang['error_droptable'] = 'Problem dropping database table... perhaps this is a permissions issue';
$lang['error_extract'] = 'Unable to extract file %s';
$lang['error_filebad'] = 'Unable to load file %s';
$lang['error_filenotwritable'] = 'The file %s could not be overwritten (permissions problem)';
$lang['error_internal'] = 'Sorry, something has gone wrong... (internal error) (%s)';
$lang['error_invalid_directory'] = 'It appears that the directory you have selected to install in is a working directory for the installer itself';
$lang['error_invalidconfig'] = 'Error in the config file, or config file missing';
$lang['error_invaliddbpassword'] = 'Database password contains characters that cannot be safely saved.';
$lang['error_invalidkey'] = 'Invalid member variable or key %s for class %s';
$lang['error_invalidparam'] = 'Invalid parameter or value for parameter: %s';
$lang['error_invalidtimezone'] = 'The timezone specified is invalid';
$lang['error_invalidqueryvar'] = 'The query variable entered contains invalid characters.  Please use only alphanumerics and underscore.';
$lang['error_missingconfigvar'] = 'The key &quot;%s&quot; is either missing from or invalid in the installer.ini file';
$lang['error_modulebad'] = 'Unable to process module \'%s\'';
$lang['error_noarchive'] = 'Problem finding archive file... please restart';
$lang['error_nlsnotfound'] = 'Problem finding NLS files in archive';
$lang['error_nocontent'] = 'Source for generating site content (%s) not found';
$lang['error_nodatabases'] = 'The PHP mysqli extension is required, but not found';
$lang['error_nodbhost'] = 'Please enter a valid hostname (or IP address) for the database connection';
$lang['error_nodbname'] = 'Please enter the name of a valid database on the host specified above';
$lang['error_nodbpass'] = 'Please enter a valid password for working on the database';
$lang['error_nodbprefix'] = 'Please enter a valid prefix for database tables';
//$lang['error_nodbtype'] = 'Please select a database type';
$lang['error_nodbuser'] = 'Please enter a valid username for authenticating to the database';
$lang['error_nodestdir'] = 'Destination directory not set';
$lang['error_nositename'] = 'Sitename is a required parameter. Please enter a suitable name for the website.';
$lang['error_notify'] = 'Please <a href="%s" target="_blank">advise CMS Made Simple</a> about the circumstances.';
$lang['error_notimezone'] = 'Please enter a valid timezone for this server';
$lang['error_overwrite'] = 'Permissions problem: cannot overwrite %s';
//$lang['error_sendingmail'] = 'Error sending mail';
$lang['error_test_name'] = 'A test is missing both a name and a lang key for such name';
$lang['error_tzlist'] = 'A problem occurred retrieving the timezone identifiers list';
$lang['errorlevel_estrict'] = 'E_STRICT is disabled ?';
$lang['errorlevel_edeprecated'] = 'E_DEPRECATED is disabled ?';
$lang['errorlevel_enotice'] = 'E_NOTICE is disabled ?';
$lang['estrict_enabled'] = 'E_STRICT is enabled in PHP\'s error_reporting. Although this will not prevent CMSMS from operating, it might result in warnings being displayed in the HTML output, particularly from older non-core modules';

// F
$lang['fail_assets_dir'] = 'An assets directory already exists.  This application might write to this directory to rationalize the location of files.  Please ensure that you have a backup';
$lang['fail_assets_msg'] = 'An assets directory already exists.  This application might write to this directory to rationalize the location of files.  Please ensure that you have a backup';
$lang['fail_cache_extension'] = 'No such cache extension was found. If possible, enable the operation of any of
<ul>
<li>APCu</li>
<li>Memcached</li>
<li>PHPredis (aka Redis)</li>
<li>YAC</li>
</ul>
on the website';

$lang['fail_config_writable'] = 'The webserver cannot modify the /lib/config.php file. Please try to change the permissions on that file to 0666 until this installer session is finished.';
$lang['fail_curl_extension'] = 'The cURL extension was not found. Not a critical absence, but it might cause problems with some non-core modules';
$lang['fail_cryption_functions'] = 'No such support was found. If possible, enable the operation of either of
<ul>
<li>Sodium</li>
<li>OpenSSL</li>
</ul>
on the website';
$lang['fail_database_support'] = 'No compatible database driver found';
$lang['fail_file_get_contents'] = 'The file_get_contents function does not exist, or is disabled. CMSMS Cannot continue (even the installer will probably fail)';
$lang['fail_file_uploads'] = 'File upload capabilities are disabled in this environment. Several functions of CMSMS will not function in this environment';
$lang['fail_func_json'] = 'json functionality was not found';
$lang['fail_func_gzopen'] = 'gzopen function was not found';
$lang['fail_func_md5'] = 'md5 functionality was not found';
$lang['fail_func_tempnam'] = 'The tempnam function does not exist. It is required for CMSMS to function';
$lang['fail_func_ziparchive'] = 'ZipArchive functionality was not found.  This might limit some modules\' functionality';
$lang['fail_ini_set'] = 'It appears that we cannot change ini settings. This could cause problems in non-core (independently-developed) modules (or when enabling debug mode)';
//N/A PHP7+  $lang['fail_magic_quotes_runtime'] = 'It appears that magic quotes are enabled in the site configuration. Please disable them and retry';
$lang['fail_intl_extension'] = 'The Intl extension was not found. Not a critical absence, but it might cause problems with some presentation and formatting if this is to be a non-English-language site';
$lang['fail_max_execution_time'] = 'The current max execution time (%s) is too low. At least %s is required, and %s or greater is recommend';
$lang['fail_memory_limit'] = 'The current memory limit value (%s) is too low. At least %s is required, and %s or greater is recommended';
$lang['fail_multibyte_support'] = 'Multibyte support is not enabled in the site configuration';
$lang['fail_output_buffering'] = 'Output buffering is not enabled.';
$lang['fail_open_basedir'] = 'Open basedir restrictions are in effect. CMSMS requires that this be disabled';
$lang['fail_php_version'] = 'The version of PHP running the site (%s) is too low. The minimum acceptable version is %s.';
$lang['fail_php_version2'] = 'The version of PHP running the site (%s) is acceptable, but %s or greater is recommended.';
$lang['fail_post_max_size'] = 'The current post max size (%s) is too low. At least %s is required, and %s or greater is recommended. Ensure that it is larger than the upload_max_filesize';
$lang['fail_pwd_writable2'] = 'The webserver must be entitled to write to the destination directory (and to all files and directories beneath it) in order to install files. The server is not entitled to write to (at least) %s';
$lang['fail_register_globals'] = 'Please disable register globals in the site PHP configuration';
$lang['fail_remote_url'] = 'Unable to connect to a remote URL. This will limit some of the functionality of CMS Made Simple';
$lang['fail_safe_mode'] = 'CMSMS will not operate properly in an environment where safe mode is enabled. Safe mode is deprecated as a failed mechanism, and will be removed in future versions of PHP';
$lang['fail_session_save_path_exists'] = 'The session save path variable value is invalid or the directory does not exist';
$lang['fail_session_save_path_writable'] = 'The session save path directory is not writeable';
$lang['fail_session_use_cookies'] = 'CMSMS requires that PHP be configured to store the session key in a cookie';
$lang['fail_sodium_functions'] = 'libsodium-related capabilities are not built-in or provided via extension. Such capabilities would improve system security.';
$lang['fail_ssl_extension'] = 'The openssl extension was not found. Some processes would work much faster if it were installed.';
$lang['fail_tmpfile'] = 'The system tmpfile() function is not available. This is required to allow us to extract archives. The optional TMPDIR url argument can be provided to the installer to specify a writeable directory. See the README file that should be in included in this directory.';
$lang['fail_tmp_dirs_empty'] = 'The CMSMS temporary-storage directories <em>(tmp/cache and tmp/templates_c) exist, and are not empty.  Please remove or empty them';
$lang['fail_version_writable'] = 'The webserver must be entitled to modify the /lib/version.php file. Please try to change the permissions on that file to 0666 until this installer session is finished.';
$lang['fail_xml_functions'] = 'The XML extension was not found. Please enable this in the site\'s PHP environment';
$lang['failed'] = 'failed';
$lang['file_get_contents'] = 'file_get_contents() method is available ?';
$lang['file_installed'] = 'Install %s';
$lang['file_uploads'] = 'File upload functionality is available ?';
$lang['finished_all_msg1'] = 'You are most welcome to participate in the CMSMS community. If you\'re not already doing that, here are some starting points';
$lang['finished_all_msg2'] = '<strong>Please remember:</strong> the CMS Made Simple Foundation always appreciates %s from CMSMS fans.'; // will include a payment-link
$lang['finished_custom_freshen_msg'] = 'The installation has been freshened. Please visit the website to check that everything is functioning correctly.';
$lang['finished_custom_install_msg'] = 'Done! Please visit the website and log in to its <a href="%s">Admin Console</a>.';
$lang['finished_custom_upgrade_msg'] = 'Done! Check that everything is working properly. Log in to the site\'s <a href="%s">Admin Console</a>. Among other things, look out for modules which need to be upgraded. Visit <a href="%s">the website</a>.<br /><strong>Hint:</strong> Now is a good time to create another backup.';
$lang['finished_freshen_msg'] = 'The installation has been freshened. To check that everything is functioning correctly, please visit <a href="%s">the website</a> or log in to its <a href="%s">Admin Console</a>.';
$lang['finished_install_msg'] = 'Done! You can now <a href="%s">visit the website</a> or log in to its <a href="%s">Admin Console</a>.';
$lang['finished_upgrade_msg'] = 'Done! Please visit the <a href="%s">website</a> and its <a href="%s">Admin Console</a> to verify correct behavior. You might also need to upgrade some non-core modules.<br /><strong>Hint:</strong> Remember to create another backup after verifying correct behavior.';
$lang['freshen'] = 'Freshen (repair) installation';
$lang['func_json'] = 'json functionality is available ?';
$lang['func_md5'] = 'md5 functionality is available ?';
$lang['func_tempnam'] = 'tempnam() method is available ?';
$lang['func_gzopen'] = 'gzopen() method is available ?';
$lang['func_ziparchive'] = 'ZipArchive class is available ?';

// G
$lang['gd_version'] = 'PHP GD2 extension is available ?';
$lang['goback'] = 'Back';
$lang['grp_admin_desc'] = 'Members of this group can manage the entire site';
$lang['grp_coder_desc'] = 'Members of this group can add/edit/delete files which run the website';

// H

// I
$lang['info_addlanguages'] = 'Select languages (in addition to English) to install. <strong>Note:</strong> not all translations are complete.';
$lang['info_addmodules'] = 'Select modules to install.';
$lang['info_adminaccount'] = 'Please provide credentials for the initial administrator account. This account will have access to all of the functionality of the CMSMS admin console.';
$lang['info_adminpath'] = "Optional substitute for the website system-folder 'admin'. If so desired, enter a file-system relative path (which will be relative to the website's root folder) e.g. 'adminBLAH' or 'over/there'. Without the surrounding quotes shown in these examples. No leading or trailing path separator. Any internal separator(s) to be '/' or '\\' in accord with the underlying file system.";
$lang['info_advanced'] = 'Advanced mode enables several more options (database tables prefix, database-server port, key used for page-alias in requests, custom locations) during site installation, and provides extra feedback.';
$lang['info_assetspath'] = "Optional substitute for the website system-folder 'assets'. If so desired, enter a website-root-path-relative path e.g. 'hiddenassets' or 'not/here' (without surrounding quotes or leading or trailing separator, and internal separator(s) according with the file system.)";
$lang['info_dbinfo'] = 'CMS Made Simple stores most of its data in a database. A MySQL 5.5+ or compatible database-server is mandatory (and 5.6.5+ is preferable). The user specified here must have sufficient privileges on the specified database to allow adding, removing and modifying tables, indexes, views and possibly procedures.';
$lang['info_errorlevel_edeprecated'] = 'E_DEPRECATED is a flag for PHP&quot;s error reporting that indicates that warnings should be displayed about code that is using deprecated techniques.  Although the CMSMS core attempts to ensure that we no longer use deprecated techniques, some modules might not.  This setting should be disabled in the PHP configuration';
$lang['info_errorlevel_estrict'] = 'E_STRICT is a flag for PHP&#39;s error reporting which indicates that strict coding standards should be respected. Although the CMSMS core attempts to conform to E_STRICT standards, some modules might not. It is recommended that this setting be disabled in the PHP configuration';
$lang['info_supporturl'] = 'An optional URL (e.g. email or website) to open when a site-help link is activated. If none is specified, the CMSMS website support-page will be used.';
$lang['info_installcontent'] = 'You can install a series of sample pages, stylesheets and templates. Those provide information and tips to aid in building websites with CMSMS and is useful to read. However, if you are already familiar with CMS Made Simple, skipping this installation will still result in a minimal set of templates, stylesheets and content pages.';
$lang['info_open_basedir_session_save_path'] = 'open_basedir is enabled in the site PHP configuration. Session capabilities could not be properly tested. However, getting to this point in the installation process probably indicates that sessions are working okay.';
$lang['info_plugspath'] = "Optional substitute for the website system-folder 'assets/user_plugins'. If so desired, enter a website-root-path-relative path e.g. 'goodstuff' or 'hackers/cant/findme' (without surrounding quotes or leading or trailing separator, and internal separator(s) according with the file system).";
$lang['info_pwd_writable'] = 'This application needs write permission to the current working directory';
$lang['info_queryvar'] = 'The name used internally by CMSMS to identify the page requested. In most circumstances you should not need to adjust this.';
$lang['info_server_api'] = 'The type of interface that PHP is using';
//$lang['info_server_software'] = '';
$lang['info_sitename'] = 'The website name used in default templates as part of the title. Please enter a human-readable name for the website.';
$lang['info_timezone'] = 'The time zone used in date/time calculations and displays. Please select the appropriate zone.';
$lang['ini_set'] = 'PHP INI settings can be changed ?';
$lang['install'] = 'Install';
//$lang['install_attachstylesheets'] = 'Attach stylesheets to themes';
$lang['install_backupconfig'] = 'Back up the config file';
$lang['install_categories'] = 'Create default template-categories';
$lang['install_contentpages'] = 'Create default content pages';
//$lang['install_core_template_types'] = 'Install standard template types';
$lang['install_create_tables'] = 'Create database tables';
$lang['install_createassets'] = 'Create assets structure';
$lang['install_createconfig'] = 'Create new config file';
$lang['install_created_index'] = 'Create index %s ... %s';
$lang['install_created_table'] = 'Create table %s ... %s';
$lang['install_createtablesindexes'] = 'Create tables and indexes';
$lang['install_createtmpdirs'] = 'Create temporary directories';
$lang['install_creating_index'] = 'Create index %s';
$lang['install_default_designs'] = 'Create default designs';
$lang['install_detectlanguages'] = 'Detect installed languages';
$lang['install_dropping_tables'] = 'Drop tables';
$lang['install_dummyindexhtml'] = 'Create dummy index.html files';
$lang['install_extractfiles'] = 'Move files';
$lang['install_initevents'] = 'Create events';
$lang['install_initsitegroups'] = 'Create initial groups';
$lang['install_initsiteperms'] = 'Set initial permissions';
$lang['install_initsiteprefs'] = 'Set initial site preferences';
$lang['install_initsiteusers'] = 'Create initial user account';
$lang['install_initsiteusertags'] = 'Initial User Defined Tags';
$lang['install_module'] = 'Install module %s';
$lang['install_modulebad'] = '%s module installation failed';
$lang['install_modules'] = 'Install available modules';
$lang['install_requireddata'] = 'Set initial required data';
$lang['install_samplecontent'] = 'Install example content';
$lang['install_schema'] = 'Create database schema';
$lang['install_setschemaver'] = 'Set schema version';
$lang['install_setsequence'] = 'Install sequence tables';
$lang['install_setsitename'] = 'Set site name';
$lang['install_stylesheets'] = 'Create default stylesheets';
$lang['install_templates'] = 'Create default templates';
$lang['install_templatetypes'] = 'Create standard template types';
$lang['install_update_sequences'] = 'Update sequence tables';
$lang['install_updatehierarchy'] = 'Update content hierarchy positions';
$lang['install_updateseq'] = 'Update sequence for %s';
$lang['install_usertags'] = 'Install user-defined-tags';
$lang['installer_language'] = 'Use this language';
$lang['installer_ver'] = 'Installer version';
$lang['intl_extension'] = 'PHP Internationalization extension is available ?';

// J

// K

// L
$lang['legend'] = 'Legend';

// M
//N/A PHP7+ $lang['magic_quotes_runtime'] = 'Magic quotes are disabled ?';
$lang['max_execution_time'] = 'PHP script max execution time is sufficient ?';
$lang['meaning'] = 'Meaning';
$lang['memory_limit'] = 'PHP memory limit is sufficient ?';
$lang['msg_clearcache'] = 'Clear server cache';
$lang['msg_configsaved'] = 'Existing config file saved to %s';
$lang['msg_upgrade_module'] = 'Upgrade module %s';
$lang['msg_upgrademodules'] = 'Upgrade modules';
$lang['msg_yourvalue'] = 'Current value: %s';
$lang['multibyte_support'] = 'PHP Multibyte extension is available ?';

// N
$lang['next'] = 'Next';
$lang['newsletter'] = 'CMSMS Newsletter'; //finished-message link-text
$lang['no'] = 'No';
$lang['none'] = 'None';

// O
$lang['open_basedir_session_save_path'] = 'open_basedir is in enabled. Cannot test session save path.';
$lang['open_basedir'] = '&quot;open_basedir&quot; is disabled ?';
$lang['output_buffering'] = 'Output buffering is enabled ?';

// P
$lang['pass_cache_extension'] = 'Extension %s was found';
$lang['pass_config_writable'] = 'The webserver may modify the config.php file';
$lang['pass_cryption_functions'] = '%s was found';
$lang['pass_database_support'] = 'At least one compatible database driver found';
$lang['pass_func_json'] = 'json functionality detected';
$lang['pass_func_md5'] = 'md5 functionality was detected';
$lang['pass_func_tempnam'] = 'tempnam() method exists';
$lang['pass_multibyte_support'] = 'Multibyte support appears to be enabled';
$lang['pass_pwd_writable'] = 'The webserver is entitled to write into the destination directory.';
$lang['pass_version_writable'] = 'The webserver may modify the version.php file';
$lang['password'] = 'Password';
$lang['ph_sitename'] = 'Enter the name';
$lang['ph_supporturl'] = 'Enter the URL';
$lang['php_version'] = 'Appropriate PHP version ?';
$lang['post_max_size'] = 'Maximum size of request-data is sufficient ?';
$lang['preprocessing_files'] = 'Adjust file organization as required';
$lang['processing_file_manifests'] = 'Remove unused files according to manifests';
$lang['prompt_addlanguages'] = 'Additional Languages';
$lang['prompt_addmodules'] = 'Additional (Non-Core) Modules';
$lang['prompt_adminpath'] = 'Site Admin-Folder';
$lang['prompt_assetspath'] = 'Site Assets-Folder';
$lang['prompt_createtables'] = 'Create Database Tables';
$lang['prompt_dbhost'] = 'Database Hostname';
$lang['prompt_dbinfo'] = 'Database Information';
$lang['prompt_dbname'] = 'Database Name';
$lang['prompt_dbpass'] = 'Database Password';
$lang['prompt_dbport'] = 'Database Port Number';
$lang['prompt_dbprefix'] = 'Database Table Name Prefix';
$lang['prompt_dbuser'] = 'Database User';
$lang['prompt_dir'] = 'Installation Directory';
$lang['prompt_installcontent'] = 'Sample Content';
$lang['prompt_plugspath'] = 'Site Plugins-Folder';
$lang['prompt_queryvar'] = 'Page-Query Variable';
$lang['prompt_sitename'] = 'Web Site Name';
$lang['prompt_supporturl'] = 'Custom Support-URL';
$lang['prompt_timezone'] = 'Web-Server Timezone';
$lang['pwd_writable'] = 'Directory Writeable';

// Q
$lang['queue_for_upgrade'] = 'Queued non-core module %s for upgrade at the next step.';

// R
$lang['readme_uc'] = 'README';
$lang['register_globals'] = '&quot;register globals&quot; is disabled ?';
$lang['remote_url'] = 'Outgoing HTTP connections are possible ?';
$lang['repeatpw'] = 'Repeat Password';
$lang['reset_site_preferences'] = 'Reset some site preferences';
$lang['reset_user_settings'] = 'Reset user preferences';
$lang['retry'] = 'Retry';

// S
$lang['safe_mode'] = '&quot;safe mode&quot; is disabled ?';
$lang['select_language'] = 'Select your preferred language from the list below. The selection will be used during this installation/upgrade, but will not affect the site\'s CMSMS installation.';
//$lang['send_admin_email'] = 'Send Admin login credentials email';
$lang['server_api'] = 'API';
$lang['server_info'] = 'Server';
$lang['server_os'] = 'Operating system';
$lang['server_software'] = 'Software';
$lang['session_capabilities'] = 'Testing for proper session capabilities (sessions are using cookies and session save path is writeable, etc)';
$lang['session_save_path_exists'] = 'Session_save_path exists ?';
$lang['session_save_path_writable'] = 'Session_save_path is writeable ?';
$lang['session_use_cookies'] = 'PHP sessions use cookies ?';
$lang['sometests_failed'] = 'The installer has performed numerous tests of the site\'s current web environment. Although no critical issues were found, it is recommended that the following items be corrected before continuing.';
$lang['step1_advanced'] = 'Advanced Mode';
$lang['step1_destdir'] = 'Top-Level Directory';
$lang['step1_info_destdir'] = '<strong>Warning:</strong> This program can install or upgrade multiple installations of CMS Made Simple. It is important that you select the correct directory for installation or upgrading.';
$lang['step1_language'] = 'Language';
//$lang['step1_title'] = 'Select Language';
$lang['step2_cmsmsfound'] = 'An installation of CMS Made Simple was found. It is possible to upgrade this installation. Before proceeding, ensure that you have a current <strong>VERIFIED</strong> backup of all its files and its database.';
$lang['step2_cmsmsfoundnoupgrade'] = 'Although an installation of CMS Made Simple was found, it is not possible to upgrade this version using this application. The version might be too old.';
$lang['step2_confirminstall'] = 'Are you sure you would like to install CMS Made Simple';
$lang['step2_confirmupgrade'] = 'Are you sure you would like to upgrade CMS Made Simple';
$lang['step2_errorsamever'] = 'The selected directory appears to contain a CMSMS installation with the same version that is included in this assistant. Continuing will freshen the installation.';
$lang['step2_errortoonew'] = 'The selected directory appears to contain a CMSMS installation with a newer version that is included in this assistant. Cannot proceed';
$lang['step2_info_freshen'] = 'Freshening the installation involves replacing all core files. The database will not be touched.';
$lang['step2_installdate'] = 'Approximate installation date';
$lang['step2_install_dirnotempty2'] = 'The installation folder already contains some files and/or folders.  Although it is possible to install CMSMS here, doing so might corrupt something else already there.  Please double check the contents of this folder.  For reference some of the files are listed below.  Please ensure that this is correct.';
$lang['step2_hdr_upgradeinfo'] = 'Version information';
$lang['step2_info_upgradeinfo'] = 'Listed below are the releases later than the installed version. Clicking the corresponding button will display details about changes in that version. There might be further instructions or warnings in each version that could affect the upgrade process.';
$lang['step2_minupgradever'] = 'The minimum version that this application can upgrade from is: %s. You might need to upgrade your application to a newer version in stages, using another method before completing the upgrade process. Please ensure that you have a complete, verified backup before using any upgrade method.';
$lang['step2_nocmsms'] = 'It looks like this is a new installation.';
$lang['step2_nofiles'] = 'As requested, CMSMS Core files will not be processed during this process';
$lang['step2_passed'] = 'Passed';
$lang['step2_pwd'] = 'Current working directory';
$lang['step2_schemaver'] = 'Database schema version';
$lang['step2_version'] = 'Current version';
$lang['step3_failed'] = 'The installer has performed numerous tests of the site\'s PHP environment, and one or more of those tests have failed. Those errors will need to be rectified before continuing. After that\'s done, click &quot;Retry&quot; below.';
$lang['step3_passed'] = 'The installer has performed numerous tests of the site\'s PHP environment, and they have all passed. This is great news!';
$lang['step9_removethis'] = '<strong>Warning</strong> For security, it is important that the installation file be removed from the root directory of the website, after verification that everything is working as expected.';
$lang['symbol'] = 'Symbol';
$lang['social_message'] = 'I have successfully installed CMS Made Simple!';
$lang['support_channels'] = 'Support channels'; //finished-message link-text
$lang['support_payments'] = 'contributions'; //finished-message link-text

// T
$lang['test_failed'] = 'A required test failed';
$lang['test_passed'] = 'A test passed <em>(passed tests are only displayed in advanced mode)</em>';
$lang['test_warning'] = 'A setting is above the required value, but below the recommended value, or...<br />A capability that might be required for some optional functionality is unavailable';
$lang['th_status'] = 'Status';
$lang['th_testname'] = 'Test';
$lang['th_value'] = 'Value';
$lang['title_api_docs'] = 'Official API Documentation';
$lang['title_docs'] = 'Official Documentation';
$lang['title_error'] = 'Houston, we have a problem!';
$lang['title_forum'] = 'Support Forum';
$lang['title_share'] = 'Share your experience with your friends.';
$lang['title_step2'] = 'Step 2 - Detect Existing Content';
$lang['title_step3'] = 'Step 3 - Compatibility Tests';
$lang['title_step4'] = 'Step 4 - Configuration Information';
$lang['title_step5'] = 'Step 5 - Site Settings';
$lang['title_step6'] = 'Step 6 - Site Administrator Information';
$lang['title_step7'] = 'Step 7 - Install Files';
$lang['title_step8'] = 'Step 8 - Setup System';
$lang['title_step9'] = 'Step 9 - Finish';
$lang['title_warning'] = 'The omens are not entirely good!';
$lang['title_welcome'] = 'Welcome';
$lang['tmp_dirs_empty'] = 'Temporary directories are empty or absent ?';
$lang['tmpfile'] = 'tmpfile() method is available ?';
$lang['to'] = 'to';

// U
$lang['uninstall_module'] = 'Uninstall module %s';
$lang['upgrade_deleteoldevents'] = 'Delete old events';
$lang['upgrade_deletetable'] = 'Deleted old table %s';
$lang['upgrade_modifytable'] = 'Modified table %s';
$lang['upgrade_modulebad'] = '%s module upgrade failed';
$lang['upgrade'] = 'Upgrade';
$lang['upgrading_schema'] = 'Update database schema';
$lang['upload_max_filesize'] = 'Maximum size of uploaded files is sufficient ?';
$lang['username'] = 'Login/Account';

// V
$lang['version_writable'] = 'Version-data file can be saved ?';

// W
$lang['warn_default_charset'] = 'The site\'s default character-encoding is: %s.  A value other than UTF-8 may cause difficulties with text processing in non-english languages';
$lang['warn_disable_functions'] = 'Note: one or more PHP core functions are disabled. This can have negative impact on your CMSMS installation, particularly with third party extensions. Please keep an eye on your error log. Your disabled functions are: <br /><br />%s';
$lang['warn_max_execution_time'] = 'Although the max execution time (%s) meets or exceeds the minimum value of %s, we recommend it be increased to %s or greater';
$lang['warn_memory_limit'] = 'The memory limit value is %s, which is above the minimum of %s. However, %s is recommended';
$lang['warn_open_basedir'] = 'open_basedir is enabled in the php configuration.  Although you may continue, CMSMS will not support installs with open_basedir restrictions.';
$lang['warn_post_max_size'] = 'The post max size value is %s, which is above the minimum of %s, however %s is recommended. Also, please ensure that this value is larger than the upload_max_filesize setting';
$lang['warn_pwcommon'] = 'The password is too common';
$lang['warn_pwdiscuss'] = 'You might like to discuss the database password with the db administrator:';
$lang['warn_pwenviron'] = 'The password uses identifiable information and is guessable';
$lang['warn_pwperms'] = 'You might like to discuss the database permissions with the db administrator.<br \>Some of them might be un-necessarily risky:';
$lang['warn_pwweak'] = 'The password is weaker than it arguably should be';
$lang['warn_tests'] = '<strong>Note:</strong> passing all of these tests should ensure that CMSMS will function properly for most sites. However, as the site grows and more functionality is added, these minimal values might become insufficient. Additionally, non-core (independently-developed) modules might have further requirements to function properly';
$lang['warn_upload_max_filesize'] = 'The current upload_max_filesize setting (%s) is sufficient, but increasing it to at least %s is recommended';
$lang['warn_url_fopen'] = 'The allow_url_fopen setting (%s) is risky.';
$lang['welcome_message'] = 'This CMS Made Simple utility allows you to quickly and easily confirm that the website\'s host is compatible with CMSMS, and then to install, upgrade or refresh CMSMS.';
$lang['wizard_step1'] = 'Welcome';
$lang['wizard_step2'] = 'Detect Existing Content';
$lang['wizard_step3'] = 'Compatibility Tests';
$lang['wizard_step4'] = 'Configuration Info';
$lang['wizard_step5'] = 'Site Settings';
$lang['wizard_step6'] = 'Site Administrator Information';
$lang['wizard_step7'] = 'Install Files';
$lang['wizard_step8'] = 'Setup System';
$lang['wizard_step9'] = 'Finish';

// X
$lang['xml_functions'] = 'PHP XML extension is available ?';

// Y
$lang['yes'] = 'Yes';

// Z
