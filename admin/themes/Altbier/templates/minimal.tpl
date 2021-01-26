<!doctype html>
<html>
 <head>
  <meta charset="utf-8" />
  <title>{$title} - {sitename}</title>
  <meta name="generator" content="CMS Made Simple" />
  <meta name="robots" content="noindex, nofollow" />
  <meta name="viewport" content="initial-scale=1.0 maximum-scale=1.0 user-scalable=no" />
  <meta name="HandheldFriendly" content="true" />
  <base href="{$admin_root}/" />
  <link rel="shortcut icon" href="themes/assets/images/cmsms-favicon.ico" />
  {block name='header'}{$header_includes|default:''}{/block}
 </head>
 <body{if !empty($bodyid)} id="{$bodyid}"{/if}>
  <section id="wrapper">
   {block name='content'}{$content|default:''}{/block}
  </section>
  <footer>
   {block name='footer'}{$bottom_includes|default:''}{/block}
  </footer>
 </body>
</html>
