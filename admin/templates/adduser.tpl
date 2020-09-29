<form action="{$selfurl}" enctype="multipart/form-data" method="post" accept-charset="utf-8">
  {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />
{/foreach}
{tab_header name='user' label=lang('profile')}
{if isset($groups)}
  {tab_header name='groups' label=lang('groups')}
{/if}
{tab_header name='settings' label=lang('settings')}

<!-- user -->
{tab_start name='user'}
<div class="pageoverflow">
  <p class="pagetext">{$t=lang('username')}
    <label for="username">*{$t}:</label>
    {cms_help key2='user_name' title=$t}
  </p>
  <input type="text" class="pageinput" id="username" name="user" maxlength="255" value="{$user}" />
</div>
<div class="pageoverflow">
  <p class="pagetext">{$t=lang('password')}
    <label for="password">*{$t}:</label>
    {cms_help key2='user_edit_password' title=$t}
  </p>
  <input type="password" class="pageinput" id="password" name="password" maxlength="100" value="{$password}" />
</div>
<div class="pageoverflow">
  <p class="pagetext">{$t=lang('passwordagain')}
    <label for="passagain">*{$t}:</label>
    {cms_help key2='user_edit_passwordagain' title=$t}
  </p>
  <input type="password" class="pageinput" id="passagain" name="passwordagain" maxlength="100" value="{$passwordagain}" />
</div>
<div class="pageoverflow">
  <p class="pagetext">{$t=lang('firstname')}
    <label for="firstname">{$t}:</label>
    {cms_help key2='user_firstname' title=$t}
  </p>
  <input type="text" class="pageinput" id="firstname" name="firstname" maxlength="50" value="{$firstname}" />
</div>
<div class="pageoverflow">
  <p class="pagetext">{$t=lang('lastname')}
    <label for="lastname">{$t}:</label>
    {cms_help key2='user_lastname' title=$t}
  </p>
  <input type="text" class="pageinput" id="lastname" name="lastname" maxlength="50" value="{$lastname}" />
</div>
<div class="pageoverflow">
  <p class="pagetext">{$t=lang('email')}
    <label for="email">{$t}:</label>
    {cms_help key2='user_email' title=$t}
  </p>
  <input type="text" class="pageinput" id="email" name="email" maxlength="255" value="{$email}" />
</div>
<div class="pageoverflow">
  <p class="pagetext">{$t=lang('active')}
    <label for="active">{$t}:</label>
    {cms_help key2='user_active' title=$t}
  </p>
  <input type="hidden" name="active" value="0" />
  <input type="checkbox" class="pageinput pagecheckbox" id="active" name="active" value="1"
{if $active} checked="checked"
{/if} />
</div>
<div class="pageoverflow">
  <p class="pagetext">{$t=lang('adminaccess')}
    <label for="adminaccess">{$t}:</label>
    {cms_help key2='user_login' title=$t}
  </p>
  <input type="hidden" name="adminaccess" value="0" />
  <input type="checkbox" class="pageinput pagecheckbox" id="adminaccess" name="adminaccess" value="1"
{if adminaccess} checked="checked"
{/if} />
</div>

{if isset($groups)}
<!-- groups -->
{tab_start name='groups'}
<input type="hidden" name="groups" value="1" />
{foreach $groups as $onegroup}<input type="hidden" name="g{$onegroup->id}" value="0" />{/foreach}
<div class="pageverflow">
  <p class="pagetext">{$t=lang('groups')}
    {$t}:
    {cms_help realm='admin' key2='info_membergroups' title=$t}
  </p>
  <div class="pageinput">
    <div class="group_memberships clear">
      <table class="pagetable">
        <thead>
          <tr>
            <th class="pageicon"></th>
            <th>{lang('name')}</th>
            <th>{lang('description')}</th>
          </tr>
        </thead>
        <tbody>
          {foreach $groups as $onegroup}
          <tr>
            {strip}{$gid=$onegroup->id}
            <td>
            <input type="checkbox" id="g{$gid}" name="g{$gid}" value="1"
{if in_array($gid,$sel_groups)} checked="checked"
{elseif ($gid == 1 && $my_userid != 1)} disabled
{/if} /></td>
            <td>
            <label for="g{$gid}">{$onegroup->name}</label></td>
            <td>{$onegroup->description}</td>
{/strip}
          </tr>
          {/foreach}
        </tbody>
      </table>
    </div>
  </div>
</div>
{/if}

<!-- settings -->
{tab_start name='settings'}
<div class="pageoverflow">
  <p class="pagetext">{$t=lang('copyusersettings')}
    <label for="copyusr">{$t}:</label>
    {cms_help key2='user_copysettings' title=$t}
  </p>
  <p class="pageinput">
    <select name="copyusersettings" id="copyusr">
      {html_options options=$users}
    </select>
  </p>
</div>
{tab_end}
<div class="pageinput pregap">
 <button type="submit" name="submit" id="submit" class="adminsubmit icon check">{lang('submit')}</button>
 <button type="submit" name="cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
</div>
</form>
