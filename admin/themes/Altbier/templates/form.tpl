{block name=form}
<form class="w-100" method="post" action="login.php">
	<fieldset>
{if isset($smarty.get.forgotpw)}{$usernamefld='forgottenusername'}{else}{$usernamefld='username'}{/if}
		<div class="form-group">
		<label class="sr-only" for="username">{'username'|lang}</label>
		<input id="username"{if !isset($smarty.post.username)} class="focus11"{/if} placeholder="{'username'|lang}" name="{$usernamefld}" type="text" value="" autofocus="autofocus" />
		</div>
	{if !empty($smarty.get.forgotpw)}
		<input type="hidden" name="forgotpwform" value="1" />
	{else}
		<div class="form-group">
		<label class="sr-only" for="lbpassword">{'password'|lang}</label>
		<input id="lbpassword"{if !isset($smarty.post.lbpassword) or isset($error)} class="focus11"{/if} placeholder="{'password'|lang}" name="password" type="password" maxlength="64" />
		</div>
	{/if}
	{if !empty($changepwhash)}
	<div class="form-group">
		<label class="sr-only" for="lbpasswordagain">{'passwordagain'|lang}</label>
		<input id="lbpasswordagain" name="passwordagain" type="password" placeholder="{'passwordagain'|lang}" maxlength="64" />
		<input type="hidden" name="forgotpwchangeform" value="1" />
		<input type="hidden" name="changepwhash" value="{$changepwhash}" />
	</div>
	{/if}
	<div class="row">
		<div class="mt-3 col-12 col-sm-6 p-0 text-left">
		<button type="submit" class="loginsubmit" name="cancel">{'cancel'|lang}</button>
		</div>
		<div class="mt-3 col-12 col-sm-6 p-0 text-left text-sm-right">
		<button type="submit" class="loginsubmit" name="submit">{'submit'|lang}</button>
		</div>
	</div>
	</fieldset>
</form>
{/block}
