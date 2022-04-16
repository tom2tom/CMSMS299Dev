{* wizard step 8 -- CMSMS infrastructure *}
{extends file='wizard_step.tpl'}

{block name='logic'}
  {$subtitle = 'title_step8'|tr}
  {$current_step = '8'}
{/block}

{block name='contents'}
  <div id="inner"></div>
  {if !empty($next_url) && empty($error)}
  <div id="bottom_nav">
    <a class="action-button positive" href="{$next_url}" title="{'next'|tr}">{if empty($lang_rtl)}<i class="icon-next-right"></i> {'next'|tr}{else}{'next'|tr} <i class="icon-next-left"></i>{/if}</a>
  </div>
{*  {else}<a href="{$retry_url}" class="action-button negative" title="{'retry'|tr}">{if !empty($lang_rtl)}<i class="icon-refresh"></i> {'retry'|tr}{else}{'retry'|tr} <i class="icon-refresh"></i>{/if}</a>*}
  {/if}
{/block}
