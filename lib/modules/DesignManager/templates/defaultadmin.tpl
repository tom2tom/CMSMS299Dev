<script type="text/javascript">
{literal}//<![CDATA[
$(document).ready(function() {
  $('#clearlocks,#cssclearlocks').on('click', function(ev) {
    ev.preventDefault();
    cms_confirm_linkclick(this,'{/literal}{$mod->Lang("confirm_clearlocks")|escape:"javascript"}','{$mod->Lang("yes")}{literal}');
    return false;
  });
});
{/literal}//]]>
</script>

{* always display templates tab *}
{tab_header name='templates' label=$mod->Lang('prompt_templates')}

{if $manage_stylesheets}
  {tab_header name='stylesheets' label=$mod->Lang('prompt_stylesheets')}
{/if}

{if $manage_designs}
  {tab_header name='designs' label=$mod->Lang('prompt_designs')}
{/if}

{if $manage_templates}
  {tab_header name='types' label=$mod->Lang('prompt_templatetypes')}
  {tab_header name='categories' label=$mod->Lang('prompt_categories')}
{/if}

{* templates tab displayed at all times*}
{tab_start name='templates'}
{include file='module_file_tpl:DesignManager;admin_defaultadmin_templates.tpl' scope='root'}

{if $manage_stylesheets}
  {tab_start name='stylesheets'}
  {include file='module_file_tpl:DesignManager;admin_defaultadmin_stylesheets.tpl' scope='root'}
{/if}

{if $manage_designs}
  {tab_start name='designs'}
  {include file='module_file_tpl:DesignManager;admin_defaultadmin_designs.tpl' scope='root'}
{/if}

{if $manage_templates}
  {tab_start name='types'}
  {include file='module_file_tpl:DesignManager;admin_defaultadmin_types.tpl' scope='root'}
  {tab_start name='categories'}
  {include file='module_file_tpl:DesignManager;admin_defaultadmin_categories.tpl' scope='root'}
{/if}

{tab_end}
