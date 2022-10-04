<?php
$lang = [
// A
'add_set' => 'Add a new controls-set',
'all_groups' => 'All Groups',
'all_users' => 'All Users',
'ascorder' => 'Ascending order',

// C
'can_delete' => 'Allow Deletions',
'can_mkdir' => 'Allow New SubDirectories',
'can_mkfile' => 'Allow New Files',
'can_upload' => 'Allow Uploaded Files',
'confirm_delete' => 'Are you sure about deleting this set?',
'created' => 'Created',

// D
'default' => 'Default',
'delete_set' => 'Delete Controls-Set',
'descorder' => 'Descending order',
'dialog_title' => 'Senior Folder Selector',

// E
'edit_set' => 'Edit Controls-Set',
'exclude_groups' => 'Prohibited User-Groups',
'exclude_patterns' => 'Prohibited Item-name Pattern(s)',
'exclude_users' => 'Prohibited Individual Users',

// F
'file_types' => 'Allowed File-types',

// H
'pagetitle_add_set' => 'New Controls-Set',
'pagetitle_edit_set' => 'Edit Controls-Set',
'pagetitle_see_set' => 'Review Controls-Set',

// I
'info_selector' => 'Click a name to toggle display of that folder\'s contents, if any.<br>Double-click a name to select that folder.',

//M
'match_groups' => 'Allowed User-Groups',
'match_patterns' => 'Allowed Item-name Pattern(s)',
'match_users' => 'Allowed Individual Users',
'modified' => 'Latest Modification', // page label
'modified2' => 'Last Modified', // table-header label

// N
'new_default' => 'Remember to nominate a replacement default controls-set',
'no_set' => 'No controls-set is recorded.',
'no_set_add' => 'No controls-set is recorded. You can add one by clicking the link above.',
'nogroup' => 'No eligible users-group is recorded',
'nouser' => 'No eligible user is recorded',
'nousername' => 'Anonymous user %d',

// R
'reltop' => '"Senior" Directory', // page label
'reltop2' => 'Senior Directory', // table-header label
'root_name' => 'Website',

// S
'select' => 'Select',
'set_id' => 'ID',
'show_hidden' => 'Show Hidden Items',
'show_thumbs' => 'Show Thumbnails',
'size' => 'Size',
'sort_field' => 'Default Sort-field',

// T
'title_select' => 'Activate this to display a folder-selection dialog',

// add|edit operation popup-help strings
'help_can_delete' => 'If enabled, users will be permitted to delete files and subdirectoris in the directory.',
'help_can_mkdir' => 'If enabled, users will be permitted to create new directories (below the specified top directory).',
'help_can_mkfile' => 'If enabled, users will be permitted to create new files',
'help_can_upload' => 'If enabled, users will be permitted to upload files to the folder',
'help_exclude_groups' => 'Operations may NOT be performed by members of the specified groups',
'help_exclude_patterns' => 'Regular expression(s) representing barred/prohibited item-names. Specify one such, or a \';\'-separated series of them. (\';\' has no special role in regular expressions.) As as example, for extensions a pattern would be like \.ext$',
'help_exclude_users' => 'Operations may NOT be performed by the specified users, regardless of group permissions',
'help_file_types' => 'Created or uploaded files must be one of the specified type(s)',
'help_match_groups' => 'Operations may NOT be performed by members of groups other than the ones specified',
'help_match_patterns' => 'Regular expression(s) representing allowed/valid item-names. Specify one such, or a \';\'-separated series of them. (\';\' has no special role in regular expressions.) As as example, for extensions a pattern would be like \.ext$',
'help_match_users' => 'Operations may NOT be performed by users OTHER THAN than the ones specified, regardless of group permissions',
'help_set_name' => 'Each controls-set should have a simple unique name, which should contain only alphanumeric characters and underscores.',
'help_set_reltop' => 'Enter the website-root-folder-relative filepath of the topmost directory to which this set of properties will apply. In virtually all cases, this should be, or start with, the name of the site uploads folder. It is vanishingly rare for a console user to be permitted to work in places other than the uploads folder or its descendants. A leading path-separator is not needed.',
'help_show_hidden' => 'If enabled, hidden files and subdirectories will be displayed',
'help_show_thumbs' => 'If enabled, thumbnails will be displayed for image files for which thumbnails are generated.',
'help_sort_field' => 'When displaying folder contents, the items there may be sorted by name, size, creation datetime or modification datetime',
] + $lang;
