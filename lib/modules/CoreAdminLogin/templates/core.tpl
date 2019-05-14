<form action="{$loginurl}" enctype="multipart/form-data" method="post">
  <input type="hidden" name="{$actionid}csrf" value="{$csrf}" />
  {if isset($smarty.get.forgotpw)}{$usernamefld='forgottenusername'}{else}{$usernamefld='username'}{/if}
  <input type="text" name="{$actionid}{$usernamefld}"{if !isset($smarty.post.password)} class="focus"{/if} placeholder="{$mod->Lang('username')}" size="25" value="" autofocus="autofocus" />
  {if isset($smarty.get.forgotpw)}
  <input type="hidden" name="{$actionid}forgotpwform" value="1" />
  {else}
  <input type="password" name="{$actionid}password"{if isset($smarty.post.password) || !empty($iserr)} class="focus"{/if} placeholder="{$mod->Lang('password')}" size="25" maxlength="100" />
  {/if}
  {if !empty($changepwhash)}
  <input type="password" name="{$actionid}passwordagain" size="25" placeholder="{$mod->Lang('passwordagain')}" maxlength="100" />
  <input type="hidden" name="{$actionid}forgotpwchangeform" value="1" />
  <input type="hidden" name="{$actionid}changepwhash" value="{$changepwhash}" />
  {/if}
  <div class="pageinput pregap">
    <button type="submit" name="{$actionid}submit" class="loginsubmit">{$mod->Lang('submit')}</button>
    {if isset($smarty.get.forgotpw) || !empty($changepwhash)}
    <button type="submit" name="{$actionid}cancel" class="loginsubmit">{$mod->Lang('cancel')}</button>
    {else}<span id="forgotpw">
     <a href="{$forgoturl}" title="{$mod->Lang('recover_start')}">{$mod->Lang('lostpw')}</a>
    </span>
    {/if}
  </div>
</form>
