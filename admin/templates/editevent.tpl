<div class="pagecontainer">
  {if !empty($error)}
   <div class="pageerrorcontainer">
     <p class="pageerror">{$error}</p>
   </div>
  {/if}

  {$maintitle}

{if $access}
  <div class="pageoverflow">
    <p class="pagetext">{lang('module_name')}:</p>
    <p class="pageinput">{$modulename}</p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext">{lang('event_name')}:</p>
    <p class="pageinput">{$event}</p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext">{lang('event_description')}</p>
    <p class="pageinput">{$description}</p>
  </div>
  <br />
  <table class="pagetable">
    <thead>
      <tr>
        <th>{lang('order')}</th>
        <th>{lang('user_tag')}</th>
        <th>{lang('module')}</th>
        <th class="pageicon">&nbsp;</th>
        <th class="pageicon">&nbsp;</th>
        <th class="pageicon">&nbsp;</th>
      </tr>
    </thead>
    {if $handlers}
    <tbody>{foreach $handlers as $one}
      <tr class="{cycle values='row1,row2'}">
        {strip}
        <td>{$one->handler_order}</td>
        <td>{$one->tag_name}</td>
        <td>{$one->module_name}</td>
        <td>
        {if !$one@first}
          <a href="{$selfurl}{$urlext}&amp;action=up&amp;order={$one->handler_order}&amp;handler={$one->handler_id}">{$iconup}</a>
        {/if}
        </td>
        <td>
        {if !$one@last}
          <a href="{$selfurl}{$urlext}&amp;action=down&amp;order={$one->handler_order}&amp;handler={$one->handler_id}">{$icondown}</a>
        {/if}
        </td>
        <td>
        {if $one->removable}
          <a href="{$selfurl}{$urlext}&amp;action=delete&amp;handler={$one->handler_id}" onclick="return confirm('{cms_html_entity_decode(lang('deleteconfirm', $one->name))}');">{$icondel}</a>
        {/if}
        </td>
{/strip}
      </tr>
    {/foreach}</tbody>
    {/if}
  </table>
  <br />
  {if $allhandlers}
  <select name="handler">
    {foreach $allhandlers as $key => $value}
      <option value="{$value}">{$key}</option>
    {/foreach}
  </select>
  <br />
  {/if}
  <form action="{$selfurl}{$urlext}" method="post">
    <input type="hidden" name="module" value="{$module}" />
    <input type="hidden" name="event" value="{$event}" />
    <button type="submit" name="create" class="adminsubmit iconadd">{lang('add')}</button>
  </form>
{else}{* no permission *}
  <div class="pageerrorcontainer">
    <p class="pageerror">{lang('noaccessto', lang('editeventhandler'))}</p>
  </div>
{/if}
</div>
