<form id="listusers" action="{$selfurl}" enctype="multipart/form-data" method="post">
  {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}">
{/foreach}
  <div class="pageoptions">
  <a href="{$addurl}{$urlext}" title="{_la('info_adduser')}">{admin_icon icon='newobject.gif' class='systemicon'}&nbsp;{_la('adduser')}</a>
  </div>
  {if !empty($userlist)}
  {if $tblpages > 1}
  <div class="browsenav postgap">
   <span id="tblpagelink">
   <span id="ftpage" class="pagechange">{_ld('layout','pager_first')}</span>&nbsp;|&nbsp;
  {if $tblpages > 2}
   <span id="pspage" class="pagechange">{_ld('layout','pager_previous')}</span>&nbsp;&lt;&gt;&nbsp;
   <span id="ntpage" class="pagechange">{_ld('layout','pager_next')}</span>&nbsp;|&nbsp;
  {/if}
   <span id="ltpage" class="pagechange">{_ld('layout','pager_last')}</span>&nbsp;
   ({_ld('layout','pageof','<span id="cpage2">1</span>',"<span id='tpage2'>{$tblpages}</span>")})&nbsp;&nbsp;
   </span>
   <select id="tblpagerows" name="tblpagerows">
    {html_options options=$pagelengths selected=$currentlength}
   </select>&nbsp;&nbsp;{_ld('layout','pager_rowspp')}{*TODO sometimes show 'pager_rows'*}
  </div>
  {/if}{* tblpages *}
  <table id="userslist" class="pagetable">
  <thead>
  <tr>
    <th class="{literal}{sss:text}{/literal}">{_la('username')}</th>
    <th class="{literal}{sss:intfor}{/literal}" style="text-align:center">{_la('active')}</th>
    {if $become}<th class="pageicon nosort"></th>{/if}{* become user *}
    <th class="pageicon nosort"></th>{* menu *}
    <th class="pageicon nosort"><input type="checkbox" id="sel_all" value="1" title="{_la('selectall')}"></th>
  </tr>
  </thead>
  <tbody>
  {foreach $userlist as $user}
  <tr class="{cycle values='row1,row2'}">
    {strip}
    <td>
    {$can_edit = $user.editable}{if $can_edit}
    <a href="{$editurl}{$urlext}&user_id={$user.id}" title="{_la('edituser')}">{$user.name}</a>
    {else}
    <span title="{_la('info_noedituser')}">{$user.name}</span>
    {/if}
    </td>

    <td class="pagepos" data-sss="{if $user.active}1{else}0{/if}">
    {if $can_edit && $user.id != $my_userid}
    <a href="{$selfurl}{$urlext}&toggleactive={$user.id}" title="{_la('info_user_active2')}" class="toggleactive">
    {if $user.active}{$icontrue2}{else}{$iconfalse2}{/if}
    </a>
    {elseif $user.active}
    {$icontrue}
    {else}
    {$iconfalse}
    {/if}
    </td>

    {if $become}
    <td class="pagepos">
    {if ($user.active && $user.id != 1 && $user.id != $my_userid)}
    <a href="{$selfurl}{$urlext}&switchuser={$user.id}" title="{_la('info_user_switch')}" class="switchuser">{$iconrun}</a>
    {/if}
    </td>
    {/if}

    <td class="pagepos">
      <span class="action" context-menu="User{$user.id}">{$iconmenu}</span></td>
    </td>

    <td>
    {if $can_edit && $user.id != $my_userid}
    <input type="checkbox" name="multiselect[]" class="multiselect" value="{$user.id}" title="{_la('info_selectuser')}">
    {/if}
    </td>
{/strip}
  </tr>
  {/foreach}
  </tbody>
  </table>

  <div class="pageoptions rowbox{if count($userlist) > 10} expand">
  <div class="boxchild">
    <a href="{$addurl}{$urlext}" title="{_la('info_adduser')}">{$iconadd}</a>
    <a href="{$addurl}{$urlext}">{_la('adduser')}</a>
  </div>
  {else}" style="justify-content:flex-end">{/if}
  <div class="boxchild">
  <label for="bulkaction">{_la('selecteditems')}:</label>&nbsp;
  <select name="bulk_action" id="bulkaction">
    {html_options options=$bulkactions}  </select>
  &nbsp;
  <div id="userlist" style="display:none">
    <label for="userlist_sub">{_la('copyfromuser')}:</label>&nbsp;
    <select name="userlist" id="userlist_sub">
      {html_options options=$userlist}    </select>
  </div>
  <button type="submit" id="bulksubmit" name="bulk" class="adminsubmit icon do">{_la('submit')}</button>
  </div>
  </div>{*rowbox*}
{/if}
</form>
{if !empty($usermenus)}
<div id="usermenus">
  {foreach $usermenus as $menu}{$menu}
{/foreach}
</div>
{/if}
