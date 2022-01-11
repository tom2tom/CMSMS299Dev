{* wizard step 9 -- finish *}
{extends file='wizard_step.tpl'}

{block name='logic'}
  {$subtitle = 'title_step9'|tr}
  {$current_step = '9'}
{/block}

{block name='contents'}
<div id="inner"></div>
<div id="bottom_nav">
<span id="complete">{*completion message goes here*}</span><br /><br />
{'finished_all_msg1'|tr}:<br />
<span id="social">
<a href="https://www.cmsmadesimple.org/support/options" target="_blank"><i class="icon-cmsms"></i> {'support_channels'|tr}</a><br />
<a href="https://www.facebook.com/cmsmadesimple" target="_blank"><i class="icon-facebook"></i> Facebook</a><br />
<a href="https://www.linkedin.com/groups/1139537" target="_blank"><i class="icon-linkedin"></i> LinkedIn</a><br />
<a href="https://twitter.com/#!/cmsms" target="_blank"><i class="icon-twitter"></i> Twitter</a>
</span><br /><br />{$msg='support_payments'|tr}
{['finished_all_msg2','<a href="https://www.cmsmadesimple.org/donations" target="_blank">'|cat:$msg|cat:'</a>']|tr}
</div>
{/block}
