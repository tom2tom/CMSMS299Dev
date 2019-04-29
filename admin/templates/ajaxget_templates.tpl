<form action="{$selfurl}" method="post">
{foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />{/foreach}
{strip}
<div class="row">
  <div class="pageoptions options-menu half">
    {if $has_add_right}
      <a id="addtemplate" accesskey="a" title="{lang_by_realm('layout','create_template')}">{admin_icon icon='newobject.gif' alt=lang_by_realm('layout','create_template')}&nbsp;{lang_by_realm('layout','create_template')}</a>&nbsp;&nbsp;
    {/if}
    {if isset($templates)}
    <a id="edittplfilter" accesskey="f" title="{lang_by_realm('layout','title_edittplfilter')}">{admin_icon icon='icons/extra/filter.gif' alt=lang_by_realm('layout','title_edittplfilter')}&nbsp;{lang_by_realm('layout','filter')}</a>&nbsp;&nbsp;
    {if $have_locks}
    <a id="clearlocks" accesskey="l" title="{lang_by_realm('layout','title_clearlocks')}" href="clearlocks.php{$urlext}&type=template">{admin_icon icon='run.gif' alt=''}&nbsp;{lang_by_realm('layout','prompt_clearlocks')}</a>&nbsp;&nbsp;
    {/if}
    {if !empty($tpl_filter[0])}
    <span style="color: green;" title="{lang_by_realm('layout','title_filterapplied')}">{lang_by_realm('layout','filterapplied')}</span>
    {/if}
    {/if}
  </div>
{*
  {if isset($tpl_nav) && $tpl_nav.numpages > 1}
  <div class="pageoptions" style="text-align: right;">
    <label for="tpl_page">{lang_by_realm('layout','prompt_page')}:</label>
    &nbsp;
    <select id="tpl_page" name="tpl_page">
      {cms_pageoptions numpages=$tpl_nav.numpages curpage=$tpl_nav.curpage}
    </select>&nbsp;
    <button type="submit" name="go" class="adminsubmit icon go">{lang('go')}</button>
  </div>
  {/if}
*}
</div>

{if !empty($templates)}
{if !empty($navpages)}
<div class="browsenav postgap">
	<a href="javascript:pagefirst()">{lang_by_realm('layout','pager_first')}</a>&nbsp;|&nbsp;
{if $navpages > 2}
	<a href="javascript:pageback()">{lang_by_realm('layout','pager_previous')}</a>&nbsp;&lt;&gt;&nbsp;
	<a href="javascript:pageforw()">{lang_by_realm('layout','pager_next')}</a>&nbsp;|&nbsp;
{/if}
	{$s1='<span id="cpage">1</span>'}{$s2="<span id='tpage'>`$navpages`</span>"}
	<a href="javascript:pagelast()">{lang_by_realm('layout','pager_last')}</a>&nbsp;
	({lang_by_realm('layout','pageof',$s1,$s2)})&nbsp;&nbsp;
	<select id="pagerows" name="pagerows">
	{html_options options=$pagelengths selected=$currentlength}
	</select>&nbsp;&nbsp;{lang_by_realm('layout','pager_rows')}
</div>
{/if} {* navpages *}
<table id="tpllist" class="pagetable" style="width:auto;">
  <thead>
    <tr>
      <th title="{lang_by_realm('layout','title_tpl_id')}">{lang_by_realm('layout','prompt_id')}</th>
      <th class="pageicon nosort"></th>
      <th title="{lang_by_realm('layout','title_tpl_name')}">{lang_by_realm('layout','prompt_name')}</th>
      <th title="{lang_by_realm('layout','title_tpl_type')}">{lang_by_realm('layout','prompt_type')}</th>
{*      <th title="{lang_by_realm('layout','title_tpl_filename')}">{lang_by_realm('layout','prompt_filename')}</th> *}
{*      <th title="{lang_by_realm('layout','title_tpl_design')}">{lang_by_realm('layout','prompt_design')}</th> *}
      <th class="pageicon" title="{lang_by_realm('layout','title_tpl_dflt')}">{lang_by_realm('layout','prompt_dflt')}</th>{* dflt *}
{*
      <th class="pageicon nosort"></th>{ * edit * }
      {if $has_add_right}<th class="pageicon"></th>{/if}{ * copy * }
      <th class="pageicon nosort"></th>{ * delete * }
*}
      <th class="pageicon nosort" title="{lang_by_realm('layout','title_menu')}"></th>{* actions-menu *}
      <th class="pageicon nosort"><input type="checkbox" value="1" id="tpl_selall" title="{lang_by_realm('layout','title_select_all')}" /></th>{* checkbox *}
    </tr>
  </thead>
  <tbody>
    {foreach $templates as $template}{strip}
    {$tid=$template->get_id()}
    {include file='tpltooltip.tpl' assign=tpl_tooltip}
    <tr class="{cycle values='row1,row2'}">
       <td>{$tid}</td>
     {* lock-icon and template name columns *}
     {if !$template->locked()}{$edit_url="edittemplate.php`$urlext`&tpl=`$tid`"}
       <td></td>
       <td><a href="{$edit_url}" data-tpl-id="{$template->get_type_id()}" class="edit_tpl tooltip" title="{lang_by_realm('layout','title_edit_template')}" data-cms-description='{$tpl_tooltip}'>{$template->get_name()}</a></td>
     {else}
       <td>{admin_icon icon='warning.gif' title=lang_by_realm('layout','title_locked')}</td>
       <td><span class="tooltip" data-cms-description='{$tpl_tooltip}'>{$template->get_name()}</span></td>
     {/if}

      {* template type column *}
      <td>
     {if $list_types}{$type_id=$template->get_type_id()}
      {if isset($list_types.$type_id)}
       {include file='tpltype_tooltip.tpl' assign='tpltype_tooltip'}
       <span class="tooltip" data-cms-description='{$tpltype_tooltip}'>{$list_types.$type_id}</span>
      {else}
       {lang('none')}
      {/if}
     {else}
       {lang('none')}
     {/if}
      </td>

      {* filename column *}
{*      <td>
        {if $template->get_content_file()} {basename($template->get_content_filename())} {/if}
      </td>
*}
      {* design column *}
{*      {$t1=$template->get_designs()}
      <td>
        {if count($t1) == 1}
        {$t1=$t1[0]}
        {$hn=$design_names.$t1}
        {if $manage_designs}
        {$edit_design_url="editdesign.php`$urlext`&design=`$t1`"}
        <a href="{$edit_design_url}" title="{lang_by_realm('layout','edit_design')}">{$hn}</a> {else} {$hn} {/if} {elseif count($t1) == 0}
        <span title="{lang_by_realm('layout','help_template_no_designs')}">{lang_by_realm('layout','prompt_none')}</span> {else}
        <span title="{lang_by_realm('layout','help_template_multiple_designs')}">{lang_by_realm('layout','prompt_multiple')} ({count($t1)})</span> {/if}
      </td>
*}
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
{*
      { * edit/copy/steal icons * }
      {if !$lock_timeout || !$template->locked()}
      <td><a href="{$edit_url}" data-tpl-id="{$tid}" class="edit_tpl" title="{lang_by_realm('layout','edit_template')}">{admin_icon icon='edit.gif' title=lang_by_realm('layout','prompt_edit')}</a></td>
      {if $has_add_right}
        <td><a href="copytemplate.php{$urlext}&tpl={$tid}" title="{lang_by_realm('layout','title_copy_template')}">{admin_icon icon='copy.gif' title=lang_by_realm('layout','title_copy_template')}</a></td>
      {/if}
      {else}
      <td>
        {$lock=$template->get_lock()}
        {if $lock.expires < $smarty.now}
          <a href="{$edit_url}" data-tpl-id="{$tid}" accesskey="e" class="steal_tpl_lock">{admin_icon icon='permissions.gif' class='edit_tpl steal_tpl_lock' title=lang_by_realm('layout','prompt_steal_lock')}</a>
        {/if}
      </td>
      {if $has_add_right}<td></td>{/if}
      {/if}

      { * delete column * }
      <td>
        {if !$template->get_type_dflt() && !$template->locked()}
        {if $template->get_owner_id() == get_userid() || $manage_templates}
        <a href="deletetemplate.php{$urlext}&tpl={$tid}" title="{lang_by_realm('layout','delete_template')}">{admin_icon icon='delete.gif' title=lang_by_realm('layout','delete_template')}</a>
        {/if}
        {/if}
      </td>
*}
      {* actions column *}
      <td><span context-menu="Template{$tid}" style="cursor:pointer;">{admin_icon icon='menu.gif' alt='menu' title=lang_by_realm('layout','title_menu') class='systemicon'}</span></td>
      {* checkbox column *}
      <td>
        {if !$template->locked() && ($template->get_owner_id() == get_userid() || $manage_templates) }
        <input type="checkbox" class="tpl_select" name="tpl_select[]" value="{$tid}" title="{lang_by_realm('layout','title_tpl_bulk')}" />
       {/if}
      </td>

    </tr>
    {/strip}{/foreach}
  </tbody>
</table>
<div id="menus2">
 {foreach $menus2 as $menu}{$menu}{/foreach}
</div>
  <div class="pageoptions rowbox" style="justify-content:flex-end">
    <div class="boxchild">
      {cms_help realm='layout' key2='help_bulk_templates' title=lang_by_realm('layout','prompt_bulk')}
      <label for="tpl_bulk_action">{lang_by_realm('layout','prompt_with_selected')}:</label>&nbsp;
      <select name="bulk_action" id="tpl_bulk_action" class="tpl_bulk_action" title="{lang_by_realm('layout','title_tpl_bulkaction')}">
        <option value="delete">{lang_by_realm('layout','prompt_delete')}</option>
{*      <option value="export">{lang_by_realm('layout','export')}</option>
        <option value="import">{lang_by_realm('layout','import')}</option>
*}
      </select>
      <button type="submit" name="submit_bulk" id="tpl_bulk_submit" class="tpl_bulk_action adminsubmit icon check">{lang('submit')}</button>
    </div>
  </div>
{elseif isset($templates)}
  {page_warning msg=lang_by_realm('layout','warn_no_templates_available')}
{else}
  {page_message msg=lang_by_realm('layout','info_no_templates')}
{/if}
{/strip}
</form>
