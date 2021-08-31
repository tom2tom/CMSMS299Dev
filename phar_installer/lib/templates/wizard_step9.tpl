{* wizard step 9 -- finish *}
{extends file='wizard_step.tpl'}

{block name='logic'}
  {$subtitle = 'title_step9'|tr}
  {$current_step = '9'}
{/block}

{block name='contents'}
<div id="inner"></div>
<div id="bottom_nav" class="complete">{* completion message *}</div>
{/block}
