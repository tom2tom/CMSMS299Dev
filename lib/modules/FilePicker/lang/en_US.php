<?php

// C
$lang['cancel'] = 'Cancel';
$lang['changedir'] = 'Display directory %s'; //tooltip
$lang['chooseit'] = 'Select %s'; //tooltip
$lang['clear'] = 'Clear';
$lang['confirm_delete'] = 'Are you sure you want to delete this?';
$lang['create_dir'] = 'Create a new directory here';

// D
$lang['dimension'] = 'Dimensions';
$lang['diretory_named'] = 'Directory %s'; //tooltip c.f. changedir
$lang['delete'] = 'Delete';
$lang['deleteit'] = 'Delete %s'; //tooltip
$lang['dependencies'] = 'Dependencies';
$lang['displayit'] = 'Display %s'; //tooltip
$lang['displayup'] = 'Display parent directory'; //tooltip
$lang['dropshort'] = 'Drop Zone';
$lang['droplong'] = 'You may drop here file(s) dragged from your file manager, to automatically begin uploading'; //tooltip

// E
$lang['error_ajax_invalidfilename'] = 'That filename is invalid';
$lang['error_ajax_fileexists'] = 'A file or directory with that name already exists';
$lang['error_ajax_mkdir'] = 'A problem occurred creating the directory %s';
$lang['error_ajax_writepermission'] = 'You do not have permission to write to this directory';
$lang['error_failed_ajax'] = 'A problem occurred with an ajax request';
$lang['error_problem_upload'] = 'A problem occurred uploading';
$lang['error_upload_acceptFileTypes'] = 'Files of this type are not acceptable in this scope.';
$lang['error_upload_maxFileSize'] = 'The file is too large';
$lang['error_upload_minFileSize'] = 'The file is too small';
$lang['error_upload_maxNumberOfFiles'] = 'You are uploading too many files at once';

// F
$lang['filename'] = 'Filename';
$lang['filterby'] = 'Filters';
$lang['filepickertitle'] = 'File Picker';
$lang['fileview'] = 'File view';
$lang['friendlyname'] = 'File Picker';

//M
$lang['moddescription'] = 'Manage custom property-sets for directories';

// N
$lang['na'] = 'Not available';
$lang['no'] = 'No';
$lang['name'] = 'Name';

// O
$lang['ok'] = 'Ok';

// P
$lang['perm_r'] = 'read';
$lang['perm_w'] = 'write';
$lang['perm_x'] = 'exec';
$lang['perm_xf'] = 'enter';

// S
$lang['select_an_audio_file'] = 'Select an Audio File';
$lang['select_a_video_file'] = 'Select a Video File';
$lang['select_a_media_file'] = 'Select a Media File';
$lang['select_a_document'] = 'Select a Document';
$lang['select_an_archive_file'] = 'Select an Archive File';
$lang['select_a_file'] = 'Select a File';
$lang['select_an_image'] = 'Select an Image';
$lang['select_file'] = 'Select File'; //popup dialog title
$lang['select_upload_files'] = 'Select file(s) to upload to here'; //tooltip
$lang['show_thumbs'] = 'Show thumbnails';
$lang['size'] = 'Size';
$lang['submit'] = 'Submit';
$lang['switcharchive'] = 'Only show archive files';
$lang['switchaudio'] = 'Only show audio files';
$lang['switchfiles'] = 'Only show regular files';
$lang['switchgrid'] = 'Display files in a grid';
$lang['switchimage'] = 'Only show image files';
$lang['switchlist'] = 'Display files as a list';
$lang['switchreset'] = 'Show all files';
$lang['switchvideo'] = 'Only show video files';

// U
$lang['unknown'] = 'Unknown';
$lang['upload'] = 'Upload';

// Y
$lang['yes'] = 'Yes';
$lang['youareintext'] = 'The current working directory (relative to the top of the installation)';

// PROFILE-RELATED TEXT

$lang['add_profile'] = 'Add a new profile';

$lang['can_delete'] = 'Allow file deletion';
$lang['can_mkdir'] = 'Allow directory creation';
$lang['can_mkfile'] = 'Allow file creation';
$lang['can_upload'] = 'Allow uploads';

$lang['delete_profile'] = 'Delete Profile';

$lang['edit_profile'] = 'Edit Profile';

$lang['hdr_add_profile'] = 'New profile';
$lang['hdr_edit_profile'] = 'Edit profile';
$lang['HelpPopupTitle_ProfileName'] = 'Profile Name';
$lang['HelpPopup_ProfileName'] = 'Each profile should have a simple, unique name.  Names should only contain alphanumeric characters, and/or underscore(s).';
$lang['HelpPopupTitle_ProfileCan_Delete'] = 'Allow deleting files and directories';
$lang['HelpPopup_ProfileCan_Delete'] = 'Optionally allow users to delete files during the selection process';
$lang['HelpPopupTitle_ProfileCan_Mkdir'] = 'Allow new directories';
$lang['HelpPopup_ProfileCan_Mkdir'] = 'Optionally allow users to create new directories (below the specified top directory) during the selection process.';
$lang['HelpPopupTitle_ProfileCan_Upload'] = 'Allow uploading';
$lang['HelpPopup_ProfileCan_Upload'] = 'Optionally allow users to upload files during the selection process';
$lang['HelpPopupTitle_ProfileDir'] = 'Top Directory';
$lang['HelpPopup_ProfileDir'] = 'Optionally enter the relative path of a directory (relative to the uploads path) to restrict operations to.';
$lang['HelpPopupTitle_ProfileShowthumbs'] = 'Show Thumbnails';
$lang['HelpPopup_ProfileShowthumbs'] = 'If enabled, thumbnails will be visible for image files for which thumbnails are generated.';

$lang['no_profiles'] = 'No profile is recorded. You can add one by clicking the icon above.';

$lang['th_created'] = 'Created';
$lang['th_default'] = 'Default';
$lang['th_id'] = 'ID';
$lang['th_last_edited'] = 'Last Edited';
$lang['th_name'] = 'Name';
$lang['th_reltop'] = 'Top Directory';
$lang['title_mkdir'] = 'Create Directory';
$lang['topdir'] = 'Top directory';
$lang['type'] = 'Type';

// HELP TEXT
$lang['help'] = <<<EOT
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
<p>Copyright &copy; 2017-2018, JoMorg and calguy1000, &copy; 2019-2020, CMSMS Foundation. All rights reserved.</p>
<p>This module has been released under the <a href="http://www.gnu.org/licenses/licenses.html#GPL">GNU Public License</a>. The module must not be used otherwise than in accordance with that license, or a later version of that licence granted by the module distributor.</p>
EOT;
