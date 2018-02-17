<script type="text/javascript">
{literal}//<![CDATA[
$(document).ready(function() {
 $('.helpicon').click(function() {
  var x = $(this).attr('name');
  $('#'+x).dialog();
 });
});
{/literal}//]]>
</script>

<div class="pagecontainer">
  <form action="{$selfurl}{$urlext}" method="post">
    <div class="pageoverflow">
      <p class="pagetext">
        <label for="username">*{lang('username')}:</label>
        {cms_help key2='help_myaccount_username' title=lang('name')}
      </p>
      <p class="pageinput">
        <input type="text" name="user" id="username" maxlength="25" value="{$userobj->username}" class="standard" />
      </p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext">
        <label for="password">{lang('password')}:</label>
        {cms_help key2='help_myaccount_password' title=lang('password')}
      </p>
      <p class="pageinput">
        <input type="password" name="password" id="password" maxlength="100" value="" class="standard" />
      </p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext">
        <label for="passwordagain">{lang('passwordagain')}:</label>
        {cms_help key2='help_myaccount_passwordagain' title=lang('passwordagain')}
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
        <input type="text" name="firstname" id="firstname" maxlength="50" value="{$userobj->firstname}" class="standard" />
      </p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext">
        <label for="lastname">{lang('lastname')}:</label>
        {cms_help key2='help_myaccount_lastname' title=lang('lastname')}
      </p>
      <p class="pageinput">
        <input type="text" name="lastname" id="lastname" maxlength="50" value="{$userobj->lastname}" class="standard" />
      </p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext">
        <label for="email">{lang('email')}:</label>
        {cms_help key2='help_myaccount_email' title=lang('email')}
      </p>
      <p class="pageinput">
        <input type="text" name="email" id="email" size="40" maxlength="255" value="{$userobj->email}" class="standard" />
      </p>
    </div>
    <br />
    <div class="pageoverflow">
      <div class="pageinput">
        <button type="submit" name="submit_account" class="adminsubmit iconcheck">{lang('submit')}</button>
        <button type="submit" name="cancel" class="adminsubmit iconcancel">{lang('cancel')}</button>
      </div>
    </div>
  </form>
</div>
