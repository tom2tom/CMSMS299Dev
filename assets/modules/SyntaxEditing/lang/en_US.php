<?php

$lang = [

// D
'description' => 'Settings for editing website textfiles',

// F
'friendlyname' => 'Advanced Editing',

// H
'help_module' => <<<'EOS'
<h3>What does this do?</h3>
<p>The module provides infrastructure for online editing of files such as templates, stylesheets and (PHP) user-defined tags, using appropriate syntax-highlighting and various advanced editing features.
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
<li>discussion may be found in the <a href="http://forum.cmsmadesimple.org">CMS Made Simple Forums</a>, or</li>
<li>you may have some success emailing the author directly.</li>
</ul></p>
<p>For the latest version of the module, or to report a bug, visit the module's <a href="http://dev.cmsmadesimple.org/projects/syntaxediting">forge-page</a>.</p>
<h3>Copyright and License</h3>
<p>Copyright &copy; 2018-2020 CMS Made Simple Foundation &lt;foundation@cmsmadesimple.org&gt;. All rights reserved.</p>
<p>This module has been released under version 2 of the <a href="http://www.gnu.org/licenses">GNU General Public License</a>, and must not be used except in accord with the terms of that license, or any later version of that license which is granted by the module's distributor.</p>
EOS
,

// I
'info_settings' => <<<'EOS'
The settings here are only for configuring the editors specified below.
<br /><br />
Other similar editors might also be available, if suitable module(s) are installed. If so, such editors' configuration would be handled in the respective modules.
<br /><br />
The editor which is <em>actually used</em> is determined by a selection on the System Settings page, perhaps overridden by individual users' choices on the (personal) Settings page.
EOS
,

// S
'settings_error' => 'Settings problem: %s. Settings NOT updated',
'settings_success' => 'Settings updated',

] + $lang;

// accumulate all editors' lang keys (lang(s) squeezed into en_US space)
$cl = CMSMS\NlsOperations::get_current_language();
if ($cl == 'en_US') $cl = 'IGNORE';
$p = cms_join_path(dirname(__DIR__),'lib','{lang,lang','ext}','{en_US.php,'.$cl.'.php}');
$from = glob($p, GLOB_BRACE|GLOB_NOSORT);
usort($from, function ($a, $b) {
	//TODO en_US file(s) always 1st
	$an = basename($a);
	return ($an == 'en_US.php') ? -1 : 0;
});
foreach ($from as $fp) {
	require_once $fp;
}
