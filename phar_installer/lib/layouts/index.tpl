<!DOCTYPE html>
<!--[if gt IE 8]><!--> <html lang="en"> <!--<![endif]-->
  <head>
{if isset($BASE_HREF)}    <base href="{$BASE_HREF}">{/if}
    <meta charset="UTF-8">
    <meta name="HandheldFriendly" content="True">
    <meta name="MobileOptimized" content="320">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="cleartype" content="on">
    <link rel="icon" type="image/ico" href="lib/images/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,700;1,400;1,700&display=swap">
    <link rel="stylesheet" href="lib/js/jquery-ui/jquery-ui.min.css">
    <link rel="stylesheet" href="lib/js/jquery-ui/jquery-ui.theme.min.css">
    <link rel="stylesheet" href="lib/styles/install.min.css">{*TODO use -rtl variant if relevant*}
    <script src="lib/js/jquery.min.js"></script>
    <script src="lib/js/jquery-ui/jquery-ui.min.js"></script>
    <title>
    {if !empty($browser_title)}
    {$browser_title}
    {elseif !empty($title)}
    {$title nocache} - CMS Made Simple&trade; {tr('apptitle')}
    {else}
    CMS Made Simple&trade; {tr('apptitle')}}
    {/if}
    </title>
  </head>
  {strip}{block name='logic'}{/block}{/strip}
  <body>
    <div class="page-row header-section">
      <a href="http://www.cmsmadesimple.org" rel="external" target="_blank" class="cmsms-logo" title="CMS Made Simple&trade;">
       <img class="cmslogo" src="lib/images/cmsms-logo.svg" onerror="this.onerror=null;this.src='lib/images/cmsms-logo.png';" alt="CMS Made Simple" title="CMS Made Simple">
      </a>
      <span class="installer-title">{tr('apptitle')}</span>
    </div>
    <div class="row no-gutter installer-section">
      <div class="cell col-4 installer-steps-section">
        <div class="inside">
        {block name='aside_content'}
          {if isset($wizard_steps)}
          <aside class="installer-steps">
            <ol id="installer-indicator">
              {foreach $wizard_steps as $classname => $step}
              {strip}
              <li class="step{if $step.active} current-step{/if}{if isset($current_step) && $current_step > $step@iteration} done-step{/if}">
                <h4 class="step-title">{tr($step.classname)}</h4>
                <p class="step-description">{tr("desc_`$step.classname`")}</p>
              </li>
              {/strip}
              {/foreach}
            </ol>
          </aside>
          {/if}
        {/block}
        </div>
      </div>
      <main role="main" class="cell col-8 installer-content-section">
        <div class="inside">
          <h1>{if isset($title)}{$title}{else}{tr('install_upgrade')}{/if}</h1>
          {if isset($subtitle)}<h3>{$subtitle}</h3>{/if}
{*          {if isset($dir) && ($in_phar || $cur_step > 1)}
          <div class="message blue icon">
            <i class="icon-folder message-icon"></i>
            <div class="content"><span class="heavy">{tr('prompt_dir')}:</span><br>{$dir}</div>
          </div>
          {/if}*}
          {if isset($error)}
          <div class="message red">
            {$error}
          </div>
          {/if}
          <article>
            {block name='contents'}WIZARD CONTENTS GO HERE{/block}
            {block name='content-footer'}{/block}
          </article>
        </div>
      </main>
    </div>
    <footer class="page-row footer-section">
{*      <div class="footer-info">
        <a href="https://forum.cmsmadesimple.org" target="_blank">{tr('title_forum')}</a> &bull; <a href="https://docs.cmsmadesimple.org" target="_blank">{tr('title_docs')}</a> &bull; <a href="http://apidoc.cmsmadesimple.org" target="_blank">{tr('title_api_docs')}</a>
      </div>*}
      <span class="shrimp">
        Copyright &copy; 2004-{$smarty.now|date_format:'Y'} <a href="http://www.cmsmadesimple.org">CMS Made Simple Foundation</a>. All rights reserved.{if isset($installer_version)}&nbsp;{tr('installer_ver')}:&nbsp;{$installer_version}{/if}
      </span>
    </footer>
  {block name='javascript'}
  {/block}
  </body>
</html>