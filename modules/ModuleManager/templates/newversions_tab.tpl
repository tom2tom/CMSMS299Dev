{if empty($upcount)}
<p>{$nvmessage}</p>
{else}
<div class="pageinfo">{if !empty($updatestxt)}{$updatestxt}{else}{$mod->Lang('info_searchtab')}{/if}</div>

{function get_module_status_icon}
{strip}
{if $status == 'stale'}
{$stale_img}
{elseif $status == 'warn'}
{$warning_img}
{elseif $status == 'new'}
{$new_img}
{/if}
{/strip}
{/function}

<table class="pagetable scrollable">
  <thead>
    <tr>
      <th></th>
      <th>{$mod->Lang('nametext')}</th>
      <th title="{$mod->Lang('title_newmoduleversion')}">{$mod->Lang('version')}</th>
      <th title="{$mod->Lang('title_yourmoduledate')}">{$mod->Lang('releasedate')}</th>
      <th title="{$mod->Lang('title_moduledownloads2')}">{$mod->Lang('downloads')}</th>
      <th title="{$mod->Lang('title_modulesize2')}">{$mod->Lang('sizetext')}</th>
      <th title="{$mod->Lang('title_yourmoduleversion')}">{$mod->Lang('yourversion')}</th>
      <th title="{$mod->Lang('title_modulestatus')}">{$mod->Lang('statustext')}</th>
      <th>&nbsp;</th>
      <th>&nbsp;</th>
      <th>&nbsp;</th>
    </tr>
  </thead>
  <tbody>
{foreach $updates as $entry}
  <tr class="{cycle values='row1,row2'}"{if $entry->age=='new'} style="font-weight:bold;"{/if}>
    <td>{get_module_status_icon status=$entry->age}</td>
    <td>
     {if $entry->description}<span title="{$entry->description|cms_escape}">{/if}{$entry->name}{if $entry->description}</span>{/if}
     {if $entry->error}<br /><span style="color:red;">{$entry->error}</span>{/if}
    </td>
    <td>{$entry->version|default:''}</td>
    <td>{$entry->date|cms_date_format}</td>
    <td>{$entry->downloads}</td>
    <td>{$entry->size|default:''}</td>
    <td>{if isset($entry->haveversion)}{$entry->haveversion}{/if}</td>
    <td>{$entry->status|default:''}</td>
    <td><a href="{$entry->depends_url}" title="{$mod->Lang('title_moduledepends')}">{$mod->Lang('dependstxt')}</a></td>
    <td><a href="{$entry->help_url}" title="{$mod->Lang('title_modulehelp')}">{$mod->Lang('helptxt')}</a></td>
    <td><a href="{$entry->about_url}" title="{$mod->Lang('title_moduleabout')}">{$mod->Lang('abouttxt')}</a></td>
  </tr>
{/foreach}
  </tbody>
</table>
{/if}
