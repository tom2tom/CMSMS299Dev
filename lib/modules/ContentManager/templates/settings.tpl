{tab_header name='general' label=$mod->Lang('prompt_general')}
{tab_header name='listsettings' label=$mod->Lang('prompt_listsettings')}
{tab_header name= 'pagedefaults' label=$mod->Lang('prompt_pagedefaults')}
{tab_start name='general'}
{include file='module_file_tpl:ContentManager;general_tab.tpl'}
{tab_start name='listsettings'}
{include file='module_file_tpl:ContentManager;listsettings_tab.tpl'}
{tab_start name='pagedefaults'}
{include file='module_file_tpl:ContentManager;pagedefaults_tab.tpl'}
{tab_end}
