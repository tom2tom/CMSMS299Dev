{if isset($module_info)}
<div class="pageoptions">
  <a id="importbtn">{admin_icon icon='import.gif'} {$mod->Lang('importxml')}</a>
</div>

{$ih={admin_icon icon='info.gif' alt='help' title="{$mod->Lang('title_modulehelp')}"}}
{$ia={admin_icon icon='icons/extra/info.gif' alt='about' title="{$mod->Lang('title_moduleabout')}"}}
<table class="pagetable">
  <thead>
    <tr>
      <th></th>
      <th>{$mod->Lang('nametext')}</th>
      <th title="{$mod->Lang('title_moduleversion')}">{$mod->Lang('vertext')}</th>
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
        <td>
          {if $item.system_module}{$system_img}{/if}
          {if $item.e_status == 'newer_available'} {$star_img}{/if}
          {if $item.missing_deps || $item.notavailable} {$missingdeps_img}{/if}
          {if $item.deprecated} {$deprecated_img}{/if}
        </td>
        <td>
          {if !$item.installed || $item.e_status == 'need_upgrade'}
            <span title="{$item.description}" class="important">{$item.name}</span>
          {elseif $item.notavailable}
            <span title="{$item.description}" class="red">{$item.name}</span>
          {elseif $item.deprecated}
            <span title="{$item.description}" class="orange">{$item.name}</span>
          {else}
            <span title="{$item.description}"{if $item.system_module} class="green"{/if}>{$item.name}</span>
          {/if}
        </td>
        <td>
          {if $item.e_status == 'newer_available'}
            <strong title="{$mod->Lang('status_newer_available')}">{$item.installed_version}</strong>
          {else}
            {$item.installed_version}
          {/if}
        </td>
        <td>{* status column *}{$ops=[]}
          {if $item.notavailable}
           {capture assign='op'}<strong title="{$mod->Lang('title_notavailable')}" class="red">{$mod->Lang('notavailable')}</strong>{/capture}{$ops[]=$op}
          {elseif !$item.installed}
           {if $item.can_install}
             {capture assign='op'}<strong title="{$mod->Lang('title_notinstalled')}">{$mod->Lang('notinstalled')}</strong>{/capture}{$ops[]=$op}
           {else if $item.missing_deps}
             {capture assign='op'}<a class="modop mod_missingdeps important" class="red" title="{$mod->Lang('title_missingdeps')}" href="{cms_action_url action='local_missingdeps' mod=$item.name}">{$mod->Lang('missingdeps')}</a>{/capture}{$ops[]=$op}
           {/if}
          {else}
           {capture assign='op'}{$tmp='status_'|cat:$item.status}<span title="{$mod->Lang($tmp)}">{$mod->Lang($item.status)}</span>{/capture}{$ops[]=$op}
           {if $item.missing_deps}
             {capture assign='op'}<a class="modop mod_missingdeps important" class="red" title="{$mod->Lang('title_missingdeps')}" href="{cms_action_url action='local_missingdeps' mod=$item.name}">{$mod->Lang('missingdeps')}</a>{/capture}{$ops[]=$op}
           {/if}
           {if !$item.can_uninstall}
             {capture assign='op'}<span title="{$mod->Lang('title_cantuninstall')}">{$mod->Lang('cantuninstall')}</span>{/capture}{$ops[]=$op}
           {/if}
          {/if}
          {if isset($item.e_status)}
            {capture assign='op'}{$tmp='status_'|cat:$item.e_status}<span {if $item.e_status == 'db_newer'}class="important"{/if} title="{$mod->Lang($tmp)}" class="orange">{$mod->Lang($item.e_status)}</span>{/capture}{$ops[]=$op}
          {/if}
          {if !$item.ver_compatible}
            {capture assign='op'}<span class="important" title="{$mod->Lang('title_notcompatible')}">{$mod->Lang('notcompatible')}</span>{/capture}{$ops[]=$op}
          {/if}
          {if !$item.system_module && !$item.writable}
            {capture assign='op'}<span title="{$mod->Lang('title_cantremove')}">{$mod->Lang('cantremove')}</span>{/capture}{$ops[]=$op}
          {/if}
          {if isset($item.dependants)}{$tmp=[]}
            {foreach $item.dependants as $one}
              {$tmp[]="<a href=\"{cms_action_url}#_{$one}\">{$one}</a>"}
            {/foreach}
            {capture assign='op'}<span title="{$mod->Lang('title_depends_upon')}">{$mod->Lang('depends_upon')}</span>: {implode(', ',$tmp)}{/capture}{$ops[]=$op}
          {/if}
          {'<br />'|implode:$ops}
        </td>
        <td>{* action column *}{$ops=[]}
          {if !$item.installed}
            {if $item.can_install}
              {capture assign='op'}<a class="modop mod_install" href="{cms_action_url action='local_install' mod=$item.name}" title="{$mod->Lang('title_install')}">{$mod->Lang('install')}</a>{/capture}{$ops[]=$op}
            {/if}
            {if !$item.system_module}{if $item.writable}
              {capture assign='op'}<a class="modop mod_remove" href="{cms_action_url action='local_remove' mod=$item.name}" title="{$mod->Lang('title_remove')}">{$mod->Lang('remove')}</a>{/capture}{$ops[]=$op}
            {else}
              {capture assign='op'}<a class="modop mod_chmod" href="{cms_action_url action='local_chmod' mod=$item.name}" title="{$mod->Lang('title_chmod')}">{$mod->Lang('changeperms')}</a>{/capture}{$ops[]=$op}
            {/if}{/if}
          {else}
            {if $item.e_status == 'need_upgrade' }
              {capture assign='op'}<a class="modop mod_upgrade" href="{cms_action_url action='local_upgrade' mod=$item.name}" title="{$mod->Lang('title_upgrade')}">{$mod->Lang('upgrade')}</a>{/capture}{$ops[]=$op}
            {/if}
            {if $item.can_uninstall}{if $item.name != 'ModuleManager' || $allow_modman_uninstall}
              {capture assign='op'}<a class="modop mod_uninstall" href="{cms_action_url action='local_uninstall' mod=$item.name}" title="{$mod->Lang('title_uninstall')}">{$mod->Lang('uninstall')}</a>{/capture}{$ops[]=$op}
            {/if}{/if}
          {/if}
          {'<br />'|implode:$ops}
        </td>
        <td style="text-align:center;">{* active column *}
          {if $item.can_uninstall}{if $item.active}
            <a class="modop mod_inactive" href="{cms_action_url action='local_active' mod=$item.name state=0}" title="{$mod->Lang('toggle_inactive')}">{admin_icon icon='true.gif'}</a>
          {else}
            <a class="modop mod_active" href="{cms_action_url action='local_active' mod=$item.name state=1}" title="{$mod->Lang('toggle_active')}">{admin_icon icon='false.gif'}</a>
          {/if}{/if}
        </td>
        <td>
          <a class="modop mod_help" href="{cms_action_url action='local_help' mod=$item.name}">{$ih}</a>
        </td>
        <td>
          <a class="modop mod_about" href="{cms_action_url action='local_about' mod=$item.name}">{$ia}</a>
        </td>
        {if $allow_export}<td style="text-align:center;">
          {if $item.active && $item.root_writable && $item.e_status != 'need_upgrade' && !$item.missing_deps}
          <a class="modop mod_export" href="{cms_action_url action='local_export' mod=$item.name}">{$exporticon}</a>
          {/if}
        </td>{/if}
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
