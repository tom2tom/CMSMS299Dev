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
  <form method="post" action="{$formurl}">
    <div class="pageoverflow">
      <p class="pagetext">
        <label for="username">*{lang('username')}:</label>&nbsp;{cms_help key2='help_myaccount_username' title=lang('name')}
      </p>
      <p class="pageinput"><input type="text" id="username" name="user" maxlength="25" value="{$userobj->username}" class="standard" /></p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext"><label for="password">{lang('password')}:</label>&nbsp;{cms_help key2='help_myaccount_password' title=lang('password')}</p>
      <p class="pageinput">
        <input type="password" id="password" name="password" maxlength="100" value="" />&nbsp;{lang('info_edituser_password')}
      </p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext"><label for="passwordagain">{lang('passwordagain')}:</label>&nbsp;{cms_help key2='help_myaccount_passwordagain' title=lang('passwordagain')}</p>
      <p class="pageinput"><input type="password" id="passwordagain" name="passwordagain" maxlength="100" value="" class="standard" />&nbsp;{lang('info_edituser_passwordagain')}</p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext"><label for="firstname">{lang('firstname')}:</label>&nbsp;{cms_help key2='help_myaccount_firstname' title=lang('firstname')}</p>
      <p class="pageinput"><input type="text" id="firstname" name="firstname" maxlength="50" value="{$userobj->firstname}" class="standard" /></p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext"><label for="lastname">{lang('lastname')}:</label>&nbsp;{cms_help key2='help_myaccount_lastname' title=lang('lastname')}</p>
      <p class="pageinput"><input type="text" id="lastname" name="lastname" maxlength="50" value="{$userobj->lastname}" class="standard" /></p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext"><label for="email">{lang('email')}:</label>&nbsp;{cms_help key2='help_myaccount_email' title=lang('email')}</p>
      <p class="pageinput"><input type="text" id="email" name="email" maxlength="255" value="{$userobj->email}" class="standard" /></p>
    </div>
    <br />
    <div class="pageoverflow">
      <div class="pageinput">
        <input type="submit" name="submit_account" value="{lang('submit')}" class="pagebutton" />
        <input type="submit" name="cancel" value="{lang('cancel')}" class="pagebutton" />
      </div>
    </div>
  </form>
</div>
