<p class="pageinfo">{$mod->Lang('info_designs')}</p>
{if $pmod}
<div class="row">
  <div class="pageoptions options-menu half">
    <a accesskey="a" href="{cms_action_url action='open_design'}" title="{$mod->Lang('create_design')}">{admin_icon icon='newobject.gif'} {$mod->Lang('create_design')}</a>&nbsp;&nbsp;
    <a accesskey="a" href="{cms_action_url action='import_design'}" title="{$mod->Lang('title_import_design')}">{admin_icon icon='import.gif'} {$mod->Lang('import_design')}</a>
  </div>
</div>
{/if}

{if isset($list_designs)}
<table class="pagetable" style="width:auto;">
  <thead>
    <tr>
{*      <th>{$mod->Lang('prompt_id')}</th> *}
      <th>{$mod->Lang('prompt_name')}</th>
{*      <th class="pageicon"><span title="{$mod->Lang('title_designs_default')}">{lang('default')}</span></th> *}
      <th class="pageicon"></th>
    </tr>
  </thead>
  <tbody>
  {foreach $list_designs as $design}{$rowclass={cycle values="row1,row2"}}
    <tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
      {$d=$design->get_id()} {$edit_url={cms_action_url action=open_design design=$d}}
{*      <td><a href="{$edit_url}" title="{$mod->Lang('title_edit_design')}">{$d}</a></td> *}
      <td>
      {if $pmod}
       <a href="{$edit_url}" title="{$mod->Lang('title_edit_design')}">{$design->get_name()}</a>
      {else}
       {$design->get_name()}
      {/if}
      </td>
{*      <td style="text-align:center;">
       {if $design->get_default()}
        {admin_icon icon='true.gif' title=$mod->Lang('prompt_dflt')}
       {else}
        <a href="{cms_action_url design_setdflt=$d}">{admin_icon icon='false.gif' title=$mod->Lang('prompt_setdflt_design')}</a>
       {/if}
      </td>
*}
      <td>
      {if $pmod}
      <a href="{$edit_url}" title="{$mod->Lang('title_edit_design')}">{admin_icon icon='edit.gif'}</a>
      <a href="{cms_action_url action=export_design design=$d}" title="{$mod->Lang('export_design')}">{admin_icon icon='export.gif'}</a>
      <a href="{cms_action_url action=delete_design design=$d}" title="{$mod->Lang('delete_design')}">{admin_icon icon='delete.gif'}</a>
      {else}
      <a href="{$edit_url}" title="{$mod->Lang('view_design')}">{admin_icon icon='view.gif'}</a>
      {/if}
      </td>
    </tr>
  {/foreach}
  </tbody>
</table>
{else}
  <p class="pageinfo">{$mod->Lang('no_design')}</p>
{/if}
