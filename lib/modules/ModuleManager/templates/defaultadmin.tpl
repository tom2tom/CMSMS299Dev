{if $pmod}
{tab_header name='installed' label=$mod->Lang('installed') active=$tab}
{if $connected}
{tab_header name='newversions' label=$newtext active=$tab}
{tab_header name='search' label=$mod->Lang('search') active=$tab}
{tab_header name='modules' label=$mod->Lang('availmodules') active=$tab}
{/if}
{/if}
{if $pset}
{tab_header name='prefs' label=$mod->Lang('prompt_settings') active=$tab}
{/if}

{if $pmod}
{tab_start name='installed'}
{include file='module_file_tpl:ModuleManager;installed_tab.tpl'}
{if $connected}
{tab_start name='newversions'}
{include file='module_file_tpl:ModuleManager;newversions_tab.tpl'}
{tab_start name='search'}
{include file='module_file_tpl:ModuleManager;search_tab.tpl'}
{tab_start name='modules'}
{include file='module_file_tpl:ModuleManager;forge_tab.tpl'}
{/if}
{/if}
{if $pset}
{tab_start name='prefs'}
{include file='module_file_tpl:ModuleManager;prefs_tab.tpl'}
{/if}
{tab_end}
