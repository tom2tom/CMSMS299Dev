{block name=footer}
{strip}
<footer id="oe_footer" class="cf">
	<div class="footer-left"></div>
	<div class="footer-right cf">
		<span id="aboutinfo" style="display:none"><a href="javascript:OE.aboutToggle();">CMSMS {lang('version')} {cms_version} &ldquo;{cms_versionname}&rdquo;</a></span>
		<ul class="links">
			<li>
				<a href="javascript:OE.aboutToggle();">{lang('about')}</a>
			</li>
			<li>
				<a href="https://docs.cmsmadesimple.org/" rel="external">{lang('documentation')}</a>
			</li>
			<li>
				<a href="https://forum.cmsmadesimple.org/" rel="external">{lang('forums')}</a>
			</li>
			<li>
			{if isset($site_help_url)}
				<a href="{$site_help_url}" rel="external">{lang('site_support')}</a>
			{else}
				<a href="https://www.cmsmadesimple.org/support/options/" rel="external">{lang('site_support')}</a>
			{/if}
			</li>
{*
			<li>
				<a href="https://www.cmsmadesimple.org/about-link/" rel="external">{lang('about')}</a>
			</li>
			<li>
				<a href="https://www.cmsmadesimple.org/about-link/about-us/" rel="external">{lang('team')}</a>
			</li>
*}
		</ul>
	</div>
</footer>
{/strip}
{/block}
