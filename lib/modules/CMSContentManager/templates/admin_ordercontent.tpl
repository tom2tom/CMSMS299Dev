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
<input type="hidden" id="orderlist" name="{$actionid}orderlist" value=""/>
<div class="information">
  {$mod->Lang('info_ordercontent')}
</div>
<br />
<div class="pageoverflow">
  <p class="pageinput">
    <button type="submit" role="button" id="btn_submit" name="{$actionid}submit" value="{$mod->Lang('submit')}" class="pagebutton ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary">
     <span class="ui-button-icon-primary ui-icon ui-icon-circle-check"></span>
     <span class="ui-button-text">{$mod->Lang('submit')}</span>
    </button>
    <button type="submit" role="button" name="{$actionid}cancel" value="{$mod->Lang('cancel')}" class="pagebutton ui-button ui-widget ui-corner-all ui-button-text-icon-primary">
     <span class="ui-button-icon-primary ui-icon ui-icon-circle-close"></span>
     <span class="ui-button-text">{$mod->Lang('cancel')}</span>
    </button>
    <button type="submit" role="button" id="btn_revert" name="{$actionid}revert" value="{$mod->Lang('revert')}" class="pagebutton ui-button ui-widget ui-corner-all ui-button-text-icon-primary">
     <span class="ui-button-icon-primary ui-icon ui-icon-circle-triangle-n"></span>
     <span class="ui-button-text">{$mod->Lang('revert')}</span>
    </button>
  </p>
</div>
<div class="pageoverflow">
  {$list = $tree->getChildren(false,true)}
  <ul id="masterlist" class="sortableList sortable">
    {display_tree list=$list}
  </ul>
</div>
<br />
<div class="pageoverflow">
  <p class="pageinput">
    <button type="submit" role="button" id="btn_submit" name="{$actionid}submit" value="{$mod->Lang('submit')}" class="pagebutton ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary">
     <span class="ui-button-icon-primary ui-icon ui-icon-circle-check"></span>
     <span class="ui-button-text">{$mod->Lang('submit')}</span>
    </button>
    <button type="submit" role="button" name="{$actionid}cancel" value="{$mod->Lang('cancel')}" class="pagebutton ui-button ui-widget ui-corner-all ui-button-text-icon-primary">
     <span class="ui-button-icon-primary ui-icon ui-icon-circle-close"></span>
     <span class="ui-button-text">{$mod->Lang('cancel')}</span>
    </button>
    <button type="submit" role="button" id="btn_revert" name="{$actionid}revert" value="{$mod->Lang('revert')}" class="pagebutton ui-button ui-widget ui-corner-all ui-button-text-icon-primary">
     <span class="ui-button-icon-primary ui-icon ui-icon-circle-triangle-n"></span>
     <span class="ui-button-text">{$mod->Lang('revert')}</span>
    </button>
  </p>
</div>
{form_end}
