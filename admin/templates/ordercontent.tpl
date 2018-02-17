<script type="text/javascript"> //OK
//<![CDATA[
var ajax_url = 'ordercontent.php{$urlext}';
{literal}
function parseTree(ul) {
  var tags = [];
  ul.children('li').each(function() {
    var subtree =  $(this).children('ul');
    if(subtree.size() > 0) {
      tags.push([$(this).attr('id'), parseTree(subtree)]);
    } else {
      tags.push($(this).attr('id'));
    }
  });
  return tags;
}

$(document).ready(function() {
  $('ul.sortable').nestedSortable({
    disableNesting: 'no-nest',
    forcePlaceholderSize: true,
    handle: 'div',
    items: 'li',
    opacity: 0.6,
    placeholder: 'placeholder',
    tabSize: 25,
    tolerance: 'pointer',
    listType: 'ul',
    toleranceElement: '> div'
  });

  $(document).on('click','[name*=submit]', function() {
    var tree = JSON.stringify(parseTree($('ul.sortable'))); //IE8+
    var ajax_res = false;
    $.ajax({
      type: 'POST',
      url:  ajax_url,
      data: {data: tree},
      cache: false,
      async: false,
      success: function(res) {
         ajax_res = true;
      }
    });
    return ajax_res;
  });
});
{/literal}//]]>
</script>

<div class="pagecontainer">
  {*$showheader TODO Rolf 29-12-16*}
  <form action="ordercontent.php{$urlext}" method="post">
    <div class="pageoverflow">
      <button type="submit" name="submit" class="adminsubmit iconcheck">{lang('submit')}</button>
      <button type="submit" name="cancel" class="adminsubmit iconcancel">{lang('cancel')}</button>
      <button type="submit" name="revert" class="adminsubmit iconundo">{lang('revert')}</button>
    </div>

    <div class="reorder-pages">
      {include file="ordercontent_tree.tpl" list=$tree->getChildren(false,true) depth=1}
    </div>

    <div class="pageoverflow">
      <button type="submit" name="submit" class="adminsubmit iconcheck">{lang('submit')}</button>
      <button type="submit" name="cancel" class="adminsubmit iconcancel">{lang('cancel')}</button>
      <button type="submit" name="revert" class="adminsubmit iconundo">{lang('revert')}</button>
    </div>
  </form>
</div>
