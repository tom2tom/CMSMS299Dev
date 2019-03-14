{block name=footer}
{strip}
  <div class="footer-left">
   <a rel="external" href="http://www.cmsmadesimple.org">CMS Made Simple&trade;</a> {cms_version} &ldquo;{cms_versionname}&rdquo;
  </div>
  <div class="footer-right cf">
    <ul class="links">
      <li>
        <a href="https://docs.cmsmadesimple.org/" rel="external" title="{lang('documentationtip')}">{lang('documentation')}</a>
      </li>
      <li>
        <a href="https://forum.cmsmadesimple.org/" rel="external" title="{lang('forums')}">{lang('forums')}</a>
      </li>
      <li>
      {if isset($site_help_url)}
        <a href="{$site_help_url}" title="{lang('site_support')}">{lang('site_support')}</a>
      {else}
        <a href="https://www.cmsmadesimple.org/support/options/" rel="external" title="{lang('site_support')}">{lang('site_support')}</a>
      {/if}
      </li>
      <li>
        <a href="http://www.cmsmadesimple.org/about-link/" rel="external" title="{lang('about')}">{lang('about')}</a>
      </li>
      <li>
        <a href="http://www.cmsmadesimple.org/about-link/about-us/" rel="external" title="{lang('team')}">{lang('team')}</a>
      </li>
    </ul>
  </div>
{/strip}
{/block}
