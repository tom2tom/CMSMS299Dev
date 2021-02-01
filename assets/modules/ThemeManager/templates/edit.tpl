<h3>{$mod->Lang('title_edit')}</h3>
{tab_header name='main' label=$mod->Lang('tab_main') active=$tab}
{tab_header name='templates' label=$mod->Lang('tab_templates') active=$tab}
{tab_header name='stylesheets' label=$mod->Lang('tab_stylesheets') active=$tab}
{tab_header name='media' label=$mod->Lang('tab_media') active=$tab}
{tab_header name='fonts' label=$mod->Lang('tab_fonts') active=$tab}
{tab_header name='code' label=$mod->Lang('tab_code') active=$tab}
{tab_start name='main'}
[GENERAL INFO]
{$form_start}
  <div class="pageoverflow postgap">{$t=$mod->Lang('name')}
    <label class="pagetext" for="dfltthm">{$t}:</label>
    {cms_help realm=$_module key='help_theme_name' title=$t}
    <p class="pageinput">
  [NAME INPUT]
    </p>
  </div>
  <div class="pageoverflow postgap">{$t=$mod->Lang('description')}
    <label class="pagetext" for="">{$t}:</label>
    {cms_help realm=$_module key='help_theme_desc' title=$t}
    <p class="pageinput">
  [DESCRIPTION INPUT]
    </p>
  </div>
  <div class="pageoverflow postgap">{$t=$mod->Lang('parent')}
    <label class="pagetext" for="">{$t}:</label>
    {cms_help realm=$_module key='help_theme_parent' title=$t}
    <p class="pageinput">
  [PARENT SELECTOR]
    </p>
  </div>
  <div class="pageoverflow postgap">{$t=$mod->Lang('copyright')}
    <label class="pagetext" for="">{$t}:</label>
    {cms_help realm=$_module key='help_theme_copyright' title=$t}
    <p class="pageinput">
  [COPYRIGHT INPUT]
    </p>
  </div>
  <div class="pageoverflow postgap">{$t=$mod->Lang('license')}
    <label class="pagetext" for="">{$t}:</label>
    {cms_help realm=$_module key='help_theme_license' title=$t}
    <p class="pageinput">
  [LICENSE SELECTOR]
    </p>
  </div>
  <div class="pageoverflow postgap">{$t=$mod->Lang('thumbnail')}
    <label class="pagetext" for="">{$t}:</label>
    {cms_help realm=$_module key='help_theme_thumb' title=$t}
    <p class="pageinput">
  [THUMBNAIL VIEW/CREATE]
    </p>
  </div>
  <div class="pageoverflow">
    <p class="pageinput">
      <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{$mod->Lang('submit')}</button>
      <button type="submit" name="{$actionid}cancel" class="adminsubmit icon undo">{$mod->Lang('close')}</button>
    </p>
  </div>
</form>
{tab_start name='templates'}
<div class="postgap">
  <label class="pagetext" for="tpltabname">{$mod->Lang('name')}:</label>
  <p id="tpltabname" class="pageinput currentname">
  [CURRENT NAME]
  </p>
</div>
{if $tplitems}
{include file='module_file_tpl:ThemeManager;listtemplates.tpl'}
{else}
<p class="information">{$mod->Lang('no_template')}</p>
{/if}
[TEMPLATE SELECTOR WITH DnD]
{tab_start name='stylesheets'}
<div class="postgap">
  <label class="pagetext" for="csstabname">{$mod->Lang('name')}:</label>
  <p id="csstabname" class="pageinput currentname">
  [CURRENT NAME]
  </p>
</div>
{if $styleitems}
{include file='module_file_tpl:ThemeManager;liststyles.tpl'}
{else}
<p class="information">{$mod->Lang('no_style')}</p>
{/if}
[STYLES SELECTOR WITH DnD]
{tab_start name='media'}
<div class="postgap">
  <label class="pagetext" for="imgtabname">{$mod->Lang('name')}:</label>
  <p id="imgtabname" class="pageinput currentname">
  [CURRENT NAME]
  </p>
</div>
{if $mediaitems}
{include file='module_file_tpl:ThemeManager;listmedia.tpl'}
{else}
<p class="information">{$mod->Lang('no_media')}</p>
{/if}
[FILES SELECTOR WITH DnD]
{tab_start name='fonts'}
<div class="postgap">
  <label class="pagetext" for="fonttabname">{$mod->Lang('name')}:</label>
  <p id="fonttabname" class="pageinput currentname">
  [CURRENT NAME]
  </p>
</div>
{if $fontitems}
{include file='module_file_tpl:ThemeManager;listfonts.tpl'}
{else}
<p class="information">{$mod->Lang('no_font')}</p>
{/if}
[FILES SELECTOR WITH DnD]
{tab_start name='code'}
<div class="postgap">
  <label class="pagetext" for="codetabname">{$mod->Lang('name')}:</label>
  <p id="codetabname" class="pageinput currentname">
  [CURRENT NAME]
  </p>
</div>
{if $codeitems}
{include file='module_file_tpl:ThemeManager;listcode.tpl'}
{else}
<p class="information">{$mod->Lang('no_code')}</p>
{/if}
[FILES SELECTOR WITH DnD]
{tab_end}

{if $contextmenus}
<div id="contextmenus">
{foreach $contextmenus as $menu}{$menu}
{/foreach}
</div>
{/if}
{* if popup dialog needed
<div id="" title="{$mod->Lang('title_X')}" style="display:none;">
 <form id="clonedialog_form" enctype="multipart/form-data" method="post">
 <div class="dlg-options">
  <label for="fld_name">{$mod->Lang('name')}:</label> <input type="text" id="fld_name" name="{$actionid}name" />
 </div>
 </form>
</div>
{/if}
*}
