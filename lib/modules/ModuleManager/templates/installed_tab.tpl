{function status}
  {$stats = []}
  {if !$item.installed}
    {if $item.can_install}
      {capture append=stats}<span title="{$mod->Lang('caninstall')}">{$mod->Lang('notinstalled')}</span>{/capture}
    {else if $item.missing_deps}
      {capture append=stats}<span class="important red" title="{$mod->Lang('title_missingdeps')}">{$mod->Lang('missingdeps')}</span>{/capture}
    {/if}
  {elseif $item.notavailable}
    {capture append=stats}<span class="important red" title="{$mod->Lang('title_notavailable')}">{$mod->Lang('notavailable')}</span>{/capture}
  {else}
    {if $item.e_status}
      {capture append=stats}<span class="{if $item.e_status == 'db_newer'}important {/if}orange"{if $item.title_key} title="{$mod->Lang({$item.title_key})}"{/if}>{$mod->Lang({$item.main_key})}</span>{/capture}
    {else}
      {capture append=stats}{$mod->Lang({$item.main_key})}{/capture}
    {/if}
    {if $item.missing_deps}
      {capture append=stats}<span class="important red" title="{$mod->Lang('title_missingdeps')}">{$mod->Lang('missingdeps')}</span>{/capture}
    {/if}
    {if !($item.system_module || $item.can_uninstall)}
      {capture append=stats}<span title="{$mod->Lang('title_cantuninstall')}">{$mod->Lang('cantuninstall')}</span>{/capture}
    {/if}
  {/if}
  {if !$item.ver_compatible}
    {capture append=stats}<span class="important" title="{$mod->Lang('title_notcompatible')}">{$mod->Lang('notcompatible')}</span>{/capture}
  {/if}
  {if !($item.system_module || $item.installed || $item.writable)}
    {capture append=stats}<span title="{$mod->Lang('title_cantremove')}">{$mod->Lang('cantremove')}</span>{/capture}
  {/if}
  {if !empty($item.dependents)}{$deps = []}
    {foreach $item.dependents as $one}
      {capture append=deps}<strong>{$one}</strong>{/capture}
    {/foreach}
    {capture append=stats}<span title="{$mod->Lang('title_depends_upon')}">{$mod->Lang('depends_upon')}</span>: {', '|implode:$deps}{/capture}
  {/if}
{"<br />\n"|implode:$stats}
{/function}

{function actions}
  {$acts = []}
  {if $item.installed}
    {if $item.e_status == 'need_upgrade' }
      {capture append=acts}<a href="{cms_action_url action='local_upgrade' mod=$item.name}" class="modop mod_upgrade" title="{$mod->Lang('title_upgrade')}">{$mod->Lang('upgrade')}</a>{/capture}
    {/if}
    {if (!$item.system_module && $item.can_uninstall)}
      {capture append=acts}<a href="{cms_action_url action='local_uninstall' mod=$item.name}" class="modop mod_uninstall" title="{$mod->Lang('title_uninstall')}">{$mod->Lang('uninstall')}</a>{/capture}
    {/if}
  {else}
    {if $item.can_install}
      {capture append=acts}<a href="{cms_action_url action='local_install' mod=$item.name}" class="modop mod_install" title="{$mod->Lang('title_install')}">{$mod->Lang('install')}</a>{/capture}
    {elseif $item.missing_deps}
      {capture append=acts}<a href="{cms_action_url action='local_missingdeps' mod=$item.name}" class="modop mod_missingdeps" title="{$mod->Lang('title_missingdeps')}">{$mod->Lang('missingdeps')}</a>{/capture}
    {/if}
    {if !$item.system_module}{if $item.writable}
      {capture append=acts}<a href="{cms_action_url action='local_remove' mod=$item.name}" class="modop mod_remove" title="{$mod->Lang('title_remove')}">{$mod->Lang('remove')}</a>{/capture}
    {else}
      {capture append=acts}<a href="{cms_action_url action='local_chmod' mod=$item.name}" class="modop mod_chmod" title="{$mod->Lang('title_chmod')}">{$mod->Lang('changeperms')}</a>{/capture}
    {/if}{/if}
  {/if}
{"<br />\n"|implode:$acts}
{/function}

