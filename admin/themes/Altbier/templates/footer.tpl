{block name=footer}
{strip}
<footer class="w-100 row" id="ac_footer">
	<div class="container-fluid">
		<div class="row pt-2">
			<div class="col align-self-start">
				<small class="copyright">Copyright &copy; <a rel="external" href="http://www.cmsmadesimple.org">CMS Made Simple&trade; {cms_version} &ldquo;{cms_versionname}&rdquo;</a></small>
			</div>
			<div class="col-auto align-self-center">
				<ul class="links ml-auto text-sm-right">
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
			<div class="col-auto align-self-end">
				<ul class="social-icons ml-auto text-sm-right">
					<li class="facebook"><a href="https://www.facebook.com/cmsmadesimple" target="_blank" title="Facebook"><i aria-hidden="true" class="fab fa-facebook-square fa-lg"></i></a></li>
					<li class="googleplus"><a href="https://plus.google.com/+cmsmadesimple" target="_blank" title="Google+"><i aria-hidden="true" class="fab fa-google-plus-square fa-lg"></i></a></li>
					<li class="linkedin"><a href="https://www.linkedin.com/groups?gid=1139537" target="_blank" title="Linkedin"><i aria-hidden="true" class="fab fa-linkedin fa-lg"></i></a></li>
					<li class="twitter"><a href="https://twitter.com/cmsms" target="_blank" title="Twitter"><i aria-hidden="true" class="fab fa-twitter-square fa-lg"></i></a></li><li class="youtube"><a href="https://www.youtube.com/user/cmsmsofficial" target="_blank" title="YouTube"><i aria-hidden="true" class="fab fa-youtube-square fa-lg"></i></a></li>
				</ul>
			</div>
		</div>
	</div>
</footer>
{/strip}
{/block}
