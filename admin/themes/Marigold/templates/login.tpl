<!doctype html>
<html>

<head>
  <meta charset="{$encoding}" />
  <title>{lang('logintitle')} - {sitename}</title>
  <base href="{$config.admin_url}/" />
  <meta name="generator" content="CMS Made Simple - Copyright (C) 2004-2018 - All rights reserved" />
  <meta name="robots" content="noindex, nofollow" />
  <meta name="viewport" content="initial-scale=1.0 maximum-scale=1.0 user-scalable=no" />
  <meta name="HandheldFriendly" content="True" />
  <link rel="shortcut icon" href="{$config.admin_url}/themes/Marigold/images/favicon/cmsms-favicon.ico" />
  <link rel="stylesheet" type="text/css" href="{$config.admin_url}/themes/Marigold/css/style.css" />{* TODO if RTL *}
<!-- html5 for old IE -->
<!--[if lt IE 9]>
 <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
 {$jsinc}
</head>

<body id="login">
  <div id="wrapper">
    <div class="login-container">
      <div class="login-box cf" {if isset($error)} id="error" {/if}>
        <div class="logo">
          <img src="{$config.admin_url}/themes/Marigold/images/layout/cmsms_login_logo.png" width="180" height="36" alt="CMS Made Simple&trade;" />
        </div>
        <div class="info-wrapper">
          <aside class="info">
            <h2>{lang('login_info_title')}</h2>
            {lang('login_info')}
            {lang('login_info_params')}
            <p style="margin-left:2em;font-weight:bold">{$smarty.server.HTTP_HOST}</p>
            <div class="pagewarn" style="padding:5px;">{lang('warn_admin_ipandcookies')}</div>
          </aside>
          <a href="#" title="{lang('open')}/{lang('close')}" class="toggle-info">{lang('open')}/{lang('close')}</a>
        </div>
        <h1>{if isset($smarty.get.forgotpw)}{lang('recoversitetitle',{sitename})}{else}{lang('loginsitetitle',{sitename})}{/if}</h1>
        <form method="post" action="login.php">
          <fieldset>
            {$usernamefld = 'username'} {if isset($smarty.get.forgotpw)}{$usernamefld ='forgottenusername'}{/if}
            <label for="lbusername">{lang('username')}</label>
            <input id="lbusername" {if !isset($smarty.post.lbusername)} class="focus" {/if} placeholder="{lang('username')}" name="{$usernamefld}" type="text" size="15" value="" autofocus="autofocus" /> {if isset($smarty.get.forgotpw) && !empty($smarty.get.forgotpw)}
            <input type="hidden" name="forgotpwform" value="1" /> {/if} {if !isset($smarty.get.forgotpw) && empty($smarty.get.forgotpw)}
            <label for="lbpassword">{lang('password')}</label>
            <input id="lbpassword" {if !isset($smarty.post.lbpassword) or isset($error)} class="focus" {/if} placeholder="{lang('password')}" name="password" type="password" size="15" maxlength="100" /> {/if} {if isset($changepwhash) && !empty($changepwhash)}
            <label for="lbpasswordagain">{lang('passwordagain')}</label>
            <input id="lbpasswordagain" name="passwordagain" type="password" size="15" placeholder="{lang('passwordagain')}" maxlength="100" />
            <input type="hidden" name="forgotpwchangeform" value="1" />
            <input type="hidden" name="changepwhash" value="{$changepwhash}" /> {/if}
            <button type="submit" name="loginsubmit" class="loginsubmit">{lang('submit')}</button>
            <button type="submit" name="logincancel" class="loginsubmit">{lang('cancel')}</button>
          </fieldset>
        </form>
        {if isset($smarty.get.forgotpw) && !empty($smarty.get.forgotpw)}
        <div class="pageinfo" style="padding:10px;">{lang('forgotpwprompt')}</div>
        {/if} {if isset($error)}
        <div class="pageerror" style="padding:10px;">{$error}</div>
        {/if} {if isset($warninglogin)}
        <div class="pagewarn" style="padding:10px;">{$warninglogin}</div>
        {/if} {if isset($acceptlogin)}<div class="pagesuccess">{$acceptlogin}</div> TODO
        {/if} {if isset($changepwhash) && !empty($changepwhash)}
        <div class="pageinfo" style="padding:10px;">{lang('passwordchange')}</div>
        {/if}
        <p class="forgotpw">
          <a href="login.php?forgotpw=1" title="{lang('recover_start')}">{lang('lostpw')}</a>
        </p>
        <p class="goto">
          <a href="{root_url}" title="{lang('goto')} {sitename}"></a>
        </p>
      </div>
    </div>
  </div>
</body>
{$pagelast|default:''}
</html>
