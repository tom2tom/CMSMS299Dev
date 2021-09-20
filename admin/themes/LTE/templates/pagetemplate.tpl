<!doctype html>
<html lang="{$lang|truncate:'2':''}" dir="{$lang_dir}">
	<head>
		<meta charset="utf-8" />
		<title>{$page_title} - {$page_subtitle|default:"{sitename}"}</title>
		<base href="{$admin_url}/" />
		<meta name="generator" content="CMS Made Simple - Copyright (C) 2019-{$smarty.now|date_format:'Y'}. All rights reserved." />
		<meta name="robots" content="noindex, nofollow" />

		<link rel="shortcut icon" href="themes/assets/images/cmsms-favicon.ico"/>
		<link rel="apple-touch-icon" href="themes/assets/images/apple-touch-icon-iphone.png" />
		<link rel="apple-touch-icon" sizes="72x72" href="themes/assets/images/apple-touch-icon-ipad.png" />
		<link rel="apple-touch-icon" sizes="114x114" href="themes/assets/images/apple-touch-icon-iphone4.png" />
		<link rel="apple-touch-icon" sizes="144x144" href="themes/assets/images/apple-touch-icon-ipad3.png" />

		<meta name="msapplication-TileImage" content="themes/assets/images/ms-application-icon.png" />
		<meta name="msapplication-TileColor" content="#f89938" />

		{* +++++ Bootstrap Start +++++ *}
		<meta name="viewport" content="width=device-width, initial-scale=1">

		<link href="{$theme_url}/css/fork-awesome.min.css" rel="stylesheet" /> {* Fork Awesome *}
		<link rel="stylesheet" href="{$theme_url}/plugins/fontawesome-free/css/all.min.css"> {* Font Awesome *}

		<link rel="stylesheet" href="//code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css"> {* Ionicons *}
		{* <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700"> {* Google Font: Source Sans Pro *}
		{* <link rel="stylesheet" href="//fonts.googleapis.com/css?family=teko:300,400,400i,700"> {* Google Font: teko *}
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700|Teko:700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{$theme_url}/dist/css/adminlte.min.css"> {* Theme style *}

		<link rel="stylesheet" href="{$theme_url}/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">  {* Tempusdominus Bootstrap 4 *}
		<link rel="stylesheet" href="{$theme_url}/plugins/icheck-bootstrap/icheck-bootstrap.min.css"> {* iCheck *}
		<link rel="stylesheet" href="{$theme_url}/plugins/jqvmap/jqvmap.min.css"> {* JQVMap *}

		<link rel="stylesheet" href="{$theme_url}/plugins/overlayScrollbars/css/OverlayScrollbars.min.css"> {* overlayScrollbars *}
		<link rel="stylesheet" href="{$theme_url}/plugins/daterangepicker/daterangepicker.css"> {* Daterange picker *}
		<link rel="stylesheet" href="{$theme_url}/plugins/summernote/summernote-bs4.css"> {* summernote *}


    {* backwards and CMSMS compatibility *}
    <link rel="stylesheet" href="{$admin_url}/style.php?{$secureparam}" /> {* backwards compatibility *}
    <link rel="stylesheet" href="{$theme_url}/css/style-override.css">
		<link rel="stylesheet" href="{$theme_url}/css/default-cmsms/jquery-ui-1.10.4.custom.min.css">


		<script src="{$theme_url}/plugins/jquery/jquery.min.js"></script> {* jQuery *}
		<script src="{$theme_url}/plugins/jquery-ui/jquery-ui.min.js"></script> {* jQuery UI 1.11.4 *}
		<script src="{$theme_url}/plugins/bootstrap/js/bootstrap.bundle.min.js"></script> {* Bootstrap 4 *}
		<script src="{$theme_url}/plugins/chart.js/Chart.min.js"></script> {* ChartJS *}
		<script src="{$theme_url}/plugins/sparklines/sparkline.js"></script> {* Sparkline *}
		<script src="{$theme_url}/plugins/jqvmap/jquery.vmap.min.js"></script> {* JQVMap *}
		<script src="{$theme_url}/plugins/jqvmap/maps/jquery.vmap.usa.js"></script>
		<script src="{$theme_url}/plugins/jquery-knob/jquery.knob.min.js"></script> {* jQuery Knob Chart *}
		<script src="{$theme_url}/plugins/moment/moment.min.js"></script>
		<script src="{$theme_url}/plugins/daterangepicker/daterangepicker.js"></script> {* daterangepicker *}
		<script src="{$theme_url}/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script> {* Tempusdominus Bootstrap 4 *}
		<script src="{$theme_url}/plugins/summernote/summernote-bs4.min.js"></script> {* Summernote *}
		<script src="{$theme_url}/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script> {* overlayScrollbars *}
		<script src="{$theme_url}/dist/js/adminlte.js"></script> {* AdminLTE App *}
		<script>$.widget.bridge('uibutton', $.ui.button)</script> {* Resolve conflict in jQuery UI tooltip with Bootstrap tooltip *}
		{* +++++ Bootstrap End +++++ *}

		<script src="../lib/jquery/js/jquery.mjs.nestedSortable.js"></script>
		<script src="../lib/jquery/js/jquery.json-2.4.min.js"></script>
		<script src="../lib/jquery/js/jquery-migrate-1.2.1.min.js"></script>
		<script src="../lib/jquery/js/jquery.cms_admin.js"></script>
		<script src="../lib/jquery/js/jquery.cmsms_dirtyform.js"></script>
		<script src="../lib/jquery/js/jquery.cmsms_lock.js"></script>
		<script src="../lib/jquery/js/jquery.cmsms_hierselector.js"></script>
		<script src="../lib/jquery/js/jquery.cmsms_autorefresh.js"></script>
		<script src="../lib/jquery/js/jquery.cmsms_filepicker.js"></script>
		<script src="../lib/jquery/js/jquery.ui.touch-punch.min.js"></script>
		<script src="{$admin_url}/cms_js_setup.php?{$secureparam}"></script>

		{* backwards and CMSMS compatibility *}
		<script src="{$theme_url}/includes/standard.min.js"></script>

	    {* TODO - Move to external file... *}
	    <script>
				$(function()
				{
			        // text blocks
			        $(".pagewarning").addClass("callout callout-danger");
			        $(".warning").addClass("callout callout-danger");
			        $(".text").addClass("callout callout-warning");
			        $(".quote").addClass("callout callout-info");
			        $(".note").addClass("callout callout-info");
			        $(".information").addClass("callout callout-info");

			        $(".green").addClass("callout callout-info");
			        $(".red").addClass("alert alert-danger alert-dismissible");

			        // tables
			        $(".pagetable").addClass("table table-striped table-hover");
			        //$(".pageicon").addClass("");

			        // buttons
			        $(".pagebutton").addClass("btn-sm btn-primary");
			        $("input[type='submit']").addClass("btn-sm btn-primary");

			        // admin home page
			        $("#topcontent_wrap").addClass("row");
			        $(".dashboard-box").addClass("col-lg-3 col-6 card");
			        $(".dashboard-inner").addClass("card-body");


							// scrollbars
			        $("body").overlayScrollbars({ });
					    //$('body').Layout('fixLayoutHeight');

			        // scrollbar for shortcuts bar
							$("#shorcuts-crol-sidebar").overlayScrollbars({ className : "os-theme-light" });
				} );
			</script>

		<!-- THIS IS WHERE HEADER STUFF SHOULD GO -->
		{$headertext|default:''}
	</head>

	<body class="hold-transition sidebar-mini {* layout-footer-fixed *} layout-fixed layout-navbar-fixed {*control-sidebar-push*}">

		<div class="wrapper">

			<nav class="main-header navbar navbar-expand navbar-dark">
				<ul class="navbar-nav">
      				<li class="nav-item">
        				<a class="nav-link" data-widget="pushmenu" href="javascript:void()"><i class="fas fa-bars"></i></a>
      				</li>
      				<li class="nav-item d-none d-sm-inline-block">
        				<a href="{$admin_url}/index.php?{$secureparam}" title="{'home'|lang}" class="nav-link"><i class="fas fa-home"></i></a>
      				</li>
					{* redundant maybe? (JM)
      				<li class="nav-item d-none d-sm-inline-block">
        				<a href="{$admin_url}/myaccount.php?{$secureparam}" class="nav-link">Account</a>
      				</li>
      		*}
    			</ul>
        		{include file='shortcuts.tpl'}
			</nav>

			<aside class="main-sidebar sidebar-dark-primary elevation-4">
				<a href="{$admin_url}/index.php?{$secureparam}" class="brand-link">
					<img src="{$theme_url}/images/logoCMS.png" alt="CMS Made Simple" class="brand-image elevation-3" style="opacity: .8" />
					<span class="brand-text font-weight-light"></span>
				</a>
				<div class="sidebar">
					{include file='user_panel.tpl'}
					{include file='navigation.tpl' nav=$theme->get_navigation_tree()}
				</div>
			</aside>

			<div class="content-wrapper">
				<div class="content-header">
					<div class="container-fluid">
						<div class="row mb-2">
							{if isset($module_icon_url) || isset($page_title)}
								<div class="col-12 col-sm-6 col-lg-6">
									<h1 class="m-0 text-dark">
										{*if isset($module_icon_url)}<img src="{$module_icon_url}" alt="{$module_name|default:''}" class="module-icon" />{/if*}
										{$page_title|default:''}
									</h1>
								</div>
							{/if}

								<div class="col-12 col-sm-6 col-lg-6">
									<span class="float-right">
										{include file='breadcrumbs.tpl' items = $theme->get_breadcrumbs()}
									</span>
								</div>

							</div>
							{if isset($page_subtitle)}
								<div class="row">
									<div class="col-12">
										<h3>{$page_subtitle}</h3>
									</div>
								</div>
							{/if}
						</div>
					</div>

        		<section class="content">
					<div class="container-fluid">{$content}</div>
				</section>

			</div>

      {include file='footer.tpl'}

      {$footertext|default:''}

		</div>
		{include file='messages.tpl'}
		{include file='bookmarks.tpl'}
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
							<span><a class="welcome-user" href="myaccount.php?{$secureparam}" title="{'myaccount'|lang}"><i class="fa fa-user"></i></a> {'welcome_user'|lang}: <a href="myaccount.php?{$secureparam}">{$user->firstname|default:$user->username}</a></span>
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