{* wizard step 8 -- CMSMS infrastructure *}
{extends file='wizard_step.tpl'}

{block name='logic'}
  {$subtitle = tr('title_step8')}
  {$current_step = '8'}
{/block}

{block name='contents'}
  <div id="inner"></div>
  {if !empty($next_url) && empty($error)}
  <div id="bottom_nav">
    <a class="action-button positive" href="{$next_url}" title="{tr('next')}">{if empty($lang_rtl)}<i class="icon-next-right"></i> {tr('next')}{else}{tr('next')} <i class="icon-next-left"></i>{/if}</a>
  </div>
{*  {else}<a href="{$retry_url}" class="action-button negative" title="{tr('retry')}">{if !empty($lang_rtl)}<i class="icon-refresh"></i> {tr('retry')}{else}{tr('retry')} <i class="icon-refresh"></i>{/if}</a>*}
  {/if}
{/block}
