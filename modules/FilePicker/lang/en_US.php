<?php

$lang = [

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
'delete' => 'Delete',
'deleteit' => 'Delete %s', //tooltip
'dependencies' => 'Dependencies',
'dimension' => 'Dimensions',
'directory_named' => 'Directory %s', //tooltip c.f. changedir
'displayit' => 'Display %s', //tooltip
'displayup' => 'Display parent directory', //tooltip
'droplong' => 'You may drop here file(s) dragged from your file manager, to automatically begin uploading', //tooltip
'dropshort' => 'Drop Zone',

// E
'error_failed_ajax' => 'A problem occurred with an ajax request',
'error_problem_upload' => 'A problem occurred uploading',
'error_title' => 'Reported error(s)',
'error_upload_acceptFileTypes' => 'Files of this type are not acceptable in this scope.',
'error_upload_ext' => '%s extension is not acceptable',
'error_upload_maxFileSize' => 'The file is too large',
'error_upload_maxNumberOfFiles' => 'You are uploading too many files at once',
'error_upload_maxTotalSize' => 'The total size of files to upload exceeds the limit specified in the PHP configuration',
'error_upload_minFileSize' => 'The file is too small',
'error_upload_size' =>'%s is too large',
'error_upload_type' => '%s type is not acceptable',

// F
'filename' => 'Filename',
'filepickertitle' => 'File Picker',
'fileview' => 'File view',
'filterby' => 'Filters',
'friendlyname' => 'File Picker',

//M
'mkdir' => 'Create New Directory',
'moddescription' => 'File selection and upload capabilities for the website',

// N
'name' => 'Name',
'no' => 'No',

// O
'ok' => 'Ok',

// P
'perm_r' => 'read',
'perm_w' => 'write',
'perm_x' => 'exec',
'perm_xf' => 'enter',

// S
'select_a_document' => 'Select a Document',
'select_a_file' => 'Select a File',
'select_a_media_file' => 'Select a Media File',
'select_a_video_file' => 'Select a Video File',
'select_an_archive_file' => 'Select an Archive File',
'select_an_audio_file' => 'Select an Audio File',
'select_an_image' => 'Select an Image',
'select_file' => 'Select File', //popup dialog title
'select_upload_files' => 'Select file(s) to upload to here', //tooltip
'size' => 'Size',
'sizecodes' => 'Bytes,kB,MB', //comma-separated 'titles' for filesizes: bytes, kilobytes, megabytes
'switcharchive' => 'Only show archive files',
'switchaudio' => 'Only show audio files',
'switchfiles' => 'Only show regular files',
'switchgrid' => 'Display files in a grid',
'switchimage' => 'Only show image files',
'switchlist' => 'Display files as a list',
'switchreset' => 'Show all files',
'switchvideo' => 'Only show video files',

// U
'upload' => 'Upload',

// Y
'yes' => 'Yes',
'youareintext' => 'The current working directory (relative to the top of the installation)',

'help' => <<<'EOT'
<h3>What does the FilePicker module do?</h3>
<p>It provides infrastructure for use by other parts of CMSMS. Specifically, it supports selection and uploading of files.</p>
<h3>How is it used?</h3>
<p>This module can be used by other modules via various API's, or via the {cms_filepicker} plugin.</p>
<p>Additionally, this module can be called directly via the <code>{cms_module module=FilePicker action=select name=string [profile=string] [type=string] [value=string]}</code> tag, but this is not recommended. Refer to the {cms_filepicker} plugin for information about the parameters.</p>
<h3>Support</h3>
<p>As per the license, this software is provided as-is. Please read the text of the license for the full disclaimer.</p>
<h3>Copyright and License</h3>
<p>Copyright &copy; 2017-2018, Fernando Morgado and Robert Campbell, &copy; 2019-2021, CMS Made Simple Foundation. All rights reserved.</p>
<p>This module has been released under the <a href="http://www.gnu.org/licenses/licenses.html#GPL">GNU Public License</a>. The module must not be used otherwise than in accordance with that license, or a later version of that licence granted by the module distributor.</p>
EOT
,

] + $lang;
