{tab_header name='example' label=$mod->Lang('example')}
{tab_header name='settings' label=$mod->Lang('settings')}
{tab_start name='example'}
{include file='module_file_tpl:MicroTiny;admin_example.tpl'}
{tab_start name ='settings'}
{include file='module_file_tpl:MicroTiny;settings.tpl'}
{tab_end}
