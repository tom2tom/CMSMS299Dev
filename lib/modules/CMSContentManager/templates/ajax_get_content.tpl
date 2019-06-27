{if isset($content_list)}
 {function do_content_row}
  {foreach $columns as $column => $flag}
  {if !$flag}{continue}{/if}
  <td class="{$column}">
    {if $column == 'expand'}
      {if $row.expand == 'open'}{$t=$mod->Lang('prompt_page_collapse')}
       <a href="{cms_action_url action='defaultadmin' collapse=$row.id}" class="page_collapse" accesskey="C" title="{$t}">
        {admin_icon icon='contract.gif' class='systemicon hier_contract' title=$t}
       </a>
      {elseif $row.expand == 'closed'}{$t=$mod->Lang('prompt_page_expand')}
       <a href="{cms_action_url action='defaultadmin' expand=$row.id}" class="page_expand" accesskey="c" title="{$t}">
        {admin_icon icon='expand.gif' class='systemicon hier_expand' title=$t}
       </a>
      {/if}
    {elseif $column == 'icon1'}
      {if isset($row.lock)} {admin_icon icon='warning.gif' class='systemicon' title=$mod->Lang('title_locked')} {/if}
    {elseif $column == 'hier'}
      {$row.hier}
    {elseif $column == 'page'}
      {if $row.can_edit}
      {if $indent}{repeat string='-&nbsp;&nbsp;' times=$row.depth-2}{/if}
      {* the tooltip *}{capture assign='tooltip_pageinfo'}{strip}
      <strong>{$mod->Lang('prompt_content_id')}:</strong> {$row.id}<br />
      <strong>{$mod->Lang('prompt_title')}:</strong> {$row.title|escape}<br />
      <strong>{$mod->Lang('prompt_name')}:</strong> {$row.menutext|escape}<br />
      {if isset($row.alias)}<strong>{$mod->Lang('prompt_alias')}:</strong> {$row.alias}<br />{/if}
      {if $row.url}
       <strong>{$mod->Lang('colhdr_url')}:</strong>
       {if $prettyurls_ok}
         {$row.url}
       {else}
         <span class="red">{$row.url}</span>
       {/if}
      {/if}
      <strong>{$mod->Lang('prompt_owner')}:</strong> {$row.owner}<br />
      <strong>{$mod->Lang('prompt_created')}:</strong> {$row.created|cms_date_format}<br />
      <strong>{$mod->Lang('prompt_lastmodified')}:</strong> {$row.lastmodified|cms_date_format}<br />
      {if isset($row.lastmodifiedby)}<strong>{$mod->Lang('prompt_lastmodifiedby')}:</strong> {$row.lastmodifiedby}<br />{/if}
      <strong>{$mod->Lang('prompt_cachable')}:</strong> {if $row.cachable}{lang('yes')}{else}{lang('no')}{/if}<br />
      <strong>{$mod->Lang('prompt_showinmenu')}:</strong> {if $row.showinmenu}{lang('yes')}{else}{lang('no')}{/if}<br />
      <strong>{$mod->Lang('wantschildren')}:</strong> {if $row.wantschildren|default:1}{lang('yes')}{else}{lang('no')}{/if}
      {/strip}{/capture}
      <a href="{cms_action_url action='admin_editcontent' content_id=$row.id}" class="page_edit tooltip" accesskey="e" data-cms-content='{$row.id}' data-cms-description='{$tooltip_pageinfo|cms_htmlentities}'>{$row.page|default:''}</a>
      {else}
        {if isset($row.lock)}
         {capture assign='tooltip_lockinfo'}{strip}
       {if $row.can_steal}<strong>{$mod->Lang('locked_steal')}:</strong><br />{/if}
      <strong>{$mod->Lang('locked_by')}:</strong> {$row.lockuser}<br />
      <strong>{$mod->Lang('locked_since')}:</strong> {$row.lock.created|date_format:'%x %H:%M'}<br />
      {if $row.lock.expires < $smarty.now}
       <span style="color:red;"><strong>{$mod->Lang('lock_expired')}:</strong> {$row.lock.expires|relative_time}</span>
      {else}
       <strong>{$mod->Lang('lock_expires')}:</strong> {$row.lock.expires|relative_time}
      {/if}
      <br />{/strip}{/capture}
         {if !$row.can_steal}
          <span class="tooltip" data-cms-description='{$tooltip_lockinfo|htmlentities}'>{$row.page}</span>
         {else}
          <a href="{cms_action_url action='admin_editcontent' content_id=$row.id}" class="page_edit tooltip steal_lock" accesskey="e" data-cms-content='{$row.id}' data-cms-description='{$tooltip_lockinfo|htmlentities}'>{$row.page}</a>
         {/if}
        {else}
         {$row.page}
        {/if}
      {/if}
    {elseif $column == 'alias'}
      {$row.alias|default:''}
    {elseif $column == 'url'}
     {if $prettyurls_ok}
       {$row.url}
     {else}
       <span class="text-red">{$row.url}</span>
     {/if}
    {elseif $column == 'template'}
      {if !empty($row.template)}
        {if $row.can_edit_tpl}
        <a href="{cms_action_url module='DesignManager' action='admin_edit_template' tpl=$row.template_id}" class="page_template" title="{$mod->Lang('prompt_page_template')}">
          {$row.template}
        </a>
        {else}
          {$row.template}
        {/if}
      {elseif $row.viewable}
        <span class="text-red" title="{$mod->Lang('error_template_notavailable')}">{$mod->Lang('critical_error')}</span> {* TODO pageerror *}
      {/if}
    {elseif $column == 'friendlyname'}
      {$row.friendlyname}
    {elseif $column == 'owner'}
      {capture assign='tooltip_ownerinfo'}{strip}
        <strong>{$mod->Lang('prompt_created')}:</strong> {$row.created|cms_date_format}<br />
        <strong>{$mod->Lang('prompt_lastmodified')}:</strong> {$row.lastmodified|cms_date_format}<br />
        {if isset($row.lastmodifiedby)}
        <strong>{$mod->Lang('prompt_lastmodifiedby')}:</strong> {$row.lastmodifiedby}<br />
        {/if}
      {/strip}{/capture}
      <span class="tooltip" data-cms-description='{$tooltip_ownerinfo|htmlentities}'>{$row.owner}</span>
    {elseif $column == 'active'}
      {if $row.active == 'inactive'}
      <a href="{cms_action_url action='defaultadmin' setactive=$row.id}" class="page_setactive" accesskey="a">
        {admin_icon icon='false.gif' class='systemicon' title=$mod->Lang('prompt_page_setactive')}
      </a>
      {elseif $row.active != 'default' && $row.active}
      <a href="{cms_action_url action='defaultadmin' setinactive=$row.id}" class="page_setinactive" accesskey="a">
        {admin_icon icon='true.gif' class='systemicon' title=$mod->Lang('prompt_page_setinactive')}
      </a>
      {/if}
    {elseif $column == 'default'}
       {if $row.default == 'yes'}
        {admin_icon icon='true.gif' class='systemicon page_default' title=$mod->Lang('prompt_page_default')}
       {else if $row.default == 'no' && $row.can_edit}
        <a href="{cms_action_url action='defaultadmin' setdefault=$row.id}" class="page_setdefault" accesskey="d">
         {admin_icon icon='false.gif' class='systemicon page_setdefault' title=$mod->Lang('prompt_page_setdefault')}
        </a>
      {/if}
{*
    {elseif $column == 'move'}
      {if isset($row.move)}
        {if $row.move == 'up'}
        <a href="{cms_action_url action='defaultadmin' moveup=$row.id}" class="page_sortup" accesskey="m">
          {admin_icon icon='arrow-u.gif' class='systemicon' title=$mod->Lang('prompt_page_sortup')}
        </a>
        {elseif $row.move == 'down'}
         <a href="{cms_action_url action='defaultadmin' movedown=$row.id}" class="page_sortdown" accesskey="m">
          {admin_icon icon='arrow-d.gif' class='systemicon' title=$mod->Lang('prompt_page_sortdown')}
         </a>
        {elseif $row.move == 'both'}
        <a href="{cms_action_url action='defaultadmin' moveup=$row.id}" class="page_sortup" accesskey="m">{admin_icon icon='arrow-u.gif' title=$mod->Lang('prompt_page_sortup')}</a>
        <a href="{cms_action_url action='defaultadmin' movedown=$row.id}" class="page_sortdown" accesskey="m">{admin_icon icon='arrow-d.gif' title=$mod->Lang('prompt_page_sortdown')}</a>
        {/if}
      {/if}
    {elseif $column == 'view'}
      {if $row.view != ''}
      <a class="page_view" target="_blank" href="{$row.view}" accesskey="v">
       {admin_icon icon='view.gif' class='systemicon' title=$mod->Lang('prompt_page_view')}
      </a>
      {/if}
    {elseif $column == 'copy'}
      {if $row.copy != ''}
      <a href="{cms_action_url action='admin_copycontent' page=$row.id}" accesskey="o">
       {admin_icon icon='copy.gif' class='systemicon page_copy' title=$mod->Lang('prompt_page_copy')}
      </a>
      {/if}
    {elseif $column == 'edit'}
      {if $row.can_edit}
      <a href="{cms_action_url action=admin_editcontent content_id=$row.id}" accesskey="e" class="page_edit" title="{$mod->Lang('addcontent')}" data-cms-content="{$row.id}">
        {admin_icon icon='edit.gif' class='systemicon page_edit' title=$mod->Lang('prompt_page_edit')}
      </a>
      {else}
        {if isset($row.lock) && $row.can_steal}
        <a href="{cms_action_url action=admin_editcontent content_id=$row.id}" accesskey="e" class="page_edit" title="{$mod->Lang('addcontent')}" data-cms-content="{$row.id}" class="steal_lock">
          {admin_icon icon='permissions.gif' class='systemicon page_edit steal_lock' title=$mod->Lang('prompt_steal_lock_edit')}
        </a>
        {/if}
      {/if}
    {elseif $column == 'addchild'}
      <a href="{cms_action_url action=admin_editcontent parent_id=$row.id}" accesskey="a" class="page_edit" title="{$mod->Lang('addchildhere')}">
       {admin_icon icon='newobject.gif' class='systemicon page_addchild' title=$mod->Lang('prompt_page_addchild')}
      </a>
    {elseif $column == 'delete'}
      {if $row.can_delete && $row.delete != ''}
      <a href="{cms_action_url action='defaultadmin' delete=$row.id}" class="page_delete" accesskey="r">
        {admin_icon icon='delete.gif' class='systemicon page_delete' title=$mod->Lang('prompt_page_delete')}
      </a>
      {/if}
*}
    {elseif $column == 'actions'}
    {$hide=empty($row.lock) || $row.lock == 1}{$t=$mod->Lang('locked_hard')}
      <span class="locked" data-id="{$row.id}" title="{$t}"{if $hide} style="display:none;"{/if}>{admin_icon icon='icons/extra/block.gif' title=$t}</span>
    {$hide=empty($row.lock) || $row.lock == -1}{$t=$mod->Lang('locked_steal')}{$url=sprintf($stealurl,$row.id)}
      <a href="{$url}" class="steal_lock" data-id="{$row.id}" title="{$t}" accesskey="e"{if $hide} style="display:none;"{/if}>{admin_icon icon='permissions.gif' title=$t}</a>
      <span context-menu="Page{$row.id}" style="cursor:pointer;">{admin_icon icon='menu.gif' alt='menu' title=$mod->Lang('title_menu') class='systemicon'}</span>
    {elseif $column == 'multiselect'}
      {if $row.multiselect}
      <label class="invisible" for="cb{$row.id}">{$mod->Lang('prompt_multiselect_toggle')}</label>
      <input type="checkbox" id="cb{$row.id}" name="{$actionid}bulk_content[]" title="{$mod->Lang('prompt_multiselect_toggle')}" value="{$row.id}" />
      {/if}
    {/if}
  </td>
  {/foreach}
 {/function}
{/if}{* $content_list *}

