<ul class="sortableList {if $depth==1}sortable{/if}">
  {foreach $list as $child} {strip} {$obj=$child->getContent(false,true,false)} {if is_object($obj)}
  <li id="page_{$obj->Id()}">
    <div class="label" {if !$obj->Active()}style="color: red;"{/if}>
      &nbsp;{$obj->Hierarchy()}:&nbsp;{$obj->Name()}{if !$obj->Active()}&nbsp;({lang('inactive')}){/if} <em>({$obj->MenuText()})</em>
    </div>
    {if $child->has_children()} {include file="ordercontent_tree.tpl" list=$child->getChildren(false,true) depth=$depth+1 tree=''} {/if}
  </li>
  {/if} {/strip} {/foreach}
</ul>
