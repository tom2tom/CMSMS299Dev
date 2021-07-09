<h3>{$title_section}</h3>
{$startform}

<p>{$help_section}</p>

{if $step == 1}
<div class="pageoverflow">
    <p class="pageinput"><strong>{$prompt_packagename}</strong>&nbsp;{$input_packagename}</p>
</div>
<div class="pageoverflow">
    <p class="pageinput"><strong>{$prompt_packageversion}</strong>&nbsp;{$input_packageversion}</p>
</div>
<div class="pageoverflow">
    <p class="pageinput"><strong>{$prompt_authorname}</strong>&nbsp;{$input_authorname}</p>
</div>
<div class="pageoverflow">
    <p class="pagetext">{$prompt_notes}</p>
    <p class="pageinput">{$input_notes}</p>
</div>
<div class="pageoverflow">
    <p class="pagetext"><h3>{$mod->Lang('packageincludes')}:</h3></p>
</div>

{if $found_templates|@sizeof > 0}
<div class="pageoverflow">

    <p class="pagetext">{$mod->Lang('templates')}</p>
    {foreach from=$found_templates item=template}
    <p class="pageinput">{$template.template_name}</p>
    {/foreach}
</div>
{/if}

{if $found_styles|@sizeof > 0}
<div class="pageoverflow">
    <p class="pagetext">{$mod->Lang('stylesheets')}</p>
    {foreach from=$found_styles item=style}
    <p class="pageinput">{$style.css_name}</p>
    {/foreach}
</div>
{/if}

{if $found_pages|@sizeof > 0}
<div class="pageoverflow">
    <p class="pagetext">{$mod->Lang('pages')}</p>
    {foreach from=$found_pages item=page}
    <p class="pageinput">{$page.content_name} ({$page.hierarchy_path})</p>
    {/foreach}
</div>
{/if}

{if $found_modules|@sizeof > 0}
<div class="pageoverflow">
    <p class="pagetext">{$mod->Lang('moduletemplates')}</p>
    {foreach from=$found_modules item=module_template}
    <p class="pageinput">{$module_template.template_name} ({$module_template.module_name})</p>
    {/foreach}
</div>
{/if}

{if $found_blocks|@sizeof > 0}
<div class="pageoverflow">
    <p class="pagetext">{$mod->Lang('gbls')}</p>
    {foreach from=$found_blocks item=block}
    <p class="pageinput">{$block.htmlblob_name}</p>
    {/foreach}
</div>
{/if}

{if $found_udts|@sizeof > 0}
<div class="pageoverflow">
    <p class="pagetext">{$mod->Lang('udts')}</p>
    {foreach from=$found_udts item=udt}
    <p class="pageinput">{$udt.userplugin_name}</p>
    {/foreach}
</div>
{/if}

{if $found_files|@sizeof > 0}
<div class="pageoverflow">
    <p class="pagetext">{$mod->Lang('files')}</p>
    {foreach from=$found_files item=file}
    <p class="pageinput">{$file.location}</p>
    {/foreach}
</div>
{/if}

{if $found_settings|@sizeof > 0}
<div class="pageoverflow">
    <p class="pagetext">{$mod->Lang('modulesettings')}</p>
    {foreach from=$found_settings item=setting}
    <p class="pageinput">{$setting.modulename}</p>
    {/foreach}
</div>
{/if}

{/if}

{if $step == 2}

<div class="pageoverflow">
    <p class="pageinput">{$mod->Lang('prompt_cmsmsversion')} {$check_cmsmsversion}</p>
</div>
<div class="pageoverflow">
    <p class="pageinput">{$mod->Lang('prompt_thmgrversion')} {$check_thmgrversion}</p>
</div>
{if $module_versions|@sizeof > 0}
<div class="pageoverflow">
    <h3>{$mod->Lang('moduleversions')}</h3>

</div>
{foreach from=$module_versions item=module}
<div class="pageoverflow">
    <p class="pageinput">{$module.name} {$module.version} {$module.info}</p>
</div>
{/foreach}
{/if}

{/if}


{if $step == 3}

{if $found_templates|@sizeof > 0}
<div class="pageoverflow">
    <p class="pagetext">Templates</p>
    {foreach from=$found_templates item=template}
    <p class="pageinput">{$template.info} {$template.template_name}</p>
    {/foreach}
</div>
{/if}

{if $found_styles|@sizeof > 0}
<div class="pageoverflow">
    <p class="pagetext">Styles</p>
    {foreach from=$found_styles item=style}
    <p class="pageinput">{$style.info} {$style.css_name}</p>
    {/foreach}
</div>
{/if}

{if $found_pages|@sizeof > 0}
<div class="pageoverflow">
    <p class="pagetext">Pages</p>
    {foreach from=$found_pages item=page}
    <p class="pageinput"> {$page.info} {$page.content_name} ({$page.hierarchy_path})</p>
    {/foreach}
</div>
{/if}

{if $found_modules|@sizeof > 0}
<div class="pageoverflow">
    <p class="pagetext">Module Templates</p>
    {foreach from=$found_modules item=module}
    <p class="pageinput">{$module.info} {$module.template_name} ({$module.module_name})</p>
    {/foreach}
</div>
{/if}

{if $found_blocks|@sizeof > 0}
<div class="pageoverflow">
    <p class="pagetext">Global Content Blocks</p>
    {foreach from=$found_blocks item=block}
    <p class="pageinput">{$block.info} {$block.htmlblob_name}</p>
    {/foreach}
</div>
{/if}

{if $found_udts|@sizeof > 0}
<div class="pageoverflow">
    <p class="pagetext">User Defined Tags</p>
    {foreach from=$found_udts item=udt}
    <p class="pageinput">{$udt.info} {$udt.userplugin_name}</p>
    {/foreach}
</div>
{/if}

{if $found_files|@sizeof > 0}
<div class="pageoverflow">
    <p class="pagetext">Files</p>
    {foreach from=$found_files item=file}
    <p class="pageinput"> {$file.info} {$file.location}</p>
    {/foreach}
</div>
{/if}


{/if}


<div class="pageoverflow">
    <p class="pagetext">&nbsp;</p>
    <p class="pageinput">{$nextstep}{$submit}</p>
</div>
{$endform}