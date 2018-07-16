<form action="login.php" method="post">
  <input type="hidden" name="csrf" value="{$csrf}" />
  {if isset($smarty.get.forgotpw)}{$usernamefld='forgottenusername'}{else}{$usernamefld='username'}{/if}
  <input type="text" name="{$usernamefld}"{if !isset($smarty.post.password)} class="focus"{/if} placeholder="{$mod->Lang('username')}" size="25" value="" autofocus="autofocus" />
  {if isset($smarty.get.forgotpw)}
  <input type="hidden" name="forgotpwform" value="1" />
  {else}
  <input type="password" name="password"{if isset($smarty.post.password) || !empty($errmessage)} class="focus"{/if} placeholder="{$mod->Lang('password')}" size="25" maxlength="100" />
  {/if}
  {if !empty($changepwhash)}
  <input type="password" name="passwordagain" size="25" placeholder="{$mod->Lang('passwordagain')}" maxlength="100" />
  <input type="hidden" name="forgotpwchangeform" value="1" />
  <input type="hidden" name="changepwhash" value="{$changepwhash}" />
  {/if}
  <div class="pageinput pregap">
    <button type="submit" name="submit" class="loginsubmit">{$mod->Lang('submit')}</button>
    {if isset($smarty.get.forgotpw)}
    <button type="submit" name="cancel" class="loginsubmit">{$mod->Lang('cancel')}</button>
    {/if}
    {if !isset($smarty.get.forgotpw)}<span id="forgotpw">
     <a href="login.php?forgotpw=1" title="{$mod->Lang('recover_start')}">{$mod->Lang('lostpw')}</a>
    </span>{/if}
  </div>
</form>
