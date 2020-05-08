{$seeme = (!$activetab || $activetab == 'sheets')}
{tab_header name='sheets' label=lang_by_realm('layout','prompt_stylesheets') active=$seeme}
{$seeme = $activetab == 'groups'}
{tab_header name='groups' label=lang_by_realm('layout','prompt_css_groups') active=$seeme}

{tab_start name='sheets'}
<div class="row">
  <div class="pageoptions options-menu half">
  {if $has_add_right}
  {$url="editstylesheet.php`$urlext`"}{$t=lang_by_realm('layout','create_stylesheet')}
  <a href="{$url}" title="{$t}">{admin_icon icon='newobject.gif'}</a>
  <a href="{$url}">{$t}</a>
  {/if}
  {if $manage_stylesheets}
  &nbsp;&nbsp;<a id="clearlocks" style="display:none;" accesskey="l" title="{lang_by_realm('layout','title_clearlocks')}" href="clearlocks.php{$urlext}&amp;type=stylesheet">{admin_icon icon='run.gif' alt=''}&nbsp;{lang_by_realm('layout','prompt_clearlocks')}</a>
  {/if}
  </div>
</div>
{include file='stylesheets.tpl'}

{tab_start name='groups'}
<div class="pageinfo">{lang_by_realm('layout','info_css_groups')}</div>
{if $has_add_right}
<div class="pageoptions pregap">
  {$url="editcssgroup.php`$urlext`"}{$t=lang_by_realm('layout','create_group')}
  <a href="{$url}" title="{$t}">{admin_icon icon='newobject.gif'}</a>
  <a href="{$url}">{$t}</a>
</div>
{/if}
{include file='stylesheetgroups.tpl'}
{tab_end}

{if $manage_stylesheets} {* TODO && single(s) or group(s) exist *}
<div id="replacedialog" title="" style="display:none;min-width:15em;">
  <form id="replacedialog_form" action="stylesheetoperations.php" enctype="multipart/form-data" method="post">
  {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />{/foreach}
  <p>{lang_by_realm('layout','prompt_current')}<br />
  <span id="from"></span>
  </p>
  <div class="pageinput pregap">
  {$t=lang_by_realm('layout','prompt_replacement')}<label for="replacement">{$t}</label>&nbsp;
  {cms_help realm='layout' key2='help_replace_stylesheet' title=$t}
  <br />
  <select id="replacement" name="newcss">
  {html_options options=$css_choices selected=-1}
  </select>
  </div>
  </form>
</div>
{/if}
