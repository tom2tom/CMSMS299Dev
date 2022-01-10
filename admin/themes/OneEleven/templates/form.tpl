{block name=form}
<form method="post" action="login.php">
	{if isset($csrf)}<input type="hidden" name="csrf" value="{$csrf}" />{/if}
	<fieldset>{assign var='lost' value=isset($smarty.get.forgotpw)}
		<label for="username">{'username'|lang}</label>
	{if isset($renewpw)}
		<input id="username" type="text" size="25" value="{$username}" disabled />
		<input type="hidden" name="username" value="{$username}" />
		<input type="hidden" name="renewpwform" value="1" />
	{else}
		{if $lost}
		<input type="hidden" name="lostpwform" value="1" />
		{assign var='usernamefld' value='forgottenusername'}
		{else}
		{assign var='usernamefld' value='username'}
		{/if}
		<input id="username" type="text"{if !isset($smarty.post.username)} class="focus"{/if} placeholder="{'username'|lang}" name="{$usernamefld}" size="25" value="" autofocus="autofocus" />
	{/if}
	{if !$lost}
		<label for="lbpassword">{'password'|lang}</label>
		<input id="lbpassword"{if !isset($smarty.post.lbpassword) || isset($error)} class="focus"{/if} placeholder="{'password'|lang}" name="password" type="password" size="25" maxlength="64" />
	{/if}
	{if !empty($changepwhash)}
		<label for="lbpasswordagain">{'passwordagain'|lang}</label>
		<input id="lbpasswordagain"  name="passwordagain" type="password" placeholder="{'passwordagain'|lang}" size="25" maxlength="64" />
		<input type="hidden" name="changepwhash" value="{$changepwhash}" />
	{/if}
		<input class="loginsubmit" name="submit" type="submit" value="{'submit'|lang}" />
	{if ($lost || isset($renewpw)) }
		<input class="loginsubmit" name="cancel" type="submit" value="{'cancel'|lang}" />
	{/if}
	</fieldset>
</form>
{/block}
