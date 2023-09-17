<?php

$lang = [

'about_generic' => 'Initial release %s<br>
Change History:<br>
<ul>
%s
</ul>',

'help_generic' => '<h3>What does this do?</h3>
<p>%s</p>
<h3>Example</h3>
<pre><code>{%s}</code></pre>
<h4>What parameters does it take?</h4>
<ul>
%s
</ul>
and/or any of Smarty\'s generic parameters (nocache, assign etc)',

'help_generic2' => '<h3>What does this do?</h3>
<p>%s</p>
<h3>Example</h3>
<pre><code>{%s}</code></pre>
<h4>What parameters does it take?</h4>
<ul>
%s
</ul>',

// specific-tag strings
//TODO e.g.$lang['X'] = sprintf($lang['help_generic'], $s1, $s2, $s3).$extra;

'help_function_page_selector' => '<h3>What does this do?</h3>
<p>This admin plugin provides a control to allow selecting a site page or other item. This is suitable for allowing a site administrator to select a page that will be stored in a preference.</p>
<h3>Example</h3>
<pre><code>{page_selector name=dfltpage value=$currentpage}</code></pre>
<h4>What parameters does it take?</h4>
<ul>
 <li>name - <em>(string)</em> - The name of the input field</li>
 <li>value - <em>(int)</em> - The id of the currently selected page</li>
 <li>allowcurrent - <em>(bool)</em> - Whether or not to allow the currently selected item to be re-selected. The default value is false.</li>
 <li>allow_all - <em>(bool)</em> - Whether or not to allow inactive content items, or content items that do not have usable links to be selected. The default value is false.</li>
 <li>for_child - <em>(bool)</em> - Indicates that we are selecting a parent page for a new content item. The default value is false.</li>
</ul>',

'help_function_cms_html_options' => <<<'EOS'
<h3>What does this do?</h3>
<p>This plugin renders options for select elements into html &lt;option&gt; and &lt;optgroup&gt; tags. Each option may have child elements, its own title tag, and its own class attribute.</p>
<h3>Examples</h3>
<pre><code>{cms_html_options options=$options [selected=value]}
{$opts[]=['label'=>'Bird','value'=>'b','title'=>'I have a pet bird']}
{$opts[]=['label'=>'Fish','value'=>'f']}
{$sub[]=['label'=>'Small Dog','value'=>'sd']}
{$sub[]=['label'=>'Medium Dog','value'=>'md']}
{$sub[]=['label'=>'Large Dog','value'=>'ld']}
{$opts[]=['label'=>'Dog','value'=>$sub]}
{$opts[]=['label'=>'Cat','value'=>'c','class'=>'cat']}
&lt;select name="pet"&gt;
 {cms_html_options options=$opts selected='md'}
&lt;/select&gt;</code></pre>
<h4>What parameters does it take?</h4>
<p>Unlike the comparable Smarty <code>{html_options}</code> plugin, this supports parameters &apos;label&apos;, &apos;title&apos; and &apos;value&apos;</p>
<ul>
 <li>options - <em>(array)</em> - An array of option definitions</li>
 <li>selected - <em>(string)</em> - The value to automatically select in the dropdown. must correspond to the value of one of the options.</li>
</ul>
<h4>Options</h4>
<p>Each option is an associative array with two or more of the following members:</p>
<ul>
 <li>label - <em>(<strong>required</strong> string)</em> A label for the option (this is what is presented to the user)</li>
 <li>value - <em>(<strong>required</strong> mixed)</em> Either a string value for the option, or an array of option definitions.
 <p>If the value of an option definition is itself an array of options, then the label will be rendered as an optgroup with children.</p>
 </li>
 <li>title - <em>(string)</em> A title attribute for the option</li>
 <li>class - <em>(string)</em> A class name for the option</li>
</ul>
EOS
,

'help_modifier_adjust' => <<<'EOS'
<h3>What does this do?</h3>
<p>This modifier allows use of PHP callables as variable-value modifiers in templates. It may be used instead of directly applying such callables/functions, which is deprecated as from Smarty 4.3.</p>
<h3>Usage:</h3>
<pre><code>{$arg1|adjust:'callable'[:optional arg2[:optional arg3 ...]]}</code></pre>
<p>The callable need not be a simple method name.</p>
<p>In accord with current Smarty security-policy in CMSMS, there is no restriction on the callables which may be used. This may change in future. Best not to rely on &apos;non-standard&apos; modifiers.</p>
<h3>Examples:</h3>
<p>The following example would calculate the md5 hash of a string-variable.</p>
<pre><code>{$somestring|adust:'md5'}</code></pre>
<p>The order of things expressed in the template must match the argument(s) expected by the callable. So, unlike most modifiers, the variable to be modified is not necesarily before the '|adjust'.</p>
<p>The following example would replace any matched content in template variable $somevar.</p>
<pre><code>{'regexpattern'|adust:'preg_replace':'newvalue':$somevar}</code></pre>
<p>A 3-character string '&amp;#;' may be used as a special-case place-holder in the template. So the following is a valid alternative to the previous example, in this case with template layout mimicing the function-call.</p>
<pre><code>{'&amp;#;'|adust:'preg_replace':'regexpattern':'newvalue':$somevar}</code></pre>
EOS
,

'help_modifier_cms_date_format' => '<h3>What does this do?</h3>
<p>This modifier is used to format dates in a suitable format. It uses standard date parameters. If no format string is specified, the system will use the date format string user preference (for logged in users) or the system date format preference.</p>
<p>This modifier is capable of understanding dates in many formats. i.e: date-time strings output from the database or integer timestamps generated by the time() function.</p>
<h3>Examples</h3>
<pre><code>{$some_date_var|cms_date_format[:&lt;format string&gt;]}<br>
{\'2012-03-24\'|cms_date_format}</code></pre>',

'help_modifier_cms_escape' => '<h3>What does this do?</h3>
<p>This modifier is used to escape the string in one of many ways. This can be used for converting the string to multiple different display formats, or to make user entered data with special characters displayable on a standard web page.</p>
<h3>Example</h3>
<pre><code>{$some_var_with_text|cms_escape[:&lt;escape type&gt;|[&lt;character set&gt;]]}</code></pre>
<h4>Valid escape types</h4>
<ul>
 <li>html <em>(default)</em> - use CMSMS\specialize</li>
 <li>htmlall - use CMSMS\entitize</li>
 <li>url - raw url encode all entities</li>
 <li>urlpathinfo - Similar to the url escape type, but also encode /</li>
 <li>quotes - Escape unescaped single quotes</li>
 <li>hex - Escape every character into hex</li>
 <li>hexentity - Hex encode every character</li>
 <li>decentity - Decimal encode every character</li>
 <li>javascript - Escape quotes, backslashes, newlines etc</li>
 <li>mail - Encode an email address into something that is safe to display</li>
 <li>nonstd - Escape non standard characters, such as document quotes</li>
</ul>
<h4>Character Set</h4>
<p>If the character set is not specified, utf-8 is assumed. The character set is only applicable to the &quot;html&quot; and &quot;htmlall&quot; escape types.</p>',

'help_modifier_relative_time' => '<h3>What does this do?</h3>
<p>This modifier converts an integer timestamp, or time/date string into a human readable amount of time from, or to now. i.e: &quot;3 hours ago.&quot;</p>
<h3>Example</h3>
<pre><code>{$some_timevar|relative_time}</code></pre>
<h4>What parameters does it take?</h4>
<p>This modifier does not accept any optional parameters.</p>',

'help_modifier_summarize' => '<h3>What does this do?</h3>
<p>This modifier is used to truncate a long sequence of text to a limited number of &quot;words&quot;.</p>
<h3>Examples</h3>
<pre><code>{$some_var_with_long_text|summarize:&lt;number&gt;}</code></pre>
<p>The following example would strip all html tags from the content and truncate it after 50 words.</p>
<pre><code>{content|strip_tags|summarize:50}</code></pre>',

'help_function_admin_icon' => '<h3>What does this do?</h3>
<p>This is an admin side only plugin to allow modules to easily display icons from the current admin theme. These icons are useful in link building or in displaying status information.</p>
<h3>Example</h3>
<pre><code>{admin_icon icon=\'edit.gif\' class=\'editicon\'}</code></pre>
<h4>What parameters does it take?</h4>
<ul>
 <li>icon - <strong>(required)</strong> - Icon identifier. An extension-stripped filename e.g. \'run\', or theme-images-path-relative filepath e.g. \'icons/extra/warning\'. Any provided file-extension is ignored.</li>
 <li>height - <em>(optional)</em> - Height of the image (in pixels if no other sizer is provided)</li>
 <li>width - <em>(optional)</em> - Width of the image (in pixels if no other sizer is provided)</li>
 <li>alt - <em>(optional)</em> - Alt attribute for the tag</li>
 <li>rel - <em>(optional)</em> - Rel attribute for the tag</li>
 <li>class - <em>(optional)</em> - Class attribute for the tag</li>
 <li>id - <em>(optional)</em> - Id attribute for the tag</li>
 <li>title - <em>(optional)</em> - Title attribute for the tag</li>
 <li>accesskey - <em>(optional)</em> - Access key character for the tag</li>
</ul>
and/or any of Smarty\'s generic parameters (nocache, assign etc)',

'help_function_cms_action_url' => '<h3>What does this do?</h3>
<p>This is a smart plugin useful for generating a URL to a module action. This plugin is useful for module developers who are generating links (either for ajax or or in the admin interface) to perform different functionality or display different data.</p>
<h3>Example</h3>
<pre><code>{cms_action_url module=News action=defaultadmin}</code><pre>,
<h4>What parameters does it take?</h4>
<ul>
 <li>module - <em>(optional)</em> - The module name to generate a URL for. This parameter is not necessary if generating a URL from within a module action to an action within the same module.</li>
 <li>action - <strong>(required)</strong> - The action name to generate a URL to</li>
 <li>returnid - <em>(optional)</em> - The integer pageid to display the results of the action in. This parameter is not necessary if the action is to be displayed on the current page, or if the URL is to an admin action from within an admin action.</li>
 <li>mid - <em>(optional)</em> - The submitted-parameters-prefix. This defaults to &quot;m1_&quot; for admin actions, and &quot;cntnt01&quot; for frontend actions.</li>
 <li>forjs - <em>(optional)</em> - An optional integer indicating that the generated URL should be suitable for use in JavaScript</li>
</ul>
and/or any of Smarty\'s generic parameters (nocache, assign etc)
<p><strong>Note:</strong> Any other parameters not accepted by this plugin are automatically passed to the called module action on the generated URL.</p>',

'help_function_cms_admin_user' => '<h3>What does this do?</h3>
<p>This admin only plugin outputs information about the specified admin user id.</p>
<h4>What parameters does it take?</h4>
<ul>
 <li>uid - <strong>required</strong> - An integer user id representing a valid admin account</li>
 <li>mode - <em>(optional)</em> - The operating mode. Possible values are:
 <ul>
  <li>username <strong>default</strong> - output the username for the specified uid</li>
  <li>email - output the email address for the specified uid</li>
  <li>firstname - output the first name for the specified uid</li>
  <li>lastname - output the surname name for the specified uid</li>
  <li>fullname - output the full name for the specified uid</li>
 </ul>
 </li>
</ul>
and/or any of Smarty\'s generic parameters (nocache, assign etc)
<h3>Example</h3>
<pre><code>{cms_admin_user uid=1 mode=email}</code></pre>',

'help_function_cms_get_language' => '<h3>What does this do?</h3>
<p>This plugin returns the current CMSMS language name. The language is used for translation strings and date formatting.</p>
<h4>What parameters does it take?</h4>
Any of Smarty\'s generic parameters (nocache, assign etc)',

'help_function_cms_help' => '<h3>What does this do?</h3>
<p>This is an admin only plugin to use to generate a link that when clicked will generate popup help for a particular item.</p>
<p>This plugin is typically used from module admin templates to display end user help in a popup window for an input field, column, or other important information.</p>
<h3>Example</h3>
<pre><code>{cms_help key=\'help_field_username\' title=&#36;foo}</code></pre>,
<h4>What parameters does it take?</h4>
<ul>
 <li>realm - <em>(optional string)</em> - The first part in a unique key to identify the help string. If this parameter is not specified, and this plugin is called from within a module action, then the current module name is used. If no module name can be found then &quot;help&quot; is used as the lang realm. key1 is an accepted alias for realm.</li>
 <li>key - <strong>(required string)</strong> - The second part in a unique key to identify the help string to display. This is usually the key from the appropriate realm\'s lang file. key2 is an accepted alias for key.</li>
 <li>title - <em>(optional string)</em> - Help box title</li>
</ul>
and/or any of Smarty\'s generic parameters (nocache, assign etc)',

'help_function_cms_init_editor' => '<h3>What does this do?</h3>
<p>This plugin is used to initialize the appropriate editor for display when WYSIWYG functionalities are required for content manipulation. This plugin will find the selected frontend or admin WYSIWYG <em>(see global settings).</em>, determine if it has been requested, and if so, generate the appropriate html code <em>(usually javascript links)</em> so that the WYSIWYG will initialize properly when the page is loaded. If no WYSIWYG editors have been requested for the frontend request this plugin will produce no output.</p>
<h3>How is it used?</h3>
<p>First, select the frontend WYSIWYG editor to be used, in the global settings page of the admin console and in relevant user&apos;s individual settings. Next, if a frontend WYSIWYG editor is to be used on numerous pages, it might be best to include the {cms_init_editor} plugin in the relevant page template. Otherwise, that plugin may be included in the &quot;Page Specific Metadata&quot; field for each such page.</p>
<h4>What parameters does it take?</h4>
<ul>
 <li>wysiwyg - <em>(optional boolean)</em> - If set and true, generate a syntax-hightlighter editor. Not appropriate for frontend diplay</li>
</ul>
and/or any of Smarty\'s generic parameters (nocache, assign etc)',

'help_function_cms_lang_info' => '<h3>What does this do?</h3>
<p>This plugin returns an object containing the information that CMSMS has about the selected language. This can include locale information, encodings, language aliases etc.</p>
<h3>Example</h3>
<pre><code>{cms_lang_info assign=\'nls\'}{$nls->locale()}</code></pre>
<h4>What parameters does it take?</h4>
<ul>
 <li><em>(optional)lang</em> - The language to return information for. If the lang parameter is not specified then the information for the current CMSMS language is used.</li>
</ul>
and/or any of Smarty\'s generic parameters (nocache, assign etc)
<h3>See Also:</h3>
<p>the Nls class documentation.</p>',

'help_function_cms_pageoptions' => '<h3>What does this do?</h3>
 <p>This plugin creates a sequence of &lt;option&gt; tags for a dropdown list that represent page numbers in a pagination.</p>
 <p>Given the number of pages, and the current page this plugin will generate a list of page numbers that allow quick navigation to a subset of the pages.</p>
<h4>What parameters does it take?</h4>
<ul>
 <li>numpages - <strong>required integer</strong> - The total number of available pages to display</li>
 <li>curpage - <strong>required integer</strong> - The current page number (must be greater than 0 and less than or equal to &quot;numpages&quot;</li>
 <li>surround - <em>(optional integer)</em> - The number of items to surround the current page by. The default value is 3.</li>
 <li>bare - <em>(optional boolean)</em> - Do not output &lt;option&gt; tags, Instead output just a simple array suitable for further manipulation in smarty</li>
</ul>
<h3>Example</h3>
<pre><code>&lt;select name="{$actionid}pagenum"&gt;{cms_pageoptions numpages=50 curpage=14}&lt;/select&gt;</code></pre>',

'help_function_share_data' => '<h3>What does this do?</h3>
<p>This plugin is used to copy one, or more active smarty variables to the parent or global scope.</p>
<h4>What parameters does it take?</h4>
<ul>
 <li>scope - <strong>optional string</strong> - The target scope to copy variables to. Possible values are &quot;parent&quot; <em>(the default)</em> or &quot;global&quot; to copy the data to the global smarty object for subsequent use throughout the page.</li>
 <li>vars - <strong>required mixed</strong> - Either an array of string variable names, or a comma separated list of string variable names</li>
</ul>
<h3>Example</h3>
<pre><code>{share_data scope=global data=\'title,canonical\'}</code></pre>
<h3>Note</h3>
<p>This plugin will not accept array accessors or object members as variable names e.g. <code>$foo[1]</code> or <code>{$foo->bar}</code> will not work.</p>',

'help_function_cms_yesno' => '<h3>What does this do?</h3>
<p>This plugin creates a set of options for a &lt;select&gt; representing a yes/no choice in a form.</p>
<p>This plugin will generate translated yes/no options, with the proper selected value.</p>
<h3>Example</h3>
<pre><code>&lt;select name=&quot;{$actionid}opt&quot;&gt;{cms_yesno selected=$opt}&lt;/select&gt;</code></pre>
<h4>What parameters does it take?</h4>
<ul>
 <li>selected - <em>(optional integer)</em> - either 0 <em>(no)</em> or 1 <em>(yes)</em></li>
</ul>
and/or any of Smarty\'s generic parameters (nocache, assign etc)',

'help_function_module_available' => '<h3>What does this do?</h3>
<p>This plugin reports whether a given module is installed and available for use.</p>
<h3>Example</h3>
<pre><code{module_available module=\'News\' assign=\'havenews\'}{if $havenews}{cms_module module=News}{/if}</code></pre>
<h4>What parameters does it take?</h4>
<ul>
 <li><strong>(required)module</strong> - (string) The name of the module</li>
</ul>
and/or any of Smarty\'s generic parameters (nocache, assign etc)
<h3>Note</h3>
<p>The short form of the module call e.g. <em>{News}</em> cannot be used in this type of expression.</p>',

'help_function_cms_set_language' => '<h3>What does this do?</h3>
<p>This plugin attempts to set the current language for use by translation strings and date formatting to the desired language. The language specified must be known to CMSMS (The nls file must exist). When this function is called, (and unless overridden in the config.php) an attempt will be made to set the locale to the local associated with the language. The locale for the language must be installed on the server.</p>
<h4>What parameters does it take?</h4>
<p><strong>(required)lang</strong> - The desired language. The language must be known to the CMSMS installation (nls file must exist).</p>',

'help_function_browser_lang' => '<h3>What does this do?</h3>
<p>This plugin detects and outputs the language that the users browser accepts, and cross references it with a list of allowed languages to determine a language value for the session.</p>
<h3>How is it used?</h3>
<p>Insert the tag early into the relevant page template <em>(it can go above the &lt;head&gt; section)</em> and provide it the name of the default language, and the accepted languages (only two character language names are accepted), then do something with the result. e.g:</p>
<pre><code>{browser_lang accepted=&quot;de,fr,en,es&quot; default=en assign=tmp}{session_put var=lang val=$tmp}</code></pre>
<p><em>({session_put} is a plugin provided by the CGSimpleSmarty module)</em></p>
<h4>What parameters does it take?</h4>
<ul>
 <li><strong>accepted <em>(required)</em></strong><br> - A comma separated list of two character language names that are accepted</li>
 <li>default<br>- <em>(optional)</em> A default language to output if no accepted language was supported by the browser. en is used if no other value is specified.</li>
</ul>
and/or any of Smarty\'s generic parameters (nocache, assign etc)',

'help_function_cms_stylesheet' => '<h3>What does this do?</h3>
<p>This plugin provides caching of css files by generating static files in the tmp/cache directory, and smarty processing of the individual stylesheets.</p>
<p>By default, the plugin grabs all the stylesheets attached to the current page, in the order specified by the designer, and combines them into a single stylesheet tag.</p>
<p>Generated stylesheets are uniquely named according to the last modification date in the database, and are re-generated only if a stylesheet has changed.</p>
<p>This tag replaces the {stylesheet} tag.</p>
<h3>How is it used?</h3>
<p>Insert the tag into the template/content head-section like: <code>{cms_stylesheet}</code></p>
<h4>What parameters does it take?</h4>
<ul>
 <li><em>(optional)</em> name - Instead of getting all stylesheets for the given page, it will only get one specifically named one, whether or not it\'s assigned to the current page</li>
 <li><em>(optional)</em> nocombine - (boolean, default false) If enabled, and there are multiple stylesheets for the page, those stylesheets will be output as separate tags rather than combined into a single tag</li>
 <li><em>(optional)</em> nolinks - (boolean, default false) If enabled, the stylesheets will be output as an URL without &lt;link&gt; tag</li>
 <li><em>(optional)</em> media - <strong>[deprecated]</strong> - When used in conjunction with the name parameter this parameter will override the media type for that stylesheet. When used in conjunction with the templateid parameter, the media parameter will only output stylesheet tags for those stylesheets that are marked as compatible with the specified media type.</li>
 <li><em>(optional)</em> min</li> - (boolean, default true unless CMS_DEBUG is defined) If enabled, the css content will be minified before storage | output</li>
 <li><em>(optional)</em> stripbackground</li> - (boolean, default false) If enabled, background-specific styling will be removed</li>
 <li><em>(optional)</em> styles</li> - (string) Optional comma-separated sequence of stylesheet ID\'s to use, in the same manner as, and as an alternative to, the &quot;name&quot; parameter</li>
/ul>
<h3>Smarty Processing</h3>
<p>When generating css files, the system passes the retrieved stylesheets through Smarty. The Smarty delimiters are changed from the usual { and } to [[ and ]] respectively, to ease transition in stylesheets. This allows creating smarty variables e.g. [[$red = \'#900\']] at the top of the stylesheet, and then using these variables later in the stylesheet, e.g.</p>
<pre><code>
h3 .error { color: [[$red]]; }<br>
</code></pre>
<p>Because the cached files are generated in the tmp/cache directory of the CMSMS installation, the CSS relative working directory is not the root of the website. Therefore any images, or other tags that require a url should use the [[root_url]] tag to force it to be an absolute url. i.e:</p>
<pre><code>
h3 .error { background: url([[$_site_uploads_url]]/images/error_background.gif); }<br>
</code></pre>
<p><strong>Note:</strong> Due to the caching nature of the plugin, Smarty variables should be placed at the top of EACH stylesheet.</p>',

'help_function_page_attr' => '<h3>What does this do?</h3>
<p>This tag can be used to return the value of the attributes of a certain page.</p>
<h3>Example</h3>
<pre><code>{page_attr key="extra1"}</code>.</pre>
<h4>What parameters does it take?</h4>
<ul>
 <li><em>(optional)</em> page (int|string) - An optional page id or alias to fetch the content from. If not specified, the current page is assumed.</li>
 <li><strong>key [required]</strong> The key to return the attribute of.
  <p>The key can either be a block name, or from a set of standard properties associated with a content page. Some of the accepted standard properties are:</p>
  <ul>
   <li>_dflt_ - (string) The value for the default content block (an alias for content_en)</li>
   <li>title</li>
   <li>description</li>
   <li>alias - (string) The unique page alias</li>
   <li>pageattr - (string) The value of the page specific smarty data attribute./li>
   <li>id - (int) The unique page id</li>
   <li>created_date - (string date) Date of the creation of the content object</li>
   <li>modified_date - (string date) Date of the last modification of the content object</li>
   <li>last_modified_by - (int) UID of the user who last modified the page</li>
   <li>owner - (int) UID of the page owner</li>
   <li>image - (string) The path to the image assocated with the content page</li>
   <li>thumbnail - (string) The path to the thumbnail assocated with the content page</li>
   <li>extra1 - (string) The value of the extra1 attribute</li>
   <li>extra2 - (string) The value of the extra2 attribute</li>
   <li>extra3 - (string) The value of the extra3 attribute</li>
   <li>pageattr - (string) The value of the page specific smarty data attribute./li>
  </ul>
  <p><strong>Note:</strong> The list above is not exhaustive. It is possible to also retrieve the unparsed contents of additional content blocks, or properties added by third party modules.</p>
 </li>
 <li><em>(optional)</em> inactive (boolean) - Allows reading page attributes from inactive pages</li>
</ul>
and/or any of Smarty\'s generic parameters (nocache, assign etc)
<h3>Returns</h3>
<p><strong>string</strong> - The actual value of the content block from the database for the specified block and page.</p>
<p><strong>Note:</strong> - The output of this plugin is not passed through smarty or cleaned for display.  If displaying the data, string data must be converted to entities and/or passed through smarty.</p>',

'help_function_page_image' => '<h3>What does this do?</h3>
<p>This tag can be used to return the value of the image or thumbnail fields of a certain page.</p>
<h3>Example</h3>
<pre><code>{page_image}</code></pre>
<h4>What parameters does it take?</h4>
<ul>
 <li><em>(optional)</em> thumbnail (bool) - Optionally display the value of the thumbnail property instead of the image property</li>
 <li><em>(optional)</em> full (bool)- Optionally output the full URL to the image relative to the image uploads path</li>
 <li><em>(optional)</em> tag (bool) - Optionally output a full image tag, if the property value is not empty. If the tag argument is enabled, full is implied.</li>
</ul>
and/or any of Smarty\'s generic parameters (nocache, assign etc)
<h3>More...</h3>
<p>If the tag argument is enabled, and the property value is not empty, this will trigger a full HTML img tag to be output. Any arguments to the plugin not listed above will automatically be included in the resulting img tag. i.e: <code>{page_image tag=true class="pageimage" id="someid" title="testing"}</code>.</p>
<p>If the plugin is outputting a full img tag, and the alt argument has not been provided, then the value of the property will be used for the alt attribute of the img tag.</p>',

'help_function_dump' => '<h3>What does this do?</h3>
<p>This tag can be used to dump the contents of any smarty variable in a more readable format. This is useful for debugging, and editing templates, to know the format and types of data available.</p>
<h3>Example</h3>
<pre><code>{dump item=\'the_smarty_variable_to_dump\'}</code></pre>
<h4>What parameters does it take?</h4>
<ul>
 <li><strong>item (required)</strong> - The smarty variable to dump the contents of</li>
 <li>maxlevel - The maximum number of levels to recurse (applicable only if recurse is also supplied. The default value is 3.</li>
 <li>nomethods - Skip output of methods from objects</li>
 <li>novars - Skip output of object members</li>
 <li>recurse - Recurse a maximum number of levels through the objects providing verbose output for each item until the maximum number of levels is reached</li>
</ul>
and/or any of Smarty\'s generic parameters (nocache, assign etc)',

'help_function_content_image' => '<h3>What does this do?</h3>
<p>This plugin allows template designers to prompt users to select an image file when editing the content of a page. It behaves similarly to the content plugin, for additional content blocks.</p>
<h3>Example</h3>
<pre><code>{content_image block=\'image1\'}</code></pre>
<h4>What parameters does it take?</h4>
<ul>
 <li><strong>(required)</strong> block (string) - The name for this additional content block.
  <p>Example:</p>
  <pre>{content_image block=\'image1\'}</pre><br>
 </li>
 <li><em>(optional)</em> label (string) - A label or prompt for this content block in the edit content page. If not specified, the block name will be used.</li>
 <li><em>(optional)</em> dir (string) - The name of a directory (relative to the uploads directory, from which to select image files. If not specified, the preference from the global settings page will be used. If that preference is empty, the uploads directory will be used.
 <p>Example: use images from the uploads/images directory.</p>
 <pre><code>{content_image block=\'image1\' dir=\'images\'}</code></pre><br>
 </li>
 <li><em>(optional)</em> default (string) - Use to set a default image used when no image is selected</li>
 <li><em>(optional)</em> urlonly (bool) - output only the url to the image, ignoring all parameters like id, name, width, height, etc</li>
 <li><em>(optional)</em> tab (string) The desired tab to display this field on in the edit form</li>
 <li><em>(optional)</em> exclude (string) - Specify a prefix of files to exclude e.g. thumb_</li>
 <li><em>(optional)</em> sort (bool) - optionally sort the options. Default is to not sort.</li>
 <li><em>(optional)</em> priority (integer) - Allows specifying an integer priority for the block within the tab</li>
</ul>
and/or any of Smarty\'s generic parameters (nocache, assign etc)
<h3>More...</h3>
<p><strong>Note:</strong> As of version 2.2, if this content block contains no value, then no output is generated.</p>
<p>In addition to the arguments listed above, this plugin will accept any number of additional arguments and forward them directly to the generated img tag if any e.g: <code>{content_image block=\'img1\' id="id_img1" class="page-image" title=\'an image block\' data-foo=bar}</code>',

'help_function_content_module' => '<h3>What does this do?</h3>
<p>This tag in effect allows interfacing with suitably-capable modules to generate bespoke content.</p>
<p><strong>Note:</strong> This block type must be used only with compatible modules, and must be used only in accordance with guidance provided by the respective modules.</p>
<h3>Example</h3>
<pre><code>{content_module module=\'FilePicker\' block=\'selectedimage\'}</code></pre>
<h4>What parameters does it take?</h4>
<ul>
 <li><strong>(required)</strong>module - The name of the module that provides the content block. That module must be installed and available.</li>
 <li><strong>(required)</strong>block - The name of the content block</li>
 <li><em>(optional)</em>label - A label for the content block, to be used when editing the page/template where the tag is placed</li>
 <li><em>(optional)</em> required - Allows specifying that the content block must contain some text</li>
 <li><em>(optional)</em> tab - The tab to display this field on in a page-edit form</li>
 <li><em>(optional)</em> priority (integer) - Allows specifying the priority for the block within the tab</li>
</ul>
and other tag-specific parameters to be passed to the module for setting up or otherwise controlling the editing of the block\'s content.<br>
and/or any of Smarty\'s generic parameters (nocache, assign etc)',

'help_function_process_pagedata' => '<h3>What does this do?</h3>
<p>This plugin will process the data in the &quot;pagedata&quot; block of content pages through smarty. It is a mechanism for out-of-order processing of page content, allowing the page body-content to determine what should be in the page header, if necessary.<br>
Since CMSMS 3.0, this plugin has been redundant and deprecated, because the displayed-page body is automatically processed by smarty before the header.</p>
<h3>How is it used?</h3>
<ol>
 <li>Don\'t !</li>
 <li>Formerly, by inserting tag <code>{process_pagedata}</code> into the very top of relevant page templates</li>
</ol>
<h4>What parameters does it take?</h4>
Any of Smarty\'s generic parameters (nocache etc) except assign (no output is generated).',

'help_function_current_date' => '<h3 style="color: red;">Deprecated</h3>
<p>Instead use <code>{$smarty.now|cms_date_format}</code></p>
<h3>What does this do?</h3>
<p>Prints the current date(and/or time). If no format is given, the \'date_format\' site-parameter will be used.</p>
<h3>Example</h3>
<pre><code>{current_date format="D d-m-y"}</code></pre>
<h4>What parameters does it take?</h4>
<ul>
 <li><em>(optional)</em>format - Date/time format string acceptable to PHP\'s date function (or as a fallback, the deprecated date function). See <a href="http://php.net/date" target="_blank">here</a> for a parameter list and information.</li>
 <li><em>(optional)</em>withtime - If true append a time-format string to the date, if not already provided for</li>
 <li><em>(optional)</em>ucword - If true return uppercase the first character of each word</li>
</ul>
and/or any of Smarty\'s generic parameters (nocache, assign etc)',

'help_function_tab_end' => <<<'EOS'
<h3>What does this do?</h3>
 <p>This plugin outputs the HTML code to denote the end of a content area.</p>
<h3>How is it used?</h3>
<p>The following code creates a tabbed content area with two tabs.</p>
<pre><code>{tab_header name='tab1' label='Tab One'}
{tab_header name='tab2' label='Tab Two'}
{tab_start name='tab1'}
&lt;p&gt;This is tab One&lt;/p&gt;
{tab_start name='tab2'}
&lt;p&gt;This is tab Two&lt;/p&gt;
<span style="color: blue;">{tab_end}</span></code></pre>
<h4>What parameters does it take?</h4>
Any of Smarty's generic parameters (nocache, assign etc)
<h3>See Also</h3>
<ul>
 <li>{tab_header}</li>
 <li>{tab_start}</li>
</ul>
EOS
,

'help_function_tab_header' => <<<'EOS'
<h3>What does this do?</h3>
<p>This tag generates the HTML code to delimit the header for a single tab in a tabbed content area.</p>
<h3>How is it used?</h3>
<p>The following code creates a tabbed content area with two tabs.</p>
<pre><code><span style="color: blue;">{tab_header name='tab1' label='Tab One'}</span>
<span style="color: blue;">{tab_header name='tab2' label='Tab Two'}</span>
{tab_start name='tab1'}
&lt;p&gt;This is tab One&lt;/p&gt;
{tab_start name='tab2'}
&lt;p&gt;This is tab Two&lt;/p&gt;
{tab_end}</code></pre>
<p><strong>Note:</strong> <code>{tab_start}</code> must be called with the names in the same order that they were provided to <code>{tab_header}</code></p>
<h4>What parameters does it take?</h4>
<ul>
 <li><strong>name - required string</strong> - The name of the tab. Must match the name of a tab passed to {tab_header}.</li>
 <li>label - <em>optional string</em> - The human readable label for the tab. If not specified, the tab name will be used.</li>
 <li>active - <em>optional mixed./em> - Indicates whether this is the active tab or not. The supplied value may be the name (string) of the active tab in a sequence of tab headers, or a boolean value.</li>
</ul>
and/or any of Smarty's generic parameters (nocache, assign etc)
<h3>See Also</h3>
<ul>
 <li>{tab_start}</li>
 <li>{tab_end}</li>
</ul>
EOS
,

'help_function_tab_start' => <<<'EOS'
<h3>What does this do?</h3>
<p>This plugin provides the html code to delimit the start of content for a specific tab in a tabbed content area.</p>
<h3>How is it used?</h3>
<p>The following code creates a tabbed content area with two tabs.</p>
<pre><code>{tab_header name='tab1' label='Tab One'}
{tab_header name='tab2' label='Tab Two'}
<span style="color: blue;">{tab_start name='tab1'}</span>
&lt;p&gt;This is tab One&lt;/p&gt;
<span style="color: blue;">{tab_start name='tab2'}</span>
&lt;p&gt;This is tab Two&lt;/p&gt;
{tab_end}</code></pre>
<p><strong>Note:</strong> <code>{tab_start}</code> must be called with the names in the same order that they were provided to <code>{tab_header}</code></p>
<h4>What parameters does it take?</h4>
<ul>
 <li><strong>name - required</strong> - The name of the tab. Must match the name of a tab passed to {tab_header}.</li>
</ul>
and/or any of Smarty's generic parameters (nocache, assign etc)
<h3>See Also</h3>
 <ul>
 <li>{tab_header}</li>
 <li>{tab_end}</li>
</ul>
EOS
,
/*
'help_function_title' => '<h3>What does this do?</h3>
<p>Prints the title of the page.</p>
<h3>Example</h3>
<pre><code>{title}</code></pre>
<h4>What parameters does it take?</h4>
Any of Smarty\'s generic parameters (nocache, assign etc)',
*/
/* GONE from 3.0 or earlier
'help_function_stylesheet' => '<h3>What does this do?</h3>
<p><strong>Deprecated:</strong> This function is deprecated and will be removed in later versions of CMSMS.</p>
<p>Gets stylesheet information from the system. By default, it grabs all of the stylesheets attached to the current template.</p>
<h3>How is it used?</h3>
<p>Insert the tag into the template/content\'s head section like: <code>{stylesheet}</code></p>
<h4>What parameters does it take?</h4>
<ul>
 <li><em>(optional)</em>name - Instead of getting all stylesheets for the given page, it will only get one specifically named one, whether it's attached to the current template or not</li>
 <li><em>(optional)</em>media - If name paramter is defined, media parameter sets a different media type for that stylesheet</li>
 <li><em>(optional)</em>templateid - If templateid is defined, return stylesheets associated with that template instead of the current one</li>
</ul>
and/or any of Smarty\'s generic parameters (nocache, assign etc)',
*/
/*
'help_function_sitename' => '<h3>What does this do?</h3>
<p>Shows the name of the site. This is defined during install and can be modified in the Global Settings section of the admin panel.</p>
<h3>Example</h3>
<pre><code>{sitename}</code></pre>
<h4>What parameters does it take?</h4>
Any of Smarty\'s generic parameters (nocache, assign etc)',
*/
/*
'help_function_search' => '<h3>What does this do?</h3>
<p>This is just a wrapper for the Search module, an alternative to <code>{cms_module module='Search'}</code>.
</p>
<h3>How is it used?</h3>
<p>Insert the tag into the template/content where the search input box is to appear, like: <code>{search}</code>. For help about the Search module, please refer to the Search module help.</p>',
*/
'help_function_cms_textarea' => '<h3>What does this do?</h3>
<p>This smarty plugin is used when building admin forms to generate a textarea field.</p>
<h3>Example</h3>
<pre><code>{cms_textarea}</code></pre>
<h4>What parameters does it take?</h4>
<ul>
 <li>name - required string : name attribute for the text area element</li>
 <li>prefix - optional string : optional prefix for the name attribute</li>
 <li>class - optional string : class attribute for the text area element. Additional classes might be added automatically.</li>
 <li>classname - alias for the class parameter</li>
 <li>forcemodule - optional string : used to specify the WYSIWYG or syntax highlighter module to enable. If specified, and available, the module name will be added o the class attribute.</li>
 <li>enablewysiwyg - optional boolean : used to specify whether a WYSIWYG textarea is required. Sets the language to &quot;html&quot;.</li>
 <li>wantedsyntax - optional string used to specify the language (html,css,php,smarty...) to use. If non empty indicates that a syntax highlighter module is requested.</li>
 <li>type - alias for the wantedsyntax parameter</li>
 <li>cols - optional integer : columns of the text area (admin theme css or the syntax/WYSIWYG module might override this)</li>
 <li>width - alias for the cols parameter</li>
 <li>rows - optional integer : rows of the text area (admin theme css or the syntax/WYSIWYG module might override this)</li>
 <li>height - alias for the rows parameter</li>
 <li>maxlength - optional integer : maxlength attribute of the text area (syntax/WYSIWYG module might ignore this)</li>
 <li>required - optional boolean : indicates a required field</li>
 <li>placeholder - optional string : placeholder attribute of the text area (syntax/WYSIWYG module might ignore this)</li>
 <li>value - optional string : default text for the text area, will undergo entity conversion</li>
 <li>text - alias for the value parameter</li>
 <li>cssname - optional string : pass this stylesheet name to the WYSIWYG module if a WYSIWYG module is enabled</li>
 <li>addtext - optional string : additional text to add to the textarea tag</li>
</ul>
and/or any of Smarty\'s generic parameters (nocache, assign etc)',

'help_function_root_url' => '<h3>What does this do?</h3>
<p>Prints the root url location for the site.</p>
<h3>Example</h3>
<pre><code>{root_url}</code></pre>
<h4>What parameters does it take?</h4>
Any of Smarty\'s generic parameters (nocache, assign etc)',

'help_function_repeat' => '<h3>What does this do?</h3>
<p>Repeats a specified sequence of characters, a specified number of times</p>
<h3>Example</h3>
<pre><code>{repeat string=\'repeat this \' times=3}</code></pre>
<h4>What parameters does it take?</h4>
<ul>
 <li>string=\'text\' - The string to repeat</li>
 <li>times=\'num\' - The number of times to repeat it</li>
</ul>
and/or any of Smarty\'s generic parameters (nocache, assign etc)',

'help_function_recently_updated' => <<<'EOS'
<h3>What does this do?</h3>
<p>Outputs a list of recently updated pages.</p>
<h3>Examples</h3>
<pre><code>{recently_updated}<br>
{recently_updated number=15 showtitle='false' leadin='Last Change: ' css_class='my_changes' dateformat='D M j G:i:s T Y'}</code></pre>
<h4>What parameters does it take?</h4>
<ul>
 <li><em>(optional)</em> number=10 - Number of updated pages to show. Example: {recently_updated number=15}.</li>
 <li><em>(optional)</em> leadin='Last changed' - Text to show left of the modified date. Example: {recently_updated leadin='Last Changed'}.</li>
 <li><em>(optional)</em> showtitle='true' - Shows the title attribute if it exists as well (true|false). Example: {recently_updated showtitle='true'}.</li>
 <li><em>(optional)</em> css_class='some_name' - Warp a div tag with this class around the list. Example: {recently_updated css_class='some_name'}.</li>
 <li><em>(optional)</em> dateformat='d.m.y h:m' - Default is d.m.y h:m Example: {recently_updated dateformat='D M j G:i:s T Y'}</li>
</ul>
and/or any of Smarty\'s generic parameters (nocache, assign etc)
EOS
,
/*
'help_function_print' => '<h3>What does this do?</h3>
<p>This is a wrapper tag for the CMSPrinting module, an alternative to <code>{cms_module module='CMSPrinting'}</code>.</p>
<h3>How is it used?</h3>
<p>Insert <code>{print}</code> on a page or in a template. For help about the CMSPrinting module, what parameters it takes etc., please refer to the CMSPrinting module help.</p>',
*/
'help_function_news' => '<h3>What does this do?</h3>
<p>This is a wrapper for the News module, an alternative <code>{cms_module module=\'News\'}</code>.
</p>
<h3>How is it used?</h3>
<p>Insert <code>{news}</code> on a page or in a template. For help about the News module, what parameters it takes etc., please refer to the News module help.</p>',

'help_function_modified_date' => '<h3>What does this do?</h3>
<p>Prints the date and time the page was last modified.</p>
<h3>Example</h3>
<pre><code>{modified_date format="%A %d-%b-%y %T %Z"}</code></pre>
<h4>What parameters does it take?</h4>
<ul>
 <li><em>(optional)</em>format - Date/Time format using parameters from PHP\'s date function. See <a href="http://php.net/date" target="_blank">here</a> for a parameter list and information. If no format is given, it will default to a format similar to \'Jan 01, 2010\'.</li>
 <li><em>(optional)</em>withtime - If true append a time-format string to the date, if not already provided for</li>
</ul>
and/or any of Smarty\'s generic parameters (nocache, assign etc)',

'help_function_metadata' => '<h3>What does this do?</h3>
<p>Displays the metadata for the page. Global metadata from the global settings page and metadata for the specific page will be shown.</p>
<h3>Example</h3>
<pre><code>{metadata}</code></pre>
<h4>What parameters does it take?</h4>
<ul>
 <li><em>(optional)</em>showbase (true/false) - If set to false, The base tag will not be output</li>
</ul>
and/or any of Smarty\'s generic parameters (nocache, assign etc)',

'help_function_menu_text' => '<h3>What does this do?</h3>
<p>Prints the menu text of the page.</p>
<h3>Example</h3>
<pre><code>{menu_text}</code></pre>
<h4>What parameters does it take?</h4>
Any of Smarty\'s generic parameters (nocache, assign etc)',

/* MM module removed 3.0, plugin gone
'help_function_menu' => '<h3>What does this do?</h3>
<p>This is a wrapper tag for the Menu Manager module, an alternative to <code>{cms_module module='MenuManager'}</code>.</p>
<h3>How is it used?</h3>
<p>Insert <code>{menu}</code> on a page or in a template. For help about the Menu Manager module, what parameters it takes etc, please refer to the Menu Manager module help.</p>',
*/
'help_function_last_modified_by' => '<h3>What does this do?</h3>
<p>Prints last person that edited this page. If no format is given, it will default to a ID number of user .</p>
<h3>Example</h3>
<pre><code>{last_modified_by format="fullname"}</code></pre>
<h4>What parameters does it take?</h4>
<ul>
 <li><em>(optional)</em>format - id, username, fullname</li>
</ul>
and/or any of Smarty\'s generic parameters (nocache, assign etc)',

'help_function_image' => '<h3>What does this do?</h3>
<p>Creates an image tag corresponding to an image stored within the site\'s uploaded-images directory</p>
<h3>How is it used?</h3>
<p class="warning">This plugin is deprecated and will be removed at a later date.</p>
<p>Insert the tag into the template/content like: <code>{image src="something.jpg"}</code></p>
<h4>What parameters does it take?</h4>
<ul>
 <li><em>(required)</em> <var>src</var> - Image filename within the site\'s images directory</li>
 <li><em>(optional)</em> <var>width</var> - Width of the image within the page. Defaults to true size.</li>
 <li><em>(optional)</em> <var>height</var> - Height of the image within the page. Defaults to true size.</li>
 <li><em>(optional)</em> <var>alt</var> - Alt text for the image -- needed for xhtml compliance. Defaults to filename.</li>
 <li><em>(optional)</em> <var>class</var> - CSS class for the image</li>
 <li><em>(optional)</em> <var>title</var> - Mouse over text for the image. Defaults to Alt text.</li>
 <li><em>(optional)</em> <var>addtext</var> - Additional text to put into the tag</li>
</ul>
and/or any of Smarty\'s generic parameters (nocache, assign etc)',

'help_function_html_blob' => '<h3>What does this do?</h3>
<p>See the help for global_content for a description.</p>',

//CHECKME still relevant?
'help_function_google_search' => '<h3>What does this do?</h3>
<p>Searches this website using Google\'s search engine.</p>
<h3>Example</h3>
<pre><code>{google_search}</code></pre>
<br>
Note: Google needs an index of this website, for this to work. A website can be submitted to Google <a href="http://www.google.com/addurl.html">here</a>.</p>
<h4>What if I want to change the look of the textbox or button?</h4>
<p>The look of the textbox and button can be changed via css. The textbox is given an id of textSearch and the button is given an id of buttonSearch.</p>
<h4>What parameters does it take?</h4>
<ul>
 <li><em>(optional)</em> domain - This tells google the website domain to search. This script tries to determine this automatically.</li>
 <li><em>(optional)</em> buttonText - The label for the search button. The default is "Search Site".</li>
</ul>
and/or any of Smarty\'s generic parameters (nocache, assign etc)',

//CHECKME still relevant?
'help_function_global_content' => '<h3>What does this do?</h3>
<p>Inserts a global content block into a template or page.</p>
<h3>How is it used?</h3>
<p>Insert the tag into the template/content like: <code>{global_content name=\'myblock\'}</code>, where name is the name given to the block when it was created.</p>
<h4>What parameters does it take?</h4>
<ul>
 <li>name - The name of the global content block to display</li>
</ul>
and/or any of Smarty\'s generic parameters (nocache, assign etc)',

'help_function_get_template_vars' => '<h3>What does this do?</h3>
<p>Dumps all the known smarty variables into the displayed page</p>
<h3>Example</h3>
<pre><code>{get_template_vars}</code></pre>
<h4>What parameters does it take?</h4>
Any of Smarty\'s generic parameters (nocache, assign etc)',

'help_function_page_error' => '<h3>What does this do?</h3>
<p>This is an admin plugin that displays an error in a CMSMS admin page.</p>
<h3>Example</h3>
<pre><code>{page_error msg=\'Error Encountered\'}</code></pre>
<h4>What parameters does it take?</h4>
<ul>
 <li>msg - <strong>required string</strong> - The error message to display</li>
</ul>
and/or any of Smarty\'s generic parameters (nocache, assign etc)',

'help_function_page_warning' => '<h3>What does this do?</h3>
<p>This is an admin plugin that displays a warning in a CMSMS admin page.</p>
<h3>Example</h3>
<pre><code>{page_warning msg=\'Something smells fishy\'}</code></pre>
<h4>What parameters does it take?</h4>
<ul>
 <li>msg - <strong>required string</strong> - The warning message to display</li>
</ul>
and/or any of Smarty\'s generic parameters (nocache, assign etc)',

'help_function_uploads_url' => '<h3>What does this do?</h3>
<p>Prints the uploads URL location for the site.</p>
<h3>Example</h3>
<pre><code>{uploads_url}</code></pre>
<h4>What parameters does it take?</h4>
Any of Smarty\'s generic parameters (nocache, assign etc)',

'help_function_embed' => '<h3>What does this do?</h3>
<p>Enable inclusion (embedding) of any other application into the CMS. The most usual use could be a forum.
This implementation is using IFRAMES so older browsers can have problems. Sorry bu this is the only known way
that works without modifying the embedded application.</p>
<h3>How is it used?</h3>
<ul>
 <li>a) Insert <code>{embed header=true}</code> into the head section of the page/template, or into the metadata section in the options tab of a the page. This will ensure that the required javaScript gets included.  If you insert this tag into the metadata section in the options tab of a content page you must ensure that <code>{metadata}</code> is in the page template.</li>
 <li>b) Insert <code>{embed url="http://www.google.com"}</code> into the page content, or into the body of the page template.</li>
</ul>
<br>
<h4>Example to make the iframe larger</h4>
<p>Add the following to the relevant style sheet:</p>
<pre>#myframe { height: 600px; }</pre>
<br>
<h4>What parameters does it take?</h4>
<ul>
 <li><em>(required)</em>url - the url to be included</li>
 <li><em>(required)</em>header=true - this will generate the header code for good resizing of the IFRAME</li>
 <li>(optional)name - an optional name to use for the iframe (instead of myframe). If this option is used, it must be used identically in both calls, i.e: {embed header=true name=foo} and {embed name=foo url=http://www.google.com} calls.</li>
</ul>',

'help_function_description' => '<h3>What does this do?</h3>
<p>Prints the description (title attribute) of the page.</p>
<h3>Example</h3>
<pre><code>{description}</code></pre>
<h4>What parameters does it take?</h4>
Any of Smarty\'s generic parameters (nocache, assign etc)',

'help_function_created_date' => '<h3>What does this do?</h3>
<p>Prints the date (and/or time) the page was created. If no format is given, the \'date_format\' site preference will be used.</p>
<h3>Example</h3>
<pre><code>{created_date format="D d-m-y"}</code></pre>
<h4>What parameters does it take?</h4>
<ul>
 <li><em>(optional)</em>format - Date/time format string acceptable to PHP\'s date function (or as a fallback, the deprecated date function). See <a href="http://php.net/date" target="_blank">here</a> for a parameter list and information.</li>
 <li><em>(optional)</em>withtime - If true append a time-format string to the date, if not already provided for</li>
</ul>
and/or any of Smarty\'s generic parameters (nocache, assign etc)',

'help_function_content' => '<h3>What does this do?</h3>
<p>This is where the content for a site page will be displayed. It\'s inserted into the template and changed based on the current page being displayed.</p>
<h3>How is it used?</h3>
<p>Insert the tag into the template/content like: <code>{content}</code>.</p>
<p><strong>The default block <code>{content}</code> is required for proper working. (so without the block-parameter)</strong> To give the block a specific label, use the label-parameter. Additional blocks can be added by using the block-parameter.</p>
<p>Example of passing page content to a User Defined Tag as a parameter:</p>
<pre><code>{content assign=pagecontent}<br>
{table_of_contents thepagecontent="$pagecontent"}</code></pre>
<h4>What parameters does it take?</h4>
<ul>
 <li><em>(optional) </em>block - Allows more than one content block per page. When multiple content tags are put on a template, that number of edit boxes will be displayed when the page is edited.
 <h3>Example</h3>
 <pre>{content block="second_content_block" label="Second Content Block"}</pre>
 <p>With this in place, when a page is edited there will a textarea called "Second Content Block".</p>
 </li>
 <li><em>(optional)</em> wysiwyg (true/false) - If set to false, then a WYSIWYG will never be used while editing this block. If true, then it acts as normal. Only works when block parameter is used.</li>
 <li><em>(optional)</em> oneline (true/false) - If set to true, then only one edit line will be shown while editing this block. If false, then it acts as normal. Only works when block parameter is used.</li>
 <li><em>(optional)</em> size (positive integer) - The size of the edit field. The default value is 50. Applicable only when the oneline option is used.</li>
 <li><em>(optional)</em> maxlength (positive integer) - The maximum length of input for the edit field. The default value is 255. Applicable only when the oneline option is used.</li>
 <li><em>(optional)</em> default (string) - Default content for this content block (additional content blocks only)</li>
 <li><em>(optional)</em> label (string) - Page label for the edit-content page</li>
 <li><em>(optional)</em> required (true/false) - Specify that the content block must contain some text</li>
 <li><em>(optional)</em> placeholder (string) - Placeholder text</li>
 <li><em>(optional)</em> priority (integer) - Specify an integer priority for the block within the tab</li>
 <li><em>(optional)</em> tab (string) - The desired tab to display this field on in the edit form</li>
 <li><em>(optional)</em> cssname (string) - A hint to the WYSIWYG editor module to use the specified stylesheet name for extended styles</li>
 <li><em>(optional)</em> noedit (true/false) - If set to true, then the content block will not be available for editing in the content editing form. This is useful for outputting a content block to page content that was created via a third party module.</li>
 <li><em>(optional)</em> data-xxxx (string) - Allows passing data attributes to the generated textarea for use by syntax hilighter and WYSIWYG modules.
  <p>i.e.: <code>{content data-foo="bar"}</code></p>
 </li>
 <li><em>(optional)</em> adminonly (true/false) - If set to true, only members of the special &quot;Admin&quot; group (gid 1) will be able to edit this content block</li>
</ul>
and/or any of Smarty\'s generic parameters (nocache, assign etc)',

'help_function_cms_versionname' => '<h3>What does this do?</h3>
<p>This tag inserts the current version of CMSMS (name only).</p>
<h3>Example</h3>
<pre><code>{cms_versionname}</code></pre>
<h4>What parameters does it take?</h4>
Any of Smarty\'s generic parameters (nocache, assign etc)',

'help_function_cms_version' => '<h3>What does this do?</h3>
<p>This tag inserts the current version of CMSMS (number only).</p>
<h3>Example</h3>
<pre><code>{cms_version}</code></pre>
<h4>What parameters does it take?</h4>
Any of Smarty\'s generic parameters (nocache, assign etc)',

'help_function_cms_selflink' => '<h3>What does this do?</h3>
<p>Creates a link to another CMSMS content page on the same site.</p>
<h3>How is it used?</h3>
<p>Insert the tag into the template/content like: <code>{cms_selflink page=&quot;1&quot;}</code> or <code>{cms_selflink page=&quot;alias&quot;}</code></p>
<h4>What parameters does it take?</h4>
<ul>
 <li><em>(optional)</em> <var>page</var> - Page ID or alias to link to</li>
 <li><em>(optional)</em> <var>anchorlink</var> - Specifies an anchor to add to the generated URL</li>
 <li><em>(optional)</em> <var>fragment</var> - An alias for &quot;anchorlink&quot;</li>
 <li><em>(optional)</em> <var>urlparam</var> - Specify additional parameter(s) for the URL</li>
 <li><em>(optional)</em> <var>tabindex =&quot;a value&quot;</var> - Set a tabindex for the link</li> <!-- Russ - 22-06-2005 -->
 <li><em>(optional)</em> <var>dir start/next/prev/up (previous)</var> - Links to the default start page or the next or previous page, or the parent page (up). If this is used <var>page</var> should not be set.</li>
</ul>
<strong>Note!</strong> Only one of the above may be used in the same cms_selflink statement!!
<ul>
 <li><em>(optional)</em> <var>text</var> - Text to show for the link. If not given, the Page Name is used instead.</li>
 <li><em>(optional)</em> <var>menu 1/0</var> - If 1 the Menu Text is used for the link text instead of the Page Name</li>
 <li><em>(optional)</em> <var>target</var> - Optional target for the a link to point to. Useful for frame and JavaScript situations.</li>
 <li><em>(optional)</em> <var>class</var> - Class for the &lt;a&gt; link. Useful for styling the link.</li>
 <li><em>(optional)</em> <var>id</var> - Optional css_id for the &lt;a&gt; link</li>
 <li><em>(optional)</em> <var>more</var> - place additional options inside the &lt;a&gt; link</li>
 <li><em>(optional)</em> <var>label</var> - Label to use in with the link if applicable</li>
 <li><em>(optional)</em> <var>label_side left/right</var> - Side of link to place the label (defaults to "left")</li>
 <li><em>(optional)</em> <var>title</var> - Text to use in the title attribute. If none is given, then the title of the page will be used for the title.</li>
 <li><em>(optional)</em> <var>rellink 1/0</var> - Make a relational link for accessible navigation. Only works if the dir parameter is set and should only go in the head section of a template.</li>
 <li><em>(optional)</em> <var>href</var> - Specifies that only the result URL to the page alias specified will be returned. This is essentially equal to {cms_selflink page=&quot;alias&quot; urlonly=1}. <strong>Example:</strong> &lt;a href=&quot;{cms_selflink href=&quot;alias&quot;}&quot;&gt;&lt;img src=&quot;&quot;&gt;&lt;/a&gt;.</li>
 <li><em>(optional)</em> <var>urlonly</var> - Specifies that only the resulting url should be output. All parameters related to generating links are ignored.</li>
 <li><em>(optional)</em> <var>image</var> - A url of an image to use in the link. <strong>Example:</strong> {cms_selflink dir=&quot;next&quot; image=&quot;next.png&quot; text=&quot;Next&quot;}.</li>
 <li><em>(optional)</em> <var>alt</var> - Alternative text to be used with image (alt="" will be used if no alt parameter is given)</li>
 <li><em>(optional)</em> <var>width</var> - Width to be used with image (no width attribute will be used on output img tag if not provided)</li>
 <li><em>(optional)</em> <var>height</var> - Height to be used with image (no height attribute will be used on output img tag if not provided)</li>
 <li><em>(optional)</em> <var>imageonly</var> - If using an image, whether to suppress display of text links. If you want no text in the link at all, also set lang=0 to suppress the label. <strong>Example:</strong> {cms_selflink dir=&quot;next&quot; image=&quot;next.png&quot; text=&quot;Next&quot; imageonly=1}.</li>
</ul>
and/or any of Smarty\'s generic parameters (nocache, assign etc)',

'help_function_cms_module' => '<h3>What does this do?</h3>
<p>This tag is used to insert a module into templates and pages. The module must have been configured as a tag plugin (check its help for details).</p>
<h3>Example</h3>
<pre><code>{cms_module module="somemodulename"}</code></pre>
<h4>What parameters does it take?</h4>
<p>module - Name of the module to insert. This is not case-sensitive.</p>
<p>All other parameters are passed on to the module.</p>',

'help_function_cms_module_hint' => '<h3>What does this do?</h3>
<p>This function plugin can be used to provide hints for module behaviour if various parameters cannot be specified on the URL. I.e: In a situation when a site is configured to use pretty urls for SEO purposes it is often impossible to provide additional module parameters like a detailtemplate or sort order on a URL. This plugin can be used in page templates, GCBs or in a page specific way to give hints as to how modules should behave.</p>
<p><strong>Note:</strong> Any parameters that are specified on the URL will override matching module hints.  i.e: When using News and a detailtemplate parameter is specified on a News detail url, any detailtemplate hints will have no effect.</p>
<p><strong>Note:</strong> In order to ensure proper behavior, module hints must be created before the {content} tag is executed in the CMSMS page template. Therefore they should (normally) be created very early in the page template process. An ideal location for page specific hints is in the &quot;Smarty data or logic that is specific to this page:&quot; textarea on the editcontent form.</p>
<h3>How is it used?</h3>
<pre><code>{cms_module_hint module=ModuleName paramname=value ...}</code></pre>
<p><strong>Note:</strong> It is possible to specify multiple parameter hints to a single module in one call to this plugin.</p>
<p><strong>Note:</strong> It is possible to call this module multiple times to provide hints to different modules.</p>
<h3>Example:</h3>
<p>Using the News module, with pretty-urls enabled. Display news articles for a specific category on one page, but using a non-standard detail template to display the individual articles on a different page, e.g. on the site\'s &quot;Sports&quot; page you are calling News like: <code>{News category=sports detailpage=sports_detail}</code>. However, using pretty urls it might be impossible to specify a detailtemplate on the links that will generate the detail views. The solution is to use the {cms_module_hint} tag on the <u>sports_detail</u> page to provide some hints as to how News should behave on that page.</p>
<p>When editing the <u>sports_detail</u> page on the options tab, in the textarea entitled &quot;Smarty data or logic that is specific to this page:&quot; you could enter a tag such as: <code>{cms_module_hint module=News detailtemplate=sports}</code>. Now when a user clicks on a link from the News summary display on the &quot;sports&quot; page she/he will be directed to the <u>sports_detail</u> page, and the News detail template entitled &quot;sports&quot; will be used to display the article.</p>
<h4>What parameters does it take?</h4>
<ul>
 <li>module - <strong>required string</strong> - The name of the module to be hinted.<li>
</ul>
<p>Any further parameters to this tag are stored as hints.</p>',

'help_function_breadcrumbs' => '<h3 style="font-weight:bold;color:#f00;">REMOVED - Use now &#123;nav_breadcrumbs&#125; or &#123;Navigator action=\'breadcrumbs\'&#125;</h3>',

'help_function_anchor' => '<h3>What does this do?</h3>
<p>Makes a proper anchor link.</p>
<h3>Example</h3>
<pre><code>{anchor anchor=\'here\' text=\'Scroll Down\'}</code></pre>
<h4>What parameters does it take?</h4>
<ul>
 <li><var>anchor</var> - Where we are linking to. The part after the #.</li>
 <li><var>text</var> - The text to display in the link</li>
 <li><var>class</var> - The class for the link, if any</li>
 <li><var>title</var> - The title to display for the link, if any</li>
 <li><var>tabindex</var> - The numeric tabindex for the link, if any</li>
 <li><var>accesskey</var> - The accesskey for the link, if any</li>
 <li><em>(optional)</em> <var>onlyhref</var> - Only display the href and not the entire link. No other options will work.</li>
</ul><br>
and/or any of Smarty\'s generic parameters (nocache, assign etc)',

'help_function_site_mapper' => '<h3>What does this do?</h3>
<p>This is actually just a wrapper tag for the Navigator module to make the tag syntax easier, and to simplify creating a sitemap.</p>
<h3>How is it used?</h3>
<p>Insert <code>{site_mapper}</code> on a page or in a template. For help about the Navigator module, what parameters it takes etc., please refer to the Navigator module help.</p>
<p>By default, if no template option is specified the minimal_menu.tpl file will be used.</p>
<p>Parameters used in the tag are each available in the template as <code>{$menuparams.paramname}</code></p>',

'help_function_redirect_url' => '<h3>What does this do?</h3>
<p>This plugin initiates redirection to a specified url. It is handy inside smarty conditional logic (for example, redirect to a splash page if the site is not live yet).</p>
<h3>Example</h3>
<pre><code>{redirect_url to=\'http://www.cmsmadesimple.org\'}</code></pre>',

'help_function_redirect_page' => '<h3>What does this do?</h3>
<p>This plugin initiates redirection to another page. It is handy inside smarty conditional logic (for example, redirect to a login page if the user is not logged in).</p>
<h3>Example</h3>
<pre><code>{redirect_page page=\'some-page-alias\'}</code></pre>',

/*plugin removed 3.0
'help_function_cms_jquery' => '<h3>What does this do?</h3>
 <p>This plugin specifies the javascript libraries and plugins to be used</p>
<h3>How is it used?</h3>
<p>Insert this tag into page/template (usually in the header) like <code>{cms_jquery}</code></p>
<h3>Example</h3>
<pre><code>{cms_jquery cdn='true' exclude='jquery-ui' append='uploads/NCleanBlue/js/ie6fix.js' include_css=0}</code></pre>
<h4><em>Outputs</em></h4>
<pre><code>&lt;script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"&gt;&lt;/script&gt;
&lt;script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"&gt;&lt;/script&gt;
&lt;script type="text/javascript" src="uploads/NCleanBlue/js/ie6fix.js"&gt;&lt;/script&gt;
</code></pre>
<h3>>Known Scripts</h3>
<ul>
 <li><var>jQuery</var></li>
 <li><var>jQuery-UI</var></li>
 <li><var>nestedSortable</var></li>
 <li><var>json</var></li>
 <li><var>migrate</var></li>
</ul>
<h4>What parameters does it take?</h4>
<ul>
 <li><em>(optional) </em><var>exclude</var> - use comma seperated value(CSV) list of scripts to be excluded. <code>'jquery-ui,migrate'</code></li>
 <li><em>(optional) </em><var>append</var> - use comma seperated value(CSV) list of script paths to be appended. <code>'/uploads/jquery.ui.nestedSortable.min.js,http://code.jquery.com/jquery-1.12.4.min.js'</code></li>
 <li><em>(optional) </em><var>cdn</var> - cdn='true' will insert jQuery and jQueryUI Frameworks using Google's Content Delivery Netwok. Default is false.</li>
 <li><em>(optional) </em><var>ssl</var> - unused, deprecated</li>
 <li><em>(optional) </em><var>custom_root</var> - use to set any base path wished.<code>custom_root='http://test.domain.com/'</code> <br>NOTE: overwrites ssl option and works with the cdn option.</li>
 <li><em>(optional) </em><var>include_css <em>(boolean)</em></var> - use to prevent css from being included with the output. Default value is true.</li>
</ul>
and/or any of Smarty\'s generic parameters (nocache, assign etc)',
*/

'help_function_cms_filepicker' => '<h3>What does this do?</h3>
<p>This plugin will create an input field that is controlled by the <em>(current)</em> file picker module to allow selecting a file. This is an admin only plugin useful for module templates, and other admin forms.</p>
<p>This plugin should be used in a module\'s admin template, and the output created by selecting a file should be handled in the normal way in the modules action php file.</p>
<p>Note: This plugin will detect (using internal mechanisms) the currently preferred filepicker module, which might be different than the CMSMS core file picker module, and that filepicker module might ignore some of these parameters.</p>
<h3>How is it used?</h3>
<ul>
 <li>name - <strong>required</strong> string - The name for the input field</li>
 <li>prefix - <em>(optional)</em> string - A prefix for the name of the input field</li>
 <li>value - <em>(optional)</em> string - The current value for the input field</li>
 <li>profile - <em>(optional)</em> string - The name of the profile to use. The profile must exist within the selected file picker module, or a default profile might be used.</li>
 <li>top - <em>(optional)</em> string - A top directory, relative to the uploads directory. This should override any top value already specified in the profile.</li>
 <li>type - <em>(optional)</em> string - An indication of the file type that can be selected.
   <p>Recognised values are: image,audio,video,media,xml,document,archive,any (lower- or upper-case)</p>
 </li>
 <li>required - <em>(optional)</em> boolean - Indicates whether or not a selection is required</li>
</ul>
<h3>Example</h3>
<p>Create a filepicker field to allow selecting images in the images/apples directory.</p>
<pre><code>{cms_filepicker prefix=$actionid name=article_image top=\'images/apples\' type=\'IMAGE\'}</code></pre>',

'help_function_thumbnail_url' => '<h3>What does this do?</h3>
<p>This tag generates an URL representing a thumbnail image when an actual image file relative to the uploads directory is specified.</p>
<p>This tag will return an empty string if the file specified does not exist, the thumbnail does not exist, or there is any permissions propblem.</p>
<h3>Example</h3>
<pre><code>&lt;img src="{thumbnail_url file=\'images/something.jpg\'}" alt="something.jpg"/&gt;</code></pre>
<h4>What parameters does it take?</h4>
<ul>
 <li>file - <strong>required</strong> - The filename and path relative to the uploads directory</li>
 <li>dir - <em>(optional)</em> - An optional directory prefix to prepend to the filename</li>
</ul>
and/or any of Smarty\'s generic parameters (nocache, assign etc)
<h3>Tip</h3>
<p>It is trivial to create a generic template or smarty function that will use the <code>{file_url}</code> and <code>{thumbnail_url}</code> plugins to generate a thumbnail and link to a larger image.</p>',

'help_function_file_url' => '<h3>What does this do?</h3>
<p>This tag generates an URL representing a file within the uploads path of the CMSMS installation.</p>
<p>This tag will return an empty string if the file specified does not exist or there is any permissions propblem.</p>
<h3>Example</h3>
<pre><code>&lt;a href="{file_url file=\'images/something.jpg\'}"&gt;view file&lt;/a&gt;</code></pre>
<h4>What parameters does it take?</h4>
<ul>
 <li>file - <strong>required</strong> - The filename and path relative to the uploads directory</li>
 <li>dir - <em>(optional)</em> - An optional directory prefix to prepend to the filename</li>
</ul>
and/or any of Smarty\'s generic parameters (nocache, assign etc)
<h3>Tip</h3>
<p>It is a trivial process to create a generic template or smarty function that will use the <code>{file_url}</code> and <code>{thumbnail_url}</code> plugins to generate a thumbnail and link to a larger image.</p>',

'help_function_form_end' => '<h3>What does this do?</h3>
<p>This plugin creates an end form element.</p>
<h3>Example</h3>
<pre><code>{form_end}</code></pre>
<h4>What parameters does it take?</h4>
Any of Smarty\'s generic parameters (nocache, assign etc)
<h3>See Also</h3>
<p>The {form_start} plugin which is the complement of this one.</p>',

'help_function_form_start' => '<h3>What does this do?</h3>
 <p>This tag creates a &lt;form&gt; tag for a module action. It is useful in module templates and is part of the separation of design from logic principle that is at the heart of CMSMS.</p>
<h4>What parameters does it take?</h4>
 <p>This tag accepts numerous parameters that can accept the &lt;form&gt; tag.</p>
<ul>
 <li>module - <em>(optional string)</em>
  <p>The module that is the destination for the form data. If this parameter is not specified then an attempt is made to determine the current module.<p>
 </li>
 <li>action - <em>(optional string)</em>
  <p>The module action that is the destination for the form data. If not specified, &quot;default&quot; is assumed for a frontend request, and &quot;defaultadmin&quot; for an admin side request.</p>
 </li>
 <li>mid = <em>(optional string)</em>
  <p>The module actionid that is the destination for the form data. If not specified, a value is automatically calculated.</p>
 </li>
 <li>returnid = <em>(optional integer)</em>
  <p>The content page id that the form should be submitted to. If not specified, the current page id is used for frontend requests.  For admin requests this attribute is not required.</p>
 </li>
 <li>inline = <em>(optional integer)</em>
  <p>A boolean value that indicates that the form should be submitted inline (form processing output replaces the original tag) or not (form processing output replaces the {content} tag). This parameter is only applicable to frontend requests, and defaults to false for frontend requests.</p>
 </li>
 <li>method = <em>(optional string)</em>
  <p>Possible values for this field are GET and POST. The default value is POST.</p>
 </li>
 <li>url = <em>(optional string)</em>
  <p>Allows specifying the action attribute for the form tag. This is useful for building forms that are not destined to a module action. A complete URL is required.</p>
 </li>
 <li>enctype = <em>(optional string)</em>
  <p>Allows specifying the encoding type for the form tag. The default value for this field is multipart/form-data.</p>
 </li>
 <li>id = <em>(optional string)</em>
  <p>Allows specifying the id attribute for the form tag.</p>
 </li>
 <li>class = <em>(optional string)</em>
  <p>Allows specifying the class attribute for the form tag.</p>
 </li>
 <li>extraparms = <em>(optional associative array)</em>
  <p>Allows specifying an associative (key/value) array with extra parameters for the form tag.
 </li>
</ul>
and/or any of Smarty\'s generic parameters (nocache, assign etc)
<p>Extra attributes may be passed to to the {form_start} tag, by prepending the attribute with the &quot;form-&quot;prefix e.g.</p>
<pre><code>{form_start form-data-foo="bar" form-novalidate=""}</code></pre>
<p><strong>Note:</strong> Smarty shorthand attributes are not permitted. Each attribute provided must have a value, even if it is empty.</p>
<h3>How is it used?</h3>
<p>In a module template the following code will generate a form tag to the current action.</p>
<pre><code>{form_start}</code></pre>
<p>This code, in a module template will generate a form tag to the named action.</p>
<pre><code>{form_start action=myaction}</code></pre>
<p>This code will generate a form tag to the named action in the named module.</p>
<pre><code>{form_start module=News action=default}</code></pre>
<p>This code will generate a form tag to the same action, but set an id, and class.</p>
<pre><code>{form_start id="myform" class="form-inline"}</code></pre>
<p>This code will generate a form tag to the named url, and set an id, and class.</p>
<pre><code>{form_start url="/products" class="form-inline"}</code></pre>
<h3>See Also</h3>
<p>See the {form_end} tag that complements this tag.</p>
<h4>Example 1:</h4>
<p>The following is a sample form for use in a module. This hypothetical form will submit to the action that generated the form, and allow the user to specify an integer pagelimit.</p>
<pre><code>{form_start}
&lt;select name="{$actionid}pagelimit"&gt;
&lt;option value="10"&gt;10&lt;/option&gt;
&lt;option value="25"&gt;25&lt;/option&gt;
&lt;option value="50"&gt;50&lt;/option&gt;
&lt;select&gt;
&lt;input type="submit" name="{$actionid}submit" value="Submit"/&gt;
{form_end}</code></pre>
<h4>Example 2:</h4>
<p>The following is a sample form for use in the frontend of a website. Entered into page content, this hypothetical form will gather a page limit, and submit it to the News module.</p>
<pre><code>{form_start method="GET" class="form-inline"}
&lt;select name="pagelimit"&gt;
&lt;option value="10"&gt;10&lt;/option&gt;
&lt;option value="25"&gt;25&lt;/option&gt;
&lt;option value="50"&gt;50&lt;/option&gt;
&lt;select&gt;
&lt;input type="submit" name="submit" value="Submit"/&gt;
{form_end}
{$pagelimit=25}
{if isset($smarty.get.pagelimit)}{$pagelimit=$smarty.get.pagelimit}{/if}
{News pagelimit=$pagelimit}</code></pre>',

'help_function_gather_content' => '<h3>What does this do?</h3>
<p>This tag collects page content by running a hooklist. List member-functions could have been registered by anything.<br><br>
Each such registered function must be like<pre><code>
 public function myfuncname($a)
 {
  $a[ => my content
  OR
  $a = array_merge($a, [my content])
  return $a; //send back the supplied parameter, updated as required
 }</code></pre>
</p>
<h4>What parameters does it take?</h4>
<ul>
 <li>list <em>(optional)</em>
  <p>Name of the hooklist to run. Defaults to &quot;gatherlist&quot;.</p>
 </li>
</ul>
and/or any of Smarty\'s generic parameters (nocache, assign etc)',

'help_function_get_jquery' => '<h3>What does this do?</h3>
<p>This tag generates html (normally for inclusion in the page header), to make the specified jQuery resources available for use in the page.</p>
<p>The \'best\' found version of each resource is used. This means prefer \'min\' format, and prefer the highest version present.</p>
<h3>Example</h3>
<pre><code>{get_jquery migrate=true}</code></pre>
<h4>What parameters does it take?</h4>
<ul>
<li>core <em>(optional boolean or boolean-like string)</em> Default true.
 <p>Provide jQuery javascript.</p>
</li>
<li>migrate <em>(optional boolean or boolean-like string)</em>
 <p>Provide jQuery Migrate javascript.</p>
</li>
<li>ui <em>(optional boolean or boolean-like string)</em> Default true.
 <p>Provide jQuery UI javascript.</p>
</li>
<li>uicss <em>(optional boolean or boolean-like string)</em> Assumed true if jQuery UI is provided.
 <p>Provide jQuery UI styles (.css file).</p>
</li>
</ul>
and/or any of Smarty\'s generic parameters (nocache, assign etc)',

'help_function_cms_queue_script' => '<h3>What does this do?</h3>
<p>Adds the specified file to the list of javascript files being accumulated, for eventual merger into one single file, to improve the efficiency of request processing.</p>
<h3>Example</h3>
<pre><code>{cms_queue_script file="&lt;path-to&gt;relevant.js"}</code></pre>
<h4>What parameters does it take?</h4>
<ul>
<li>file - absolute or relative filepath. If relative, several website folders are checked:
 <ol>
 <li>the \'assets\' folder</li>
 <li>the \'uploads\' folder</li>
 <li>the \'root\' folder</li>
 </ol>
</li>
<li>priority <em>(optional integer)</em> 0 (use the default priority) or 1(highest) to 3
 <p>Scripts in the eventual merged file are grouped and ordered by their respective priority settings, highest) to lowest.</p>
</li>
</ul>',

'help_function_cms_render_scripts' => '<h3>What does this do?</h3>
<p>This tag initiates a merger of previously specified javascript files into one single file, to improve the efficiency of request processing.<br>
Tag output is the page-content appropriate to use that merged file, a string like<br>
<pre>&lt;script src="WHATEVER.js"&gt;&lt;/script&gt;</pre>
</p>
<h3>How is it used?</h3>
<p>Insert the tag into the template/content like: {cms_render_scripts}</p>
<p>Its location would be after suitable instance(s) of companion-tag {cms_queue_script}</p>
<h4>What parameters does it take?</h4>
<ul>
<li>force: optional flag. If true, re-create the merged file even if its contents seem unchanged</li>
<li>defer: optional flag. If true, defer package download</li>
<li>async: optional flag. If true, do async download</li>
</ul><br>
and/or any of Smarty\'s generic parameters (nocache, assign etc)',

'block' => 'Block tags ...',
'function' => 'Function tags might perform a task, or query the database, and typically display output. They can be called like {tagname [attribute=value...]}',
'license' => 'License',
'modifier' => 'Modifier tags take the output of a smarty variable and modify it. They are called like: {$variable|modifier[:arg:...]}',
'postfilter' => 'Postfilter tags are called automatically by smarty after the compilation of every template. They cannot be called manually.',
'prefilter' => 'Prefilter tags are called automatically by smarty before the compilation of every template. They canot be called manually.',
'tag_about' => 'Display the history and author information for this plugin, if available',
'tag_adminplugin' => 'Indicates that the tag is available in the admin interface only, and is usually used in module templates',
'tag_cachable' => 'Indicates whether the output of the plugin can be cached (when smarty caching is enabled). Admin plugins, and modifiers cannot be cached.',
'tag_help' => 'Display the help (if any exists) for this tag',
'tag_info' => 'Each plugin (also known as tag) is a vehicle for including some (generally small amount of) PHP functionality in page content and/or template(s).',
'tag_info2' => 'Such plugins might be designed to generate page content and/or (in a template) set variable(s) for use elsewhere in the template.',
'tag_info3' => 'Tag type broadly indicates the role of the tag. See the individual tooltips.<br>Tags may be for admin/console use only, or also for frontend use.<br>Click the respective tag help icon for specific detail.',
'tag_name' => 'The name of the tag',
'tag_type' => 'The tag type (block, function, modifier, or pre- or post-filter)',
'title_admin' => 'This plugin is only available from the CMSMS admin console',
'title_notadmin' => 'This plugin is usable in both the admin console and on the website frontend.',
'title_cachable' => 'This plugin is cachable',
'title_notcachable' => 'This plugin is not cachable',
'user_tag' => 'User Defined Tag',
'udt__scope' => 'User plugins are for frontend use only.',
'viewabout' => 'Display history and author information for this tag',
'viewhelp' => 'Display help for this tag',

'help_tagcode' => 'Enter PHP code here. Keep in mind that these tags are operated like Smarty function plugins.<br>
<ul>
 <li><strong>Note:</strong> the tag code may access some aspects of the <a href=\"https://www.cmsmadesimple.org/APIDOC2_0\">CMSMS API</a> to interact with the core system and/or with modules. System security is paramount.</li>
 <li>CMSMS variables commonly used in module-actions: $gCms, $config, $db, $smarty will be in scope. Smarty supplies a template-object which will be in scope as $template.</li>
 <li>Parameters passed to the tag e.g. <code>{myudt param1=value1 param2=value2}</code> will be in scope as correspondingly-named variables, as well as members of an associative array $params.</li>
 <li>When relevant, pass results to Smarty for formatting via the $template->assign() method.</li>
 <li>Otherwise, results generated by the tag are to be returned, not echoed.</li>
 <li>It is best to keep these tags short, with a single and small piece of functionality.</li>
</ul>',
'help_tagdesc' => 'Enter details and notes about the tag for future reference when debugging or modifying the tag.',
'help_taglicense' => 'If this tag is to be distributed as a file, it should probably include a license. That must be compatible with CMSMS licensing i.e. GPL2+ or compatible',
'help_tagname' => 'The name must be unique. It may contain only alphanumeric characters and underscores, and must not start with a digit.',
'help_tagparams' => 'Enter a description for each parameter, one per line. Specify at least a name, whether the parameter is optional, default value if any, and brief purpose/nature/usage.',

] + $lang;
