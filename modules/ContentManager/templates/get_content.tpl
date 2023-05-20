{if isset($content_list)}
 {function do_content_row}
  {foreach $columns as $column => $flag}
  {if !$flag}{continue}{/if}
  <td class="{$column}">
    {if $column == 'expand'}
      {if empty($pattern)}
      {if $row.expand == 'open'}{$t=_ld($_module,'prompt_page_collapse')}
       <a href="{cms_action_url action='defaultadmin' collapse=$row.id}" class="page_collapse" accesskey="C" title="{$t}">
        {admin_icon icon='contract.gif' class='systemicon hier_contract' title=$t}
       </a>
      {elseif $row.expand == 'closed'}{$t=_ld($_module,'prompt_page_expand')}
       <a href="{cms_action_url action='defaultadmin' expand=$row.id}" class="page_expand" accesskey="c" title="{$t}">
        {admin_icon icon='expand.gif' class='systemicon hier_expand' title=$t}
       </a>
      {/if}
      {/if}
    {elseif $column == 'icon1'}
      {if isset($row.lock)} {admin_icon icon='warning.gif' class='systemicon' title=_ld($_module,'title_locked')} {/if}
    {elseif $column == 'hier'}
      {$row.hier}
    {elseif $column == 'page'}
      {if $row.can_edit}
      {if $indent}{repeat string='-&nbsp;&nbsp;' times=$row.depth-2}{/if}
      {* the tooltip *}{capture assign='tooltip_pageinfo'}{strip}
      <strong>{_ld($_module,'prompt_content_id')}:</strong> {$row.id}<br>
      <strong>{_ld($_module,'prompt_title')}:</strong> {$row.title|escape}<br>
      <strong>{_ld($_module,'prompt_name')}:</strong> {$row.menutext|escape}<br>
      {if isset($row.alias)}<strong>{_ld($_module,'prompt_alias')}:</strong> {$row.alias}<br>{/if}
      {if $row.url}
       <strong>{_ld($_module,'colhdr_url')}:</strong>
       {if $prettyurls_ok}
         {$row.url}
       {else}
         <span class="red">{$row.url}</span>
       {/if}
      {/if}
      <strong>{_ld($_module,'prompt_owner')}:</strong> {$row.owner}<br>
      <strong>{_ld($_module,'prompt_created')}:</strong> {$row.created|cms_date_format:'timed'}<br>
      {if !isset($columns['modified'])}
      <strong>{_ld($_module,'prompt_lastmodified')}:</strong> {$row.lastmodified|cms_date_format:'timed'}<br>
      {/if}
      {if isset($row.lastmodifiedby)}<strong>{_ld($_module,'prompt_lastmodifiedby')}:</strong> {$row.lastmodifiedby}<br>{/if}
      <strong>{_ld($_module,'prompt_cachable')}:</strong> {if $row.cachable}{_ld($_module,'yes')}{else}{_ld($_module,'no')}{/if}<br>
      <strong>{_ld($_module,'prompt_showinmenu')}:</strong> {if $row.showinmenu}{_ld($_module,'yes')}{else}{_ld($_module,'no')}{/if}<br>
      <strong>{_ld($_module,'wantschildren')}:</strong> {if $row.wantschildren|default:1}{_ld($_module,'yes')}{else}{_ld($_module,'no')}{/if}
      {/strip}{/capture}
      <a href="{cms_action_url action='editcontent' content_id=$row.id}" class="page_edit tooltip" accesskey="e" data-cms-content="{$row.id}" data-cms-description="{$tooltip_pageinfo|cms_escape:'htmlall'}">{$row.page|default:''}</a>
      {else}
        {if isset($row.lock)}
         {capture assign='tooltip_lockinfo'}{strip}
       {if $row.can_steal}<strong>{_ld($_module,'locked_steal')}:</strong><br>{/if}
      <strong>{_ld($_module,'locked_by')}:</strong> {$row.lockuser}<br>
      <strong>{_ld($_module,'locked_since')}:</strong> {$row.lock.created|cms_date_format:'timed'}<br>
      {if $row.lock.expires < $smarty.now}
       <span style="color:red;"><strong>{_ld($_module,'lock_expired')}:</strong> {$row.lock.expires|relative_time}</span>
      {else}
       <strong>{_ld($_module,'lock_expires')}:</strong> {$row.lock.expires|relative_time}
      {/if}
      <br>{/strip}{/capture}
         {if !$row.can_steal}
          <span class="tooltip" data-cms-description='{$tooltip_lockinfo|escape:'htmlall'}'>{$row.page}</span>
         {else}
          <a href="{cms_action_url action='editcontent' content_id=$row.id}" class="page_edit tooltip steal_lock" accesskey="e" data-cms-content="{$row.id}" data-cms-description="{$tooltip_lockinfo|escape:'htmlall'}">{$row.page}</a>
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
        <a href="{cms_action_url module='ContentManager' action='edittemplate' tpl=$row.template_id}" class="page_template" title="{_ld($_module,'prompt_page_template')}">
          {$row.template}
        </a>
        {else}
          {$row.template}
        {/if}
      {elseif $row.viewable}
        <span class="text-red" title="{_ld($_module,'error_template_notavailable')}">{_ld($_module,'critical_error')}</span> {* TODO pageerror *}
      {/if}
    {elseif $column == 'friendlyname'}
      {$row.friendlyname}
    {elseif $column == 'owner'}
      {capture assign='tooltip_ownerinfo'}{strip}
        <strong>{_ld($_module,'prompt_created')}:</strong> {$row.created|cms_date_format:'timed'}<br>
        {if !isset($columns['modified'])}
        <strong>{_ld($_module,'prompt_lastmodified')}:</strong> {$row.lastmodified|cms_date_format:'timed'}<br>
        {/if}
        {if isset($row.lastmodifiedby)}
        <strong>{_ld($_module,'prompt_lastmodifiedby')}:</strong> {$row.lastmodifiedby}<br>
        {/if}
      {/strip}{/capture}
      <span class="tooltip" data-cms-description="{$tooltip_ownerinfo|escape:'htmlall'}">{$row.owner}</span>
    {elseif $column == 'active'}{*TODO td style="text-align:center"*}
      {if $row.active == 'inactive'}
      <a href="{cms_action_url action='defaultadmin' setactive=$row.id}" class="page_setactive" accesskey="a">
        {admin_icon icon='false.gif' class='systemicon' title=_ld($_module,'prompt_page_setactive')}
      </a>
      {elseif $row.active != 'default' && $row.active}
      <a href="{cms_action_url action='defaultadmin' setinactive=$row.id}" class="page_setinactive" accesskey="a">
        {admin_icon icon='true.gif' class='systemicon' title=_ld($_module,'prompt_page_setinactive')}
      </a>
      {/if}
    {elseif $column == 'default'}{*TODO td style="text-align:center"*}
       {if $row.default == 'yes'}
        {admin_icon icon='true.gif' class='systemicon page_default' title=_ld($_module,'prompt_page_default')}
       {else if $row.default == 'no' && $row.can_edit}
        <a href="{cms_action_url action='defaultadmin' setdefault=$row.id}" class="page_setdefault" accesskey="d">
         {admin_icon icon='false.gif' class='systemicon page_setdefault' title=_ld($_module,'prompt_page_setdefault')}
        </a>
      {/if}
    {elseif $column == 'created'}
       <span style="display:none">{$row.created}</span>{$row.created|date_format:'Y-m-d H:i'}
    {elseif $column == 'modified'}
       <span style="display:none">{$row.lastmodified}</span>{$row.lastmodified|date_format:'Y-m-d H:i'}
    {elseif $column == 'actions'}{*TODO td style="text-align:center"*}
    {$hide=empty($row.lock) || $row.lock == 1}{$t=_ld($_module,'locked_hard')}
      <span class="locked" data-id="{$row.id}" title="{$t}"{if $hide} style="display:none;"{/if}>{admin_icon icon='icons/extra/block.gif' title=$t}</span>
    {$hide=empty($row.lock) || $row.lock == -1}{$t=_ld($_module,'locked_steal')}{$url=sprintf($stealurl,$row.id)}
      <a href="{$url}" class="steal_lock" data-id="{$row.id}" title="{$t}" accesskey="e"{if $hide} style="display:none;"{/if}>{admin_icon icon='permissions.gif' title=$t}</a>
      <span context-menu="Page{$row.id}" style="cursor:pointer;">{admin_icon icon='menu.gif' alt='menu' title=_ld($_module,'title_menu') class='systemicon'}</span>
    {elseif $column == 'multiselect'}{*TODO td style="text-align:center"*}
      {if $row.multiselect}
      <label class="invisible" for="cb{$row.id}">{_ld($_module,'prompt_multiselect_toggle')}</label>
      <input type="checkbox" id="cb{$row.id}" name="{$actionid}bulk_content[]" title="{_ld($_module,'prompt_multiselect_toggle')}" value="{$row.id}">
      {/if}
    {/if}
  </td>
  {/foreach}
 {/function}
{/if}{* $content_list *}

