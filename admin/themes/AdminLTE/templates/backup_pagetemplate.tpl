<!doctype html>
<html lang="{$lang|truncate:'2':''}" dir="{$lang_dir}">
	<head>
		<meta charset="utf-8" />
		<title>{$page_title} - {$page_subtitle|default:"{sitename}"}</title>
		<base href="{$admin_url}/" />
		<meta name="generator" content="CMS Made Simple - Copyright (C) 2019-{$smarty.now|date_format:'%Y'}. All rights reserved." />
		<meta name="robots" content="noindex, nofollow" />

		<link rel="shortcut icon" href="{$theme_url}/images/favicon/favicon.ico"/>
		<link rel="apple-touch-icon" href="{$theme_url}/images/favicon/apple-touch-icon-iphone.png" />
		<link rel="apple-touch-icon" sizes="72x72" href="{$theme_url}/images/favicon/apple-touch-icon-ipad.png" />
		<link rel="apple-touch-icon" sizes="114x114" href="{$theme_url}/images/favicon/apple-touch-icon-iphone4.png" />
		<link rel="apple-touch-icon" sizes="144x144" href="{$theme_url}/images/favicon/apple-touch-icon-ipad3.png" />

		<meta name="msapplication-TileImage" content="{$theme_url}/images/favicon/ms-application-icon.png" />
		<meta name="msapplication-TileColor" content="#f89938" />

		{*<link rel="stylesheet" href="style.php?{$secureparam}" />*}

	{* +++++ Bootstrap Start +++++ *}
		<meta name="viewport" content="width=device-width, initial-scale=1">

		{cms_queue_css file="{$theme_path}/plugins/fontawesome-free/css/all.min.css"} {* Font Awesome *}
		<link rel="stylesheet" href="//code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css"> {* Ionicons *}
		{cms_queue_css file="{$theme_path}/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css"} {* Tempusdominus Bbootstrap 4 *}
		{cms_queue_css file="{$theme_path}/plugins/icheck-bootstrap/icheck-bootstrap.min.css"} {* iCheck *}
		{cms_queue_css file="{$theme_path}/plugins/jqvmap/jqvmap.min.css"} {* JQVMap *}
		{cms_queue_css file="{$theme_path}/dist/css/adminlte.min.css"} {* Theme style *}
		{cms_queue_css file="{$theme_path}/plugins/overlayScrollbars/css/OverlayScrollbars.min.css"} {* overlayScrollbars *}
		{cms_queue_css file="{$theme_path}/plugins/daterangepicker/daterangepicker.css"} {* Daterange picker *}
		{cms_queue_css file="{$theme_path}/plugins/summernote/summernote-bs4.css"} {* summernote *}
		<link rel="stylesheet" href="//fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700"> {* Google Font: Source Sans Pro *}

		{cms_render_css}
		{* <script src="{$theme_url}/plugins/jquery/jquery.min.js"></script> {* jQuery *}
		{cms_queue_script file="{$theme_path}/plugins/jquery/jquery.min.js"} {* jQuery *}
		{cms_queue_script file="{$theme_path}/plugins/jquery-ui/jquery-ui.min.js"} {* jQuery UI 1.11.4 *}
		{cms_queue_script file="{$theme_path}/plugins/bootstrap/js/bootstrap.bundle.min.js"} {* Bootstrap 4 *}
		{cms_queue_script file="{$theme_path}/plugins/chart.js/Chart.min.js"} {* ChartJS *}
		{cms_queue_script file="{$theme_path}/plugins/sparklines/sparkline.js"} {* Sparkline *}
		{cms_queue_script file="{$theme_path}/plugins/jqvmap/jquery.vmap.min.js"} {* JQVMap *}
		{cms_queue_script file="{$theme_path}/plugins/jqvmap/maps/jquery.vmap.usa.js"}
		{cms_queue_script file="{$theme_path}/plugins/jquery-knob/jquery.knob.min.js"} {* jQuery Knob Chart *}
		{cms_queue_script file="{$theme_path}/plugins/moment/moment.min.js"}
		{cms_queue_script file="{$theme_path}/plugins/daterangepicker/daterangepicker.js"} {* daterangepicker *}
		{cms_queue_script file="{$theme_path}/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"} {* Tempusdominus Bootstrap 4 *}
		{cms_queue_script file="{$theme_path}/plugins/summernote/summernote-bs4.min.js"} {* Summernote *}
		{cms_queue_script file="{$theme_path}/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"} {* overlayScrollbars *}
		{cms_queue_script file="{$theme_path}/dist/js/adminlte.js"} {* AdminLTE App *}
		{cms_render_scripts defer=0}
		<script>$.widget.bridge('uibutton', $.ui.button)</script> {* Resolve conflict in jQuery UI tooltip with Bootstrap tooltip *}
	{* +++++ Bootstrap End +++++ *}

		<script src="{$admin_url}/cms_js_setup.php?{$secureparam}"></script>

	{* TODO - Move to external file... *}
		<script>
			$(function() {
				// text blocks
				$(".pagewarning").addClass("callout callout-danger");
				$(".text").addClass("callout callout-warning");
				$(".quote").addClass("callout callout-info");
				$(".note").addClass("callout callout-info");

				$(".red").addClass("alert alert-danger alert-dismissible");

				// tabs
				$("#page_tabs").addClass("card-header");
				$("#page_content").addClass("card-body");
				$(".tabheader").addClass("nav-link");

				// tables
				$(".pagetable").addClass("table table-striped table-hover");
				$(".pageicon").addClass("");

				// buttons
				$(".pagebutton").addClass("btn bg-gradient-primary");
				$("input[type='submit']").addClass("btn bg-gradient-primary");

				// admin home page
				$("#topcontent_wrap").addClass("row");
				$(".dashboard-box").addClass("col-lg-3 col-6 card");
				$(".dashboard-inner").addClass("card-body");
			} );
		</script>

	 	{$headertext|default:''}
	</head>

	<body class="hold-transition sidebar-mini layout-fixed">

		<div class="wrapper">

			<nav class="main-header navbar navbar-expand navbar-primary navbar-dark">
				<ul class="navbar-nav">
      				<li class="nav-item">
        				<a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
      				</li>
      				<li class="nav-item d-none d-sm-inline-block">
        				<a href="{$admin_url}/index.php?{$secureparam}" class="nav-link">Home</a>
      				</li>
      				<li class="nav-item d-none d-sm-inline-block">
        				<a href="{$admin_url}/myaccount.php?{$secureparam}" class="nav-link">Account</a>
      				</li>
    			</ul>
    			{include file='shortcuts.tpl'}
			</nav>

			<aside class="main-sidebar sidebar-dark-primary elevation-4">
				<a href="{$admin_url}/index.php?{$secureparam}" class="brand-link">
					<img src="{$theme_url}/images/logo.png" alt="CMSMS" class="brand-image elevation-3" style="opacity: .8" />
					<span class="brand-text font-weight-light">CMS Made Simple CMS</span>
				</a>

				{include file='navigation.tpl' nav=$theme->get_navigation_tree()}
			</aside>

			<div class="content-wrapper">
				<div class="content-header">
					<div class="container-fluid">
						<div class="row">
						{if isset($module_icon_url) or isset($page_title)}
							<div class="col-12 col-sm-6 col-lg-6">
								<h2>
									{if isset($module_icon_url)}<img src="{$module_icon_url}" alt="{$module_name|default:''}" class="module-icon" />{/if}
									{$page_title|default:''}
								</h2>
							</div>
						{/if}
						{if isset($module_help_url)}
							<div class="col-12 col-sm-6 col-lg-6">
								<span class="float-right"><a href="{$module_help_url}">{'module_help'|lang}</a></span>
							</div>
						{/if}
						</div>
					</div>
				</div>
						
				{if $page_title && $page_subtitle}<header><h3>{$page_subtitle}</h3></header>{/if}
						
				<section class="content">
					<div class="container-fluid">{$content}</div>
				</section>

			</div>

			{include file='footer.tpl'}

			{$footertext|default:''}

		</div>

	</body>
</html>
{* +++++++++++++++++++++++++++++++++++++++++++++++++++++++

		<div id="oe_container" class="sidebar-on">

			<header role="banner" class="cf header">
				<div class="header-top cf">
					<div class="cms-logo">
						<a href="https://www.cmsmadesimple.org" rel="external">
							<img src="{$theme_url}/images/layout/cmsms-logo.jpg" width="205" height="69" alt="CMS Made Simple" title="CMS Made Simple" />
						</a>
					</div>
					<span class="admin-title"> {'adminpaneltitle'|lang} - {sitename}</span>
				</div>

				<div class='clear'></div>

				<div class="header-bottom cf">
					<div class="welcome" data-username="{$user->username}">
						{if isset($myaccount)}
							<span><a class="welcome-user" href="myaccount.php?{$secureparam}" title="{'myaccount'|lang}"><i class="fa fa-user"></i></a> {'welcome_user'|lang}: <a href="myaccount.php?{$secureparam}">{$user->username}</a></span>
						{else}
							<span><a class="welcome-user"><i class="fa fa-user"></i></a> {'welcome_user'|lang}: <span data-username="{$user->username}">{$user->username}</span></span>
						{/if}
					</div>
					{include file='shortcuts.tpl'}
				</div>
			</header>

			<div id="oe_admin-content">
				<div class="shadow">&nbsp;</div>

				<div id="oe_sidebar">
					<aside>
						<span title="{'open'|lang}/{'close'|lang}" class="toggle-button close"></span>
						{include file='navigation.tpl' nav=$theme->get_navigation_tree()}
					</aside>
				</div>

				<div id="oe_mainarea" class="cf">
					{include file='messages.tpl'}

					<article role="main" class="content-inner">
						<header class="pageheader{if isset($is_ie)} drop-hidden{/if} cf">
							{if isset($module_icon_url) or isset($page_title)}
								<h1>{if isset($module_icon_url)}<img src="{$module_icon_url}" alt="{$module_name|default:''}" class="module-icon" />{/if}
									{$page_title|default:''}
								</h1>
							{/if}
							{if isset($module_help_url)} <span class="helptext"><a href="{$module_help_url}">{'module_help'|lang}</a></span>{/if}
						</header>
						
						{if $page_title && $page_subtitle}<header class="subheader"><h3 class="subtitle">{$page_subtitle}</h3></header>{/if}
						
						<section class="cf">{$content}</section>
					</article>
				</div>

				<div class="spacer">&nbsp;</div>
			</div>

			{include file='footer.tpl'}

			{$footertext|default:''}

		</div>
+++++++++++++++++++++++++++++++++++++++++++++++++++++++ *}