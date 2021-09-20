{if !empty($stylesheets)}
{function css_info}
{strip}{if $css->locked()}
{$lock=$css->get_lock()}
{if $css->lock_expired()}<span style='font-weight:bold;color:red;'>{_ld('layout','msg_steal_lock')}</span><br />{/if}
<strong>{_ld('layout','prompt_lockedby')}:</strong> {cms_admin_user uid=$lock.uid}<br />
<strong>{_ld('layout','prompt_lockedsince')}:</strong> {$lock.create_date|cms_date_format:'timed'}<br />
{if $lock.expires < $smarty.now}
<strong>{_ld('layout','prompt_lockexpired')}:</strong> <span style='color:red;'>{$lock.expires|relative_time}</span>
{else}
<strong>{_ld('layout','prompt_lockexpires')}:</strong> {$lock.expires|relative_time}
{/if}
{else}
<strong>{_ld('layout','prompt_name')}:</strong> {$css->get_name()} <em>({$css->get_id()})</em><br />
{$tmp=$css->get_created()}
<strong>{_ld('layout','prompt_created')}:</strong> {$tmp|cms_date_format:'timed'}
{$t2=$css->get_modified()}{if $t2 && ($t2!=$tmp)}<br />
<strong>{_ld('layout','prompt_modified')}:</strong> {$t2|cms_date_format:'timed'}
{/if}
{$tmp=$css->get_description()}{if $tmp}<br />
<strong>{_ld('layout','prompt_description')}:</strong> {$tmp|strip_tags|cms_escape|summarize}{/if}
{/if}{/strip}
{/function}

{if $navpages > 1}
<div class="browsenav postgap">
 <a href="javascript:pagefirst(pagetable)">{_ld('layout','pager_first')}</a>&nbsp;|&nbsp;
{if $navpages > 2}
 <a href="javascript:pageback(pagetable)">{_ld('layout','pager_previous')}</a>&nbsp;&lt;&gt;&nbsp;
 <a href="javascript:pageforw(pagetable)">{_ld('layout','pager_next')}</a>&nbsp;|&nbsp;
{/if}
 <a href="javascript:pagelast(pagetable)">{_ld('layout','pager_last')}</a>&nbsp;
 ({_ld('layout','pageof','<span id="cpage">1</span>',"<span id='tpage'>`$navpages`</span>")})&nbsp;&nbsp;
 <select id="pagerows" name="pagerows">
  {html_options options=$pagelengths selected=$currentlength}
 </select>&nbsp;&nbsp;{_ld('layout','pager_rows')}
</div>
{/if} {* navpages *}

  <form action="stylesheetoperations.php" enctype="multipart/form-data" method="post">
  {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />{/foreach}
  <table id="csslist" class="pagetable" style="width:auto;">
    <thead>
     {strip}<tr>
      {if $manage_stylesheets}
      <th title="{_ld('layout','title_css_id')}">{_ld('layout','prompt_id')}</th>
      {/if}
      <th title="{_ld('layout','title_css_name')}">{_ld('layout','prompt_name')}</th>
      {if $manage_stylesheets}
      <th class="pageicon nosort"></th>{* menu *}
      <th class="pageicon nosort">
       <input type="checkbox" id="css_selall" title="{_ld('layout','title_css_selectall')}" value="1" />
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
      <td><a class="action edit_css tooltip" href="{$url}" data-css-id="{$sid}" data-cms-description="{css_info}" title="{_ld('layout','title_edit_stylesheet')}">{$css->get_name()}</a></td>
      <td>
       {$ul=!$css->locked()}
       {$t=_ld('layout','prompt_locked')}
       <span class="locked" data-css-id="{$sid}" title="{$t}"{if $ul} style="display:none;"{/if}>{admin_icon icon='icons/extra/block.gif' title=$t}</span>
       {$t=_ld('layout','prompt_steal_lock')}
       <a class="steal_lock" href="{$url}&amp;steal=1" data-css-id="{$sid}" title="{$t}" accesskey="e"{if $ul} style="display:none;"{/if}>{admin_icon icon='permissions.gif' title=$t}</a>
       <span class="action" context-menu="Stylesheet{$sid}"{if !$ul} style="display:none;"{/if}>{admin_icon icon='menu.gif' alt='menu' title=_ld('layout','title_menu') class='systemicon'}</span></td>
      <td>
       <input type="checkbox" id="{$css@index}" class="css_select action" name="css_select[]" title="{_ld('layout','prompt_select')}" value="{$sid}" />
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
      {cms_help 0='layout' key='help_bulk_css' title=_ld('layout','prompt_bulk')}
      <label for="bulk_action">{_ld('layout','prompt_with_selected')}:</label>&nbsp;
      <select name="bulk_action" id="bulk_action" class="action">
        <option value="delete" title="{_ld('layout','title_delete')}">{_ld('layout','prompt_delete')}</option>
{*      <option value="export">{_ld('layout','export')}</option>
        <option value="import">{_ld('layout','import')}</option>
*}
      </select>
     <button type="submit" name="bulk_submit" id="bulk_submit" class="adminsubmit icon check action">{_ld('admin','submit')}</button>
    </div>
  </div>
  {/if}{* manage *}
  </form>
{elseif isset($stylesheets)}
  <p class="pagewarn pregap">{_ld('layout','warn_no_stylesheets')}</p>
{else}
  <p class="pageinfo pregap">{_ld('layout','info_no_stylesheets')}</p>
{/if}
