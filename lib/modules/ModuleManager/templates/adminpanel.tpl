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
{include file='module_file_tpl:ModuleManager;admin_installed.tpl'}
{if $connected}
{tab_start name='newversions'}
{include file='module_file_tpl:ModuleManager;newversionstab.tpl'}
{tab_start name='search'}
{include file='module_file_tpl:ModuleManager;admin_search_tab.tpl'}
{tab_start name='modules'}
{include file='module_file_tpl:ModuleManager;forge_modules.tpl'}
{/if}
{/if}
{if $pset}
{tab_start name='prefs'}
{include file='module_file_tpl:ModuleManager;adminprefs.tpl'}
{/if}
{tab_end}
