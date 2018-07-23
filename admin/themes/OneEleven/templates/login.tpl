<!doctype html>
<html lang="{$lang_code|truncate:'2':''}" dir="{$lang_dir|default:'ltr'}">
	<head>
		<title>{'logintitle'|lang} - {sitename}</title>
		<meta charset="{$encoding}" />
		<meta name="generator" content="CMS Made Simple" />
		<meta name="robots" content="noindex, nofollow" />
		<meta name="viewport" content="initial-scale=1.0 maximum-scale=1.0 user-scalable=no" />
		<meta name="HandheldFriendly" content="true" />
		<meta name="msapplication-TileColor" content="#f89938" />
		<meta name="msapplication-TileImage" content="{$admin_url}/themes/OneEleven/images/favicon/ms-application-icon.png" />
		<base href="{$admin_url}/" />
		<link rel="shortcut icon" href="themes/OneEleven/images/favicon/cmsms-favicon.ico" />
{$header_includes|default:''}
		<script type="text/javascript" src="themes/OneEleven/includes/login.js"></script>
	</head>
	<body id="login">
		<div id="wrapper">
			<div class="login-container">
				<div class="login-box cf"{if !empty($error)} id="error"{/if}>
					<div class="logo">
						<a rel="external" href="http://www.cmsmadesimple.org">
						<img src="themes/OneEleven/images/layout/cmsms_login_logo.png" width="180" height="36" alt="CMS Made Simple" />
						</a>
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
					{if isset($form)}{$form}{else}{include file='form.tpl'}{/if}
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
					{/if} <a href="{root_url}" title="{'goto'|lang} {sitename}"> <img class="goback" width="16" height="16" src="themes/OneEleven/images/layout/goback.png" alt="{'goto'|lang} {sitename}" /> </a>
					<p class="forgotpw">
						<a href="login.php?forgotpw=1">{'lostpw'|lang}</a>
					</p>
				</div>
			</div>
		</div>
		{$bottom_includes|default:''}
	</body>
</html>
