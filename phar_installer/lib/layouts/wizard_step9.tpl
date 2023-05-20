{* wizard step 9 -- finish *}
{extends file='wizard_step.tpl'}

{block name='logic'}
  {$subtitle = 'title_step9'|tr}
  {$current_step = '9'}
{/block}

{block name='contents'}
<div id="inner"></div>
<div id="bottom_nav">
<p id="complete">{*completion message goes here*}</p><br>
{'finished_all_msg1'|tr}:<br>
<p id="social">
<a href="https://www.cmsmadesimple.org/support/options" target="_blank"><i class="icon-cmsms"></i> {'support_channels'|tr}</a><br>
<a href="https://www.cmsmadesimple.org/community/newsletter" target="_blank"><i class="icon-news"></i> {'newsletter'|tr}</a><br>
<a href="https://www.facebook.com/cmsmadesimple" target="_blank"><i class="icon-facebook"></i> Facebook</a><br>
<a href="https://www.linkedin.com/groups/1139537" target="_blank"><i class="icon-linkedin"></i> LinkedIn</a><br>
<a href="https://twitter.com/#!/cmsms" target="_blank"><i class="icon-twitter"></i> Twitter</a>
</p><br>{$link="<a href=\"https://www.cmsmadesimple.org/donations\" target=\"_blank\">{'support_payments'|tr}</a>"}
{['finished_all_msg2',$link]|tr}
</div>
{/block}
