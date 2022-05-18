<h3>{_la('modifyeventhandlers')}</h3>
<fieldset>
  <legend><strong>{_la('event')}</strong></legend>
  <div class="pageoverflow">
    <p class="pagetext">{_la('name')}:</p>
    <p class="pageinput">{$event}</p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext">{_la('originator')}:</p>
    <p class="pageinput">{$originname}</p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext">{_la('description')}:</p>
    <p class="pageinput">{$description}</p>
  </div>
</fieldset>
<br />
<h4>{_la('eventhandlers')}</h3>
{if $handlers}
  <table class="pagetable">
  <thead>
    <tr>
      <th>{_la('order')}</th>
      <th>{_la('tag')}</th>
      <th>{_la('originator')}</th>
      <th class="pageicon"></th>
      <th class="pageicon"></th>
      <th class="pageicon"></th>
    </tr>
  </thead>
  <tbody>{foreach $handlers as $one}
    <tr class="{cycle values='row1,row2'}">
      {strip}
      <td>{$one.handler_order}</td>
      <td>{$one.tag_name}</td>
      <td>{$one.module_name}</td>
      <td class="pagepos icons_wide">
      {if !$one@first}
      <a href="{$selfurl}{$urlext}&event={$event}&originator={$originator}&action=up&order={$one.handler_order}&handler={$one.handler_id}">{$iconup}</a>
      {/if}
      </td>
      <td class="pagepos icons_wide">
      {if !$one@last}
      <a href="{$selfurl}{$urlext}&event={$event}&originator={$originator}&action=down&order={$one.handler_order}&handler={$one.handler_id}">{$icondown}</a>
      {/if}
      </td>
      <td class="pagepos icons_wide">
      {if $one.removable}{if $one.tag_name}{$myname=$one.tag_name}{else}{$myname=$one.module_name}{/if}
{*TODO replace link onclick handler*}
      <a href="{$selfurl}{$urlext}&event={$event}&originator={$originator}&action=delete&handler={$one.handler_id}" onclick="cms_confirm_linkclick(this,'{_la('deleteconfirm', $myname)}');return false;">{$icondel}</a>
      {/if}
      </td>
{/strip}
    </tr>
  {/foreach}</tbody>
  </table>
{else}
{_la('none')}<br />
{/if}

{if $allhandlers}
<div class="pageinput pregap">
 <p class="pageinfo">{_la('info_handlers')}</p>
 <form action="{$selfurl}" enctype="multipart/form-data" method="post">
  <div class="hidden">
   {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />
{/foreach}
   <input type="hidden" name="originator" value="{$originator}" />
   <input type="hidden" name="event" value="{$event}" />
  </div>
  <select name="handler">
  {foreach $allhandlers as $key => $value}
  <option value="{$value}">{$key}</option>
  {/foreach}
  </select>
  <button type="submit" name="add" class="adminsubmit icon add" title="{_la('addhandler')}">{_la('add')}</button>
 </form>
</div>
{/if}
