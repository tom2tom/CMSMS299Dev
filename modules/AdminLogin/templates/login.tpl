<!doctype html>
<html lang="{$lang_code}" dir="{$lang_dir}">
<head>
  <title>{_ld($_module,'loginto',{sitename})}</title>
  <base href="{$admin_url}/" />
  <meta charset="{$encoding}" />
  <meta name="generator" content="CMS Made Simple" />
  <meta name="robots" content="noindex, nofollow" />
  <meta name="viewport" content="initial-scale=1.0 maximum-scale=1.0" />
  <meta name="HandheldFriendly" content="true" />
  <link rel="shortcut icon" href="themes/assets/images/cmsms-favicon.ico" />
  {$header_includes|default:''}
</head>
<body>
  <div id="login-container">
    <div id="login-box" class="colbox{if !empty($error)} error{/if}">
      <div class="boxchild">
        {if empty($smarty.get.forgotpw)}<div class="rowbox expand">
          <div class="boxchild">
        {/if}
          <h1>{_ld($_module,'login_sitetitle',{sitename})}</h1>
          {if empty($smarty.get.forgotpw)}</div>
            <div class="boxchild">
            <a id="show-info" href="javascript:void()" title="{_ld($_module,'open')}"></a>
            </div>
          </div>
       {/if}
     </div>
     {$start_form}
     {if !empty($changepwhash)}<input type="hidden" name="{$actionid}forgotpwchangeform" value="1" />
      <input type="hidden" name="{$actionid}changepwhash" value="{$changepwhash}" />
     {elseif !empty($smarty.get.forgotpw)}
      <input type="hidden" name="{$actionid}forgotpwform" value="1" />
     {/if}
      <div class="boxchild">
        <label for="lbusername">{_ld($_module,'username')}</label>
        <input type="text" id="lbusername" class="focus" name="{$actionid}username" placeholder="{_ld($_module,'username')}" size="15" autofocus="autofocus" />
      </div>
      {if empty($smarty.get.forgotpw)}
       <div class="boxchild">
        <label for="lbpassword">{_ld($_module,'password')}</label>
        <input type="password" id="lbpassword" class="focus" name="{$actionid}password" placeholder="{_ld($_module,'password')}" size="15" maxlength="64" />
      </div>
      {/if}
      {if !empty($changepwhash)}
      <div class="boxchild">
        <label for="lbpasswordagain">{_ld($_module,'passwordagain')}</label>
        <input type="password" id="lbpasswordagain" name="{$actionid}passwordagain" placeholder="{_ld($_module,'passwordagain')}" size="15" maxlength="64" />
      </div>
      {/if}
      {strip}<div class="boxchild" style="margin-top:10px;">
      {if !empty($smarty.get.forgotpw)}
      <div class="message info">{_ld($_module,'info_recover')}</div>
      {/if}
      {if !empty($error)}<div class="message error">{$error}</div>
      {else if !empty($warning)}<div class="message warning">{$warning}</div>
      {elseif !empty($message)}<div class="message success">{$message}</div>
      {/if}
      {if !empty($changepwhash)}<div class="message info">{_ld($_module,'passwordchangedlogin')}</div>
      {/if}
      </div>{/strip}
      <div class="boxchild" style="margin-top:10px;">
        <button type="submit" class="loginsubmit" name="{$actionid}submit">{_ld($_module,'submit')}</button>
        {if !empty($smarty.get.forgotpw)}<button type="submit" class="loginsubmit" name="{$actionid}cancel">{_ld($_module,'cancel')}</button>
        {/if}
      </div>
      </form>
      <div class="boxchild">
        {if empty($smarty.get.forgotpw)}<div class="rowbox expand">
          <div class="boxchild">
        {/if}
           <a id="goback" href="{root_url}" title="{_ld($_module,'goto',{sitename})}"></a>
        {if empty($smarty.get.forgotpw)}</div>
            <div class="boxchild">
              <a id="forgotpw" href="{$forgot_url}" title="{_ld($_module,'recover_start')}">{_ld($_module,'lostpw')}</a>
            </div>
          </div>
        {/if}
      </div>
    </div>{* login-box *}
    <div id="logo">
      <a href="https://www.cmsmadesimple.org"><img src="themes/assets/images/cmsms-logotext-dark.svg" alt="CMS Made Simple&trade;" /></a>
    </div>
    <div id="maininfo" style="display:none;">
      <a id="hide-info" href="javascript:void()" title="{_ld($_module,'close')}"></a>
      <h2>{_ld($_module,'login_info_title')}</h2>
      {_ld($_module,'login_info')}<br />
      {_ld($_module,'login_info_params')}
      <p class="warning">{_ld($_module,'login_info_ipandcookies')}</p>
    </div>{* maininfo *}
  </div>{* login-container *}
  {$bottom_includes|default:''}
</body>
</html>
