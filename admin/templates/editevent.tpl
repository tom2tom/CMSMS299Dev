<h3>{_ld('admin','modifyeventhandlers')}</h3>
<fieldset>
  <legend><strong>{_ld('admin','event')}</strong></legend>
  <div class="pageoverflow">
    <p class="pagetext">{_ld('admin','name')}:</p>
    <p class="pageinput">{$event}</p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext">{_ld('admin','originator')}:</p>
    <p class="pageinput">{$originname}</p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext">{_ld('admin','description')}:</p>
    <p class="pageinput">{$description}</p>
  </div>
</fieldset>
<br />
<h4>{_ld('admin','eventhandlers')}</h3>
{if $handlers}
  <table class="pagetable">
  <thead>
    <tr>
      <th>{_ld('admin','order')}</th>
      <th>{_ld('admin','tag')}</th>
      <th>{_ld('admin','originator')}</th>
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
{*TODO replace link onclick handler*}
      <a href="{$selfurl}{$urlext}&amp;event={$event}&amp;originator={$originator}&amp;action=delete&amp;handler={$one.handler_id}" onclick="cms_confirm_linkclick(this,'{_ld('admin','deleteconfirm', $myname)}');return false;">{$icondel}</a>
      {/if}
      </td>
{/strip}
    </tr>
  {/foreach}</tbody>
  </table>
{else}
{_ld('admin','none')}<br />
{/if}

{if $allhandlers}
<div class="pageinput pregap">
 <p class="pageinfo">{_ld('admin','info_handlers')}</p>
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
  <button type="submit" name="add" class="adminsubmit icon add" title="{_ld('admin','addhandler')}">{_ld('admin','add')}</button>
 </form>
</div>
{/if}
