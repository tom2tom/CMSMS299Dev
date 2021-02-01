<h3>{$mod->Lang('title_inspect')}</h3>
<div class="pageoverflow postgap">
 <label class="pagetext" for="thmname">{$mod->Lang('name')}:</label>
 <p id="thmname" class="pageinput">
 [NAME]
 </p>
</div>
{tab_header name='main' label=$mod->Lang('tab_main') active=$tab}
{tab_header name='templates' label=$mod->Lang('tab_templates') active=$tab}
{tab_header name='stylesheets' label=$mod->Lang('tab_stylesheets') active=$tab}
{tab_header name='media' label=$mod->Lang('tab_media') active=$tab}
{tab_header name='fonts' label=$mod->Lang('tab_fonts') active=$tab}
{tab_header name='code' label=$mod->Lang('tab_code') active=$tab}
{tab_header name='preview' label=$mod->Lang('tab_preview') active=$tab}
{tab_start name='main'}
  <div class="pageoverflow postgap">
    <label class="pagetext" for="">{$mod->Lang('description')}:</label>
    <p id="" class="pageinput">
  [DESCRIPTION]
    </p>
  </div>
  <div class="pageoverflow postgap">
    <label class="pagetext" for="">{$mod->Lang('parent')}:</label>
    <p id="" class="pageinput">
  [PARENT]
    </p>
  </div>
  <div class="pageoverflow postgap">
    <label class="pagetext" for="">{$mod->Lang('copyright')}:</label>
    <p id="" class="pageinput">
  [COPYRIGHT]
    </p>
  </div>
  <div class="pageoverflow postgap">
    <label class="pagetext" for="">{$mod->Lang('license')}:</label>
    <p id="" class="pageinput">
  [LICENSE]
    </p>
  </div>
  <div class="pageoverflow postgap">
    <label class="pagetext" for="">{$mod->Lang('thumbnail')}:</label>
    <p id="" class="pageinput">
  [THUMBNAIL VIEW/CREATE]
    </p>
  </div>
{tab_start name='templates'}
{if $tplitems}
{include file='module_file_tpl:ThemeManager;listtemplates.tpl'}
{else}
<p class="information">{$mod->Lang('no_template')}</p>
{/if}
{tab_start name='stylesheets'}
{if $styleitems}
{include file='module_file_tpl:ThemeManager;liststyles.tpl'}
{else}
<p class="information">{$mod->Lang('no_style')}</p>
{/if}
{tab_start name='media'}
{if $mediaitems}
{include file='module_file_tpl:ThemeManager;listmedia.tpl'}
{else}
<p class="information">{$mod->Lang('no_media')}</p>
{/if}
{tab_start name='fonts'}
{if $fontitems}
{include file='module_file_tpl:ThemeManager;listfonts.tpl'}
{else}
<p class="information">{$mod->Lang('no_font')}</p>
{/if}
{tab_start name='code'}
{if $codeitems}
{include file='module_file_tpl:ThemeManager;listcode.tpl'}
{else}
<p class="information">{$mod->Lang('no_code')}</p>
{/if}
{tab_start name='preview'}
[IFRAME]
{tab_end}

{$form_start}
  <div class="pageoverflow pregap">
    <p class="pageinput">
      <button type="submit" name="{$actionid}close" class="adminsubmit icon close">{$mod->Lang('close')}</button>
    </p>
  </div>
</form>
{if $contextmenus}
<div id="contextmenus">
{foreach $contextmenus as $menu}{$menu}
{/foreach}
</div>
{/if}
