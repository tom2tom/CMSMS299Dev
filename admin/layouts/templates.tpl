{if !empty($templates)}
{function tpl_info}
{strip}{if $template->locked()}
  {$lock=$template->get_lock()}
  {if $template->lock_expired()}<span style="font-weight:bold;color:red">{_ld('layout','msg_steal_lock')}</span><br>{/if}
  <strong>{_ld('layout','prompt_lockedby')}:</strong> {cms_admin_user uid=$lock.uid}<br>
  <strong>{_ld('layout','prompt_lockedsince')}:</strong> {$lock.create_date|cms_date_format:'timed'}<br>
  {if $lock.expires < $smarty.now}
    <strong>{_ld('layout','prompt_lockexpired')}:</strong> <span style="color:red">{$lock.expires|relative_time}</span>
  {else}
    <strong>{_ld('layout','prompt_lockexpires')}:</strong> {$lock.expires|relative_time}
  {/if}
{else}
  <strong>{_ld('layout','prompt_owner')}:</strong> {cms_admin_user uid=$template->get_owner_id()}<br>
  {$tmp=$template->get_created()}
  <strong>{_ld('layout','prompt_created')}:</strong> {$tmp|cms_date_format:'timed'}
  {$t2=$template->get_modified()}{if $t2 && ($t2!=$tmp)}<br>
  <strong>{_ld('layout','prompt_modified')}:</strong> {$t2|cms_date_format:'timed'}
  {/if}
  {$tmp=$template->get_description()}{if $tmp}<br>
<strong>{_ld('layout','prompt_description')}:</strong> {$tmp|adjust:'strip_tags'|cms_escape|summarize}{/if}
{/if}{/strip}
{/function}

{function type_info}
{strip}{$tpltype=$list_all_types.$type_id}
<strong>{_ld('layout','prompt_type')}:</strong> {$type_id}<br>
{$tmp=$tpltype->get_description()}{if $tmp}
 <strong>{_ld('layout','prompt_description')}:</strong> {$tmp|summarize}
{/if}{/strip}
{/function}

