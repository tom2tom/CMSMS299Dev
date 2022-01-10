{block name=form}
<form class="w-100" method="post" action="login.php">
 {if isset($csrf)}<input type="hidden" name="csrf" value="{$csrf}" />{/if}
 <fieldset>{$lost=isset($smarty.get.forgotpw)}
  <div class="form-group">
  <label class="sr-only" for="username">{'username'|lang}</label>
  {if isset($renewpw)}
  <input id="username" type="text" size="25" value="{$username}" disabled />
  <input type="hidden" name="username" value="{$username}" />
  <input type="hidden" name="renewpwform" value="1" />
  {else}
  {if $lost}
  <input type="hidden" name="lostpwform" value="1" />
  {$usernamefld='forgottenusername'}
  {else}
  {$usernamefld='username'}
  {/if}
  <input id="username" type="text"{if !isset($smarty.post.username)} class="focus11"{/if} placeholder="{'username'|lang}" name="{$usernamefld}" value="" autofocus="autofocus" size="25" />
  {/if}
  </div>
 {if !$lost}
  <div class="form-group">
  <label class="sr-only" for="lbpassword">{'password'|lang}</label>
  <input id="lbpassword"{if !isset($smarty.post.lbpassword) || isset($error)} class="focus11"{/if} placeholder="{'password'|lang}" name="password" type="password" size="25" maxlength="64" />
  </div>
 {/if}
 {if !empty($changepwhash)}
 <div class="form-group">
  <label class="sr-only" for="lbpasswordagain">{'passwordagain'|lang}</label>
  <input id="lbpasswordagain" name="passwordagain" type="password" placeholder="{'passwordagain'|lang}" size="25" maxlength="64" />
  <input type="hidden" name="changepwhash" value="{$changepwhash}" />
 </div>
 {/if}
 <div class="row">
  <div class="mt-3 col-12 col-sm-6 p-0 text-left">
  <button type="submit" class="loginsubmit" name="submit">{'submit'|lang}</button>
  </div>
 {if ($lost || isset($renewpw))}
  <div class="mt-3 col-12 col-sm-6 p-0 text-left text-sm-right">
  <button type="submit" class="loginsubmit" name="cancel">{'cancel'|lang}</button>
  </div>
 {/if}
 </div>
 </fieldset>
</form>
{/block}
