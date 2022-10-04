<!DOCTYPE html>
<html lang="{$lang_code}" dir="{$lang_dir}">
<head>
  <title>{_ld($_module,'loginto',{sitename})}</title>
  <meta charset="{$encoding}">
  <meta name="generator" content="CMS Made Simple">
  <meta name="robots" content="noindex, nofollow">
  <meta name="viewport" content="initial-scale=1.0 maximum-scale=1.0">
  <meta name="HandheldFriendly" content="true">
  <base href="{$admin_url}/">
  <link rel="shortcut icon" href="themes/assets/images/cmsms-favicon.ico">
  {$header_includes|default:''}
</head>
<body>{$lost=isset($smarty.get.forgotpw)}
  <div id="login-container">
    <div id="login-box" class="colbox{if !empty($error)} error{/if}">
      <div class="boxchild">
        {if !$lost}<div class="rowbox expand">
          <div class="boxchild">
        {/if}
          <h1>{_ld($_module,'login_sitetitle',{sitename})}</h1>
        {if !$lost}</div>
            <div class="boxchild">
            <a id="show-info" href="javascript:void()" title="{_ld($_module,'open')}"></a>
            </div>
          </div>
        {/if}
      </div>
     {$start_form}
      {if isset($csrf)}<input type="hidden" name="{$actionid}csrf" value="{$csrf}">{/if}
      <div class="boxchild">
        <label for="lbusername">{_ld($_module,'username')}</label>
        {if isset($renewpw)}
        <input type="text" id="lbusername" size="25" value="{$username}" disabled>
        <input type="hidden" name="{$actionid}renewpwform" value="1">
        <input type="hidden" name="{$actionid}username" value="{$username}">
        {else}
        <input type="text" id="lbusername" class="focus" name="{$actionid}username" placeholder="{_ld($_module,'username')}" size="25" autofocus>
        {if $lost}
        <input type="hidden" name="{$actionid}lostpwform" value="1">
        {/if}
        {/if}
      </div>
      {if !$lost}
       <div class="boxchild">
        <label for="lbpassword">{_ld($_module,'password')}</label>
        <input type="password" id="lbpassword" class="focus" name="{$actionid}password" placeholder="{_ld($_module,'password')}" size="25" maxlength="72">
      </div>
      {/if}
      {if !empty($changepwhash)}
      <div class="boxchild">
        <label for="lbpasswordagain">{_ld($_module,'passwordagain')}</label>
        <input type="password" id="lbpasswordagain" name="{$actionid}passwordagain" placeholder="{_ld($_module,'passwordagain')}" size="25" maxlength="64">
        <input type="hidden" name="{$actionid}changepwhash" value="{$changepwhash}">
      </div>
      {/if}
      {strip}<div class="boxchild" style="margin-top:10px;">
      {if $lost}<div class="message info">{_ld($_module,'info_recover')}</div>
      {elseif isset($renewpw)}<div class="message warning">{_ld($_module,'info_replace')}</div>
{*    {elseif !empty($changepwhash)}<div class="message info">{_ld($_module,'info_TODOpasswordchangedlogin')}</div>*}{/if}
      {if !empty($errmessage)}<div class="message error">{$errmessage}</div>{/if}
      {if !empty($warnmessage)}<div class="message warning">{$warnmessage}</div>{/if}
      {if !empty($infomessage)}<div class="message info">{$infomessage}</div>{/if}
      </div>{/strip}
      <div class="boxchild" style="margin-top:10px;">
        <button type="submit" class="loginsubmit" name="{$actionid}submit">{_ld($_module,'submit')}</button>
        {if ($lost || isset($renewpw))}
        <button type="submit" class="loginsubmit" name="{$actionid}cancel">{_ld($_module,'cancel')}</button>
        {/if}
      </div>
      </form>
      <div class="boxchild">
          <div class="rowbox expand">
            <div class="boxchild">
             <a id="goback" href="{root_url}" title="{_ld($_module,'goto',{sitename})}"></a>
            </div>
            {if !($lost || isset($renewpw))}
            <div class="boxchild">
             <a id="forgotpw" href="{$forgot_url}" title="{_ld($_module,'recover_start')}">{_ld($_module,'lostpw')}</a>
            </div>
            {/if}
          </div>
      </div>
    </div>{* login-box *}
    <div id="logo">
      <a href="https://www.cmsmadesimple.org"><img src="themes/assets/images/cmsms-logotext-dark.svg" alt="CMS Made Simple&trade;"></a>
    </div>
    <a id="hide-info" href="javascript:void()" title="{_ld($_module,'close')}"></a>
    <div id="maininfo" class="message info" style="display:none">
{*    <p>{_ld($_module,'login_info_params',"<strong>{$smarty.server.HTTP_HOST}</strong>")}</p>*}
      <p>{_ld($_module,'login_info_params')}</p>
      <p>{_ld($_module,'info_cookies')}</p>
    </div>{* maininfo *}
  </div>{* login-container *}
  {$bottom_includes|default:''}
</body>
</html>