<div class="rowbox flow">
  <div class="pageoptions boxchild">
    {if $can_add_content}
    <a href="{cms_action_url action=admin_editcontent}" accesskey="n" title="{$mod->Lang('prompt_addcontent')}" class="pageoptions">{$t=$mod->Lang('addcontent')}{admin_icon icon='newobject.gif' alt=$t}&nbsp;{$t}</a>
    {/if}
    {if !$have_filter && isset($content_list)}
    <a class="expandall" href="{cms_action_url action='defaultadmin' expandall=1}" accesskey="e" title="{$mod->Lang('prompt_expandall')}">{$t=$mod->Lang('expandall')}{admin_icon icon='expandall.gif' alt=$t}&nbsp;{$t}</a>
    <a class="collapseall" href="{cms_action_url action='defaultadmin' collapseall=1}" accesskey="c" title="{$mod->Lang('prompt_collapseall')}">{$t=$mod->Lang('contractall')}{admin_icon icon='contractall.gif' alt=$t}&nbsp;{$t}</a>
    {if $can_reorder_content}
    <a id="ordercontent" href="{cms_action_url action=admin_ordercontent}" accesskey="r" title="{$mod->Lang('prompt_ordercontent')}">{$t=$mod->Lang('reorderpages')}{admin_icon icon='reorder.gif' alt=$t}&nbsp;{$t}</a>
    {/if}
    {if $have_locks}
    <a id="clearlocks" href="{cms_action_url action=admin_clearlocks}" accesskey="l" title="{$mod->Lang('prompt_clearlocks')}">{$t=$mod->Lang('title_clearlocks')}{admin_icon icon='run.gif' alt=$t}&nbsp;{$t}</a>
    {/if}
    {/if}
    <a id="filterdisplay" accesskey="f" title="{$mod->Lang('prompt_filter')}">{$t=lang('filter')}{admin_icon icon=$filterimage alt=$t}&nbsp;{$t}</a>
    {if !empty($have_filter)}<span style="color: red;"><em>({lang('filter_applied')})</em></span>{/if}
  </div>{*boxchild*}

  <div class="pageoptions options-form boxchild">
    {if isset($content_list)}
    <span><label for="ajax_find">{lang('find')}:</label>&nbsp;
    <input type="text" id="ajax_find" name="ajax_find" title="{$mod->Lang('title_listcontent_find')}" value="" size="25" /></span>
    {/if}
    {if isset($content_list) && $npages > 1}
      {form_start action='defaultadmin'}
       <span>{$mod->Lang('page')}:&nbsp;
        <select name="{$actionid}curpage" id="{$actionid}curpage">
        {html_options options=$pagelist selected=$curpage}
        </select>
        <button type="submit" name="{$actionid}submitpage" class="invisible adminsubmit icon check">{lang('go')}</button>
       </span>
      </form>
    {/if}
  </div>{*boxchild*}
