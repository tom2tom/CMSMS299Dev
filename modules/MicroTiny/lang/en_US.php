<?php
/*
UI strings for MicroTiny module
Copyright (C) 2009-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

// A
$lang['admindescription'] = 'TinyMCE tailored for CMSMS';

// B
$lang['browse'] = 'Browse';

// C
$lang['cancel'] = 'Cancel';
$lang['class'] = 'Class';
$lang['cmsms_linker'] = 'Site-page link';

// D
$lang['description'] = 'Description';
$lang['dimensions'] = 'WxH';
$lang['dimension'] = 'Dimension';
$lang['dirinfo'] = 'Change working directory to';

// E
$lang['edit_image'] = 'Edit Image';
$lang['edit_profile'] = 'Edit Profile';
$lang['editor_publicname'] = 'TinyMCE'; // for picklists etc
$lang['error_badparam'] = 'Invalid parameter';
$lang['error_cantchangesysprofilename'] = 'You cannot change the name of a system profile';
$lang['error_missingparam'] = 'A required parameter was missing';
$lang['error_nopage'] = 'No page alias selected';
$lang['example'] = 'Example';

// F
$lang['filepickertitle'] = 'File Picker'; //lazy, get this from FilePicker-module lang
$lang['friendlyname'] = 'Content Editor Settings'; //admin menu label
$lang['fileview'] = 'File View';
$lang['filename'] = 'File Name';
$lang['filterby'] = 'Filter by';

// H
$lang['height'] = 'Height';
$lang['help'] = <<<'EOS'
<h3>What does the MicroTiny module do?</h3>
<p>It provides a tailored version of the <a href="http://www.tinymce.com" target="_blank">TinyMCE</a> editor. It allows content editors a near-WYSIWYG appearance for editing content. It works with content blocks in CMSMS content pages (when a WYSIWYG has been allowed), in module Admin forms where WYSIWYG editors are allowed, and allows restricted capabilities for editing HTML blocks on frontend pages.</p>
</p>In order for MicroTiny to be used as the WYSIWYG editor in the admin console the MicroTiny WYSIWYG Editor needs to be selected in the user&apos's preferences. Please select &quot;MicroTiny&quot; in the &quot;Select WYSIWYG to Use&quot; option under &quot;My Preferences &gt;&gt; User Preferences&quot; in the CMSMS Admin panel.  Additional options in various modules or in content page templates, and content pages themselves can control whether a text area or a WYSIWYG field is provided in various edit forms.</p>
<p>For frontend editing capabilities, MicroTiny must be selected as the &quot;Frontend WYSIWYG&quot; in the global settings page of the CMSMS admin console.</p>
<h3>Features:</h3>
<ul>
  <li>Supports a subset of HTML5 block and inline elements.</li>
  <li>Separate profiles for admin editors and frontend editors.</li>
  <li>A custom file picker for selecting previously uploaded media.</li>
  <li>Custom plugin for creating links to CMSMS content pages <em>(Admin only)</em>.</li>
  <li>Customizable (somewhat) profiles for Admin behavior and frontend behavior.</li>
  <li>Customizable appearance by specifying a stylesheet to use for the editor.</li>
</ul>
<h3>How is it used</h3>
  <ul>
    <li>Install and configure the module</li>
    <li>Set MicroTiny as your WYSIWYG editor of choice in &quot;My Preferences&quot;</li>
  </ul>
<h3>About HTML, TinyMCE, and content editing:</h3>
  <ul>
    <li>WYSIWYG-like editor:
      <p>This editor provides the ability to edit content in an environment that is similar <em>(but not necessarily identical to)</em> to the intended output on the website frontend. Numerous factors can influence differences, including:</p>
      <ul>
        <li>Incomplete or incorrect stylesheets</li>
        <li>Use of advanced styling that the editor cannot understand</li>
        <li>Use of HTML elements that the WYSIWYG does not understand.</li>
      </ul>
    </li>

    <li>Subset of HTML elements:
      <p>As a simple content editor this editor does not support all of the HTML elements (particularly the new HTML5 block level elements. Any element that the editor does not understand or support will be stripped from the content upon save. As a general rule of thumb <em>(not including &lt;div&gt;)</em> you can assume that the editor supports only the elements that are directly available via the various menu and toolbar options.<p>
    </li>

    <li>Edit blocks of content, not the entire page:
      <p>As CMS Made Simple is a heavily templated environment using the Smarty template element, it is intended that the WYSIWYG editor is used only for specific blocks of content or data elements (i.e: the main content area of a page, or the description for a News or Blog article). This module <em>(and CMSMS)</em> do not support full-page editing.</p>
    </li>

    <li>Intended for simple content editing not design:
      <p>The intent and purpose of this module is to provide a WYSIWYG-like environment where editors can insert and edit content within specific blocks with limited formatting capabilities that will not interfere with, or override the styling of the page template. It is not intended for and will not be supported as a general HTML editor or layout manipulator.</p>
      <p>Website developers should understand the points above, assume that content editors can and WILL be editing within a WYSIWYG area and ensure that only simple content is there. If advanced layout techniques are needed for a specific area, then developers should modify the appropriate templates so that the restricted functionality editor will work properly.</p>
    </li>

    <li>Separation of Logic, Functionality and Design from Content.
      <p>This editor is built with the assumption that content for a specific area of a page (or a blog article, news article, or product description, ...) is data. The data are styled by the appropriate templates, and should not be mixed with design elements, or functionality of the website.</p>
      <p>As a simple example. If you are insisting that editors use certain classes for images, layout their images in a certain manner, or insert block elements such as &lt;div&gt; or &lt;section&gt; into their content for proper styling then this is not the editor module for you. Such styling concerns should be taken care of in stylesheets and templates, such that your editor can enter text without having to remember rules.</p>
      <p>This module is not designed to handle special cases where advanced HTML is required. In such pages the WYSIWYG editor should be disabled, and editing access to the page restricted to those with the ability to understand and edit HTML code manually.</p>
      <p>As this module is intended to provide a restricted editor for specific blocks, for use by editors without HTML knowledge. Since the WYSIWYG editor does not understand the Smarty logic, you should NOT (as a general rule) mix Smarty logic or module calls within WYSIWYG enabled areas. It is best to disable the WYSIWYG for these areas/pages and restrict edit access to those pages.</p>
    </li>
  </ul>
<h3>About Images and Media:</h3>
  <p>Each profile can enable or disable the ability of the editor to graphically insert image or media elements into the edited content. This is useful in highly structured environments where images and other media can be included in final output via other means. Particularly on frontend editing forms, where the identity of the user cannot necessarily be trusted, it is recommended that users not have the ability to insert images or other media.</p>
  <p><strong>Note:</strong> This module does not provide the ability to upload or otherwise manipulate files, images or media. That functionality is handled elsewhere in CMSMS.</p>

<h3>About Frontend Editing:</h3>
  <p>This module provides a unique &quot;profile&quot; for configuring the WYSIWYG editor on frontend requests. By default the frontend profile is highly limited.</p>
  <p>To enable frontend WYSIWYG editors, the <code>{cms_init_editor}</code> tag must be included in the head part of the template. Additionally, this module must be set as the &quot;Frontend WYSIWYG&quot; in the global settings page of the CMSMS admin console.</p>

<h3>About Styles and Colors:</h3>
  <p>This module provides the <em>(optional)</em> ability to associate a stylesheet with the profile. This provides the ability to style the edit portion WYSIWYG editor in a manner similar to the website style. Providing a more WYSIWYG like experience for the content editor.</p>
  <p>Additionally, in conjunction with the <code>classname</code> parameter of the <code>{cms_textarea}</code> and <code>{content}</code> tags this module allows the content editor module to override the specified stylesheet differently for each content block. This allows distinct styling of each WYSIWYG area, if there are multiple WYSIWYG areas on the page. This functionality is restricted to the Admin interface only.</p>
  <p>For example, in a page template adding the cssname parameter to the {content} tag allows specifying a CMSMS stylesheet to use to customize the appearance of that content block e.g: <code>{content block=&apos;second block&apos; cssname=&apos;whiteonblack&apos;}</code>
  <p>Additionally, a setting in the content editing section of the &quot;Global Settings&quot; page allows automatically supplying the css name parameter with the name of the content block.</p>

  <h4>Styles for the WYSIWYG editor</h4>
    <p>The stylesheet for the WYSIWYG editor area should style everything from the body element downwards. It is only necessary to style the elements available to, and used by the content editor. Here is a simple example of a stylesheet for a white-on-black theme:</p>
<pre><code>
body {
 background: black;
 color: white;
}
p {
 margin-bottom: 0.5em;
}
h1 {
 color: yellow;
 margin-bottom: 1em;
}
h2 {
 color: cyan;
 margin-bottom: 0.75em;
}
</code></pre>

<h3>FAQ:</h3>
  <dl>
    <dt>Q: Where is the support for <em style="color: red;">&quot;some functionality&quot;</em> in the editor, and how do I activate it?</dt>
      <dd>A: You don&apos;t. The version of TinyMCE distributed with MicroTiny is a trimmed down, custom package. It includes custom plugins, but doesn&apos;t support the addition of custom plugins or the ability to customize the configuration in any way other than the edit profile form. If you require additional functionality in a WYSIWYG editor you might find a suitable module in the CMSMS forge.</dd>
    <br/>
    <dt>Q: Which HTML/HTML5 tags are supported by this module, and how do I change that?</dt>
      <dd>A: The list of supported elements in the default TinyMCE editor can be found at <a href="https://www.tiny.cloud/docs-3x/reference/Configuration3x/Configuration3x@valid_elements/#defaultruleset" target="_blank">the TinyMCE website</a>. There is no mechanism in the MicroTiny module to extend that.</dd>
    <br/>
    <dt>Q: I cannot get the MicroTiny editor to work in the admin interface, what can I do?</dt>
      <dd>A: There are a few steps you can follow to diagnose this issue:
        <ol>
          <li>Check the CMSMS Admin log, your PHP error log, and the JavaScript console for indications of a problem.</li>
          <li>Ensure that the example WYSIWYG area works in the MicroTiny Admin panel under &quot;Extensions >> MicroTiny WYSIWYG Editor&quot;. If that does not work, recheck the site PHP error log and JavaScript console.</li>
          <li>Ensure that MicroTiny is selected as the &quot;WYSIWYG to use&quot; in your user preferences.</li>
          <li>Check other content pages. If MicroTiny works on one or more of those then that indicates that a flag to disable WYSIWYG editors on all content blocks might be set on some content pages.</li>
          <li>Check the page template(s). The wysiwyg=false parameter may be specified on one or more content blocks in the page template(s) which will disable the WYSIWYG editor.</li>
        </ol>
      </dd>
    <dt>Q: How do I insert a &lt;br/&gt; instead of create new paragraphs?</dt>
      <dd>A: Press [shift]+Enter instead of just the Enter key.</dd>
    <br />
    <dt>Q: Why is <em style="color: red;">&quot;some functionality&quot;</em> available in the menubar, and not the toolbar?</dt>
      <dd>A: For this most part this is done intentionally to allow web developers the ability to further restrict the functionality of certain editor profiles. The menubar can be toggled off in different profiles thus denying the user the functionality only available in the menubar.</dd>
  </dl>
<h3>Caching:</h3>
  <p>In an effort to improve performance, MicroTiny will attempt to cache the generated javaScript files unless something has changed. This functionality can be disabled by setting the special config entry <code>mt_disable_cache</code> to true. i.e: adding <code>\$config[&quot;mt_disable_cache&quot;] = true;</code> to the config.php file.</p>
<h3>See Also:</h3>
<ul>
  <li><code>{content}</code> tag in &quot;Extensions >> Tags&quot;</li>
  <li><code>{cms_textarea}</code> tag in &quot;Extensions >> Tags&quot;</li>
  <li><code>{cms_init_editor}</code> tag in &quot;Extensions >> Tags&quot;</li>
  <li> <a href="https://www.tinymce.com" target="_blank">TinyMCE</a></li>
</ul>
EOS;
$lang['help_sourcesri'] = <<<'EOS'
If using a non-local source, enter here a subresource integrity value, like 'hashtype-base64string', corresponding to the main script file i.e. &lt;selected source url&gt;/tinymce.min.js. The hash value would normally be availble from the script source or otherwise from various online calculators e.g. https://www.srihash.org.
<br /><br />
Refer to https://developer.mozilla.org/en-US/docs/Web/Security/Subresource_Integrity
<br /><br />
Leave the value empty for a locally-sourced script.
EOS;
$lang['help_sourceurl'] = <<<'EOS'
Enter here the topmost URL which specifies which, and from where, text-editor source files will be retrieved at runtime in preparation for using the editor.
<br /><br />
The editor may be installed on and run from this website, or run from CDN. The last part of the URL will often be a version-number. To use onsite sources, they must be manually installed in the place represented by the specified URL.
<br /><br />
CDN example: https://somecdnsite.com/tinymce/4.5.6
<br /><br />
NOTE: CMSMS-specific editor-plugins are currently coded for TinyMCE version 4, <strong>not 5</strong>.
<br /><br />
One good CDN source, perhaps the best, is cdnjs. (browse for 'tinymce', omit the trailing '/tinymce[.min].js'). TinyMCE operates its own cloud service, use of which requires registration and key-retrieval.
EOS;
$lang['help_skin'] = <<<'EOS'
Enter here the absolute or relative URL which specifies which, and from where, non-default-skin/css files will be retrieved at runtime in preparation for using the editor.
<br /><br />
The skin may be available from the same source as the editor, or somewhere else. The URL will often be the main sources-URL with '/skins/somename' appended. A value like '/skins/somename' or '/somename' will be treated as relative to the main sources-URL. If empty, the default skin will be used. To use onsite styles, they must be manually installed in the place represented by the specified URL.
<br /><br />
CDN example: https://somecdnsite.com/tinymce/4.5.6/skins/skin-name<br />
OR /skins/skin-name<br />
OR /skin-name<br />
OR empty
<br /><br />
One good CDN source, perhaps the best, is cdnjs. (browse for 'tinymce')
EOS;

// I
$lang['image'] = 'Image';
$lang['info_linker_autocomplete'] = 'This is an auto complete field. Begin by typing a few characters of the desired page alias, menu text, or title. Any matching items will be displayed in a list.';
$lang['info_source'] = <<<'EOS'
The settings here are only for configuring TinyMCE, the editor provided by the MicroTiny module.
<br /><br />
Other similar editors might also be available, if suitable module(s) are installed. If so, such editors' configuration would be handled in the respective modules.
<br /><br />
The editor which is actually used is determined by a selection on the System Settings page, perhaps overridden by individual users' choice on their (personal) Settings page.
EOS;

// L
$lang['label_skin'] = 'Skin URL';
$lang['label_sourcesri'] = 'Main-Script Integrity Hash';
$lang['label_sourceurl'] = 'Scripts URL';
$lang['loading_info'] = 'Loading...';

// M
$lang['mailto_image'] = 'Create a mail image'; // WHAT ??
$lang['mailto_text'] = 'Email link';
$lang['mailto_title'] = 'Create an email link';
$lang['microtiny_friendlyname'] = 'MicroTiny';

$lang['msg_cancelled'] = 'Operation canceled';
$lang['mthelp_allowcssoverride'] = 'If enabled, then any code that initializes a MicroTiny WYSIWYG area will be able to specify the name of a stylesheet to use instead of the default stylesheet specified above.';
$lang['mthelp_dfltstylesheet'] = 'Associate a stylesheet with MicroTiny. This allows the editor to appear similar to the website appearance.';
$lang['mthelp_profileallowimages'] = 'Allow embedding images and videos into the text area. For very tightly controlled designs the editor might only be able to select images, or videos for specific areas of a web page.';
$lang['mthelp_profileallowtables'] = 'Allow embedding and manipulating tables for tabular data. Note: this should not be used for controlling page layout, but only for tabular data.';
$lang['mthelp_profilelabel'] = 'A description for this profile. The description cannot be edited for system profiles.';
$lang['mthelp_profilename'] = 'The name for this profile. The name of system profiles cannot be edited.';
$lang['mthelp_profilemenubar'] = 'Indicates if the menubar should be enabled in the viewable profiles. The menubar typically has more options than the toolbar';
$lang['mthelp_profilestatusbar'] = 'This flag indicates if the statusbar at the bottom of the WYSIWYG area should be enabled. The status bar displays some useful scope information for advanced editors, and other useful information';
$lang['mthelp_profileresize'] = 'This flag indicates if the WYSIWYG area can be resized. In order for resize abilities to work the statusbar must be enabled';

// N
$lang['newwindow'] = 'New window';
$lang['none'] = 'None';

// O
$lang['ok'] = 'Ok';

// P
$lang['postinstall_notice'] = 'Before editing page content, appropriate URLs must be recorded in the MicroTiny module settings.';
$lang['postinstall_title'] = 'TinyMCE Sources Needed';
$lang['profile_admin'] = 'Admin Editing';
$lang['profile_allowcssoverride'] = 'Allow Stylesheet Override';
$lang['profile_allowimages'] = 'Allow Images';
$lang['profile_allowresize'] = 'Allow Resize';
$lang['profile_allowtables'] = 'Allow Tables';
$lang['profile_dfltstylesheet'] = 'Stylesheet for Editor';
$lang['profile_frontend'] = 'Frontend Editing';
$lang['profile_label'] = 'Label';
$lang['profile_menubar'] = 'Show Menubar';
$lang['profile_name'] = 'Profile Name';
$lang['profile_showstatusbar'] = 'Show Statusbar';
$lang['profiledesc___admin__'] = 'This profile is used by all users who are authorized to use the MicroTiny editor, and have chosen it as their WYSIWYG editor';
$lang['profiledesc___frontend__'] = 'This profile is used for all frontend requests where the MicroTiny editor is allowed';
$lang['prompt_anchortext'] = 'Anchor Text';
$lang['prompt_class'] = 'Class Attribute';
$lang['prompt_email'] = 'Email Address';
$lang['prompt_insertmailto'] = 'Email link';
$lang['prompt_linker'] = 'Enter Page title';
$lang['prompt_linktext'] = 'Link Text';
$lang['prompt_name'] = 'Name';
$lang['prompt_profiles'] = 'Capabilities';
$lang['prompt_rel'] = 'Rel Attribute';
$lang['prompt_selectedalias'] = 'Selected Page Alias';
$lang['prompt_source'] = 'Source Parameters';
$lang['prompt_target'] = 'Target';
$lang['prompt_texttodisplay'] = 'Text to Display';

// S
$lang['settings_title'] = 'MicroTiny Editor Settings'; // central settings title
$lang['settings'] = 'Settings'; // tab title
$lang['size'] = 'Size';
$lang['submit'] = 'Submit';

// T
$lang['tooltip_selectedalias'] = 'This field is read only';
$lang['title_cmsms_linker'] = 'Link to a page on this site';
$lang['title_cmsms_filebrowser'] = 'Select a file';
$lang['title_edit_profile'] = 'Edit profile';
$lang['tmpnotwritable'] = 'The configuration could not be written to the tmp directory! Please fix this...';
$lang['tab_general_title'] = 'General';
$lang['tab_advanced_title'] = 'Advanced';
$lang['type'] = 'Type';

// U
$lang['usestaticconfig_help'] = 'This generates a static configuration file instead of the dynamic one. Works better on some servers (for instance when running PHP as CGI)';
$lang['usestaticconfig_text'] = 'Use static config';

// W
$lang['width'] = 'Width';

// V
$lang['view_source'] = 'View Source';

// Y
$lang['youareintext'] = 'Current Directory';
