<form id="listusers" action="{$selfurl}" enctype="multipart/form-data" method="post">
  {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />
{/foreach}
  <div class="pageoptions">
  <a href="{$addurl}{$urlext}" title="{_ld('admin','info_adduser')}">{admin_icon icon='newobject.gif' class='systemicon'}&nbsp;{_ld('admin','adduser')}</a>
  </div>

  <table class="pagetable">
  <thead>
  <tr>
    <th>{_ld('admin','username')}</th>
    <th style="text-align: center;">{_ld('admin','active')}</th>
    {if $is_admin}<th class="pageicon"></th>{/if}
    <th class="pageicon"></th>
    <th class="pageicon"></th>
    <th class="pageicon"><input type="checkbox" id="sel_all" value="1" title="{_ld('admin','selectall')}" /></th>
  </tr>
  </thead>
  <tbody>
  {foreach $userlist as $user} {$can_edit = $user->access_to_user}
  <tr class="{cycle values='row1,row2'}">
    {strip}
    <td>
    {if $can_edit}
    <a href="{$editurl}{$urlext}&amp;user_id={$user->id}" title="{_ld('admin','edituser')}">{$user->username}</a>
    {else}
    <span title="{_ld('admin','info_noedituser')}">{$user->username}</span>
    {/if}
    </td>

    <td style="text-align: center;">
    {if $can_edit && $user->id != $my_userid}
    <a href="{$selfurl}{$urlext}&amp;toggleactive={$user->id}" title="{_ld('admin','info_user_active2')}" class="toggleactive">
    {if $user->active}{$icontrue}{else}{$iconfalse}{/if}
    </a>
    {/if}
    </td>

    {if $is_admin}
    <td>
    {if $user->active && $user->id != $my_userid}
    <a href="{$selfurl}{$urlext}&amp;switchuser={$user->id}" title="{_ld('admin','info_user_switch')}" class="switchuser">{$iconrun}</a>
    {/if}
    </td>
    {/if}

    <td>
    {if $can_edit}
    <a href="{$editurl}{$urlext}&amp;user_id={$user->id}" title="{_ld('admin','edituser')}">{$iconedit}</a>
    {/if}
    </td>

    <td>
    {if $can_edit && $user->id != $my_userid}
    <a href="{$deleteurl}{$urlext}&amp;user_id={$user->id}" class="js-delete" title="{_ld('admin','deleteuser')}">{$icondel}</a>
    {/if}
    </td>

    <td>
    {if $can_edit && $user->id != $my_userid}
    <input type="checkbox" name="multiselect[]" class="multiselect" value="{$user->id}" title="{_ld('admin','info_selectuser')}" />
    {/if}
    </td>
{/strip}
  </tr>
  {/foreach}
  </tbody>
  </table>

  <div class="pageoptions rowbox{if count($userlist) > 10} expand">
  <div class="boxchild">
    <a href="{$addurl}{$urlext}" title="{_ld('admin','info_adduser')}">{$iconadd}</a>
    <a href="{$addurl}{$urlext}">{_ld('admin','adduser')}</a>
  </div>
  {else}" style="justify-content:flex-end;">{/if}
  <div class="boxchild">
  <label for="bulkaction">{_ld('admin','selecteditems')}:</label>&nbsp;
  <select name="bulkaction" id="bulkaction">
    <option value="clearoptions">{_ld('admin','clearusersettings')}</option>
    <option value="copyoptions">{_ld('admin','copyusersettings2')}</option>
    <option value="disable">{_ld('admin','disable')}</option>
    <option value="enable">{_ld('admin','enable')}</option>
  </select>&nbsp;
  <div id="userlist" style="display: none;">
    <label for="userlist_sub">{_ld('admin','copyfromuser')}:</label>&nbsp;
    <select name="userlist" id="userlist_sub">
      {html_options options=$userlist}    </select>
  </div>
  <button type="submit" id="bulksubmit" name="bulk" class="adminsubmit icon do">{_ld('admin','submit')}</button>
  </div>
  </div>{*rowbox*}
</form>
