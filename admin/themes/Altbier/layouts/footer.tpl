{block name=footer}
{strip}
<footer class="row no-gutter between align-center w-100" id="ab_footer">
{*	<div class="row between align-center w-100">*}
{*		<div class="cell">
			<small class="cell col-auto copyright">Copyright &copy; <a rel="external" href="http://www.cmsmadesimple.org">CMS Made Simple Foundation</a></small>
		</div>*}
		<div class="cell col-auto"></div>{*placeholder*}
			<div class="row end align-center">
				<ul class="cell col-auto external-icons">
					<span id="aboutinfo" style="display:none">CMSMS {'version'|lang} {cms_version} &ldquo;{cms_versionname}&rdquo;</span>
					<li>{$t='about'|lang}
						<a href="javascript:AB.aboutToggle();" title="{$t}"><i class="fa fa-info-circle" aria-title="{$t}"></i></a>
					</li>
{*					<li>
						<a href="https://www.cmsmadesimple.org" rel="external">
						<img  class="cmsnav" src="themes/assets/images/cmsms-icon.svg" onerror="this.onerror=null;this.src='themes/assets/images/cmsms-logo.png';" alt="CMS Made Simple" title="CMS Made Simple">
						</a>
					</li>
					<li>{$t='documentation'|lang}
						<a href="https://docs.cmsmadesimple.org/" rel="external" title=""{$t}>{$t}</a>
					</li>
*}
					<li>{$t='cms_forums'|lang}
						<a href="https://forum.cmsmadesimple.org/" rel="external" title="{$t}"><i class="fa fa-people-arrows" aria-title="{$t}"></i></a>
					</li>
{*					<li>
					{if isset($site_help_url)}{$t='site_support'|lang}
						<a href="{$site_help_url}" title="{$t}">{$t}</a>
					{else}{$t='site_support'|lang}
						<a href="https://www.cmsmadesimple.org/support/options/" rel="external" title="{$t}">{$t}</a>
					{/if}
					</li>
					<li>{$t='about'|lang}
						<a href="http://www.cmsmadesimple.org/about-link/" rel="external" title="{$t}">{$t}</a>
					</li>
*}
					<li>{$t='cms_team'|lang}
						<a href="http://www.cmsmadesimple.org/about-link/about-us/" rel="external" title="{$t}"><i class="fa fa-users-cog" aria-title="{$t}"></i></a>
					</li>
					<li class="help">
					{if isset($module_help_url)}{$t='module_help'|lang}
						<a href="{$module_help_url}" rel="external" title="{$t}"><i class="fa fa-question-circle" aria-title="{$t}"></i></a>
					{else}{$t='documentationtip'|lang}
						<a href="https://docs.cmsmadesimple.org/" rel="external" title="{$t}"><i class="fa fa-question-circle" aria-title="{$t}"></i></a>
					{/if}
					</li>
					<li class="help">
					{if isset($site_help_url)}{$t='site_support'|lang}
						<a href="{$site_help_url}" title="{$t}"><i class="fa fa-life-ring" aria-title="{$t}"></i></a>
					{else}{$t='site_support'|lang}
						<a href="https://www.cmsmadesimple.org/support/options/" rel="external" title="{$t}"><i class="fa fa-life-ring" aria-title="{$t}"></i></a>
					{/if}
					</li>
				</ul>
				<ul class="cell col-auto social-icons">
					<li class="facebook"><a href="https://www.facebook.com/cmsmadesimple" target="_blank" title="CMSMS on Facebook"><i class="fab fa-facebook-square" aria-title="CMSMS on Facebook"></i></a></li>
					<li class="linkedin"><a href="https://www.linkedin.com/groups?gid=1139537" target="_blank" title="CMSMS on LinkedIn"><i class="fab fa-linkedin" aria-title="CMSMS on LinkedIn"></i></a></li>
					<li class="twitter"><a href="https://twitter.com/cmsms" target="_blank" title="CMSMS on Twitter"><i class="fab fa-twitter-square" aria-title="CMSMS on Twitter"></i></a></li>
				</ul>
			</div>
		</div>
{*	</div> *}
</footer>
{/strip}
{/block}
