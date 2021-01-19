<div class="postgap">
 {if !empty($results)}
  <a href="{$selfurl}{$urlext}&download=1&jobtype=1">{admin_icon icon='export.gif'} {lang('download')}</a>
  {if $pclear}
    <a id="clearlink" href="{$selfurl}{$urlext}&clear=1">{admin_icon icon='delete.gif'} {lang('clear')}</a>
  {/if}
  <a id="filterlink" href="javascript:void()">{admin_icon icon='icons/extra/filter.gif'} {lang('filter')} ...</a>
  {if count($pagelist) > 1}
    <div style="text-align: right; float: right;">
      {lang('page')}:
      <select id="pagenum">
        {html_options options=$pagelist selected=$page}
      </select>
    </div>
  {/if}
 {elseif $filter_applied}
   <a id="filterlink" href="javascript:void()">{admin_icon icon='icons/extra/filter.gif'} {lang('filter')} ...</a>
 {/if}
</div>
{if !empty($results)}
<table class="pagetable">
  <thead>
    <tr>
      <th>{lang('severity')}</th>
      <th>{lang('when')}</th>
      <th>{lang('subject')}</th>
      <th>{lang('msg')}</th>
      <th>{lang('itemid')}</th>
      <th>{lang('ip_addr')}</th>
      <th>{lang('username')}</th>
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
      <td>{$one.msg}</td>
      <td>{if $one.item_id != -1}{$one.item_id}{/if}</td>
      <td>{$one.ip_addr|default:''}</td>
      <td>{$one.username}</td>
    {/strip}</tr>
    {/foreach}
  </tbody>
</table>
{/if}{* results *}
{if !empty($results) || $filter_applied}
<div id="filter_dlg" title="{lang('filter')}" style="display:none;min-width:35em;">
  {form_start action=$selfurl extraparms=$extras}
  <div class="colbox">
    <div class="rowbox flow">
      <label class="boxchild" for="f_sev">{lang('f_sev')}:</label>
      <select class="boxchild" id="f_sev" name="{$actionid}f_sev">
      {html_options options=$severity_list selected=$filter->severity}
      </select>
    </div>
    <div class="rowbox flow">
    <label class="boxchild" for="f_act">{lang('f_msg')}:</label>
    <input class="boxchild" id="f_act" name="{$actionid}f_msg" value="{$filter->msg}" />
  </div>
  <div class="rowbox flow">
    <label class="boxchild" for="f_item">{lang('f_subj')}:</label>
    <input class="boxchild" id="f_item" name="{$actionid}f_subj" value="{$filter->subject}" />
  </div>
  <div class="rowbox flow">
    <label class="boxchild" for="f_user">{lang('f_user')}:</label>
    <input class="boxchild" id="f_user" name="{$actionid}f_user" value="{$filter->username}" />
  </div>
  </div>
  <div class="pageinput pregap">
    <button type="submit" name="{$actionid}filter" class="adminsubmit icon do">{lang('filter')}</button>
    <button type="submit" name="{$actionid}reset" class="adminsubmit icon undo">{lang('reset')}</button>
  </div>
  </form>
</div>
{/if}
