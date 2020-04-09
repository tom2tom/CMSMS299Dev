{* wizard step 9 -- files *}

{extends file='wizard_step.tpl'}
{block name='logic'}
  {$subtitle = 'title_step9'|tr}
  {$current_step = '9'}
{/block}

{block name='contents'}
<div id="inner" style="overflow: auto; min-height: 10em; max-height: 35em;"></div>
<div id="bottom_nav">{* bottom nav is needed here *}</div>
{/block}
