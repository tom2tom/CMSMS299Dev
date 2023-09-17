{if empty($upcount)}
<p>{$nvmessage}</p>
{else}
<div class="pageinfo">{if !empty($updatestxt)}{$updatestxt}{else}{_ld($_module,'info_searchtab')}{/if}</div>

{if !empty($updates)}
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
      <th>{_ld($_module,'nametext')}</th>
      <th title="{_ld($_module,'title_newmoduleversion')}">{_ld($_module,'version')}</th>
      <th title="{_ld($_module,'title_yourmoduledate')}">{_ld($_module,'releasedate')}</th>
      <th title="{_ld($_module,'title_moduledownloads2')}">{_ld($_module,'downloads')}</th>
      <th title="{_ld($_module,'title_modulesize2')}">{_ld($_module,'sizetext')}</th>
      <th title="{_ld($_module,'title_yourmoduleversion')}">{_ld($_module,'yourversion')}</th>
      <th title="{_ld($_module,'title_modulestatus')}">{_ld($_module,'statustext')}</th>
      <th>&nbsp;</th>
      <th>&nbsp;</th>
      <th>&nbsp;</th>
    </tr>
  </thead>
  <tbody>
{foreach $updates as $entry}
  <tr class="{cycle values='row1,row2'}"{if $entry->age=='new'} style="font-weight:bold"{/if}>
    <td>{get_module_status_icon status=$entry->age}</td>
    <td>
     {if $entry->description}<span title="{$entry->description|cms_escape}">{/if}{$entry->name}{if $entry->description}</span>{/if}
     {if $entry->error}<br><span style="color:red">{$entry->error}</span>{/if}
    </td>
    <td>{$entry->version|default:''}</td>
    <td>{$entry->date|cms_date_format}</td>
    <td>{$entry->downloads}</td>
    <td>{$entry->size|default:''}</td>
    <td>{if isset($entry->haveversion)}{$entry->haveversion}{/if}</td>
    <td>{$entry->status|default:''}</td>
    <td><a href="{$entry->depends_url}" title="{_ld($_module,'title_moduledepends')}">{_ld($_module,'dependstxt')}</a></td>
    <td><a href="{$entry->help_url}" title="{_ld($_module,'title_modulehelp')}">{_ld($_module,'helptxt')}</a></td>
    <td><a href="{$entry->about_url}" title="{_ld($_module,'title_moduleabout')}">{_ld($_module,'abouttxt')}</a></td>
  </tr>
{/foreach}
  </tbody>
</table>
{/if}
{/if}
