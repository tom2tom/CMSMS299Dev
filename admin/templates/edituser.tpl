{if $user}<h3 class="pagesubtitle">{lang('username')}:&nbsp;{$user}</h3>{/if}

<form action="{$selfurl}{$urlext}" enctype="multipart/form-data" method="post">
  <input type="hidden" value="{$user_id}" name="user_id" />

  {tab_header name='user' label=lang('profile')}
  {if isset($groups)}{tab_header name='groups' label=lang('groups')}{/if}
  {if $manage_users}{tab_header name='settings' label=lang('settings')}{/if}

  {tab_start name='user'}
  <!-- user profile -->
  <div class="pageoverflow">
  <p class="pagetext">
    <label for="username">{lang('username')}:</label>
    {cms_help realm='admin' key2='info_adduser_username' title=lang('username')}
  </p>
  <p class="pageinput">
    <input type="text" id="username" name="user" maxlength="25" value="{$user}" class="standard" />
  </p>
  </div>
  <div class="pageoverflow">
  <p class="pagetext">
    <label for="password">{lang('password')}:</label>
    {cms_help realm='admin' key2='info_edituser_password' title=lang('password')}
  </p>
  <p class="pageinput">
    <input type="password" name="password" id="password" autocomplete="off" maxlength="100" value="" class="standard" />
  </p>
  </div>
  <div class="pageoverflow">
  <p class="pagetext">
    <label for="passwordagain">{lang('passwordagain')}:</label>
    {cms_help realm='admin' key2='info_edituser_passwordagain' title=lang('passwordagain')}
  </p>
  <p class="pageinput">
    <input type="password" name="passwordagain" id="passwordagain" maxlength="100" value="" class="standard" />
  </p>
  </div>
  <div class="pageoverflow">
  <p class="pagetext">
    <label for="firstname">{lang('firstname')}:</label>
    {cms_help key2='help_myaccount_firstname' title=lang('firstname')}
  </p>
  <p class="pageinput">
    <input id="firstname" type="text" name="firstname" maxlength="50" value="{$firstname}" class="standard" />
  </p>
  </div>
  <div class="pageoverflow">
  <p class="pagetext">
    <label for="lastname">{lang('lastname')}:</label>
    {cms_help key2='help_myaccount_lastname' title=lang('lastname')}
  </p>
  <p class="pageinput">
    <input id="lastname" type="text" name="lastname" maxlength="50" value="{$lastname}" class="standard" />
  </p>
  </div>
  <div class="pageoverflow">
  <p class="pagetext">
    <label for="email">{lang('email')}:</label>
    {cms_help key2='help_myaccount_email' title=lang('email')}
  </p>
  <p class="pageinput">
    <input id="email" type="text" name="email" size="50" maxlength="255" value="{$email}" class="standard" />
  </p>
  </div>

  {if !$access_user}
  <div class="pageoverflow">
  <p class="pagetext">
    <label for="active">{lang('active')}:</label>
    {cms_help realm='admin' key2='info_user_active' title=lang('active')}
  </p>
  <input type="hidden" name="active" value="0" />
  <p class="pageinput">
    <input type="checkbox" name="active" id="active" class="pagecheckbox" value="1"{if $active} checked="checked"{/if} />
  </p>
  </div>
  {/if}
  {if isset($groups)}
  {tab_start name='groups'}
  <!-- group options -->
  <div class="pageverflow">
  <input type="hidden" name="groups" value="1" />
  <div class="pageinfo">{lang('info_membergroups')}</div>
  <br />
  <p class="pagetext">
    {lang('groups')}:
  </p>
  <br />
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
        <td>
        <input type="hidden" name="g{$onegroup->id}" value="0" />
        <input type="checkbox" name="g{$onegroup->id}" id="g{$onegroup->id}" value="1"{if in_array($onegroup->id,$membergroups)} checked="checked"{/if} />
        </td>
        <td><label for="g{$onegroup->id}">{$onegroup->name}</label></td>
        <td>{$onegroup->description}</td>
      </tr>
      {/foreach}
      </tbody>
    </table>
    </div>
  </div>
  </div>
  {/if}
  {if $manage_users}
  {tab_start name='settings'}
  <!-- user settings -->
  <div class="pageoverflow">
  <p class="pagetext">
    <label for="copyusersettings">{lang('copyusersettings')}:</label>
    {cms_help realm='admin' key2='info_copyusersettings' title=lang('copyusersettings')}
  </p>
  <p class="pageinput">
    <select id="copyusersettings" name="copyusersettings">
     {html_options options=$users}
    </select>
  </p>
  </div>
  <div class="pageoverflow">
  <p class="pagetext">
    <label for="clearusersettings">{lang('clearusersettings')}:</label>
    {cms_help realm='admin' key2='info_clearusersettings' title=lang('clearusersettings')}
  </p>
  <input type="hidden" name="clearusersettings" value="0" />
  <p class="pageinput">
    <input type="checkbox" name="clearusersettings" id="clearusersettings" value="1" />
  </p>
  </div>
  {/if}
  {tab_end}
  <div class="pageinput pregap">
    <button type="submit" name="submit" id="submit" class="adminsubmit icon check">{lang('submit')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
  </div>
</form>
