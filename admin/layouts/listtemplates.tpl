{if $manage_templates}
  {$seeme = (!$activetab || $activetab == 'templates')}
  {tab_header name='templates' label=_ld('layout','prompt_templates') active=$seeme}
  {$seeme = $activetab == 'types'}
  {tab_header name='types' label=_ld('layout','prompt_templatetypes') active=$seeme}
  {$seeme = $activetab == 'groups'}
  {tab_header name='groups' label=_ld('layout','prompt_tpl_groups') active=$seeme}

{tab_start name='templates'}
{/if}
<div class="row">
  <div class="pageoptions options-menu{if (empty($templates) || $navpages < 2)} half{/if}">
    {if $has_add_right}
    <a id="addtemplate" href="edittemplate.php{$urlext}" accesskey="a" title="{_ld('layout','create_template')}">{admin_icon icon='newobject.gif' alt=_ld('layout','create_template')}&nbsp;{_ld('layout','create_template')}</a>&nbsp;&nbsp;
    {/if}
   <a id="clearlocks" style="display:none;" accesskey="l" title="{_ld('layout','title_clearlocks')}" href="clearlocks.php{$urlext}&type=template">{admin_icon icon='run.gif' alt=''}&nbsp;{_ld('layout','prompt_clearlocks')}</a>
    {if !empty($templates)}
    &nbsp;&nbsp;<a class="edit_filter" accesskey="f" title="{_ld('layout','title_edittplfilter')}">{admin_icon icon='icons/extra/filter.gif' alt=_ld('layout','title_edittplfilter')}&nbsp;{_ld('layout','filter')}</a>
    {if !empty($tpl_filter.0)}
    &nbsp;&nbsp;<span style="color:green;" title="{_ld('layout','title_filterapplied')}">{_ld('layout','filterapplied')}</span>
    {/if}
    {if $navpages > 1}
    &nbsp;&nbsp;
    {if $direction == 'rtl'}
    {admin_icon icon='icons/extra/search' alt="{_ld('layout','search')}" addtext='style=position:relative;left:1.8em'}
    {/if}
    <input type="text" id="finder" title="{_ld('layout','title_search')}" size="10" maxlength="15" placeholder="{_ld('layout','search')}" />
    {if $direction != 'rtl'}
    {admin_icon icon='icons/extra/search' alt="{_ld('layout','search')}" addtext='style=position:relative;left:-1.8em'}
    {/if}
    {/if}
    {/if}
  </div>
</div>

{if !empty($templates)}
{if $navpages > 1}
<div class="browsenav postgap">
 <span id="tblpagelink">
 <a href="javascript:pagefirst(tpltable)">{_ld('layout','pager_first')}</a>&nbsp;|&nbsp;
{if $navpages > 2}
 <a href="javascript:pageback(tpltable)">{_ld('layout','pager_previous')}</a>&nbsp;&lt;&gt;&nbsp;
 <a href="javascript:pageforw(tpltable)">{_ld('layout','pager_next')}</a>&nbsp;|&nbsp;
{/if}
 <a href="javascript:pagelast(tpltable)">{_ld('layout','pager_last')}</a>&nbsp;
 ({_ld('layout','pageof','<span id="cpage">1</span>',"<span id='tpage'>`$navpages`</span>")})&nbsp;&nbsp;
 </span>
 <select id="pagerows" name="pagerows">
  {html_options options=$pagelengths selected=$currentlength}
 </select>&nbsp;&nbsp;{_ld('layout','pager_rows')}
</div>
{/if} {* navpages *}
{/if}
{include file='templates.tpl'}

{if $manage_templates}
 {tab_start name='types'}
 {include file='template-types.tpl'}
 {tab_start name='groups'}
 {include file='template-groups.tpl'}
 {tab_end}
{/if}

<div id="filterdialog" title="{_ld('layout','tpl_filter')}" style="display:none;">
 <form id="filterdialog_form" action="{$selfurl}" enctype="multipart/form-data" method="post">
  {foreach $extraparms2 as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />
  {/foreach}
  <select class="boxchild" id="filter_tpl" name="filter[]" title="{_ld('layout','title_filter')}">
   {html_options options=$filter_tpl_options selected=$tpl_filter.0}  </select>
 </form>
</div>

{if $manage_templates}{* TODO && single(s) exist *}
<div id="replacedialog" title="{_ld('layout','prompt_replace_typed',_ld('layout','prompt_template'))}" style="display:none;min-width:15em;">
  <form id="replacedialog_form" action="templateoperations.php" enctype="multipart/form-data" method="post">
  {foreach $extraparms3 as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />
{/foreach}
   <p>{_ld('layout','prompt_current')}<br />
   <span id="from"></span>
   </p>
   <div class="pregap">
    {$t=_ld('layout','prompt_replacement')}<label class="pagetext" for="replacement">{$t}</label>
    {cms_help 0='layout' key='help_replace_template' title=$t}
    <div class="pageinput">
     <select id="replacement" name="newtpl">
      {html_options options=$tpl_choices selected=-1}     </select>
    </div>
   </div>
  </form>
</div>
{/if}
