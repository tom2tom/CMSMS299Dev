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
	<link rel="stylesheet" href="themes/LTE/css/solid.min.css" />{* Font Awesome 1 *}
	<link rel="stylesheet" href="themes/LTE/css/fontawesome.min.css" />{* Font Awesome 2 *}
{*	<link rel="stylesheet" href="themes/LTE/UNUSED-plugins/icheck-bootstrap/icheck-bootstrap.min.css" />*}{* icheck bootstrap *}
	<link rel="stylesheet" href="themes/LTE/css/adminlte.min.css" />{* theme-specific styles + bootstrap grid *}
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" />{* Google font: Source Sans Pro *}
</head>

<body class="hold-transition login-page">
	<div class="login-box">
		<div class="login-logo">
			<a href="https://www.cmsmadesimple.org"><img src="themes/assets/images/cmsms-logotext-dark.svg" class="img-fluid" style="max-width: 268px;" alt="CMS Made Simple logo" title="CMS Made Simple" /></a>
		</div>
		<!-- /.login-logo -->

		<div class="card">
			<div class="card-body login-card-body">
				<p class="login-box-msg">{$lost=isset($smarty.get.forgotpw)}
				{if $lost}{['forgotpwtitle',{sitename}]|lang}
				{elseif isset($renewpw)}{['renewpwtitle',{sitename}]|lang}
				{elseif !empty($sitelogo)}{'login_admin'|lang}
				{else}{['login_sitetitle',{sitename}]|lang}{/if}</p>
				<form method="post" action="login.php">
					{if isset($csrf)}<input type="hidden" name="csrf" value="{$csrf}" />{/if}
					<div class="input-group mb-3">
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
						<div class="input-group-append">
							<div class="input-group-text">
								<span class="fas fa-user-secret"></span>
							</div>
						</div>
					</div>
					{if !$lost}
						<div class="input-group mb-3">
							<input id="lbpassword" name="password" type="password" class="form-control{if !isset($smarty.post.lbpassword) or isset($error)} focus{/if}" placeholder="{'password'|lang}" size="25" maxlength="64" />
							<div class="input-group-append">
								<div class="input-group-text">
									<span class="fas fa-key"></span>
								</div>
							</div>
						</div>
					{/if}
					{if !empty($changepwhash)}
						<div class="input-group mb-3">
							<input id="lbpasswordagain" name="passwordagain" type="password" class="form-control" placeholder="{'passwordagain'|lang}" size="25" maxlength="64" />
							<div class="input-group-append">
								<div class="input-group-text">
									<span class="fas fa-key"></span>
								</div>
							</div>
							<input type="hidden" name="changepwhash" value="{$changepwhash}" />
						</div>
					{/if}
					<div class="row mb-3">
						<div class="col-4">
							 <button class="btn btn-default bg-orange btn-block" name="submit" type="submit">{'submit'|lang}</button>
						</div>
						<!-- /.col -->
						<div class="col-4">
							{if ($lost || isset($renewpw))}
							<button class="btn btn-default bg-orange btn-block" name="cancel" type="submit">{'cancel'|lang}</button>
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
				<div class="mb-3">
					<a href="login.php?forgotpw=1" class="text-orange"><span class="fas fa-user-lock fa-fw"></span>&nbsp;&nbsp;{'lostpw'|lang}</a>
				</div>
				{/if}

				<div>
					<a href="{root_url}" class="text-orange" title="{['goto',{sitename}]|lang}"><span class="fas fa-arrow-circle-left fa-fw"></span>&nbsp;&nbsp;{['goto',{sitename}]|lang}</a>
				</div>
			</div>
			<!-- /.login-card-body -->

		</div>

		<footer>
			<p class="text-center"><small>
				Powered by <a rel="external" class="text-orange" href="https://cmsmadesimple.org"><b>CMS Made Simple</b></a>
			</small></p>
		</footer>

	</div>
	<!-- /.login-box -->

	{get_jquery migrate=true ui=true uicss=false}
{*	<script type="text/javascript" src="themes/LTE/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>*}{* Bootstrap 4 *}
{*	<script type="text/javascript" src="themes/LTE/includes/adminlte.min.js"></script>*}{* AdminLTE App *}
	<script type="text/javascript" src="themes/LTE/includes/login.min.js"></script>

</body>

</html>
{* +++++++++++++++++++++++++++++++++++++++++++++++++

<div class="info-wrapper open">
	<aside class="info">
		<h2>{'login_info_title'|lang}</h2>
		<p>{'login_info'|lang}</p>
		{'login_info_params'|lang}
		<p><strong>({$smarty.server.HTTP_HOST})</strong></p>
		<p class="warning">{'warn_admin_ipandcookies'|lang}</p>
	</aside>
	<a href="javascript:void()" title="{'open'|lang}/{'close'|lang}" class="toggle-info">{'open'|lang}/{'close'|lang}</a>
</div>

+++++++++++++++++++++++++++++++++++++++++++++++++ *}
