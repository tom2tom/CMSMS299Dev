{block name=footer}
{strip}
<div class="row no-gutter end align-center">
  <span id="aboutinfo" style="display:none"><a href="javascript:themejs.aboutToggle()">CMSMS {_la('version')} {cms_version} &ldquo;{cms_versionname}&rdquo;</a></span>
  <ul class="links">
    <li>
      {$t=_la('about')}
      <a href="javascript:themejs.aboutToggle();" title="{$t}">{$t}</a>
    </li>
    <li>
      <a href="https://docs.cmsmadesimple.org/" rel="external" title="{_la('documentationtip')}">{_la('documentation')}</a>
    </li>
    <li>
      <a href="https://forum.cmsmadesimple.org/" rel="external" title="{_la('cms_forums')}">{_la('forums')}</a>
    </li>
    <li>
      {$t=_la('site_support')}
      <a href="{if isset($site_help_url)}{$site_help_url}{else}https://www.cmsmadesimple.org/support/options/{/if}" rel="external" title="{$t}">{$t}</a>
    </li>
  </ul>
</div>
{/strip}
{/block}
