{block name=form}
<form method="post" action="login.php">
	<fieldset>{assign var='usernamefld' value='username'}
		{if isset($smarty.get.forgotpw)}{assign var='usernamefld' value='forgottenusername'}{/if}
		<label for="username">{'username'|lang}</label>
		<input id="username"{if !isset($smarty.post.username)} class="focus"{/if} placeholder="{'username'|lang}" name="{$usernamefld}" type="text" size="15" value="" autofocus="autofocus" />
	{if isset($smarty.get.forgotpw) && !empty($smarty.get.forgotpw)}
		<input type="hidden" name="forgotpwform" value="1" />
	{/if}
	{if !isset($smarty.get.forgotpw) && empty($smarty.get.forgotpw)}
		<label for="lbpassword">{'password'|lang}</label>
		<input id="lbpassword"{if !isset($smarty.post.lbpassword) or isset($error)} class="focus"{/if} placeholder="{'password'|lang}" name="password" type="password" size="15" maxlength="64" />
	{/if}
	{if isset($changepwhash) && !empty($changepwhash)}
		<label for="lbpasswordagain">{'passwordagain'|lang}</label>
		<input id="lbpasswordagain"  name="passwordagain" type="password" size="15" placeholder="{'passwordagain'|lang}" maxlength="64" />
		<input type="hidden" name="forgotpwchangeform" value="1" />
		<input type="hidden" name="changepwhash" value="{$changepwhash}" />
	{/if}
		<input class="loginsubmit" name="submit" type="submit" value="{'submit'|lang}" />
		<input class="loginsubmit" name="cancel" type="submit" value="{'cancel'|lang}" />
	</fieldset>
</form>
{/block}
