<!doctype html>
<html lang="{$lang_code|truncate:'2':''}" dir="{$lang_dir|default:'ltr'}">
	<head>
		<title>{'logintitle'|lang} - {sitename}</title>
		<meta charset="{$encoding}" />
		<meta name="generator" content="CMS Made Simple - Copyright (C) 2004-2018 - All rights reserved" />
		<meta name="robots" content="noindex, nofollow" />
		<meta name="viewport" content="initial-scale=1.0 maximum-scale=1.0 user-scalable=no" />
		<meta name="HandheldFriendly" content="true"/>
		<meta name="msapplication-TileColor" content="#f89938" />
		<meta name="msapplication-TileImage" content="{$assets_url}/images/ms-application-icon.png" />
		<base href="{$admin_url}/" />
		<link rel="shortcut icon" href="{$assets_url}/images/cmsms-favicon.ico"/>
		<link rel="stylesheet" href="themes/Marigold/css/style{if $lang_dir=='rtl'}-rtl{/if}.css" />
        {$header_includes|default:''}
		<script type="text/javascript" src="themes/Marigold/includes/login.js"></script>
		<!--[if lt IE 9]>
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.min.js"></script>
		<![endif]-->
	</head>
	<body id="login">
		<div id="wrapper">
			<div class="login-container">
				<div class="login-box cf"{if !empty($error)} id="error"{/if}>
					<div class="logo">
						<img src="themes/Marigold/images/layout/cmsms_login_logo.png" width="180" height="36" alt="CMS Made Simple&trade;" />
					</div>
					<div class="info-wrapper open">
					<aside class="info">
					<h2>{'login_info_title'|lang}</h2>
						<p>{'login_info'|lang}</p>
							{'login_info_params'|lang}
							<p><strong>({$smarty.server.HTTP_HOST})</strong></p>
						<p class="warning">{'warn_admin_ipandcookies'|lang}</p>
					</aside>
					<a href="#" title="{'open'|lang}/{'close'|lang}" class="toggle-info">{'open'|lang}/{'close'|lang}</a>
					</div>
					<header>
						<h1>{'logintitle'|lang}</h1>
					</header>
					{$form}
					{if !empty($smarty.get.forgotpw)}
						<div class="message warning">
							{'forgotpwprompt'|lang}
						</div>
					{/if}
					{if !empty($error)}
						<div class="message error">
							{$error}
						</div>
					{/if}
					{if !empty($warning)}
						<div class="message warning">
							{$warning}
						</div>
					{/if}
					{if !empty($message)}
						<div class="message success">
							{$message}
						</div>
					{/if}
					{if !empty($changepwhash)}
						<div class="warning message">
							{'passwordchange'|lang}
						</div>
					{/if} <a href="{root_url}" title="{'goto'|lang} {sitename}"> <img class="goback" width="16" height="16" src="{$admin_url}/themes/Marigold/images/layout/goback.png" alt="{'goto'|lang} {sitename}" /> </a>
					<p class="forgotpw">
						<a href="login.php?forgotpw=1">{'lostpw'|lang}</a>
					</p>
				</div>
				<footer>
					<small class="copyright">Copyright &copy; <a rel="external" href="http://www.cmsmadesimple.org">CMS Made Simple&trade;</a></small>
				</footer>
			</div>
		</div>
		{$bottom_includes|default:''}
	</body>
</html>
