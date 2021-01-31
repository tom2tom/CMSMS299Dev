<form action="{$loginurl}" enctype="multipart/form-data" method="post">
  <input type="hidden" name="{$actionid}csrf" value="{$csrf}" />
  {if isset($renewpw)}
  <input type="hidden" name="{$actionid}renewpwform" value="1" />
  <input type="hidden" name="{$actionid}username" value="{$username}" />
  <input type="text" size="25" value="{$username}" disabled />
  {else}
    {if isset($smarty.get.forgotpw)}
      {$usernamefld='forgottenusername'}
    {else}
      {$usernamefld='username'}
    {/if}
    <input type="text" name="{$actionid}{$usernamefld}"{if !isset($smarty.post.password)} class="focus"{/if} placeholder="{$mod->Lang('username')}" size="25" value="" autofocus="autofocus" />
  {/if}
  {if isset($smarty.get.forgotpw)}
  <input type="hidden" name="{$actionid}forgotpwform" value="1" />
  {else}
  <input type="password" name="{$actionid}password"{if isset($smarty.post.password) || isset($renewpw) || !empty($iserr)} class="focus"{/if} placeholder="{$mod->Lang('password')}" size="25" maxlength="64" />
  {/if}
  {if isset($renewpw) || !empty($changepwhash)}
  <input type="password" name="{$actionid}passwordagain" size="25" placeholder="{$mod->Lang('passwordagain')}" maxlength="64" />
   {if !isset($renewpw)}
  <input type="hidden" name="{$actionid}forgotpwchangeform" value="1" />
  <input type="hidden" name="{$actionid}changepwhash" value="{$changepwhash}" />
   {/if}
  {/if}
  <div class="pageinput pregap">
    <button type="submit" class="loginsubmit" name="{$actionid}submit">{$mod->Lang('submit')}</button>
    {if isset($smarty.get.forgotpw) || isset($renewpw) || !empty($changepwhash)}
    <button type="submit" class="loginsubmit" name="{$actionid}cancel">{$mod->Lang('cancel')}</button>
    {elseif !isset($renewpw)}<span id="forgotpw">
     <a href="{$forgoturl}" title="{$mod->Lang('recover_start')}">{$mod->Lang('lostpw')}</a>
    </span>
    {/if}
  </div>
</form>
