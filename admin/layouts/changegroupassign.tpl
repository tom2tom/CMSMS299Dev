<div class="pageinfo">
  {_la('info_changeusergroup')}
  {cms_help 0='help' key='help_group_permissions' title=_la('grouppermissions')}
</div>
{*
<div id="admin_group_warning" style="display:none">
  {$admin_group_warning}
</div>
*}
{if $group_list}
<div class="pageoverflow">
  <form action="{$selfurl}" enctype="multipart/form-data" method="post">
    {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}">
{/foreach}
    <strong>{_la('selectgroup')}:</strong>&nbsp;
    <select name="groupsel" id="groupsel">
    {foreach $group_list as $thisgroup}
      <option value="{$thisgroup->id}"{if $thisgroup->id == $disp_group} selected="selected"{/if}>{$thisgroup->name}</option>
    {/foreach}
    </select>&nbsp;
    <button type="submit" name="filter" class="adminsubmit icon do">{_la('apply')}</button>
  </form>
</div>
{if $displaygroups && $users}{$group_count=count($displaygroups)}
<br>
<form id="groupname" action="{$selfurl}" enctype="multipart/form-data" method="post">
{foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}">
{/foreach}
<div class="pageoptions postgap">
  <button type="submit" name="submit" class="adminsubmit icon check">{_la('submit')}</button>
  <button type="submit" name="cancel" class="adminsubmit icon cancel">{_la('cancel')}</button>
</div>
<table class="pagetable" id="permtable">
  <thead>
    <tr>
      <th>{if isset($title_group)}{$title_group}{/if}</th>
      {foreach $displaygroups as $thisgroup}{if $thisgroup->id != -1}
      <th class="g{$thisgroup->id}" {strip}
      {if $thisgroup->active}
        >{$thisgroup->name}
      {else}
        title="{_la('info_group_inactive')}">
        {if $group_count >= 5}
          {$text=$thisgroup->name|cat:'!'}
        {else}
          {$text=$thisgroup->name|cat:'&nbsp;({_la("inactive")})'}
        {/if}
        <em>{$text}</em>
      {/if}
      {/strip}</th>
      {/if}{/foreach}
    </tr>
  </thead>
  <tbody>
    {foreach $users as $user}
    <tr class="{cycle values='row1,row2'}">
      {strip}
      <td>{$user->name}</td>
      {foreach $displaygroups as $thisgroup}
        {if $thisgroup->id != -1} {$gid=$thisgroup->id}
        <td class="pagepos g{$gid}">
        <input type="hidden" name="ug_{$user->id}_{$gid}" value="0">
        <input type="checkbox" name="ug_{$user->id}_{$gid}" value="1"
{if $usr1perm}
        {if isset($user->group[$gid])} checked{/if}>
{elseif $grp1perm || $pmod} {* any non-Admin change, checked|disabled for Admin *}
        {if isset($user->group[$gid])} checked{elseif $gid == 1} disabled{/if}>
{elseif $user_id == $user->id} {* self-removal *}
        {if isset($user->group[$gid])} checked{else} disabled{/if}>
{/if}
        </td>
      {/if}{/foreach} {*displaygroups*}
{/strip}
    </tr>
    {/foreach}
  </tbody>
</table>
{if count($users) > 10}
<div class="pageinput pregap">
  <button type="submit" name="submit" class="adminsubmit icon check">{_la('submit')}</button>
  <button type="submit" name="cancel" class="adminsubmit icon cancel">{_la('cancel')}</button>
</div>
{/if}
</form>
{/if}{*$displaygroups && $users*}
{else}
{_la('TODO no relevant group and/or user')}
{/if}{*$group_list*}
