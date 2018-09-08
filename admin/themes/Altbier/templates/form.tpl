{block name=form}
<form class="w-100" method="post" action="login.php">
	<fieldset>
		{assign var='usernamefld' value='username'}
		{if isset($smarty.get.forgotpw)}{assign var='usernamefld' value='forgottenusername'}{/if}
		<div class="form-group">
		<label class="sr-only" for="username">{'username'|lang}</label>
		<input id="username"{if !isset($smarty.post.username)} class="focus11"{/if} placeholder="{'username'|lang}" name="{$usernamefld}" type="text" value="" autofocus="autofocus" />
		</div>
	{if isset($smarty.get.forgotpw) && !empty($smarty.get.forgotpw)}
		<input type="hidden" name="forgotpwform" value="1" />
	{/if}
	{if !isset($smarty.get.forgotpw) && empty($smarty.get.forgotpw)}
	<div class="form-group">
		<label class="sr-only" for="lbpassword">{'password'|lang}</label>
		<input id="lbpassword"{if !isset($smarty.post.lbpassword) or isset($error)} class="focus11"{/if} placeholder="{'password'|lang}" name="password" type="password" maxlength="100"/>
	</div>
	{/if}
	{if isset($changepwhash) && !empty($changepwhash)}
	<div class="form-group">
		<label class="sr-only" for="lbpasswordagain">{'passwordagain'|lang}</label>
		<input id="lbpasswordagain"  name="passwordagain" type="password" placeholder="{'passwordagain'|lang}" maxlength="100" />
		<input type="hidden" name="forgotpwchangeform" value="1" />
		<input type="hidden" name="changepwhash" value="{$changepwhash}" />
	</div>
	{/if}
	<div class="row">
		<div class="mt-3 col-12 col-sm-6 p-0 text-left">
		<input class="loginsubmit" name="cancel" type="submit" value="{'cancel'|lang}" />
		</div>
		<div class="mt-3 col-12 col-sm-6 p-0 text-left text-sm-right">
		<input class="loginsubmit" name="submit" type="submit" value="{'submit'|lang}" />
		</div>
	</div>
	</fieldset>
</form>
{/block}
