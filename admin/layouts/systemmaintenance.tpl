{tab_header name='content' label=_la('sysmaintab_content') active=isset($active_content)}
{tab_header name='database' label=_la('sysmaintab_database') active=isset($active_database)}
{tab_header name='cache' label=_la('sysmaintab_cache') active=isset($active_cache)}
{if isset($changelog)}
{tab_header name='changelog' label=_la('sysmaintab_changelog') active=isset($active_changelog)}
{/if}
{tab_start name='content'}
  {_la('sysmain_pagesfound', {$pagecount})}

  {if $invalidtypescount == 0 && $withoutaliascount == 0}
  <p class="green"><strong>{_la('sysmain_nocontenterrors')}</strong></p>
  {/if}
  {if $invalidtypescount > 0}
  <form action="{$selfurl}" enctype="multipart/form-data" method="post" onsubmit="confirmsubmit(this,'{_la("sysmain_confirmfixtypes")|escape:"javascript"}');return false;">
    {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />
{/foreach}
    <div class="pageoverflow">
      <p class="pagetext">{_la('sysmain_pagesinvalidtypes',{$invalidtypescount})}:</p>
      <p class="pageinput">
        {foreach $pageswithinvalidtype as $page} {$page.content_name} <em>({$page.content_alias}) - {$page.type}</em><br />{/foreach}
        <button type="submit" name="fixtypes" class="pregap adminsubmit icon do">{_la('sysmain_fixtypes')}</button>
      </p>
    </div>
  </form>
  {/if}
  {if $withoutaliascount > 0}
  <form action="{$selfurl}" enctype="multipart/form-data" method="post" onsubmit="confirmsubmit(this,'{_la("sysmain_confirmfixaliases")|escape:"javascript"}');return false;">
    {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />
{/foreach}
    <div class="pageoverflow">
      <p class="pagetext">{_la('sysmain_pagesmissinalias',{$withoutaliascount})}:</p>
      <p class="pageinput">
        {foreach $pagesmissingalias as $page} {$page}<br /> {/foreach}
        <button type="submit" name="addaliases" class="pregap adminsubmit icon do">{_la('sysmain_fixaliases')}</button>
      </p>
    </div>
  </form>
  {/if}

  <form action="{$selfurl}" enctype="multipart/form-data" method="post" onsubmit="confirmsubmit(this,'{_la("sysmain_confirmupdatehierarchy")|escape:"javascript"}');return false;">
    {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />
{/foreach}
    <div class="pageoverflow">
      <p class="pageinput pregap">
        <button type="submit" name="updatehierarchy" class="adminsubmit icon do" title="{_la('sysmain_tipupdatehierarchy')}">{_la('sysmain_updatehierarchy')}</button>
      </p>
    </div>
  </form>
  <form action="{$selfurl}" enctype="multipart/form-data" method="post" onsubmit="confirmsubmit(this,'{_la("sysmain_confirmupdateroutes")|escape:"javascript"}');return false;">
    {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />
{/foreach}
    <div class="pageoverflow">
      <p class="pageinput pregap">
        <button type="submit" name="updateroutes" class="adminsubmit icon do" title="{_la('sysmain_tipupdateroutes')}">{_la('sysmain_updateroutes')}</button>
      </p>
    </div>
  </form>

{if !empty($export)}
  <form action="{$selfurl}" enctype="multipart/form-data" method="post">
    {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />
{/foreach}
  <p class="pageinput pregap">
    <button type="submit" name="export" class="adminsubmit icon do" title="{_la('exportsite_tip')}">{_la('exportsite')}</button>
  </p>
  </form>
{/if}

{tab_start name='database'}
<form action="{$selfurl}" enctype="multipart/form-data" method="post">
    {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />
{/foreach}
    <p>{_la('sysmain_tablesfound',$tablecount,$nonseqcount)}</p>
    {if $errorcount==0}
    <p class="green"><strong>{_la('sysmain_nostr_errors')}</strong></p>
    {else}
    <p class="red"><strong>
      {if $errorcount>1}
        {_la('sysmain_str_errors',{$errorcount})}:<br />
        {foreach $errortables as $val}{$val}{if $val@last}<br />{else},<br />{/if}
{/foreach}
      {else}
        {_la('sysmain_str_error',{$errorcount})}: {$errortables}
      {/if}
    </strong></p>
    {/if}

    <div class="pageoverflow">
      <p class="pageinput pregap">
        <button type="submit" name="optimizeall" class="adminsubmit icon do" title="{_la('sysmain_tipoptimizetables')}">{_la('sysmain_optimizetables')}</button>
      </p>
    </div>
    <div class="pageoverflow">
      <p class="pageinput pregap">
        <button type="submit" name="repairall" class="adminsubmit icon do" title="{_la('sysmain_tiprepairtables')}">{_la('sysmain_repairtables')}</button>
      </p>
    </div>
</form>

{tab_start name='cache'}
  {if isset($cachetype)}{_la('sysmain_cache_type',{$cachetype})}{/if}
  <form action="{$selfurl}" enctype="multipart/form-data" method="post">
    {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />
{/foreach}
    <div class="pageoverflow">
      <p class="pageinput pregap">
        <button type="submit" name="clearcache" class="adminsubmit icon do">{_la('clearcache')}</button>
      </p>
    </div>
  </form>

{if isset($changelog)}
{tab_start name='changelog'}
  <p class="file">{$changelogfilename}</p>
  <div class="changelog">{$changelog}</div>
{/if}
{tab_end}
