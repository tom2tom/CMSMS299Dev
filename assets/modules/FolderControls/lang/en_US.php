<?php
// A
$lang['access'] = 'Access';
$lang['all_groups'] = 'All Groups';
$lang['all_users'] = 'All Users';
$lang['ascorder'] = 'Ascending order';

$lang['can_delete'] = 'Allow deleting files and directories';
$lang['can_mkdir'] = 'Allow directory creation';
$lang['can_mkfile'] = 'Allow file creation';
$lang['cancel'] = 'Cancel';
$lang['controlsupdated'] = 'Usage controls have been updated';
$lang['created'] = 'Created';

$lang['desc_default'] = 'Make this the default';

$lang['edit'] = 'Edit';
$lang['err_topdir'] = 'The specified top directory does not exist';
$lang['exclude_groups'] = 'Prohibited user-groups';
$lang['exclude_patterns'] = 'Prohibited item-name pattern(s)';
$lang['exclude_users'] = 'Prohibited users';

$lang['files_type'] = 'Allowed file types';
$lang['friendlyname'] = 'Extended Permissions'; // 'Usage Controls';

$lang['help_can_delete'] = 'Allow deleting files and sub-directories';
$lang['help_can_mkdir'] = 'Allow creation and upload of new sub-directories';
$lang['help_can_mkfile'] = 'Allow creation and upload of new files';
$lang['help_exclude_groups'] = 'Operations may NOT be performed by members of the specified groups';
$lang['help_exclude_patterns'] = 'Regular expression(s) representing barred/prohibited item-names. Specify one such, or a comma-separated series of them. As as example, for extensions a pattern would be like \.ext$';
$lang['help_exclude_users'] = 'Operations may NOT be performed by the specified users';
$lang['help_files_type'] = 'Files created or uploaded must be one of the specified type(s)';
$lang['help_match_groups'] = 'Operations may NOT be performed by members of groups other than the ones specified';
$lang['help_match_patterns'] = 'Regular expression(s) representing allowed/valid item-names. Specify one such, or a comma-separated series of them. As as example, for extensions a pattern would be like \.ext$';
$lang['help_match_users'] = 'Operations may NOT be performed by users other than the ones specified';
$lang['help_setname'] = 'Each permission-set must have a unique name. Best if it is meaningful to users and contains only alphanumeric characters and underscores.';
$lang['help_show_hidden'] = 'If enabled, any hidden files and directories will also be displayed.';
$lang['help_show_thumbs'] = 'If enabled, thumbnails (if they exist) will be displayed instead of corresponding image files.';
$lang['help_sort_field'] = 'When displaying folder contents, the items may be sorted by name, size, creation datetime or modification datetime';
$lang['help_topdir'] = 'Enter the website-root-relative filepath of the directory to which this set of properties will apply. It need not be unique. All descendant directories will also inherit the properties, except where another set of properties prevails.';

$lang['info_cset'] = 'Each permission-set is a collection of permissions and properties which apply to a specified directory and (except where countervailed by another such set) all of the directory\'s descendants.
<br /><br /><strong>NOTE</strong> some independently-developed modules might bypass the permission-set apparatus.';

$lang['match_groups'] = 'Allowed user-groups';
$lang['match_patterns'] = 'Allowed item-name pattern(s)';
$lang['match_users'] = 'Allowed users';
$lang['moddescription'] = 'Manage folder-specific permissions and properties';
$lang['modified'] = 'Last modified'; //c.f. 'text_modified' ?

$lang['name'] = 'Name';
$lang['no_cset'] = 'No permission-set is recorded.';
$lang['nogroup'] = 'No group is recorded';
$lang['nouser'] = 'No user is recorded';

$lang['postinstall'] = 'The Folder-Controls module has been unistalled';

$lang['really_uninstall'] = 'Are you sure you want to uninstall the Folder-Controls module?';

$lang['show_hidden'] = 'Show hidden files';
$lang['show_thumbs'] = 'Show image thumbnails';
$lang['size'] = 'Size';
$lang['sort_field'] = 'Default sort-field';
$lang['submit'] = 'Submit';

$lang['text_created'] = 'Created';
$lang['text_id'] = 'ID'; //or 'itemid'
$lang['text_modified'] = 'Modified';
$lang['text_reltop'] = 'Top Directory';
$lang['title_add_cset'] = 'New Permission-Set';
$lang['title_cset_name'] = 'Permission-Set Name';
$lang['title_delete_cset'] = 'Delete permission-set';
$lang['title_edit_cset'] = 'Edit Permission-Set';
$lang['topdir'] = 'Top directory';

$lang['uninstalled'] = 'The Folder-Controls module has been uninstalled';

$lang['help'] = <<<EOT
<h3>What does this do?</h3>
<p>This module enables additional permissions and properties for specified website folders.</p>
<h3>Support</h3>
<p>This module is provided as-is. Please read the text of the license for the full disclaimer.</p>
<p>For help:<ul>
<li>discussion may be found in the <a href="http://forum.cmsmadesimple.org">CMS Made Simple Forums</a>; or</li>
<li>you may have some success emailing the author directly.</li>
</ul></p>
<p>For the latest version of the module, or to report a bug, visit the module's <a href="http://dev.cmsmadesimple.org/projects/foldercontrols">forge-page</a>.</p>
<h3>Copyright and License</h3>
<p>Copyright &copy; 2018 CMS Made Simple Foundation &lt;foundation@cmsmadsimple.org&gt;. All rights reserved.</p>
<p>This module has been released under version 2 of the <a href="http://www.gnu.org/licenses/gpl.html">GNU General Public License</a>, and must not be used except in accordance with the terms of that license, or any later version of that license which is granted by the module's distributor.</p>
EOT;
