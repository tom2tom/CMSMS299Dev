<script type="text/javascript">
//<![CDATA[{literal}
function parseTree(ul) {
  var tags = [];
  ul.children('li').each(function() {
    var subtree = $(this).children('ul');
    tags.push($(this).attr('id'));
    if (subtree.size() > 0) {
      tags.push(parseTree(subtree));
    }
  });
  return tags;
}

$(document).ready(function() {
  $(document).on('click', '#btn_submit', function(ev) {
    ev.preventDefault();
    var form = $(this).closest('form');
    cms_confirm({/literal}'{$mod->Lang("confirm_reorder")|escape:"javascript"}'{literal}).done(function() {
      var tree = JSON.stringify(parseTree($('#masterlist'))); //IE8+
      $('#orderlist').val(tree);
      form.submit();
    });
  });

  $(document).on('click', '.haschildren', function(ev) {
    ev.preventDefault();
    var list = $(this).closest('div.label').next('ul');
    if ($(this).hasClass('expanded')) {
      // currently expanded... so now collapse
      list.hide();
      $(this).removeClass('expanded').addClass('collapsed').text('+');
    } else {
      // currently collapsed... so now expand
      list.show();
      $(this).removeClass('collapsed').addClass('expanded').text('-');
    }
  });

  $('ul.sortable').nestedSortable({
    disableNesting: 'no-nest',
    forcePlaceholderSize: true,
    handle: 'div',
    items: 'li',
    opacity: 0.6,
    placeholder: 'placeholder',
    tabSize: 20,
    tolerance: 'pointer',
    listType: 'ul',
    toleranceElement: '> div'
  });
});
{/literal}//]]>
</script>

{function display_tree depth=0}
  {foreach $list as $node}
    {$obj=$node->getContent(false,true,false)}
    <li id="page_{$obj->Id()}" {if !$obj->WantsChildren()}class="no-nest"{/if}>
      <div class="label" {if !$obj->Active()}style="color: red;"{/if}>
        <span>&nbsp;</span>{$obj->Hierarchy()}:&nbsp;{$obj->Name()|cms_escape}{if !$obj->Active()}&nbsp;({$mod->Lang('prompt_inactive')}){/if} <em>({$obj->MenuText()|cms_escape})</em>
        {if $node->has_children()}
          <span class="haschildren expanded">-</span>
        {/if}
      </div>
      {if $node->has_children()}
      <ul>
        {$list=$node->getChildren(false,true)}
        {display_tree list=$list depth=$depth+1}
      </ul>
      {/if}
    </li>
  {/foreach}
{/function}

<h3>{$mod->Lang('prompt_ordercontent')}</h3>
{form_start action='admin_ordercontent' id="theform"}
<input type="hidden" id="orderlist" name="{$actionid}orderlist" value="" />
<div class="pageinfo">{$mod->Lang('info_ordercontent')}</div>
<div class="bottomsubmits">
  <p class="pageinput">
    <button type="submit" name="{$actionid}submit" id="btn_submit" class="adminsubmit icon check">{$mod->Lang('submit')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{$mod->Lang('cancel')}</button>
    <button type="submit" name="{$actionid}revert" id="btn_revert" class="adminsubmit icon undo">{$mod->Lang('revert')}</button>
  </p>
</div>
<div class="pageoverflow">
  {$list = $tree->getChildren(false,true)}
  <ul id="masterlist" class="sortableList sortable">
    {display_tree list=$list}
  </ul>
</div>
{if $list|count > 10}
<div class="bottomsubmits">
  <p class="pageinput">
    <button type="submit" name="{$actionid}submit" id="btn_submit" class="adminsubmit icon check">{$mod->Lang('submit')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{$mod->Lang('cancel')}</button>
    <button type="submit" name="{$actionid}revert" id="btn_revert" class="adminsubmit icon undo">{$mod->Lang('revert')}</button>
  </p>
</div>
{/if}
{form_end}
