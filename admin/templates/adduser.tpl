<form action="{$selfurl}" enctype="multipart/form-data" method="post" accept-charset="utf-8">
  {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />
{/foreach}
{tab_header name='user' label=_ld('admin','profile')}
{if isset($groups)}
  {tab_header name='groups' label=_ld('admin','groups')}
{/if}
{tab_header name='settings' label=_ld('admin','settings')}

<!-- user -->
{tab_start name='user'}
<div class="pageoverflow">
  {$t=_ld('admin','username')}<label class="pagetext" for="username">*{$t}:</label>
  {cms_help 0='help' key='user_name' title=$t}
  <div class="pageinput">
    <input type="text" id="username" name="user" maxlength="255" value="{$user}" />
  </div>
</div>
<div class="pageoverflow">
  {$t=_ld('admin','password')}<label class="pagetext" for="password">*{$t}:</label>
  {cms_help 0='help' key='user_edit_password' title=$t}
  <div class="pageinput">
    <input type="text" id="password" name="password" maxlength="64" value="{$password}" />
  </div>
</div>
<div class="pageoverflow">
  {$t=_ld('admin','passwordagain')}<label class="pagetext" for="passagain">*{$t}:</label>
  {cms_help 0='help' key='user_edit_passwordagain' title=$t}
  <div class="pageinput">
    <input type="text" id="passagain" name="passwordagain" maxlength="64" value="{$passwordagain}" />
  </div>
</div>
<div class="pageoverflow">
  {$t=_ld('admin','firstname')}<label class="pagetext" for="firstname">{$t}:</label>
  {cms_help 0='help' key='user_firstname' title=$t}
  <div class="pageinput">
    <input type="text" id="firstname" name="firstname" maxlength="50" value="{$firstname}" />
  </div>
</div>
<div class="pageoverflow">
  {$t=_ld('admin','lastname')}<label class="pagetext" for="lastname">{$t}:</label>
  {cms_help 0='help' key='user_lastname' title=$t}
  <div class="pageinput">
    <input type="text" name="lastname" maxlength="50" value="{$lastname}" />
  </div>
</div>
<div class="pageoverflow">
  {$t=_ld('admin','email')}<label class="pagetext" for="email">{$t}:</label>
  {cms_help 0='help' key='user_email' title=$t}
  <div class="pageinput">
    <input type="text" id="email" name="email" maxlength="255" value="{$email}" />
  </div>
</div>
<div class="pageoverflow">
  {$t=_ld('admin','active')}<label class="pagetext" for="active">{$t}:</label>
  {cms_help 0='help' key='user_active' title=$t}
  <input type="hidden" name="active" value="0" />
  <div class="pageinput">
    <input type="checkbox" class="pagecheckbox" id="active" name="active" value="1"{if $active} checked="checked"{/if} />
  </div>
</div>
{*
<div class="pageoverflow">
  {$t=_ld('admin','adminaccess')}<label class="pagetext" for="adminaccess">{$t}:</label>
  {cms_help 0='help' key='user_login' title=$t}
  <input type="hidden" name="adminaccess" value="0" />
  <div class="pageinput">
    <input type="checkbox" class="pagecheckbox" id="adminaccess" name="adminaccess" value="1"
  </div>
{if adminaccess} checked="checked"
{/if} />
</div>
*}
{if isset($groups)}
<!-- groups -->
{tab_start name='groups'}
<input type="hidden" name="groups" value="1" />
{* {foreach $groups as $onegroup}<input type="hidden" name="sel_groups[]" value="{$onegroup->id}" />{/foreach} *}
<div class="pageverflow">
  {$t=_ld('admin','groups')}}<label class="pagetext" for="grpmembers">{$t}:</label
  {cms_help 0='admin' key='info_membergroups' title=$t}
  <div class="pageinput">
    <div class="group_memberships clear">
      <table class="pagetable" id="grpmembers">
        <thead>
          <tr>
            <th class="pageicon"></th>
            <th>{_ld('admin','name')}</th>
            <th>{_ld('admin','description')}</th>
          </tr>
        </thead>
        <tbody>
          {foreach $groups as $onegroup}
          <tr>
            {strip}{$gid=$onegroup->id}
            <td>
            <input type="checkbox" id="g{$gid}" name="sel_groups[]" value="{$gid}"
{if $sel_groups && in_array($gid,$sel_groups)} checked="checked"
{elseif ($gid == 1 && $my_userid != 1)} disabled
{/if} /></td>
            <td>
            <label class="pagetext" for="g{$gid}">{$onegroup->name}</label></td>
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
  {$t=_ld('admin','copyusersettings')}<label class="pagetext" for="copyusrsettings">{$t}:</label>
  {cms_help 0='help' key='user_copysettings' title=$t}
  <div class="pageinput">
  <select name="copyusersettings" id="copyusrsettings">
    {html_options options=$users selected=$copyusersettings}   </select>
  </div>
</div>
{tab_end}
<div class="pageinput pregap">
  <button type="submit" name="submit" id="submit" class="adminsubmit icon check">{_ld('admin','submit')}</button>
  <button type="submit" name="cancel" class="adminsubmit icon cancel">{_ld('admin','cancel')}</button>
</div>
</form>
