{if $jobs}{$core=_ld('jobs','core')}
  <div class="pageinfo">{_ld('jobs','info_background_jobs')}</div>
  <table class="pagetable" style="width:auto;">
    <thead>
      <tr>
        <th>{_ld('jobs','name')}</th>
        <th>{_ld('jobs','origin')}</th>
        <th>{_ld('jobs','created')}</th>
        <th>{_ld('jobs','start')}</th>
        <th>{_ld('jobs','frequency')}</th>
        <th>{_ld('jobs','until')}</th>
        <th>{_ld('jobs','errors')}</th>
        <th class="pageicon"></th>
      </tr>
    </thead>
    <tbody>
    {foreach $jobs as $job}{$now=$smarty.now}
      <tr class="{cycle values='row1,row2'}">
        <td>{$job->name}</td>
        <td>{$job->module|default:$core}</td>
        <td>{$job->created|relative_time}</td>
        <td>{strip}{if $job->start}
           {if $job->start < $now - $jobinterval*60}<span class="red">
           {elseif $job->start < $now + $jobinterval*60}<span class="green">
           {else}<span>
           {/if}
           {$job->start|relative_time}</span>
        {/if}{/strip}</td>
        <td>{$job->frequency}</td>
        <td>{if $job->until}{$job->until|cms_date_format:'timed'}{/if}</td>
        <td>{if $job->errors > 0}<span class="red">{$job->errors}</span>{/if}</td>
        <td></td>
      </tr>
    {/foreach}
    </tbody>
  </table>
{else}
  <div class="information">{_ld('jobs','info_no_jobs')}</div>
{/if}
