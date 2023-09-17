{* wizard step 7 -- files *}
{extends file='wizard_step.tpl'}

{block name='logic'}
  {$subtitle = tr('title_step7')}
  {$current_step = '7'}
{/block}

{block name='contents'}
  <div class="message blue icon">
    <i class="icon-folder message-icon"></i>
    <div class="content"><span class="heavy">{tr('prompt_dir')}:</span><br>{$dir}</div>
  </div>

  <div id="inner"></div>
  {if (!empty($next_url) && empty($error))}
  <div id="bottom_nav">
    <a class="action-button positive" href="{$next_url}" title="{tr('next')}">{if empty($lang_rtl)}<i class="icon-next-right"></i> {tr('next')}{else}{tr('next')} <i class="icon-next-left"></i>{/if}</a>
  </div>
{*  {else}<a href="{$retry_url}" class="action-button negative" title="{tr('retry')}">{if !empty($lang_rtl)}<i class="icon-refresh"></i> {tr('retry')}{else}{tr('retry')} <i class="icon-refresh"></i>{/if}</a>*}
  {/if}
{/block}
