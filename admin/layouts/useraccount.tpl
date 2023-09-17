<form action="{$selfurl}" enctype="multipart/form-data" method="post">
  {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}">
{/foreach}
  <div class="pageoverflow">
    {$t=_la('username')}<label class="pagetext" for="username">*&nbsp;{$t}:</label>
    {cms_help realm='help' key='user_username' title=$t}
    <div class="pageinput">
      <input type="text" id="username" name="user" maxlength="25" value="{$userobj->username}">
    </div>
  </div>
  <div class="pageoverflow">
    {$t=_la('password')}<label class="pagetext" for="password">{$t}:</label>
    {cms_help realm='help' key='user_password' title=$t}
    <div class="pageinput">
      <input type="text" id="password" name="password" maxlength="64" value="">
    </div>
  </div>
  <div class="pageoverflow">
    {$t=_la('passwordagain')}<label class="pagetext" for="passagain">{$t}:</label>
    {cms_help realm='help' key='user_passwordagain' title=$t}
    <div class="pageinput">
      <input type="text" id="passagain" name="passwordagain" maxlength="64" value="">
    </div>
  </div>
  <div class="pageoverflow">
    {$t=_la('firstname')}<label class="pagetext" for="firstname">{$t}:</label>
    {cms_help realm='help' key='user_firstname' title=$t}
    <div class="pageinput">
      <input type="text" id="firstname" name="firstname" maxlength="50" value="{$userobj->firstname}">
    </div>
  </div>
  <div class="pageoverflow">
    {$t=_la('lastname')}<label class="pagetext" for="lastname">{$t}:</label>
    {cms_help realm='help' key='user_lastname' title=$t}
    <div class="pageinput">
      <input type="text" id="lastname" name="lastname" maxlength="50" value="{$userobj->lastname}">
    </div>
  </div>
  <div class="pageoverflow">
    {$t=_la('email')}<label class="pagetext" for="email">{$t}:</label>
    {cms_help realm='help' key='user_email' title=$t}
    <div class="pageinput">
      <input type="text" id="email" name="email" size="40" maxlength="255" value="{$userobj->email}">
    </div>
  </div>
  <div class="pageinput pregap">
    <button type="submit" name="submit" class="adminsubmit icon apply">{_la('apply')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{_la('cancel')}</button>
  </div>
</form>
