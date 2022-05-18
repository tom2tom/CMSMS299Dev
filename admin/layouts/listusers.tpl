<form id="listusers" action="{$selfurl}" enctype="multipart/form-data" method="post">
  {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />
{/foreach}
  <div class="pageoptions">
  <a href="{$addurl}{$urlext}" title="{_la('info_adduser')}">{admin_icon icon='newobject.gif' class='systemicon'}&nbsp;{_la('adduser')}</a>
  </div>

  <table class="pagetable">
  <thead>
  <tr>
    <th>{_la('username')}</th>
    <th style="text-align: center;">{_la('active')}</th>
    {if $is_admin}<th class="pageicon"></th>{/if}
    <th class="pageicon"></th>
    <th class="pageicon"></th>
    <th class="pageicon"><input type="checkbox" id="sel_all" value="1" title="{_la('selectall')}" /></th>
  </tr>
  </thead>
  <tbody>
  {foreach $userlist as $user} {$can_edit = $user->access_to_user}
  <tr class="{cycle values='row1,row2'}">
    {strip}
    <td>
    {if $can_edit}
    <a href="{$editurl}{$urlext}&user_id={$user->id}" title="{_la('edituser')}">{$user->username}</a>
    {else}
    <span title="{_la('info_noedituser')}">{$user->username}</span>
    {/if}
    </td>

    <td class="pagepos">
    {if $can_edit && $user->id != $my_userid}
    <a href="{$selfurl}{$urlext}&toggleactive={$user->id}" title="{_la('info_user_active2')}" class="toggleactive">
    {if $user->active}{$icontrue}{else}{$iconfalse}{/if}
    </a>
    {/if}
    </td>

    {if $is_admin}
    <td class="pagepos icons_wide">
    {if $user->active && $user->id != $my_userid}
    <a href="{$selfurl}{$urlext}&switchuser={$user->id}" title="{_la('info_user_switch')}" class="switchuser">{$iconrun}</a>
    {/if}
    </td>
    {/if}

    <td class="pagepos icons_wide">
    {if $can_edit}
    <a href="{$editurl}{$urlext}&user_id={$user->id}" title="{_la('edituser')}">{$iconedit}</a>
    {/if}
    </td>

    <td class="pagepos icons_wide">
    {if $can_edit && $user->id != $my_userid}
    <a href="{$deleteurl}{$urlext}&user_id={$user->id}" class="js-delete" title="{_la('deleteuser')}">{$icondel}</a>
    {/if}
    </td>

    <td>
    {if $can_edit && $user->id != $my_userid}
    <input type="checkbox" name="multiselect[]" class="multiselect" value="{$user->id}" title="{_la('info_selectuser')}" />
    {/if}
    </td>
{/strip}
  </tr>
  {/foreach}
  </tbody>
  </table>

  <div class="pageoptions rowbox{if count($userlist) > 10} expand">
  <div class="boxchild">
    <a href="{$addurl}{$urlext}" title="{_la('info_adduser')}">{$iconadd}</a>
    <a href="{$addurl}{$urlext}">{_la('adduser')}</a>
  </div>
  {else}" style="justify-content:flex-end;">{/if}
  <div class="boxchild">
  <label for="bulkaction">{_la('selecteditems')}:</label>&nbsp;
  <select name="bulk_action" id="bulkaction">
    {html_options options=$bulkactions}  </select>
  &nbsp;
  <div id="userlist" style="display: none;">
    <label for="userlist_sub">{_la('copyfromuser')}:</label>&nbsp;
    <select name="userlist" id="userlist_sub">
      {html_options options=$userlist}    </select>
  </div>
  <button type="submit" id="bulksubmit" name="bulk" class="adminsubmit icon do">{_la('submit')}</button>
  </div>
  </div>{*rowbox*}
</form>
