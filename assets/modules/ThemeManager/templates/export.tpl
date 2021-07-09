<h3>{$title_section}</h3>
{$startform}
{if isset($message) && $message !=''}
<div class="pageerrorcontainer">
{$mod->Lang($message)}
</div>
{/if}

<p>{$help_section}</p>

{if $step < 9}
<table cellspacing="0" class="pagetable">
    <thead>
        <tr>
            <th>{$idtext}</th>
            <th>{$nametext}</th>
            <th><input id="selectall" type="checkbox" onclick="select_all();" /> {$actiontext}</th>
        </tr>
    </thead>
    <tbody>
        {if $table_items|@sizeof > 0}
	{foreach from=$table_items item=entry}
        <tr class="{cycle values="row1,row2"}">
            <td>{$entry.id}</td>
            <td>{$entry.name}</td>
            <td>{$entry.input}</td>
        </tr>
	{/foreach}
        {else}
        <tr>
            <td colspan="3">{$mod->Lang('no_components')}</td>
        </tr>
        {/if}
    </tbody>
</table>
{/if}

{if $step == 9}
<div class="pageoverflow">
    <p class="pagetext">{$prompt_packagename} *</p>
    <p class="pageinput">{$input_packagename}&nbsp;{$info_packagename}</p>
</div>
<div class="pageoverflow">
    <p class="pagetext">{$prompt_packageversion} *</p>
    <p class="pageinput">{$input_packageversion}&nbsp;{$info_packageversion}</p>
</div>
<div class="pageoverflow">
    <p class="pagetext">{$prompt_authorname}</p>
    <p class="pageinput">{$input_authorname}&nbsp;{$info_authorname}</p>
</div>
<div class="pageoverflow">
    <p class="pagetext">{$prompt_notes} {$info_notes}</p>
    <p class="pageinput">{$input_notes}</p>
</div>
{/if}

{if $step >= 10}
{foreach from=$package_info key=label item=field}
{assign var="tr" value=prompt_$label}
<div class="pageoverflow">
    <p class="pagetext">{$mod->Lang($tr)}</p>
    <p class="pageinput">{$field}</p>
</div>
{/foreach}
{/if}

<div class="pageoverflow">
    <p class="pagetext">&nbsp;</p>
    <p class="pageinput">{$nextstep}{$submit}</p>
</div>

<div class="info">
    <table class="pagetable">
        <tr><th>{$mod->Lang('list')}</th></tr>
    </table>
    {if $step >= 2}
    <p class="pagetext">{$mod->Lang('templates')}</p>
    <div class="pageinput">
        {if $found_templates|@sizeof > 0}
        <ul>
            {foreach from=$found_templates item=template}
            <li>{$template.name} ({$mod->Lang('id')}: {$template.id})</li>
            {/foreach}
        </ul>
        {else}
        <p>{$mod->Lang('noselection')}</p>
        {/if}
    </div>
    {/if}

    {if $step >= 3}
    <p class="pagetext">{$mod->Lang('stylesheets')}</p>
    <div class="pageinput">
        {if $found_styles|@sizeof > 0}
        <ul>
            {foreach from=$found_styles item=style}
            <li>{$style.name} ({$mod->Lang('id')}: {$style.id})</li>
            {/foreach}
        </ul>
        {else}
        <p>{$mod->Lang('noselection')}</p>
        {/if}
    </div>
    {/if}

    {if $step >= 4}
    <p class="pagetext">{$mod->Lang('pages')}</p>
    <div class="pageinput">
        {if $found_pages|@sizeof > 0}
        <ul>
            {foreach from=$found_pages item=page}
            <li>{$page.name} ({$mod->Lang('id')}: {$page.id})</li>
            {/foreach}
        </ul>
        {else}
        <p>{$mod->Lang('noselection')}</p>
        {/if}
    </div>
    {/if}

    {if $step >= 5}
    <p class="pagetext">{$mod->Lang('moduletemplates')}</p>
    <div class="pageinput">
        {if $found_modules|@sizeof > 0}
        <ul>
            {foreach from=$found_modules item=module}
            <li>{$module.name} ({$mod->Lang('module')}: {$module.id})</li>
            {/foreach}
        </ul>
        {else}
        <p>{$mod->Lang('noselection')}</p>
        {/if}
    </div>
    {/if}

    {if $step >= 6}
    <p class="pagetext">{$mod->Lang('gbls')}</p>
    <div class="pageinput">
        {if $found_blocks|@sizeof > 0}
        <ul>
            {foreach from=$found_blocks item=block}
            <li>{$block.name} (({$mod->Lang('id')}: {$block.id})</li>
            {/foreach}
        </ul>
        {else}
        <p>{$mod->Lang('noselection')}</p>
        {/if}
    </div>
    {/if}

    {if $step >= 7}
    <p class="pagetext">{$mod->Lang('udts')}</p>
    <div class="pageinput">
        {if $found_udts|@sizeof > 0}
        <ul>
            {foreach from=$found_udts item=udt}
            <li>{$udt.name} (({$mod->Lang('id')}: {$udt.id})</li>
            {/foreach}
        </ul>
        {else}
        <p>{$mod->Lang('noselection')}</p>
        {/if}
    </div>
    {/if}

    {if $step >= 8}
    <p class="pagetext">{$mod->Lang('files')}</p>
    <div class="pageinput">
        {if $found_files|@sizeof > 0}
        <ul>
            {foreach from=$found_files item=file}
            <li>{$file.name}</li>
            {/foreach}
        </ul>
        {else}
        <p>{$mod->Lang('noselection')}</p>
        {/if}
    </div>
    {/if}

    {if $step >= 9}
    <p class="pagetext">{$mod->Lang('modulesettings')}</p>
    <div class="pageinput">
        {if $found_settings|@sizeof > 0}
        <ul>
            {foreach from=$found_settings item=module}
            <li>{$module.name}</li>
            {/foreach}
        </ul>
        {else}
        <p>{$mod->Lang('noselection')}</p>
        {/if}
    </div>
    {/if}

</div>

{$endform}

{literal}
<script type="text/javascript">
    //<![CDATA[
    function select_all()
    {
        checkboxes = document.getElementsByTagName("input");
        elem = document.getElementById('selectall');
        state = elem.checked;
        for (i=0; i<checkboxes.length ; i++)
        {
            if (checkboxes[i].type == "checkbox")
            {
                checkboxes[i].checked=state;
            }
        }
    }
    //]]>
</script>
{/literal}