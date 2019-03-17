{tab_header name='content' label=lang('sysmaintab_content') active=isset($active_content)}
{tab_header name='cache' label=lang('sysmaintab_cache') active=isset($active_cache)}
{tab_header name='database' label=lang('sysmaintab_database') active=isset($active_database)}
{if isset($changelog)}
{tab_header name='changelog' label=lang('sysmaintab_changelog') active=isset($active_changelog)}
{/if}
{tab_start name='content'}
  {lang('sysmain_pagesfound', {$pagecount})}

  {if $invalidtypescount == 0 && $withoutaliascount == 0}
  <p class='green'><strong>{lang('sysmain_nocontenterrors')}</strong></p>
  {/if}
  {if $invalidtypescount > 0}
  <form action="{$selfurl}{$urlext}" method="post" onsubmit="confirmsubmit(this,'{lang("sysmain_confirmfixtypes")|escape:"javascript"}');return false;">
    <div class="pageoverflow">
      <p class="pagetext">{lang('sysmain_pagesinvalidtypes',{$invalidtypescount})}:</p>
      <p class="pageinput">
        {foreach $pageswithinvalidtype as $page} {$page.content_name} <em>({$page.content_alias}) - {$page.type}</em><br />{/foreach}
        <button type="submit" name="fixtypes" class="pregap adminsubmit icon do">{lang('sysmain_fixtypes')}</button>
      </p>
    </div>
  </form>
  {/if}
  {if $withoutaliascount > 0}
  <form action="{$selfurl}{$urlext}" method="post" onsubmit="confirmsubmit(this,'{lang("sysmain_confirmfixaliases")|escape:"javascript"}');return false;">
    <div class="pageoverflow">
      <p class="pagetext">{lang('sysmain_pagesmissinalias',{$withoutaliascount})}:</p>
      <p class="pageinput">
        {foreach $pagesmissingalias as $page} {$page}<br /> {/foreach}
        <button type="submit" name="addaliases" class="pregap adminsubmit icon do">{lang('sysmain_fixaliases')}</button>
      </p>
    </div>
  </form>
  {/if}

  <form action="{$selfurl}{$urlext}" method="post" onsubmit="confirmsubmit(this,'{lang("sysmain_confirmupdatehierarchy")|escape:"javascript"}');return false;">
    <div class="pageoverflow">
      <p class="pageinput pregap">
        <button type="submit" name="updatehierarchy" class="adminsubmit icon do" title="{lang('sysmain_tipupdatehierarchy')}">{lang('sysmain_updatehierarchy')}</button>
      </p>
    </div>
  </form>
  <form action="{$selfurl}{$urlext}" method="post" onsubmit="confirmsubmit(this,'{lang("sysmain_confirmupdateroutes")|escape:"javascript"}');return false;">
    <div class="pageoverflow">
      <p class="pageinput pregap">
        <button type="submit" name="updateroutes" class="adminsubmit icon do" title="{lang('sysmain_tipupdateroutes')}">{lang('sysmain_updateroutes')}</button>
      </p>
    </div>
  </form>

{if !empty($export)}
  <form action="{$selfurl}{$urlext}" method="post">
  <p class="pageinput pregap">
    <button type="submit" name="export" class="adminsubmit icon do" title="{lang('exportsite_tip')}">{lang('exportsite')}</button>
  </p>
  </form>
{/if}

{tab_start name='cache'}
  {if isset($cachetype)}{lang('sysmain_cache_type',{$cachetype})}{/if}
  <form action="{$selfurl}{$urlext}" method="post">
    <div class="pageoverflow">
      <p class="pageinput pregap">
        <button type="submit" name="clearcache" class="adminsubmit icon do">{lang('clearcache')}</button>
      </p>
    </div>
  </form>

{tab_start name='database'}
<form action="{$selfurl}{$urlext}" method="post">
    <p>{lang('sysmain_tablesfound',$tablecount,$nonseqcount)}</p>
    {if $errorcount==0}
    <p class='green'><strong>{lang('sysmain_nostr_errors')}</strong></p>
    {else}
    <p class='red'><strong>{if $errorcount>1}{lang('sysmain_str_errors',{$errorcount})}{else}{lang('sysmain_str_error',{$errorcount})}{/if}: {$errortables}</strong></p>
    {/if}

    <div class="pageoverflow">
      <p class="pageinput pregap">
        <button type="submit" name="optimizeall" class="adminsubmit icon do" title="{lang('sysmain_tipoptimizetables')}">{lang('sysmain_optimizetables')}</button>
      </p>
    </div>
    <div class="pageoverflow">
      <p class="pageinput pregap">
        <button type="submit" name="repairall" class="adminsubmit icon do" title="{lang('sysmain_tiprepairtables')}">{lang('sysmain_repairtables')}</button>
      </p>
    </div>
</form>

{if isset($changelog)}
{tab_start name='changelog'}
  <p class='file'>{$changelogfilename}</p>
  <div class="changelog">{$changelog}</div>
{/if}
{tab_end}
