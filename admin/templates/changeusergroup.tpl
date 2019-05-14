<div class="pageinfo">
  {lang('info_changeusergroup')}
  {cms_help key2='help_group_permissions' title=lang('info_changeusergroup')}
</div>
{*
<div id="admin_group_warning" style="display:none">
  {$admin_group_warning}
</div>
*}
<div class="pageoverflow">
  <form action="{$selfurl}{$urlext}" enctype="multipart/form-data" method="post">
    <strong>{lang('selectgroup')}:</strong>&nbsp;
    <select name="groupsel" id="groupsel">
    {foreach $allgroups as $thisgroup}
      {if $thisgroup->id == $disp_group}
      <option value="{$thisgroup->id}" selected="selected">{$thisgroup->name}</option>
      {else}
      <option value="{$thisgroup->id}">{$thisgroup->name}</option>
      {/if}
    {/foreach}
    </select>&nbsp;
    <button type="submit" name="filter" class="adminsubmit icon do">{lang('apply')}</button>
  </form>
</div>
<br />
<form id="groupname" action="{$selfurl}{$urlext}" enctype="multipart/form-data" method="post">
<div class="pageoptions">
  <button type="submit" name="submit" class="adminsubmit icon check">{lang('submit')}</button>
  <button type="submit" name="cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>'
</div>
{$group_count=count($group_list)}
<table class="pagetable" id="permtable">
  <thead>
    <tr>
      <th>{if isset($title_group)}{$title_group}{/if}</th>
      {foreach $group_list as $thisgroup} {if $thisgroup->id != -1} {$text=$thisgroup->name}{if $thisgroup->active} {$title=''} {$tag='span'} {else} {$title=lang('info_group_inactive')} {$tag='em'} {if $group_count >= 5} {$text=$thisgroup->name|cat:'!'} {else} {$text=$thisgroup->name|cat:'&nbsp;({lang("inactive")})'} {/if} {/if}
      <th class="g{$thisgroup->id}">
        <{$tag} title="{$title}">{$text}</{$tag}>
      </th>
      {/if} {/foreach}
    </tr>
  </thead>
  <tbody>
    {foreach $users as $user}
    <tr class="{cycle values='row1,row2'}">
      {strip}
      <td>{$user->name}</td>
      {foreach $group_list as $thisgroup} {if $user->id == $user_id} {if $thisgroup->id != -1}
      <td class="g{$thisgroup->id}">--</td>
      {/if} {else} {if $thisgroup->id != -1} {if ($thisgroup->id == 1 && $user->id == 1)}
      <td class="g{$thisgroup->id}">&nbsp;</td>
      {else} {$gid=$thisgroup->id}
      <td class="g{$gid}">
        <input type="hidden" name="ug_{$user->id}_{$gid}" value="0" />
        <input type="checkbox" name="ug_{$user->id}_{$gid}" value="1"{if isset($user->group[$gid])} checked="checked"{/if} />
      </td>
      {/if}
      {/if}
      {/if}
      {/foreach}
{/strip}
    </tr>
    {/foreach}
  </tbody>
</table>
{if count($users) > 10}
<div class="pageinput pregap">
  <button type="submit" name="submit" class="adminsubmit icon check">{lang('submit')}</button>
  <button type="submit" name="cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
</div>
{/if}
</form>
