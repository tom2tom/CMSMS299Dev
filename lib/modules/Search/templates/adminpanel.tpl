{tab_header name='statistics' label=$mod->Lang('statistics') active=$tab}
{tab_header name='options' label=$mod->Lang('options') active=$tab}
{tab_start name='statistics'}
{include file='module_file_tpl:Search;admin_statistics_tab.tpl'}
{tab_start name='options'}
{include file='module_file_tpl:Search;options_tab.tpl'}
{tab_end}
