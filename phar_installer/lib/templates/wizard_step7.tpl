{* wizard step 7 -- files *}
{extends file='wizard_step.tpl'}

{block name='logic'}
  {$subtitle = 'title_step7'|tr}
  {$current_step = '7'}
{/block}

{block name='contents'}
  <div class="message blue icon">
    <i class="icon-folder message-icon"></i>
    <div class="content"><strong>{'prompt_dir'|tr}:</strong><br />{$dir}</div>
  </div>

  <div id="inner" style="overflow: auto; min-height: 10em; max-height: 35em;"></div>
  {if !empty($next_url)}
  <div id="bottom_nav">
    <a class="action-button positive" href="{$next_url}" title="{'next'|tr}"><i class="icon-cog"></i> {'next'|tr}</a>
  </div>
  {/if}
{/block}
