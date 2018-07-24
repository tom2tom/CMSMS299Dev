<!doctype html>
<html lang="{$lang_code|truncate:'2':''}" dir="{$lang_dir|default:'ltr'}">
	<head>
{$thetitle=$pagetitle}
{if $thetitle && $subtitle}{$thetitle="{$thetitle} - {$subtitle}"}{/if}
{if $thetitle}{$thetitle="{$thetitle} - "}{/if}
		<title>{$thetitle}{sitename}</title>
		<meta charset="utf-8" />
		<meta name="generator" content="CMS Made Simple" />
		<meta name="robots" content="noindex, nofollow" />
		<meta name="viewport" content="initial-scale=1.0 maximum-scale=1.0 user-scalable=no" />
		<meta name="referrer" content="origin" />
		<meta name="HandheldFriendly" content="true" />
		<meta name="msapplication-TileColor" content="#f89938" />
		<meta name="msapplication-TileImage" content="{$admin_url}/themes/OneEleven/images/favicon/ms-application-icon.png" />
		<base href="{$admin_url}/" />
		<link rel="shortcut icon" href="themes/OneEleven/images/favicon/cmsms-favicon.ico" />
		<link rel="apple-touch-icon" href="themes/OneEleven/images/favicon/apple-touch-icon-iphone.png" />
		<link rel="apple-touch-icon" sizes="72x72" href="themes/OneEleven/images/favicon/apple-touch-icon-ipad.png" />
		<link rel="apple-touch-icon" sizes="114x114" href="themes/OneEleven/images/favicon/apple-touch-icon-iphone4.png" />
		<link rel="apple-touch-icon" sizes="144x144" href="themes/OneEleven/images/favicon/apple-touch-icon-ipad3.png" />
{if isset($header_includes)}{$header_includes}{else}
		<link rel="stylesheet" href="style.php?{$secureparam}" />
		<link rel="stylesheet" href="themes/OneEleven/css/default-cmsms/jquery-ui-1.10.4.custom.min.css" />
		{cms_jquery append="{$admin_url}/themes/OneEleven/includes/standard.js" include_css=0}
		<!--[if lt IE 9]>
		<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
		<![endif]-->
{/if}
	</head>
	<body id="{$pagetitle|md5}" class="oe_{$pagealias}">
		<!-- start container -->
		<div id="oe_container" class="sidebar-on">
			<!-- start header -->
			<header role="banner" class="cf header">
				<!-- start header-top -->
				<div class="header-top cf">
					<!-- logo -->
					<div class="cms-logo">
						<a href="http://www.cmsmadesimple.org" rel="external"><img src="{$admin_url}/themes/OneEleven/images/layout/cmsms-logo.jpg" width="205" height="69" alt="CMS Made Simple" title="CMS Made Simple" /></a>
					</div>
					<!-- title -->
					<span class="admin-title"> {'adminpaneltitle'|lang} - {sitename}</span>
				</div>
				<div class='clear'></div>
				<!-- end header-top //-->
				<!-- start header-bottom -->
				<div class="header-bottom cf">
					<!-- welcome -->
					<div class="welcome">
					{if isset($myaccount)}
						<span><a class="welcome-user" href="myaccount.php?{$secureparam}" title="{'myaccount'|lang}">{'myaccount'|lang}</a> {'welcome_user'|lang}: <a href="myaccount.php?{$secureparam}">{$username}</a></span>
					{else}
						<span><a class="welcome-user">{'myaccount'|lang}</a> {'welcome_user'|lang}: {$username}</span>
					{/if}
					</div>
					<!-- bookmarks -->
					{include file='shortcuts.tpl'}
				</div>
				<!-- end header-bottom //-->
			</header>
			<!-- end header //-->
			<!-- start content -->
			<div id="oe_admin-content">
				<div class="shadow">
					&nbsp;
				</div>
				<!-- start sidebar -->
				<div id="oe_sidebar">
				  <aside>
				    <span title="{'open'|lang}/{'close'|lang}" class="toggle-button close">{'open'|lang}/{'close'|lang}</span>
 			            {include file='navigation.tpl' nav=$theme->get_navigation_tree()}
				    </aside>
				</div>
				<!-- end sidebar //-->
				<!-- start main -->
				<div id="oe_mainarea" class="cf">
					{strip}
					{include file='messages.tpl'}
					<article role="main" class="content-inner">
					  <header class="pageheader{if isset($is_ie)} drop-hidden{/if} cf">
						{if !empty($pageicon) || !empty($pagetitle)}
							<h1>{if !empty($pageicon)}<span class="headericon">{$pageicon}</span> {/if}{$pagetitle|default:''}</h1>
						{/if}
						{if isset($module_help_url)} <span class="helptext"><a href="{$module_help_url}">{'module_help'|lang}</a></span>{/if}
					</header>
					{if $pagetitle && $subtitle}<header class="subheader"><h3 class="subtitle">{$subtitle}</h3></header>{/if}
						<section class="cf">
							<div class="pagecontainer">{$content}</div>
						</section>
					</article>
					{/strip}
				</div>
				<!-- end main //-->
				<div class="spacer">
					&nbsp;
				</div>
			</div>
			<!-- end content //-->
			<!-- start footer -->
			{include file='footer.tpl'}
			<!-- end footer //-->
		</div>
		<!-- end container //-->
	{$bottom_includes|default:''}
	</body>
</html>
