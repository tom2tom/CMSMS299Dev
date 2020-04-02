<?php

#D
$lang['description'] = 'Settings for editing website page content';

#F
$lang['friendlyname'] = 'Rich Text Editing';

#H
$lang['help_module'] = <<<'EOS'
<h3>What does this do?</h3>
<p>The module allows users to choose a rich-text editor for page content, an alternative to the hefty MicroTiny.
<br /><br />
Editors currently supported are:
<ul>
<li><a href="https://alex-d.github.io/Trumbowyg">Trumbowyg</a></li>
<li><a href="https://www.cssscript.com/wysiwyg-editor-yseditor">ysEditor</a></li>
</ul>
See <a href="https://alternativeto.net/software/trumbowyg">some candidates</a>.
<h3>How is it used?</h3>
<p>After installing the module, go (via the site's extensions menu) to the module's administration page. There, specify respective source-URL's default themes.
<br /><br />
The module implements a CMSMS-standard interface by which other modules and administration procedures can make rich-text content editing available to site users.</p>
%s
<h3>Support</h3>
<p>This module is provided as-is. Please read the text of the license for the full disclaimer.</p>
<p>For help:<ul>
<li>discussion may be found in the <a href="http://forum.cmsmadesimple.org">CMS Made Simple Forums</a>; or</li>
<li>you may have some success emailing the author directly.</li>
</ul></p>
<p>For the latest version of the module, or to report a bug, visit the module's <a href="http://dev.cmsmadesimple.org/projects/richediting">forge-page</a>.</p>
<h3>Copyright and License</h3>
<p>Copyright &copy; 2019-2020 Tom Phane &lt;tomph@cmsmadesimple.org&gt;. All rights reserved.</p>
<p>This module has been released under version 3 of the <a href="http://www.gnu.org/licenses/agpl.html">GNU Affero General Public License</a>, and must not be distributed or used except in accordance with the terms of that license, or any later version of that license which is granted by the module's distributor.</p>
EOS;
//ABANDONED <li><a href="https://jaredreich.com/pell">Pell</a></li>

#I
$lang['info_settings'] = <<<'EOS'
The settings here are only for configuring the editors specified below.
<br /><br />
Other similar editors might also be available, if suitable module(s) are installed. If so, such editors' configuration would be handled in the respective modules.
<br /><br />
The editor which is <em>actually used</em> is determined by a selection on the System Settings page, perhaps overridden by individual users' choices on the (personal) Settings page.
EOS;

#S
$lang['settings_error'] = 'Settings problem: %s. Settings NOT updated';
$lang['settings_success'] = 'Settings updated';

// accumulate all editors' lang keys (all such keys are squeezed into en_US realm)
$cl = CMSMS\NlsOperations::get_current_language();
if ($cl == 'en_US') $cl = 'IGNORE';
$p = cms_join_path(dirname(__DIR__),'lib','*','{lang,lang','ext}','{en_US.php,'.$cl.'.php}');
$from = glob($p, GLOB_BRACE|GLOB_NOSORT);
usort($from, function ($a, $b) {
	//TODO en_US file(s) always 1st
	$an = basename($a);
	return ($an == 'en_US.php') ? -1 : 0;
});
foreach ($from as $fp) {
	require_once $fp;
}
