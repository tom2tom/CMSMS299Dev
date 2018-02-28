<div class="pagecontainer">

  {tab_header name='database' label=lang('sysmaintab_database') active=isset($active_database))}
  {tab_header name='content' label=lang('sysmaintab_content') active=isset($active_content)}
{if isset($changelog)}
  {tab_header name='changelog' label=lang('sysmaintab_changelog') active=isset($active_changelog)}
 {/if}
  {tab_start name='database'}
  <form action="{$selfurl}{$urlext}" method="post">
    <fieldset>
      <legend>{lang('sysmain_database_status')}:</legend>
      <p>{lang('sysmain_tablesfound',$tablecount,$nonseqcount)}</p>

      {if $errorcount==0}
      <p class='green'><strong>{lang('sysmain_nostr_errors')}</strong></p>
      {else}
      <p class='red'><strong>{$errorcount} {if $errorcount>1}{lang('sysmain_str_errors')}{else}{lang('sysmain_str_error')}{/if}: {$errortables}</strong></p>
      {/if}

      <div class="pageoverflow">
        <p class="pagetext">{lang('sysmain_optimizetables')}:</p>
        <p class="pageinput">
          <button type="submit" name="optimizeall" class="adminsubmit icondo">{lang('sysmain_optimize')}</button>
        </p>
      </div>
      <div class="pageoverflow">
        <p class="pagetext">{lang('sysmain_repairtables')}:</p>
        <p class="pageinput">
          <button type="submit" name="repairall" class="adminsubmit icondo">{lang('sysmain_repair')}</button>
        </p>
      </div>
    </fieldset>
  </form>
  {tab_start name='content'}
  <form action="{$selfurl}{$urlext}" method="post">
    <fieldset>
      <legend>{lang('sysmain_cache_status')}&nbsp;</legend>
      <div class="pageoverflow">
        <p class="pagetext">{lang('clearcache')}:</p>
        <p class="pageinput">
          <button type="submit" name="clearcache" class="adminsubmit icondo">{lang('clear')}</button>
        </p>
      </div>
    </fieldset>
  </form>

  <fieldset>
    <legend>{lang('sysmain_content_status')}&nbsp;</legend>
    <form action="{$selfurl}{$urlext}" method="post" onsubmit="return confirm('{lang('sysmain_confirmupdatehierarchy')|escape:'javascript'}');">
      {$pagecount} {lang('sysmain_pagesfound')}

      <div class="pageoverflow">
        <p class="pagetext">{lang('sysmain_updatehierarchy')}:</p>
        <p class="pageinput">
          <button type="submit" name="updatehierarchy" class="adminsubmit icondo">{lang('sysmain_update')}</button>
        </p>
      </div>
    </form>

    <form action="{$selfurl}{$urlext}" method="post" onsubmit="return confirm('{lang('sysmain_confirmupdateurls')|escape:'javascript'}');">
      <div class="pageoverflow">
        <p class="pagetext">{lang('sysmain_updateurls')}:</p>
        <p class="pageinput">
          <button type="submit" name="updateurls" class="adminsubmit icondo">{lang('sysmain_update')}</button>
        </p>
      </div>
    </form>

    {if $withoutaliascount!="0"}
    <form action="{$selfurl}{$urlext}" method="post" onsubmit="return confirm('{lang('sysmain_confirmfixaliases')|escape:'javascript'}');">
      <div class="pageoverflow">
        <p class="pagetext">{$withoutaliascount} {lang('sysmain_pagesmissinalias')}:</p>
        <p class="pageinput">
          {foreach $pagesmissingalias as $page} {*{$page.count}.*} {$page.content_name}<br /> {/foreach}
          <br />
          <button type="submit" name="addaliases" class="adminsubmit icondo">{lang('sysmain_fixaliases')}</button>
        </p>
      </div>
    </form>
    {/if} {if $invalidtypescount!="0"}
    <form action="{$selfurl}{$urlext}" method="post" onsubmit="return confirm('{lang('sysmain_confirmfixtypes')|escape:'javascript'}');">
      <div class="pageoverflow">
        <p class="pagetext">{$invalidtypescount} {lang('sysmain_pagesinvalidtypes')}:</p>
        <p class="pageinput">
          {foreach $pageswithinvalidtype as $page} {$page.content_name} <em>({$page.content_alias}) - {$page.type}</em><br /> {/foreach}
          <br />
          <button type="submit" name="fixtypes" class="adminsubmit icondo">{lang('sysmain_fixtypes')}<button>
        </p>
      </div>
    </form>
    {/if} {if $invalidtypescount=="0" && $withoutaliascount==""}
    <p class='green'><strong>{lang('sysmain_nocontenterrors')}</strong></p>
    {/if}
  </fieldset>

  {if isset($changelog)}
  {tab_start name='changelog'}
    <p class='file'>{$changelogfilename}</p>
    <div class="changelog">{$changelog}</div>
  {/if}
  {tab_end}
</div>
