{tab_header name='results' label=_ld($_module,'statistics') active=$tab}
{tab_header name='settings' label=_ld($_module,'options') active=$tab}
{tab_start name='results'}
{include file='module_file_tpl:Search;results_tab.tpl'}
{tab_start name='settings'}
{include file='module_file_tpl:Search;settings_tab.tpl'}
{tab_end}
