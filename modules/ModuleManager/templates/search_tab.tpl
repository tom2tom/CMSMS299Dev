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

{$formstart}
<fieldset>
<legend>{_ld($_module,'search_input')}:</legend>
<div class="pageoverflow">
  <label class="pagetext" for="searchterm">{_ld($_module,'searchterm')}:</label>
  <div class="pageinput">
    <input id="searchterm" type="text" name="{$actionid}term" size="50" value="{$term}" title="{_ld($_module,'title_searchterm')}" placeholder="{_ld($_module,'entersearchterm')}">&nbsp;
    <input type="hidden" name="{$actionid}advanced" value="0">
    <input type="checkbox" id="advanced" name="{$actionid}advanced" value="1"{if $advanced} checked{/if} title="{_ld($_module,'title_advancedsearch')}">&nbsp;<label for="advanced">{_ld($_module,'prompt_advancedsearch')}</label>
    <span id="advhelp" style="display: none;"><br>{_ld($_module,'advancedsearch_help')}</span>
  </div>
</div>
<div class="pageinput pregap">
  <button type="submit" name="{$actionid}submit" class="adminsubmit icon search">{_ld($_module,'search')}</button>
</div>
</fieldset>
</form>

{if isset($search_data)}
<fieldset>
  <legend>{_ld($_module,'search_results')}:</legend>
  <table class="pagetable scrollable">
    <thead>
      <tr>
        <th></th>
        <th>{_ld($_module,'nametext')}</th>
        <th><span title="{_ld($_module,'title_modulelastversion')}">{_ld($_module,'version')}</span></th>
        <th><span title="{_ld($_module,'title_modulelastreleasedate')}">{_ld($_module,'releasedate')}</span></th>
        <th><span title="{_ld($_module,'title_moduletotaldownloads')}">{_ld($_module,'downloads')}</span></th>
        <th><span title="{_ld($_module,'title_modulestatus')}">{_ld($_module,'statustext')}</span></th>
        <th>&nbsp;</th>
        <th>&nbsp;</th>
        <th>&nbsp;</th>
      </tr>
    </thead>
    <tbody>
      {foreach $search_data as $entry}
      <tr class="{cycle values='row1,row2'}"{if $entry->age=='new'} style="font-weight:bold;"{/if}>
        <td>{get_module_status_icon status=$entry->age}</td>
        <td>{if $entry->description}<span title="{$entry->description|cms_escape}">{/if}{$entry->name}{if $entry->description}</span>{/if}</td>
        <td>{$entry->version}</td>
        <td>{$entry->date|cms_date_format}</td>
        <td>{$entry->downloads}</td>
        <td>
         {if $entry->candownload}
          <span title="{_ld($_module,'title_moduleinstallupgrade')}">{$entry->status}</span>
         {else}
          {$entry->status}
         {/if}
        </td>
        <td><a href="{$entry->depends_url}" title="{_ld($_module,'title_moduledepends')}">{_ld($_module,'dependstxt')}</a></td>
        <td><a href="{$entry->help_url}" title="{_ld($_module,'title_modulehelp')}">{_ld($_module,'helptxt')}</a></td>
        <td><a href="{$entry->about_url}" title="{_ld($_module,'title_moduleabout')}">{_ld($_module,'abouttxt')}</a></td>
      </tr>
      {/foreach}
    </tbody>
  </table>
</fieldset>
{/if}
