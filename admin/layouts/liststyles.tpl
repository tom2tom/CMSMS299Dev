{$seeme = (!$activetab || $activetab == 'sheets')}
{tab_header name='sheets' label=_ld('layout','prompt_stylesheets') active=$seeme}
{$seeme = $activetab == 'groups'}
{tab_header name='groups' label=_ld('layout','prompt_css_groups') active=$seeme}

{tab_start name='sheets'}
<div class="row">
  <div class="pageoptions options-menu half">
  {if $has_add_right}
  {$url="editstylesheet.php{$urlext}"}{$t=_ld('layout','create_stylesheet')}
  <a href="{$url}" title="{$t}">{admin_icon icon='newobject.gif'}</a>
  <a href="{$url}">{$t}</a>
  {/if}
  {if $manage_stylesheets}
  &nbsp;&nbsp;<a id="clearlocks" style="display:none;" accesskey="l" title="{_ld('layout','title_clearlocks')}" href="clearlocks.php{$urlext}&type=stylesheet">{admin_icon icon='run.gif' alt=''}&nbsp;{_ld('layout','prompt_clearlocks')}</a>
  {/if}
  </div>
</div>
{if isset($stylesheets) && count($stylesheets) > 1}
{if $navpages > 1}
<div class="browsenav postgap">
 <span id="tblpagelink">
 <span id="ftpage" class="pagechange">{_ld('layout','pager_first')}</span>&nbsp;|&nbsp;
{if $navpages > 2}
 <span id="pspage" class="pagechange">{_ld('layout','pager_previous')}</span>&nbsp;&lt;&gt;&nbsp;
 <span id="ntpage" class="pagechange">{_ld('layout','pager_next')}</span>&nbsp;|&nbsp;
{/if}
 <span id="ltpage" class="pagechange">{_ld('layout','pager_last')}</span>&nbsp;
 ({_ld('layout','pageof','<span id="cpage">1</span>',"<span id='tpage'>{$navpages}</span>")})&nbsp;&nbsp;
 </span>
 <select id="pagerows" name="pagerows">
  {html_options options=$pagelengths selected=$currentlength}
 </select>&nbsp;&nbsp;{_ld('layout','pager_rowspp')}{*TODO sometimes show 'pager_rows'*}
</div>
{/if}{* navpages *}
{/if}
{include file='stylesheets.tpl'}

{tab_start name='groups'}
<div class="pageinfo">{_ld('layout','info_css_groups')}</div>
{if $has_add_right}
<div class="pageoptions pregap">
  {$url="editcssgroup.php{$urlext}"}{$t=_ld('layout','create_group')}
  <a href="{$url}" title="{$t}">{admin_icon icon='newobject.gif'}</a>
  <a href="{$url}">{$t}</a>
</div>
{/if}
{if isset($list_groups) && count($list_groups) > 1}
{if $grppages > 1}
<div class="browsenav postgap">
 <span id="tblpagelink2">
 <a href="javascript:pagefirst(grptable)">{_ld('layout','pager_first')}</a>&nbsp;|&nbsp;
{if $grppages > 2}
 <a href="javascript:pageback(grptable)">{_ld('layout','pager_previous')}</a>&nbsp;&lt;&gt;&nbsp;
 <a href="javascript:pageforw(grptable)">{_ld('layout','pager_next')}</a>&nbsp;|&nbsp;
{/if}
 <a href="javascript:pagelast(grptable)">{_ld('layout','pager_last')}</a>&nbsp;
 ({_ld('layout','pageof','<span id="cpage2">1</span>',"<span id='tpage2'>{$grppages}</span>")})&nbsp;&nbsp;
 </span>
 <select id="pagerows2" name="pagerows2">
  {html_options options=$pagelengths2 selected=$currentlength2}
 </select>&nbsp;&nbsp;{_ld('layout','pager_rowspp')}{*TODO sometimes show 'pager_rows'*}
</div>
{/if}{* grppages *}
{/if}
{include file='stylesheetgroups.tpl'}
{tab_end}

{if $manage_stylesheets} {* TODO && single(s) or group(s) exist *}
<div id="replacedialog" title="" style="display:none;min-width:15em;">
  <form id="replacedialog_form" action="stylesheetoperations.php" enctype="multipart/form-data" method="post">
  {foreach $extraparms2 as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />
{/foreach}
   <p>{_ld('layout','prompt_current')}<br />
   <span id="from"></span>
   </p>
   <div class="pregap">
    {$t=_ld('layout','prompt_replacement')}<label class="pagetext" for="replacement">{$t}</label>&nbsp;
    {cms_help 0='layout' key='help_replace_stylesheet' title=$t}
    <div class="pageinput">
     <select id="replacement" name="newcss">
      {html_options options=$css_choices selected=-1}     </select>
    </div>
   </div>
  </form>
</div>
{/if}
