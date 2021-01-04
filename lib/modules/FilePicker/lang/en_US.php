<?php
$lang = [
//*
// C
'cancel' => 'Cancel',
'changedir' => 'Display directory %s', //tooltip
'choose' => 'Choose',
'chooseit' => 'Select %s', //tooltip
'clear' => 'Clear',
'confirm' =>'Confirm',
'confirm_delete' => 'Are you sure you want to delete this?',
'create_dir' => 'Create a new directory here',

// D
'dimension' => 'Dimensions',
'directory_named' => 'Directory %s', //tooltip c.f. changedir
'delete' => 'Delete',
'deleteit' => 'Delete %s', //tooltip
'dependencies' => 'Dependencies',
'displayit' => 'Display %s', //tooltip
'displayup' => 'Display parent directory', //tooltip
'dropshort' => 'Drop Zone',
'droplong' => 'You may drop here file(s) dragged from your file manager, to automatically begin uploading', //tooltip

// E
'error_ajax_invalidfilename' => 'Filename is invalid',
'error_ajax_fileexists' => 'A file or directory with that name already exists',
'error_ajax_mkdir' => 'A problem occurred creating the directory %s',
'error_ajax_writepermission' => 'You do not have permission to write to this directory',
'error_failed_ajax' => 'A problem occurred with an ajax request',
'error_problem_upload' => 'A problem occurred uploading',
'error_title' => 'Reported error(s)',
'error_upload_ext' => '%s extension is not acceptable',
'error_upload_size' =>'%s is too large',
'error_upload_type' => '%s type is not acceptable',
'error_upload_acceptFileTypes' => 'Files of this type are not acceptable in this scope.',
'error_upload_maxFileSize' => 'The file is too large',
'error_upload_minFileSize' => 'The file is too small',
'error_upload_maxNumberOfFiles' => 'You are uploading too many files at once',
'error_upload_maxTotalSize' => 'The total size of files to upload exceeds the limit specified in the PHP configuration',

// F
'filename' => 'Filename',
'filterby' => 'Filters',
'filepickertitle' => 'File Picker',
'fileview' => 'File view',
//*/
'friendlyname' => 'Folder Controls', //was 'File Picker'

//M
'moddescription' => 'Manage property-sets applied to filesystem directories',

//*
// N
'na' => 'Not available',
'no' => 'No',
'name' => 'Name',

// O
'ok' => 'Ok',

// P
'perm_r' => 'read',
'perm_w' => 'write',
'perm_x' => 'exec',
'perm_xf' => 'enter',

// S
'select_an_audio_file' => 'Select an Audio File',
'select_a_video_file' => 'Select a Video File',
'select_a_media_file' => 'Select a Media File',
'select_a_document' => 'Select a Document',
'select_an_archive_file' => 'Select an Archive File',
'select_a_file' => 'Select a File',
'select_an_image' => 'Select an Image',
'select_file' => 'Select File', //popup dialog title
'select_upload_files' => 'Select file(s) to upload to here', //tooltip
'show_thumbs' => 'Show thumbnails',
'size' => 'Size',
'submit' => 'Submit',
'switcharchive' => 'Only show archive files',
'switchaudio' => 'Only show audio files',
'switchfiles' => 'Only show regular files',
'switchgrid' => 'Display files in a grid',
'switchimage' => 'Only show image files',
'switchlist' => 'Display files as a list',
'switchreset' => 'Show all files',
'switchvideo' => 'Only show video files',

// U
'unknown' => 'Unknown',
'upload' => 'Upload',

// Y
'yes' => 'Yes',
'youareintext' => 'The current working directory (relative to the top of the installation)',
//*/
// PROFILE-RELATED TEXT

'add_profile' => 'Add a new profile',

'can_delete' => 'Allow file deletion',
'can_mkdir' => 'Allow directory creation',
'can_mkfile' => 'Allow file creation',
'can_upload' => 'Allow uploads',

'delete_profile' => 'Delete Profile',

'edit_profile' => 'Edit Profile',

'hdr_add_profile' => 'New profile',
'hdr_edit_profile' => 'Edit profile',
'HelpPopupTitle_ProfileName' => 'Profile Name',
'HelpPopup_ProfileName' => 'Each profile should have a simple, unique name.  Names should only contain alphanumeric characters, and/or underscore(s).',
'HelpPopupTitle_ProfileCan_Delete' => 'Allow deleting files and directories',
'HelpPopup_ProfileCan_Delete' => 'Optionally allow users to delete files during the selection process',
'HelpPopupTitle_ProfileCan_Mkdir' => 'Allow new directories',
'HelpPopup_ProfileCan_Mkdir' => 'Optionally allow users to create new directories (below the specified top directory) during the selection process.',
'HelpPopupTitle_ProfileCan_Upload' => 'Allow uploading',
'HelpPopup_ProfileCan_Upload' => 'Optionally allow users to upload files during the selection process',
'HelpPopupTitle_ProfileDir' => 'Top Directory',
'HelpPopup_ProfileDir' => 'Optionally enter the relative path of a directory (relative to the uploads path) to restrict operations to.',
'HelpPopupTitle_ProfileShowthumbs' => 'Show Thumbnails',
'HelpPopup_ProfileShowthumbs' => 'If enabled, thumbnails will be visible for image files for which thumbnails are generated.',

'no_profiles' => 'No profile is recorded. You can add one by clicking the icon above.',

'th_created' => 'Created',
'th_default' => 'Default',
'th_id' => 'ID',
'th_last_edited' => 'Last Edited',
'th_name' => 'Name',
'th_reltop' => 'Top Directory',
'title_mkdir' => 'Create Directory',
'topdir' => 'Top directory',
'type' => 'Type',

// HELP TEXT TODO profiles-only
'help' => <<<'EOT'
<h3>What does this do?</h3>
<p>This module provides some file-related capabilities for use by other modules:
<ol>
<li>select files</li>
<li>upload files</li>
<li>delete files</li>
<li>create and remove sub-directories</li>
<li>accumulate properties of file(s) and directories for some purpose e.g.
<ul>
<li>for upload</li>
<li>to use in a WYSIWYG field</li>
<li>to associate an image or thumbnail with a page</li>
<li>to attach to a news article</li>
</ul>
</li>
<li>manage directory profiles</li>
</ol>
</p>
<h3>How is it used?</h3>
<p>This module can be used by other modules via various API's, or via the {cms_filepicker} plugin.</p>
<p>Additionally, this module can be called directly via the <code>{cms_module module=FilePicker action=select name=string [profile=string] [type=string] [value=string]}</code> tag, but this is not recommended. Refer to the {cms_filepicker} plugin for information about the parameters.</p>
<p>Profiles can be used by the {cms_filepicker} plugin or by this module's &quot;select&quot; action when definining how the picker should behave.   Other module parameters, or user permissions, can override the settings defined in the profile.</p>
<h3>Support</h3>
<p>As per the license, this software is provided as-is. Please read the text of the license for the full disclaimer.</p>
<h3>Copyright and License</h3>
<p>Copyright &copy; 2017-2018, JoMorg and calguy1000, &copy; 2019-2021, CMSMS Foundation. All rights reserved.</p>
<p>This module has been released under the <a href="http://www.gnu.org/licenses/licenses.html#GPL">GNU Public License</a>. The module must not be used otherwise than in accordance with that license, or a later version of that licence granted by the module distributor.</p>
EOT
,

] + $lang;
