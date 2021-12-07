<!DOCTYPE html>
<html lang="en">{* Change lang="en" to the language of your site *}
{* Note: anything inside these is a Smarty comment, it will not show up in the page source *}
  <head>
{cms_lang_info assign='nls'}{* With cms_lang_info we retrieve current language information, the assign gives us $nls variable we can work with *}
    <meta charset="{$nls->encoding()}">
    {metadata nocache}{* Don't remove this! Metadata is entered in Site Admin/Global settings. *}
    <title>{sitename} - {title nocache}</title>
    {if isset($canonical)}<link rel="canonical" href="{$canonical}">{elseif isset($content_obj)}<link rel="canonical" href="{$content_obj->GetURL()}">{/if} {* see the News Detail template for how canonical url can be assigned from module *}
    <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css2?family=Noto+Sans+Mono:wght@500&family=Noto+Sans:ital,wght@0,400;0,700;1,400&family=Noto+Serif:ital,wght@0,400;0,700;1,400&display=swap">
    {cms_stylesheet min=false}{* This is how the stylesheet(s) assigned to pages using this template are brought in. Omit min param for production. *}
    <link rel="icon" type="image/x-icon" href="{$_site_themes_url}/Steppo/media/favicon_cms.ico">
{$fp=cms_join_path($_site_themes_path,'Steppo','js','functions.min.js')}{cms_queue_script file=$fp}{* TODO js for alternate nav *}
    {cms_render_scripts defer=false}
  </head>
  <body>
    <div id="topnav">{* Start Flexbox row *}
      {Navigator loadprops=0 template='cssmenu' no_of_levels=1 nocache}
    </div>{* End Flexbox row *}

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
      <div>
        {Search}
      </div>
    </header>
    <h2 class="accessibility">Navigation</h2>

    <main>
      <div class="rowbox">{* Start Flexbox row *}
        <div id="col1">
          {content block='Sidebar'}
        </div>
        <div id="col2">
          {content}
        </div>
      </div>{* End Flexbox row *}
    </main>

    <footer>
    {include file='cms_template:Footer'}
    </footer>
    <h2 class="accessibility">Navigation</h2>
    <div id="bottomnav">{* Start Flexbox row *}
      {Navigator loadprops=0 template='cssmenu' start_level=2 nocache}
    </div>{* End Flexbox row *}
  </body>
</html>
