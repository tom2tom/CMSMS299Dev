<!DOCTYPE html>
<html lang="{$lang_code|truncate:'2':''}" dir="{$lang_dir|default:'ltr'}">
<head>
	<title>CMS Made Simple | Log in</title>
	<meta charset="{$encoding}">
	<meta name="generator" content="CMS Made Simple" />
	<meta name="referrer" content="origin" />
	<meta name="robots" content="noindex, nofollow" />
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<base href="{$admin_url}/" />
	<link rel="shortcut icon" href="themes/assets/images/cmsms-favicon.ico" />
{*	<link rel="stylesheet" href="admin/loginstyle.php" />*}
	<link rel="stylesheet" href="themes/LTE/styles/solid.min.css" />{* Font Awesome 1 *}
	<link rel="stylesheet" href="themes/LTE/styles/fontawesome.min.css" />{* Font Awesome 2 *}
{*	<link rel="stylesheet" href="themes/LTE/UNUSED-plugins/icheck-bootstrap/icheck-bootstrap.min.css" />*}{* icheck bootstrap *}
	<link rel="preconnect" href="https://fonts.googleapis.com" />
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" />{* Google font: Source Sans Pro *}
{*	<link rel="stylesheet" href="themes/LTE/styles/adminlte.min.css" />{* theme-specific styles + bootstrap grid *}
	<link rel="stylesheet" href="themes/LTE/styles/adminlte.core.min.css" />{* theme styles + bootstap grid *}
	<link rel="stylesheet" href="themes/LTE/styles/adminlte.pages.min.css" />{* login-specific extras *}
	<link rel="stylesheet" href="themes/LTE/styles/style-override.css" />
	<link rel="stylesheet" href="themes/LTE/styles/style{if $lang_dir == 'rtl'}-rtl{/if}.min.css" />
</head>

<body class="hold-transition login-page">
	<div class="login-box">
		<div class="card">
			<div class="card-body">
				<noscript>
					<div class="alert alert-danger">{'login_info_needjs'|lang}</div>
				</noscript>
				{if !empty($sitelogo)}
					<img id="sitelogo" src="{$sitelogo}" alt="{sitename}" />
				{/if}
				<p class="login-box-msg">{$lost=isset($smarty.get.forgotpw)}
				{if $lost}{['forgotpwtitle',{sitename}]|lang}
				{elseif isset($renewpw)}{['renewpwtitle',{sitename}]|lang}
				{else}{['login_sitetitle',{sitename}]|lang}{/if}</p>
				<form method="post" action="login.php">
					{if isset($csrf)}<input type="hidden" name="csrf" value="{$csrf}" />{/if}
					<div class="input-icon mb-3">
						{if isset($renewpw)}
						<input id="lbusername" type="text" class="form-control" size="20" maxlength="64" value="{$username}" disabled />
						<input type="hidden" name="username" value="{$username}" />
						<input type="hidden" name="renewpwform" value="1" />
 						{else}
						{if $lost}
						<input type="hidden" name="lostpwform" value="1" />
						{$usernamefld='forgottenusername'}
						{else}
						{$usernamefld='username'}
						{/if}
						<input id="lbusername" type="text"{if !isset($smarty.post.lbusername)} class="form-control"{/if} placeholder="{'username'|lang}" name="{$usernamefld}" size="25" maxlength="64" value="" autofocus="autofocus" />
						{/if}
						<i class="fas fa-user-secret" aria-hidden="true"></i>
					</div>
					{if !$lost}
						<div class="input-icon mb-3">
							<input id="lbpassword" name="password" type="password" class="form-control{if !isset($smarty.post.lbpassword) or isset($error)} focus{/if}" placeholder="{'password'|lang}" size="25" maxlength="64" />
							<i class="fas fa-key" aria-hidden="true"></i>
						</div>
					{/if}
					{if !empty($changepwhash)}
						<div class="input-icon mb-3">
							<input id="lbpasswordagain" name="passwordagain" type="password" class="form-control" placeholder="{'passwordagain'|lang}" size="25" maxlength="64" />
							<i class="fas fa-key" aria-hidden="true"></i>
							<input type="hidden" name="changepwhash" value="{$changepwhash}" />
						</div>
					{/if}
					<div class="row mb-3">
						<div class="col-4">
							 <button class="btn btn-default w-100" name="submit" type="submit">{'submit'|lang}</button>
						</div>
						<!-- /.col -->
						<div class="col-4">
							{if ($lost || isset($renewpw))}
							<button class="btn btn-default w-100" name="cancel" type="submit">{'cancel'|lang}</button>
							{/if}
						</div>
						<!-- /.col -->
						<div class="col-4">
						</div>
						<!-- /.col -->
					</div>
				</form>

				{if $lost}<div class="alert alert-info">{'forgotpwprompt'|lang}</div>
				{elseif isset($renewpw)}<div class="alert alert-warning">{'renewpwprompt'|lang}</div>
{*				{elseif !empty($changepwhash)}<div class="alert alert-info">{'passwordchange'|lang}</div>*}{/if}
				{if !empty($errmessage)}<div class="alert alert-danger">{$errmessage}</div>{/if}
				{if !empty($warnmessage)}<div class="alert alert-warning">{$warnmessage}</div>{/if}
				{if !empty($infomessage)}<div class="alert alert-info">{$infomessage}</div>{/if}

				{if !($lost || isset($renewpw))}
				<div class="mb-2">
					<a href="javascript:void()" id="toggle-info" class="text-primary" title="{'open'|lang}/{'close'|lang}"><span class="fas fa-info-circle fa-fw" aria-hidden="true"></span>&nbsp;&nbsp;{'login_info_title'|lang}</a>
				</div>
				<div class="mb-2">
					<a href="login.php?forgotpw=1" class="text-primary"><span class="fas fa-user-lock fa-fw" aria-hidden="true"></span>&nbsp;&nbsp;{'lostpw'|lang}</a>
				</div>
				<a href="{root_url}" class="text-primary"><span class="fas fa-arrow-circle-left fa-fw" aria-hidden="true"></span>&nbsp;&nbsp;{['goto',{sitename}]|lang}</a>
				<div id="info-wrapper" class="alert alert-info">
{*					<p>{['login_info_params',"<strong>{$smarty.server.HTTP_HOST}</strong>"]|lang}</p>*}
					<p>{'login_info_params'|lang}</p>
					<p>{'info_cookies'|lang}</p>
				</div>
				{/if}

			</div>
			<!-- /.login-card-body -->

		</div>

		<footer>
			<p class="text-center"><small>
				Powered by <a href="https://cmsmadesimple.org" rel="external">
				<img src="themes/assets/images/cmsms-logotext-dark.svg" class="img-fluid" style="max-width:15em" alt="CMS Made Simple logo" title="CMS Made Simple" />
				</a>
			</small></p>
		</footer>
	</div>
	<!-- /.login-box -->

	{get_jquery migrate=true ui=true uicss=false}
{*	<script type="text/javascript" src="themes/LTE/includes/adminlte.min.js"></script>*}{* AdminLTE App *}
	<script type="text/javascript" src="themes/LTE/includes/login.min.js"></script>

</body>

</html>
