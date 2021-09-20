<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<base href="{$admin_url}/" />

	<title>CMS Made Simple | Log in</title>

	<meta name="generator" content="CMS Made Simple - Copyright (C) 2019-{$smarty.now|date_format:'Y'} - All rights reserved" />
	<meta name="robots" content="noindex, nofollow" />
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<link rel="shortcut icon" href="themes/assets/images/favicon/favicon.ico" />
	{*<link rel="stylesheet" href="loginstyle.php" />*}

	<link rel="stylesheet" href="{$theme_url}/plugins/fontawesome-free/css/all.min.css"> {* Font Awesome *}
	{*<link rel="stylesheet" href="//code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">*} {* Ionicons *}
	{*<link rel="stylesheet" href="{$theme_url}/plugins/icheck-bootstrap/icheck-bootstrap.min.css">*} {* icheck bootstrap *}
	<link rel="stylesheet" href="{$theme_url}/dist/css/adminlte.min.css"> {* Theme style *}
	<link href="//fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet"> {* Google Font: Source Sans Pro - TODO: move to CMS! - *}
</head>

<body class="hold-transition login-page">
	<div class="login-box">
		<div class="login-logo">
			<a href="https://cmsmadesimple.org"><img src="{$theme_url}/images/logoCMS.png" class="img-fluid" style="max-width: 268px;" alt="CMS Made Simple logo" title="CMS Made Simple"/></a>
		</div>
		<!-- /.login-logo -->

		<div class="card">
			<div class="card-body login-card-body">
				<p class="login-box-msg">{['login_sitetitle',{sitename}]|lang}</p>

				<form method="post" action="login.php">
					<div class="input-group mb-3">
						{$usernamefld = 'username'}
						{if isset($smarty.get.forgotpw)}{$usernamefld = 'forgottenusername'}{/if}
						<input id="lbusername"{if !isset($smarty.post.lbusername)} class="form-control"{/if} placeholder="{'username'|lang}" name="{$usernamefld}" type="text" size="15" value="" autofocus="autofocus" />
						<div class="input-group-append">
							<div class="input-group-text">
								<span class="fas fa-user"></span>
							</div>
						</div>
					</div>
					{if isset($smarty.get.forgotpw) && !empty($smarty.get.forgotpw)}
						<input type="hidden" name="forgotpwform" value="1" />
					{/if}
					{if isset($smarty.get.forgotpw) && !empty($smarty.get.forgotpw)}
						<input type="hidden" name="forgotpwform" value="1" />
					{/if}
					{if !isset($smarty.get.forgotpw) && empty($smarty.get.forgotpw)}
						<div class="input-group mb-3">
							<input id="lbpassword" class="form-control{if !isset($smarty.post.lbpassword) or isset($error)} focus{/if}" placeholder="{'password'|lang}" name="password" type="password" size="15" maxlength="100"/>
							<div class="input-group-append">
								<div class="input-group-text">
									<span class="fas fa-lock"></span>
								</div>
							</div>
						</div>
					{/if}
					{if isset($changepwhash) && !empty($changepwhash)}
						<div class="input-group mb-3">
							<input id="lbpasswordagain" name="passwordagain" type="password" size="15" class="form-control" placeholder="{'passwordagain'|lang}" maxlength="100" />
							<input type="hidden" name="forgotpwchangeform" value="1" />
							<input type="hidden" name="changepwhash" value="{$changepwhash}" />
						</div>
					{/if}
					<div class="row mb-3">
						<div class="col-4">
							 <button class="btn btn-default bg-orange btn-block" name="loginsubmit" type="submit">{'submit'|lang}</button>
						</div>
						<!-- /.col -->
						<div class="col-4">
							<button class="btn btn-default bg-orange btn-block" name="logincancel" type="submit">{'cancel'|lang}</button>
						</div>
						<!-- /.col -->
						<div class="col-4">

						</div>
						<!-- /.col -->
					</div>
				</form>

				{if isset($smarty.get.forgotpw) && !empty($smarty.get.forgotpw)}
					<div class="alert alert-warning">{'forgotpwprompt'|lang}</div>
				{/if}

				{if isset($error)}
					<div class="alert alert-danger">{$error}</div>
				{/if}

				{if isset($warninglogin)}
					<div class="alert alert-warning">{$warninglogin}</div>
				{/if}

				{if isset($acceptlogin)}
					<div class="alert alert-success">{$acceptlogin}</div>
				{/if}

				{if isset($changepwhash) && !empty($changepwhash)}
					<div class="alert alert-info">{'passwordchange'|lang}</div>
				{/if}

				<div class="mb-3">
					<a href="login.php?forgotpw=1" class="text-orange"><span class="fas fa-user-lock fa-fw"></span>&nbsp;&nbsp;{'lostpw'|lang}</a>
				</div>

				<div>
					<a href="{root_url}" class="text-orange" title="{['goto',{sitename}]|lang}"><span class="fas fa-arrow-circle-left fa-fw"></span>&nbsp;&nbsp;{['goto',{sitename}]|lang}</a>
				</div>

			</div>
			<!-- /.login-card-body -->

		</div>

		<footer>
			<p class="text-center"><small>
				Powered by <a rel="external" class="text-orange" href="https://cmsmadesimple.org"><b>CMS Made Simple</b></a>.<br />
				| some slogan here, maybe? |.
			</small></p>
		</footer>

	</div>
	<!-- /.login-box -->

	<script src="{$theme_url}/plugins/jquery/jquery.min.js"></script> {* jQuery *}
	<script src="{$theme_url}/plugins/bootstrap/js/bootstrap.bundle.min.js"></script> {* Bootstrap 4 *}
	<script src="{$theme_url}/dist/js/adminlte.min.js"></script> {* AdminLTE App *}

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
