{* wizard step 3 *}

{extends file='wizard_step.tpl'}
{block name='logic'}
  {$subtitle = tr('title_step3')}
  {$current_step = '3'}
{/block}

{block name='contents'}

{if $tests_failed}
  {if !$can_continue}
    <div class="message red">{tr('step3_failed')}</div>
  {else}
    <div class="message yellow">{tr('sometests_failed')}</div>
  {/if}
{/if}

{if $tests_failed || $verbose}
  {if !empty($information)}
  <table class="bordered-table shrimp">
    <caption>
      {tr('server_info')}
    </caption>
    <tbody>
  {foreach $information as $test}
      <tr>
        <td{if !empty($test->msg_key)} title="{tr($test->msg_key)}"{/if}>{if !empty($test->name_key)}{tr($test->name_key)}{else}{$test->name}{/if}</td>
        <td>{$test->value}</td>
      </tr>
  {/foreach}
    </tbody>
  </table>
  {/if}
  <table class="bordered-table installer-test-legend shrimp">
    <thead class="tbhead">
      <tr>
        <th>{tr('th_status')}</th>
        <th>{tr('th_testname')}</th>
      </tr>
    </thead>
    <tbody>
    {foreach $tests as $test}
      <tr class="{cycle values='odd,even'}{if $test->status == 'test_fail'} error{/if}{if $test->status == 'test_warn'} warning{/if}">
        <td class="{$test->status}">{if $test->status == 'test_fail'}<i title="{tr('test_failed')}" class="icon-cancel red"></i>{elseif $test->status == 'test_warn'}<i title="{tr('test_warning')}" class="icon-warning yellow"></i>{else}<i title="{tr('test_passed')|strip_tags}" class="icon-check green"></i>{/if}</td>
        <td>
          {if $test->name_key}{tr($test->name_key)}{else}{$test->name}{/if}
          {$str = $test->msg()}
          {if $str && ($verbose || $test->status != 'test_pass')}
            <br>
            <span class="tests-infotext">{$str}</span>
          {/if}
        </td>
      </tr>
    {/foreach}
    </tbody>
  </table>
  <br>
{else}
  <div class="message green">{tr('step3_passed')}</div>
{/if}
{if $tests_failed}
<table class="bordered-table shrimp">
  <caption>
    {tr('legend')}
  </caption>
  <thead>
    <tr>
      <th>{tr('symbol')}</th>
      <th>{tr('meaning')}</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td class="test_fail red"><i title="{tr('test_failed')}" class="icon-cancel red"></td>
      <td>{tr('test_failed')}</td>
    </tr>
    <tr>
      <td class="test_pass green"><i title="{tr('test_passed')|adjust:'strip_tags'}" class="icon-check green"></i></td>
      <td>{tr('test_passed')}</td>
    </tr>
    <tr>
      <td class="test_warn yellow"><i title="{tr('test_warning')}" class="icon-warning yellow"></i></td>
      <td>{tr('test_warning')}</td>
    </tr>
  </tbody>
</table>
<br>
{/if}
{if $can_continue}<div class="message {if $tests_failed}yellow{else}blue{/if}">{tr('warn_tests')}</div>{/if}

{if $can_continue}{wizard_form_start}{/if}
<div id="bottom_nav">
{if !empty($lang_rtl)}{if ($can_continue && empty($error))} <button type="submit" class="action-button positive" name="next">{tr('next')} <i class="icon-next-left"></i></button>{/if}{/if}
{if $tests_failed}<a href="{$retry_url}" class="action-button negative" title="{tr('retry')}">{if !empty($lang_rtl)}<i class="icon-refresh"></i> {tr('retry')}{else}{tr('retry')} <i class="icon-refresh"></i>{/if}</a>{/if}
{if empty($lang_rtl)}{if ($can_continue && empty($error))} <button type="submit" class="action-button positive" name="next"><i class="icon-next-right"></i> {tr('next')}</button>{/if}{/if}
</div>
{if $can_continue}</form>{/if}
{/block}
