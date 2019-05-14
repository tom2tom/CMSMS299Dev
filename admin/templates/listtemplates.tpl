{if $manage_templates}
  {$seeme = (!$activetab || $activetab == 'templates')}
  {tab_header name='templates' label=lang_by_realm('layout','prompt_templates') active=$seeme}
  {$seeme = $activetab == 'groups'}
  {tab_header name='groups' label=lang_by_realm('layout','prompt_tpl_groups') active=$seeme}
  {$seeme = $activetab == 'types'}
  {tab_header name='types' label=lang_by_realm('layout','prompt_templatetypes') active=$seeme}

{tab_start name='templates'}
{/if}
<div class="row">
  <div class="pageoptions options-menu half">
    {if $has_add_right}
    <a id="addtemplate" href="edittemplate.php{$urlext}" accesskey="a" title="{lang_by_realm('layout','create_template')}">{admin_icon icon='newobject.gif' alt=lang_by_realm('layout','create_template')}&nbsp;{lang_by_realm('layout','create_template')}</a>&nbsp;&nbsp;
    {/if}
   <a id="clearlocks" style="display:none;" accesskey="l" title="{lang_by_realm('layout','title_clearlocks')}" href="clearlocks.php{$urlext}&type=template">{admin_icon icon='run.gif' alt=''}&nbsp;{lang_by_realm('layout','prompt_clearlocks')}</a>
    {if isset($templates)}
    &nbsp;&nbsp;<a class="edit_filter" accesskey="f" title="{lang_by_realm('layout','title_edittplfilter')}">{admin_icon icon='icons/extra/filter.gif' alt=lang_by_realm('layout','title_edittplfilter')}&nbsp;{lang_by_realm('layout','filter')}</a>
    {if !empty($tpl_filter.0)}
    &nbsp;&nbsp;<span style="color:green;" title="{lang_by_realm('layout','title_filterapplied')}">{lang_by_realm('layout','filterapplied')}</span>
    {/if}
    {/if}
  </div>
</div>

{if !empty($templates)}
{if $navpages > 1}
<div class="browsenav postgap">
 <a href="javascript:pagefirst(tpltable)">{lang_by_realm('layout','pager_first')}</a>&nbsp;|&nbsp;
{if $navpages > 2}
 <a href="javascript:pageback(tpltable)">{lang_by_realm('layout','pager_previous')}</a>&nbsp;&lt;&gt;&nbsp;
 <a href="javascript:pageforw(tpltable)">{lang_by_realm('layout','pager_next')}</a>&nbsp;|&nbsp;
{/if}
 <a href="javascript:pagelast(tpltable)">{lang_by_realm('layout','pager_last')}</a>&nbsp;
 ({lang_by_realm('layout','pageof','<span id="cpage">1</span>',"<span id='tpage'>`$navpages`</span>")})&nbsp;&nbsp;
 <select id="pagerows" name="pagerows">
  {html_options options=$pagelengths selected=$currentlength}
 </select>&nbsp;&nbsp;{lang_by_realm('layout','pager_rows')}
</div>
{/if} {* navpages *}
{/if}
{include file='templates.tpl'}

{if $manage_templates}
 {tab_start name='groups'}
 {include file='template-groups.tpl'}
 {tab_start name='types'}
 {include file='template-types.tpl'}
 {tab_end}
{/if}

<div id="filterdialog" title="{lang_by_realm('layout','tpl_filter')}" style="display:none;">
 <form id="filterdialog_form" action="{$selfurl}" enctype="multipart/form-data" method="post">
  {foreach $extraparms2 as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />
  {/foreach}
  <select class="boxchild" id="filter_tpl" name="filter[]" title="{lang_by_realm('layout','title_filter')}">
   {html_options options=$filter_tpl_options selected=$tpl_filter.0}
  </select>
 </form>
</div>

{if $manage_templates}{* TODO && single(s) exist *}
<div id="replacedialog" title="{lang_by_realm('layout','prompt_replace_typed',lang_by_realm('layout','prompt_template'))}" style="display:none;min-width:15em;">
  <form id="replacedialog_form" action="templateoperations.php" enctype="multipart/form-data" method="post">
  {foreach $extraparms3 as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />
  {/foreach}
  <p>{lang_by_realm('layout','prompt_current')}<br />
  <span id="from"></span>
  </p>
  <div class="pregap">
  {$t=lang_by_realm('layout','prompt_replacement')}<label for="replacement">{$t}</label>&nbsp;
  {cms_help realm='layout' key2='help_replace_template' title=$t}
  <br />
  <select id="replacement" name="newtpl">
  {html_options options=$tpl_choices selected=-1}
  </select>
  </div>
  </form>
</div>
{/if}
