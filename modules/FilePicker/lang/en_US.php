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
'error_ajax_invalidfilename' => 'Filename is invalid',
'error_ajax_fileexists' => 'A file or directory with that name already exists',
'error_ajax_mkdir' => 'A problem occurred creating the directory %s',
'error_ajax_writepermission' => 'You do not have permission to write to this directory',
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

//H
'help_action' => 'The filepicker-module action to use, filepicker or select',
'help_content' => 'Whether to return the constructed selector as a JSON string',
'help_name' => 'Name-attribute of the displayed selector element',
'help_profile' => 'Name of a recorded controls-set, hence settings which influence what may be uploaded and/or selected',
'help_type' => 'Wanted file-type identifier e.g. any or ANY',
'help_value' => 'Initial value of the selector element',

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
<h3>What does the File Picker module do?</h3>
<p>It provides infrastructure for use by other parts of CMSMS. Specifically, it supports selection and uploading of files.</p>
<h3>How is it used?</h3>
<p>This module can be used by other modules via various API's, or via plugin(s) in any page or template:<br>
<pre><code>{cms_module module=FilePicker action=select name='string' optional other params <em>see below</em>...}</code></pre><br>
or<br>
<pre><code>{content_module module=FilePicker block='string' [profile='string' <em>see below</em>]}</code></pre><br>
or (when this module is the system-default for selecting files)<br>
<pre><code>{cms_filepicker name='string' optional other params...}</code></pre><br>
Refer also to the {content_module} plugin help, for more information about using that tag.<br>
Refer to the {cms_filepicker} plugin help, for information about using that tag.</p>
<h3>Support</h3>
<p>This software is provided as-is. Please read the text of the license for the full disclaimer.</p>
<p>Support might be available through the CMSMS Forum:</p>
<ul>
 <li>first, <a href="https://forum.cmsmadesimple.org" target="_blank">search the forum</a> for issues with the module similar to those you are finding.</li>
 <li>then, if necessary, <a href="https://forum.cmsmadesimple.org/posting.php?mode=post&f=7" target="_blank">open a new forum topic</a> to request help, with a thorough description of your issue, and steps to reproduce it.</li>
</ul>
If you find a bug you can <a href="http://dev.cmsmadesimple.org/bug/list/6" target="_blank">submit a bug report</a>.<br>
You can <a href="http://dev.cmsmadesimple.org/feature_request/list/6" target="_blank">submit a feature request</a> to suggest improvement.
<h3>Copyright and License</h3>
<p>Copyright &copy; 2017-2018, Fernando Morgado and Robert Campbell, &copy; 2019-2022, CMS Made Simple Foundation. All rights reserved.</p>
<p>This module has been released under version 3 of the <a href="https://www.gnu.org/licenses/licenses.html#GPL">General Public License</a>. The module may not be distributed or used otherwise than in accordance with that license, or a later version of that licence granted by the module's distributor.</p>
EOT
,

] + $lang;
