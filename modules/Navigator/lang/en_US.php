<?php
//D
$lang['description'] = 'This module provides a simple and easy way to generate the HTML needed for a website navigation directly, and dynamically from the CMSMS page structure.  It provides flexible filtering, and templating capabilities to build powerful, fast, and appealing website navigations with no interaction from the content editor.';

//F
$lang['friendlyname'] = 'Sitepages Navigation Builder';

//H
//  <li>\$node->tabindex -- Tab index, if defined.</li> irrlevant in nodes
$lang['help'] = <<<EOT
<h3>What does this do?</h3>
  <p>The &quot;Navigator&quot; module is an engine for generating navigations from the CMSMS content tree and a Smarty template.  This module provides flexible filtering capabilities to allow building numerous navigations based on different criteria, and a simple-to-use data format for generating navigations with complete flexibility.</p>
  <p>This module has no admin interface of its own, instead Navigator templates are managed like all other templates via the admin-console template-editing page.</p>
<h3>How is it used?</h3>
<p>The simplest way to use this module is to insert the <code>{Navigator}</code> tag into a template. The module accepts numerous parameters to alter its behavior and filter the data.</p>
<h3>Why do I care about templates?</h3>
<p>This is the power of CMSMS. Navigations can be built automatically using the data from the site's content hierarchy, and a Smarty template. There is no need to edit a navigation object each time a content page is added or removed from the system, or re-ordered. Additionally, navigation templates can easily include javascript or advanced functionality and can be shared between websites.</p>
<p>This module is distributed with a few sample templates. Those may be used as-is, and also serve as models for producing custom navigation templates to your liking. Navigation styling is accomplished by stylesheets. Such stylesheets are not included with the Navigator module.</p>
<h3>The node object:</h3>
<p>Each navigation template is given an array of node objects that match the criteria specified in the Smarty tag which initiated the navigation. The properties of those objects are:</p>
<ul>
  <li>\$node->accesskey -- Page access key (if any)</li>
  <li>\$node->alias -- Page alias</li>
  <li>\$node->children -- An array of node ids representing the displayable children of this node. Empty if the node does not have children to display.</li>
  <li>\$node->children_exist -- TRUE if this node has any children that could be displayed but are not being displayed due to other filtering parameters (number of levels, etc).</li>
  <li>\$node->created -- Page creation date</li>
  <li>\$node->current -- TRUE if this node is the currently selected page</li>
  <li>\$node->default -- TRUE if this node refers to the default content object.</li>
  <li>\$node->extra1 -- The extra1 page property (if any)</li>
  <li>\$node->extra2 -- The extra2 page property (if any)</li>
  <li>\$node->extra3 -- The extra3 page property (if any)</li>
  <li>\$node->has_children -- TRUE if this node has any children at all</li>
  <li>\$node->hierarchy -- Plain-format tree hierarchy position of the page (e.g. 4.8.2)</li>
  <li>\$node->id -- The page numeric ID</li>
  <li>\$node->image -- The page image property (if any)</li>
  <li>\$node->menutext -- Menu text</li>
  <li>\$node->modified -- Page modified date</li>
  <li>\$node->parent -- TRUE if this node is an ancestor of the currently selected page</li>
  <li>\$node->raw_menutext -- Menu text without having html entities converted</li>
  <li>\$node->target -- The target-property (if any) for the url/link</li>
  <li>\$node->thumbnail -- The page thumbnail property (if any)</li>
  <li>\$node->title -- The page title (if any)</li>
  <li>\$node->titleattribute -- Description, or Title attribute (title) (if any)</li>
  <li>\$node->url -- The URL to open/show the page. This should be used when building links.</li>
