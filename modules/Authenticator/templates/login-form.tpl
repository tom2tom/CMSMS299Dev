<form action="{$loginurl}" enctype="multipart/form-data" method="post">
  {if isset($csrf)}<input type="hidden" name="{$actionid}csrf" value="{$csrf}" />{/if}
  {$lost=isset($smarty.get.forgotpw)}
  {if isset($renewpw)}
  <input type="text" size="25" value="{$username}" disabled />
  <input type="hidden" name="{$actionid}username" value="{$username}" />
  <input type="hidden" name="{$actionid}renewpwform" value="1" />
  {else}
  {if $lost}
  <input type="hidden" name="{$actionid}lostpwform" value="1" />
  {$usernamefld='forgottenusername'}
  {else}
  {$usernamefld='username'}
  {/if}
  <input type="text" name="{$actionid}{$usernamefld}"{if !isset($smarty.post.password)} class="focus"{/if} placeholder="{_ld($_module,'username')}" size="25" value="" autofocus="autofocus" />
  {/if}
  {if !$lost}
  <input type="password" name="{$actionid}password"{if isset($smarty.post.password) || isset($renewpw) || !empty($iserr)} class="focus"{/if} placeholder="{_ld($_module,'password')}" size="25" maxlength="64" />
  {/if}
  {if !empty($changepwhash)}
  <input type="password" name="{$actionid}passwordagain" placeholder="{_ld($_module,'passwordagain')}" size="25" maxlength="64" />
  <input type="hidden" name="{$actionid}changepwhash" value="{$changepwhash}" />
  {/if}
  <div class="pageinput pregap">
    <button type="submit" class="loginsubmit" name="{$actionid}submit">{_ld($_module,'submit')}</button>
    {if ($lost || isset($renewpw))}
    <button type="submit" class="loginsubmit" name="{$actionid}cancel">{_ld($_module,'cancel')}</button>
    {else}<span id="forgotpw">
     <a href="{$forgoturl}" title="{_ld($_module,'recover_start')}">{_ld($_module,'lostpw')}</a>
    </span>
    {/if}
  </div>
</form>
