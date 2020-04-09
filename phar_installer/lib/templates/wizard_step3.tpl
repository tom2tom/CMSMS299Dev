{* wizard step 3 *}

{extends file='wizard_step.tpl'}
{block name='logic'}
    {$subtitle = 'title_step3'|tr}
    {$current_step = '3'}
{/block}

{block name='contents'}

{if $tests_failed}
  {if !$can_continue}
    <div class="message red">{'step3_failed'|tr}</div>
  {else}
    <div class="message yellow">{'sometests_failed'|tr}</div>
  {/if}
{/if}

{if $tests_failed || $verbose}
  {if isset($information)}
  <table class="table bordered-table small-font">
    <caption>
        {'server_info'|tr}
    </caption>
    <tbody>
  {foreach $information as $test}
        <tr>
            <td{if $test->msg_key} title="{$test->msg_key|tr}"{/if}>{if $test->name_key}{$test->name_key|tr}{else}{$test->name}{/if}</td>
            <td>{$test->value}</td>
        </tr>
  {/foreach}
    </tbody>
  </table>
  {/if}
  <table class="table bordered-table installer-test-legend small-font">
    <caption>
        {'legend'|tr}
    </caption>
    <thead class="tbhead">
        <tr>
            <th>{'symbol'|tr}</th>
            <th>{'meaning'|tr}</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="test_fail red"><i title="{'test_failed'|tr}" class="icon-cancel red"></td>
            <td>{'test_failed'|tr}</td>
        </tr>
        <tr>
            <td class="test_pass green"><i title="{'test_passed'|tr|strip_tags}" class="icon-check green"></i></td>
            <td>{'test_passed'|tr}</td>
        </tr>
        <tr>
            <td class="test_warn yellow"><i title="{'test_warning'|tr}" class="icon-warning yellow"></i></td>
            <td>{'test_warning'|tr}</td>
        </tr>
    </tbody>
  </table>
  <br />
  <table class="table zebra-table bordered-table installer-test-information">
    <thead class="tbhead">
        <tr>
            <th>{'th_status'|tr}</th>
            <th>{'th_testname'|tr}</th>
        </tr>
    </thead>
    <tbody>
    {foreach $tests as $test}
        <tr class="{cycle values='odd,even'}{if $test->status == 'test_fail'} error{/if}{if $test->status == 'test_warn'} warning{/if}">
            <td class="{$test->status}">{if $test->status == 'test_fail'}<i title="{'test_failed'|tr}" class="icon-cancel red"></i>{elseif $test->status == 'test_warn'}<i title="{'test_warning'|tr}" class="icon-warning yellow"></i>{else}<i title="{'test_passed'|tr|strip_tags}" class="icon-check green"></i>{/if}</td>
            <td>
                {if $test->name_key}{$test->name_key|tr}{else}{$test->name}{/if}
                {$str = $test->msg()}
                {if $str && ($verbose || $test->status != 'test_pass')}
                  <br />
                  <span class="tests-infotext">{$str}</span>
                {/if}
            </td>
        </tr>
    {/foreach}
    </tbody>
  </table>
{else}
  <div class="message green">{'step3_passed'|tr}</div>
{/if}

{if !$tests_failed}
<div class="message blue">{'warn_tests'|tr}</div>
{/if}

{if $can_continue}{wizard_form_start}{/if}
<div id="bottom_nav">
{if $tests_failed}<a href="{$retry_url}" class="action-button orange" title="{'retry'|tr}"><i class="icon-refresh"></i> {'retry'|tr}</a>{/if}
{if $can_continue} <button class="action-button positive" type="submit" name="next"><i class="icon-cog"></i> {'next'|tr}</button>{/if}
</div>
{if $can_continue}{wizard_form_end}{/if}

{/block}
