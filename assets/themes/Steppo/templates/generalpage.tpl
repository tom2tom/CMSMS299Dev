<!DOCTYPE html>
<html lang="en">{* Change lang="en" to the language of your site *}
{* Note: anything inside these is a Smarty comment, it will not show up in the page source *}
  <head>
{cms_lang_info assign='nls'}{* With cms_lang_info we retrieve current language information, the assign gives us $nls variable we can work with *}
    <meta charset="{$nls->encoding()}">
    {metadata nocache}{* Don't remove this! Metadata is entered in Site Admin/Global settings. *}
    <title>{sitename} - {title nocache}</title>
    <meta name="HandheldFriendly" content="True">
    <meta name="MobileOptimized" content="320">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="cleartype" content="on">
    <meta name="msapplication-TileImage" content="{$_site_themes_url}/Steppo/media/cmsms-152x152.png">
    <meta name="msapplication-TileColor" content="#595a5c">{* medium-dark grey *}
    {if isset($canonical)}<link rel="canonical" href="{$canonical}">{elseif isset($content_obj)}<link rel="canonical" href="{$content_obj->GetURL()}">{/if} {* see the News Detail template for how canonical url can be assigned from module *}
    <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css2?family=Noto+Sans+Mono:wght@500&family=Noto+Sans:ital,wght@0,400;0,700;1,400&family=Noto+Serif:ital,wght@0,400;0,700;1,400&display=swap">
    {cms_stylesheet min=false}{* This is how the stylesheet(s) assigned to pages using this template are brought in. Omit min param for production. *}
    <link rel="apple-touch-icon-precomposed" sizes="152x152" href="{$_site_themes_url}/Steppo/media/cmsms-152x152.png">
    <link rel="apple-touch-icon-precomposed" sizes="120x120" href="{$_site_themes_url}/Steppo/media/cmsms-120x120.png">
    <link rel="apple-touch-icon-precomposed" sizes="72x72" href="{$_site_themes_url}/Steppo/media/cmsms-76x76.png">
    <link rel="apple-touch-icon-precomposed" href="{$_site_themes_url}/Steppo/media/cmsms-60x60.png">
    <link rel="shortcut icon" sizes="196x196" href="{$_site_themes_url}/Steppo/media/cmsms-196x196.png">
    <link rel="shortcut icon" href="{$_site_themes_url}/Steppo/media/cmsms-60x60.png">
    <link rel="icon" type="image/x-icon" href="{$_site_themes_url}/Steppo/media/favicon_cms.ico">
{$fp=cms_join_path($_site_themes_path,'Steppo','js','functions.min.js')}{cms_queue_script file=$fp}
    {cms_render_scripts defer=false}
  </head>
  <body>

    <header>
{* TODO show dark-texted logo when relevant *}
      <a class="logo" href="{$_site_root_url}"><img src="{$_site_themes_url}/Steppo/media/cmsms-logotext-light.svg" alt="{sitename}"></a>
      <!-- Start accessibility links, jump to nav or content -->
      <ul class="accessibility">
        <li><a href="#nav" title="Skip to navigation" accesskey="n">Skip to navigation</a></li>
        <li><a href="#main" title="Skip to content" accesskey="s">Skip to content</a></li>
      </ul>
      <!-- End accessibility links -->
      <input type="checkbox" aria-hidden="true" id="nav_toggle">
      <label for="nav_toggle" aria-hidden="true">Menu</label>
      <div id="nav">
        {Search submit='' searchtext=''}
        <nav aria-label="Main">
        {Navigator loadprops=0 template='Simple Navigation' collapse=1 nocache}
        </nav>
      </div>
    </header>

    <main>
      {block name=focus}{/block}{* Sub-templates may include stuff here to tailor the presentation *}
      <!-- Start Content -->
      <p id="main">{content}</p>
      <!-- End Content -->
    </main>

    <footer>
    {include file='cms_template:Footer'}
    </footer>
  </body>
</html>
