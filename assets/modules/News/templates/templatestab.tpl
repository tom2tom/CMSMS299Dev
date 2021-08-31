<div class="rowbox expand">
  {if $can_mod}
  <div class="pageoptions boxchild">
    <a href="{cms_action_url action='edittemplate' tpl='-1'}">{admin_icon icon='newobject.gif' alt=$mod->Lang('addtemplate')} {$mod->Lang('addtemplate')}</a>
  </div>{*boxchild*}
  {/if}
{if $tplcount > 0 && isset($rowchanger2)}
  <div class="boxchild">
   {$mod->Lang('pageof','<span id="cpage2">1</span>',"<span id='tpage2' style='margin-right:0.5em;'>`$totpg2`</span>")}
   {$rowchanger2}{$mod->Lang('pagerows')}
   <a href="javascript:pagefirst(tpltable)">{$mod->Lang('first')}</a>
{if $tplpages > 2}
   <a href="javascript:pageprev(tpltable)">{$mod->Lang('previous')}</a>
   <a href="javascript:pagenext(tpltable)">{$mod->Lang('next')}</a>
{/if}
   <a href="javascript:pagelast(tpltable)">{$mod->Lang('last')}</a>
  </div>{*boxchild*}
{/if}
</div>{*rowbox*}

{if !empty($tpllist)}
<table id="tpltable" class="pagetable{if $tplcount > 1} table_sort{/if}" style="width:auto;">
 <thead>
  <tr>
    <th{if $tplcount > 1} class="{literal}{sss:'text'}{/literal}"{/if}>{lang('name')}</th>
    <th{if $tplcount > 1} class="{literal}{sss:'text'}{/literal}"{/if}>{lang('type')}</th>
    <th{if $tplcount > 1} class="{literal}{sss:'icon'}{/literal}"{/if} title="{$mod->Lang('tip_tpl_type')}">{lang('default')}</th>
    <th class="pageicon{if $tplcount > 1} nosort{/if}"></th>{/strip}
  </tr>
 </thead>
 <tbody>
  {foreach $tpllist as $elem}{cycle name='tpls' values="row1,row2" assign='rowclass'}
  <tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
    {strip}<td{if $elem->desc} title="{$elem->desc}"{/if}>
       {if $elem->url}
      <a href="{$elem->url}" class="edit_tpl" title="{$mod->Lang('tip_edit_template')}">{$elem->name}</a>
       {else}
      {$elem->name}
       {/if}
    </td>
    <td>{$elem->type}</td>
    <td>{$elem->dflt}</td>
    <td>
    {if $elem->edit}{$elem->edit}{else}&nbsp;{/if}
    {if $elem->copy}{$elem->copy}{else}&nbsp;{/if}
    {if $elem->del}{$elem->del}{/if}
    </td>{/strip}
  </tr>
  {/foreach}
 </tbody>
</table>
{else}
<p class="pageinfo">{$mod->Lang('notemplate')}</p>
{/if}
