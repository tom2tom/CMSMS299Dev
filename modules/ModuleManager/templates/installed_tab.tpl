{function status}
  {$stats = []}
  {if !$item.installed}
    {if $item.can_install}
      {capture append=stats}<span title="{_ld($_module,'caninstall')}">{_ld($_module,'notinstalled')}</span>{/capture}
    {else if $item.missing_deps}
      {capture append=stats}<span class="important red" title="{_ld($_module,'title_missingdeps')}">{_ld($_module,'missingdeps')}</span>{/capture}
    {/if}
  {elseif $item.notavailable}
    {capture append=stats}<span class="important red" title="{_ld($_module,'title_notavailable')}">{_ld($_module,'notavailable')}</span>{/capture}
  {else}
    {if $item.e_status}
      {capture append=stats}<span class="{if $item.e_status == 'db_newer'}important {/if}orange"{if $item.title_key} title="{_ld($_module,{$item.title_key})}"{/if}>{_ld($_module,{$item.main_key})}</span>{/capture}
    {else}
      {capture append=stats}{_ld($_module,{$item.main_key})}{/capture}
    {/if}
    {if $item.missing_deps}
      {capture append=stats}<span class="important red" title="{_ld($_module,'title_missingdeps')}">{_ld($_module,'missingdeps')}</span>{/capture}
    {/if}
    {if !($item.bundled || $item.can_uninstall)}
      {capture append=stats}<span title="{_ld($_module,'title_cantuninstall')}">{_ld($_module,'cantuninstall')}</span>{/capture}
    {/if}
  {/if}
  {if !$item.ver_compatible}
    {capture append=stats}<span class="important" title="{_ld($_module,'title_notcompatible')}">{_ld($_module,'notcompatible')}</span>{/capture}
  {/if}
  {if !($item.bundled || $item.installed || $item.writable)}
    {capture append=stats}<span title="{_ld($_module,'title_cantremove')}">{_ld($_module,'cantremove')}</span>{/capture}
  {/if}
  {if !empty($item.dependents)}{$deps = []}
    {foreach $item.dependents as $one}
      {capture append=deps}<strong>{$one}</strong>{/capture}
    {/foreach}
    {capture append=stats}<span title="{_ld($_module,'title_depends_upon')}">{_ld($_module,'depends_upon')}</span>: {', '|implode:$deps}{/capture}
  {/if}
{"<br />\n"|implode:$stats}
{/function}

{function actions}
  {$acts = []}
  {if $item.installed}
    {if $item.e_status == 'need_upgrade' }
      {capture append=acts}<a href="{cms_action_url action='local_upgrade' mod=$item.name}" class="modop mod_upgrade" title="{_ld($_module,'title_upgrade')}">{_ld($_module,'upgrade')}</a>{/capture}
    {/if}
    {if $item.can_uninstall}
      {capture append=acts}<a href="{cms_action_url action='local_uninstall' mod=$item.name}" class="modop mod_uninstall" title="{_ld($_module,'title_uninstall')}">{_ld($_module,'uninstall')}</a>{/capture}
    {/if}
  {else}
    {if $item.can_install}
      {capture append=acts}<a href="{cms_action_url action='local_install' mod=$item.name}" class="modop mod_install" title="{_ld($_module,'title_install')}">{_ld($_module,'install')}</a>{/capture}
    {elseif $item.missing_deps}
      {capture append=acts}<a href="{cms_action_url action='local_missingdeps' mod=$item.name}" class="modop mod_missingdeps" title="{_ld($_module,'title_missingdeps')}">{_ld($_module,'missingdeps')}</a>{/capture}
    {/if}
    {if !$item.bundled}{if $item.writable}
      {capture append=acts}<a href="{cms_action_url action='local_remove' mod=$item.name}" class="modop mod_remove" title="{_ld($_module,'title_remove')}">{_ld($_module,'remove')}</a>{/capture}
    {else}
      {capture append=acts}<a href="{cms_action_url action='local_chmod' mod=$item.name}" class="modop mod_chmod" title="{_ld($_module,'title_chmod')}">{_ld($_module,'changeperms')}</a>{/capture}
    {/if}{/if}
  {/if}
{"<br />\n"|implode:$acts}
{/function}

