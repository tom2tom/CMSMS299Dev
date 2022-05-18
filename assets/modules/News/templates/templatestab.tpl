<div class="rowbox expand">
  {if $can_mod}
  <div class="pageoptions boxchild">
    <a href="{cms_action_url action='edittemplate' tpl='-1'}">{admin_icon icon='newobject.gif' alt=_ld($_module,'addtemplate')} {_ld($_module,'addtemplate')}</a>
  </div>{*boxchild*}
  {/if}
{if $tplcount2 > 0 && isset($rowchanger2)}
  <div class="boxchild">
   <span id="tpglink">
   <a href="javascript:pagefirst(tpltable)">{_ld('layout','pager_first')}</a>
{if $tplpages2 > 2}
   <a href="javascript:pageprev(tpltable)">{_ld('layout','pager_previous')}</a>
   <a href="javascript:pagenext(tpltable)">{_ld('layout','pager_next')}</a>
{/if}
   <a href="javascript:pagelast(tpltable)">{_ld('layout','pager_last')}</a>
   {_ld($_module,'pageof','<span id="cpage2">1</span>',"<span id='tpage2' style='margin-right:0.5em;'>`$totpg2`</span>")}
   </span>
   {$rowchanger2}{_ld($_module,'pagerows')}
  </div>{*boxchild*}
{/if}
</div>{*rowbox*}

{if !empty($tpllist2)}
<table id="tpltable" class="pagetable{if $tplcount2 > 1} table_sort{/if}">
 <thead>
  <tr>
    <th{if $tplcount2 > 1} class="{literal}{sss:'text'}{/literal}"{/if}>{_la('name')}</th>
    <th{if $tplcount2 > 1} class="{literal}{sss:'text'}{/literal}"{/if}>{_la('type')}</th>
    <th{if $tplcount2 > 1} class="{literal}{sss:'forint'}{/literal}"{/if} title="{_ld($_module,'tip_tpl_type')}">{_la('default')}</th>
    <th class="pageicon{if $tplcount2 > 1} nosort{/if}"></th>{* TODO actions menu-column instead of these *}
    <th class="pageicon{if $tplcount2 > 1} nosort{/if}"></th>
    <th class="pageicon{if $tplcount2 > 1} nosort{/if}"></th>{/strip}
  </tr>
 </thead>
 <tbody>
  {foreach $tpllist2 as $elem}{cycle name='tpls' values="row1,row2" assign='rowclass'}
  <tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
    {strip}<td{if $elem->desc} title="{$elem->desc}"{/if}>
       {if $elem->url}
      <a href="{$elem->url}" class="edit_tpl" title="{_ld($_module,'tip_edit_template')}">{$elem->name}</a>
       {else}
      {$elem->name}
       {/if}
    </td>
    <td>{$elem->type}</td>
    <td class=pagepos icons_wide"><span style="display:none">$elem->dflt_mode</span>{$elem->dflt}</td>
    <td class=pagepos icons_wide">{if $elem->edit}{$elem->edit}{/if}</td>{* TODO actions menu instead of these *}
    <td class=pagepos icons_wide">{if $elem->copy}{$elem->copy}{/if}</td>
    <td class=pagepos icons_wide">{if $elem->del}{$elem->del}{/if}</td>{/strip}
  </tr>
  {/foreach}
 </tbody>
</table>
{else}
<p class="pageinfo">{_ld($_module,'notemplate')}</p>
{/if}
