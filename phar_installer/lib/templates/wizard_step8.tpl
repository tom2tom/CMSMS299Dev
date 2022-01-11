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
    <a class="action-button positive" href="{$next_url}" title="{'next'|tr}"><i class='icon-next-{if empty($lang_rtl)}right{else}left{/if}'></i> {'next'|tr}</a>
  </div>
  {/if}
{/block}
