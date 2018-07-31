{if $events}
<h4>{lang('filterbymodule')}</h4>
<form action="{$selfurl}{$urlext}" method="post">
  <div class="oneliner cf">
  <select name="senderfilter">
   <option value="{lang('showall')}"</option>
   {foreach $senders as $one}
    <option value="{$one}"{if $one == $senderfilter} selected="selected"{/if}>{$one}</option>
   {/foreach}
  </select>
  <button type="submit" name="submit" class="adminsubmit icon do">{lang('submit')}</button>
  </div>
</form>
<br />
<table class="pagetable">
  <thead>
    <tr>
      <th title="{lang('title_event_originator')}">{lang('originator')}</th>
      <th title="{lang('title_event_name')}">{lang('event')}</th>
      <th title="{lang('title_event_handlers')}">{lang('eventhandler')}</th>
      <th title="{lang('title_event_description')}" style="width:50%;">{lang('description')}</th>
      <th class="pageicon">&nbsp;</th>
      {if $access}
      <th class="pageicon">&nbsp;</th>
      {/if}
    </tr>
  </thead>
  <tbody>
    <tr class="{cycle values='row1,row2'}">
      {strip}
      {foreach $events as $one} {if !$senderfilter || $senderfilter == $one.originator}
      <td>{$one.originator}</td>
      <td>
       {if $access}
        <a href="{$editurl}{$urlext}&amp;action=edit&amp;module={$one.originator}&amp;event={$one.event_name}" title="{lang('edit')}">{$one.event_name}</a>
       {else}
        {$one.event_name}
       {/if}
      </td>
      <td>
       {if $one.usage_count > 0}
        <a href="{$helpurl}{$urlext}&amp;module={$one.originator}&amp;event={$one.event_name}" title="{lang('help')}">{$one.usage_count}</a>
       {/if}
      </td>
      <td>{$one.description}</td>
      <td class="icons_wide">
        <a href="{$helpurl}{$urlext}&amp;module={$one.originator}&amp;event={$one.event_name}">{$iconinfo}</a>
      </td>
      {if $access}
      <td class="icons_wide">
        <a href="{$editurl}{$urlext}&amp;action=edit&amp;module={$one.originator}&amp;event={$one.event_name}">{$iconedit}</a>
      </td>
      {/if}
      {/if} {/foreach}
{/strip}
    </tr>
  </tbody>
</table>
{else}
{lang('none')}
{/if}