{if !empty($module_info)}
{$it = {admin_icon icon='true.gif' alt='active' title="{_ld($_module,'title_active')}"}}
{$if = {admin_icon icon='false.gif' alt='inactive' title="{_ld($_module,'title_inactive')}"}}
{$ih = {admin_icon icon='info.gif' alt='help' title="{_ld($_module,'title_modulehelp')}"}}
{$ia = {admin_icon icon='icons/extra/info.gif' alt='about' title="{_ld($_module,'title_moduleabout')}"}}
<div class="pageoptions">
  <a id="importbtn">{admin_icon icon='import.gif'} {_ld($_module,'importxml')}</a>
</div>
<table class="pagetable">
  <thead>
    <tr>
      <th>{_ld($_module,'nametext')}</th>
      <th title="{_ld($_module,'title_moduleversion')}">{_ld($_module,'version')}</th>
      <th></th>
      <th title="{_ld($_module,'title_modulestatus')}">{_ld($_module,'status')}</th>
      <th title="{_ld($_module,'title_moduleaction')}">{_ld($_module,'action')}</th>
      <th class="pageicon" title="{_ld($_module,'title_moduleactive')}" style="text-align:center;">{_ld($_module,'active')}</th>
      <th class="pageicon" title="{_ld($_module,'title_modulehelp')}"></th>
      <th class="pageicon" title="{_ld($_module,'title_moduleabout')}"></th>
      {if $allow_export}<th class="pageicon" title="{_ld($_module,'title_moduleexport')}">{_ld($_module,'export')}</th>{/if}
    </tr>
  </thead>
  <tbody>
    {foreach $module_info as $item}
      <tr class="{cycle values='row1,row2'}" id="_{$item.name}">
        <td>{strip}
          {if !$item.installed || $item.e_status == 'need_upgrade'}
            <span class="important"{if $item.description} title="{$item.description}"{/if}>{$item.name}</span>
          {elseif $item.notavailable}
            <span class="red"{if $item.description} title="{$item.description}"{/if}>{$item.name}</span>
          {elseif $item.deprecated}
            <span class="orange"{if $item.description} title="{$item.description}"{/if}>{$item.name}</span>
          {else}
            <span{if $item.description} title="{$item.description}"{/if}>{$item.name}</span>
          {/if}
{/strip}</td>
          {if $item.e_status == 'newer_available'}
            <td class="important" title="{_ld($_module,'title_newer_available')}">{strip}
          {else}
            <td>{strip}
          {/if}
          {$item.installed_version}
{/strip}</td>
        <td>{strip}
          {if $item.bundled}{$bundled_img}{/if}
          {if $item.missing_deps}{$missingdeps_img}{/if}
          {if $item.e_status == 'newer_available'}{$upgradeable_img}{/if}
          {if $item.stale_upgrade}{$staleupgrade_img}
          {elseif $item.fresh_upgrade}{$freshupgrade_img}{/if}
{*        {elseif $item.notinforge}{$noforge_img}{/if *}
          {if $item.stagnant}{$stagnant_img}{/if}
          {if $item.deprecated}{$deprecated_img}{/if}
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
              {admin_icon icon='true.gif' alt='active' title="{_ld($_module,'active')}"}
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
  <div class="pageerror">{_ld($_module,'error_nomodules')}</div>
{/if}
<div id="importdlg" title="{_ld($_module,'importxml')}" style="display:none;">
 {form_start id='local_import' action='local_import'}
  <div class="pageoverflow">
    <label class="pagetext" for="xml_upload">{_ld($_module,'uploadfile')}:</label>
    {cms_help 0=$_module key='help_mm_importxml' title=_ld($_module,'title_mm_importxml')}
    <div class="pageinput">
      <input id="xml_upload" type="file" name="{$actionid}upload" accept="text/xml" />
    </div>
  </div>
 </form>
</div>
