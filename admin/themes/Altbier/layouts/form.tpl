{block name=form}
<form class="w-100" method="post" action="login.php">
 {if isset($csrf)}<input type="hidden" name="csrf" value="{$csrf}">{/if}
 <fieldset>{$lost=isset($smarty.get.forgotpw)}
  <div class="form-group">
  <label class="sr-only" for="username">{lang('username')}</label>
  {if isset($renewpw)}
  <input id="username" type="text" size="25" value="{$username}" disabled>
  <input type="hidden" name="username" value="{$username}">
  <input type="hidden" name="renewpwform" value="1">
  {else}
  {if $lost}
  <input type="hidden" name="lostpwform" value="1">
  {$usernamefld='forgottenusername'}
  {else}
  {$usernamefld='username'}
  {/if}
  <input id="username" type="text"{if !isset($smarty.post.username)} class="focus11"{/if} placeholder="{lang('username')}" name="{$usernamefld}" size="25" value="" autofocus>
  {/if}
  </div>
 {if !$lost}
  <div class="form-group">
  <label class="sr-only" for="lbpassword">{lang('password')}</label>
  <input id="lbpassword" type="password"{if !isset($smarty.post.lbpassword) || isset($error)} class="focus11"{/if} placeholder="{lang('password')}" name="password" size="25" maxlength="72">
  </div>
 {/if}
 {if !empty($changepwhash)}
 <div class="form-group">
  <label class="sr-only" for="lbpasswordagain">{lang('passwordagain')}</label>
  <input id="lbpasswordagain" name="passwordagain" type="password" placeholder="{lang('passwordagain')}" size="25" maxlength="72">
  <input type="hidden" name="changepwhash" value="{$changepwhash}">
 </div>
 {/if}
 <div class="row">
  <div class="cell col-12 small mt-3 p-0">
  <button type="submit" class="loginsubmit" name="submit">{lang('submit')}</button>
  </div>
 {if ($lost || isset($renewpw))}
  <div class="cell col-12 small mt-3 p-0">
  <button type="submit" class="loginsubmit" name="cancel">{lang('cancel')}</button>
  </div>
 {/if}
 </div>
 </fieldset>
</form>
{/block}
