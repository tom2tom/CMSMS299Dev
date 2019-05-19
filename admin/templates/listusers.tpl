<form id="listusers" action="{$selfurl}" enctype="multipart/form-data" method="post">
  {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />
{/foreach}
  <div class="pageoptions">
  <a href="{$addurl}{$urlext}" title="{lang('info_adduser')}">{admin_icon icon='newobject.gif' class='systemicon'}&nbsp;{lang('adduser')}</a>
  </div>

  <table class="pagetable">
  <thead>
  <tr>
    <th>{lang('username')}</th>
    <th style="text-align: center;">{lang('active')}</th>
    {if $is_admin}<th class="pageicon"></th>{/if}
    <th class="pageicon"></th>
    <th class="pageicon"></th>
    <th class="pageicon"><input type="checkbox" id="sel_all" value="1" title="{lang('selectall')}" /></th>
  </tr>
  </thead>
  <tbody>
  {foreach $userlist as $user} {$can_edit = $user->access_to_user}
  <tr class="{cycle values='row1,row2'}">
    {strip}
    <td>
    {if $can_edit}
    <a href="{$editurl}{$urlext}&amp;user_id={$user->id}" title="{lang('edituser')}">{$user->username}</a>
    {else}
    <span title="{lang('info_noedituser')}">{$user->username}</span>
    {/if}
    </td>

    <td style="text-align: center;">
    {if $can_edit && $user->id != $my_userid}
    <a href="{$selfurl}{$urlext}&amp;toggleactive={$user->id}" title="{lang('info_user_active2')}" class="toggleactive">
    {if $user->active}{$icontrue}{else}{$iconfalse}{/if}
    </a>
    {/if}
    </td>

    {if $is_admin}
    <td>
    {if $user->active && $user->id != $my_userid}
    <a href="{$selfurl}{$urlext}&amp;switchuser={$user->id}" title="{lang('info_user_switch')}" class="switchuser">{$iconrun}</a>
    {/if}
    </td>
    {/if}

    <td>
    {if $can_edit}
    <a href="{$editurl}{$urlext}&amp;user_id={$user->id}" title="{lang('edituser')}">{$iconedit}</a>
    {/if}
    </td>

    <td>
    {if $can_edit && $user->id != $my_userid}
    <a href="{$deleteurl}{$urlext}&amp;user_id={$user->id}" class="js-delete" title="{lang('deleteuser')}">{$icondel}</a>
    {/if}
    </td>

    <td>
    {if $can_edit && $user->id != $my_userid}
    <input type="checkbox" name="multiselect[]" class="multiselect" value="{$user->id}" title="{lang('info_selectuser')}" />
    {/if}
    </td>
{/strip}
  </tr>
  {/foreach}
  </tbody>
  </table>

  <div class="pageoptions rowbox{if count($userlist) > 10} expand">
  <div class="boxchild">
    <a href="{$addurl}{$urlext}" title="{lang('info_adduser')}">{$iconadd}</a>
    <a href="{$addurl}{$urlext}">{lang('adduser')}</a>
  </div>
  {else}" style="justify-content:flex-end;">{/if}
  <div class="boxchild">
  <label for="bulkaction">{lang('selecteditems')}:</label>&nbsp;
  <select name="bulkaction" id="bulkaction">
    <option value="clearoptions">{lang('clearusersettings')}</option>
    <option value="copyoptions">{lang('copyusersettings2')}</option>
    <option value="disable">{lang('disable')}</option>
    <option value="enable">{lang('enable')}</option>
  </select>&nbsp;
  <div id="userlist" style="display: none;">
    <label for="userlist_sub">{lang('copyfromuser')}:</label>&nbsp;
    <select name="userlist" id="userlist_sub">
    {html_options options=$userlist}
    </select>
  </div>
  <button type="submit" id="bulksubmit" name="bulk" class="adminsubmit icon do">{lang('submit')}</button>
  </div>
  </div>{*rowbox*}
</form>