{if !empty($templates)}
<form action="{$bulkurl}" enctype="multipart/form-data" method="post">
  {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}">
{/foreach}
  <table class="pagetable" id="tpllist">
   <thead>
    <tr>
     <th class="{literal}{sss:numeric}{/literal}" title="{_ld('layout','title_tpl_id')}">{_ld('layout','prompt_id')}</th>{strip}
     <th class="{literal}{sss:text}{/literal}" title="{_ld('layout','title_tpl_name')}">{_ld('layout','prompt_name')}</th>
     <th class="{literal}{sss:text}{/literal}" title="{_ld('layout','title_tpl_type')}">{_ld('layout','prompt_type')}</th>
{*   <th class="{literal}{sss:text}{/literal}" title="{_ld('layout','title_tpl_filename')}">{_ld('layout','prompt_filename')}</th> *}
{*   <th class="{literal}{sss:text}{/literal}" title="{_ld('layout','title_tpl_design')}">{_ld('layout','prompt_design')}</th> *}
     <th class="pageicon {literal}{sss:intfor}{/literal}" title="{_ld('layout','title_tpl_dflt')}">{_ld('layout','prompt_dflt')}</th>{* dflt *}
{*
     <th class="pageicon nosort"></th>{ * edit * }
     {if $has_add_right}<th class="pageicon"></th>{/if}{ * copy * }
     <th class="pageicon nosort"></th>{ * delete * }
*}
     <th class="pageicon nosort" title="{_ld('layout','title_menu')}"></th>{* locks/actions-menu *}
     <th class="pageicon nosort"><input type="checkbox" id="tpl_selall" title="{_ld('layout','title_select_all')}" value="1"></th>{* checkbox *}{/strip}
    </tr>
   </thead>
   <tbody>
  {$icontrue = {admin_icon icon='true.gif' title=_ld('layout','title_dflt_tpl') class='systemicon'}}
  {$iconfalse = {admin_icon icon='false.gif' title=_ld('layout','prompt_notdflt_tpl') class='systemicon'}}
  {$iconmenu = {admin_icon icon='menu.gif' alt='menu' title=_ld('layout','title_menu') class='systemicon'}}
  {foreach $templates as $template}{$tid=$template->get_id()}
  {cycle values="row1,row2" assign='rowclass'}
   <tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
    <td>{$tid}</td>{strip}
    {$url="edittemplate.php{$urlext}&tpl=$tid"}
    {* template name column *}
     <td><a href="{$url}" class="action edit_tpl tooltip" data-tpl-id="{$tid}" data-cms-description="{tpl_info}" title="{_ld('layout','title_edit_template')}">{$template->get_name()}</a></td>
    {* template type column *}
    <td>
   {if $list_types}{$type_id=$template->get_type_id()}
    {if isset($list_types.$type_id)}
     <span class="tooltip" data-cms-description="{type_info}">{$list_types.$type_id}</span>
    {else}
     {_la('none')}
    {/if}
   {else}
     {_la('none')}
   {/if}
    </td>
    {* default column *}
    {if $list_all_types && isset($list_all_types.$type_id)}{$the_type=$list_all_types.$type_id}
    {if $the_type->get_dflt_flag()}{$t=$template->get_type_dflt()}
      <td class="pagepos" data-sss="{if $t}1{else}2{/if}">
      {if $t}{$icontrue}{else}{$iconfalse}{/if}
      </td>
    {else}
      <td class="pagepos" title="{_ld('layout','prompt_title_na')}" data-sss="3">{_ld('layout','prompt_na')}</td>
    {/if}
    {else}
     <td></td>
    {/if}
   {* actions column *}
    <td>
    {$ul=!$template->locked()}
    {$t=_ld('layout','prompt_locked')}
     <span class="locked" data-tpl-id="{$tid}" title="{$t}"{if $ul} style="display:none"{/if}>{admin_icon icon='icons/extra/block.gif' title=$t}</span>
    {$t=_ld('layout','prompt_steal_lock')}
     <a href="{$url}&steal=1" class="steal_lock" data-tpl-id="{$tid}" title="{$t}" accesskey="e"{if $ul} style="display:none"{/if}>{admin_icon icon='permissions.gif' title=$t}</a>
    <span class="action" context-menu="Template{$tid}"{if !$ul} style="display:none"{/if}>{$iconmenu}</span>
    </td>
    {* checkbox column *}
    <td>
    {if $ul && ($manage_templates || $template->get_owner_id() == $curuser)}
    <input type="checkbox" class="tpl_select action" name="tpl_select[]" value="{$tid}" title="{_ld('layout','title_tpl_bulk')}">
    {/if}
    </td>{/strip}
    </tr>
  {/foreach}
   </tbody>
  </table>
 {if $manage_templates}
  <div class="pageoptions rowbox" style="justify-content:flex-end">
    <div class="boxchild">
      {cms_help realm='layout' key='help_bulk_templates' title=_ld('layout','prompt_bulk')}
      <label for="bulkaction">{_ld('layout','prompt_with_selected')}:</label>&nbsp;
      <select name="bulk_action" id="bulkaction" class="action" title="{_ld('layout','title_tpl_bulkaction')}">
      <option value="delete">{_ld('layout','prompt_delete')}</option>
{*    <option value="export">{_ld('layout','export')}</option>
      <option value="import">{_ld('layout','import')}</option>
*}
      </select>
      <button type="submit" name="bulk_submit" id="bulk_submit" class="adminsubmit icon check action">{_la('submit')}</button>
    </div>
  </div>
 {/if}
</form>
{/if}
{if $manage_templates}
<div id="tplmenus">
  {foreach $tplmenus as $menu}{$menu}
{/foreach}
</div>
{/if}
{elseif isset($templates)}
<p class="pagewarn pregap">{_ld('layout','warn_no_templates_available')}</p>
{else}
<p class="pageinfo pregap">{_ld('layout','info_no_templates')}</p>
{/if}
