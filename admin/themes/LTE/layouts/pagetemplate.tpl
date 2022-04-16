<!DOCTYPE html>
<html lang="{$lang_code|truncate:'2':''}" dir="{$lang_dir|default:'ltr'}">
	<head>
		<title>{$page_title} - {$page_subtitle|default:"{sitename}"}</title>
		<base href="{$admin_url}/" />
		<meta charset="{$encoding}" />
		<meta name="generator" content="CMS Made Simple" />
		<meta name="robots" content="noindex, nofollow" />
		<meta name="msapplication-TileImage" content="themes/assets/images/ms-application-icon.png" />
		<meta name="msapplication-TileColor" content="#f89938" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />

		<link rel="shortcut icon" href="themes/assets/images/cmsms-favicon.ico" />
		<link rel="apple-touch-icon" href="themes/assets/images/apple-touch-icon-iphone.png" />
		<link rel="apple-touch-icon" sizes="72x72" href="themes/assets/images/apple-touch-icon-ipad.png" />
		<link rel="apple-touch-icon" sizes="114x114" href="themes/assets/images/apple-touch-icon-iphone4.png" />
		<link rel="apple-touch-icon" sizes="144x144" href="themes/assets/images/apple-touch-icon-ipad3.png" />

{*		<link href="themes/LTE/styles/fork-awesome.min.css" rel="stylesheet" />*}{* Fork Awesome *}
		<link rel="stylesheet" href="themes/LTE/styles/fontawesome.min.css" />{* Font Awesome #1 *}
		<link rel="stylesheet" href="themes/LTE/styles/solid.min.css" />{* Font Awesome #2 *}
{*		<link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css" />* } {* Ionicons *}
		<link rel="preconnect" href="https://fonts.googleapis.com" />
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
		<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Source+Code+Pro:wght@400;700&family=Source+Sans+Pro:ital,wght@0,400;0,700;1,400;1,700&display=swap" />
		<link rel="stylesheet" href="themes/LTE/styles/OverlayScrollbars.min.css" />
{*		<link rel="stylesheet" href="themes/LTE/styles/adminlte.min.css" />* }{* theme styles + bootstap grid *}
		<link rel="stylesheet" href="themes/LTE/styles/adminlte.core.min.css" />{* theme styles + bootstap grid *}
		<link rel="stylesheet" href="themes/LTE/styles/style-override.css" />
{*
		<link rel="stylesheet" href="themes/LTE/UNUSED-plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css" />{* Tempusdominus Bootstrap 4 * }
		<link rel="stylesheet" href="themes/LTE/UNUSED-plugins/icheck-bootstrap/icheck-bootstrap.min.css" /> {* iCheck * }
		<link rel="stylesheet" href="themes/LTE/UNUSED-plugins/overlayScrollbars/css/OverlayScrollbars.min.css" />{* overlayScrollbars * }
		<link rel="stylesheet" href="themes/LTE/UNUSED-plugins/daterangepicker/daterangepicker.css" /> {* Daterange picker * }
		<link rel="stylesheet" href="themes/LTE/UNUSED-plugins/summernote/summernote-bs4.css" /> {* summernote * }

		{* backwards and CMSMS compatibility * }
		<link rel="stylesheet" href="style.php?{$secureparam}" /> {* backwards compatibility * }
*}
		<link rel="stylesheet" href="themes/LTE/styles/style{if $lang_dir == 'rtl'}-rtl{/if}.min.css" />
		<link rel="stylesheet" href="themes/LTE/styles/topfiles.css" />
{*
		<link rel="stylesheet" href="themes/LTE/styles/default-cmsms/jquery-ui-1.10.4.custom.min.css" />
		<script src="themes/LTE/UNUSED-plugins/jquery/jquery.min.js"></script>{* jQuery * }
		<script src="themes/LTE/UNUSED-plugins/jquery-ui/jquery-ui.min.js"></script>{* jQuery UI 1.11.4 * }
		<script type="text/javascript" src="themes/LTE/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>{* Bootstrap 4 * }
		<script type="text/javascript" src="themes/LTE/UNUSED-plugins/chart.js/Chart.min.js"></script>{* ChartJS * }
		<script type="text/javascript" src="themes/LTE/UNUSED-plugins/sparklines/sparkline.js"></script>{* Sparkline * }
		<script type="text/javascript" src="themes/LTE/UNUSED-plugins/jqvmap/jquery.vmap.min.js"></script>{* JQVMap * }
		<script type="text/javascript" src="themes/LTE/UNUSED-plugins/jqvmap/maps/jquery.vmap.usa.js"></script>
		<script type="text/javascript" src="themes/LTE/UNUSED-plugins/jquery-knob/jquery.knob.min.js"></script>{* jQuery Knob Chart * }
		<script type="text/javascript" src="themes/LTE/UNUSED-plugins/moment/moment.min.js"></script>
		<script type="text/javascript" src="themes/LTE/UNUSED-plugins/daterangepicker/daterangepicker.js"></script>{* daterangepicker * }
		<script type="text/javascript" src="themes/LTE/UNUSED-plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>{* Tempusdominus Bootstrap 4 * }
		<script type="text/javascript" src="themes/LTE/UNUSED-plugins/summernote/summernote-bs4.min.js"></script>{* Summernote * }
*}
		{$headertext|default:''}
		<script type="text/javascript" src="themes/LTE/includes/adminlte.min.js"></script>{* AdminLTE App *}
{*
moved	<script>$.widget.bridge('uibutton', $.ui.button)</script>*}{* Resolve conflict between jQueryUI tooltip and Bootstrap tooltip * }
		<script type="text/javascript" src="../lib/jquery/js/jquery.mjs.nestedSortable.js"></script>
		<script type="text/javascript" src="../lib/jquery/js/jquery.json-2.4.min.js"></script>
		<script type="text/javascript" src="../lib/jquery/js/jquery-migrate-1.2.1.min.js"></script>
		<script type="text/javascript" src="../lib/jquery/js/jquery.cms_admin.js"></script>{* TODO deprecated location * }
		<script type="text/javascript" src="../lib/jquery/js/jquery.cmsms_dirtyform.js"></script>
		<script type="text/javascript" src="../lib/jquery/js/jquery.cmsms_lock.js"></script>
		<script type="text/javascript" src="../lib/jquery/js/jquery.cmsms_hierselector.js"></script>
		<script type="text/javascript" src="../lib/jquery/js/jquery.cmsms_autorefresh.js"></script>
		<script type="text/javascript" src="../lib/jquery/js/jquery.cmsms_filepicker.js"></script>
		<script type="text/javascript" src="../lib/jquery/js/jquery.ui.touch-punch.min.js"></script>{* TODO deprecated location * }
		<script type="text/javascript" src="cms_js_setup.php?{$secureparam}"></script>{* TODO deprecated location, if needed * }
*}
		{* backwards and CMSMS compatibility *}
		<script type="text/javascript" src="themes/LTE/includes/standard.min.js"></script>
{*
		<script type="module" src="https://unpkg.com/ionicons@6.0.0/dist/ionicons/ionicons.esm.js"></script>
		<script nomodule src="https://unpkg.com/ionicons@6.0.0/dist/ionicons/ionicons.js"></script>
*}
{* moved inline js to display_login_page() header setup
		<script>{literal}
			$(function() {
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
				$("body").overlayScrollbars();
				//$('body').Layout('fixLayoutHeight');

				// scrollbar for shortcuts bar
				$("#shorcuts-crol-sidebar").overlayScrollbars({className: "os-theme-light"});
			});
		{/literal}</script>
*}
	</head>

	<body class="hold-transition sidebar-mini {* layout-footer-fixed *} layout-fixed layout-navbar-fixed {*control-sidebar-push*}">

{*		<div class="wrapper"> *}

			<nav class="main-header navbar navbar-dark">
				<ul class="navbar-nav">
					<li class="nav-item">
						<a class="nav-link" data-widget="pushmenu" href="javascript:void()"><i class="fas fa-bars"></i></a>
					</li>
{*					<li class="nav-item d-none d-sm-inline-block">
						<a href="index.php?{$secureparam}" title="{'home'|lang}" class="nav-link"><i class="fas fa-home"></i></a>
					</li>
					redundant maybe? (JM)
					<li class="nav-item d-none d-sm-inline-block">
						<a href="useraccount.php?{$secureparam}" class="nav-link">Account</a>
					</li>
*}
				</ul>
				<a id="headerlogo" href="https://www.cmsmadesimple.org" rel="external" title="CMSMS Home" target="_blank"></a>
				{include file='shortcuts.tpl'}
			</nav>

		<div class="wrapper">

			<aside class="main-sidebar sidebar-dark-primary elevation-4">
{* page layout doesn't work {if isset($sitelogo)}
				<a href="menu.php?{$secureparam}" title="{'home'|lang}"<img src="{$sitelogo}" class="brand-image elevation-3" /></a>
				{/if}
				<span class="brand-text font-weight-light">{sitename}</span>
*}
				<div class="sidebar">
{* only useful if effective user != user	{include file='user_panel.tpl'*}
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
										{if isset($module_icon_url)}<img src="{$module_icon_url}" alt="{$module_name|default:''}" class="module-icon" />{/if}
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

{*			{include file='footer.tpl'*}
			{$footertext|default:''}

		</div>
		{include file='messages.tpl'}
		{include file='bookmarks.tpl'}
	</body>
</html>
{* +++++++++++++++++++++++++++++++++++++++++++++++++++++++

	<div id="oe_container" class="sidebar-on">

		<header role="banner" class="header cf">
			<div class="header-top cf">
				<div class="cms-logo">
					<a href="https://www.cmsmadesimple.org" rel="external">
						<img src="themes/LTE/images/layout/cmsms-logo.jpg" width="205" height="69" alt="CMS Made Simple" title="CMS Made Simple" />
					</a>
				</div>
				<span class="admin-title"> {'adminpaneltitle'|lang} - {sitename}</span>
			</div>

			<div class="header-bottom cf">
				<div class="welcome" data-username="{$user->username}">
					{if isset($myaccount)}
						<span><a class="welcome-user" href="useraccount.php?{$secureparam}" title="{'myaccount'|lang}"><i class="fa fa-user"></i></a> {'welcome_user'|lang}: <a href="useraccount.php?{$secureparam}">{$user->firstname|default:$user->username}</a></span>
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

{ *		{include file='footer.tpl'* }
		{$footertext|default:''}

	</div>
+++++++++++++++++++++++++++++++++++++++++++++++++++++++ *}
