{function display_tree}
  {foreach $list as $node}
    {$obj=$node->getContent()}{$desc=$node->has_children()}
    <li id="page_{$obj->Id()}"{if !$obj->WantsChildren()} class="no-nest"{/if}>
      <div class="label{if !$obj->Active()} red{/if}">
        <span class="{if $desc}haschildren expanded{else}nomarker{/if}"></span>&nbsp;
        {$obj->Hierarchy()}:&nbsp;
        {$name=$obj->Name()}{$name|cms_escape}
        {if $pcount <= 30}
        {$txt=$obj->MenuText()}{if $txt && $txt!=$name}&nbsp;<em>({$txt|cms_escape})</em>{/if}
        {/if}
        {if !$obj->Active()}&nbsp;({_ld($_module,'prompt_inactive')}){/if}
      </div>
      {if $desc}<ul>
        {$list=$node->getChildren(false,true)}{display_tree list=$list depth=$depth+1}
      </ul>{/if}
    </li>
  {/foreach}
{/function}

<h3>{_ld($_module,'prompt_ordercontent')}</h3>
{form_start action='ordercontent' id="theform"}
<input type="hidden" id="orderlist" name="{$actionid}orderlist" value="" />
<div class="pageinfo">{_ld($_module,'info_ordercontent')}</div>
<div class="pageinput postgap">
  <button type="submit" name="{$actionid}submit" class="adminsubmit icon check btn_submit">{_ld($_module,'submit')}</button>
  <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{_ld($_module,'cancel')}</button>
  <button type="submit" name="{$actionid}revert" class="adminsubmit icon undo btn_revert">{_ld($_module,'revert')}</button>
  <button type="button" class="adminsubmit icon do btn_expall">{_ld($_module,'expandall')}</button>
  <button type="button" class="adminsubmit icon do btn_expnone">{_ld($_module,'contractall')}</button>
{if $dir == 'rtl'}
  {admin_icon icon='icons/extra/search' alt="{_ld('layout','search')}" addtext='style=position:relative;left:1.8em'}
{/if}
  <input type="text" id="ajax_find" title="{_ld($_module,'title_listcontent_find')}" size="15" maxlength="20" placeholder="{_ld('layout','search')}" />
{if $dir != 'rtl'}
  {admin_icon icon='icons/extra/search' alt="{_ld('layout','search')}" addtext='style=position:relative;left:-1.8em'}
{/if}
</div>
<div class="pageoverflow">
  <ul id="masterlist" class="sortableList sortable">
   {$list=$hier->getChildren(false,true)}{display_tree list=$list depth=0}
  </ul>
</div>
{if $pcount > 10}
 <div class="pageinput pregap">
  <button type="submit" name="{$actionid}submit" class="adminsubmit icon check btn_submit">{_ld($_module,'submit')}</button>
  <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{_ld($_module,'cancel')}</button>
  <button type="submit" name="{$actionid}revert" class="adminsubmit icon undo btn_revert">{_ld($_module,'revert')}</button>
  <button type="button" class="adminsubmit icon do btn_expall">{_ld($_module,'expandall')}</button>
  <button type="button" class="adminsubmit icon do btn_expnone">{_ld($_module,'contractall')}</button>
 </div>
{/if}
</form>
