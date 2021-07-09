{if !empty($stylesheets)}
{function css_info}
{strip}{if $css->locked()}
{$lock=$css->get_lock()}
{if $css->lock_expired()}<span style='font-weight:bold;color:red;'>{lang_by_realm('layout','msg_steal_lock')}</span><br />{/if}
<strong>{lang_by_realm('layout','prompt_lockedby')}:</strong> {cms_admin_user uid=$lock.uid}<br />
<strong>{lang_by_realm('layout','prompt_lockedsince')}:</strong> {$lock.create_date|cms_date_format|cms_escape}<br />
{if $lock.expires < $smarty.now}
<strong>{lang_by_realm('layout','prompt_lockexpired')}:</strong> <span style='color:red;'>{$lock.expires|relative_time}</span>
{else}
<strong>{lang_by_realm('layout','prompt_lockexpires')}:</strong> {$lock.expires|relative_time}
{/if}
{else}
<strong>{lang_by_realm('layout','prompt_name')}:</strong> {$css->get_name()} <em>({$css->get_id()})</em><br />
{$tmp=$css->get_created()}
<strong>{lang_by_realm('layout','prompt_created')}:</strong> {$tmp|cms_date_format|cms_escape}
{$t2=$css->get_modified()}{if $t2 && ($t2!=$tmp)}<br />
<strong>{lang_by_realm('layout','prompt_modified')}:</strong> {$t2|cms_date_format|cms_escape}
{/if}
{$tmp=$css->get_description()}{if $tmp}<br />
<strong>{lang_by_realm('layout','prompt_description')}:</strong> {$tmp|strip_tags|cms_escape|summarize}{/if}
{/if}{/strip}
{/function}

{if $navpages > 1}
<div class="browsenav postgap">
 <a href="javascript:pagefirst(pagetable)">{lang_by_realm('layout','pager_first')}</a>&nbsp;|&nbsp;
{if $navpages > 2}
 <a href="javascript:pageback(pagetable)">{lang_by_realm('layout','pager_previous')}</a>&nbsp;&lt;&gt;&nbsp;
 <a href="javascript:pageforw(pagetable)">{lang_by_realm('layout','pager_next')}</a>&nbsp;|&nbsp;
{/if}
 <a href="javascript:pagelast(pagetable)">{lang_by_realm('layout','pager_last')}</a>&nbsp;
 ({lang_by_realm('layout','pageof','<span id="cpage">1</span>',"<span id='tpage'>`$navpages`</span>")})&nbsp;&nbsp;
 <select id="pagerows" name="pagerows">
  {html_options options=$pagelengths selected=$currentlength}
 </select>&nbsp;&nbsp;{lang_by_realm('layout','pager_rows')}
</div>
{/if} {* navpages *}

  <form action="stylesheetoperations.php" enctype="multipart/form-data" method="post">
  {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />{/foreach}
  <table id="csslist" class="pagetable" style="width:auto;">
    <thead>
     {strip}<tr>
      {if $manage_stylesheets}
      <th title="{lang_by_realm('layout','title_css_id')}">{lang_by_realm('layout','prompt_id')}</th>
      {/if}
      <th title="{lang_by_realm('layout','title_css_name')}">{lang_by_realm('layout','prompt_name')}</th>
      {if $manage_stylesheets}
      <th class="pageicon nosort"></th>{* menu *}
      <th class="pageicon nosort">
       <input type="checkbox" id="css_selall" title="{lang_by_realm('layout','title_css_selectall')}" value="1" />
      </th>
      {/if}
      </tr>{/strip}
    </thead>
    <tbody>
      {foreach $stylesheets as $css}
      {cycle values="row1,row2" assign='rowclass'}
      <tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
      {if $manage_stylesheets}{$sid=$css->get_id()}
      <td>{$sid}</td>{strip}
      {$url="editstylesheet.php`$urlext`&amp;css=`$sid`"}
      <td><a class="action edit_css tooltip" href="{$url}" data-css-id="{$sid}" data-cms-description="{css_info}" title="{lang_by_realm('layout','title_edit_stylesheet')}">{$css->get_name()}</a></td>
      <td>
       {$ul=!$css->locked()}
       {$t=lang_by_realm('layout','prompt_locked')}
       <span class="locked" data-css-id="{$sid}" title="{$t}"{if $ul} style="display:none;"{/if}>{admin_icon icon='icons/extra/block.gif' title=$t}</span>
       {$t=lang_by_realm('layout','prompt_steal_lock')}
       <a class="steal_lock" href="{$url}&amp;steal=1" data-css-id="{$sid}" title="{$t}" accesskey="e"{if $ul} style="display:none;"{/if}>{admin_icon icon='permissions.gif' title=$t}</a>
       <span class="action" context-menu="Stylesheet{$sid}"{if !$ul} style="display:none;"{/if}>{admin_icon icon='menu.gif' alt='menu' title=lang_by_realm('layout','title_menu') class='systemicon'}</span></td>
      <td>
       <input type="checkbox" id="{$css@index}" class="css_select action" name="css_select[]" title="{lang_by_realm('layout','prompt_select')}" value="{$sid}" />
      </td>
      {else}
      <td><span class="tooltip" data-cms-description="{css_info}">{$css->get_name()}</span></td>
      {/if}{* manage *}{/strip}
      </tr>
      {/foreach}
    </tbody>
  </table>
  {if $manage_stylesheets}
  <div id="cssmenus">
  {foreach $cssmenus as $menu}{$menu}{/foreach}
  </div>
  <div class="pageoptions rowbox" style="justify-content:flex-end;" id="bulkoptions">
    <div class="boxchild">
      {cms_help realm='layout' key2='help_bulk_css' title=lang_by_realm('layout','prompt_bulk')}
      <label for="bulk_action">{lang_by_realm('layout','prompt_with_selected')}:</label>&nbsp;
      <select name="bulk_action" id="bulk_action" class="action">
        <option value="delete" title="{lang_by_realm('layout','title_delete')}">{lang_by_realm('layout','prompt_delete')}</option>
{*      <option value="export">{lang_by_realm('layout','export')}</option>
        <option value="import">{lang_by_realm('layout','import')}</option>
*}
      </select>
     <button type="submit" name="bulk_submit" id="bulk_submit" class="adminsubmit icon check action">{lang('submit')}</button>
    </div>
  </div>
  {/if}{* manage *}
  </form>
{elseif isset($stylesheets)}
  <p class="pagewarn pregap">{lang_by_realm('layout','warn_no_stylesheets')}</p>
{else}
  <p class="pageinfo pregap">{lang_by_realm('layout','info_no_stylesheets')}</p>
{/if}
