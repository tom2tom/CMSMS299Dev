{block name=footer}
{strip}
  <div class="footer-left">
    <a rel="external" href="http://www.cmsmadesimple.org">CMS Made Simple</a>&trade; {cms_version} &ldquo;{cms_versionname}&rdquo;
  </div>
  <div class="footer-right">
    <ul class="links">
      <li>
        <a href="https://docs.cmsmadesimple.org/" rel="external" title="{_ld('admin','documentationtip')}">{_ld('admin','documentation')}</a>
      </li>
      <li>
        <a href="https://forum.cmsmadesimple.org/" rel="external" title="{_ld('admin','cms_forums')}">{_ld('admin','forums')}</a>
      </li>
      <li>
      {if isset($site_help_url)}
        <a href="{$site_help_url}" title="{_ld('admin','site_support')}">{_ld('admin','site_support')}</a>
      {else}
        <a href="https://www.cmsmadesimple.org/support/options/" rel="external" title="{_ld('admin','site_support')}">{_ld('admin','site_support')}</a>
      {/if}
      </li>
      <li>
        <a href="http://www.cmsmadesimple.org/about-link/" rel="external" title="{_ld('admin','about')}">{_ld('admin','about')}</a>
      </li>
      <li>
        <a href="http://www.cmsmadesimple.org/about-link/about-us/" rel="external" title="{_ld('admin','team')}">{_ld('admin','team')}</a>
      </li>
    </ul>
  </div>
{/strip}
{/block}
