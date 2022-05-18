<div class="pageinfo">
  {_la('info_changegroupperms')}
  {cms_help 0='help' key='help_group_permissions' title=_la('info_changegroupperms')}
</div>

<div class="pageoverflow">
  <form action="{$selfurl}" enctype="multipart/form-data" method="post">
  {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />
{/foreach}
    <strong>{_la('selectgroup')}:</strong>&nbsp;
    <select name="groupsel" id="groupsel">
    {foreach $allgroups as $thisgroup}
     <option value="{$thisgroup->id}"{if $thisgroup->id == $disp_group} selected="selected"{/if}>{$thisgroup->name}</option>
    {/foreach}
  </select>&nbsp;
  <button type="submit" name="filter" class="adminsubmit icon do">{_la('apply')}</button>
  </form>
</div>
<br />
<form id="groupname" action="{$selfurl}" enctype="multipart/form-data" method="post">
  <div class="hidden">
    {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />
{/foreach}
    {$hidden}
  </div>

  <div class="pageinput postgap">
    <button type="submit" name="submit" class="adminsubmit icon check">{_la('submit')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{_la('cancel')}</button>
  </div>

  <table class="pagetable scrollable" id="permtable">
    <thead>
      <tr>
        <th>{_la('permission')}</th>
        {foreach $group_list as $thisgroup}{if $thisgroup->id != -1}
        <th class="g{$thisgroup->id}">{$thisgroup->name}</th>
        {/if}{/foreach}
      </tr>
    </thead>
    <tbody>
      {foreach $perms as $section => $list}
      <tr>
        <td colspan="{count($group_list)+1}">
          <h3 style="margin:1em 0 0 0">{$section|upper}</h3>
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
        <td class="pagepos g{$gid}">
        <input type="hidden" name="pg_{$perm->id}_{$gid}" value="0" />
        <input type="checkbox" name="pg_{$perm->id}_{$gid}" value="1"{strip}
          {if $gid == 1}
            {if in_array($perm->id, $ultras)}
              {if isset($perm->group[1])} checked="checked"{/if}
              {if !$usr1perm} disabled{/if}
            {else}
              checked="checked" disabled
            {/if}
          {elseif isset($perm->group[$gid])}
              checked="checked"
              {if !( $usr1perm || (($grp1perm || $pmod) && !in_array($perm->id, $ultras)) )} disabled{/if}
          {elseif !( $usr1perm || (($grp1perm || $pmod) && !in_array($perm->id, $ultras)) )}
              disabled
          {/if}
        {/strip} />
        </td>
        {/if} {/foreach}
      </tr>
      {/foreach}
      {/foreach}
    </tbody>
  </table>
{if count($perms) > 10}
  <div class="pageinput pregap">
    <button type="submit" name="submit" class="adminsubmit icon check">{_la('submit')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{_la('cancel')}</button>
  </div>
{/if}
</form>
