{if isset($header)}
<h3>{$header}</h3>
{/if}

<div class="pagewarn">
  {_ld($_module,'general_notice')} {_ld($_module,'compatibility_disclaimer')}
  <h3>{_ld($_module,'use_at_your_own_risk')}</h3>
</div>

<p class="pagerows">
{foreach $letter_urls as $key => $url}
  {if $key == $curletter}
  <span class="current">{$key}</span>&nbsp;
  {else}
  <a href="{$url}" title="{_ld($_module,'title_letter',$key)}">{$key}</a>&nbsp;
  {/if}
{/foreach}
</p>

{if isset($message) && $message}
<div class="pageinfo">{$message}</div>
{/if}

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

{if !empty($items)}
{$ih={admin_icon icon='info.gif' alt='help' title="{_ld($_module,'title_modulehelp')}"}}
{$ia={admin_icon icon='icons/extra/info.gif' alt='about' title="{_ld($_module,'title_moduleabout')}"}}
<table class="pagetable scrollable">
  <thead>
    <tr>
      <th></th>
      <th>{$nametext}</th>
      <th><span title="{_ld($_module,'title_modulelastversion')}">{$vertext}</span></th>
      <th><span title="{_ld($_module,'title_modulelastreleasedate')}">{_ld($_module,'releasedate')}</span></th>
      <th><span title="{_ld($_module,'title_moduletotaldownloads')}">{_ld($_module,'downloads')}</span></th>
      <th>&nbsp;</th>
      <th>&nbsp;</th>
      <th class="pageicon" title="{_ld($_module,'title_modulehelp')}"></th>
      <th class="pageicon" title="{_ld($_module,'title_moduleabout')}"></th>
    </tr>
  </thead>
  <tbody>
  {foreach $items as $entry}
      <tr class="{cycle values='row1,row2'}"{if $entry->age=='new'} style="font-weight:bold"{/if}>
      <td>{get_module_status_icon status=$entry->age}</td>
      <td>{if $entry->description}<span title="{$entry->description|cms_escape}">{/if}{$entry->name}{if $entry->description}</span>{/if}</td>
      <td>{$entry->version}</td>
      <td>{$entry->date|cms_date_format}</td>
      <td>{$entry->downloads}</td>
      <td>{if $entry->candownload}
        <span title="{_ld($_module,'title_moduleinstallupgrade')}">{$entry->status}</span>
        {else}
        {$entry->status}
        {/if}
      </td>
      <td><a href="{$entry->depends_url}" title="{_ld($_module,'title_moduledepends')}">{_ld($_module,'dependstxt')}</a></td>
      <td><a href="{$entry->help_url}">{$ih}</a></td>
      <td><a href="{$entry->about_url}"></a>{$ia}</td>
    </tr>
  {/foreach}
  </tbody>
</table>
{/if}