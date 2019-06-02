<?php

#A
$lang['ace_helpmain'] = <<<'EOS'
A feature-rich, high-performance, <a href="https://ace.c9.io/#nav=production">popular</a> open-source editor.
<br /><br />
For more information, go to the <a href="https://ace.c9.io">Ace website</a>.
EOS;
$lang['ace_helptheme'] = <<<'EOS'
Specify the theme name (lower case, any ' ' replaced by '_').
<br /><br />
Ace themes can be evaluated at <a href="https://ace.c9.io/build/kitchen-sink">C9</a>.
EOS;
$lang['ace_theme'] = 'Default Ace Theme';
$lang['ace_url'] = 'Ace Editor Script URL';

#C
$lang['codemirror_helpmain'] = <<<'EOS'
A feature-rich, versatile, <a href="https://codemirror.net/doc/realworld.html">popular</a> open-source editor. Its support for mobile-device browsers is work-in-progress.
<br /><br />
For more information, go to the <a href="https://codemirror.net">CodeMirror website</a>.
EOS;
$lang['codemirror_helptheme'] = <<<'EOS'
Specify the theme name (lower case, any ' ' replaced by '_').
<br /><br />
CodeMirror themes can be evaluated at the <a href="https://codemirror.net/demo/theme.html">website</a>.
EOS;
$lang['codemirror_theme'] = 'Default CodeMirror Theme';
$lang['codemirror_url'] = 'CodeMirror Editor Script URL';

#D
$lang['description'] = 'Settings for editing website textfiles';

#F
$lang['friendlyname'] = 'Advanced Editing';

#H
$lang['help_module'] = <<<'EOS'
<h3>What does this do?</h3>
<p>The module provides infrastructure for online editing of files such as templates and stylesheets, using appropriate syntax-highlighting and various advanced editing features.
<br /><br />
Popular editors <a href="https://ace.c9.io">Ace</a> and <a href="https://codemirror.net">CodeMirror</a> are supported.</p>
<h3>How is it used?</h3>
<p>After installing the module, go (via the site's extensions menu) to the module's administration page. There, specify respective file sources (CDN's) and default themes.
<br /><br />
The module implements a CMSMS-standard interface by which other modules and administration procedures can make advanced editing available to site users.</p>
%s
<h3>Support</h3>
<p>This module is provided as-is. Please read the text of the license for the full disclaimer.</p>
<p>For help:<ul>
<li>discussion may be found in the <a href="http://forum.cmsmadesimple.org">CMS Made Simple Forums</a>; or</li>
<li>you may have some success emailing the author directly.</li>
</ul></p>
<p>For the latest version of the module, or to report a bug, visit the module's <a href="http://dev.cmsmadesimple.org/projects/corefilemanager">forge-page</a>.</p>
<h3>Copyright and License</h3>
<p>Copyright &copy; 2018 CMS Made Simple Foundation &lt;foundation@cmsmadesimple.org&gt;. All rights reserved.</p>
<p>This module has been released under version 3 of the <a href="http://www.gnu.org/licenses/agpl.html">GNU Affero General Public License</a>, and must not be used except in accordance with the terms of that license, or any later version of that license which is granted by the module's distributor.</p>
EOS;

#I
$lang['info_settings'] = <<<'EOS'
The settings here are only for configuring the editors specified below.
<br /><br />
Other similar editors might also be available, if suitable module(s) are installed. If so, such editors' configuration would be handled in the respective modules.
<br /><br />
The editor which is <em>actually used</em> is determined by a selection on the System Settings page, perhaps overridden by individual users' choices on the (personal) Settings page.
EOS;

#S
$lang['settings_acecdn'] = <<<'EOS'
Enter here the URL which specifies which, and from where, text-editor source files will be retrieved at runtime in preparation for using the editor.
<br /><br />
The editor may be installed on and run from this website, or run from CDN. The last part of the URL will often be a version-number. To use onsite sources, they must be manually installed in the specified place.
<br /><br />
CDN example: https://somecdnsite.com/ace/1.2.3
<br /><br />
One good CDN source, perhaps the best, is <a href="https://cdnjs.com">cdnjs</a>. (browse for 'ace', omit the trailing '/ace.js')
EOS;
$lang['settings_cmcdn'] = <<<'EOS'
Enter here the URL which specifies which, and from where, text-editor source files will be retrieved at runtime in preparation for using the editor.
<br /><br />
The editor may be installed on and run from this website, or run from CDN. The last part of the URL will often be a version-number. To use onsite sources, they must be manually installed in the specified place.
<br /><br />
CDN example: https://somecdnsite.com/codemirror/5.4.3
<br /><br />
One good CDN source, perhaps the best, is <a href="https://cdnjs.com">cdnjs</a>. (browse for 'codemirror', omit the trailing '/codemirror.min.js')
EOS;

$lang['settings_error'] = 'Settings problem: %s. Settings NOT updated';
$lang['settings_success'] = 'Settings updated';
