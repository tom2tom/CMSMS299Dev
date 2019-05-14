<div class="pageinfo">
  {lang('info_changegroupperms')}
  {cms_help key2='help_group_permissions' title=lang('info_changegroupperms')}
</div>

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
  <div class="hidden">
    {$hidden2}
  </div>

  <div class="pageoverflow">
    <p class="pageoptions">
      <button type="submit" name="submit" class="adminsubmit icon check">{lang('submit')}</button>
      <button type="submit" name="cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
    </p>
  </div>

  <table class="pagetable scrollable" id="permtable">
    <thead>
      <tr>
        <th>{lang('permission')}</th>
        {foreach $group_list as $thisgroup} {if $thisgroup->id != -1}
        <th class="g{$thisgroup->id}">{$thisgroup->name}</th>
        {/if} {/foreach}
      </tr>
    </thead>
    <tbody>
      {foreach $perms as $section => $list}
      <tr>
        <td colspan="{count($group_list)+1}">
          <h3>{$section|upper}</h3>
        </td>
      </tr>
      {foreach $list as $perm}
      <tr class="{cycle values='row1,row2'}">
        <td>
          <span style="margin-left:3em;font-weight:bold;">{$perm->label}</span>
          {if !empty($perm->description)}
          <div class="description">
          <span style="margin-left:3em;">{$perm->description}</span>
          </div>
          {/if}
        </td>
        {foreach $group_list as $thisgroup} {if $thisgroup->id != -1} {$gid=$thisgroup->id}
        <td class="g{$thisgroup->id}">
          <input type="hidden" name="pg_{$perm->id}_{$gid}" value="0" />
          <input type="checkbox" name="pg_{$perm->id}_{$gid}" value="1"{if isset($perm->group[$gid]) || $gid == 1} checked="checked"{/if}{if $gid == 1} disabled="disabled"{/if} />
        </td>
        {/if} {/foreach}
      </tr>
      {/foreach}
      {/foreach}
    </tbody>
  </table>
{if count($perms) > 10}
  <div class="pageinput pregap">
    <button type="submit" name="submit" class="adminsubmit icon check">{lang('submit')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
  </div>
{/if}
</form>