<div class="rowbox flow" style="align-items:center;">
  <div class="pageoptions boxchild">
    {if $can_add_content}
    <a href="{cms_action_url action=editcontent}" accesskey="n" title="{_ld($_module,'prompt_addcontent')}" class="pageoptions">{$t=_ld($_module,'addcontent')}{admin_icon icon='newobject.gif' alt=$t}&nbsp;{$t}</a>
    {/if}
    {if !$have_filter && isset($content_list)}
    <a class="expandall" href="{cms_action_url action='defaultadmin' expandall=1}" accesskey="e" title="{_ld($_module,'prompt_expandall')}">{$t=_ld($_module,'expandall')}{admin_icon icon='expandall.gif' alt=$t}&nbsp;{$t}</a>
    <a class="collapseall" href="{cms_action_url action='defaultadmin' collapseall=1}" accesskey="c" title="{_ld($_module,'prompt_collapseall')}">{$t=_ld($_module,'contractall')}{admin_icon icon='contractall.gif' alt=$t}&nbsp;{$t}</a>
    {if $can_reorder_content}
    <a id="ordercontent" href="{cms_action_url action=ordercontent}" accesskey="r" title="{_ld($_module,'prompt_ordercontent')}">{$t=_ld($_module,'reorderpages')}{admin_icon icon='reorder.gif' alt=$t}&nbsp;{$t}</a>
    {/if}
    {if $have_locks}
    <a id="clearlocks" href="{cms_action_url action=clearlocks}" accesskey="l" title="{_ld($_module,'prompt_clearlocks')}">{$t=_ld($_module,'title_clearlocks')}{admin_icon icon='run.gif' alt=$t}&nbsp;{$t}</a>
    {/if}
    {/if}
    <a id="filterdisplay" accesskey="f" title="{_ld($_module,'prompt_filter')}">{$t=_ld($_module,'filter')}{admin_icon icon='icons/extra/filter' alt=$t}&nbsp;{$t}</a>
    {if !empty($have_filter)}<span style="color: red;"><em>({_ld($_module,'filter_applied')})</em></span>{/if}
  </div>{*boxchild*}
  <div class="boxchild">
    {if $lang_dir == 'rtl'}
    {admin_icon icon='icons/extra/search' alt="{_ld('layout','search')}" addtext='style=position:relative;left:1.8em'}
    {/if}
    <input type="text" id="ajax_find" title="{_ld($_module,'title_listcontent_find')}" size="10" maxlength="15" value="{$pattern}" placeholder="{_ld('layout','search')}">
    {if $lang_dir != 'rtl'}
    {admin_icon icon='icons/extra/search' alt="{_ld('layout','search')}" addtext='style=position:relative;left:-1.8em'}
    {/if}
  </div>{*boxchild*}
  {if (empty($pattern) && $npages > 1)}
  <div class="options-form boxchild">
    {form_start action='defaultadmin'}
      <span>{_ld($_module,'page')}:&nbsp;
        <select name="{$actionid}curpage" id="{$actionid}curpage">
         {html_options options=$pagelist selected=$curpage}    </select>
        <button type="submit" name="{$actionid}submitpage" class="invisible adminsubmit icon check">{_ld($_module,'go')}</button>
      </span>
    </form>
  </div>{*boxchild*}
  {/if}
