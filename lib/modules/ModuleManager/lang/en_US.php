<?php

$lang = [

// A
'about_title' => 'About the %s module',
'admin_title' => 'Module Manager Admin Panel',
'abouttxt' => 'About',
'accessdenied' => 'Access denied. Please check your permissions',
'action_activated' => 'Module %s has been activated',
'action_installed' => 'Module %s has been installed with the following message(s):<br /><br />%s',
'action_upgraded' => 'Module %s has been upgraded',
'action' => 'Action',
'active' => 'Active',
'admindescription' => 'Manage the modules which provide custom functionality to the website',
'advancedsearch_help' => 'Specify words to include or exclude from the search using a \'+\' or \'-\', surround exact phrases with quotes e.g. +red -apple +"some text"',
'allowuninstall' => 'Allow Module Manager to be uninstalled? Be careful, the uninstallation is irreversible!',
'all_modules_up_to_date' => 'No module newer than the ones installed is available in the repository',
'availablemodules' => 'The current status of modules available from the current repository',
'available_updates' => 'One or more modules are available for upgrade; Before upgrading, please read the about information for the release and make sure you have a current backup of this website.',
'availmodules' => 'Available Modules',

// B
'back' => 'Back',
'back_to_module' => 'Return to Module Manager',

// C
'cancel' => 'Cancel',
'caninstall' => 'This module is ready to install',
'cantdownload' => 'Cannot download',
'cantinstall' => 'Cannot install (file-storage inhibited)',
'cantremove' => 'Cannot remove',
'cantuninstall' => 'Cannot uninstall',
'cantupgrade' => 'Cannot upgrade (file-storage inhibited)',
'changeperms' => 'Change Permissions',
'confirm_action' => 'Yes, I am sure',
'confirm_chmod' => 'Continuing will attempt to change the permissions on this modules files.  Are you sure you want to continue?',
'confirm_resetcache' => 'Are you sure you want to clear the local cache?',
'confirm_reseturl' => 'Are you sure you want to reset the repository URL?',
'confirm_settings' => 'Are you sure you want to save these settings?',
'confirm_remove' => 'Are you sure you want to remove this modules files from the file system',
'confirm_upgrade' => 'Are you sure you want to upgrade this module?',
'compatibility_disclaimer' => 'Some of the modules have been created by the CMSMS team, and others are by independent developers. The modules might be incompatible with this system, or not tested, or perhaps not even functional.  Please read the information presented via the "help" and "about" links for a module, before attempting its installation.',

// D
'db_newer' => 'Database Version Newer', // e_status title
'dependstxt' => 'Dependencies',
'depend_activate' => 'Module %s will be activated.',
'depend_customization' => '<strong>Warning:</strong> This module has customized templates and/or language strings. Continuing might generate errors or you might have difficulty with new functionality.',
'depend_install' => 'Module %s (version %s) will be installed.',
'depend_upgrade' => 'Module %s will be upgraded to version %s.',
'depends_upon' => 'Depends on',
'display_in_english' => 'Display in English',
'display_in_mylanguage' => 'Display in NAME LANGUAGE HERE',
'download' => 'Download &amp; Install',
'downloads' => 'Downloads',

// E
'entersearchterm' => 'Enter search term',
'error' => 'Error!',
'err_nothingtodo' => 'Nothing to do',
'err_xml_open' => 'Probllem opening XML file to import',
'err_xml_moduleinstalled' => 'Cannot import XML: This module is already installed and loaded',
'err_xml_dtdmismatch' => 'The DTD Version in the specified XML file does mot match what we expected.  Perhaps the file is too old?',
'err_xml_oldermodule' => 'The XML file you are attempting to import contains an older version of a module that is already installed',
'err_xml_sameversion' => 'The XML file you are attempting to import contains the same version of a module that is already installed',
'err_xml_moduleincompatible' => 'The module contained within this XML file is not compatible with the present CMSMS version',
'err_xml_invalid' => 'Your XML file seems to be invalid or corrupt',
'error_active_failed' => 'The operation to toggle the active state of a module failed',
'error_checksum' => 'Checksum error. This probably indicates a corrupt file, either when it was uploaded to the repository, or a problem in transit down to your machine. (expected %s and got %s)',
'error_chmodfailed' => 'One or more problems encountered when changing permissions of files',
'error_connectnomodules' => 'No module matches your search criteria.',
'error_downloadxml' => 'A problem occurred downloading the XML file: %s',
'error_dependencynotfound' => 'One or more dependencies could not be found in the repository',
'error_dependencynotfound2' => 'The module %s (%s) could not be found in the repository, but it is a dependency.<br/>This module might no longer be available in the module repository, or might require manual installation.<br />Please ensure that it is installed correctly, then retry this operation.',
'error_fileupload' => 'A problem occurred uploading the file',
'error_getmodule' => 'A problem occurred instantiating %s',
'error_internal' => 'Internal Error... Please report this to the system administrator',
'error_invaliduploadtype' => 'The file uploaded is not valid for this field',
'error_minimumrepository' => 'The repository version is not compatible with the present CMSMS version',
'error_missingparam' => 'A required parameter was missing or invalid',
'error_missingmoduleinfo' => 'Problem retrieving module information for module %s',
'error_moduleexport' => 'Module export failed',
'error_moduleinstallfailed' => 'Module installation failed',
'error_moduleremovefailed' => 'Failed to remove module',
'error_moduleuninstallfailed' => 'Module uninstallation failed',
'error_moduleupgradefailed' => 'Module upgrade failed',
'error_module_object' => 'Error: could not get an instance of the %s module',
'error_nodata' => 'No data retrieved',
'error_nofilename' => 'No filename parameter supplied',
'error_nofilesize' => 'No filesize parameter supplied',
'error_nofileuploaded' => 'Please upload a module XML file',
'error_nomatchingmodules' => 'Error: could not find any matching modules in the repository',
'error_nomodules' => 'Error: could not retrieve list of installed modules',
'error_norepositoryurl' => 'The URL for the Module Repository has not been specified',
'error_noresults' => 'We expected some results to be available from queued operations, but none were found. Please try to reproduce this experience, and provide sufficient information to support personnel for diagnoses.',
'error_notconfirmed' => 'The operation was not confirmed',
'error_nothingtodo' => 'Oops.  You requested an action, but we didn\'t calculate anything to do.  This probably means some kind of bug.',
'error_notxmlfile' => 'The file uploaded was not an XML file',
'error_permissions' => '<strong><em>WARNING:</em></strong> Insufficient directory permissions to install modules.  You might also be experiencing problems with PHP Safe mode.  Please ensure that safe mode is disabled, and that file system permissions are sufficient.',
'error_request_problem' => 'A problem occurred communicating with the module server',
'error_search' => 'Search Error',
'error_searchterm' => 'You have entered an invalid search term.  The term must consist of ASCII characters and be three or more characters long',
'error_skipping' => 'Skipping install/upgrade of %s due to errors in setting up dependencies. Please see message above, and try again.',
'error_unsatisfiable_dependency' => 'Cannot find the required module "%s" (version %s or later) in the repository. It is directly required by %s; this could indicate a problem with the version of this module in the repository. Please contact the module\'s author. Aborting.',
'error_upgrade' => 'Upgrade of module %s failed!',
'export' => 'Export',

// F
'friendlyname' => 'Modules Manager',

// G
'general_notice' => 'This shows the latest XML files in the CMSMS modules-repository.  The modules might or might not be the latest available versions. Additionally, the module repository might only contain modules released within the last few months.',

// H
'helptxt' => 'Help',
'help_allowuninstall' => 'If enabled, then this module can be uninstalled.  This option is provided to prevent the accidental removal of this module which would result in an unrecoverable error',
'help_disable_caching' => 'TODO',
'help_dl_chunksize' => 'This parameter specifies the size <em>(in kilobytes)</em> of each chunk of data that will be downloaded from the repository when requesting a module.',
'help_latestdepends' => 'When installing a module with dependencies, this will ensure that the newest version of a dependent module is installed',
'help_mm_importxml' => 'This form allows importing a module XML file that you received from another user, or downloaded from the <a class="external" href="http://dev.cmsmadesimple.org" target="_blank">CMSMS Forge</a>',

// I
'importxml' => 'Import Module',
'incompatible' => 'Incompatible',
'info_searchtab' => 'This tab displays a list of installed modules for which there is a newer version available',
'install' => 'Install',
'installation_complete' => 'Installation Process Complete!',
'installed' => 'Installed',  // e_status title etc
'install_module' => 'Install Module',
'install_procede' => 'Proceed',
'install_submit' => 'Install',
'install_with_deps' => 'Evaluate all Dependencies and Install',
'instcount' => 'Modules currently installed',

// L
'latestdepends' => 'Always install the newest dependency module',

// M
'minversion' => 'Minimum Version',
'missingdeps' => 'Missing dependencies',
'mod_name_ver' => '%s version %s',
'module' => 'Module',
'moddescription' => 'A client for the CMS Made Simple&trade; modules repository. This module allows previewing and installing modules from the CMSMS Forge without the need for ftp-ing, or unzipping archives.  Module XML files are downloaded using REST, integrity verified, and then expanded automatically.',
'msg_batch_completed' => '%d operations completed',
'msg_cachecleared' => 'Cache cleared',
'msg_cancelled' => 'Operation canceled',
'msg_module_activated' => 'Module %s activated',
'msg_module_chmod' => 'Permissions changed',
'msg_module_deactivated' => 'Module %s deactivated',
'msg_module_exported' => 'Module %s exported to XML',
'msg_module_imported' => 'Module imported',
'msg_module_installed' => 'Module %s successfully installed',
'msg_module_removed' => 'Module files permanently removed',
'msg_module_uninstall' => '<strong>Warning:</strong> Uninstalling a module typically deletes all of the data owned by that module, along with all preferences and templates.  Please use extreme caution.',
'msg_module_uninstalled' => 'Module %s successfully uninstalled. Templates and data associated with this module have been deleted',
'msg_module_upgraded' => 'Module %s successfully upgraded',
'msg_nodependencies' => 'This file has not listed any dependencies',
'msg_prefssaved' => 'Preferences Updated',
'msg_urlreset' => 'The modules-repository URL has been reset to its default value',

// N
'nametext' => 'Module Name',
'need_upgrade' => 'Needs Upgrade', // e_status title
'newerversion' => 'Newer version installed',
'newer_available' => 'New Version Available', // e_status title
'notavailable' => 'Cannot be loaded, possibly due to dependencies',
'notcompatible' => 'Not compatible',
'notice' => 'Notice',
'notinstalled' => 'Not installed',

// O
'onlynewesttext' => 'Show only newest version',
'operation_results' => 'Operation Results',

// P
'postinstall' => 'Module Manager has been successfully installed.',
'postuninstall' => 'Module Manager has been uninstalled.  Users will no longer have the ability to install modules from the remote repository.  However, local installation is still possible.',
'preferences' => 'Preferences',
'preferencessaved' => 'Preferences saved',
'prompt_advancedsearch' => 'Advanced Search',
'prompt_disable_caching' => 'Disable caching of requests from the server',
'prompt_dl_chunksize' => 'Download Chunk Size (kB)',
'prompt_otheroptions' => 'Other Options',
'prompt_repository_url' => 'Modules-Repository URL',
'prompt_settings' => 'Settings',

// R
'really_uninstall' => 'Are you sure you want to uninstall? You will be missing a lot of nice functionality.',
'releasedate' => 'Date',
'remove' => 'Remove',
'repositorycount' => 'Modules found in the repository',
'reset' => 'Reset',
'reset_cache' => 'Reset Cache',

// S
'search' => 'Search',
'searchterm' => 'Search Term',
'search_input' => 'Search Input',
'search_noresults' => 'Search succeeded but no result matched the expression',
'search_results' => 'Search Results',
'sizetext' => 'Size (Kilobytes)',
'status' => 'Status',
'statustext' => 'Status/Action',
'submit' => 'Submit',
'success' => 'Success',

// T
'tab_newversions' => '%s Upgrade(s) Available',
'time_warning' => 'Installing modules is a data and memory intensive operation. Depending upon the network bandwidth, server load, and installation tasks that need to be performed this could take several minutes.  Also, installing modules might cause problems with a working installation.  It is recommended that you have a verified backup of your site before proceeding.',
'title_' => '', // no title for no-status items
'title_active' => 'Currently active. Click to toggle to inactive/unusable. No module data would be affected.',
'title_advancedsearch' => 'Enable advanced search functionality',
'title_cantremove' => 'The file-system permissions on this module\'s directory and/or its contents prevent deleting some or all items',
'title_cantuninstall' => 'This module is used by other installed module(s), and so cannot be uninstalled',
'title_chmod' => 'Attempt to recursively change permissions on this directory',
'title_db_newer' => 'The version number stored in the database is greater than the one in the module.',
'title_depends_upon' => 'This module depends on on other modules for its functionality.',
'title_deprecated' => 'This icon indicates a deprecated module (development has stopped and there will be no new release).',
//'title_has_dependents' => 'This module is used by other installed modules, and so cannot be uninstalled',
'title_inactive' => 'Currently not active. Click to toggle to active/usable.',
'title_install' => 'Install this module',
'title_installed' => 'This module is currently installed and available for use.',
'title_letter' => 'Show modules whose name starts with %s',
'title_missingdeps' => 'This icon indicates a module which cannot be installed or upgraded due to missing pre-requisite(s)',
'title_missingdeps2' => 'Missing Requisites',
'title_mm_importxml' => 'Import module from XML',
'title_moduleabout' => 'View the author and changelog information for this module',
'title_moduleaction' => 'This column displays the actions available for each module',
'title_moduleactive' => 'This column displays the active state of installed modules. In most cases, the state may be toggled by clicking the icon. Inactive modules are not loaded and cannot be used, but their data remain intact.',
'title_moduledepends' => 'View the dependencies for this module',
'title_moduledownloads' => 'This column displays the approximate number of downloads for each release of the module',
'title_moduledownloads2' => 'This column displays the approximate number of downloads for the newest version of the module',
'title_moduleexport' => 'Export this module to XML for sharing',
'title_modulehelp' => 'View basic usage instructions for this module',
'title_moduleinstallupgrade' => 'Install or Upgrade this module',
'title_modulelastreleasedate' => 'This column displays the date of the last release for the module',
'title_modulelastversion' => 'This column displays the version number of the last release for the module',
'title_modulereleaseabout' => 'View the author and changelog information for this release',
'title_modulereleasedate' => 'This column displays the release date of the module',
'title_modulereleasedepends' => 'View the dependencies for this release',
'title_modulereleasehelp' => 'View the documentation supplied with this release',
'title_modulesize2' => 'This column displays the size of each module\'s XML file to be downloaded (in kilobytes)',
'title_modulestatus' => 'This column displays the status for each module',
'title_moduletotaldownloads' => 'This column displays the approximate total downloads for all released versions of the module',
'title_moduleversion' => 'This column displays the module version',
'title_need_upgrade' => 'The upgrade routine needs to be run on this module',
'title_new' => 'This module was released/updated within the last month',
'title_newer_available' => 'A newer version of this module is available in the repository',
'title_newmoduleversion' => 'This column displays the version number of the most recent release of the module',
'title_notavailable' => 'This is not ready for use at this time',
'title_notcompatible' => 'This module has not passed tests for compatibility with the present version of CMSMS',
'title_notinstalled' => 'This module exists in the modules subdirectory but has not been installed for use',
'title_remove' => 'Remove this modules files from the module directory',
'title_searchterm' => 'Enter a natural language search term.  If advanced mode is enabled, then boolean operations similar to Google can be used',
'title_stale' => 'This module is marked &quot;stale&quot; (last release over two years ago). This means it might work fine, but it has not had any recent development. Use your own discretion when using this module!',
'title_star' => 'This icon indicates that a newer version of this module is available in the repository',
'title_system' => 'This icon indicates a system module (distributed with the CMSMS core)',
'title_uninstall' => 'Uninstall this module. This action might destroy data and templates associated with the module',
'title_upgrade' => 'Upgrade this module',
'title_warning' => 'This module was released some time ago. Be careful!',
'title_yourmoduledate' => 'This column displays the date of the latest release for this module',
'title_yourmoduleversion' => 'This column displays the version number of module that is currently installed',

// U
'uninstall_module' => 'Uninstall Module',
'uninstall' => 'Uninstall',
'uninstalled' => 'Module Uninstalled',
'unknown' => 'Unknown',
'upgrade' => 'Upgrade',
'upgraded' => 'Module upgraded to version %s',
'upgrade_available' => 'Newer version available (%s), you have (%s)',
'upgrade_module' => 'Upgrade module',
'uploadfile' => 'Upload XML File',
'uptodate' => 'Installed',
'use_at_your_own_risk' => 'Use at <strong>your own risk</strong>',

// V
'version' => 'Version',
'versionsformodule' => 'Available versions of the module %s',

// W
'warning' => 'Warning',
'warn_dependencies' => 'The module you selected to install or upgrade depends on one or more additional modules that must also be installed or upgraded.',
'warn_modulecustom' => 'The following modules have customizations in the &lt;root&gt;/assets/module_custom directory of your installation.  These customizations might or might not cause errors after the upgrade.  You might need to remove or revise these customizations to restore proper functionality.  Proceed with caution.',
// X
'xmltext' => 'XML File',
'xmlstatus' => 'XML package created for %s, containing %d files',

// Y
'yes' => 'Yes',
'yourversion' => 'Your Version',

] + $lang;

