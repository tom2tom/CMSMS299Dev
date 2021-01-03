<?php

$lang = [

'desc_addglobalcontentpost' => 'Sent after a new global content block is created',
'desc_addglobalcontentpre' => 'Sent before a new global content block is created',
'desc_addgrouppost' => 'Sent after a new group is created',
'desc_addgrouppre' => 'Sent before a new group is created',
'desc_addstylesheetpost' => 'Sent after a new stylesheet is created',
'desc_addstylesheetpre' => 'Sent before a new stylesheet is created',
'desc_addtemplatepost' => 'Sent after a new template is created',
'desc_addtemplatepre' => 'Sent before a new template is created',
'desc_addtemplatetypepost' => 'Sent after a template type definition is saved to the database',
'desc_addtemplatetypepre' => 'Sent prior to a template type definition being saved to the database',
'desc_adduserpost' => 'Sent after a new user is created',
'desc_adduserpre' => 'Sent before a new user is created',
'desc_changegroupassignpost' => 'Sent after group assignments are saved',
'desc_changegroupassignpre' => 'Sent before group assignments are saved',
'desc_contentdeletepost' => 'Sent after content is deleted from the system',
'desc_contentdeletepre' => 'Sent before content is deleted from the system',
'desc_contenteditpost' => 'Sent after edits to content are saved',
'desc_contenteditpre' => 'Sent before edits to content are saved',
'desc_contentpostcompile' => 'Sent after content has been processed by Smarty',
'desc_contentpostrender' => 'Sent before the combined HTML is sent to the browser',
'desc_contentprecompile' => 'Sent before content is sent to Smarty for processing',
'desc_contentprerender' => 'Sent before any Smarty processing is performed.',
'desc_contentstylesheet' => 'Sent before the stylesheet is sent to the browser',
'desc_deleteglobalcontentpost' => 'Sent after a global content block is deleted from the system',
'desc_deleteglobalcontentpre' => 'Sent before a global content block is deleted from the system',
'desc_deletegrouppost' => 'Sent after a group is deleted from the system',
'desc_deletegrouppre' => 'Sent before a group is deleted from the system',
'desc_deletestylesheetpost' => 'Sent after a stylesheet is deleted from the system',
'desc_deletestylesheetpre' => 'Sent before a stylesheet is deleted from the system',
'desc_deletetemplatepost' => 'Sent after a template is deleted from the system',
'desc_deletetemplatepre' => 'Sent before a template is deleted from the system',
'desc_deletetemplatetypepost' => 'Sent after a template type definition is deleted',
'desc_deletetemplatetypepre' => 'Sent prior to a template type definition being deleted',
'desc_deleteuserpost' => 'Sent after a user is deleted from the system',
'desc_deleteuserpre' => 'Sent before a user is deleted from the system',
'desc_editglobalcontentpost' => 'Sent after edits to a global content block are saved',
'desc_editglobalcontentpre' => 'Sent before edits to a global content block are saved',
'desc_editgrouppost' => 'Sent after edits to a group are saved',
'desc_editgrouppre' => 'Sent before edits to a group are saved',
'desc_editstylesheetpost' => 'Sent after edits to a stylesheet are saved',
'desc_editstylesheetpre' => 'Sent before edits to a stylesheet are saved',
'desc_edittemplatepost' => 'Sent after edits to a template are saved',
'desc_edittemplatepre' => 'Sent before edits to a template are saved',
'desc_edittemplatetypepost' => 'Sent after a template type definition is saved',
'desc_edittemplatetypepre' => 'Sent before a template type definition is saved',
'desc_edituserpost' => 'Sent after edits to a user are saved',
'desc_edituserpre' => 'Sent before edits to a user are saved',
'desc_generic' => 'Sent %s',
'desc_globalcontentpostcompile' => 'Sent after a global content block has been processed by Smarty',
'desc_globalcontentprecompile' => 'Sent before a global content block is sent to Smarty for processing',
'desc_loginfailed' => 'Sent after a user failed to login into the Admin panel',
'desc_loginpost' => 'Sent after a user logs into the Admin panel',
'desc_logoutpost' => 'Sent after a user logs out of the Admin panel',
'desc_lostpassword' => 'Sent when the lost password form is submitted',
'desc_lostpasswordreset' => 'Sent when the lost password form is submitted',
'desc_metadatapostrender' => 'Sent from the metadata plugin after page metadata has been processed via Smarty',
'desc_metadataprerender' => 'Sent from the metadata plugin before any processing has occurred',
'desc_moduleinstalled' => 'Sent after a module is installed',
'desc_moduleuninstalled' => 'Sent after a module is uninstalled',
'desc_moduleupgraded' => 'Sent after a module is upgraded',
'desc_pagebodyprerender' => 'Sent before the page content after the head section (if any) is populated by Smarty',
'desc_pagebodypostrender' => 'Sent after the page content after the head section (if any) is populated by Smarty',
'desc_pageheadprerender' => 'Sent before the page head section is populated by Smarty',
'desc_pageheadpostrender' => 'Sent after the page head section is populated by Smarty',
'desc_pagetopprerender' => 'Sent before the page-top (from page start to &lt;head&gt;) is populated by Smarty',
'desc_pagetoppostrender' => 'Sent after the page-top (from page start to &lt;head&gt;) is populated by Smarty',
'desc_postrequest' => 'Sent at the end of processing each admin or frontend request',
'desc_smartypostcompile' => 'Sent after any content destined for Smarty has been processed',
'desc_smartyprecompile' => 'Sent before any content destined for Smarty is sent for processing',
'desc_stylesheetpostcompile' => 'Sent after a stylesheet is compiled through Smarty',
'desc_stylesheetpostrender' => 'Sent after a stylesheet is passed through Smarty, but before cached to disk',
'desc_stylesheetprecompile' => 'Sent before a stylesheet is compiled through Smarty',
'desc_templatepostcompile' => 'Sent after a template has been processed by Smarty',
'desc_templateprecompile' => 'Sent before a template is sent to Smarty for processing',
'desc_templateprefetch' => 'Sent before a template is fetched from Smarty',

'help_addglobalcontentpost' => "<h4>Parameters</h4>
<ul>
<li>'global_content' - Reference to the affected global content block object.</li>
</ul>
",
'help_addglobalcontentpre' => "<h4>Parameters</h4>
<ul>
<li>'global_content' - Reference to the affected global content block object.</li>
</ul>
",
'help_addgrouppost' => "<h4>Parameters</h4>
<ul>
<li>'group' - Reference to the affected group object.</li>
</ul>
",
'help_addgrouppre' => "<h4>Parameters</h4>
<ul>
<li>'group' - Reference to the affected group object.</li>
</ul>
",
'help_addstylesheetpost' => "<h4>Parameters</h4>
<ul>
<li>'stylesheet' - Reference to the affected stylesheet object.</li>
</ul>
",
'help_addstylesheetpre' => "<h4>Parameters</h4>
<ul>
<li>'stylesheet' - Reference to the affected stylesheet object.</li>
</ul>
",
'help_addtemplatepost' => "<h4>Parameters</h4>
<ul>
<li>'template' - Reference to the affected template object.</li>
</ul>
",
'help_addtemplatepre' => "<h4>Parameters</h4>
<ul>
<li>'template' - Reference to the affected template object.</li>
</ul>
",
'help_addtemplatetypepost' => "<h4>Parameters</h4>
<ul>
  <li>'TemplateType' - Reference to the affected template type object.</li>
</ul>",
'help_addtemplatetypepre' => "<h4>Parameters</h4>
<ul>
  <li>'TemplateType' - Reference to the affected template type object.</li>
</ul>",
/*
'help_adduserpluginpost' => '<h4>Parameters</h4>
<ul>
<li>None</li>
</ul>
',
'help_adduserpluginpre' => '<h4>Parameters</h4>
<ul>
<li>None</li>
</ul>
',
*/
'help_adduserpost' => "<h4>Parameters</h4>
<ul>
<li>'user' - Reference to the affected user object.</li>
</ul>
",
'help_adduserpre' => "<h4>Parameters</h4>
<ul>
<li>'user' - Reference to the affected user object.</li>
</ul>
",
'help_changegroupassignpost' => "<h4>Parameters></h4>
<ul>
<li>'group' - Reference to the affected group object.</li>
<li>'users' - Array of references to user objects now belonging to the affected group.</li>
</ul>
",
'help_changegroupassignpre' => "<h4>Parameters></h4>
<ul>
<li>'group' - Reference to the group object.</li>
<li>'users' - Array of references to user objects belonging to the group.</li>
</ul>
",
'help_contentdeletepost' => "<h4>Parameters</h4>
<ul>
<li>'content' - Reference to the affected content object.</li>
</ul>
",
'help_contentdeletepre' => "<h4>Parameters</h4>
<ul>
<li>'content' - Reference to the affected content object.</li>
</ul>
",
'help_contenteditpost' => "<h4>Parameters</h4>
<ul>
<li>'content' - Reference to the affected content object.</li>
</ul>
",
'help_contenteditpre' => "<h4>Parameters</h4>
<ul>
<li>'global_content' - Reference to the affected content object.</li>
</ul>
",
'help_contentpostcompile' => "<h4>Parameters</h4>
<ul>
<li>'content' - Reference to the affected content text.</li>
</ul>
",
'help_contentpostrender' => "<h4>Parameters</h4>
<ul>
<li>'content' - Reference to the html text.</li>
</ul>
",
'help_contentprecompile' => "<h4>Parameters</h4>
<ul>
<li>'content' - Reference to the affected content text.</li>
</ul>
",
'help_contentprerender' => "<h4>Parameters</h4>
<ul>
<li>'content' - Reference to the affected content object..</li>
</ul>
",
'help_contentstylesheet' => "<h4>Parameters</h4>
<ul>
<li>'content' - Reference to the affected stylesheet text.</li>
</ul>
",
'help_deleteglobalcontentpost' => "<h4>Parameters</h4>
<ul>
<li>'global_content' - Reference to the affected global content block object.</li>
</ul>
",
'help_deleteglobalcontentpre' => "<h4>Parameters</h4>
<ul>
<li>'global_content' - Reference to the affected global content block object.</li>
</ul>
",
'help_deletegrouppost' => "<h4>Parameters</h4>
<ul>
<li>'group' - Reference to the affected group object.</li>
</ul>
",
'help_deletegrouppre' => "<h4>Parameters</h4>
<ul>
<li>'group' - Reference to the affected group object.</li>
</ul>
",
'help_deletestylesheetpost' => "<h4>Parameters</h4>
<ul>
<li>'stylesheet' - Reference to the affected stylesheet object.</li>
</ul>
",
'help_deletestylesheetpre' => "<h4>Parameters</h4>
<ul>
<li>'stylesheet' - Reference to the affected stylesheet object.</li>
</ul>
",
'help_deletetemplatepost' => "<h4>Parameters</h4>
<ul>
<li>'template' - Reference to the affected template object.</li>
</ul>
",
'help_deletetemplatepre' => "<h4>Parameters</h4>
<ul>
<li>'template' - Reference to the affected template object.</li>
</ul>
",
'help_deletetemplatetypepost' => "<h4>Parameters</h4>
<ul>
  <li>'TemplateType' - Reference to the affected template type object.</li>
</ul>",
'help_deletetemplatetypepre' => "<h4>Parameters</h4>
<ul>
  <li>'TemplateType' - Reference to the affected template type object.</li>
</ul>",
/*
'help_deleteuserpluginpost' => '<h4>Parameters</h4>
<ul>
<li>None</li>
</ul>
',
'help_deleteuserpluginpre' => '<h4>Parameters</h4>
<ul>
<li>None</li>
</ul>
',
*/
'help_deleteuserpost' => "<h4>Parameters</h4>
<ul>
<li>'user' - Reference to the affected user object.</li>
</ul>
",
'help_deleteuserpre' => "<h4>Parameters</h4>
<ul>
<li>'user' - Reference to the affected user object.</li>
</ul>
",
'help_editglobalcontentpost' => "<h4>Parameters</h4>
<ul>
<li>'global_content' - Reference to the affected global content block object.</li>
</ul>
",
'help_editglobalcontentpre' => "<h4>Parameters</h4>
<ul>
<li>'global_content' - Reference to the affected global content block object.</li>
</ul>
",
'help_editgrouppost' => "<h4>Parameters</h4>
<ul>
<li>'group' - Reference to the affected group object.</li>
</ul>
",
'help_editgrouppre' => "<h4>Parameters</h4>
<ul>
<li>'group' - Reference to the affected group object.</li>
</ul>
",
'help_editstylesheetpost' => "<h4>Parameters</h4>
<ul>
<li>'stylesheet' - Reference to the affected stylesheet object.</li>
</ul>
",
'help_editstylesheetpre' => "<h4>Parameters</h4>
<ul>
<li>'stylesheet' - Reference to the affected stylesheet object.</li>
</ul>
",
'help_edittemplatepost' => "<h4>Parameters</h4>
<ul>
<li>'template' - Reference to the affected template object.</li>
</ul>
",
'help_edittemplatepre' => "<h4>Parameters</h4>
<ul>
<li>'template' - Reference to the affected template object.</li>
</ul>
",
'help_edittemplatetypepost' => "<h4>Parameters</h4>
<ul>
  <li>'TemplateType' - Reference to the affected template type object.</li>
</ul>",
'help_edittemplatetypepre' => "<h4>Parameters</h4>
<ul>
  <li>'TemplateType' - Reference to the affected template type object.</li>
</ul>",
/*
'help_edituserpluginpost' => '<h4>Parameters</h4>
<ul>
<li>None</li>
</ul>
',
'help_edituserpluginpre' => '<h4>Parameters</h4>
<ul>
<li>None</li>
</ul>
',
*/
'help_edituserpost' => "<h4>Parameters</h4>
<ul>
<li>'user' - Reference to the affected user object.</li>
</ul>
",
'help_edituserpre' => "<h4>Parameters</h4>
<ul>
<li>'user' - Reference to the affected user object.</li>
</ul>
",
'help_generic' => "<h4>Parameters</h4>
<ul>
%s
</ul>
",
'help_globalcontentpostcompile' => "<h4>Parameters</h4>
<ul>
<li>'global_content' - Reference to the affected global content block text.</li>
</ul>
",
'help_globalcontentprecompile' => "<h4>Parameters</h4>
<ul>
<li>'global_content' - Reference to the affected global content block text.</li>
</ul>
",
'help_loginfailed' => "<h4>Parameters</h4>
<ul>
  <li>'user' - (string) The username of the failed login attempt.</li>
</ul>",
'help_loginpost' => "<h4>Parameters</h4>
<ul>
<li>'user' - Reference to the affected user object.</li>
</ul>
",
'help_logoutpost' => "<h4>Parameters</h4>
<ul>
<li>'user' - Reference to the affected user object.</li>
</ul>
",
'help_lostpassword' => "<h4>Parameters</h4>
<ul>
<li>'username' - The username entered in the lostpassword form.</li>
</ul>
",
'help_lostpasswordreset' => "<h4>Parameters</h4>
<ul>
<li>'uid' - The integer userid for the account.</li>
<li>'username' - The username for the reset account.</li>
<li>'ip' - The IP address of the client that performed the reset.</li>
</ul>
",
'help_moduleinstalled' => '<h4>Parameters</h4>
<ul>
<li>None</li>
</ul>
',
'help_moduleuninstalled' => '<h4>Parameters</h4>
<ul>
<li>None</li>
</ul>
',
'help_moduleupgraded' => '<h4>Parameters</h4>
<ul>
<li>None</li>
</ul>
',
'help_smartypostcompile' => "<h4>Parameters</h4>
<ul>
<li>'content' - Reference to the affected text.</li>
</ul>
",
'help_smartyprecompile' => "<h4>Parameters</h4>
<ul>
<li>'content' - Reference to the affected text.</li>
</ul>
",
'help_stylesheetpostcompile' => '<h4>Parameters</h4>
<ul>
<li>None</li>
</ul>
',
'help_stylesheetpostrender' => "<h4>Parameters</h4>
<ul>
<li>'content' - Reference to the stylesheet text.</li>
</ul>
",
'help_stylesheetprecompile' => '<h4>Parameters</h4>
<ul>
<li>None</li>
</ul>
',
'help_templatepostcompile' => "<h4>Parameters</h4>
<ul>
<li>'template' - Reference to the affected template text.</li>
<li>'type' - The type of template call.  i.e: template for a whole template, tpl_head, tpl_body, or tpl_top for a partial template.</li>
</ul>
",
'help_templateprecompile' => "<h4>Parameters</h4>
<ul>
<li>'template' - Reference to the affected template text.</li>
<li>'type' - The type of template call.  i.e: template for a whole template, tpl_head, tpl_body, or tpl_top for a partial template.</li>
</ul>
",
'help_metadatapostrender' => "<h4>Parameters</h4>
<ul>
<li>'content_id' - Page numeric identifier.</li>
<li>'html' - Reference to processed metadata (string) which may be amended as appropriate.</li>
</ul>
",
'help_metadataprerender' => "<h4>Parameters</h4>
<ul>
<li>'content_id' - Page numeric identifier.</li>
<li>'showbase' - Reference to boolean variable: whether to show a base tag.</li>
<li>'html' - Reference to string which may be populated/amended with metadata as appropriate.</li>
</ul>
",
'help_pagebodyprerender' => "<h4>Parameters</h4>
<ul>
<li>'content' - The page content-object.</li>
<li>'html' - Reference to the (string) content (if any) of the page-section.</li>
</ul>
",
'help_pagebodypostrender' => "<h4>Parameters</h4>
<ul>
<li>'content' - The page content-object.</li>
<li>'html' - Reference to the (string) content (if any) of the page-section.</li></ul>
",
'help_pageheadprerender' => "<h4>Parameters</h4>
<ul>
<li>'content' - The page content-object.</li>
<li>'html' - Reference to the (string) content (if any) of the page-section.</li></ul>
",
'help_pageheadpostrender' => "<h4>Parameters</h4>
<ul>
<li>'content' - The page content-object.</li>
<li>'html' - Reference to the (string) content (if any) of the page-section.</li></ul>
",
'help_pagetopprerender' => "<h4>Parameters</h4>
<ul>
<li>'content' - The page content-object.</li>
<li>'html' - Reference to the (string) content (if any) of the page-section.</li></ul>
",
'help_pagetoppostrender' => "<h4>Parameters</h4>
<ul>
<li>'content' - The page content-object.</li>
<li>'html' - Reference to the (string) content (if any) of the page-section.</li></ul>
",
'help_postrequest' => '<h4>Parameters</h4>
<ul>
<li>None</li>
</ul>',
'help_templateprefetch' => '<h4>Parameters</h4>
<ul>
<li>None</li>
</ul>',

] + $lang;
