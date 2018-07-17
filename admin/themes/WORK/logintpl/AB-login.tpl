<!doctype html>
<html>
	<head>
		<meta charset="{$encoding}" />
		<title>{'logintitle'|lang} - {sitename}</title>
		<base href="{$config.admin_url}/" />
		<meta name="generator" content="CMS Made Simple - Copyright (C) 2004-2018 - All rights reserved" />
		<meta name="robots" content="noindex, nofollow" />
		<meta name="viewport" content="initial-scale=1.0 maximum-scale=1.0" />
		<meta name="HandheldFriendly" content="True"/>
		<link rel="shortcut icon" href="{$config.admin_url}/themes/Altbier/images/favicon/cmsms-favicon.ico"/>
		<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,400i,600,600i" rel="stylesheet">
		<link rel="stylesheet" href="{$config.admin_url}/themes/Altbier/css/bootstrap_reboot-grid.min.css">
		<link rel="stylesheet" href="loginstyle.php">
		<link rel="stylesheet" href="{$config.admin_url}/themes/Altbier/css/default-cmsms/jquery-ui-1.10.4.custom.min.css">
		<script>FontAwesomeConfig = { searchPseudoElements: true }</script>
		<script defer src="https://use.fontawesome.com/releases/v5.0.8/js/all.js"></script>
		{cms_jquery exclude="jquery.ui.nestedSortable-1.3.4.js,jquery.json-2.2.js" append="`$config.admin_url`/themes/Altbier/includes/login.js"}
	</head>
	<body id="login">
		<div class="container py-5">
				<div class="logo row">
					<div class="col-12 mx-auto text-center"><img class="img-fluid" src="{$config.admin_url}/themes/Altbier/images/layout/cmsms_login_logo.png" width="310" height="85" alt="CMS Made Simple&trade;" /></div>
				</div>
				<div class="row">
					<div class="col-12">
						<div class="login-box mx-auto p-2 p-sm-4"{if isset($error)} id="error"{/if}>
								<div class="col-12 info-wrapper open">
									<aside class="p-4 info">
										<h2>{'login_info_title'|lang}</h2>
										<p>{'login_info'|lang}</p>
										{'login_info_params'|lang}
										<p class="pl-4"><strong>({$smarty.server.HTTP_HOST})</strong></p>
										<div class="warning-message mt-3 py-3 row">
											<div class="col-2"><i aria-hidden="true" class="fas fa-2x fa-exclamation-circle"></i> </div>
											<p class="col-10">{'warn_admin_ipandcookies'|lang}</p>
										</div>
									</aside>
								</div>
								
							<header class="col-12 text-center">
								<h1><a href="#" title="{'open'|lang}/{'close'|lang}" class="toggle-info"><span tabindex="0" role="note" aria-label="{'login_info_title'|lang}" class="fas fa-info-circle"></span><span class="sr-only">{'open'|lang}/{'close'|lang}</span></a> {'logintitle'|lang}</h1>
							</header>
							<div class="col-12 mx-auto text-center">
							<form class="w-100" method="post" action="login.php">
								<fieldset>
									{assign var='usernamefld' value='username'}
									{if isset($smarty.get.forgotpw)}{assign var='usernamefld' value='forgottenusername'}{/if}
									<div class="form-group">
									<label class="sr-only" for="lbusername">{'username'|lang}</label>
									<input id="lbusername"{if !isset($smarty.post.lbusername)} class="focus11"{/if} placeholder="{'username'|lang}" name="{$usernamefld}" type="text" value="" autofocus="autofocus" />
									</div>
								{if isset($smarty.get.forgotpw) && !empty($smarty.get.forgotpw)}
									<input type="hidden" name="forgotpwform" value="1" />
								{/if}
								{if !isset($smarty.get.forgotpw) && empty($smarty.get.forgotpw)}
								<div class="form-group">
									<label class="sr-only" for="lbpassword">{'password'|lang}</label>
									<input id="lbpassword"{if !isset($smarty.post.lbpassword) or isset($error)} class="focus11"{/if} placeholder="{'password'|lang}" name="password" type="password" maxlength="100"/>
								</div>
								{/if}
								{if isset($changepwhash) && !empty($changepwhash)}
								<div class="form-group">
									<label class="sr-only" for="lbpasswordagain">{'passwordagain'|lang}</label>
									<input id="lbpasswordagain"  name="passwordagain" type="password" placeholder="{'passwordagain'|lang}" maxlength="100" />
									<input type="hidden" name="forgotpwchangeform" value="1" />
									<input type="hidden" name="changepwhash" value="{$changepwhash}" />
								</div>
								{/if}
								<div class="row">
									<div class="mt-3 col-12 col-sm-6 p-0 text-left">
									<input class="loginsubmit" name="logincancel" type="submit" value="{'cancel'|lang}" />
									</div>
									<div class="mt-3 col-12 col-sm-6 p-0 text-left text-sm-right">
									<input class="loginsubmit" name="loginsubmit" type="submit" value="{'submit'|lang}" />
									</div>
								</div>									
								</fieldset>

							</form>
							</div>
							{if isset($smarty.get.forgotpw) && !empty($smarty.get.forgotpw)}
								<div tabindex="0" role="alertdialog" class="col-12 message warning mt-2 py-2">
									{'forgotpwprompt'|lang}
								</div>
							{/if}
							{if isset($error)}
								<div tabindex="0" role="alertdialog" class="col-12 message error mt-2 py-2">
									{$error}
								</div>
							{/if}
							{if isset($warninglogin)}
								<div tabindex="0" role="alertdialog" class="col-12 message warning mt-2 py-2">
									{$warninglogin}
								</div>
							{/if}
							{if isset($acceptlogin)}
								<div tabindex="0" role="alertdialog" class="col-12 message success mt-2 py-2">
									{$acceptlogin}
								</div>
							{/if}
							{if isset($changepwhash) && !empty($changepwhash)}
								<div tabindex="0" role="alertdialog" class="col-12 warning message mt-2 py-2">
									{'passwordchange'|lang}
								</div>
							{/if} 
							<div class="col-12 mt-5 px-0">
								<div class="row alt-actions">
									<a class="col-12 col-sm-6" href="{root_url}" title="{'goto'|lang} {sitename}"><span aria-hidden="true" class="fas fa-chevron-circle-left"></span> Go Back</a>
									<a href="login.php?forgotpw=1" class="col-12 text-left text-sm-right col-sm-6"><span class="fas fa-question-circle" aria-hidden="true"></span> {'lostpw'|lang}</a>
								</div>
							</div>
						</div>
					</div>
				</div>
				<footer class="row">
					<small class="col-12 copyright">Copyright &copy; <a rel="external" href="http://www.cmsmadesimple.org">CMS Made Simple&trade;</a></small>
				</footer>
		
		</div>
	</body>
</html>
