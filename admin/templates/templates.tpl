{if !empty($templates)}
{function tpl_info}
{strip}{if $template->locked()}
  {$lock=$template->get_lock()}
  {if $template->lock_expired()}<strong style="color:red;">{lang_by_realm('layout','msg_steal_lock')}</strong><br />{/if}
  <strong>{lang_by_realm('layout','prompt_lockedby')}:</strong> {cms_admin_user uid=$lock.uid}<br />
  <strong>{lang_by_realm('layout','prompt_lockedsince')}:</strong> {$lock.create_date|cms_date_format|cms_escape}<br />
  {if $lock.expires|timestamp < $smarty.now}
    <strong>{lang_by_realm('layout','prompt_lockexpired')}:</strong> <span style="color:red;">{$lock.expires|relative_time}</span>
  {else}
    <strong>{lang_by_realm('layout','prompt_lockexpires')}:</strong> {$lock.expires|relative_time}
  {/if}
{else}
  <strong>{lang_by_realm('layout','prompt_owner')}:</strong> {cms_admin_user uid=$template->get_owner_id()}<br />
  <strong>{lang_by_realm('layout','prompt_created')}:</strong> {$template->get_created()|cms_date_format|cms_escape}<br />
  <strong>{lang_by_realm('layout','prompt_modified')}:</strong> {$template->get_modified()|relative_time}
  {$tmp=$template->get_description()}
  {if $tmp != ''}<br /><strong>{lang_by_realm('layout','prompt_description')}:</strong> {$tmp|strip_tags|cms_escape|summarize}{/if}
{/if}{/strip}
{/function}

{function type_info}
{strip}{$tpltype=$list_all_types.$type_id}
<strong>{lang_by_realm('layout','prompt_id')}:</strong> {$type_id}<br/>
{$tmp=$tpltype->get_description()}{if $tmp}
 <strong>{lang_by_realm('layout','prompt_description')}:</strong> {$tmp|summarize}
{/if}{strip}
{/function}

<table id="tpllist" class="pagetable" style="width:auto;">
 <thead>
  <tr>
    <th title="{lang_by_realm('layout','title_tpl_id')}">{lang_by_realm('layout','prompt_id')}</th>{strip}
    <th title="{lang_by_realm('layout','title_tpl_name')}">{lang_by_realm('layout','prompt_name')}</th>
    <th title="{lang_by_realm('layout','title_tpl_type')}">{lang_by_realm('layout','prompt_type')}</th>
{*    <th title="{lang_by_realm('layout','title_tpl_filename')}">{lang_by_realm('layout','prompt_filename')}</th> *}
{*    <th title="{lang_by_realm('layout','title_tpl_design')}">{lang_by_realm('layout','prompt_design')}</th> *}
    <th class="pageicon" title="{lang_by_realm('layout','title_tpl_dflt')}">{lang_by_realm('layout','prompt_dflt')}</th>{* dflt *}
{*
    <th class="pageicon nosort"></th>{ * edit * }
    {if $has_add_right}<th class="pageicon"></th>{/if}{ * copy * }
    <th class="pageicon nosort"></th>{ * delete * }
*}
    <th class="pageicon nosort" title="{lang_by_realm('layout','title_menu')}"></th>{* locks/actions-menu *}
    <th class="pageicon nosort"><input type="checkbox" id="tpl_selall" title="{lang_by_realm('layout','title_select_all')}" value="1" /></th>{* checkbox *}{/strip}
  </tr>
 </thead>
 <tbody>
  {foreach $templates as $template}{$tid=$template->get_id()}
  {cycle values="row1,row2" assign='rowclass'}
  <tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
    <td>{$tid}</td>{strip}
    {$url="edittemplate.php`$urlext`&amp;tpl=`$tid`"}
    {* template name column *}
     <td><a href="{$url}" class="action edit_tpl tooltip" data-tpl-id="{$tid}" data-cms-description="{tpl_info}" title="{lang_by_realm('layout','title_edit_template')}">{$template->get_name()}</a></td>
    {* template type column *}
    <td>
   {if $list_types}{$type_id=$template->get_type_id()}
    {if isset($list_types.$type_id)}
     <span class="tooltip" data-cms-description="{type_info}">{$list_types.$type_id}</span>
    {else}
     {lang('none')}
    {/if}
   {else}
     {lang('none')}
   {/if}
    </td>
    {* default column *}
    {if $list_all_types && isset($list_all_types.$type_id)}{$the_type=$list_all_types.$type_id}
    {if $the_type->get_dflt_flag()}
      <td style="text-align:center;">
      {if $template->get_type_dflt()}
       {admin_icon icon='true.gif' title=lang_by_realm('layout','title_dflt_tpl')}
      {else}
       {admin_icon icon='false.gif' title=lang_by_realm('layout','prompt_notdflt_tpl')}
      {/if}
      </td>
    {else}
     <td title="{lang_by_realm('layout','prompt_title_na')}">{lang_by_realm('layout','prompt_na')}</td>
    {/if}
    {else}
     <td></td>
    {/if}
   {* actions column *}
    <td>
    {$ul=!$template->locked()}
    {$t=lang_by_realm('layout','prompt_locked')}
     <span class="locked" data-tpl-id="{$tid}" title="{$t}"{if $ul} style="display:none;"{/if}>{admin_icon icon='icons/extra/block.gif' title=$t}</span>
    {$t=lang_by_realm('layout','prompt_steal_lock')}
     <a href="{$url}&amp;steal=1" class="steal_lock" data-tpl-id="{$tid}" title="{$t}" accesskey="e"{if $ul} style="display:none;"{/if}>{admin_icon icon='permissions.gif' title=$t}</a>
    <span class="action" context-menu="Template{$tid}"{if !$ul} style="display:none;"{/if}>{admin_icon icon='menu.gif' alt='menu' title=lang_by_realm('layout','title_menu') class='systemicon'}</span>
    </td>
    {* checkbox column *}
    <td>
    {if !$template->locked() && ($template->get_owner_id() == get_userid() || $manage_templates) }
    <input type="checkbox" class="tpl_select action" name="tpl_select[]" value="{$tid}" title="{lang_by_realm('layout','title_tpl_bulk')}" />
     {/if}
    </td>{/strip}
  </tr>
  {/foreach}
 </tbody>
</table>
<div id="tplmenus">
 {foreach $tplmenus as $menu}{$menu}{/foreach}
</div>

<div class="pageoptions rowbox" style="justify-content:flex-end">
  <div class="boxchild">
    <form action="{$selfurl}" enctype="multipart/form-data" method="post">
    {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />
    {/foreach}
    {cms_help realm='layout' key2='help_bulk_templates' title=lang_by_realm('layout','prompt_bulk')}
    <label for="bulk_action">{lang_by_realm('layout','prompt_with_selected')}:</label>&nbsp;
    <select name="bulk_action" id="bulk_action" class="action" title="{lang_by_realm('layout','title_tpl_bulkaction')}">
    <option value="delete">{lang_by_realm('layout','prompt_delete')}</option>
{*    <option value="export">{lang_by_realm('layout','export')}</option>
    <option value="import">{lang_by_realm('layout','import')}</option>
*}
    </select>
    <button type="submit" name="bulk_submit" id="bulk_submit" class="adminsubmit icon check action">{lang('submit')}</button>
    </form>
  </div>
</div>
{elseif isset($templates)}
<p class="pagewarn pregap">{lang_by_realm('layout','warn_no_templates_available')}</p>
{else}
<p class="pageinfo pregap">{lang_by_realm('layout','info_no_templates')}</p>
{/if}
