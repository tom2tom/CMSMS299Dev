{if !empty($stylesheets)}
  {if $have_css_locks && $manage_stylesheets}
  <div class="row">
    <div class="pageoptions options-menu half">
      <a id="cssclearlocks" accesskey="l" title="{lang_by_realm('layout','title_clearlocks')}" href="clearlocks.php{$urlext}&type=stylesheet">{admin_icon icon='run.gif' alt=''}&nbsp;{lang_by_realm('layout','prompt_clearlocks')}</a>&nbsp;&nbsp;
    </div>
  </div>
  {/if}
  <form action="stylesheetoperations.php" method="post">
  {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />{/foreach}
  <table class="pagetable" style="width:auto;">
    <thead>
     {strip}<tr>
      {if $manage_stylesheets}
      <th title="{lang_by_realm('layout','title_css_id')}">{lang_by_realm('layout','prompt_id')}</th>
{* for locking  <th class="pageicon"></th> *}
      {/if}
      <th title="{lang_by_realm('layout','title_css_name')}">{lang_by_realm('layout','prompt_name')}</th>
{* sub-menu items    {if $manage_stylesheets}
      <th class="pageicon"></th>{ * edit * }
      <th class="pageicon"></th>{ * copy * }
      <th class="pageicon"></th>{ * delete * }
      {/if}
*}
      {if $manage_stylesheets}
      <th class="pageicon"></th>{* menu *}
      <th class="pageicon">
       <input type="checkbox" id="css_selall" title="{lang_by_realm('layout','title_css_selectall')}" value="1" />
      </th>{* multiple *}
      {/if}
      </tr>{/strip}
    </thead>
    <tbody>
      {strip}
      {foreach $stylesheets as $css}
        {$sid=$css->get_id()}
        {include file='csstooltip.tpl' assign='css_tooltip'}
        {if $manage_stylesheets}
        {$edit_css="editstylesheet.php`$urlext`&css=`$sid`"}
        {/if}
{* sub-menu items
        {$copy_css="copystylesheet.php`$urlext`&css=`$sid`"}
        {$delete_css="delete_stylesheet`$urlext`&css=`$sid`"}
*}
    <tr class="{cycle values='row1,row2'}">
    {if !$css->locked()}
      {if $manage_stylesheets}
      <td>{$sid}</td>
{* for locking <td></td> *}
      <td><a href="{$edit_css}" data-css-id="{$sid}" class="edit_css tooltip" title="{lang_by_realm('layout','title_edit_stylesheet')}" data-cms-description='{$css_tooltip}'>{$css->get_name()}</a></td>
      {else}
      <td><span class="tooltip" data-cms-description='{$css_tooltip}'>{$css->get_name()}</span></td>
      {/if}
    {else}
      {if $manage_stylesheets}
      <td>{$sid}</td>
      <td>{admin_icon icon='warning.gif' title=lang_by_realm('layout','title_locked')}</td>
      {/if}
      <td><span class="tooltip" data-cms-description='{$css_tooltip}'>{$css->get_name()}</span></td>
    {/if}
    {if $manage_stylesheets}
    <td><span context-menu="Stylesheet{$sid}" style="cursor:pointer;">{admin_icon icon='menu.gif' alt='menu' title=lang_by_realm('layout','title_menu') class='systemicon'}</span></td>
    {if !$css->locked()}
{* sub-menu items
      <td><a href="{$edit_css}" data-css-id="{$sid}" class="edit_css" title="{lang_by_realm('layout','title_edit_stylesheet')}">{admin_icon icon='edit.gif' title=lang_by_realm('layout','title_edit_stylesheet')}</a></td>
      <td><a href="{$copy_css}" title="{lang_by_realm('layout','title_copy_stylesheet')}">{admin_icon icon='copy.gif' title=lang_by_realm('layout','title_copy_stylesheet')}</a></td>
      <td><a href="{$delete_css}" title="{lang_by_realm('layout','title_delete_stylesheet')}">{admin_icon icon='delete.gif' title=lang_by_realm('layout','title_delete_stylesheet')}</a></td>
*}
      <td>
        <input type="checkbox" id="{$css@index}" class="css_select" name="css_select[]" title="{lang_by_realm('layout','prompt_select')}" value="{$sid}" />
      </td>
    {else}
{*
      <td>
        {$lock=$css->get_lock()}
        {if $lock.expires < $smarty.now}
          <a href="{$edit_css}" data-css-id="{$sid}" accesskey="e" class="steal_css_lock">{admin_icon icon='permissions.gif' class='edit_css steal_css_lock' title=lang_by_realm('layout','prompt_steal_lock')}</a>
        {/if}
      </td>
      <td></td>
      <td></td>
*}
      <td></td>
    {/if}{* locked *}
    {/if}{* manage *}
      </tr>
      {/foreach}
{/strip}
    </tbody>
  </table>
  {if $manage_stylesheets}
  <div id="menus1">
  {foreach $menus1 as $menu}{$menu}{/foreach}
  </div>
  <div class="pageoptions rowbox" style="justify-content:flex-end;" id="bulkoptions">
    <div class="boxchild">
      {cms_help realm='layout' key2='help_css_bulk' title=lang_by_realm('layout','prompt_bulk')}
      <label for="css_bulk_action">{lang_by_realm('layout','prompt_with_selected')}:</label>&nbsp;
      <select name="css_bulk_action" id="css_bulk_action" class="cssx_bulk_action">
        <option value="delete" title="{lang_by_realm('layout','title_delete')}">{lang_by_realm('layout','prompt_delete')}</option>
{*      <option value="export">{lang_by_realm('layout','export')}</option>
        <option value="import">{lang_by_realm('layout','import')}</option>
*}
      </select>
     <button type="submit" name="submit_bulk_css" id="css_bulk_submit" class="adminsubmit icon check">{lang('submit')}</button>
    </div>
  </div>
  {/if}{* manage *}
  </form>
{elseif isset($stylesheets)}
  <p class="pagewarn pregap">{lang_by_realm('layout','warn_no_stylesheets')}</p>
{else}
  <p class="pageinfo pregap">{lang_by_realm('layout','info_no_stylesheets')}</p>
{/if}
