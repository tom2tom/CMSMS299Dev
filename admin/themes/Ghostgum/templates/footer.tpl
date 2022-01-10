{block name=footer}
{strip}
  <div class="footer-left">
    <a rel="external" href="http://www.cmsmadesimple.org">CMS Made Simple</a>&trade; {cms_version} &ldquo;{cms_versionname}&rdquo;
  </div>
  <div class="footer-right">
    <ul class="links">
      <li>
        <a href="https://docs.cmsmadesimple.org/" rel="external" title="{_la('documentationtip')}">{_la('documentation')}</a>
      </li>
      <li>
        <a href="https://forum.cmsmadesimple.org/" rel="external" title="{_la('cms_forums')}">{_la('forums')}</a>
      </li>
      <li>
      {if isset($site_help_url)}
        <a href="{$site_help_url}" title="{_la('site_support')}">{_la('site_support')}</a>
      {else}
        <a href="https://www.cmsmadesimple.org/support/options/" rel="external" title="{_la('site_support')}">{_la('site_support')}</a>
      {/if}
      </li>
      <li>
        <a href="http://www.cmsmadesimple.org/about-link/" rel="external" title="{_la('about')}">{_la('about')}</a>
      </li>
      <li>
        <a href="http://www.cmsmadesimple.org/about-link/about-us/" rel="external" title="{_la('team')}">{_la('team')}</a>
      </li>
    </ul>
  </div>
{/strip}
{/block}