$lang['help'] = <<<'EOS'
<h3>What Does This Do?</h3>
<p>A client for the CMS Made Simple Module Repository. This module allows previewing and installing modules from the CMSMS Forge without the need for ftp-ing, or unzipping archives.  Module XML files are downloaded using REST, integrity verified, and then expanded automatically.</p>
<h3>How is it used</h3>
<p>In order to use this module you will need the 'Modify Modules' permission.</p>
<br />
<p>You can find the interface for this module under the 'Site Admin' menu.  When you select this module, the 'Module Repository' installation will automatically be queried for a list of its available XML modules.  This list will be cross-referenced with the list of currently installed modules, and a summary page displayed.  From there, you can view the descriptive information, the Help, and the About information for a module without physically installing it.  You can also choose to upgrade or install modules.</p>
<h3>Support</h3>
<p>As per the license, this software is provided as-is. Please read the text of the license for the full disclaimer.</p>
<h3>Copyright and License</h3>
<p>Copyright &copy; 2006-2021 CMS Made Simple Foundation <a href="mailto:foundation@cmsmadesimple.org">&lt;foundation@cmsmadesimple.org&gt;</a>. All rights reserved.</p>
<p>This module has been released under the <a href="http://www.gnu.org/licenses/licenses.html#GPL">GNU General Public License</a>. The module may not be distriubted or used except in accord with that license.</p>
EOS;
