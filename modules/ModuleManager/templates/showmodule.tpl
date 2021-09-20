{if isset($header)}
<h3>{$header}</h3>
{/if}

{if isset($letters)}
<p class="pagerows">{$letters}</p>
{/if}
<div style="clear:both;"></div>

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

{if $itemcount > 0}
<table class="pagetable scrollable">
  <thead>
    <tr>
      <th></th>
      <th>{$nametext}</th>
      <th title="{_ld($_module,'title_modulelastversion')}">{$vertext}</th>
      <th title="{_ld($_module,'title_modulereleasedate')}">{_ld($_module,'releasedate')}</th>
      <th title="{_ld($_module,'title_moduledownloads')}">{_ld($_module,'downloads')}</th>
      <th>{$sizetext}</th>
      <th>{$statustext}</th>
      <th>&nbsp;</th>
      <th>&nbsp;</th>
      <th>&nbsp;</th>
    </tr>
  </thead>
  <tbody>
{foreach $items as $entry}
   <tr class="{cycle values='row1,row2'}"{if $entry->age=='new'} style="font-weight:bold;"{/if}>
     <td>{get_module_status_icon status=$entry->age}</td>
     <td>{if $entry->description}<span title="{$entry->description|cms_escape}">{/if}{$entry->name}{if $entry->description}</span>{/if}</td>
     <td>{$entry->version}</td>
     <td>{$entry->date|cms_date_format}</td>
     <td>{$entry->downloads}</td>
     <td>{$entry->size}</td>
     <td>{$entry->status}</td>
     <td title="{_ld($_module,'title_modulereleasedepends')}">{$entry->dependslink}</td>
     <td title="{_ld($_module,'title_modulereleasehelp')}">{$entry->helplink}</td>
     <td title="{_ld($_module,'title_modulereleaseabout')}">{$entry->aboutlink}</td>
   </tr>
{/foreach}
  </tbody>
</table>
{/if}
