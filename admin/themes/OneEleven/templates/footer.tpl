{block name=footer}
{strip}
<footer id="oe_footer" class="cf">
	<div class="footer-left">
		<small class="copyright">Copyright &copy; <a rel="external" href="http://www.cmsmadesimple.org">CMS Made Simple&trade; {cms_version} &ldquo;{cms_versionname}&rdquo;</a></small>
	</div>
	<div class="footer-right cf">
		<ul class="links">
			<li>
				<a href="https://docs.cmsmadesimple.org/" rel="external" title="{'documentation'|lang}">{'documentation'|lang}</a>
			</li>			
			<li>
				<a href="https://forum.cmsmadesimple.org/" rel="external" title="{'forums'|lang}">{'forums'|lang}</a>
			</li>
			<li>
			{if isset($site_help_url)}
				<a href="{$site_help_url}" title="{'site_support'|lang}">{'site_support'|lang}</a>
			{else}
				<a href="https://www.cmsmadesimple.org/support/options/" rel="external" title="{'site_support'|lang}">{'site_support'|lang}</a>
			{/if}
			</li>
			<li>
				<a href="http://www.cmsmadesimple.org/about-link/" rel="external" title="{'about'|lang}">{'about'|lang}</a>
			</li>
			<li>
				<a href="http://www.cmsmadesimple.org/about-link/about-us/" rel="external" title="{'team'|lang}">{'team'|lang}</a>
			</li>
		</ul>
	</div>
</footer>
{/strip}
{/block}