</div>{*rowbox*}

{form_start action='multicontent' id='listform'}
<div id="contentlist">
 {* error container *}
 {if isset($error)}
 <div id="error_cont" class="pageerror">{$error}</div>
 {/if}
 {if isset($content_list)}
  <table id="contenttable" class="pagetable">
    <thead>
      <tr>{strip}
        {foreach $columns as $column => $type}
          {if $type}
          {if empty($sortcols.$column)}
           {$styp='false'}
          {elseif $sortcols.$column == 'text'}
           {$styp='text'}
          {elseif $sortcols.$column == 'link'}
           {$styp='linktext'}
          {elseif $sortcols.$column == 'icon'}
           {$styp='icon'}
          {elseif $sortcols.$column == 'number'}
           {$styp='numeric'}
          {elseif $sortcols.$column == 'date'}
           {$styp='intfor'}
          {else}
           {$styp='false'}
          {/if}
          <th class="{if $type=='icon'}pageicon {/if}{literal}{sss:{/literal}{$styp}{literal}}{/literal}"{if $column != 'multiselect'} {/if}
          {if $column == 'expand' || $column == 'hier' || $column == 'icon1'} {* || $column == 'view' || $column == 'copy' || $column == 'edit' || $column == 'delete'*}
            title="{_ld($_module,"coltitle_{$column}")}"> {* no column header *}
          {elseif $column == 'multiselect'}
            ><input type="checkbox" id="selectall" value="1" title="{_ld($_module,'select_all')}">
          {elseif $column == 'page'}
            title="{$coltitle_page}">{$colhdr_page}
          {elseif $column == 'default' && $have_locks}
            title="{_ld($_module,'error_action_contentlocked')}">({_ld($_module,"colhdr_{$column}")})
          {else}
            title="{_ld($_module,"coltitle_{$column}")}">{_ld($_module,"colhdr_{$column}")}
          {/if}
        {/strip}</th>{/if}{* $type *}
        {/foreach}
      </tr>
    </thead>
    <tbody class="contentrows">
      {foreach $content_list as $row}{strip}{cycle values='row1,row2' assign='rowclass'}
      <tr class="{$rowclass}{if isset($row.active) && $row.active=='inactive'} inactive{/if}{if isset($row.selected)} selected{/if}" data-id="{$row.id}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
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
    <a href="{cms_action_url action=editcontent}" accesskey="n" title="{_ld($_module,'prompt_addcontent')}" class="pageoptions">{$t=_ld($_module,'addcontent')}{admin_icon icon='newobject.gif' class='systemicon' alt=$t}&nbsp;{$t}</a>
  </div>
  {else}" style="justify-content:flex-end;">{/if}
  {if $multiselect && isset($bulk_options)}
  <div class="boxchild">
    {cms_help 0=$_module key='help_bulk' title=_ld($_module,'prompt_bulk')}
    <label for="bulkaction">{_ld($_module,'prompt_withselected')}:</label>&nbsp;
    <select name="{$actionid}bulk_action" id="bulkaction">
      {html_options options=$bulk_options}    </select>
    <button type="submit" name="{$actionid}bulk_submit" id="bulk_submit" class="adminsubmit icon check">{_ld($_module,'submit')}</button>
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
