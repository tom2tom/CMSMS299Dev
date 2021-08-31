{* wizard step 7 -- files *}
{extends file='wizard_step.tpl'}

{block name='logic'}
  {$subtitle = 'title_step7'|tr}
  {$current_step = '7'}
{/block}

{block name='contents'}
  <div class="message blue icon">
    <i class="icon-folder message-icon"></i>
    <div class="content"><span class="heavy">{'prompt_dir'|tr}:</span><br />{$dir}</div>
  </div>

  <div id="inner"></div>
  {if (!empty($next_url) && empty($error))}
  <div id="bottom_nav">
    <a class="action-button positive" href="{$next_url}" title="{'next'|tr}"><i class="icon-cog"></i> {'next'|tr}</a>
  </div>
  {/if}
{/block}