</div>{*rowbox*}

{form_start action='admin_multicontent' id='listform'}
<div id="contentlist">
 {* error container *}
 {if isset($error)}
 <div id="error_cont" class="pageerror">{$error}</div>
 {/if}
 {if isset($content_list)}
  <table id="contenttable" class="pagetable" style="width:auto;">
    <thead>
      <tr>{strip}
        {foreach $columns as $column => $flag}
          {if $flag}<th{if $flag=='icon'} class="pageicon" {elseif $column != 'multiselect'} {/if}
          {if $column == 'expand' || $column == 'hier' || $column == 'icon1'} {* || $column == 'view' || $column == 'copy' || $column == 'edit' || $column == 'delete'*}
            title="{$mod->Lang("coltitle_{$column}")}"> {* no column header *}
          {elseif $column == 'multiselect'}
            ><input type="checkbox" id="selectall" value="1" title="{$mod->Lang('select_all')}" />
          {elseif $column == 'page'}
            title="{$coltitle_page}">{$colhdr_page}
          {elseif $column == 'default' && $have_locks}
            title="{$mod->Lang('error_action_contentlocked')}">({$mod->Lang("colhdr_{$column}")})
          {else}
            title="{$mod->Lang("coltitle_{$column}")}">{$mod->Lang("colhdr_{$column}")}
          {/if}
        {/strip}</th>{/if}
        {/foreach}
      </tr>
    </thead>
    <tbody class="contentrows">
      {foreach $content_list as $row}{strip}{cycle values='row1,row2' assign='rowclass'}
      <tr class="{$rowclass}{if isset($row.selected)} selected{/if}" data-id="{$row.id}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
        {do_content_row row=$row columns=$columns}
      </tr>
{/strip}{/foreach}
    </tbody>
  </table>
{else}
<p class="pageinfo">No page is recorded</p>
{/if} {* $contentlist *}
</div>{* #contentlist *}

{if isset($content_list)}
  <div class="pageoptions rowbox{if $can_add_content} expand">
  <div class="boxchild">
    <a href="{cms_action_url action=admin_editcontent}" accesskey="n" title="{$mod->Lang('prompt_addcontent')}" class="pageoptions">{$t=$mod->Lang('addcontent')}{admin_icon icon='newobject.gif' class='systemicon' alt=$t}&nbsp;{$t}</a>
  </div>
  {else}" style="justify-content:flex-end;">{/if}
  {if $multiselect && isset($bulk_options)}
  <div class="boxchild">
    {cms_help realm=$_module key2='help_bulk' title=$mod->Lang('prompt_bulk')}
    <label for="bulk_action">{$mod->Lang('prompt_withselected')}:</label>&nbsp;
    <select name="{$actionid}bulk_action" id="bulk_action">
      {html_options options=$bulk_options}
    </select>
    <button type="submit" name="{$actionid}bulk_submit" id="bulk_submit" class="adminsubmit icon check">{lang('submit')}</button>
  </div>
  {/if}
  </div>{*rowbox*}
{/if}
</form>{* #listform *}

{if isset($content_list)}
<div id="menus">
 {foreach $menus as $menu}{$menu}{/foreach}
</div>
{/if}
