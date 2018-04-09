<!doctype html>
<html lang="{$lang_code}" dir="{$lang_dir}">
<head>
  <meta charset="{$encoding}" />
  <title>{lang('login_sitetitle', {sitename})}</title>
  <base href="{$module_url}/" />
  <meta name="copyright" content="Ted Kulp, CMS Made Simple" />
  <meta name="robots" content="noindex, nofollow" />
  <meta name="viewport" content="initial-scale=1.0 maximum-scale=1.0" />
  <meta name="HandheldFriendly" content="true" />
  <link rel="shortcut icon" href="{$module_url}/images/favicon/cmsms-favicon.ico" />
  <link rel="stylesheet" type="text/css" href="{$module_url}/css/admin_login{if $lang_dir == 'rtl'}-rtl{/if}.css" />
  {$header_includes|default:''}
</head>
<body>{include file='admin_login_core.tpl'}</body>
{$bottom_includes|default:''}
</html>
