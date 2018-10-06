<div class="pageoverflow">
<table class="pagetable">
  <thead>
    <tr>
      <th style="width:75%;">{$nameprompt}</th>
      <th>{$defaultprompt}</th>
      <th class="pageicon">&nbsp;</th>
      <th class="pageicon">&nbsp;</th>
    </tr>
  </thead>
{foreach $items as $entry}
   <tr class="{cycle values='row1,row2'}">
     <td>{$entry->name}</td>
     <td>{$entry->default}</td>
     <td>{$entry->editlink}</td>
     <td>{$entry->deletelink}</td>
   </tr>
{/foreach}
</table>
</div>
<div class="pageoverflow">
  <p class="pageoptions">{$newtemplatelink}</p>
</div>
