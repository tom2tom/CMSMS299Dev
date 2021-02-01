{if $jobs}{$core=lang_by_realm('jobs', 'core')}
  <div class="pageinfo">{lang_by_realm('jobs', 'info_background_jobs')}</div>
  <table class="pagetable" style="width:auto;">
    <thead>
      <tr>
        <th>{lang_by_realm('jobs', 'name')}</th>
        <th>{lang_by_realm('jobs', 'origin')}</th>
        <th>{lang_by_realm('jobs', 'created')}</th>
        <th>{lang_by_realm('jobs', 'start')}</th>
        <th>{lang_by_realm('jobs', 'frequency')}</th>
        <th>{lang_by_realm('jobs', 'until')}</th>
        <th>{lang_by_realm('jobs', 'errors')}</th>
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
        <td>{if $job->until}{$job->until|date_format:'%x %X'}{/if}</td>
        <td>{if $job->errors > 0}<span class="red">{$job->errors}</span>{/if}</td>
        <td></td>
      </tr>
    {/foreach}
    </tbody>
  </table>
{else}
  <div class="information">{lang_by_realm('jobs', 'info_no_jobs')}</div>
{/if}
