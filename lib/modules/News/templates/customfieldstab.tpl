<script type="text/javascript">
{literal}//<![CDATA[
$(document).ready(function() {
  $('a.del_fielddef').click(function(ev) {
    var self = $(this);
    ev.preventDefault();
    cms_confirm({/literal}'{$mod->Lang("areyousure")}','{$mod->Lang("yes")}'{literal}).done(function() {
      window.location = self.attr('href');
    });
  });
});
{/literal}//]]>
</script>

{if $itemcount > 0}
<table class="pagetable">
  <thead>
    <tr>
      <th>{$fielddeftext}</th>
      <th>{$typetext}</th>
      <th class="pageicon">&nbsp;</th>
      <th class="pageicon">&nbsp;</th>
      <th class="pageicon">&nbsp;</th>
      <th class="pageicon">&nbsp;</th>
    </tr>
  </thead>
  <tbody>
    {foreach $items as $entry}
    <tr class="{cycle values='row1,row2'}">
      <td>{$entry->name}</td>
      <td>{$entry->type}</td>
      <td>{$entry->uplink}</td>
      <td>{$entry->downlink}</td>
      <td>{$entry->editlink}</td>
      <td><a href="{$entry->delete_url}" class="del_fielddef">{admin_icon icon='delete.gif' alt=$mod->Lang('delete')}</a></td>
    </tr>
    {/foreach}
  </tbody>
</table>
{/if}

<div class="pageoptions">
  <a href="{$addurl}" title="{$mod->Lang('addfielddef')}">{admin_icon icon='newobject.gif'} {$mod->Lang('addfielddef')}</a>
</div>