</ul>
<h3>Examples:</h3>
<ul>
   <li>A simple navigation that is only 2 levels deep, using the default template:<br>
     <pre><code>{Navigator number_of_levels=2}</code></pre>
   </li>
     <li>Display a simple navigation two levels deep starting with the children of the current page.  Use the default template:</li>
     <pre><code>{Navigator number_of_levels=2 start_page=\$page_alias}</code></pre>
   </li>
   <li>Display a simple navigation two levels deep starting with the children of the current page.  Use the default template:</li>
     <pre><code>{Navigator number_of_levels=2 childrenof=\$page_alias}</code></pre>
   </li>
   <li>Display a navigation two levels deep starting with the current page, its peers, and everything below them.  Use the default template:</li>
     <pre><code>{Navigator number_of_levels=2 start_page=\$page_alias show_root_siblings=1}</code></pre>
   </li>
   <li>Display a navigation of the specified menu items and their children.  Use the template named mymenu</li>
     <pre><code>{Navigator items='alias1,alias2,alias3' number_of_levels=3 template=mymenu}</code></pre>
   </li>
</ul>
EOT;
$lang['help_action'] = 'Specify the action of the module.  This module supports two actions:
<ul>
  <li><em>default</em> - Used to build a primary navigation. (this action is implied if no action name is specified).</li>
  <li>breadcrumbs - Used to build a mini navigation consisting of the path from the root of the site down to the current page.</li>
</ul>';
$lang['help_collapse'] = 'When enabled, only items directly related to the current active page will be output';
$lang['help_childrenof'] = 'This option will display only items that are descendants of the selected page id or alias.  i.e: <code>{Navigator childrenof=$page_alias}</code> will only display the children of the current page.';
$lang['help_excludeprefix'] = 'Exclude all items (and their children) who\'s page alias matches one of the specified (comma separated) prefixes.  This parameter must not be used in conjunction with the includeprefix parameter.';
$lang['help_idnodes'] = 'Generate data for template processing based on object numeric-identifiers, instead of actual objects (the latter approach is deprecated since Navigator version 2.0)';
$lang['help_includeprefix'] = 'Include only those items who\'s page alias matches one of the specified (comma separated) prefixes.  This parameter cannot be combined with the excludeprefix parameter.';
$lang['help_items'] = 'Specify a comma separated list of page aliases that this navigation should display.';
$lang['help_loadprops'] = 'Deprecated since Navigator version 2.0. Does nothing. The former \'extended\' navigation parameters (such as target, extra1, image, thumbnail, etc) are always available for use in navigation templates.';
$lang['help_nlevels'] = 'Alias for number_of_levels';
$lang['help_number_of_levels'] = 'This setting will limit the depth of the generated navigation to the specified number of levels.  By default the value for this parameter is implied to be unlimited, except when using the items parameter, in which case the number_of_levels parameter is implied to be 1';
$lang['help_root2'] = 'Used only in the &quot;breadcrumbs&quot; action this parameter indicates that the breadcrumbs should go no further up the page tree than the specified page alias or numeric id. Or specifying a negative integer value will only display the breadcrumbs up to the top level and will ignore the default page.';
$lang['help_show_all'] = 'This option will cause the menu to show all nodes even if they are flagged to be omitted from the menu. It will still not display inactive pages however.';
$lang['help_show_root_siblings'] = 'This option only becomes useful if start_element or start_page are used.  It basically will display the siblings along side of the selected start_page/element.';
$lang['help_start_element'] = 'Starts the menu displaying at the given start_element and showing that element and its children only.  Takes a hierarchy position (e.g. 5.1.2).';
$lang['help_start_level'] = 'This option will have the menu only display items starting at the given level relative to the current page.  An easy example would be if you had one menu on the page with number_of_levels=1
.  Then as a second menu, you have start_level=2.  Now, your second menu will show items based on what is selected in the first menu.  The minimum value for this parameter is 2';
$lang['help_start_page'] = 'Starts the menu displaying at the given start_page and showing that element and its children only.  Takes a page alias.';
$lang['help_template'] = 'The template to use for displaying the menu.  The named template must exist in the system or else an error will be displayed.  If this parameter is not specified the default template of type Navigator::Navigation will be used';
$lang['help_start_text'] = 'Useful only in the breadcrumbs action, this parameter allows specifying some optional text to display at the beginning of the breadcrumb navigation.  An example would be &quot;You Are Here&quot;';

//T
$lang['type_breadcrumbs'] = 'Breadcrumbs';
$lang['type_Navigator'] = 'Navigator';
$lang['type_navigation'] = 'Navigation';

//Y
$lang['youarehere'] = 'You are here';
