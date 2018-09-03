{* wizard step 7 -- files *}
{extends file='wizard_step.tpl'}

{block name='logic'}
  {$subtitle = 'title_step7'|tr}
  {$current_step = '7'}
{/block}

{block name='contents'}
  <div id="inner" style="overflow: auto; min-height: 10em; max-height: 35em;"></div>
  {if !empty($next_url)}
  <div id="bottom_nav">
    <a class="action-button positive" href="{$next_url}" title="{'next'|tr}"><i class="icon-cog"></i> {'next'|tr}</a>
  </div>
  {/if}
{/block}
