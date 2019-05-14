<h3>{lang('modifyeventhandlers')}</h3>
<fieldset>
  <legend><strong>{lang('event')}</strong></legend>
  <div class="pageoverflow">
    <p class="pagetext">{lang('name')}:</p>
    <p class="pageinput">{$event}</p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext">{lang('originator')}:</p>
    <p class="pageinput">{$originname}</p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext">{lang('description')}:</p>
    <p class="pageinput">{$description}</p>
  </div>
</fieldset>
<br />
<h4>{lang('eventhandlers')}</h3>
{if $handlers}
  <table class="pagetable">
  <thead>
    <tr>
      <th>{lang('order')}</th>
      <th>{lang('tag')}</th>
      <th>{lang('originator')}</th>
      <th class="pageicon">&nbsp;</th>
      <th class="pageicon">&nbsp;</th>
      <th class="pageicon">&nbsp;</th>
    </tr>
  </thead>
  <tbody>{foreach $handlers as $one}
    <tr class="{cycle values='row1,row2'}">
      {strip}
      <td>{$one.handler_order}</td>
      <td>{$one.tag_name}</td>
      <td>{$one.module_name}</td>
      <td>
      {if !$one@first}
      <a href="{$selfurl}{$urlext}&amp;event={$event}&amp;originator={$originator}&amp;action=up&amp;order={$one.handler_order}&amp;handler={$one.handler_id}">{$iconup}</a>
      {/if}
      </td>
      <td>
      {if !$one@last}
      <a href="{$selfurl}{$urlext}&amp;event={$event}&amp;originator={$originator}&amp;action=down&amp;order={$one.handler_order}&amp;handler={$one.handler_id}">{$icondown}</a>
      {/if}
      </td>
      <td>
      {if $one.removable}{if $one.tag_name}{$myname=$one.tag_name}{else}{$myname=$one.module_name}{/if}
      <a href="{$selfurl}{$urlext}&amp;event={$event}&amp;originator={$originator}&amp;action=delete&amp;handler={$one.handler_id}" onclick="cms_confirm_linkclick(this,'{lang('deleteconfirm', $myname)}');return false;">{$icondel}</a>
      {/if}
      </td>
{/strip}
    </tr>
  {/foreach}</tbody>
  </table>
{else}
{lang('none')}<br />
{/if}

{if $allhandlers}
<div class="pageinput pregap">
 <p class="pageinfo">{lang('info_handlers')}</p>
 <form action="{$selfurl}{$urlext}" enctype="multipart/form-data" method="post">
  <input type="hidden" name="event" value="{$event}" />
  <input type="hidden" name="originator" value="{$originator}" />
  <select name="handler">
  {foreach $allhandlers as $key => $value}
  <option value="{$value}">{$key}</option>
  {/foreach}
  </select>
  <button type="submit" name="add" class="adminsubmit icon add" title="{lang('addhandler')}">{lang('add')}</button>
 </form>
</div>
{/if}
