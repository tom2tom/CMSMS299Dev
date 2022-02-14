<!DOCTYPE html>
<!--[if gt IE 8]><!--> <html lang="en"> <!--<![endif]-->
  <head>
    <base href="{$BASE_HREF}" />
    <meta charset="utf-8">
    <meta name='HandheldFriendly' content='True' />
    <meta name='MobileOptimized' content='320' />
    <meta name='viewport' content='width=device-width, initial-scale=1.0' />
    <meta http-equiv='cleartype' content='on' />
    <link rel="icon" type="image/ico" href="lib/images/favicon.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,700;1,400;1,700&display=swap" />
    <link rel="stylesheet" type="text/css" href="lib/js/jquery-ui/jquery-ui.min.css" />
    <link rel="stylesheet" type="text/css" href="lib/styles/install.min.css" />{*TODO use -rtl variant if relevant*}
    <script src="lib/js/jquery.min.js"></script>
    <script src="lib/js/jquery-ui/jquery-ui.min.js"></script>
    <title>
    {if !empty($browser_title)}
    {$browser_title}
    {elseif !empty($title)}
    {$title nocache} - CMS Made Simple&trade; {'apptitle'|tr}
    {else}
    CMS Made Simple&trade; {'apptitle'|tr}}
    {/if}
    </title>
  </head>
  {strip}{block name='logic'}{/block}{/strip}
  <body>
    <div class="page-row header-section">
      <a href="http://www.cmsmadesimple.org" rel="external" target="_blank" class="cmsms-logo" title="CMS Made Simple&trade;">
       <img class="cmslogo" src="lib/images/cmsms-logo.svg" onerror="this.onerror=null;this.src='lib/images/cmsms-logo.png';" alt="CMS Made Simple" title="CMS Made Simple" />
      </a>
      <span class="installer-title">{'apptitle'|tr}</span>
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
                <h4 class="step-title">{$step.classname|tr}</h4>
                <p class="step-description">{'desc_'|cat:$step.classname|tr}</p>
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
          <h1>{if isset($title)}{$title}{else}{'install_upgrade'|tr}{/if}</h1>
          {if isset($subtitle)}<h3>{$subtitle}</h3>{/if}
{*          {if isset($dir) && ($in_phar || $cur_step > 1)}
          <div class="message blue icon">
            <i class="icon-folder message-icon"></i>
            <div class="content"><span class="heavy">{'prompt_dir'|tr}:</span><br />{$dir}</div>
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
        <a href="https://forum.cmsmadesimple.org" target="_blank">{'title_forum'|tr}</a> &bull; <a href="https://docs.cmsmadesimple.org" target="_blank">{'title_docs'|tr}</a> &bull; <a href="http://apidoc.cmsmadesimple.org" target="_blank">{'title_api_docs'|tr}</a>
      </div>*}
      <span class="shrimp">
        Copyright &copy; 2004-{$smarty.now|date_format:'Y'} <a href="http://www.cmsmadesimple.org">CMS Made Simple Foundation</a>. All rights reserved.{if isset($installer_version)}&nbsp;{'installer_ver'|tr}:&nbsp;{$installer_version}{/if}
      </span>
    </footer>
  {block name='javascript'}
  <script>
  var cmsms_lang = {
    freshen : '{'confirm_freshen'|tr|addslashes}',
    upgrade : '{'confirm_upgrade'|tr|addslashes}',
    message : '{'social_message'|tr|addslashes}'
  };
  </script>
  {/block}
  </body>
</html>
