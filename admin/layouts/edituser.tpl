{if $user}<h3 class="pagesubtitle">{_la('username')}:&nbsp;{$user}</h3>{/if}

<form action="{$selfurl}" enctype="multipart/form-data" method="post" accept-charset="utf-8">
  {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />
{/foreach}
  <input type="hidden" name="user_id" value="{$user_id}" />

  {tab_header name='profile' label=_la('profile')}
  {if $groups}{tab_header name='groups' label=_la('groups')}{/if}
  {if $manage_users}{tab_header name='settings' label=_la('settings')}{/if}

  {tab_start name='profile'}
  <!-- user profile -->
  <div class="pageoverflow">
    {$t=_la('username')}<label class="pagetext" for="username">{$t}:</label>
    {cms_help 0='help' key='user_name' title=$t}
    <div class="pageinput">
      <input type="text" id="username" name="user" maxlength="25" value="{$user}" />
    </div>
  </div>
  <div class="pageoverflow">
    {$t=_la('password')}<label class="pagetext" for="password">{$t}:</label>
    {cms_help 0='help' key='user_edit_password' title=$t}
    <div class="pageinput">
      <input type="text" id="password" name="password" autocomplete="off" maxlength="64" />
    </div>
  </div>
  <div class="pageoverflow">
    {$t=_la('passwordagain')}<label class="pagetext" for="passagain">{$t}:</label>
    {cms_help 0='help' key='user_edit_passwordagain' title=$t}
    <div class="pageinput">
      <input type="text" id="passagain" name="passwordagain" autocomplete="off" maxlength="64" />
    </div>
  </div>
  {if !($access_user || $user_id == 1)}
  <div class="pageoverflow">
    {$t=_la('onetimepassword')}<label class="pagetext" for="repass">{$t}:</label>
    {cms_help 0='help' key='user_repass' title=$t}
    <input type="hidden" name="pwreset" value="0" />
    <div class="pageinput">
      <input type="checkbox" id="repass" class="pagecheckbox" name="pwreset" value="1"{if $pwreset} checked="checked"{/if} />
    </div>
  </div>
  {/if}
  <div class="pageoverflow">
    {$t=_la('firstname')}<label class="pagetext" for="firstname">{$t}:</label>
    {cms_help 0='help' key='user_firstname' title=$t}
    <div class="pageinput">
      <input type="text" id="firstname" name="firstname" maxlength="50" value="{$firstname}" />
    </div>
  </div>
  <div class="pageoverflow">
    {$t=_la('lastname')}<label class="pagetext" for="lastname">{$t}:</label>
    {cms_help 0='help' key='user_lastname' title=$t}
    <div class="pageinput">
      <input type="text" id="lastname" name="lastname" maxlength="50" value="{$lastname}" />
    </div>
  </div>
  <div class="pageoverflow">
    {$t=_la('email')}<label class="pagetext" for="email">{$t}:</label>
    {cms_help 0='help' key='user_email' title=$t}
    <div class="pageinput">
      <input type="text" id="email" name="email" size="40" maxlength="255" value="{$email}" />
    </div>
  </div>

  {if $perm1usr}
  <input type="hidden" name="active" value="1" />
{* <input type="hidden" name="adminaccess" value="1" /> *}
  {elseif $access_user}
  <input type="hidden" name="active" value="{$active}" />
{* <input type="hidden" name="adminaccess" value="{$adminaccess}" /> *}
  {else}
  <div class="pageoverflow">
    {$t=_la('active')}<label class="pagetext" for="active">{$t}:</label>
    {cms_help 0='help' key='user_active' title=$t}
    <input type="hidden" name="active" value="0" />
    <div class="pageinput">
      <input type="checkbox" id="active" class="pagecheckbox" name="active" value="1"{if $active} checked="checked"{/if} />
    </div>
  </div>
{*
  <div class="pageoverflow">
    {$t=_la('adminaccess')}<label class="pagetext" for="adminaccess">{$t}:</label>
    {cms_help 0='help' key='user_login' title=$t}
    <input type="hidden" name="adminaccess" value="0" />
    <div class="pageinput">
      <input type="checkbox" id="adminaccess" class="pagecheckbox" name="adminaccess" value="1"{if $adminaccess} checked="checked"{/if} />
    </div>
  </div>
*}
  {/if}{*!$access_user*}

  {if isset($groups)}
  {tab_start name='groups'}
  <!-- group options -->
  <input type="hidden" name="groups" value="1" />
  {foreach $groups as $onegroup}<input type="hidden" name="g{$onegroup->id}" value="0" />{/foreach}
  <div class="pageoverflow">
    <div class="pageinfo postgap">{_la('info_membergroups')}</div>
  </div>
  <label class="pagetext" for="grpsselect">{_la('select')}:</label>
  <br />
  <div class="pageinput">
    <div class="group_memberships clear">
    <table id="grpsselect" class="pagetable">
      <thead>
      <tr>
        <th class="pageicon"></th>
        <th>{_la('name')}</th>
        <th>{_la('description')}</th>
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
        <td><label class="pagetext" for="g{$gid}">{$onegroup->name}</label></td>
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
    {$t=_la('copyusersettings')}<label class="pagetext" for="copyusersettings">{$t}:</label>
    {cms_help 0='help' key='user_copysettings' title=$t}
    <div class="pageinput">
    <select id="copyusersettings" name="copyusersettings">
      {html_options options=$users selected=$copyusersettings}    </select>
    </div>
  </div>
  <div class="pageoverflow">
    {$t=_la('clearusersettings')}<label class="pagetext" for="clearsettings">{$t}:</label>
    {cms_help 0='help' key='user_clearsettings' title=$t}
    <input type="hidden" name="clearusersettings" value="0" />
    <div class="pageinput">
      <input type="checkbox" id="clearsettings" class="pagecheckbox" name="clearusersettings" value="1" />
    </div>
  </div>
  {/if}
  {tab_end}
  <div class="pageinput pregap">
    <button type="submit" id="submit" class="adminsubmit icon check" name="submit">{_la('submit')}</button>
    <button type="submit" class="adminsubmit icon cancel" name="cancel">{_la('cancel')}</button>
  </div>
</form>
