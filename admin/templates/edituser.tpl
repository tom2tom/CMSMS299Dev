{if $user}<h3 class="pagesubtitle">{lang('username')}:&nbsp;{$user}</h3>{/if}

<form action="{$selfurl}" enctype="multipart/form-data" method="post" accept-charset="utf-8">
  {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />
{/foreach}
  <input type="hidden" name="user_id" value="{$user_id}" />

  {tab_header name='profile' label=lang('profile')}
  {if $groups}{tab_header name='groups' label=lang('groups')}{/if}
  {if $manage_users}{tab_header name='settings' label=lang('settings')}{/if}

  {tab_start name='profile'}
  <!-- user profile -->
  <div class="pageoverflow">
  <p class="pagetext">{$t=lang('username')}
    <label for="username">{$t}:</label>
    {cms_help key2='user_name' title=$t}
  </p>
  <input type="text" class="pageinput" name="user" id="username" maxlength="25" value="{$user}" />
  </div>
  <div class="pageoverflow">
  <p class="pagetext">{$t=lang('password')}
    <label for="password">{$t}:</label>
    {cms_help key2='user_edit_password' title=$t}
  </p>
  <input type="password" class="pageinput" name="password" id="password" autocomplete="off" maxlength="64" />
  </div>
  <div class="pageoverflow">
  <p class="pagetext">{$t=lang('passwordagain')}
    <label for="passwordagain">{$t}:</label>
    {cms_help key2='user_edit_passwordagain' title=$t}
  </p>
  <input type="password" class="pageinput" name="passwordagain" id="passwordagain" autocomplete="off" maxlength="64" />
  </div>
  <div class="pageoverflow">
  <p class="pagetext">{$t=lang('firstname')}
    <label for="firstname">{$t}:</label>
    {cms_help key2='user_firstname' title=$t}
  </p>
  <input type="text" class="pageinput" name="firstname" id="firstname" maxlength="50" value="{$firstname}" />
  </div>
  <div class="pageoverflow">
  <p class="pagetext">{$t=lang('lastname')}
    <label for="lastname">{$t}:</label>
    {cms_help key2='user_lastname' title=$t}
  </p>
  <input type="text" class="pageinput" name="lastname" id="lastname" maxlength="50" value="{$lastname}" />
  </div>
  <div class="pageoverflow">
  <p class="pagetext">{$t=lang('email')}
    <label for="email">{$t}:</label>
    {cms_help key2='user_email' title=$t}
  </p>
  <p class="pageinput">
    <input type="text" name="email" id="email" size="50" maxlength="255" value="{$email}" />
  </p>
  </div>

  {if $perm1usr}
  <input type="hidden" name="active" value="1" />
{* <input type="hidden" name="adminaccess" value="1" /> *}
  {elseif $access_user}
  <input type="hidden" name="active" value="{$active}" />
{* <input type="hidden" name="adminaccess" value="{$adminaccess}" /> *}
  {else}
  <div class="pageoverflow">
   <p class="pagetext">{$t=lang('active')}
    <label for="active">{$t}:</label>
    {cms_help key2='user_active' title=$t}
   </p>
   <input type="hidden" name="active" value="0" />
   <input type="checkbox" name="active" id="active" class="pageinput pagecheckbox" value="1"{if $active} checked="checked"{/if} />
  </div>
{*
  <div class="pageoverflow">
   <p class="pagetext">{$t=lang('adminaccess')}
    <label for="adminaccess">{$t}:</label>
    {cms_help key2='user_login' title=$t}
   </p>
   <input type="hidden" name="adminaccess" value="0" />
   <input type="checkbox" name="adminaccess" id="adminaccess" class="pageinput pagecheckbox" value="1"{if $adminaccess} checked="checked"{/if} />
  </div>
*}
  {/if}{*!$access_user*}

  {if isset($groups)}
  {tab_start name='groups'}
  <!-- group options -->
  <input type="hidden" name="groups" value="1" />
  {foreach $groups as $onegroup}<input type="hidden" name="g{$onegroup->id}" value="0" />{/foreach}
  <div class="pageverflow">
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
        {strip}{$gid=$onegroup->id}
        <td>
        <input type="checkbox" id="g{$gid}" name="g{$gid}" value="1"
{if in_array($gid,$membergroups)} checked="checked"
{elseif ($gid == 1 && $my_userid != 1)} disabled
{/if} />
        </td>
        <td><label for="g{$gid}">{$onegroup->name}</label></td>
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

  {if $manage_users}
  {tab_start name='settings'}
  <!-- user settings -->
  <input type="hidden" name="settings" value="1" />
  <div class="pageoverflow">
  <p class="pagetext">{$t=lang('copyusersettings')}
    <label for="copyuser">{$t}:</label>
    {cms_help key2='user_copysettings' title=$t}
  </p>
  <p class="pageinput">
    <select id="copyuser" name="copyusersettings">
     {html_options options=$users}
    </select>
  </p>
  </div>
  <div class="pageoverflow">
  <p class="pagetext">{$t=lang('clearusersettings')}
    <label for="clearsettings">{$t}:</label>
    {cms_help key2='user_clearsettings' title=$t}
  </p>
  <input type="hidden" name="clearusersettings" value="0" />
  <input type="checkbox" name="clearusersettings" id="clearsettings" class="pageinput pagecheckbox" value="1" />
  </div>
  {/if}
  {tab_end}
  <div class="pageinput pregap">
    <button type="submit" name="submit" id="submit" class="adminsubmit icon check">{lang('submit')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
  </div>
</form>