{if !empty($module_info)}
{$it = {admin_icon icon='true.gif' alt='active' title="{$mod->Lang('title_active')}"}}
{$if = {admin_icon icon='false.gif' alt='inactive' title="{$mod->Lang('title_inactive')}"}}
{$ih = {admin_icon icon='info.gif' alt='help' title="{$mod->Lang('title_modulehelp')}"}}
{$ia = {admin_icon icon='icons/extra/info.gif' alt='about' title="{$mod->Lang('title_moduleabout')}"}}
<div class="pageoptions">
  <a id="importbtn">{admin_icon icon='import.gif'} {$mod->Lang('importxml')}</a>
</div>
<table class="pagetable">
  <thead>
    <tr>
      <th></th>
      <th>{$mod->Lang('nametext')}</th>
      <th title="{$mod->Lang('title_moduleversion')}">{$mod->Lang('version')}</th>
      <th title="{$mod->Lang('title_modulestatus')}">{$mod->Lang('status')}</th>
      <th title="{$mod->Lang('title_moduleaction')}">{$mod->Lang('action')}</th>
      <th class="pageicon" title="{$mod->Lang('title_moduleactive')}" style="text-align:center;">{$mod->Lang('active')}</th>
      <th class="pageicon" title="{$mod->Lang('title_modulehelp')}"></th>
      <th class="pageicon" title="{$mod->Lang('title_moduleabout')}"></th>
      {if $allow_export}<th class="pageicon" title="{$mod->Lang('title_moduleexport')}">{$mod->Lang('export')}</th>{/if}
    </tr>
  </thead>
  <tbody>
    {foreach $module_info as $item}
      <tr class="{cycle values='row1,row2'}" id="_{$item.name}">
        <td>{strip}
          {if $item.system_module}{$system_img}{/if}
          {if $item.e_status == 'newer_available'} {$star_img}{/if}
          {if $item.missing_deps} {$missingdeps_img}{/if}
          {if $item.deprecated} {$deprecated_img}{/if}
{/strip}</td>
        <td>{strip}
          {if !$item.installed || $item.e_status == 'need_upgrade'}
            <span class="important"{if $item.description} title="{$item.description}"{/if}>{$item.name}</span>
          {elseif $item.notavailable}
            <span class="red"{if $item.description} title="{$item.description}"{/if}>{$item.name}</span>
          {elseif $item.deprecated}
            <span class="orange"{if $item.description} title="{$item.description}"{/if}>{$item.name}</span>
          {else}
            <span{if $item.system_module} class="green"{/if}{if $item.description} title="{$item.description}"{/if}>{$item.name}</span>
          {/if}
{/strip}</td>
          {if $item.e_status == 'newer_available'}
            <td class="important" title="{$mod->Lang('title_newer_available')}">{strip}
          {else}
            <td>{strip}
          {/if}
          {$item.installed_version}
{/strip}</td>
        <td>
{strip}{status}{/strip}
        </td>
        <td>
{strip}{actions}{/strip}
        </td>
        <td style="text-align:center;">{strip}{* active column *}
          {if ($item.installed && $item.active)}
            {if $item.can_deactivate}
              <a href="{cms_action_url action='local_active' mod=$item.name state=0}" class="modop mod_inactive">{$it}</a>
            {else}
              {admin_icon icon='true.gif' alt='active' title="{$mod->Lang('active')}"}
            {/if}
          {elseif $item.installed}
            <a href="{cms_action_url action='local_active' mod=$item.name state=1}" class="modop mod_active">{$if}</a>
          {/if}
{/strip}</td>
        <td>
          <a href="{cms_action_url action='local_help' mod=$item.name}" class="modop mod_help">{$ih}</a>
        </td>
        <td>
          <a href="{cms_action_url action='local_about' mod=$item.name}" class="modop mod_about">{$ia}</a>
        </td>
        {if $allow_export}<td style="text-align:center;">{strip}
          <a href="{cms_action_url action='local_export' mod=$item.name}" class="modop mod_export">{$exporticon}</a>
{/strip}</td>{/if}
      </tr>
    {/foreach}
  </tbody>
</table>
{else}
  <div class="pageerror">{$mod->Lang('error_nomodules')}</div>
{/if}
<div id="importdlg" title="{$mod->Lang('importxml')}" style="display:none;">
 {form_start id='local_import' action='local_import'}
  <div class="pageoverflow">
    <p class="pagetext">
      <label for="xml_upload">{$mod->Lang('uploadfile')}:</label>
       {cms_help key='help_mm_importxml' title=$mod->Lang('title_mm_importxml')}
    </p>
    <p class="pageinput">
      <input id="xml_upload" type="file" name="{$actionid}upload" accept="text/xml"/>
    </p>
  </div>
 </form>
</div>
