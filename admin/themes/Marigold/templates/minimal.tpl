<!doctype html>
<html>
	<head>
		<meta charset="utf-8" />
		<title>{$title} - {sitename}</title>
		<base href="{$admin_root}/"/>
		<meta name="generator" content="CMS Made Simple - Copyright (C) 2004-2018 - All rights reserved" />
		<meta name="robots" content="noindex, nofollow" />
		<meta name="viewport" content="initial-scale=1.0 maximum-scale=1.0 user-scalable=no" />
		<meta name="HandheldFriendly" content="True"/>
		<link rel="shortcut icon" href="{$theme_root}/images/favicon/cmsms-favicon.ico"/>
		{block name='css'}{$dynamic_css|default:''}{/block}
  	        {block name='js'}
		        <!-- learn IE html5 -->
		        <!--[if lt IE 9]>
			    <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
			<![endif]-->
			{cms_jquery exclude="jquery.ui.nestedSortable-1.3.4.js,jquery.json-2.2.js"}
		{/block}
		{$dynamic_headtext|default:''}
	</head>
	<body id="{$pageid|default:''}">
	        <section id="wrapper">
	                {block name='content'}{$content|default:''}{/block}
  	        </section>
	        <footer>
	                {block name='footer'}{$footer|default:''}{/block}
	        </footer>
	</body>
</html>
