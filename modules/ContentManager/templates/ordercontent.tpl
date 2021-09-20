{function display_tree}
  {foreach $list as $node}
    {$obj=$node->getContent()}{$desc=$node->has_children()}
    <li id="page_{$obj->Id()}" {if !$obj->WantsChildren()}class="no-nest"{/if}>
      <div class="label{if !$obj->Active()} red{/if}">
        <span>&nbsp;</span>{$obj->Hierarchy()}:&nbsp;{$obj->Name()|cms_escape}{if !$obj->Active()}&nbsp;({_ld($_module,'prompt_inactive')}){/if} <em>({$obj->MenuText()|cms_escape})</em>
        {if $desc}
          <span class="haschildren expanded">-</span>
        {/if}
      </div>
      {if $desc}
      <ul>
        {$list=$node->getChildren(false,true)}
        {display_tree list=$list depth=$depth+1}
      </ul>
      {/if}
    </li>
  {/foreach}
{/function}

<h3>{_ld($_module,'prompt_ordercontent')}</h3>
{form_start action='admin_ordercontent' id="theform"}
<input type="hidden" id="orderlist" name="{$actionid}orderlist" value="" />
<div class="pageinfo">{_ld($_module,'info_ordercontent')}</div>
<div class="pageinput postgap">
  <button type="submit" name="{$actionid}submit" id="btn_submit" class="adminsubmit icon check">{_ld($_module,'submit')}</button>
  <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{_ld($_module,'cancel')}</button>
  <button type="submit" name="{$actionid}revert" id="btn_revert" class="adminsubmit icon undo">{_ld($_module,'revert')}</button>
</div>
<div class="pageoverflow">
  {$list = $tree->getChildren(false,true)}
  <ul id="masterlist" class="sortableList sortable">
    {display_tree list=$list depth=0}
  </ul>
</div>
{if $list|count > 10}
 <div class="pageinput pregap">
  <button type="submit" name="{$actionid}submit" id="btn_submit" class="adminsubmit icon check">{_ld($_module,'submit')}</button>
  <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{_ld($_module,'cancel')}</button>
  <button type="submit" name="{$actionid}revert" id="btn_revert" class="adminsubmit icon undo">{_ld($_module,'revert')}</button>
 </div>
{/if}
</form>
