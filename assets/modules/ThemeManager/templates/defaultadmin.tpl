{if $pset}
{tab_header name='themes' label=$mod->Lang('tab_themes') active=$tab}
{tab_header name='settings' label=$mod->Lang('tab_settings') active=$tab}
{tab_start name='themes'}
{/if}
{if $pdev && $pmod && $themes && count($themes) > 20}
<div class="pageoptions postgap">{$t=$mod->Lang('create_theme')}
  <a href="{$addurl}" title="{$t}">{$iconadd}</a>
  <a href="{$addurl}">{$t}</a>
</div>
{/if}
{if $themes}
<table class="pagetable">
  <thead>
    <tr>
      <th>{$mod->Lang('name')}</th>
      <th>{$mod->Lang('description')}</th>
      <th>{$mod->Lang('modified')}</th>
      <th>{$mod->Lang('current')}</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
    {foreach $themes as $one}{cycle values='row1,row2' assign='rowclass'}
    <tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
      {strip}{$rn=$one.rawname}
      <td>
       {if $pmod}
        <a href="{$editurl}&amp;theme={$rn}" title="{$mod->Lang('edit', {$rn})}">{$rn}</a>
       {else}
        {$rn}
       {/if}
      </td>
      <td{if $one.fulldesc} title="{$one.fulldesc}"{/if}>{$one.description}</td>
      <td>{$one.modified|cms_date_format|cms_escape}</td>
      {if $pmod}
      <td style="text-align:center;">
        {if $one.current}
        {$icontrue}
        {else}
        <a href="{$activateurl}&amp;theme={$rn}">{$iconfalse}</a>
        {/if}
      {else}
      <td>
        {if $one.current}{$mod->Lang('yes')}{/if}
      </td>
      {/if}
      <td>
        <span class="action" context-menu="Theme-{$rn}">{admin_icon icon='menu.gif' alt='menu' title=$mod->Lang('help_menu') class='systemicon'}</span>
      </td>
{/strip}
    </tr>
    {/foreach}
  </tbody>
</table>
{else}
<p class="information pregap">{$mod->Lang('no_theme')}</p>
{/if}
{if $pmod}
<div class="pageoptions pregap">{$t=$mod->Lang('create_theme')}
  {if $pdev}
  <a href="{$addurl}" title="{$t}">{$iconadd}</a>
  <a href="{$addurl}">{$t}</a>
  <br />
  {/if}
  <p class="pagetext">{$mod->Lang('title_import')}</p>
  <form action="{$importurl}" enctype="multipart/form-data" method="post">
    {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />
  {/foreach}
    <input type="file" name="{$actionid}import_file" id="theme_select" title="{$mod->Lang('help_themeimport')|escape:'javascript'}" size="30" maxlength="50" accept="text/xml" multiple="" />
  </form>
  <br />
  <div id="theme_dropzone" title="{$mod->Lang('help_drop')}">
   <p>{$mod->Lang('title_drop')}</>
  </div>
</div>
{/if}
{if $pset}
{tab_start name='settings'}
{include file='module_file_tpl:ThemeManager;settingstab.tpl'}
{tab_end}
{/if}
{if $themes && $contextmenus}
<div id="contextmenus">
{foreach $contextmenus as $menu}{$menu}
{/foreach}
</div>
{/if}
{if $themes && $pmod}{* popup dialog for clone name *}
<div id="clone_dlg" title="{$mod->Lang('title_clonename')}" style="display:none;">
 <form id="clonedialog_form" enctype="multipart/form-data" method="post">
 <div class="dlg-options">
  <label for="fld_name">{$mod->Lang('name')}:</label> <input type="text" id="fld_name" name="{$actionid}name" />
 </div>
 </form>
</div>
{/if}
