<form action="{$selfurl}{$urlext}" enctype="multipart/form-data" method="post">
{tab_header name='user' label=lang('myaccount')}
{if isset($groups)}
  {tab_header name='groups' label=lang('groups')}
{/if}
{tab_header name='settings' label=lang('settings')}

{tab_start name='user'}
<!-- user -->
<div class="pageoverflow">
  <p class="pagetext">
    <label for="username">*{lang('username')}:</label>
    {cms_help realm='admin' key2='info_adduser_username' title=lang('username')}
  </p>
  <p class="pageinput">
    <input id="username" type="text" name="user" maxlength="255" value="{$user}" class="standard"/>
  </p>
</div>
<div class="pageoverflow">
  <p class="pagetext">
    <label for="password">*{lang('password')}:</label>
    {cms_help realm='admin' key2='info_edituser_password' title=lang('password')}
  </p>
  <p class="pageinput">
    <input type="password" id="password" name="password" maxlength="100" value="{$password}" class="standard"/>
  </p>
</div>
<div class="pageoverflow">
  <p class="pagetext">
    <label for="passwordagain">*{lang('passwordagain')}:</label>
    {cms_help realm='admin' key2='info_edituser_passwordagain' title=lang('passwordagain')}
  </p>
  <p class="pageinput">
    <input type="password" id="passwordagain" name="passwordagain" maxlength="100" value="{$passwordagain}" class="standard"/>
  </p>
</div>
<div class="pageoverflow">
  <p class="pagetext">
    <label for="firstname">{lang('firstname')}:</label>
    {cms_help key2='help_myaccount_firstname' title=lang('firstname')}
  </p>
  <p class="pageinput">
    <input type="text" id="firstname" name="firstname" maxlength="50" value="{$firstname}" class="standard"/>
  </p>
</div>
<div class="pageoverflow">
  <p class="pagetext">
    <label for="lastname">{lang('lastname')}:</label>
    {cms_help key2='help_myaccount_lastname' title=lang('lastname')}
  </p>
  <p class="pageinput">
    <input type="text" id="lastname" name="lastname" maxlength="50" value="{$lastname}" class="standard"/>
  </p>
</div>
<div class="pageoverflow">
  <p class="pagetext">
    <label for="email">{lang('email')}:</label>
    {cms_help key2='help_myaccount_email' title=lang('email')}
  </p>
  <p class="pageinput">
    <input type="text" id="email" name="email" maxlength="255" value="{$email}" class="standard"/>
  </p>
</div>
<div class="pageoverflow">
  <p class="pagetext">
    {lang('active')}:
    {cms_help realm='admin' key2='info_user_active' title=lang('active')}
  </p>
  <p class="pageinput">
    <input type="checkbox" class="pagecheckbox" name="active" value="1"{if $active == 1} checked="checked"{/if}/>
  </p>
</div>

{if isset($groups)}
<!-- groups -->
{tab_start name='groups'}
<div class="pageverflow">
  <p class="pagetext">
    {lang('groups')}:
    {cms_help realm='admin' key2='info_membergroups' title=lang('groups')}
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
            {strip}
            <td>
            <input type="checkbox" name="sel_groups[]" id="g{$onegroup->id}" value="{$onegroup->id}"{if in_array($onegroup->id,$sel_groups)} checked="checked"{/if} /></td>
            <td>
            <label for="g{$onegroup->id}">{$onegroup->name}</label></td>
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
  <p class="pagetext">
    {lang('copyusersettings')}:
    {cms_help realm='admin' key2='info_copyusersettings' title=lang('copyusersettings')}
  </p>
  <p class="pageinput">
    <select name="copyusersettings">
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
