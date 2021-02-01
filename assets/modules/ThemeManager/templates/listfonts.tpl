<table id="tpllist" class="pagetable" style="width:auto;">
 <thead>
  <tr>
   <th>{$title_name}</th>
   <th>{$title_created}</th>
   <th>{$title_modified}</th>
   <th>{$title_custom}</th>
   <th>{$title_pages}</th>{* TODO styles *}
   <th></th>
  </tr>
 </thead>
 <tbody>
 {foreach $fontitems as $entry}{cycle values='row1,row2' assign='rowclass'}
  <tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
   {strip}
   <td>{$entry.name}</td>
   <td>{$entry.created|cms_date_format|cms_escape}</td>
   <td>{$entry.modified|cms_date_format|cms_escape}</td>
   <td>{$entry.custom}</td>
   <td>{$entry.usage}</td>
   <td>
    <span class="action" context-menu="Font-{$entry.id}">{admin_icon icon='menu.gif' alt='menu' title=$help_menu class='systemicon'}</span>
   </td>
{/strip}
  </tr>
{/foreach}
 </tbody>
</table>
