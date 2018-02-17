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
  <link rel="stylesheet" href="loginstyle.php" />
  <!-- learn IE html5 -->
  <!--[if lt IE 9]>
   <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
  <![endif]-->
  {cms_jquery exclude="jquery.ui.nestedSortable-1.3.4.js,jquery.json-2.2.js" append="`$config.admin_url`/themes/Marigold/includes/login.js"}
</head>

<body id="login">
  <div id="wrapper">
    <div class="login-container">
      <div class="login-box cf" {if isset($error)} id="error" {/if}>
        <div class="logo">
          <img src="{$config.admin_url}/themes/Marigold/images/layout/cmsms_login_logo.png" width="180" height="36" alt="CMS Made Simple&trade;" />
        </div>
        <div class="info-wrapper open">
          <aside class="info">
            <h2>{lang('login_info_title')}</h2>
            <p>{lang('login_info')}</p>
            {lang('login_info_params')}
            <p><strong>({$smarty.server.HTTP_HOST})</strong></p>
            <p class="warning">{lang('warn_admin_ipandcookies')}</p>
          </aside>
          <a href="#" title="{lang('open')}/{lang('close')}" class="toggle-info">{lang('open')}/{lang('close')}</a>
        </div>
        <header>
          <h1>{lang('logintitle')}</h1>
        </header>
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
        <div class="message warning">
          {lang('forgotpwprompt')}
        </div>
        {/if} {if isset($error)}
        <div class="message error">
          {$error}
        </div>
        {/if} {if isset($warninglogin)}
        <div class="message warning">
          {$warninglogin}
        </div>
        {/if} {if isset($acceptlogin)}
        <div class="message success">
          {$acceptlogin}
        </div>
        {/if} {if isset($changepwhash) && !empty($changepwhash)}
        <div class="warning message">
          {lang('passwordchange')}
        </div>
        {/if} <a href="{root_url}" title="{lang('goto')} {sitename}"><img class="goback" width="16" height="16" src="{$config.admin_url}/themes/Marigold/images/layout/goback.png" alt="{lang('goto')} {sitename}" /> </a>
        <p class="forgotpw">
          <a href="login.php?forgotpw=1">{lang('lostpw')}</a>
        </p>
      </div>
      <footer>
        <small class="copyright">Copyright &copy; <a rel="external" href="http://www.cmsmadesimple.org">CMS Made Simple&trade;</a></small>
      </footer>
    </div>
  </div>
</body>

</html>
