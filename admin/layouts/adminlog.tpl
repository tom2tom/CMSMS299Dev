<div class="postgap">
 {if !empty($results)}
  <a href="{$selfurl}{$urlext}&download=1&jobtype=1">{admin_icon icon='export.gif'} {_la('download')}</a>
  {if $pclear}
    <a id="clearlink" href="{$selfurl}{$urlext}&clear=1">{admin_icon icon='delete.gif'} {_la('clear')}</a>
  {/if}
  <a id="filterlink" href="javascript:void()">{admin_icon icon='icons/extra/filter.gif'} {_la('filter')} ...</a>
  {if count($pagelist) > 1}
    <div style="text-align: right; float: right;">
      {_la('page')}:
      <select id="pagenum">
        {html_options options=$pagelist selected=$page}     </select>
    </div>
  {/if}
 {elseif $filter_applied}
   <a id="filterlink" href="javascript:void()">{admin_icon icon='icons/extra/filter.gif'} {_la('filter')} ...</a>
 {/if}
</div>
{if !empty($results)}
<table class="pagetable">
  <thead>
    <tr>
      <th>{_la('severity')}</th>
      <th>{_la('when')}</th>
      <th>{_la('subject')}</th>
      <th>{_la('detail')}</th>
      <th>{_la('itemid')}</th>
      <th>{_la('ip_addr')}</th>
      <th>{_la('username')}</th>
    </tr>
  </thead>
  <tbody>
    {foreach $results as $one}
     {if $one.severity == 1}
      {$rowclass='adminlog_notice'}
     {elseif $one.severity == 2}
      {$rowclass='adminlog_warning'}
     {elseif $one.severity == 3}
      {$rowclass='adminlog_error'}
     {else}
      {$rowclass=''}
     {/if}
    <tr class="{cycle values='row1,row2'} {$rowclass}">{strip}
      <td>{$severity_list[$one.severity]}</td>
      <td>{$one.when}</td>
      <td>{$one.subject}</td>
      <td>{$one.message}</td>
      <td>{if $one.item_id != -1}{$one.item_id}{/if}</td>
      <td>{$one.ip_addr|default:''}</td>
      <td>{$one.username}</td>
    {/strip}</tr>
    {/foreach}
  </tbody>
</table>
{/if}{* results *}
{if !empty($results) || $filter_applied}
<div id="filter_dlg" title="{_la('filter')}" style="display:none;min-width:35em;">
  {form_start action=$selfurl extraparms=$extras}
  <div class="colbox">
    <div class="rowbox flow">
      <label class="boxchild" for="f_sev">{_la('f_sev')}:</label>
      <select class="boxchild" id="f_sev" name="{$actionid}f_sev">
       {html_options options=$severity_list selected=$filter->severity}      </select>
    </div>
    <div class="rowbox flow">
    <label class="boxchild" for="f_act">{_la('f_msg')}:</label>
    <input class="boxchild" id="f_act" name="{$actionid}f_msg" value="{$filter->message}" />
  </div>
  <div class="rowbox flow">
    <label class="boxchild" for="f_item">{_la('f_subj')}:</label>
    <input class="boxchild" id="f_item" name="{$actionid}f_subj" value="{$filter->subject}" />
  </div>
  <div class="rowbox flow">
    <label class="boxchild" for="f_user">{_la('f_user')}:</label>
    <input class="boxchild" id="f_user" name="{$actionid}f_user" value="{$filter->username}" />
  </div>
  </div>
  <div class="pageinput pregap">
    <button type="submit" name="{$actionid}filter" class="adminsubmit icon do">{_la('filter')}</button>
    <button type="submit" name="{$actionid}reset" class="adminsubmit icon undo">{_la('reset')}</button>
  </div>
  </form>
</div>
{elseif !$filter_applied}
<p class="information">{_la('adminlogempty')}</p>
{/if}
