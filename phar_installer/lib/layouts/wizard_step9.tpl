{* wizard step 9 -- finish *}
{extends file='wizard_step.tpl'}

{block name='logic'}
  {$subtitle = tr('title_step9')}
  {$current_step = '9'}
{/block}

{block name='contents'}
<div id="inner"></div>
<div id="bottom_nav">
<p id="complete">{*completion message goes here*}</p><br>
{tr('finished_all_msg1')}:<br>
<p id="social">
<a href="https://www.cmsmadesimple.org/support/options" target="_blank"><i class="icon-cmsms"></i> {tr('support_channels')}</a><br>
<a href="https://www.cmsmadesimple.org/community/newsletter" target="_blank"><i class="icon-news"></i> {tr('newsletter')}</a><br>
<a href="https://www.facebook.com/cmsmadesimple" target="_blank"><i class="icon-facebook"></i> Facebook</a><br>
<a href="https://www.linkedin.com/groups/1139537" target="_blank"><i class="icon-linkedin"></i> LinkedIn</a><br>
<a href="https://x.com/#!/cmsms" target="_blank"><i class="icon-x"></i> X</a>
</p><br>{$link="<a href=\"https://www.cmsmadesimple.org/donations\" target=\"_blank\">{tr('support_payments')}</a>"}
{tr('finished_all_msg2',$link)}
</div>
{/block}
