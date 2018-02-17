<script type="text/javascript">
{literal}//<![CDATA[
$(document).ready(function() {
  $('#sel_all').cmsms_checkall();
  $('.switchuser').click(function(ev) {
    ev.preventDefault();
    var _href = $(this).attr('href');
    cms_confirm({/literal}'{lang("confirm_switchuser")|escape:"javascript"}'{literal}).done(function() {
      window.location.href = _href;
    });
  });
  $('.toggleactive').click(function(ev) {
    ev.preventDefault();
    var _href = $(this).attr('href');
    cms_confirm({/literal}'{lang("confirm_toggleuseractive")|escape:"javascript"}'{literal}).done(function() {
      window.location.href = _href;
    });
  });
  $(document).on('click', '.js-delete', function(ev) {
    ev.preventDefault();
    var _href = $(this).attr('href');
    cms_confirm({/literal}'{lang("confirm_delete_user")|escape:"javascript"}'{literal}).done(function() {
      window.location.href = _href;
    });
  });
  $('#withselected, #bulksubmit').attr('disabled', 'disabled');
  $('#bulksubmit').button({
    'disabled': true
  });
  $('#sel_all, .multiselect').on('click', function() {
    if(!$(this).is(':checked')) {
      $('#withselected').attr('disabled', 'disabled');
      $('#bulksubmit').attr('disabled', 'disabled').button({
        'disabled': true
      });
    } else {
      $('#withselected').removeAttr('disabled');
      $('#bulksubmit').removeAttr('disabled').button({
       'disabled': false
      });
    }
  });
  $('#listusers').submit(function(ev) {
    ev.preventDefault();
    var v = $('#withselected').val();
    if(v === 'delete') {
      cms_confirm({/literal}'{lang("confirm_delete_user")|escape:"javascript"}'{literal}).done(function() {
        $('#listusers').unbind('submit');
        $('#bulksubmit').click();
      }).fail(function() {
        return false;
      });
    } else {
      cms_confirm({/literal}'{lang("confirm_bulkuserop")|escape:"javascript"}'{literal}).done(function() {
        return true;
      });
    }
  });
  $('#withselected').change(function() {
    var v = $(this).val();
    if(v === 'copyoptions') {
      $('#userlist').show();
    } else {
      $('#userlist').hide();
    }
  });
});
{/literal}//]]>
</script>

<h3 class="invisible">{lang('currentusers')}</h3>

<form id="listusers" action="{$selfurl}{$urlext}" method="post">
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
    {foreach $users as $user} {$can_edit = $user->access_to_user}
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

<div class="pageoptions">
  <div style="width: 40%; float: left;">
    <a href="{$addurl}{$urlext}" title="{lang('info_adduser')}">{$iconadd}</a>
    <a href="{$addurl}{$urlext}">{lang('adduser')}</a>
  </div>
  <div style="width: 40%; float: right; text-align: right;">
    <label for="withselected">{lang('selecteditems')}:</label>
    &nbsp;
    <select name="bulkaction" id="withselected">
      <option value="delete">{lang('delete')}</option>
      <option value="clearoptions">{lang('clearusersettings')}</option>
      <option value="copyoptions">{lang('copyusersettings2')}</option>
      <option value="disable">{lang('disable')}</option>
      <option value="enable">{lang('enable')}</option>
    </select>&nbsp;
    <div id="userlist" style="display: none;">
      <label for="userlist_sub">{lang('copyfromuser')}:</label>
    &nbsp;
      <select name="userlist" id="userlist_sub">
        {html_options options=$userlist}
      </select>&nbsp;
    </div>

    <button type="submit" id="bulksubmit" name="bulk" class="adminsubmit iconcheck">{lang('submit')}</button>
  </div>
</div>
{form_end}
